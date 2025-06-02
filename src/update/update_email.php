<?php
session_start();
require_once 'connect.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Не вдалося змінити пошту. Спробуйте пізніше.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Користувач не авторизований. Будь ласка, увійдіть до системи.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $newEmail = trim($data['newEmail'] ?? '');
    $currentPassword = $data['passwordForEmailChange'] ?? '';
    $user_id = $_SESSION['user_id'];
    $sessionEmail = $_SESSION['email'] ?? ''; 

    if (empty($newEmail) || empty($currentPassword)) {
        $response['message'] = 'Нова електронна пошта та поточний пароль мають бути заповнені.';
        echo json_encode($response);
        exit();
    }
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Некоректний формат нової електронної пошти.';
        echo json_encode($response);
        exit();
    }
    if (strtolower($newEmail) === strtolower($sessionEmail)) {
        $response['status'] = 'info';
        $response['message'] = 'Нова пошта співпадає з поточною. Змін не відбулося.';
        echo json_encode($response);
        exit();
    }

    $stmt_user = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    if (!$stmt_user) {
        $response['message'] = 'Помилка підготовки запиту (користувач): ' . $conn->error;
        error_log("DB prepare error (user for email change password check): " . $conn->error);
        echo json_encode($response);
        exit();
    }
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_db_data = $result_user->fetch_assoc();
    $stmt_user->close();

    if (!$user_db_data || !password_verify($currentPassword, $user_db_data['password_hash'])) {
        $response['message'] = 'Поточний пароль введено невірно.';
        echo json_encode($response);
        exit();
    }

    $stmt_check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    if (!$stmt_check_email) {
        $response['message'] = 'Помилка підготовки запиту (перевірка унікальності пошти): ' . $conn->error;
        error_log("DB prepare error (email uniqueness check for update): " . $conn->error);
        echo json_encode($response);
        exit();
    }
    $stmt_check_email->bind_param("si", $newEmail, $user_id);
    $stmt_check_email->execute();
    $result_check_email = $stmt_check_email->get_result();

    if ($result_check_email->num_rows > 0) {
        $response['message'] = 'Ця електронна пошта вже використовується іншим користувачем.';
        $stmt_check_email->close();
        echo json_encode($response);
        exit();
    }
    $stmt_check_email->close();

    $stmt_update = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
    if (!$stmt_update) {
        $response['message'] = 'Помилка підготовки запиту до БД для оновлення пошти: ' . $conn->error;
        error_log("DB email update prepare error: " . $conn->error);
        echo json_encode($response);
        exit();
    }
    $stmt_update->bind_param("si", $newEmail, $user_id);
    if ($stmt_update->execute()) {
        if ($stmt_update->affected_rows > 0) {
            $_SESSION['email'] = $newEmail; 
            $response['status'] = 'success';
            $response['message'] = 'Електронну пошту успішно змінено!';
            $response['new_email_for_display'] = $newEmail; 
        } else {
            $response['status'] = 'info';
            $response['message'] = 'Дані не змінилися або вже були оновлені.';
        }
    } else {
        $response['message'] = 'Помилка оновлення пошти в БД: ' . $stmt_update->error;
        error_log("DB email update execute error: " . $stmt_update->error);
    }
    $stmt_update->close();

} else {
    $response['message'] = 'Некоректний метод запиту. Очікується POST.';
}

$conn->close();
echo json_encode($response);
?>