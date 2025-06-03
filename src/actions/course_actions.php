<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../connect.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Невідома дія або помилка.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Користувач не авторизований.';
    echo json_encode($response);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;

if (!$action) {
    $response['message'] = 'Дію не вказано.';
    echo json_encode($response);
    exit();
}

function isUserTeacherOfCourse($conn, $userId, $courseId) {
    $stmt = $conn->prepare("SELECT author_id FROM courses WHERE course_id = ? AND author_id = ?");
    if (!$stmt) return false;
    $stmt->bind_param("ii", $courseId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_teacher = $result->num_rows > 0;
    $stmt->close();
    return $is_teacher;
}

function generateJoinCodeInternal($conn, $length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    $max_tries = 10;
    $try_count = 0;
    do {
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        $stmt_check = $conn->prepare("SELECT course_id FROM courses WHERE join_code = ?");
        if (!$stmt_check) {
            error_log("Prepare failed for join_code check: (" . $conn->errno . ") " . $conn->error);
            return false;
        }
        $stmt_check->bind_param("s", $randomString);
        $stmt_check->execute();
        $stmt_check->store_result();
        $num_rows = $stmt_check->num_rows;
        $stmt_check->close();
        $try_count++;
        if ($try_count > $max_tries && $num_rows > 0) {
             error_log("Failed to generate unique join code after $max_tries attempts.");
             return false;
        }
    } while ($num_rows > 0);
    return $randomString;
}

function handleUploadedAssignmentFiles($conn, $assignment_id, $course_id_for_path, $files_array) {
    $uploaded_file_paths_db = [];
    $base_upload_dir_server = dirname(__DIR__, 2) . '/public/uploads/assignments_attachments/';
    $relative_upload_dir_base = 'uploads/assignments_attachments/course_' . $course_id_for_path . '/assignment_' . $assignment_id . '/';
    $absolute_upload_dir = $base_upload_dir_server . 'course_' . $course_id_for_path . '/assignment_' . $assignment_id . '/';

    if (!is_dir($absolute_upload_dir)) {
        if (!mkdir($absolute_upload_dir, 0775, true)) {
            error_log('Failed to create directory for assignment attachments: ' . $absolute_upload_dir);
            return ['error' => 'Не вдалося створити директорію для файлів завдання.'];
        }
    }

    $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'zip', 'ppt', 'pptx', 'xls', 'xlsx', 'mp4', 'mov', 'avi', 'mp3', 'wav'];
    $max_file_size_bytes = 15 * 1024 * 1024; // 15 MB

    if (isset($files_array['name']) && is_array($files_array['name'])) {
        $file_count = count($files_array['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($files_array['error'][$i] === UPLOAD_ERR_OK) {
                $file_tmp_name = $files_array['tmp_name'][$i];
                $file_name_original = basename($files_array['name'][$i]);
                $file_size = $files_array['size'][$i];
                $file_extension = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));

                if (!in_array($file_extension, $allowed_extensions)) {
                    error_log("Invalid file type for assignment attachment: " . $file_name_original . " (ext: " . $file_extension . ")");
                    continue;
                }

                if ($file_size > $max_file_size_bytes) {
                    error_log("File too large for assignment attachment: " . $file_name_original . " (size: " . $file_size . ")");
                    continue;
                }
                
                $safe_original_name = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $file_name_original);
                $new_filename = time() . '_' . uniqid('', true) . '_' . $safe_original_name;
                $upload_path_absolute_file = $absolute_upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp_name, $upload_path_absolute_file)) {
                    $db_file_path = $relative_upload_dir_base . $new_filename;
                    
                    $stmt_insert_file = $conn->prepare("INSERT INTO assignment_files (assignment_id, file_name, file_path) VALUES (?, ?, ?)");
                    if ($stmt_insert_file) {
                        $stmt_insert_file->bind_param("iss", $assignment_id, $file_name_original, $db_file_path);
                        if (!$stmt_insert_file->execute()) {
                            error_log('DB assignment_files insert error: ' . $stmt_insert_file->error);
                            if (file_exists($upload_path_absolute_file)) unlink($upload_path_absolute_file);
                        } else {
                            $uploaded_file_paths_db[] = ['name' => $file_name_original, 'path' => $db_file_path, 'id' => $stmt_insert_file->insert_id];
                        }
                        $stmt_insert_file->close();
                    } else {
                         error_log('DB assignment_files prepare error: ' . $conn->error);
                         if (file_exists($upload_path_absolute_file)) unlink($upload_path_absolute_file);
                    }
                } else {
                    error_log('Failed to move uploaded assignment attachment: ' . $file_name_original . '. Error code: ' . $files_array['error'][$i]);
                }
            } elseif ($files_array['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                error_log('File upload error for assignment attachment. File: ' . ($files_array['name'][$i] ?? 'N/A') . '. Code: ' . $files_array['error'][$i]);
            }
        }
    }
    return ['success' => true, 'files' => $uploaded_file_paths_db];
}


