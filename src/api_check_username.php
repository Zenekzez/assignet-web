<?php
require_once 'connect.php'; // Підключення до БД
header('Content-Type: application/json'); // Вказуємо, що відповідь буде у форматі JSON

$response = ['is_available' => false, 'message' => 'Не вдалося перевірити юзернейм.'];

// Очікуємо, що юзернейм прийде методом POST від JavaScript
if (isset($_POST['username'])) {
    $username = trim($_POST['username']);

    // Мінімальна серверна валідація для отриманого юзернейма
    // (довжина та символи, як у твоїй JS-валідації, але не надто складно)
    if (strlen($username) >= 3 && strlen($username) <= 20 && preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $response = ['is_available' => false, 'message' => 'Цей юзернейм вже використовується.'];
            } else {
                $response = ['is_available' => true, 'message' => 'Юзернейм вільний.'];
            }
            $stmt->close();
        } else {
             $response = ['is_available' => false, 'message' => 'Помилка підготовки запиту до БД.'];
        }
    } elseif (strlen($username) > 0) { // Якщо юзернейм є, але не пройшов базову валідацію
        $response = ['is_available' => false, 'message' => 'Юзернейм: 3-20 символів (літери, цифри, \'_\').'];
    }
    // Якщо юзернейм порожній, JS не мав би відправляти запит, але можна додати обробку
}

$conn->close();
echo json_encode($response); // Відправляємо JSON-відповідь
exit();
?>