<?php
session_start();
require_once __DIR__ . '/../connect.php';

header('Content-Type: application/json'); 

$response = ['status' => 'error', 'message' => 'Не вдалося обробити запит.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Помилка: Користувач не авторизований.';
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_name = trim($_POST['course_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $author_id = $_SESSION['user_id'];

    if (empty($course_name)) {
        $response['message'] = 'Назва курсу не може бути порожньою.';
        echo json_encode($response);
        exit();
    }
    if (mb_strlen($course_name) > 70) { 
        $response['message'] = 'Назва курсу занадто довга (максимум 70 символів).';
        echo json_encode($response);
        exit();
    }
     if (mb_strlen($description) > 1000) { 
        $response['message'] = 'Опис курсу занадто довгий (максимум 1000 символів).';
        echo json_encode($response);
        exit();
    }


    function generateJoinCode($conn, $length = 8) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        do {
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            $stmt_check = $conn->prepare("SELECT course_id FROM courses WHERE join_code = ?");
            $stmt_check->bind_param("s", $randomString);
            $stmt_check->execute();
            $stmt_check->store_result();
        } while ($stmt_check->num_rows > 0);
        $stmt_check->close();
        return $randomString;
    }
    $join_code = generateJoinCode($conn);

 
    $default_colors = ['#f0ad4e', '#5cb85c', '#5bc0de', '#d9534f', '#ba68c8', '#7986cb', '#4db6ac', '#a1887f', '#ff8a65', '#9575cd'];
    $color_classes = ['course-color-orange', 'course-color-green', 'course-color-lblue', 'course-color-red', 'course-color-purple', 'course-color-indigo', 'course-color-teal', 'course-color-brown', 'course-color-deeporange', 'course-color-deeppurple'];
    $randomIndex = array_rand($default_colors);
    $course_color_hex = $default_colors[$randomIndex]; 
    $course_color_class = $color_classes[$randomIndex];


    $sql = "INSERT INTO courses (course_name, author_id, join_code, description, color, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sisss", $course_name, $author_id, $join_code, $description, $course_color_hex);
        if ($stmt->execute()) {
            $new_course_id = $stmt->insert_id;
            $response = [
                'status' => 'success',
                'message' => 'Курс успішно створено!',
                'course' => [
                    'id' => $new_course_id,
                    'name' => $course_name,
                    'description' => $description,
                    'join_code' => $join_code,
                    'color_class' => $course_color_class, 
                    'author_username' => $_SESSION['username'] ?? 'Автор' 
                ]
            ];
        } else {
            $response['message'] = "Помилка створення курсу: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = "Помилка підготовки запиту: " . $conn->error;
    }
} else {
    $response['message'] = 'Некоректний метод запиту.';
}

$conn->close();
echo json_encode($response);
exit();
?>