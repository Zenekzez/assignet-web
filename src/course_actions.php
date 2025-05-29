<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/connect.php'; 

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
    $stmt->close();
    return $result->num_rows > 0;
}


if ($action === 'create_announcement') {
    // ... (код як у попередній відповіді) ...
     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
        $content = trim($_POST['announcement_content'] ?? '');

        if (!$course_id || empty($content)) {
            $response['message'] = 'ID курсу або вміст оголошення не можуть бути порожніми.';
        } elseif (!isUserTeacherOfCourse($conn, $current_user_id, $course_id)) {
            $response['message'] = 'У вас немає прав для публікації оголошень в цьому курсі.';
        } else {
            $stmt = $conn->prepare("INSERT INTO course_announcements (course_id, user_id, content) VALUES (?, ?, ?)");
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
                        'author_username' => $_SESSION['username'] 
                    ];
                } else {
                    $response['message'] = 'Помилка публікації оголошення: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $response['message'] = 'Помилка підготовки запиту: ' . $conn->error;
            }
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для створення оголошення.';
    }
} elseif ($action === 'get_announcements') {
    // ... (код як у попередній відповіді) ...
     if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

        if (!$course_id) {
            $response['message'] = 'ID курсу не вказано.';
        } else {
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
                    $row['content'] = htmlspecialchars($row['content']); 
                    $announcements[] = $row;
                }
                $response['status'] = 'success';
                $response['announcements'] = $announcements;
                unset($response['message']); 
                $stmt->close();
            } else {
                $response['message'] = 'Помилка отримання оголошень: ' . $conn->error;
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
        $join_code_visible = isset($_POST['join_code_visible']) && $_POST['join_code_visible'] == '1' ? 1 : 0; // Обробка чекбоксу

        if (!$course_id || empty($course_name)) {
            $response['message'] = 'ID курсу або назва курсу не можуть бути порожніми.';
        } elseif (!preg_match('/^#[0-9A-Fa-f]{6}$/i', $color)) { // Додав i для нечутливості до регістру
             $response['message'] = 'Некоректний формат кольору. Очікується HEX (напр. #RRGGBB).';
        } elseif (!isUserTeacherOfCourse($conn, $current_user_id, $course_id)) {
            $response['message'] = 'У вас немає прав для зміни налаштувань цього курсу.';
        } else {
            $stmt = $conn->prepare("UPDATE courses SET course_name = ?, description = ?, color = ?, join_code_visible = ? WHERE course_id = ?");
            if ($stmt) {
                $stmt->bind_param("sssii", $course_name, $description, $color, $join_code_visible, $course_id);
                if ($stmt->execute()) {
                     $response['status'] = 'success'; // Навіть якщо нічого не змінилося, запит успішний
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
                $response['message'] = 'Помилка підготовки запиту: ' . $conn->error;
                error_log("Course settings prepare error: " . $conn->error);
            }
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для оновлення налаштувань.';
    }
}

// ... (існуючі блоки if/elseif для інших actions) ...

// НОВИЙ БЛОК ДЛЯ СТВОРЕННЯ ЗАВДАННЯ
elseif ($action === 'create_assignment') {
    $course_id_form = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

    // КРИТИЧНО: Перевірка, чи є користувач викладачем цього курсу
    if (!$course_id_form || !isUserTeacherOfCourse($conn, $current_user_id, $course_id_form)) { //
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
        if ($section_title === '') { // Якщо передано порожній рядок, зберігаємо NULL
            $section_title = null;
        }

         if ($max_points !== false && $max_points > 100) {
            $response['message'] = 'Максимальна кількість балів не може перевищувати 100.';
            echo json_encode($response);
            exit();
        }
        
        if (empty($title) || $max_points === false || $max_points < 0 || empty($due_date_str)) {
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

        $stmt_insert_assignment = $conn->prepare("INSERT INTO assignments (course_id, title, description, max_points, due_date, section_title, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        if ($stmt_insert_assignment) {
            $stmt_insert_assignment->bind_param("ississ", $course_id_form, $title, $description, $max_points, $due_date_sql, $section_title);
            if ($stmt_insert_assignment->execute()) {
                $new_assignment_id = $stmt_insert_assignment->insert_id;
                $response['status'] = 'success';
                $response['message'] = 'Завдання успішно створено!';
                // Повертаємо дані про створене завдання, щоб JavaScript міг його додати до списку без перезавантаження
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
                    'updated_at_formatted' => null, // Бо щойно створене
                    'due_date_formatted' => $due_date_obj->format('d.m.Y H:i'),
                    'is_deadline_soon' => false, // Розрахунок is_deadline_soon краще робити при отриманні
                    'submission_status' => 'pending_submission' // Для нового завдання у студента ще немає здачі
                ];
            } else {
                $response['message'] = 'Помилка створення завдання в БД: ' . $stmt_insert_assignment->error;
                error_log('DB assignment creation error: ' . $stmt_insert_assignment->error);
            }
            $stmt_insert_assignment->close();
        } else {
            $response['message'] = 'Помилка підготовки запиту для створення завдання: ' . $conn->error;
            error_log('DB assignment prepare error: ' . $conn->error);
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для створення завдання.';
    }

// НОВИЙ БЛОК ДЛЯ ОТРИМАННЯ ЗАВДАНЬ
} elseif ($action === 'get_assignments') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $course_id_get = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
        $sort_by = $_GET['sort_by'] ?? 'due_date_asc'; // Отримуємо параметр сортування

        if (!$course_id_get) {
            $response['message'] = 'ID курсу не вказано.';
            echo json_encode($response);
            exit();
        }

        $assignments_list = [];
        // Базовий SQL, сортування буде додано динамічно
        $sql_assignments_base = "SELECT assignment_id, title, description, max_points, due_date, section_title, created_at, updated_at
                                 FROM assignments
                                 WHERE course_id = ?";

        // Динамічне додавання ORDER BY
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
            $now = new DateTime(); // Поточний час

            // Визначаємо, чи є поточний користувач викладачем цього курсу
            $is_current_user_teacher_of_this_course = isUserTeacherOfCourse($conn, $current_user_id, $course_id_get); //

            while ($row = $result_assignments->fetch_assoc()) {
                $row['is_deadline_soon'] = false;
                if ($row['due_date']) {
                    $due_date_obj = new DateTime($row['due_date']);
                    $time_diff = $now->diff($due_date_obj);
                    $row['is_deadline_soon'] = ($due_date_obj > $now && $time_diff->days <= 3 && !$time_diff->invert);
                    $row['due_date_formatted'] = $due_date_obj->format('d.m.Y H:i');
                } else {
                    $row['due_date_formatted'] = 'Не вказано';
                }

                $created_at_obj = new DateTime($row['created_at']);
                $row['created_at_formatted'] = $created_at_obj->format('d.m.Y H:i');

                $updated_at_obj = new DateTime($row['updated_at']);
                if ($updated_at_obj->getTimestamp() > $created_at_obj->getTimestamp() + 5) {
                    $row['updated_at_formatted'] = $updated_at_obj->format('d.m.Y H:i');
                } else {
                    $row['updated_at_formatted'] = null;
                }

                // Отримання статусу здачі для студента
                $row['submission_status'] = 'not_applicable'; // Якщо викладач або не студент
                if (!$is_current_user_teacher_of_this_course) {
                    $stmt_submission_status = $conn->prepare("SELECT status FROM submissions WHERE assignment_id = ? AND student_id = ? ORDER BY submission_date DESC LIMIT 1");
                    if ($stmt_submission_status) {
                        $stmt_submission_status->bind_param("ii", $row['assignment_id'], $current_user_id);
                        $stmt_submission_status->execute();
                        $result_submission_status = $stmt_submission_status->get_result();
                        if ($submission = $result_submission_status->fetch_assoc()) {
                            $row['submission_status'] = $submission['status'];
                        } else {
                            $row['submission_status'] = 'pending_submission';
                        }
                        $stmt_submission_status->close();
                    } else {
                        $row['submission_status'] = 'error_fetching_status'; // Помилка запиту
                    }
                }
                
                // Екранування HTML для безпеки перед відправкою на клієнт
                $row['title'] = htmlspecialchars($row['title']);
                $row['description'] = htmlspecialchars($row['description'] ?? ''); // Опис може бути довгим, тому обережно з ним на картці
                $row['section_title'] = $row['section_title'] ? htmlspecialchars($row['section_title']) : null;


                $assignments_list[] = $row;
            }
            $stmt_assignments->close();
            $response['status'] = 'success';
            $response['assignments'] = $assignments_list;
            $response['is_teacher_of_course'] = $is_current_user_teacher_of_this_course;

        } else {
            $response['message'] = 'Помилка отримання списку завдань: ' . $conn->error;
            error_log('DB get assignments error: ' . $conn->error);
        }
    } else {
        $response['message'] = 'Некоректний метод запиту для отримання завдань.';
    }
}

