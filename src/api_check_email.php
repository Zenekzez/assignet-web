<?php
require_once 'connect.php'; // Підключення до БД
header('Content-Type: application/json');

$response = ['is_available' => false, 'message' => 'Не вдалося перевірити email.'];

if (isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) { // Базова перевірка формату
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $response = ['is_available' => false, 'message' => 'Цей email вже використовується.'];
            } else {
                $response = ['is_available' => true, 'message' => 'Email вільний.'];
            }
            $stmt->close();
        } else {
            $response = ['is_available' => false, 'message' => 'Помилка підготовки запиту до БД.'];
        }
    } elseif (strlen($email) > 0) {
         $response = ['is_available' => false, 'message' => 'Введіть коректну адресу електронної пошти.'];
    }
}

$conn->close();
echo json_encode($response);
exit();
?>