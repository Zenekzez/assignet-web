<?php
// File: src/update_avatar.php (НОВИЙ ФАЙЛ)
session_start();
require_once 'connect.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Не вдалося завантажити аватарку.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Користувач не авторизований.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['avatarFile']) && $_FILES['avatarFile']['error'] == 0) {
        $allowed_mime_types = ['image/jpeg', 'image/png'];
        $max_file_size = 2 * 1024 * 1024; // 2MB

        $file_mime_type = mime_content_type($_FILES['avatarFile']['tmp_name']);
        $file_size = $_FILES['avatarFile']['size'];

        if (!in_array($file_mime_type, $allowed_mime_types)) {
            $response['message'] = 'Неприпустимий тип файлу. Дозволено лише PNG та JPEG.';
            echo json_encode($response);
            exit();
        }

        if ($file_size > $max_file_size) {
            $response['message'] = 'Файл занадто великий. Максимальний розмір - 2MB.';
            echo json_encode($response);
            exit();
        }

        // Створюємо директорію для завантажень, якщо її немає
        // Шлях відносно кореня вашого веб-сервера або абсолютний шлях
        // Важливо: переконайтеся, що веб-сервер має права на запис у цю директорію
        $upload_dir_relative = '../public/uploads/avatars/'; // Шлях відносно цього скрипта
        $upload_dir_absolute = realpath(__DIR__ . '/' . $upload_dir_relative) . '/';

        if (!is_dir($upload_dir_absolute)) {
            if (!mkdir($upload_dir_absolute, 0775, true)) { // 0775 дає права на запис для власника та групи
                $response['message'] = 'Не вдалося створити директорію для завантаження: ' . $upload_dir_absolute;
                error_log('Failed to create directory: ' . $upload_dir_absolute);
                echo json_encode($response);
                exit();
            }
        }
        
        $file_extension = pathinfo($_FILES['avatarFile']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('avatar_', true) . '.' . strtolower($file_extension);
        $upload_path = $upload_dir_absolute . $new_filename;
        
        // Шлях для збереження в БД (відносний до кореня сайту, якщо потрібно для <img> src)
        // Наприклад, якщо uploads/ знаходиться в public/, а public/ є коренем сайту
        $db_avatar_path = 'uploads/avatars/' . $new_filename; // Або інший шлях, який ви використовуєте для доступу до файлів через URL

        if (move_uploaded_file($_FILES['avatarFile']['tmp_name'], $upload_path)) {
            // Оновлення шляху в базі даних
            $stmt_update = $conn->prepare("UPDATE users SET avatar_path = ? WHERE user_id = ?");
            if ($stmt_update) {
                $stmt_update->bind_param("si", $db_avatar_path, $user_id);
                if ($stmt_update->execute()) {
                    // Видалення старого аватара, якщо він є і не є стандартним
                    if (isset($_SESSION['db_avatar_path']) && 
                        !empty($_SESSION['db_avatar_path']) && 
                        $_SESSION['db_avatar_path'] !== 'path/to/default/avatar.png' && // Замініть на ваш реальний шлях до стандартного аватара
                        file_exists($upload_dir_absolute . basename($_SESSION['db_avatar_path']))) {
                        unlink($upload_dir_absolute . basename($_SESSION['db_avatar_path']));
                    }

                    $_SESSION['db_avatar_path'] = $db_avatar_path; // Оновлюємо сесію
                    $response['status'] = 'success';
                    $response['message'] = 'Аватарку успішно оновлено!';
                    $response['new_avatar_url'] = $db_avatar_path; // Надсилаємо новий шлях для оновлення на клієнті
                } else {
                    $response['message'] = 'Помилка оновлення шляху в БД: ' . $stmt_update->error;
                    error_log('DB update error: ' . $stmt_update->error);
                }
                $stmt_update->close();
            } else {
                 $response['message'] = 'Помилка підготовки запиту до БД: ' . $conn->error;
                 error_log('DB prepare error: ' . $conn->error);
            }
        } else {
            $response['message'] = 'Помилка переміщення завантаженого файлу.';
            error_log('File move error. Target: ' . $upload_path);
        }
    } else {
        $response['message'] = 'Файл не було завантажено або сталася помилка: ' . ($_FILES['avatarFile']['error'] ?? 'невідома помилка');
        error_log('File upload error code: ' . ($_FILES['avatarFile']['error'] ?? 'N/A'));
    }
} else {
    $response['message'] = 'Некоректний метод запиту.';
}

$conn->close();
echo json_encode($response);
?>