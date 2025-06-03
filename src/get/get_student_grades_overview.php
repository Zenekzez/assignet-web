<?php
session_start();
require_once __DIR__ . '/../connect.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Не вдалося завантажити огляд оцінок.', 'courses_grades' => []];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Користувач не авторизований.';
    echo json_encode($response);
    exit();
}

$student_id = $_SESSION['user_id'];
$courses_grades_data = [];

try {
    $stmt_courses = $conn->prepare("
        SELECT 
            c.course_id, 
            c.course_name,
            u.username AS teacher_username,
            u.first_name AS teacher_first_name,
            u.last_name AS teacher_last_name
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        JOIN users u ON c.author_id = u.user_id
        WHERE e.student_id = ?
        ORDER BY c.course_name ASC
    ");
    if (!$stmt_courses) {
        throw new Exception("Помилка підготовки запиту курсів: " . $conn->error);
    }
    $stmt_courses->bind_param("i", $student_id);
    $stmt_courses->execute();
    $result_courses = $stmt_courses->get_result();

    $course_ids = [];
    while ($course_row = $result_courses->fetch_assoc()) {
        $course_ids[] = $course_row['course_id'];
        $courses_grades_data[$course_row['course_id']] = [
            'course_id' => $course_row['course_id'],
            'course_name' => htmlspecialchars($course_row['course_name']),
            'teacher_display_name' => htmlspecialchars(trim($course_row['teacher_first_name'] . ' ' . $course_row['teacher_last_name'])),
            'teacher_username' => htmlspecialchars($course_row['teacher_username']),
            'assignments' => []
        ];
    }
    $stmt_courses->close();

    if (empty($course_ids)) {
        $response['status'] = 'success';
        $response['message'] = 'Ви ще не записані на жоден курс.';
        echo json_encode($response);
        exit();
    }

    $course_ids_placeholders = implode(',', array_fill(0, count($course_ids), '?'));
    
    $sql_assignments = "
        SELECT
            a.assignment_id,
            a.title AS assignment_title,
            a.max_points,
            a.due_date,
            a.course_id,
            s.status AS submission_status,
            s.grade AS submission_grade,
            s.submission_date AS last_submission_date
        FROM assignments a
        LEFT JOIN (
            -- Підзапит для отримання останньої здачі для кожного завдання студента
            SELECT 
                s1.assignment_id, s1.student_id, s1.status, s1.submission_date, s1.grade
            FROM submissions s1
            INNER JOIN (
                SELECT assignment_id, student_id, MAX(submission_date) AS max_submission_date
                FROM submissions
                WHERE student_id = ? -- Важливо додати student_id сюди для оптимізації підзапиту
                GROUP BY assignment_id, student_id
            ) s2 ON s1.assignment_id = s2.assignment_id 
                   AND s1.student_id = s2.student_id 
                   AND s1.submission_date = s2.max_submission_date
            WHERE s1.student_id = ? 
        ) s ON a.assignment_id = s.assignment_id
        WHERE a.course_id IN (" . $course_ids_placeholders . ")
        ORDER BY a.course_id, ISNULL(a.due_date), a.due_date ASC, a.created_at ASC
    ";

    $types = str_repeat('i', count($course_ids));
    $stmt_assignments = $conn->prepare($sql_assignments);
    if (!$stmt_assignments) {
        throw new Exception("Помилка підготовки запиту завдань: " . $conn->error);
    }
    
    $bind_params = [$student_id, $student_id]; 
    foreach ($course_ids as $cid) {
        $bind_params[] = $cid;
    }
    $stmt_assignments->bind_param("ii" . $types, ...$bind_params);
    
    $stmt_assignments->execute();
    $result_assignments = $stmt_assignments->get_result();

    $now = new DateTime(); 

    while ($assignment_row = $result_assignments->fetch_assoc()) {
        $current_course_id = $assignment_row['course_id'];
        
        if (is_null($assignment_row['submission_status'])) {
            if ($assignment_row['due_date'] && new DateTime($assignment_row['due_date']) < $now) {
                $assignment_row['submission_status'] = 'missed';
            } else {
                $assignment_row['submission_status'] = 'pending_submission';
            }
        }
        
        if (isset($courses_grades_data[$current_course_id])) {
            $courses_grades_data[$current_course_id]['assignments'][] = [
                'assignment_id' => $assignment_row['assignment_id'],
                'assignment_title' => htmlspecialchars($assignment_row['assignment_title']),
                'max_points' => $assignment_row['max_points'],
                'grade' => ($assignment_row['submission_grade'] !== null) ? (int)$assignment_row['submission_grade'] : null,
                'submission_status' => $assignment_row['submission_status'],
                'due_date' => $assignment_row['due_date'],
                'last_submission_date' => $assignment_row['last_submission_date']
            ];
        }
    }
    $stmt_assignments->close();

    $response['status'] = 'success';
    $response['message'] = 'Огляд оцінок успішно завантажено.';
    $response['courses_grades'] = array_values($courses_grades_data);

} catch (Exception $e) {
    $response['message'] = "Помилка сервера: " . $e->getMessage();
    error_log("Error in get_student_grades_overview.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>