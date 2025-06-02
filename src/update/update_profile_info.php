<?php
session_start();
require_once 'connect.php'; 
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Не вдалося оновити інформацію. Спробуйте пізніше.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Користувач не авторизований. Будь ласка, увійдіть до системи.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $firstName = trim($data['firstName'] ?? '');
    $lastName = trim($data['lastName'] ?? '');
    $newUsername = trim($data['username'] ?? '');
    $user_id = $_SESSION['user_id'];
    $currentUsername = $_SESSION['username']; 

    
    if (empty($firstName) || empty($lastName) || empty($newUsername)) {
        $response['message'] = 'Ім\'я, прізвище та юзернейм не можуть бути порожніми.';
        echo json_encode($response);
        exit();
    }
    if (!preg_match("/^[a-zA-Z0-9_]{3,20}$/", $newUsername)) {
        $response['message'] = "Юзернейм має містити від 3 до 20 символів (тільки літери, цифри та знак підкреслення '_').";
        echo json_encode($response);
        exit();
    }
    if (mb_strlen($firstName) > 30 || mb_strlen($lastName) > 30) {
        $response['message'] = "Ім'я та прізвище не можуть перевищувати 30 символів.";
        echo json_encode($response);
        exit();
    }


    if ($newUsername !== $currentUsername) {
        $stmt_check_username = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        if ($stmt_check_username) {
            $stmt_check_username->bind_param("si", $newUsername, $user_id);
            $stmt_check_username->execute();
            $result_check_username = $stmt_check_username->get_result();
            if ($result_check_username->num_rows > 0) {
                $response['message'] = 'Цей юзернейм вже використовується іншим користувачем.';
                $stmt_check_username->close();
                echo json_encode($response);
                exit();
            }
            $stmt_check_username->close();
        } else {
            $response['message'] = 'Помилка підготовки запиту для перевірки юзернейма: ' . $conn->error;
            error_log('DB username check prepare error: ' . $conn->error);
            echo json_encode($response);
            exit();
        }
    }

  
    $stmt_update = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ? WHERE user_id = ?");
    if ($stmt_update) {
        $stmt_update->bind_param("sssi", $firstName, $lastName, $newUsername, $user_id);
        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                $_SESSION['db_first_name'] = $firstName;
                $_SESSION['db_last_name'] = $lastName;
                $_SESSION['username'] = $newUsername;

                $response['status'] = 'success';
                $response['message'] = 'Інформацію профілю успішно оновлено!';
                $response['new_username'] = $newUsername;
            } else {
                $_SESSION['db_first_name'] = $firstName; 
                $_SESSION['db_last_name'] = $lastName;
                $_SESSION['username'] = $newUsername;

                $response['status'] = 'success'; 
                $response['message'] = 'Дані не змінилися або вже були оновлені.';
                $response['new_username'] = $newUsername;
            }
        } else {
            $response['message'] = 'Помилка оновлення даних в БД: ' . $stmt_update->error;
            error_log('DB profile update error: ' . $stmt_update->error);
        }
        $stmt_update->close();
    } else {
        $response['message'] = 'Помилка підготовки запиту до БД для оновлення профілю: ' . $conn->error;
        error_log('DB profile update prepare error: ' . $conn->error);
    }
} else {
    $response['message'] = 'Некоректний метод запиту. Очікується POST.';
}

$conn->close();
echo json_encode($response);
?>