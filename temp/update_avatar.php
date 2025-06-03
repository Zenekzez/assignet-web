<?php
/*
session_start();
require_once 'connect.php'; 
header('Content-Type: application/json'); 

$response = ['status' => 'error', 'message' => 'Не вдалося завантажити аватарку. Спробуйте пізніше.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Користувач не авторизований. Будь ласка, увійдіть до системи.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['avatarFile']) && $_FILES['avatarFile']['error'] == UPLOAD_ERR_OK) {
        $allowed_mime_types = ['image/jpeg', 'image/png'];
        $max_file_size = 2 * 1024 * 1024; 

        $file_tmp_name = $_FILES['avatarFile']['tmp_name'];
        $file_size = $_FILES['avatarFile']['size'];
        $file_name_original = $_FILES['avatarFile']['name'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $file_mime_type = finfo_file($finfo, $file_tmp_name);
        finfo_close($finfo);

        if (!in_array($file_mime_type, $allowed_mime_types)) {
            $response['message'] = 'Неприпустимий тип файлу. Дозволено лише файли форматів PNG та JPEG.';
            echo json_encode($response);
            exit();
        }

        if ($file_size > $max_file_size) {
            $response['message'] = 'Файл занадто великий. Максимально допустимий розмір файлу - 2MB.';
            echo json_encode($response);
            exit();
        }

        $project_root = dirname(__DIR__);
        $upload_dir_absolute = $project_root . '/public/uploads/avatars/';


        if (!is_dir($upload_dir_absolute)) {
            if (!mkdir($upload_dir_absolute, 0775, true)) {
                $response['message'] = 'Помилка: не вдалося створити директорію для завантаження аватарів. Перевірте права доступу.';
                error_log('Failed to create directory: ' . $upload_dir_absolute);
                echo json_encode($response);
                exit();
            }
        }
        
        $file_extension = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));
        $new_filename_base = 'avatar_user_' . $user_id . '_' . time(); 
        $new_filename = $new_filename_base . '.' . $file_extension;
        $upload_path_absolute = $upload_dir_absolute . $new_filename;
        
        $db_avatar_path = 'uploads/avatars/' . $new_filename;

        $old_avatar_db_path = $_SESSION['db_avatar_path'] ?? null;
        if ($old_avatar_db_path && $old_avatar_db_path !== 'assets/default_avatar.png') { 
            $old_avatar_server_path = $project_root . '/public/' . $old_avatar_db_path;
            if (file_exists($old_avatar_server_path)) {
                unlink($old_avatar_server_path);
            }
        }

        if (move_uploaded_file($file_tmp_name, $upload_path_absolute)) {
            $stmt_update = $conn->prepare("UPDATE users SET avatar_path = ? WHERE user_id = ?");
            if ($stmt_update) {
                $stmt_update->bind_param("si", $db_avatar_path, $user_id);
                if ($stmt_update->execute()) {
                    $_SESSION['db_avatar_path'] = $db_avatar_path; 
                    $response['status'] = 'success';
                    $response['message'] = 'Аватарку успішно оновлено!';
                    $response['new_avatar_url'] = $db_avatar_path; 
                } else {
                    $response['message'] = 'Помилка оновлення шляху аватара в базі даних: ' . $stmt_update->error;
                    error_log('DB avatar update error: ' . $stmt_update->error);
                    if(file_exists($upload_path_absolute)) unlink($upload_path_absolute);
                }
                $stmt_update->close();
            } else {
                 $response['message'] = 'Помилка підготовки запиту до бази даних для оновлення аватара: ' . $conn->error;
                 error_log('DB prepare error for avatar update: ' . $conn->error);
                 if(file_exists($upload_path_absolute)) unlink($upload_path_absolute);
            }
        } else {
            $response['message'] = 'Помилка під час збереження файлу аватара на сервері.';
            error_log('File move_uploaded_file error. Source: ' . $file_tmp_name . ' Target: ' . $upload_path_absolute);
        }
    } elseif (isset($_FILES['avatarFile']['error']) && $_FILES['avatarFile']['error'] != UPLOAD_ERR_NO_FILE) {
        $upload_errors = array(
            UPLOAD_ERR_INI_SIZE   => 'Розмір файлу перевищує директиву upload_max_filesize в php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'Розмір файлу перевищує директиву MAX_FILE_SIZE, вказану в HTML-формі.',
            UPLOAD_ERR_PARTIAL    => 'Файл було завантажено лише частково.',
            UPLOAD_ERR_NO_TMP_DIR => 'Відсутня тимчасова директорія для завантаження.',
            UPLOAD_ERR_CANT_WRITE => 'Не вдалося записати файл на диск.',
            UPLOAD_ERR_EXTENSION  => 'PHP-розширення зупинило завантаження файлу.',
        );
        $error_code = $_FILES['avatarFile']['error'];
        $response['message'] = $upload_errors[$error_code] ?? 'Невідома помилка під час завантаження файлу.';
        error_log('File upload error code from client: ' . $error_code);
    } else {
         $response['message'] = 'Файл для завантаження не було вибрано.';
    }
} else {
    $response['message'] = 'Некоректний метод запиту. Очікується POST.';
}

$conn->close();
echo json_encode($response);
*/
?> 