if ($action === 'create_announcement') {
     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
        $content = trim($_POST['announcement_content'] ?? '');

        if (!$course_id || empty($content)) {
            $response['message'] = 'ID курсу або вміст оголошення не можуть бути порожніми.';
        } elseif (!isUserTeacherOfCourse($conn, $current_user_id, $course_id)) {
            $response['message'] = 'У вас немає прав для публікації оголошень в цьому курсі.';
        } else {
            $stmt = $conn->prepare("INSERT INTO course_announcements (course_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("iis", $course_id, $current_user_id, $content);
                if ($stmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Оголошення успішно опубліковано!';
                    $response['announcement'] = [
                        'announcement_id' => $stmt->insert_id,
                        'course_id' => $course_id,
                        'user_id' => $current_user_id,
                        'content' => htmlspecialchars($content),
                        'created_at' => date('Y-m-d H:i:s'), 
                        'author_username' => $_SESSION['username'] ?? 'Автор'
                    ];
                } else {
                    $response['message'] = 'Помилка публікації оголошення: ' . $stmt->error;
                    error_log('DB announcement creation error: ' . $stmt->error);
                }
                $stmt->close();
            } else {
                $response['message'] = 'Помилка підготовки запиту: ' . $conn->error;
                error_log('DB announcement prepare error: ' . $conn->error);
            }
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для створення оголошення.';
    }
} elseif ($action === 'get_announcements') {
     if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

        if (!$course_id) {
            $response['message'] = 'ID курсу не вказано.';
        } else {
            $can_view_sql = "SELECT 
                                CASE
                                    WHEN EXISTS (SELECT 1 FROM courses WHERE course_id = ? AND author_id = ?) THEN 1
                                    WHEN EXISTS (SELECT 1 FROM enrollments WHERE course_id = ? AND student_id = ?) THEN 1
                                    ELSE 0
                                END AS can_view";
            $stmt_can_view = $conn->prepare($can_view_sql);
            if ($stmt_can_view) {
                $stmt_can_view->bind_param("iiii", $course_id, $current_user_id, $course_id, $current_user_id);
                $stmt_can_view->execute();
                $result_can_view = $stmt_can_view->get_result();
                $can_view_data = $result_can_view->fetch_assoc();
                $stmt_can_view->close();

                if (!$can_view_data || $can_view_data['can_view'] != 1) {
                    $response['message'] = 'У вас немає доступу для перегляду оголошень цього курсу.';
                    echo json_encode($response);
                    exit();
                }
            } else {
                 $response['message'] = 'Помилка перевірки доступу до оголошень: ' . $conn->error;
                 error_log("DB error checking announcement view permissions: " . $conn->error);
                 echo json_encode($response);
                 exit();
            }

            $stmt = $conn->prepare("SELECT ca.*, u.username AS author_username, u.avatar_path AS author_avatar_path
                                    FROM course_announcements ca
                                    JOIN users u ON ca.user_id = u.user_id
                                    WHERE ca.course_id = ? 
                                    ORDER BY ca.created_at DESC");
            if ($stmt) {
                $stmt->bind_param("i", $course_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $announcements = [];
                while ($row = $result->fetch_assoc()) {
                    $row['content'] = htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8');
                    $announcements[] = $row;
                }
                $response['status'] = 'success';
                $response['announcements'] = $announcements;
                unset($response['message']); 
                $stmt->close();
            } else {
                $response['message'] = 'Помилка отримання оголошень: ' . $conn->error;
                error_log("DB get_announcements prepare error: " . $conn->error);
            }
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для отримання оголошень.';
    }
} elseif ($action === 'update_course_settings') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $course_id = filter_input(INPUT_POST, 'course_id_settings', FILTER_VALIDATE_INT);
        $course_name = trim($_POST['course_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#007bff');
        $join_code_visible = isset($_POST['join_code_visible']) && $_POST['join_code_visible'] == '1' ? 1 : 0;

        if (!$course_id || empty($course_name)) {
            $response['message'] = 'ID курсу або назва курсу не можуть бути порожніми.';
        } elseif (!preg_match('/^#[0-9A-Fa-f]{6}$/i', $color)) { 
             $response['message'] = 'Некоректний формат кольору. Очікується HEX (напр. #RRGGBB).';
        } elseif (!isUserTeacherOfCourse($conn, $current_user_id, $course_id)) {
            $response['message'] = 'У вас немає прав для зміни налаштувань цього курсу.';
        } else {
            $stmt = $conn->prepare("UPDATE courses SET course_name = ?, description = ?, color = ?, join_code_visible = ? WHERE course_id = ? AND author_id = ?");
            if ($stmt) {
                $stmt->bind_param("sssiii", $course_name, $description, $color, $join_code_visible, $course_id, $current_user_id);
                if ($stmt->execute()) {
                     $response['status'] = 'success'; 
                     $response['message'] = $stmt->affected_rows > 0 ? 'Налаштування курсу успішно оновлено!' : 'Дані не змінилися або вже були оновлені.';
                     $response['updated_data'] = [
                         'course_name' => $course_name,
                         'description' => $description,
                         'color' => $color,
                         'join_code_visible' => (bool)$join_code_visible
                     ];
                } else {
                    $response['message'] = 'Помилка оновлення налаштувань: ' . $stmt->error;
                    error_log("Course settings update error: " . $stmt->error);
                }
                $stmt->close();
            } else {
                $response['message'] = 'Помилка підготовки запиту для оновлення налаштувань: ' . $conn->error;
                error_log("Course settings prepare error: " . $conn->error);
            }
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для оновлення налаштувань.';
    }
}
elseif ($action === 'regenerate_join_code') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

        if (!$course_id) {
            $response['message'] = 'ID курсу не вказано.';
        } elseif (!isUserTeacherOfCourse($conn, $current_user_id, $course_id)) {
            $response['message'] = 'У вас немає прав для зміни коду приєднання цього курсу.';
        } else {
            $new_join_code = generateJoinCodeInternal($conn);
            if ($new_join_code === false) {
                $response['message'] = 'Не вдалося згенерувати унікальний код. Можливо, сталася помилка сервера або вичерпано спроби.';
                error_log("Failed to generate unique join code for course_id: " . $course_id);
            } else {
                $stmt_update_code = $conn->prepare("UPDATE courses SET join_code = ? WHERE course_id = ? AND author_id = ?");
                if ($stmt_update_code) {
                    $stmt_update_code->bind_param("sii", $new_join_code, $course_id, $current_user_id);
                    if ($stmt_update_code->execute()) {
                        if ($stmt_update_code->affected_rows > 0) {
                            $response['status'] = 'success';
                            $response['message'] = 'Новий код приєднання успішно згенеровано та збережено!';
                            $response['new_join_code'] = $new_join_code;
                        } else {
                            $response['message'] = 'Не вдалося оновити код приєднання. Можливо, курс не знайдено або дані не змінилися.';
                        }
                    } else {
                        $response['message'] = 'Помилка оновлення коду приєднання в БД: ' . $stmt_update_code->error;
                        error_log('DB join code update error: ' . $stmt_update_code->error . ' for course_id: ' . $course_id);
                    }
                    $stmt_update_code->close();
                } else {
                    $response['message'] = 'Помилка підготовки запиту для оновлення коду: ' . $conn->error;
                    error_log('DB join code update prepare error: ' . $conn->error);
                }
            }
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для генерації нового коду.';
    }
}
elseif ($action === 'create_assignment') {
    $course_id_form = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

    if (!$course_id_form || !isUserTeacherOfCourse($conn, $current_user_id, $course_id_form)) { 
        $response['message'] = 'У вас немає прав для створення завдань в цьому курсі.';
        echo json_encode($response);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['assignment_title'] ?? '');
        $description = trim($_POST['assignment_description'] ?? '');
        $max_points = filter_input(INPUT_POST, 'assignment_max_points', FILTER_VALIDATE_INT);
        $due_date_str = trim($_POST['assignment_due_date'] ?? '');
        $section_title = trim($_POST['assignment_section_title'] ?? null);
        if ($section_title === '') { 
            $section_title = null;
        }
        
        if ($max_points !== false && ($max_points < 0 || $max_points > 100)) { 
            $response['message'] = 'Максимальна кількість балів повинна бути від 0 до 100.';
            echo json_encode($response);
            exit();
        }
        
        if (empty($title) || $max_points === false || empty($due_date_str)) { 
            $response['message'] = 'Будь ласка, заповніть усі обов\'язкові поля: назва, бали, дата здачі.';
            echo json_encode($response);
            exit();
        }

        try {
            $due_date_obj = new DateTime($due_date_str);
            $due_date_sql = $due_date_obj->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $response['message'] = 'Некоректний формат дати здачі.';
            echo json_encode($response);
            exit();
        }

        $conn->begin_transaction();

        $stmt_insert_assignment = $conn->prepare("INSERT INTO assignments (course_id, title, description, max_points, due_date, section_title, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        if ($stmt_insert_assignment) {
            $stmt_insert_assignment->bind_param("ississ", $course_id_form, $title, $description, $max_points, $due_date_sql, $section_title);
            if ($stmt_insert_assignment->execute()) {
                $new_assignment_id = $stmt_insert_assignment->insert_id;

                $uploaded_files_info = [];
                if (isset($_FILES['assignment_files']) && is_array($_FILES['assignment_files']['name']) && !empty($_FILES['assignment_files']['name'][0])) {
                     $file_handling_result = handleUploadedAssignmentFiles($conn, $new_assignment_id, $course_id_form, $_FILES['assignment_files']);
                     if (isset($file_handling_result['error'])) {
                         $conn->rollback();
                         $response['message'] = $file_handling_result['error'];
                         $stmt_temp_delete = $conn->prepare("DELETE FROM assignments WHERE assignment_id = ?");
                         if ($stmt_temp_delete) {
                             $stmt_temp_delete->bind_param("i", $new_assignment_id);
                             $stmt_temp_delete->execute();
                             $stmt_temp_delete->close();
                         }
                         echo json_encode($response);
                         exit();
                     }
                     $uploaded_files_info = $file_handling_result['files'] ?? [];
                }

                $conn->commit(); 

                $response['status'] = 'success';
                $response['message'] = 'Завдання успішно створено!';
                $response['assignment'] = [
                    'assignment_id' => $new_assignment_id,
                    'course_id' => $course_id_form,
                    'title' => htmlspecialchars($title),
                    'description' => htmlspecialchars($description),
                    'max_points' => $max_points,
                    'due_date' => $due_date_sql,
                    'section_title' => $section_title ? htmlspecialchars($section_title) : null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_at_formatted' => date('d.m.Y H:i'),
                    'updated_at_formatted' => null, 
                    'due_date_formatted' => $due_date_obj->format('d.m.Y H:i'),
                    'is_deadline_soon' => false, 
                    'submission_status' => 'pending_submission',
                    'attached_files' => $uploaded_files_info 
                ];
            } else {
                $conn->rollback();
                $response['message'] = 'Помилка створення завдання в БД: ' . $stmt_insert_assignment->error;
                error_log('DB assignment creation error: ' . $stmt_insert_assignment->error);
            }
            $stmt_insert_assignment->close();
        } else {
            $conn->rollback();
            $response['message'] = 'Помилка підготовки запиту для створення завдання: ' . $conn->error;
            error_log('DB assignment prepare error: ' . $conn->error);
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для створення завдання.';
    }
} elseif ($action === 'get_assignments') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $course_id_get = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
        $sort_by = $_GET['sort_by'] ?? 'due_date_asc'; 

        if (!$course_id_get) {
            $response['message'] = 'ID курсу не вказано.';
            echo json_encode($response);
            exit();
        }
        
        $can_view_sql_asm = "SELECT 
                            CASE
                                WHEN EXISTS (SELECT 1 FROM courses WHERE course_id = ? AND author_id = ?) THEN 1
                                WHEN EXISTS (SELECT 1 FROM enrollments WHERE course_id = ? AND student_id = ?) THEN 1
                                ELSE 0
                            END AS can_view";
        $stmt_can_view_asm = $conn->prepare($can_view_sql_asm);
        if ($stmt_can_view_asm) {
            $stmt_can_view_asm->bind_param("iiii", $course_id_get, $current_user_id, $course_id_get, $current_user_id);
            $stmt_can_view_asm->execute();
            $result_can_view_asm = $stmt_can_view_asm->get_result();
            $can_view_data_asm = $result_can_view_asm->fetch_assoc();
            $stmt_can_view_asm->close();

            if (!$can_view_data_asm || $can_view_data_asm['can_view'] != 1) {
                $response['message'] = 'У вас немає доступу для перегляду завдань цього курсу.';
                echo json_encode($response);
                exit();
            }
        } else {
             $response['message'] = 'Помилка перевірки доступу до завдань: ' . $conn->error;
             error_log("DB error checking assignment view permissions: " . $conn->error);
             echo json_encode($response);
             exit();
        }

        $assignments_list = [];
        $sql_assignments_base = "SELECT assignment_id, title, description, max_points, due_date, section_title, created_at, updated_at
                                 FROM assignments
                                 WHERE course_id = ?";

        $order_by_clause = "";
        switch ($sort_by) {
            case 'created_at_desc':
                $order_by_clause = " ORDER BY created_at DESC, ISNULL(due_date), due_date ASC";
                break;
            case 'created_at_asc':
                $order_by_clause = " ORDER BY created_at ASC, ISNULL(due_date), due_date ASC";
                break;
            case 'due_date_desc':
                 $order_by_clause = " ORDER BY ISNULL(due_date), due_date DESC, created_at DESC";
                break;
            case 'due_date_asc':
            default:
                $order_by_clause = " ORDER BY ISNULL(due_date), due_date ASC, created_at DESC";
                break;
        }
        $sql_assignments = $sql_assignments_base . $order_by_clause;

        $stmt_assignments = $conn->prepare($sql_assignments);
        if ($stmt_assignments) {
            $stmt_assignments->bind_param("i", $course_id_get);
            $stmt_assignments->execute();
            $result_assignments = $stmt_assignments->get_result();
            $now = new DateTime(); 
            $is_current_user_teacher_of_this_course = isUserTeacherOfCourse($conn, $current_user_id, $course_id_get); 

            while ($row = $result_assignments->fetch_assoc()) {
                $row['is_deadline_soon'] = false;
                $due_date_obj = null; 
                if ($row['due_date']) {
                    try { 
                        $due_date_obj = new DateTime($row['due_date']);
                        $time_diff = $now->diff($due_date_obj);
                        $row['is_deadline_soon'] = ($due_date_obj > $now && $time_diff->days <= 3 && !$time_diff->invert);
                        $row['due_date_formatted'] = $due_date_obj->format('d.m.Y H:i');
                    } catch (Exception $e) {
                        error_log("Invalid due_date format for assignment " . $row['assignment_id'] . ": " . $row['due_date']);
                        $row['due_date_formatted'] = 'Некоректна дата';
                    }
                } else {
                    $row['due_date_formatted'] = 'Не вказано';
                }
                $created_at_obj = null; 
                try { 
                    $created_at_obj = new DateTime($row['created_at']);
                    $row['created_at_formatted'] = $created_at_obj->format('d.m.Y H:i');
                } catch (Exception $e) {
                    error_log("Invalid created_at format for assignment " . $row['assignment_id'] . ": " . $row['created_at']);
                    $row['created_at_formatted'] = 'Некоректна дата';
                }
                try { 
                    $updated_at_obj = new DateTime($row['updated_at']);
                    if ($created_at_obj && $updated_at_obj->getTimestamp() > $created_at_obj->getTimestamp() + 5) { 
                        $row['updated_at_formatted'] = $updated_at_obj->format('d.m.Y H:i');
                    } else {
                        $row['updated_at_formatted'] = null;
                    }
                } catch (Exception $e) {
                     error_log("Invalid updated_at format for assignment " . $row['assignment_id'] . ": " . $row['updated_at']);
                     $row['updated_at_formatted'] = null;
                }
                
                $row['submission_status'] = 'not_applicable'; 
                if (!$is_current_user_teacher_of_this_course) {
                    $stmt_submission_status = $conn->prepare("SELECT status FROM submissions WHERE assignment_id = ? AND student_id = ? ORDER BY submission_date DESC LIMIT 1");
                    if ($stmt_submission_status) {
                        $stmt_submission_status->bind_param("ii", $row['assignment_id'], $current_user_id);
                        $stmt_submission_status->execute();
                        $result_submission_status = $stmt_submission_status->get_result();
                        if ($submission = $result_submission_status->fetch_assoc()) {
                            $row['submission_status'] = $submission['status'];
                        } else {
                            if ($due_date_obj && $due_date_obj < $now) {
                                $row['submission_status'] = 'missed';
                            } else {
                                $row['submission_status'] = 'pending_submission';
                            }
                        }
                        $stmt_submission_status->close();
                    } else {
                        error_log("DB prepare error for submission status: " . $conn->error);
                        $row['submission_status'] = 'error_fetching_status'; 
                    }
                }
                
                $row['title'] = htmlspecialchars($row['title']);
                $row['description'] = htmlspecialchars($row['description'] ?? ''); 
                $row['section_title'] = $row['section_title'] ? htmlspecialchars($row['section_title']) : null;

                $assignments_list[] = $row;
            }
            $stmt_assignments->close();
            $response['status'] = 'success';
            $response['assignments'] = $assignments_list;
            $response['is_teacher_of_course'] = $is_current_user_teacher_of_this_course;
            unset($response['message']);

        } else {
            $response['message'] = 'Помилка отримання списку завдань: ' . $conn->error;
            error_log('DB get assignments error: ' . $conn->error);
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для отримання завдань.';
    }
}
elseif ($action === 'get_assignment_details_for_edit') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $assignment_id_get = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);

        if (!$assignment_id_get) {
            $response['message'] = 'ID завдання не вказано.';
            echo json_encode($response);
            exit();
        }

        $stmt_course_id_check = $conn->prepare("SELECT course_id FROM assignments WHERE assignment_id = ?"); // Renamed variable for clarity
        if (!$stmt_course_id_check) {
             $response['message'] = 'Помилка підготовки запиту (перевірка ID курсу).';
             error_log("DB prepare error (course_id check for edit assignment): " . $conn->error);
             echo json_encode($response);
             exit();
        }
        $stmt_course_id_check->bind_param("i", $assignment_id_get);
        $stmt_course_id_check->execute();
        $result_course_id = $stmt_course_id_check->get_result();
        if (!($course_data_row = $result_course_id->fetch_assoc())) {
            $response['message'] = 'Завдання не знайдено (для перевірки курсу).';
            $stmt_course_id_check->close();
            echo json_encode($response);
            exit();
        }
        $course_id_for_check = $course_data_row['course_id'];
        $stmt_course_id_check->close();

        if (!isUserTeacherOfCourse($conn, $current_user_id, $course_id_for_check)) {
            $response['message'] = 'У вас немає прав для редагування цього завдання.';
            echo json_encode($response);
            exit();
        }

        $stmt_assignment_details = $conn->prepare("SELECT assignment_id, title, description, max_points, due_date, section_title FROM assignments WHERE assignment_id = ?");
        if ($stmt_assignment_details) {
            $stmt_assignment_details->bind_param("i", $assignment_id_get);
            $stmt_assignment_details->execute();
            $result_assignment_details = $stmt_assignment_details->get_result();
            if ($assignment_data = $result_assignment_details->fetch_assoc()) {
                $assignment_data['title'] = htmlspecialchars_decode($assignment_data['title'] ?? '', ENT_QUOTES);
                $assignment_data['description'] = htmlspecialchars_decode($assignment_data['description'] ?? '', ENT_QUOTES);
                $assignment_data['section_title'] = $assignment_data['section_title'] ? htmlspecialchars_decode($assignment_data['section_title'], ENT_QUOTES) : null;
                
                $attached_files_list = [];
                $stmt_files = $conn->prepare("SELECT file_id, file_name, file_path FROM assignment_files WHERE assignment_id = ? ORDER BY file_name ASC");
                if ($stmt_files) {
                    $stmt_files->bind_param("i", $assignment_id_get);
                    $stmt_files->execute();
                    $result_files = $stmt_files->get_result();
                    while ($file_row = $result_files->fetch_assoc()) {
                        $attached_files_list[] = $file_row;
                    }
                    $stmt_files->close();
                } else {
                    error_log('DB get_assignment_details_for_edit (files) prepare error: ' . $conn->error);
                }
                $assignment_data['attached_files'] = $attached_files_list;

                $response['status'] = 'success';
                $response['assignment'] = $assignment_data;
                 unset($response['message']);
            } else {
                $response['message'] = 'Завдання не знайдено.';
            }
            $stmt_assignment_details->close();
        } else {
            $response['message'] = 'Помилка отримання даних завдання: ' . $conn->error;
            error_log('DB get assignment details for edit error: ' . $conn->error);
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для отримання деталей завдання.';
    }
}
elseif ($action === 'update_assignment') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $assignment_id_edit = filter_input(INPUT_POST, 'assignment_id_edit', FILTER_VALIDATE_INT);
        $course_id_form = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT); 
        $title = trim($_POST['assignment_title'] ?? '');
        $description = trim($_POST['assignment_description'] ?? '');
        $max_points = filter_input(INPUT_POST, 'assignment_max_points', FILTER_VALIDATE_INT);
        $due_date_str = trim($_POST['assignment_due_date'] ?? '');
        $section_title = trim($_POST['assignment_section_title'] ?? null);
        if ($section_title === '') $section_title = null;
        $files_to_delete_ids = isset($_POST['delete_files']) && is_array($_POST['delete_files']) ? $_POST['delete_files'] : [];


        if (!$assignment_id_edit || !$course_id_form) { 
            $response['message'] = 'ID завдання або курсу не вказано.';
            echo json_encode($response);
            exit();
        }
        if (!isUserTeacherOfCourse($conn, $current_user_id, $course_id_form)) { 
            $response['message'] = 'У вас немає прав для оновлення завдань в цьому курсі.';
            echo json_encode($response);
            exit();
        }
        if (empty($title) || $max_points === false || $max_points < 0 || empty($due_date_str)) {
            $response['message'] = 'Будь ласка, заповніть усі обов\'язкові поля: назва, бали, дата здачі.';
            echo json_encode($response);
            exit();
        }
         if ($max_points > 100) {
            $response['message'] = 'Максимальна кількість балів не може перевищувати 100.';
            echo json_encode($response);
            exit();
        }
        try {
            $due_date_obj = new DateTime($due_date_str);
            $due_date_sql = $due_date_obj->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $response['message'] = 'Некоректний формат дати здачі.';
            echo json_encode($response);
            exit();
        }
        
        $conn->begin_transaction();

        try {
            if (!empty($files_to_delete_ids)) {
                $placeholders = implode(',', array_fill(0, count($files_to_delete_ids), '?'));
                $types = str_repeat('i', count($files_to_delete_ids));
                
                $stmt_get_files_to_delete = $conn->prepare("SELECT file_path FROM assignment_files WHERE file_id IN ($placeholders) AND assignment_id = ?");
                if ($stmt_get_files_to_delete) {
                    $params_for_get = $files_to_delete_ids; // bind_param needs variables
                    $params_for_get[] = $assignment_id_edit; // Add assignment_id at the end
                    
                    $bind_types = $types . "i";
                    $stmt_get_files_to_delete->bind_param($bind_types, ...$params_for_get);
                    $stmt_get_files_to_delete->execute();
                    $result_files_to_delete = $stmt_get_files_to_delete->get_result();
                    $base_upload_dir_server_edit = dirname(__DIR__, 2) . '/public/';
                    while ($file_row = $result_files_to_delete->fetch_assoc()) {
                        $file_server_path = $base_upload_dir_server_edit . $file_row['file_path'];
                        if (file_exists($file_server_path) && is_file($file_server_path)) {
                            if (!unlink($file_server_path)) {
                                error_log("Could not delete assignment attachment file from server: " . $file_server_path);
                                // Decide if this is a critical error to rollback
                            }
                        }
                    }
                    $stmt_get_files_to_delete->close();

                    $stmt_delete_marked_files = $conn->prepare("DELETE FROM assignment_files WHERE file_id IN ($placeholders) AND assignment_id = ?");
                    if ($stmt_delete_marked_files) {
                        $stmt_delete_marked_files->bind_param($bind_types, ...$params_for_get); 
                        if (!$stmt_delete_marked_files->execute()) {
                            throw new Exception('Помилка видалення позначених файлів з БД: ' . $stmt_delete_marked_files->error);
                        }
                        $stmt_delete_marked_files->close();
                    } else {
                        throw new Exception('Помилка підготовки запиту для видалення позначених файлів: ' . $conn->error);
                    }
                } else {
                     throw new Exception('Помилка підготовки запиту для отримання шляхів файлів для видалення: ' . $conn->error);
                }
            }

            if (isset($_FILES['assignment_files_edit']) && is_array($_FILES['assignment_files_edit']['name']) && !empty($_FILES['assignment_files_edit']['name'][0])) {
                $file_handling_result = handleUploadedAssignmentFiles($conn, $assignment_id_edit, $course_id_form, $_FILES['assignment_files_edit']);
                if (isset($file_handling_result['error'])) {
                    throw new Exception($file_handling_result['error']);
                }
            }

            $stmt_update_assignment = $conn->prepare("UPDATE assignments SET title = ?, description = ?, max_points = ?, due_date = ?, section_title = ?, updated_at = NOW() WHERE assignment_id = ? AND course_id = ?");
            if ($stmt_update_assignment) {
                $stmt_update_assignment->bind_param("ssissii", $title, $description, $max_points, $due_date_sql, $section_title, $assignment_id_edit, $course_id_form);
                if ($stmt_update_assignment->execute()) {
                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = 'Завдання успішно оновлено!';
                } else {
                    throw new Exception('Помилка оновлення завдання в БД: ' . $stmt_update_assignment->error);
                }
                $stmt_update_assignment->close();
            } else {
                throw new Exception('Помилка підготовки запиту для оновлення завдання: ' . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = $e->getMessage();
            error_log('DB assignment update/file handling error: ' . $e->getMessage());
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для оновлення завдання.';
    }
}
elseif ($action === 'delete_assignment') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $assignment_id_delete = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
        $course_id_form = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT); 

        if (!$assignment_id_delete || !$course_id_form) { 
            $response['message'] = 'ID завдання або курсу не вказано.';
            echo json_encode($response);
            exit();
        }
        if (!isUserTeacherOfCourse($conn, $current_user_id, $course_id_form)) { 
            $response['message'] = 'У вас немає прав для видалення завдань в цьому курсі.';
            echo json_encode($response);
            exit();
        }

        $conn->begin_transaction(); 
        try {
            
            $stmt_get_assignment_files = $conn->prepare("SELECT file_path FROM assignment_files WHERE assignment_id = ?");
            if (!$stmt_get_assignment_files) {
                throw new Exception("Помилка підготовки отримання файлів завдання: " . $conn->error);
            }
            $stmt_get_assignment_files->bind_param("i", $assignment_id_delete);
            $stmt_get_assignment_files->execute();
            $result_assignment_files = $stmt_get_assignment_files->get_result();
            $base_upload_dir_server_delete = dirname(__DIR__, 2) . '/public/';
            while ($file_row = $result_assignment_files->fetch_assoc()) {
                $file_to_delete_server_path = $base_upload_dir_server_delete . $file_row['file_path'];
                if (file_exists($file_to_delete_server_path) && is_file($file_to_delete_server_path)) {
                    if(!unlink($file_to_delete_server_path)){
                        error_log("Could not delete assignment attachment file: " . $file_to_delete_server_path);
                    }
                }
            }
            $stmt_get_assignment_files->close();
            // Записи з assignment_files видаляться через ON DELETE CASCADE при видаленні завдання


            $stmt_get_submission_files = $conn->prepare("SELECT file_path FROM submissions WHERE assignment_id = ? AND file_path IS NOT NULL");
            if (!$stmt_get_submission_files) {
                throw new Exception("Помилка підготовки отримання файлів здач: " . $conn->error);
            }
            $stmt_get_submission_files->bind_param("i", $assignment_id_delete);
            $stmt_get_submission_files->execute();
            $result_submission_files = $stmt_get_submission_files->get_result();
            while ($file_row = $result_submission_files->fetch_assoc()) {
                $file_to_delete_server_path = dirname(__DIR__, 2) . '/public/' . $file_row['file_path'];
                if (file_exists($file_to_delete_server_path) && is_file($file_to_delete_server_path)) {
                    if(!unlink($file_to_delete_server_path)){
                        error_log("Could not delete submission file: " . $file_to_delete_server_path);
                    }
                }
            }
            $stmt_get_submission_files->close();

            // Записи з submissions також видаляться через ON DELETE CASCADE при видаленні завдання

            $stmt_delete_assignment = $conn->prepare("DELETE FROM assignments WHERE assignment_id = ? AND course_id = ?");
            if (!$stmt_delete_assignment) {
                throw new Exception("Помилка підготовки запиту для видалення завдання: " . $conn->error);
            }
            $stmt_delete_assignment->bind_param("ii", $assignment_id_delete, $course_id_form);
            if ($stmt_delete_assignment->execute()) {
                if ($stmt_delete_assignment->affected_rows > 0) {
                    $conn->commit(); 
                    $response['status'] = 'success';
                    $response['message'] = 'Завдання та всі пов\'язані з ним дані (здачі, файли) успішно видалено!';
                } else {
                    $conn->rollback(); 
                    $response['message'] = 'Завдання не знайдено для видалення або вже було видалено.';
                }
            } else {
                throw new Exception("Помилка видалення завдання з БД: " . $stmt_delete_assignment->error);
            }
            $stmt_delete_assignment->close();
        } catch (Exception $e) {
            $conn->rollback(); 
            $response['message'] = $e->getMessage();
            error_log('DB assignment/submissions/attachments delete error: ' . $e->getMessage());
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для видалення завдання.';
    }
}
elseif ($action === 'get_assignment_submission_details') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $assignment_id = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);

        if (!$assignment_id) {
            $response['message'] = 'ID завдання не вказано.';
            echo json_encode($response);
            exit();
        }

        $assignment_details = null;
        $submission_details = null;
        $is_teacher_of_course = false; 

        $stmt_ass = $conn->prepare(
            "SELECT a.*, c.author_id as course_author_id, c.course_id 
             FROM assignments a 
             JOIN courses c ON a.course_id = c.course_id 
             WHERE a.assignment_id = ?"
        );
        if ($stmt_ass) {
            $stmt_ass->bind_param("i", $assignment_id);
            $stmt_ass->execute();
            $result_ass = $stmt_ass->get_result();
            if ($row_ass = $result_ass->fetch_assoc()) {
                $assignment_details = $row_ass;
                $is_teacher_of_course = ($current_user_id == $assignment_details['course_author_id']);

                if (!$is_teacher_of_course) {
                    $stmt_check_enrollment = $conn->prepare("SELECT 1 FROM enrollments WHERE course_id = ? AND student_id = ?");
                    if(!$stmt_check_enrollment) { 
                        error_log("Prepare failed for enrollment check (get_assignment_submission_details): " . $conn->error);
                        $response['message'] = 'Помилка перевірки зарахування на курс.';
                        echo json_encode($response);
                        exit();
                    }
                    $stmt_check_enrollment->bind_param("ii", $assignment_details['course_id'], $current_user_id);
                    $stmt_check_enrollment->execute();
                    if($stmt_check_enrollment->get_result()->num_rows == 0) {
                        $response['message'] = 'Ви не зараховані на курс, до якого належить це завдання.';
                        $stmt_ass->close();
                        $stmt_check_enrollment->close();
                        echo json_encode($response);
                        exit();
                    }
                    $stmt_check_enrollment->close();
                }
            }
            $stmt_ass->close();
        } else {
             error_log("Prepare failed for assignment details (get_assignment_submission_details): " . $conn->error);
             $response['message'] = 'Помилка отримання даних завдання.';
             echo json_encode($response);
             exit();
        }


        if (!$assignment_details) {
            $response['message'] = 'Завдання не знайдено.';
            echo json_encode($response);
            exit();
        }

        // Fetch teacher attachments for the assignment
        $teacher_attached_files = [];
        $stmt_teacher_files = $conn->prepare("SELECT file_id, file_name, file_path FROM assignment_files WHERE assignment_id = ? ORDER BY file_name ASC");
        if($stmt_teacher_files) {
            $stmt_teacher_files->bind_param("i", $assignment_id);
            $stmt_teacher_files->execute();
            $result_teacher_files = $stmt_teacher_files->get_result();
            while ($file_row = $result_teacher_files->fetch_assoc()) {
                $teacher_attached_files[] = $file_row;
            }
            $stmt_teacher_files->close();
        } else {
            error_log("DB prepare error for teacher_attached_files in get_assignment_submission_details: " . $conn->error);
        }
        $assignment_details['teacher_attachments'] = $teacher_attached_files;
        
        if (!$is_teacher_of_course) {
            $stmt_sub = $conn->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ? ORDER BY submission_date DESC LIMIT 1");
            if ($stmt_sub) {
                $stmt_sub->bind_param("ii", $assignment_id, $current_user_id);
                $stmt_sub->execute();
                $result_sub = $stmt_sub->get_result();
                if ($row_sub = $result_sub->fetch_assoc()) {
                    $submission_details = $row_sub;
                }
                $stmt_sub->close();
            } else {
                 error_log("DB prepare error for student submission details: " . $conn->error);
            }
        }

        $response['status'] = 'success';
        $response['assignment_details'] = $assignment_details;
        $response['submission_details'] = $submission_details; 
        $response['is_teacher_of_course'] = $is_teacher_of_course;
         unset($response['message']);

    } else {
        $response['message'] = 'Некоректний метод запиту.';
    }
} elseif ($action === 'submit_assignment') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($current_user_id)) {
        $assignment_id = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
        $submission_text = isset($_POST['submission_text']) ? trim($_POST['submission_text']) : null; 
        $assignment_data_for_paths = null; 

        if (!$assignment_id) {
            $response['message'] = 'ID завдання не вказано.';
            echo json_encode($response);
            exit();
        }

        $stmt_assignment_info = $conn->prepare("SELECT a.course_id, c.author_id FROM assignments a JOIN courses c ON a.course_id = c.course_id WHERE a.assignment_id = ?");
        if ($stmt_assignment_info) {
            $stmt_assignment_info->bind_param("i", $assignment_id);
            $stmt_assignment_info->execute();
            $result_assignment_info = $stmt_assignment_info->get_result();
            if ($assignment_info_row = $result_assignment_info->fetch_assoc()) {
                $assignment_data_for_paths = $assignment_info_row; 
                if ($assignment_info_row['author_id'] == $current_user_id) {
                    $response['message'] = 'Викладачі не можуть здавати завдання.';
                    $stmt_assignment_info->close();
                    echo json_encode($response);
                    exit();
                }
                $stmt_check_enrollment = $conn->prepare("SELECT 1 FROM enrollments WHERE course_id = ? AND student_id = ?");
                if(!$stmt_check_enrollment){
                     error_log("Prepare failed for enrollment check (submit_assignment): " . $conn->error);
                     $response['message'] = 'Помилка перевірки зарахування на курс.';
                     $stmt_assignment_info->close();
                     echo json_encode($response);
                     exit();
                }
                $stmt_check_enrollment->bind_param("ii", $assignment_info_row['course_id'], $current_user_id);
                $stmt_check_enrollment->execute();
                if($stmt_check_enrollment->get_result()->num_rows == 0) {
                    $response['message'] = 'Ви не можете здати завдання, оскільки не зараховані на цей курс.';
                     $stmt_check_enrollment->close();
                     $stmt_assignment_info->close();
                    echo json_encode($response);
                    exit();
                }
                $stmt_check_enrollment->close();
            } else {
                $response['message'] = 'Завдання або курс не знайдено.';
                $stmt_assignment_info->close();
                echo json_encode($response);
                exit();
            }
            $stmt_assignment_info->close();
        } else {
            $response['message'] = 'Помилка перевірки інформації про завдання: ' . $conn->error;
            error_log("DB assignment info check error for submission: " . $conn->error);
            echo json_encode($response);
            exit();
        }

        $file_path_db = null;
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == UPLOAD_ERR_OK) {
            if (!$assignment_data_for_paths || !isset($assignment_data_for_paths['course_id'])) {
                 $response['message'] = 'Помилка: не вдалося визначити ID курсу для збереження файлу.';
                 error_log('Failed to get course_id for submission file path.');
                 echo json_encode($response);
                 exit();
            }
            $base_dir_for_upload = dirname(__DIR__, 2) . '/public/'; // Changed to end with /public/
            $course_id_for_path = $assignment_data_for_paths['course_id'];
            $structure = 'uploads/submissions/course_' . $course_id_for_path . '/assignment_' . $assignment_id . '/student_' . $current_user_id . '/';
            $upload_dir_absolute = $base_dir_for_upload . $structure;


            if (!is_dir($upload_dir_absolute)) {
                if (!mkdir($upload_dir_absolute, 0775, true)) {
                    $response['message'] = 'Не вдалося створити директорію для завантаження файлу: ' . $upload_dir_absolute;
                    error_log('Failed to create submission directory: ' . $upload_dir_absolute);
                    echo json_encode($response);
                    exit();
                }
            }

            $file_name_original_submission = basename($_FILES['submission_file']['name']); // basename for security
            $file_extension = strtolower(pathinfo($file_name_original_submission, PATHINFO_EXTENSION));
            $allowed_extensions_submission = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'zip']; 
            if (!in_array($file_extension, $allowed_extensions_submission)) {
                 $response['message'] = 'Неприпустимий тип файлу. Дозволені: ' . implode(', ', $allowed_extensions_submission) . '.';
                 echo json_encode($response);
                 exit();
            }
            if ($_FILES['submission_file']['size'] > 5 * 1024 * 1024) { 
                $response['message'] = 'Файл занадто великий. Максимальний розмір - 5MB.';
                echo json_encode($response);
                exit();
            }
            
            $safe_original_name_submission = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $file_name_original_submission);
            $new_filename = uniqid('sub_', true) . '_' . $safe_original_name_submission;
            $upload_path_absolute_file = $upload_dir_absolute . $new_filename;

            if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $upload_path_absolute_file)) {
                $file_path_db = $structure . $new_filename; // Path relative to public/
            } else {
                $response['message'] = 'Помилка переміщення завантаженого файлу. Код помилки: ' . $_FILES['submission_file']['error'];
                error_log('File move error for submission. Target: ' . $upload_path_absolute_file . '. Error code: ' . $_FILES['submission_file']['error']);
                echo json_encode($response);
                exit();
            }
        } elseif (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] != UPLOAD_ERR_NO_FILE) {
            $response['message'] = 'Помилка завантаження файлу: код ' . $_FILES['submission_file']['error'];
            error_log('File upload error code for submission: ' . $_FILES['submission_file']['error']);
            echo json_encode($response);
            exit();
        }
        
        if (empty($file_path_db) && ($submission_text === null || $submission_text === '')) {
            $response['message'] = 'Ви повинні прикріпити файл або надати текстову відповідь.';
            echo json_encode($response);
            exit();
        }

        $stmt_check_sub = $conn->prepare("SELECT submission_id, file_path FROM submissions WHERE assignment_id = ? AND student_id = ? ORDER BY submission_date DESC LIMIT 1");
        if (!$stmt_check_sub) {
            $response['message'] = 'Помилка підготовки перевірки існуючої здачі: ' . $conn->error;
            error_log('DB check existing submission prepare error: ' . $conn->error);
            echo json_encode($response);
            exit();
        }
        $stmt_check_sub->bind_param("ii", $assignment_id, $current_user_id);
        $stmt_check_sub->execute();
        $result_check_sub = $stmt_check_sub->get_result();
        $existing_submission = $result_check_sub->fetch_assoc();
        $stmt_check_sub->close();

        if ($existing_submission) { 
            $submission_id_to_update = $existing_submission['submission_id'];
            $old_file_path_db = $existing_submission['file_path'];

            if ($file_path_db && $old_file_path_db) { 
                $old_file_server_path = dirname(__DIR__, 2) . '/public/' . $old_file_path_db;
                if (file_exists($old_file_server_path) && is_file($old_file_server_path)) {
                    unlink($old_file_server_path);
                }
            }
            $final_file_path_for_update = $file_path_db ?? $old_file_path_db; 

            $stmt_update_sub = $conn->prepare("UPDATE submissions SET submission_date = NOW(), file_path = ?, submission_text = ?, status = 'submitted', grade = NULL, graded_at = NULL, feedback = NULL WHERE submission_id = ?");
            if ($stmt_update_sub) {
                $stmt_update_sub->bind_param("ssi", $final_file_path_for_update, $submission_text, $submission_id_to_update);
                if ($stmt_update_sub->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Роботу успішно оновлено та здано!';
                } else {
                    $response['message'] = 'Помилка оновлення здачі: ' . $stmt_update_sub->error;
                    error_log('DB update submission execute error: ' . $stmt_update_sub->error);
                }
                $stmt_update_sub->close();
            } else {
                 $response['message'] = 'Помилка підготовки оновлення здачі: ' . $conn->error;
                 error_log('DB update submission prepare error: ' . $conn->error);
            }
        } else { 
            $stmt_insert_sub = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, submission_date, file_path, submission_text, status) VALUES (?, ?, NOW(), ?, ?, 'submitted')");
            if ($stmt_insert_sub) {
                $stmt_insert_sub->bind_param("iiss", $assignment_id, $current_user_id, $file_path_db, $submission_text);
                if ($stmt_insert_sub->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Роботу успішно здано!';
                } else {
                    $response['message'] = 'Помилка збереження здачі: ' . $stmt_insert_sub->error;
                     error_log('DB insert submission execute error: ' . $stmt_insert_sub->error);
                }
                $stmt_insert_sub->close();
            } else {
                 $response['message'] = 'Помилка підготовки збереження здачі: ' . $conn->error;
                 error_log('DB insert submission prepare error: ' . $conn->error);
            }
        }
    } else {
        $response['message'] = 'Некоректний метод або користувач не авторизований.';
    }
} 
elseif ($action === 'delete_course') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $course_id_to_delete = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

        if (!$course_id_to_delete) {
            $response['message'] = 'ID курсу для видалення не вказано.';
            echo json_encode($response);
            exit();
        }

        if (!isUserTeacherOfCourse($conn, $current_user_id, $course_id_to_delete)) {
            $response['message'] = 'У вас немає прав для видалення цього курсу.';
            echo json_encode($response);
            exit();
        }

        $conn->begin_transaction();
        try {
            $assignment_ids = [];
            $stmt_get_assignments = $conn->prepare("SELECT assignment_id FROM assignments WHERE course_id = ?");
            if(!$stmt_get_assignments) throw new Exception("Помилка підготовки отримання ID завдань: " . $conn->error);
            $stmt_get_assignments->bind_param("i", $course_id_to_delete);
            $stmt_get_assignments->execute();
            $result_assignments = $stmt_get_assignments->get_result();
            while ($row = $result_assignments->fetch_assoc()) {
                $assignment_ids[] = $row['assignment_id'];
            }
            $stmt_get_assignments->close();

            if (!empty($assignment_ids)) {
                $placeholders = implode(',', array_fill(0, count($assignment_ids), '?'));
                $types = str_repeat('i', count($assignment_ids));

                // Delete teacher's attachments for assignments
                $stmt_get_teacher_files = $conn->prepare("SELECT file_path FROM assignment_files WHERE assignment_id IN ($placeholders)");
                if (!$stmt_get_teacher_files) throw new Exception("Помилка підготовки отримання файлів завдань (викладача): " . $conn->error);
                $stmt_get_teacher_files->bind_param($types, ...$assignment_ids);
                $stmt_get_teacher_files->execute();
                $result_teacher_files = $stmt_get_teacher_files->get_result();
                $base_dir_for_delete = dirname(__DIR__, 2) . '/public/';
                while($file_row = $result_teacher_files->fetch_assoc()){
                    $file_to_delete_server_path = $base_dir_for_delete . $file_row['file_path'];
                    if (file_exists($file_to_delete_server_path) && is_file($file_to_delete_server_path)) {
                        if (!unlink($file_to_delete_server_path)) {
                             error_log("Could not delete assignment attachment file: " . $file_to_delete_server_path);
                        }
                    }
                     // Delete parent directories if they become empty (optional, more complex)
                    $file_dir = dirname($file_to_delete_server_path);
                    if (is_dir($file_dir) && count(scandir($file_dir)) == 2) { // . and ..
                        rmdir($file_dir);
                        // Potentially go up further if course/assignment dirs become empty
                        $assignment_dir = dirname($file_dir);
                        if (is_dir($assignment_dir) && count(scandir($assignment_dir)) == 2) rmdir($assignment_dir);
                        $course_dir = dirname($assignment_dir);
                         if (is_dir($course_dir) && count(scandir($course_dir)) == 2) rmdir($course_dir);
                    }
                }
                $stmt_get_teacher_files->close();
                // Records in assignment_files will be deleted by ON DELETE CASCADE when assignments are deleted

                // Delete students' submission files
                $stmt_get_submission_files = $conn->prepare("SELECT file_path FROM submissions WHERE assignment_id IN ($placeholders) AND file_path IS NOT NULL");
                if (!$stmt_get_submission_files) throw new Exception("Помилка підготовки отримання шляхів файлів здач: " . $conn->error);
                $stmt_get_submission_files->bind_param($types, ...$assignment_ids);
                $stmt_get_submission_files->execute();
                $result_submission_files = $stmt_get_submission_files->get_result();
                while($file_row = $result_submission_files->fetch_assoc()){
                    $file_to_delete_server_path = $base_dir_for_delete . $file_row['file_path'];
                    if (file_exists($file_to_delete_server_path) && is_file($file_to_delete_server_path)) {
                        if (!unlink($file_to_delete_server_path)) {
                             error_log("Could not delete submission file: " . $file_to_delete_server_path);
                        }
                    }
                     // Delete parent directories for submission files
                    $file_dir = dirname($file_to_delete_server_path);
                    if (is_dir($file_dir) && count(scandir($file_dir)) == 2) { rmdir($file_dir); }
                    $student_dir = dirname($file_dir);
                    if (is_dir($student_dir) && count(scandir($student_dir)) == 2) { rmdir($student_dir); }
                    // Assignment and course dirs will be handled with teacher attachments or if no attachments existed
                }
                $stmt_get_submission_files->close();
                // Records in submissions will be deleted by ON DELETE CASCADE when assignments are deleted
            }
            
            // Deleting assignments will cascade to assignment_files and submissions
            $stmt_delete_assignments = $conn->prepare("DELETE FROM assignments WHERE course_id = ?");
            if(!$stmt_delete_assignments) throw new Exception("Помилка підготовки видалення завдань: " . $conn->error);
            $stmt_delete_assignments->bind_param("i", $course_id_to_delete);
            if (!$stmt_delete_assignments->execute()) throw new Exception("Помилка видалення завдань: " . $stmt_delete_assignments->error);
            $stmt_delete_assignments->close();

            $stmt_delete_announcements = $conn->prepare("DELETE FROM course_announcements WHERE course_id = ?");
            if(!$stmt_delete_announcements) throw new Exception("Помилка підготовки видалення оголошень: " . $conn->error);
            $stmt_delete_announcements->bind_param("i", $course_id_to_delete);
            if (!$stmt_delete_announcements->execute()) throw new Exception("Помилка видалення оголошень: " . $stmt_delete_announcements->error);
            $stmt_delete_announcements->close();

            $stmt_delete_enrollments = $conn->prepare("DELETE FROM enrollments WHERE course_id = ?");
            if(!$stmt_delete_enrollments) throw new Exception("Помилка підготовки видалення зарахувань: " . $conn->error);
            $stmt_delete_enrollments->bind_param("i", $course_id_to_delete);
            if (!$stmt_delete_enrollments->execute()) throw new Exception("Помилка видалення зарахувань: " . $stmt_delete_enrollments->error);
            $stmt_delete_enrollments->close();

            $stmt_delete_course = $conn->prepare("DELETE FROM courses WHERE course_id = ? AND author_id = ?");
            if(!$stmt_delete_course) throw new Exception("Помилка підготовки видалення курсу: " . $conn->error);
            $stmt_delete_course->bind_param("ii", $course_id_to_delete, $current_user_id);
            if ($stmt_delete_course->execute()) {
                if ($stmt_delete_course->affected_rows > 0) {
                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = 'Курс та всі пов\'язані дані успішно видалено!';
                     // Try to remove the main course attachment directory if it's empty
                    $course_attachment_main_dir = dirname(__DIR__, 2) . '/public/uploads/assignments_attachments/course_' . $course_id_to_delete;
                    if (is_dir($course_attachment_main_dir) && count(scandir($course_attachment_main_dir)) == 2) { // Check if empty (only . and ..)
                        rmdir($course_attachment_main_dir);
                    }
                     // Try to remove the main course submission directory if it's empty
                    $course_submission_main_dir = dirname(__DIR__, 2) . '/public/uploads/submissions/course_' . $course_id_to_delete;
                    if (is_dir($course_submission_main_dir) && count(scandir($course_submission_main_dir)) == 2) {
                        rmdir($course_submission_main_dir);
                    }

                } else {
                    throw new Exception('Курс не знайдено для видалення, або ви не є його автором.');
                }
            } else {
                throw new Exception("Помилка виконання запиту на видалення курсу: " . $stmt_delete_course->error);
            }
            $stmt_delete_course->close();

        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Помилка видалення курсу: ' . $e->getMessage();
            error_log("Course deletion critical error: " . $e->getMessage() . " for course_id: " . $course_id_to_delete . " by user_id: " . $current_user_id);
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для видалення курсу.';
    }
}
else {
  $response['message'] = "Невідома дія: " . htmlspecialchars($action);
}

$conn->close();
echo json_encode($response);
?>