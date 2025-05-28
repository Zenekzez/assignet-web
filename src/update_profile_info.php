<?php
session_start();
require_once 'connect.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Не вдалося оновити інформацію.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Користувач не авторизований.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $firstName = trim($data['firstName'] ?? '');
    $lastName = trim($data['lastName'] ?? '');
    $username = trim($data['username'] ?? '');
    $user_id = $_SESSION['user_id'];

    // Валідація
    if (empty($firstName) || empty($lastName) || empty($username)) {
        $response['message'] = 'Ім\'я, прізвище та юзернейм не можуть бути порожніми.';
        echo json_encode($response);
        exit();
    }
    // Цей регулярний вираз тут правильний для PHP
    if (!preg_match("/^[a-zA-Z0-9_]{3,20}$/", $username)) {
        $response['message'] = "Юзернейм: 3-20 символів (літери, цифри, '_').";
        echo json_encode($response);
        exit();
    }
    // Перевірка унікальності нового юзернейма (якщо він змінився)
    if ($username !== $_SESSION['username']) {
        $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $stmt_check->bind_param("si", $username, $user_id);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $response['message'] = 'Цей юзернейм вже зайнятий.';
            $stmt_check->close();
            echo json_encode($response);
            exit();
        }
        $stmt_check->close();
    }

    $stmt_update = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ? WHERE user_id = ?");
    $stmt_update->bind_param("sssi", $firstName, $lastName, $username, $user_id);

    if ($stmt_update->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Інформацію профілю оновлено!';
        // Оновити юзернейм в сесії, якщо він змінився
        if ($username !== $_SESSION['username']) {
            $_SESSION['username'] = $username;
            $response['new_username'] = $username; // Для оновлення на фронтенді
        }
        // Оновити ім'я та прізвище в сесії, якщо вони там зберігаються і використовуються
        $_SESSION['db_first_name'] = $firstName; // Припускаємо, що так вони зберігаються
        $_SESSION['db_last_name'] = $lastName;
    } else {
        $response['message'] = 'Помилка оновлення в базі даних: ' . $stmt_update->error;
    }
    $stmt_update->close();
} else {
    $response['message'] = 'Некоректний метод запиту.';
}
$conn->close();
echo json_encode($response);
?>