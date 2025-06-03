<?php
session_start();
require_once 'connect.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Не вдалося завантажити завдання.', 'assignments' => []];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Користувач не авторизований.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];
$all_uncompleted_assignments = [];

try {
    $stmt = $conn->prepare("
        SELECT
            a.assignment_id, a.title AS assignment_title, a.description AS assignment_description,
            a.max_points, a.due_date,
            a.section_title AS assignment_section_title,
            a.created_at AS assignment_created_at,
            c.course_id, c.course_name,
            latest_s.status AS submission_status,
            latest_s.submission_date AS last_submission_date,
            latest_s.grade AS submission_grade
        FROM assignments a
        JOIN courses c ON a.course_id = c.course_id
        JOIN enrollments e ON a.course_id = e.course_id
        LEFT JOIN (
            -- Підзапит для отримання останньої здачі для кожного завдання студента
            SELECT s1.assignment_id, s1.student_id, s1.status, s1.submission_date, s1.grade
            FROM submissions s1
            INNER JOIN (
                SELECT assignment_id, student_id, MAX(submission_date) AS max_submission_date
                FROM submissions
                GROUP BY assignment_id, student_id
            ) s2 ON s1.assignment_id = s2.assignment_id AND s1.student_id = s2.student_id AND s1.submission_date = s2.max_submission_date
        ) latest_s ON a.assignment_id = latest_s.assignment_id AND e.student_id = latest_s.student_id
        WHERE e.student_id = ? 
          AND (latest_s.status IS NULL OR latest_s.status NOT IN ('submitted', 'graded'))
        ORDER BY ISNULL(a.due_date), a.due_date ASC, a.created_at DESC
    ");

    if (!$stmt) {
        throw new Exception("Помилка підготовки SQL-запиту: " . $conn->error);
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $now = new DateTime();
    $urgent_threshold_days = 3; 

    while ($assignment = $result->fetch_assoc()) {
        $due_date_obj = $assignment['due_date'] ? new DateTime($assignment['due_date']) : null;
        $category = 'pending'; 

        if ($due_date_obj) {
            if ($due_date_obj < $now) {
                $assignment['submission_status'] = $assignment['submission_status'] ?: 'missed';
                $category = 'overdue'; 
            } else {
                $interval = $now->diff($due_date_obj);
                if (!$interval->invert && $interval->days <= $urgent_threshold_days) {
                    $category = 'urgent'; 
                }
            }
        } else {
            $assignment['submission_status'] = $assignment['submission_status'] ?: 'pending_submission';
        }
        
        $assignment['category_slug'] = $category; 
        $all_uncompleted_assignments[] = $assignment;
    }
    $stmt->close();

    $response['status'] = 'success';
    $response['message'] = 'Завдання успішно завантажено.';
    $response['assignments'] = $all_uncompleted_assignments;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in get_student_all_assignments.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>