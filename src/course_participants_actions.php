<?php
// File: src/course_participants_actions.php
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

// Функція для перевірки, чи є поточний користувач викладачем курсу
function isUserTeacherOfCourse($conn, $userId, $courseId) {
    $stmt = $conn->prepare("SELECT author_id FROM courses WHERE course_id = ? AND author_id = ?");
    if (!$stmt) return false;
    $stmt->bind_param("ii", $courseId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_teacher = $result->num_rows > 0;
    $stmt->close();
    return $is_teacher;
}

// Функція для перевірки, чи може користувач переглядати учасників курсу (викладач або студент курсу)
function canUserViewCourseParticipants($conn, $userId, $courseId) {
    if (isUserTeacherOfCourse($conn, $userId, $courseId)) {
        return true;
    }
    $stmt = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE course_id = ? AND student_id = ?");
    if (!$stmt) return false;
    $stmt->bind_param("ii", $courseId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_enrolled = $result->num_rows > 0;
    $stmt->close();
    return $is_enrolled;
}

if ($action === 'get_course_participants') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

        if (!$course_id) {
            $response['message'] = 'ID курсу не вказано.';
            echo json_encode($response);
            exit();
        }

        if (!canUserViewCourseParticipants($conn, $current_user_id, $course_id)) {
            $response['message'] = 'У вас немає доступу для перегляду учасників цього курсу.';
            echo json_encode($response);
            exit();
        }
        
        $teacher_data = null;
        $students_data = [];

        // Отримати дані викладача
        $stmt_teacher = $conn->prepare(
            "SELECT u.user_id, u.username, u.first_name, u.last_name, u.avatar_path
             FROM users u
             JOIN courses c ON u.user_id = c.author_id
             WHERE c.course_id = ?"
        );
        if ($stmt_teacher) {
            $stmt_teacher->bind_param("i", $course_id);
            $stmt_teacher->execute();
            $result_teacher = $stmt_teacher->get_result();
            if ($teacher = $result_teacher->fetch_assoc()) {
                $teacher_data = $teacher;
            }
            $stmt_teacher->close();
        } else {
            $response['message'] = 'Помилка отримання даних викладача: ' . $conn->error;
            error_log('DB get teacher error in course_participants_actions: ' . $conn->error);
            echo json_encode($response);
            exit();
        }

        // Отримати список студентів
        $stmt_students = $conn->prepare(
            "SELECT u.user_id, u.username, u.first_name, u.last_name, u.avatar_path
             FROM users u
             JOIN enrollments e ON u.user_id = e.student_id
             WHERE e.course_id = ?
             ORDER BY u.last_name, u.first_name"
        );
        if ($stmt_students) {
            $stmt_students->bind_param("i", $course_id);
            $stmt_students->execute();
            $result_students = $stmt_students->get_result();
            while ($student = $result_students->fetch_assoc()) {
                $students_data[] = $student;
            }
            $stmt_students->close();
        } else {
            $response['message'] = 'Помилка отримання списку студентів: ' . $conn->error;
            error_log('DB get students error in course_participants_actions: ' . $conn->error);
            echo json_encode($response);
            exit();
        }

        $response['status'] = 'success';
        $response['teacher'] = $teacher_data;
        $response['students'] = $students_data;
        $response['student_count'] = count($students_data);
        $response['is_current_user_teacher'] = isUserTeacherOfCourse($conn, $current_user_id, $course_id);
        unset($response['message']);

    } else {
        $response['message'] = 'Некоректний метод запиту для отримання учасників.';
    }
} elseif ($action === 'remove_student_from_course') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
        $student_id_to_remove = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);

        if (!$course_id || !$student_id_to_remove) {
            $response['message'] = 'Не вказано ID курсу або ID студента.';
            echo json_encode($response);
            exit();
        }

        if (!isUserTeacherOfCourse($conn, $current_user_id, $course_id)) {
            $response['message'] = 'У вас немає прав для видалення студентів з цього курсу.';
            echo json_encode($response);
            exit();
        }
        
        if ($current_user_id == $student_id_to_remove) {
             $response['message'] = 'Ви не можете видалити себе з курсу таким чином.';
             echo json_encode($response);
             exit();
        }

        $stmt_remove = $conn->prepare("DELETE FROM enrollments WHERE course_id = ? AND student_id = ?");
        if ($stmt_remove) {
            $stmt_remove->bind_param("ii", $course_id, $student_id_to_remove);
            if ($stmt_remove->execute()) {
                if ($stmt_remove->affected_rows > 0) {
                    $response['status'] = 'success';
                    $response['message'] = 'Студента успішно видалено з курсу.';
                } else {
                    $response['message'] = 'Студента не знайдено в цьому курсі або вже видалено.';
                }
            } else {
                $response['message'] = 'Помилка видалення студента: ' . $stmt_remove->error;
                error_log('DB remove student error in course_participants_actions: ' . $stmt_remove->error);
            }
            $stmt_remove->close();
        } else {
            $response['message'] = 'Помилка підготовки запиту для видалення студента: ' . $conn->error;
            error_log('DB remove student prepare error in course_participants_actions: ' . $conn->error);
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для видалення студента.';
    }
} else {
    $response['message'] = "Невідома дія: " . htmlspecialchars($action);
}

$conn->close();
echo json_encode($response);
?>