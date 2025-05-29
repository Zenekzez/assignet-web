<?php
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

function isUserTeacherOfCourse($conn, $userId, $courseId) {
    $stmt = $conn->prepare("SELECT author_id FROM courses WHERE course_id = ? AND author_id = ?");
    if (!$stmt) return false;
    $stmt->bind_param("ii", $courseId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result->num_rows > 0;
}

if ($action === 'create_announcement') {
    // ... (код як у попередній відповіді) ...
     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
        $content = trim($_POST['announcement_content'] ?? '');

        if (!$course_id || empty($content)) {
            $response['message'] = 'ID курсу або вміст оголошення не можуть бути порожніми.';
        } elseif (!isUserTeacherOfCourse($conn, $current_user_id, $course_id)) {
            $response['message'] = 'У вас немає прав для публікації оголошень в цьому курсі.';
        } else {
            $stmt = $conn->prepare("INSERT INTO course_announcements (course_id, user_id, content) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iis", $course_id, $current_user_id, $content);
                if ($stmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Оголошення успішно опубліковано!';
                    $response['announcement'] = [
                        'announcement_id' => $stmt->insert_id,
                        'course_id' => $course_id,
                        'user_id' => $current_user_id,
                        'content' => htmlspecialchars($content),
                        'created_at' => date('Y-m-d H:i:s'), 
                        'author_username' => $_SESSION['username'] 
                    ];
                } else {
                    $response['message'] = 'Помилка публікації оголошення: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $response['message'] = 'Помилка підготовки запиту: ' . $conn->error;
            }
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для створення оголошення.';
    }
} elseif ($action === 'get_announcements') {
    // ... (код як у попередній відповіді) ...
     if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

        if (!$course_id) {
            $response['message'] = 'ID курсу не вказано.';
        } else {
            $stmt = $conn->prepare("SELECT ca.*, u.username AS author_username 
                                    FROM course_announcements ca
                                    JOIN users u ON ca.user_id = u.user_id
                                    WHERE ca.course_id = ? 
                                    ORDER BY ca.created_at DESC");
            if ($stmt) {
                $stmt->bind_param("i", $course_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $announcements = [];
                while ($row = $result->fetch_assoc()) {
                    $row['content'] = htmlspecialchars($row['content']); 
                    $announcements[] = $row;
                }
                $response['status'] = 'success';
                $response['announcements'] = $announcements;
                unset($response['message']); 
                $stmt->close();
            } else {
                $response['message'] = 'Помилка отримання оголошень: ' . $conn->error;
            }
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для отримання оголошень.';
    }
} elseif ($action === 'update_course_settings') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $course_id = filter_input(INPUT_POST, 'course_id_settings', FILTER_VALIDATE_INT);
        $course_name = trim($_POST['course_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#007bff');
        $join_code_visible = isset($_POST['join_code_visible']) && $_POST['join_code_visible'] == '1' ? 1 : 0; // Обробка чекбоксу

        if (!$course_id || empty($course_name)) {
            $response['message'] = 'ID курсу або назва курсу не можуть бути порожніми.';
        } elseif (!preg_match('/^#[0-9A-Fa-f]{6}$/i', $color)) { // Додав i для нечутливості до регістру
             $response['message'] = 'Некоректний формат кольору. Очікується HEX (напр. #RRGGBB).';
        } elseif (!isUserTeacherOfCourse($conn, $current_user_id, $course_id)) {
            $response['message'] = 'У вас немає прав для зміни налаштувань цього курсу.';
        } else {
            $stmt = $conn->prepare("UPDATE courses SET course_name = ?, description = ?, color = ?, join_code_visible = ? WHERE course_id = ?");
            if ($stmt) {
                $stmt->bind_param("sssii", $course_name, $description, $color, $join_code_visible, $course_id);
                if ($stmt->execute()) {
                     $response['status'] = 'success'; // Навіть якщо нічого не змінилося, запит успішний
                     $response['message'] = $stmt->affected_rows > 0 ? 'Налаштування курсу успішно оновлено!' : 'Дані не змінилися або вже були оновлені.';
                     $response['updated_data'] = [
                         'course_name' => $course_name,
                         'description' => $description,
                         'color' => $color,
                         'join_code_visible' => (bool)$join_code_visible
                     ];
                } else {
                    $response['message'] = 'Помилка оновлення налаштувань: ' . $stmt->error;
                    error_log("Course settings update error: " . $stmt->error);
                }
                $stmt->close();
            } else {
                $response['message'] = 'Помилка підготовки запиту: ' . $conn->error;
                error_log("Course settings prepare error: " . $conn->error);
            }
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для оновлення налаштувань.';
    }
}

$conn->close();
echo json_encode($response);
?>