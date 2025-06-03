<?php
session_start();
require_once __DIR__ . '/../connect.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Не вдалося завантажити аватарку.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Користувач не авторизований.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['avatarFile'])) { 
        if ($_FILES['avatarFile']['error'] == UPLOAD_ERR_OK) {
            $allowed_mime_types = ['image/jpeg', 'image/png'];
            $max_file_size = 2 * 1024 * 1024; 

            $file_tmp_name = $_FILES['avatarFile']['tmp_name']; 
            $file_mime_type = mime_content_type($file_tmp_name);
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

            
            $upload_dir_relative = '../../public/uploads/avatars/'; 
            $upload_dir_absolute = realpath(__DIR__ . '/' . $upload_dir_relative);

           
            if ($upload_dir_absolute) {
                $upload_dir_absolute .= '/';
            } else {
                $response['message'] = 'Помилка визначення шляху для завантаження.';
                error_log('Failed to resolve realpath for upload directory: ' . __DIR__ . '/' . $upload_dir_relative);
                echo json_encode($response);
                exit();
            }


            if (!is_dir($upload_dir_absolute)) {
                if (!mkdir($upload_dir_absolute, 0775, true)) {
                    $response['message'] = 'Не вдалося створити директорію для завантаження: ' . $upload_dir_absolute;
                    error_log('Failed to create directory: ' . $upload_dir_absolute);
                    echo json_encode($response);
                    exit();
                }
            }

            $file_extension = pathinfo($_FILES['avatarFile']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('avatar_', true) . '.' . strtolower($file_extension);
            $upload_path = $upload_dir_absolute . $new_filename;
            
            $db_avatar_path = 'uploads/avatars/' . $new_filename;

            if (move_uploaded_file($file_tmp_name, $upload_path)) {
                
                $old_avatar_db_path = $_SESSION['db_avatar_path'] ?? null;
                if ($old_avatar_db_path &&
                    $old_avatar_db_path !== 'assets/default_avatar.png' && 
                    $old_avatar_db_path !== 'path/to/default/avatar.png') { 

                    
                    $public_root_path = realpath(__DIR__ . '/../../public');
                    if ($public_root_path) {
                        $old_avatar_server_path = $public_root_path . '/' . $old_avatar_db_path;
                        if (file_exists($old_avatar_server_path) && is_file($old_avatar_server_path)) {
                           unlink($old_avatar_server_path);
                        }
                    }
                }


                $stmt_update = $conn->prepare("UPDATE users SET avatar_path = ? WHERE user_id = ?");
                if ($stmt_update) {
                    $stmt_update->bind_param("si", $db_avatar_path, $user_id);
                    if ($stmt_update->execute()) {
                        $_SESSION['db_avatar_path'] = $db_avatar_path;
                        $response['status'] = 'success';
                        $response['message'] = 'Аватарку успішно оновлено!';
                        $response['new_avatar_url'] = $db_avatar_path; 
                    } else {
                        $response['message'] = 'Помилка оновлення шляху в БД: ' . $stmt_update->error;
                        error_log('DB update error: ' . $stmt_update->error);
                        if(file_exists($upload_path)) unlink($upload_path); 
                    }
                    $stmt_update->close();
                } else {
                    $response['message'] = 'Помилка підготовки запиту до БД: ' . $conn->error;
                    error_log('DB prepare error: ' . $conn->error);
                    if(file_exists($upload_path)) unlink($upload_path); 
                }
            } else {
                $response['message'] = 'Помилка переміщення завантаженого файлу.';
                error_log('File move error. Source: ' . $file_tmp_name . '. Target: ' . $upload_path);
            }
        } elseif ($_FILES['avatarFile']['error'] != UPLOAD_ERR_NO_FILE) {
            $upload_errors = array(
                UPLOAD_ERR_INI_SIZE   => 'Розмір файлу перевищує директиву upload_max_filesize в php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'Розмір файлу перевищує директиву MAX_FILE_SIZE, вказану в HTML-формі.',
                UPLOAD_ERR_PARTIAL    => 'Файл було завантажено лише частково.',
                UPLOAD_ERR_NO_TMP_DIR => 'Відсутня тимчасова директорія для завантаження.',
                UPLOAD_ERR_CANT_WRITE => 'Не вдалося записати файл на диск.',
                UPLOAD_ERR_EXTENSION  => 'PHP-розширення зупинило завантаження файлу.',
            );
            $error_code = $_FILES['avatarFile']['error'];
            $response['message'] = $upload_errors[$error_code] ?? 'Невідома помилка під час завантаження файлу. Код: ' . $error_code;
            error_log('File upload error code from client: ' . $error_code . '. Message: ' . ($upload_errors[$error_code] ?? 'Unknown error'));
        } else {
            $response['message'] = 'Файл для завантаження не було вибрано.';
        }
    } else {
        $response['message'] = 'Файл не було надіслано або неправильне ім\'я поля у формі.';
        error_log('Avatar file field not found in _FILES array.');
    }
} else {
    $response['message'] = 'Некоректний метод запиту.';
}

$conn->close();
echo json_encode($response);
?>