// ДЛЯ СТОРІНКИ assignment_view.php - деталі завдання + статус здачі студента
elseif ($action === 'get_assignment_submission_details') { // JavaScript буде викликати цю дію
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $assignment_id = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);
        // $current_user_id вже має бути визначено з сесії

        if (!$assignment_id) {
            $response['message'] = 'ID завдання не вказано.';
            echo json_encode($response);
            exit();
        }

        $assignment_details = null;
        $submission_details = null;

        // 1. Отримати деталі завдання
        $stmt_ass = $conn->prepare("SELECT a.*, c.author_id as course_author_id FROM assignments a JOIN courses c ON a.course_id = c.course_id WHERE a.assignment_id = ?");
        if ($stmt_ass) {
            $stmt_ass->bind_param("i", $assignment_id);
            $stmt_ass->execute();
            $result_ass = $stmt_ass->get_result();
            if ($row_ass = $result_ass->fetch_assoc()) {
                $assignment_details = $row_ass;
                // Екранування для безпечного виведення (якщо потрібно відправляти HTML)
                // $assignment_details['title'] = htmlspecialchars($row_ass['title']);
                // $assignment_details['description'] = nl2br(htmlspecialchars($row_ass['description'] ?? ''));
            }
            $stmt_ass->close();
        }

        if (!$assignment_details) {
            $response['message'] = 'Завдання не знайдено.';
            echo json_encode($response);
            exit();
        }
        
        $is_teacher_of_course = ($current_user_id == $assignment_details['course_author_id']);

        // 2. Якщо користувач не викладач, отримати деталі його здачі
        if (!$is_teacher_of_course) {
            $stmt_sub = $conn->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ? ORDER BY submission_date DESC LIMIT 1");
            if ($stmt_sub) {
                $stmt_sub->bind_param("ii", $assignment_id, $current_user_id);
                $stmt_sub->execute();
                $result_sub = $stmt_sub->get_result();
                if ($row_sub = $result_sub->fetch_assoc()) {
                    $submission_details = $row_sub;
                    // $submission_details['submission_text'] = nl2br(htmlspecialchars($row_sub['submission_text'] ?? ''));
                    // $submission_details['feedback'] = nl2br(htmlspecialchars($row_sub['feedback'] ?? ''));
                }
                $stmt_sub->close();
            }
        }

        $response['status'] = 'success';
        $response['assignment_details'] = $assignment_details;
        $response['submission_details'] = $submission_details; // Буде null, якщо викладач або студент ще не здавав
        $response['is_teacher_of_course'] = $is_teacher_of_course;

    } else {
        $response['message'] = 'Некоректний метод запиту.';
    }
} elseif ($action === 'submit_assignment') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($current_user_id)) {
        $assignment_id = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
        $submission_text = trim($_POST['submission_text'] ?? null);

        if (!$assignment_id) {
            $response['message'] = 'ID завдання не вказано.';
            echo json_encode($response);
            exit();
        }

        // Перевірка, чи користувач не є викладачем курсу (студенти не повинні здавати завдання у своїх курсах)
        // і чи курс взагалі існує для цього завдання
        $stmt_course_check = $conn->prepare("SELECT c.author_id FROM assignments a JOIN courses c ON a.course_id = c.course_id WHERE a.assignment_id = ?");
        if ($stmt_course_check) {
            $stmt_course_check->bind_param("i", $assignment_id);
            $stmt_course_check->execute();
            $course_info_res = $stmt_course_check->get_result();
            if ($course_info = $course_info_res->fetch_assoc()) {
                if ($course_info['author_id'] == $current_user_id) {
                    $response['message'] = 'Викладачі не можуть здавати завдання.';
                    echo json_encode($response);
                    exit();
                }
            } else {
                $response['message'] = 'Завдання або курс не знайдено.';
                echo json_encode($response);
                exit();
            }
            $stmt_course_check->close();
        }


        $file_path_db = null;
        // Обробка завантаження файлу
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == UPLOAD_ERR_OK) {
            $upload_dir_relative = '../public/uploads/submissions/'; // Шлях відносно цього скрипта (src)
            $upload_dir_absolute = realpath(__DIR__ . '/' . $upload_dir_relative);

            if (!$upload_dir_absolute) { // Якщо realpath не спрацював (шлях не існує)
                 // Спробуємо створити відносно __DIR__
                $upload_dir_absolute = __DIR__ . '/' . $upload_dir_relative;
            }
            $upload_dir_absolute .= '/course_' . $assignment_data['course_id'] . '/assignment_' . $assignment_id . '/student_' . $current_user_id . '/';


            if (!is_dir($upload_dir_absolute)) {
                if (!mkdir($upload_dir_absolute, 0775, true)) {
                    $response['message'] = 'Не вдалося створити директорію для завантаження файлу: ' . $upload_dir_absolute;
                    error_log('Failed to create submission directory: ' . $upload_dir_absolute);
                    echo json_encode($response);
                    exit();
                }
            }

            $file_extension = strtolower(pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION));
            // Додай перевірку на дозволені розширення файлів, якщо потрібно
            // $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png', 'zip'];
            // if (!in_array($file_extension, $allowed_extensions)) { ... }

            $new_filename = uniqid('sub_', true) . '.' . $file_extension;
            $upload_path_absolute_file = $upload_dir_absolute . $new_filename;

            if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $upload_path_absolute_file)) {
                // Шлях для збереження в БД (відносний до папки public)
                $file_path_db = 'uploads/submissions/course_' . $assignment_data['course_id'] . '/assignment_' . $assignment_id . '/student_' . $current_user_id . '/' . $new_filename;
            } else {
                $response['message'] = 'Помилка переміщення завантаженого файлу.';
                error_log('File move error for submission. Target: ' . $upload_path_absolute_file);
                echo json_encode($response);
                exit();
            }
        } elseif (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] != UPLOAD_ERR_NO_FILE) {
            $response['message'] = 'Помилка завантаження файлу: код ' . $_FILES['submission_file']['error'];
            echo json_encode($response);
            exit();
        }
        
        if (empty($file_path_db) && empty($submission_text)) {
            $response['message'] = 'Ви повинні прикріпити файл або надати текстову відповідь.';
            echo json_encode($response);
            exit();
        }


        // Перевірка, чи вже є здача, і оновлення або вставка
        $stmt_check_sub = $conn->prepare("SELECT submission_id FROM submissions WHERE assignment_id = ? AND student_id = ?");
        $stmt_check_sub->bind_param("ii", $assignment_id, $current_user_id);
        $stmt_check_sub->execute();
        $result_check_sub = $stmt_check_sub->get_result();
        $existing_submission = $result_check_sub->fetch_assoc();
        $stmt_check_sub->close();

        if ($existing_submission) { // Оновлюємо існуючу здачу
            $submission_id_to_update = $existing_submission['submission_id'];
            // Тут можна додати логіку видалення старого файлу, якщо він замінюється
            $stmt_update_sub = $conn->prepare("UPDATE submissions SET submission_date = NOW(), file_path = ?, submission_text = ?, status = 'submitted', grade = NULL, graded_at = NULL, feedback = NULL WHERE submission_id = ?");
            if ($stmt_update_sub) {
                $stmt_update_sub->bind_param("ssi", $file_path_db, $submission_text, $submission_id_to_update);
                if ($stmt_update_sub->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Роботу успішно оновлено та здано!';
                } else {
                    $response['message'] = 'Помилка оновлення здачі: ' . $stmt_update_sub->error;
                }
                $stmt_update_sub->close();
            } else {
                 $response['message'] = 'Помилка підготовки оновлення здачі: ' . $conn->error;
            }
        } else { // Вставляємо нову здачу
            $stmt_insert_sub = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, submission_date, file_path, submission_text, status) VALUES (?, ?, NOW(), ?, ?, 'submitted')");
            if ($stmt_insert_sub) {
                $stmt_insert_sub->bind_param("iiss", $assignment_id, $current_user_id, $file_path_db, $submission_text);
                if ($stmt_insert_sub->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = 'Роботу успішно здано!';
                } else {
                    $response['message'] = 'Помилка збереження здачі: ' . $stmt_insert_sub->error;
                }
                $stmt_insert_sub->close();
            } else {
                 $response['message'] = 'Помилка підготовки збереження здачі: ' . $conn->error;
            }
        }
    } else {
        $response['message'] = 'Некоректний метод або користувач не авторизований.';
    }
}

$conn->close();
echo json_encode($response);
?>