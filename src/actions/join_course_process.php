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
    $course_code = trim($_POST['course_code'] ?? '');
    $student_id = $_SESSION['user_id'];

    if (empty($course_code)) {
        $response['message'] = 'Код курсу не може бути порожнім.';
        echo json_encode($response);
        exit();
    }

    // 1. Знайти курс за кодом
    $stmt_find_course = $conn->prepare("SELECT course_id, author_id, course_name, description, color FROM courses WHERE join_code = ?");
    if (!$stmt_find_course) {
        $response['message'] = 'Помилка підготовки запиту пошуку курсу: ' . $conn->error;
        echo json_encode($response);
        exit();
    }
    $stmt_find_course->bind_param("s", $course_code);
    $stmt_find_course->execute();
    $result_course = $stmt_find_course->get_result();

    if ($result_course->num_rows == 0) {
        $response['message'] = 'Курс з таким кодом не знайдено.';
        $stmt_find_course->close();
        echo json_encode($response);
        exit();
    }

    $course = $result_course->fetch_assoc();
    $course_id = $course['course_id'];
    $author_id_of_course = $course['author_id']; // Змінено назву змінної для ясності
    $stmt_find_course->close();

    // 2. Перевірка, чи користувач не є автором курсу
    if ($author_id_of_course == $student_id) {
        $response['message'] = 'Ви не можете приєднатися до власного курсу як студент.';
        echo json_encode($response);
        exit();
    }

    // 3. Перевірка, чи користувач вже не приєднаний до цього курсу
    $stmt_check_enrollment = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE course_id = ? AND student_id = ?");
    if (!$stmt_check_enrollment) {
        $response['message'] = 'Помилка підготовки запиту перевірки зарахування: ' . $conn->error;
        echo json_encode($response);
        exit();
    }
    $stmt_check_enrollment->bind_param("ii", $course_id, $student_id);
    $stmt_check_enrollment->execute();
    $stmt_check_enrollment->store_result();

    if ($stmt_check_enrollment->num_rows > 0) {
        $response['message'] = 'Ви вже приєднані до цього курсу.';
        $stmt_check_enrollment->close();
        echo json_encode($response);
        exit();
    }
    $stmt_check_enrollment->close();

    // 4. Якщо всі перевірки пройдені, додаємо запис до enrollments
    $stmt_enroll = $conn->prepare("INSERT INTO enrollments (course_id, student_id, enrolled_at) VALUES (?, ?, NOW())");
    if (!$stmt_enroll) {
        $response['message'] = 'Помилка підготовки запиту зарахування: ' . $conn->error;
        echo json_encode($response);
        exit();
    }
    $stmt_enroll->bind_param("ii", $course_id, $student_id);

    if ($stmt_enroll->execute()) {
        // Отримаємо ім'я автора для відображення на картці
        $stmt_get_author = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
        $author_username_for_course = 'Автор невідомий'; // Змінено назву змінної
        if ($stmt_get_author) {
            $stmt_get_author->bind_param("i", $author_id_of_course);
            $stmt_get_author->execute();
            $result_author = $stmt_get_author->get_result();
            if ($author_row = $result_author->fetch_assoc()) {
                $author_username_for_course = $author_row['username'];
            }
            $stmt_get_author->close();
        }

        // Визначення CSS-класу для кольору (аналогічно до get_user_courses.php)
        $default_colors_hex = ['#f0ad4e', '#5cb85c', '#5bc0de', '#d9534f', '#ba68c8', '#7986cb', '#4db6ac', '#a1887f', '#ff8a65', '#9575cd'];
        $color_classes = ['course-color-orange', 'course-color-green', 'course-color-lblue', 'course-color-red', 'course-color-purple', 'course-color-indigo', 'course-color-teal', 'course-color-brown', 'course-color-deeporange', 'course-color-deeppurple'];
        $color_hex = $course['color'];
        $color_class = 'course-color-default';
        $hex_index = array_search(strtolower($color_hex), array_map('strtolower', $default_colors_hex));
        if ($hex_index !== false && isset($color_classes[$hex_index])) {
            $color_class = $color_classes[$hex_index];
        }

        $response['status'] = 'success';
        $response['message'] = 'Ви успішно приєдналися до курсу!';
        $response['course'] = [ 
            'id' => $course_id,
            'name' => $course['course_name'],
            'description' => $course['description'],
            'color_class' => $color_class,
            'author_username' => $author_username_for_course
        ];
    } else {
        $response['message'] = 'Не вдалося приєднатися до курсу: ' . $stmt_enroll->error;
    }
    $stmt_enroll->close();

} else {
    $response['message'] = 'Некоректний метод запиту.';
}

$conn->close();
echo json_encode($response);
exit();
?>