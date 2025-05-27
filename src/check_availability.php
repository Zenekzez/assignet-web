<?php
header('Content-Type: application/json'); // Повідомляємо клієнту, що відповідь буде у форматі JSON
require_once 'connect.php'; // Підключення до бази даних

$response = ['available' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $sql = "SELECT user_id FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows == 0) {
                    $response['available'] = true;
                } else {
                    $response['message'] = "Ця електронна пошта вже зареєстрована.";
                }
                $stmt->close();
            } else {
                $response['message'] = "Помилка підготовки запиту до БД (email).";
            }
        } elseif (!empty($email)) {
            $response['message'] = "Некоректний формат електронної пошти.";
        }
        // Якщо email порожній, нічого не робимо, available залишається false, message порожнім
        // Клієнтська сторона має обробити порожнє поле окремо

    } elseif (isset($_POST['username'])) {
        $username = trim($_POST['username']);
        // Додамо базову перевірку формату юзернейма, аналогічну до reg.js/process_registration.php
        if (!empty($username) && preg_match("/^[a-zA-Z0-9_]{3,20}$/", $username)) {
            $sql = "SELECT user_id FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows == 0) {
                    $response['available'] = true;
                } else {
                    $response['message'] = "Цей юзернейм вже зайнятий.";
                }
                $stmt->close();
            } else {
                $response['message'] = "Помилка підготовки запиту до БД (username).";
            }
        } elseif (!empty($username)) {
             $response['message'] = "Юзернейм: 3-20 символів (літери, цифри, '_').";
        }
        // Якщо username порожній, нічого не робимо
    } else {
        $response['message'] = "Не вказано тип перевірки (email/username).";
    }
} else {
    $response['message'] = "Некоректний метод запиту.";
}

$conn->close();
echo json_encode($response);
exit();
?>