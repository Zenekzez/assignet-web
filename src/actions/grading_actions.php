<?php
// File: src/grading_actions.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Невідома дія або помилка.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Користувач не авторизований.';
    echo json_encode($response);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;

if (!$action) {
    $response['message'] = 'Дію не вказано.';
    echo json_encode($response);
    exit();
}

if (!function_exists('isUserTeacherOfCourse')) {
    function isUserTeacherOfCourse($conn, $userId, $courseId) {
        $stmt_check_teacher = $conn->prepare("SELECT author_id FROM courses WHERE course_id = ? AND author_id = ?");
        if (!$stmt_check_teacher) {
            error_log("isUserTeacherOfCourse prepare failed: " . $conn->error);
            return false;
        }
        $stmt_check_teacher->bind_param("ii", $courseId, $userId);
        $stmt_check_teacher->execute();
        $result_check_teacher = $stmt_check_teacher->get_result();
        $is_teacher = $result_check_teacher->num_rows > 0;
        $stmt_check_teacher->close();
        return $is_teacher;
    }
}

function isUserTeacherForSubmission($conn, $userId, $submissionId) {
    $stmt = $conn->prepare(
        "SELECT c.author_id
         FROM submissions s
         JOIN assignments a ON s.assignment_id = a.assignment_id
         JOIN courses c ON a.course_id = c.course_id
         WHERE s.submission_id = ?"
    );
    if (!$stmt) {
        error_log("isUserTeacherForSubmission prepare failed: " . $conn->error);
        return false;
    }
    $stmt->bind_param("i", $submissionId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($course_info = $result->fetch_assoc()) {
        $is_teacher = ($course_info['author_id'] == $userId);
        $stmt->close();
        return $is_teacher;
    }
    $stmt->close();
    return false;
}

function isUserStudentOfCourse($conn, $userId, $courseId) {
    $stmt = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE course_id = ? AND student_id = ?");
    if (!$stmt) {
        error_log("isUserStudentOfCourse prepare failed: " . $conn->error);
        return false;
    }
    $stmt->bind_param("ii", $courseId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_enrolled = $result->num_rows > 0;
    $stmt->close();
    return $is_enrolled;
}


if ($action === 'get_submission_for_grading') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $submission_id = filter_input(INPUT_GET, 'submission_id', FILTER_VALIDATE_INT);

        if (!$submission_id) {
            $response['message'] = 'ID зданої роботи не вказано.';
            echo json_encode($response);
            exit();
        }

        if (!isUserTeacherForSubmission($conn, $current_user_id, $submission_id)) {
            $response['message'] = 'У вас немає прав для перегляду або оцінювання цієї роботи.';
            echo json_encode($response);
            exit();
        }

        $sql = "SELECT
                    s.submission_id, s.assignment_id, s.student_id, s.submission_date, s.file_path,
                    s.submission_text, s.status AS submission_status, s.grade, s.graded_at,
                    a.title AS assignment_title, a.description AS assignment_description, a.max_points, a.course_id, a.due_date AS assignment_due_date,
                    u.username AS student_username, u.first_name AS student_first_name, u.last_name AS student_last_name, u.avatar_path AS student_avatar_path,
                    c.course_name
                FROM submissions s
                JOIN assignments a ON s.assignment_id = a.assignment_id
                JOIN users u ON s.student_id = u.user_id
                JOIN courses c ON a.course_id = c.course_id
                WHERE s.submission_id = ?";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $submission_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($submission_data = $result->fetch_assoc()) {
                if ($submission_data['grade'] !== null) {
                    $submission_data['grade'] = intval($submission_data['grade']);
                }
                $response['status'] = 'success';
                $response['submission_details'] = $submission_data;
                unset($response['message']);
            } else {
                $response['message'] = 'Здану роботу не знайдено.';
            }
            $stmt->close();
        } else {
            $response['message'] = 'Помилка підготовки запиту для отримання даних роботи: ' . $conn->error;
            error_log("DB get_submission_for_grading prepare error: " . $conn->error);
        }
    } else {
        $response['message'] = 'Некоректний метод запиту.';
    }
} elseif ($action === 'save_grade_and_feedback') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submission_id = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
        $grade_input = $_POST['grade'] ?? null;

        if (!$submission_id) {
            $response['message'] = 'ID зданої роботи не вказано.';
            echo json_encode($response);
            exit();
        }

        if (!isUserTeacherForSubmission($conn, $current_user_id, $submission_id)) {
            $response['message'] = 'У вас немає прав для оцінювання цієї роботи.';
            echo json_encode($response);
            exit();
        }

        $stmt_max_points = $conn->prepare(
            "SELECT a.max_points
             FROM submissions s
             JOIN assignments a ON s.assignment_id = a.assignment_id
             WHERE s.submission_id = ?"
        );
        $max_points = 100;
        if($stmt_max_points){
            $stmt_max_points->bind_param("i", $submission_id);
            $stmt_max_points->execute();
            $result_max_points = $stmt_max_points->get_result();
            if($assignment_info = $result_max_points->fetch_assoc()){
                $max_points = intval($assignment_info['max_points']);
            }
            $stmt_max_points->close();
        } else {
            $response['message'] = 'Не вдалося отримати максимальну кількість балів для завдання.';
            error_log("DB get_max_points for grading prepare error: " . $conn->error);
            echo json_encode($response);
            exit();
        }

        $grade_to_save = null;
        if ($grade_input !== '' && $grade_input !== null) {
            if (!is_numeric($grade_input) || floor($grade_input) != $grade_input) {
                $response['message'] = 'Оцінка повинна бути цілим числом.';
                echo json_encode($response);
                exit();
            }
            $grade_to_save = intval($grade_input);
            if ($grade_to_save < 0 || $grade_to_save > $max_points) {
                $response['message'] = "Оцінка повинна бути в межах від 0 до " . $max_points . ".";
                echo json_encode($response);
                exit();
            }
        } elseif ($grade_input === '') {
             $grade_to_save = null;
        }

        $sql_update = "UPDATE submissions SET grade = ?, graded_at = NOW(), status = 'graded' WHERE submission_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update) {
            if ($grade_to_save === null) {
                 $stmt_update->bind_param("si", $grade_to_save, $submission_id);
            } else {
                 $stmt_update->bind_param("ii", $grade_to_save, $submission_id);
            }

            if ($stmt_update->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Оцінку успішно збережено.';
                $response['updated_grade'] = $grade_to_save;
            } else {
                $response['message'] = 'Помилка збереження оцінки: ' . $stmt_update->error;
                error_log("DB save_grade_and_feedback execute error: " . $stmt_update->error);
            }
            $stmt_update->close();
        } else {
            $response['message'] = 'Помилка підготовки запиту для збереження оцінки: ' . $conn->error;
            error_log("DB save_grade_and_feedback prepare error: " . $conn->error);
        }
    } else {
        $response['message'] = 'Некоректний метод запиту.';
    }
} elseif ($action === 'get_my_grades_for_course') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
        $student_id = $current_user_id;

        if (!$course_id) {
            $response['message'] = 'ID курсу не вказано.';
            echo json_encode($response);
            exit();
        }

        if (!isUserStudentOfCourse($conn, $student_id, $course_id)) {
            $response['message'] = 'Ви не є студентом цього курсу або курс не знайдено.';
            echo json_encode($response);
            exit();
        }

        $grades_data = [];
        $sql_final = "SELECT
                    a.assignment_id,
                    a.title AS assignment_title,
                    a.max_points,
                    a.due_date,
                    s_latest.submission_id,
                    s_latest.status AS submission_status,
                    s_latest.grade,
                    s_latest.submission_date
                FROM assignments a
                LEFT JOIN (
                    SELECT s1.*
                    FROM submissions s1
                    INNER JOIN (
                        SELECT
                            assignment_id,
                            student_id,
                            MAX(submission_date) AS max_submission_date
                        FROM submissions
                        WHERE student_id = ?
                        GROUP BY assignment_id, student_id
                    ) s2 ON s1.assignment_id = s2.assignment_id
                           AND s1.student_id = s2.student_id
                           AND s1.submission_date = s2.max_submission_date
                    WHERE s1.student_id = ?
                ) s_latest ON a.assignment_id = s_latest.assignment_id
                WHERE a.course_id = ?
                ORDER BY ISNULL(a.due_date), a.due_date ASC, a.created_at ASC";

        $stmt = $conn->prepare($sql_final);
        if ($stmt) {
            $stmt->bind_param("iii", $student_id, $student_id, $course_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                if ($row['grade'] !== null) {
                    $row['grade'] = intval($row['grade']);
                }

                $is_submitted_or_graded = in_array($row['submission_status'], ['submitted', 'graded']);
                $due_date_obj = $row['due_date'] ? new DateTime($row['due_date']) : null;
                $now = new DateTime();

                if ($due_date_obj && $due_date_obj < $now && !$is_submitted_or_graded) {
                    $row['submission_status'] = 'missed';
                } elseif (empty($row['submission_status'])) {
                    if ($due_date_obj && $due_date_obj < $now) {
                        $row['submission_status'] = 'missed';
                    } else {
                        $row['submission_status'] = 'pending_submission';
                    }
                }
                $grades_data[] = $row;
            }
            $stmt->close();
            $response['status'] = 'success';
            $response['grades'] = $grades_data;
            unset($response['message']);
        } else {
            $response['message'] = 'Помилка підготовки запиту для отримання оцінок: ' . $conn->error;
            error_log("DB get_my_grades_for_course prepare error: " . $conn->error);
        }
    } else {
        $response['message'] = 'Некоректний метод запиту.';
    }
} elseif ($action === 'get_course_grades_summary') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

        if (!$course_id) {
            $response['message'] = 'ID курсу не вказано.';
            echo json_encode($response);
            exit();
        }

        if (!isUserTeacherOfCourse($conn, $current_user_id, $course_id)) {
            $response['message'] = 'У вас немає прав для перегляду журналу оцінок цього курсу.';
            echo json_encode($response);
            exit();
        }

        $assignments_list = [];
        $students_grades_list = [];

        // 1. Отримати список завдань курсу (для заголовків таблиці)
        // ВИПРАВЛЕНО: Додано due_date та created_at до SELECT
        $stmt_assignments = $conn->prepare("SELECT assignment_id, title, max_points, due_date, created_at FROM assignments WHERE course_id = ? ORDER BY ISNULL(due_date), due_date ASC, created_at ASC");
        if ($stmt_assignments) {
            $stmt_assignments->bind_param("i", $course_id);
            $stmt_assignments->execute();
            $result_assignments = $stmt_assignments->get_result();
            while ($assignment = $result_assignments->fetch_assoc()) {
                $assignments_list[] = $assignment;
            }
            $stmt_assignments->close();
        } else {
            $response['message'] = 'Помилка отримання списку завдань: ' . $conn->error;
            error_log("DB get_course_grades_summary (assignments) prepare error: " . $conn->error);
            echo json_encode($response);
            exit();
        }

        // 2. Отримати список студентів курсу
        $stmt_students = $conn->prepare(
            "SELECT u.user_id, u.first_name, u.last_name, u.username, u.avatar_path
             FROM users u
             JOIN enrollments e ON u.user_id = e.student_id
             WHERE e.course_id = ?
             ORDER BY u.last_name, u.first_name"
        );
        if ($stmt_students) {
            $stmt_students->bind_param("i", $course_id);
            $stmt_students->execute();
            $result_students = $stmt_students->get_result();

            $stmt_submission_grade = $conn->prepare(
                "SELECT s.submission_id, s.grade, s.status
                 FROM submissions s
                 WHERE s.assignment_id = ? AND s.student_id = ?
                 ORDER BY s.submission_date DESC
                 LIMIT 1"
            );

            if (!$stmt_submission_grade) {
                $response['message'] = 'Помилка підготовки запиту для отримання оцінок студентів: ' . $conn->error;
                error_log("DB get_course_grades_summary (submission_grade) prepare error: " . $conn->error);
                $stmt_students->close();
                echo json_encode($response);
                exit();
            }

            while ($student = $result_students->fetch_assoc()) {
                $student_data = [
                    'user_id' => $student['user_id'],
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'username' => $student['username'],
                    'avatar_path' => $student['avatar_path'],
                    'grades_by_assignment_id' => []
                ];

                foreach ($assignments_list as $assignment) { // $assignment тепер містить 'due_date' та 'created_at'
                    $assignment_id_current = $assignment['assignment_id'];
                    $stmt_submission_grade->bind_param("ii", $assignment_id_current, $student['user_id']);
                    $stmt_submission_grade->execute();
                    $result_grade = $stmt_submission_grade->get_result();

                    $grade_info = ['grade' => null, 'submission_id' => null, 'status' => 'pending_submission'];
                    if ($submission = $result_grade->fetch_assoc()) {
                        $grade_info['grade'] = ($submission['grade'] !== null) ? intval($submission['grade']) : null;
                        $grade_info['submission_id'] = $submission['submission_id'];
                        $grade_info['status'] = $submission['status'];
                    }

                    // Використовуємо $assignment['due_date'] безпосередньо
                    if ($assignment['due_date'] && new DateTime($assignment['due_date']) < new DateTime() &&
                        !in_array($grade_info['status'], ['submitted', 'graded'])) {
                        $grade_info['status'] = 'missed';
                    } elseif ($grade_info['status'] === 'pending_submission' &&
                               $assignment['due_date'] && new DateTime($assignment['due_date']) < new DateTime()){
                        $grade_info['status'] = 'missed';
                    }

                    $student_data['grades_by_assignment_id'][$assignment_id_current] = $grade_info;
                }
                $students_grades_list[] = $student_data;
            }
            $stmt_submission_grade->close();
            $stmt_students->close();

            $response['status'] = 'success';
            $response['assignments'] = $assignments_list;
            $response['students_grades'] = $students_grades_list;
            unset($response['message']);

        } else {
            $response['message'] = 'Помилка отримання списку студентів: ' . $conn->error;
            error_log("DB get_course_grades_summary (students) prepare error: " . $conn->error);
            echo json_encode($response);
            exit();
        }
    } else {
        $response['message'] = 'Некоректний метод запиту.';
    }
} else {
    $response['message'] = "Невідома дія: " . htmlspecialchars($action);
}

$conn->close();
echo json_encode($response);
?>