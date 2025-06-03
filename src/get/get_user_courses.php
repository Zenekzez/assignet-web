<?php
session_start();
require_once 'connect.php'; 

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Не вдалося завантажити курси.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Користувач не авторизований.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];
$teaching_courses = [];
$enrolled_courses = []; 

function getColorClass($hex_color) {
    $default_colors_hex = ['#f0ad4e', '#5cb85c', '#5bc0de', '#d9534f', '#ba68c8', '#7986cb', '#4db6ac', '#a1887f', '#ff8a65', '#9575cd'];
    $color_classes = ['course-color-orange', 'course-color-green', 'course-color-lblue', 'course-color-red', 'course-color-purple', 'course-color-indigo', 'course-color-teal', 'course-color-brown', 'course-color-deeporange', 'course-color-deeppurple'];
    
    $color_class = 'course-color-default';
    if ($hex_color) { 
        $hex_index = array_search(strtolower($hex_color), array_map('strtolower', $default_colors_hex));
        if ($hex_index !== false && isset($color_classes[$hex_index])) {
            $color_class = $color_classes[$hex_index];
        }
    }
    return $color_class;
}

try {
    
    $sql_teaching = "SELECT c.course_id, c.course_name, c.description, c.color, u.username AS author_username
                     FROM courses c
                     JOIN users u ON c.author_id = u.user_id
                     WHERE c.author_id = ?";
    $stmt_teaching = $conn->prepare($sql_teaching);
    if ($stmt_teaching) {
        $stmt_teaching->bind_param("i", $user_id);
        $stmt_teaching->execute();
        $result_teaching = $stmt_teaching->get_result();

        while ($row = $result_teaching->fetch_assoc()) {
            $teaching_courses[] = [
                'id' => $row['course_id'],
                'name' => $row['course_name'],
                'description' => $row['description'],
                'color_hex' => $row['color'], 
                'color_class' => getColorClass($row['color']), 
                'author_username' => $row['author_username']
            ];
        }
        $stmt_teaching->close();
    } else {
         error_log("Failed to prepare teaching courses statement: " . $conn->error);
    }


    $sql_enrolled = "SELECT c.course_id, c.course_name, c.description, c.color, u_author.username AS author_username
                     FROM enrollments e
                     JOIN courses c ON e.course_id = c.course_id
                     JOIN users u_author ON c.author_id = u_author.user_id
                     WHERE e.student_id = ?";
    $stmt_enrolled = $conn->prepare($sql_enrolled);
    if ($stmt_enrolled) {
        $stmt_enrolled->bind_param("i", $user_id);
        $stmt_enrolled->execute();
        $result_enrolled = $stmt_enrolled->get_result();

        while ($row = $result_enrolled->fetch_assoc()) {
            $enrolled_courses[] = [
                'id' => $row['course_id'],
                'name' => $row['course_name'],
                'description' => $row['description'],
                'color_hex' => $row['color'],
                'color_class' => getColorClass($row['color']), 
                'author_username' => $row['author_username']
            ];
        }
        $stmt_enrolled->close();
    } else {
        error_log("Failed to prepare enrolled courses statement: " . $conn->error);
    }


    $response['status'] = 'success';
    $response['teaching_courses'] = $teaching_courses;
    $response['enrolled_courses'] = $enrolled_courses;

} catch (Exception $e) {
    $response['message'] = 'Помилка сервера: ' . $e->getMessage();
    error_log("Exception in get_user_courses: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
exit();
?>