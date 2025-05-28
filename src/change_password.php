<?php
session_start();
require_once 'connect.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Не вдалося змінити пароль.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Користувач не авторизований.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $currentPassword = $data['currentPassword'] ?? '';
    $newPassword = $data['newPassword'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (empty($currentPassword) || empty($newPassword)) {
        $response['message'] = 'Всі поля мають бути заповнені.';
        echo json_encode($response);
        exit();
    }

    // Перевірка поточного пароля
    $stmt_user = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($user_db_data = $result_user->fetch_assoc()) {
        if (password_verify($currentPassword, <span class="math-inline">user\_db\_data\['password\_hash'\]\)\) \{
// Валідація нового пароля \(серверна\)
if \(\!preg\_match\("/^\(?\=\.\*\[a\-z\]\)\(?\=\.\*\[A\-Z\]\)\(?\=\.\*\\d\)\[A\-Za\-z\\d@</span>!%*?&]{8,}$/", $newPassword)) {
                $response['message'] = 'Новий пароль не відповідає вимогам безпеки.';
                echo json_encode($response);
                exit();
            }

            // Хешування та оновлення пароля
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt_update->bind_param("si", $newPasswordHash, $user_id);
            if ($stmt_update->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Пароль успішно змінено!';
            } else {
                $response['message'] = 'Помилка оновлення пароля в БД: ' . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $response['message'] = 'Поточний пароль введено невірно.';
        }
    } else {
        $response['message'] = 'Помилка отримання даних користувача.';
    }
    $stmt_user->close();
} else {
    $response['message'] = 'Некоректний метод запиту.';
}
$conn->close();
echo json_encode($response);
?>