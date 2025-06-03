<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/templates/layout.php';
require_once __DIR__ . '/../../src/connect.php';

$course_id_get = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
$current_user_id_php = $_SESSION['user_id'] ?? null;
$course_data = null;
$author_username = 'Невідомий';
$is_teacher_php = false;
$banner_color_hex = '#007bff';
$page_title_course = 'Курс не знайдено';
$join_code_visible_db = true;
$course_join_code_from_db_php = null;
$actual_course_name_php = '';

if (!$current_user_id_php) {
    header("Location: login.php");
    exit();
}

if (!defined('WEB_ROOT_REL_FROM_HTML_CV')) { 
    define('WEB_ROOT_REL_FROM_HTML_CV_PHP', '../');
}
$default_avatar_rel_path_php = 'assets/default_avatar.png';


if (!$course_id_get) {
} else {
    $stmt_course = $conn->prepare("SELECT course_name, author_id, color, join_code, description, join_code_visible FROM courses WHERE course_id = ?");
    if ($stmt_course) {
        $stmt_course->bind_param("i", $course_id_get);
        $stmt_course->execute();
        $result_course = $stmt_course->get_result();
        if ($course_data_row = $result_course->fetch_assoc()) {
            $course_data = $course_data_row;
            $page_title_course = htmlspecialchars($course_data['course_name']);
            $actual_course_name_php = $course_data['course_name']; 
            $banner_color_hex = (!empty($course_data['color'])) ? htmlspecialchars($course_data['color']) : '#007bff';
            $join_code_visible_db = (bool)$course_data['join_code_visible'];
            $course_join_code_from_db_php = $course_data['join_code'];

            if ($current_user_id_php == $course_data['author_id']) {
                $is_teacher_php = true;
            } else {
                $stmt_check_enrollment = $conn->prepare("SELECT 1 FROM enrollments WHERE course_id = ? AND student_id = ?");
                if ($stmt_check_enrollment) {
                    $stmt_check_enrollment->bind_param("ii", $course_id_get, $current_user_id_php);
                    $stmt_check_enrollment->execute();
                    if($stmt_check_enrollment->get_result()->num_rows == 0) {
                        $course_data = null; 
                        $page_title_course = 'Доступ обмежено';
                    }
                    $stmt_check_enrollment->close();
                } else {
                    error_log("Failed to prepare statement for enrollment check: " . $conn->error);
                    $course_data = null; 
                }
            }

            if ($course_data) { 
                $stmt_author = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
                if ($stmt_author) {
                    $stmt_author->bind_param("i", $course_data['author_id']);
                    $stmt_author->execute();
                    $result_author = $stmt_author->get_result();
                    if ($author_user_row = $result_author->fetch_assoc()) {
                        $author_username = $author_user_row['username'];
                    }
                    $stmt_author->close();
                } else {
                     error_log("Failed to prepare statement for author username: " . $conn->error);
                }
            }
        } else {
            $course_data = null;
        }
        $stmt_course->close();
    } else {
        error_log("Failed to prepare statement for course data: " . $conn->error);
        $course_data = null; 
    }
}
?>

<title><?php echo $page_title_course; ?> - AssignNet</title>
<link rel="icon" href="public/assets/assignnet_logo.png" type="image/x-icon">
<link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML_CV_PHP; ?>css/course_view_styles.css">
<link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML_CV_PHP; ?>css/course_people_styles.css">
<link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML_CV_PHP; ?>css/grades_tab_styles.css">

<main class="page-content-wrapper">
    <div class="course-view-main-content">
        <?php if ($course_data): ?>
            <div class="course-header-bar">
                <div class="breadcrumbs">
                    <a href="home.php">Мої курси</a> &gt;
                    <span class="breadcrumb-course-name"><?php echo htmlspecialchars($course_data['course_name']); ?></span> &gt;
                    <span id="current-tab-breadcrumb">Стрічка</span>
                </div>
            </div>

            <div class="course-banner" style="background-color: <?php echo $banner_color_hex; ?>;">
                <h1 class="course-banner-title"><?php echo htmlspecialchars($course_data['course_name']); ?></h1>
                <?php
                if ( ($is_teacher_php && !empty($course_join_code_from_db_php)) || (!$is_teacher_php && $join_code_visible_db && !empty($course_join_code_from_db_php)) ) {
                    echo '<div class="course-join-code-container">';
                    echo '  <span class="course-join-code-label">Код курсу: </span>';
                    echo '  <strong id="courseJoinCodeTextForBanner">' . htmlspecialchars($course_join_code_from_db_php) . '</strong>';
                    echo '  <button type="button" id="copyJoinCodeBtnBanner" class="copy-join-code-btn" title="Копіювати код">';
                    echo '      <i class="fas fa-copy"></i>';
                    echo '  </button>';
                    echo '  <span id="copyJoinCodeFeedbackBanner" class="copy-feedback-message"></span>';
                    echo '</div>';
                }
                ?>
            </div>

            <?php if (!empty($course_data['description'])): ?>
            <div class="course-description-section">
                <h3><i class="fas fa-info-circle"></i> Про курс</h3>
                <p><?php echo nl2br(htmlspecialchars($course_data['description'])); ?></p>
            </div>
            <?php endif; ?>

            <nav class="course-tab-navigation">
                <a href="#" class="tab-link active" data-tab="stream">Стрічка</a>
                <a href="#" class="tab-link" data-tab="assignments">Завдання</a>
                <a href="#" class="tab-link" data-tab="people">Учасники</a>
                <?php if ($is_teacher_php): ?>
                    <a href="#" class="tab-link" data-tab="grades">Оцінки</a>
                    <a href="#" class="tab-link" data-tab="settings">Налаштування курсу</a>
                <?php else: ?>
                    <a href="#" class="tab-link" data-tab="my-grades">Мої оцінки</a>
                <?php endif; ?>
            </nav>

            <div id="course-tab-content" class="course-tab-content-area">
                <div id="tab-stream" class="tab-pane active">
                    <h2>Стрічка курсу</h2>
                    <?php if ($is_teacher_php): ?>
                        <form id="createAnnouncementForm" class="course-form">
                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id_get); ?>">
                            <div>
                                <label for="announcement_content">Нове оголошення:</label>
                                <textarea id="announcement_content" name="announcement_content" rows="4" required placeholder="Напишіть щось для курсу..."></textarea>
                            </div>
                            <button type="submit">Опублікувати</button>
                        </form>
                    <?php endif; ?>
                    <div id="announcementsArea">
                        <p><i class="fas fa-spinner fa-spin"></i> Завантаження оголошень...</p>
                    </div>
                </div>

                <div id="tab-assignments" class="tab-pane">
                    <h2>Завдання</h2>
                    <div class="assignments-tab-content-wrapper">                        <?php if ($is_teacher_php): ?>
                            <button id="showCreateAssignmentModalBtn" class="course-action-button">
                                <i class="fas fa-plus"></i> Створити завдання
                            </button>
                        <?php endif; ?>

                        <div id="assignmentSectionsFilterContainer" class="sections-filter-container" style="margin-top: 15px; margin-bottom: 15px;">
                        </div>

                        <div class="assignments-controls">
                            <label for="assignmentSortSelect">Сортувати:</label>
                            <select id="assignmentSortSelect" class="form-control-sm">
                                <option value="due_date_asc">Датою здачі (спочатку найближчі)</option>
                                <option value="due_date_desc">Датою здачі (спочатку найпізніші)</option>
                                <option value="created_at_desc">Датою публікації (спочатку нові)</option>
                                <option value="created_at_asc">Датою публікації (спочатку старі)</option>
                            </select>
                        </div>
                        <div id="assignmentsListArea">
                            <p><i class="fas fa-spinner fa-spin"></i> Завантаження завдань...</p>
                        </div>
                    </div>
                </div>

                <div id="tab-people" class="tab-pane">
                    <h2>Викладач</h2>
                    <div id="teacherInfoArea" class="people-list-section">
                        <p><i class="fas fa-spinner fa-spin"></i> Завантаження...</p>
                    </div>
                    <hr class="people-divider">
                    <div class="students-header">
                        <h2>Студенти</h2>
                        <span id="studentCount" class="student-count-badge">0</span>
                    </div>
                    <div id="studentsListArea" class="people-list-section">
                        <p>Завантаження списку студентів...</p>
                    </div>
                </div>

                <?php if ($is_teacher_php): ?>
                <div id="tab-grades" class="tab-pane">
                    <h2>Журнал оцінок</h2>
                    <div id="teacherGradesSummaryArea" class="grades-summary-container">
                        <p><i class="fas fa-spinner fa-spin"></i> Завантаження журналу оцінок...</p>
                    </div>
                </div>
                <div id="tab-settings" class="tab-pane">
                    <h2>Налаштування курсу</h2>
                    <form id="courseSettingsForm" class="course-form">
                         <input type="hidden" name="course_id_settings" value="<?php echo htmlspecialchars($course_id_get); ?>">
                        <div>
                            <label for="course_name_settings">Назва курсу:</label>
                            <input type="text" id="course_name_settings" name="course_name" value="<?php echo htmlspecialchars($course_data['course_name']); ?>" required>
                        </div>
                        <div>
                            <label for="course_description_settings">Опис курсу:</label>
                            <textarea id="course_description_settings" name="description" rows="3"><?php echo htmlspecialchars($course_data['description'] ?? ''); ?></textarea>
                        </div>
                        <div>
                            <label for="course_color_settings">Колір банера:</label>
                            <input type="color" id="course_color_settings" name="color" value="<?php echo $banner_color_hex; ?>">
                             <small>Цей колір буде використано для банера та картки курсу.</small>
                        </div>
                        <div>
                            <label>
                                <input type="checkbox" id="join_code_visible_settings" name="join_code_visible" value="1" <?php echo $join_code_visible_db ? 'checked' : ''; ?>>
                                Показувати код приєднання студентам
                            </label>
                        </div>

                        <div style="margin-top: 20px; margin-bottom: 20px; padding:15px; border: 1px solid #eee; border-radius: 5px; background-color:#f9f9f9;">
                            <label for="displayJoinCodeSettings" style="font-weight:bold;">Код приєднання до курсу:</label>
                            <div style="display:flex; align-items:center; margin-top:8px;">
                                <strong id="displayJoinCodeSettings" style="font-size: 1.2em; padding: 6px 10px; background-color: #e9ecef; border-radius: 4px; margin-right:10px; user-select:all;"><?php
                                    echo !empty($course_join_code_from_db_php) ? htmlspecialchars($course_join_code_from_db_php) : 'N/A';
                                ?></strong>
                                <button type="button" id="regenerateJoinCodeBtnCourseSettings" class="course-action-button" style="background-color: #6c757d; color:white; padding: 8px 12px; font-size: 0.85em;">
                                    <i class="fas fa-sync-alt"></i> Згенерувати новий
                                </button>
                            </div>
                            <small style="display: block; margin-top: 8px;">Старий код стане недійсним. Новий код зберігається одразу. Збережіть налаштування, щоб оновити видимість коду для студентів, якщо ви її змінили.</small>
                        </div>
                        <button type="submit">Зберегти налаштування</button>
                    </form>

                    <hr style="margin-top: 30px; margin-bottom: 30px;">

                    <div class="danger-zone-section">
                        <h3>Небезпечна зона</h3>
                        <p>Видалення курсу є незворотною дією. Будуть видалені всі завдання, оголошення, здані роботи та зарахування студентів.</p>
                        <button id="showDeleteCourseModalBtn" class="button-danger">
                            <i class="fas fa-trash-alt"></i> Видалити цей курс
                        </button>
                    </div>
                </div>
                <?php else: ?>
                <div id="tab-my-grades" class="tab-pane">
                    <h2>Мої оцінки</h2>
                    <div id="myGradesArea">
                        <p><i class="fas fa-spinner fa-spin"></i> Завантаження ваших оцінок...</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="course-not-found">
                <h1><?php echo $page_title_course; ?></h1>
                <p>
                    <?php
                    if (!$course_id_get) echo "ID курсу не вказано.";
                    elseif ($page_title_course === 'Доступ обмежено') echo "Ви не зараховані на цей курс або не маєте прав для його перегляду.";
                    else echo "Курс з ID " . htmlspecialchars($_GET['course_id'] ?? 'невідомим') . " не знайдено.";
                    ?>
                </p>
                <a href="home.php" class="button">Повернутися на головну</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php if ($is_teacher_php && $course_data): ?>
    <div id="createAssignmentModal" class="modal-overlay" style="display: none;">
        <div class="modal-content create-assignment-modal-content">
            <button class="modal-close-btn" id="closeCreateAssignmentModalBtn" aria-label="Закрити">&times;</button>
            <h2>Створити нове завдання</h2>
            <form id="createAssignmentFormInternal" class="course-form">
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id_get); ?>">
                <div class="form-group-modal-infield">
                    <label for="assignment_title_modal" class="form-label-infield">Назва завдання:</label>
                    <input type="text" id="assignment_title_modal" name="assignment_title" class="form-control-modal-infield" required>
                </div>
                <div class="form-group-modal-infield">
                    <label for="assignment_description_modal" class="form-label-infield">Опис:</label>
                    <textarea id="assignment_description_modal" name="assignment_description" rows="5" class="form-control-modal-infield"></textarea>
                </div>
                <div class="form-group-modal-infield">
                    <label for="assignment_section_create_modal" class="form-label-infield">Розділ/Тема (новий або існуючий):</label>
                    <input type="text" id="assignment_section_create_modal" name="assignment_section_title" class="form-control-modal-infield" list="existing_sections_list_create" placeholder="Наприклад: Тиждень 1, Модуль А">
                    <datalist id="existing_sections_list_create"></datalist>
                    <small>Залиште порожнім, щоб додати завдання без розділу.</small>
                </div>
                <div class="form-row-modal">
                    <div class="form-group-modal half-width">
                        <label for="assignment_max_points_modal">Макс. балів:</label>
                        <input type="number" id="assignment_max_points_modal" name="assignment_max_points" min="0" max="100" value="100" class="form-control-modal" required>
                    </div>
                    <div class="form-group-modal half-width">
                        <label for="assignment_due_date_modal">Дата та час здачі:</label>
                        <input type="datetime-local" id="assignment_due_date_modal" name="assignment_due_date" class="form-control-modal" required>
                    </div>
                </div>
                 <div class="form-group-modal-infield" style="margin-top: 15px;">
                    <label for="assignment_files_modal" class="form-label-infield" style="top: -8px;">Прикріпити файли (до 15MB кожен):</label>
                    <input type="file" id="assignment_files_modal" name="assignment_files[]" class="form-control-modal-infield" multiple style="padding-top: 12px; padding-bottom:12px; height: auto;">
                    <small>Ви можете вибрати декілька файлів. Дозволені типи: pdf, doc(x), ppt(x), xls(x), txt, zip, зображення, відео, аудіо.</small>
                </div>
                <button type="submit" class="submit-button-modal">Створити завдання</button>
            </form>
        </div>
    </div>

    <div id="editAssignmentModal" class="modal-overlay" style="display: none;">
        <div class="modal-content create-assignment-modal-content">
            <button class="modal-close-btn" id="closeEditAssignmentModalBtn" aria-label="Закрити">&times;</button>
            <h2>Редагувати завдання</h2>
            <form id="editAssignmentFormInternal" class="course-form">
                <input type="hidden" name="assignment_id_edit" id="assignment_id_edit">
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id_get); ?>">
                <div class="form-group-modal-infield">
                    <label for="assignment_title_edit_modal" class="form-label-infield">Назва завдання:</label>
                    <input type="text" id="assignment_title_edit_modal" name="assignment_title" class="form-control-modal-infield" required>
                </div>
                <div class="form-group-modal-infield">
                    <label for="assignment_description_edit_modal" class="form-label-infield">Опис:</label>
                    <textarea id="assignment_description_edit_modal" name="assignment_description" rows="5" class="form-control-modal-infield"></textarea>
                </div>
                <div class="form-group-modal-infield">
                    <label for="assignment_section_edit_modal" class="form-label-infield">Розділ/Тема (новий або існуючий):</label>
                    <input type="text" id="assignment_section_edit_modal" name="assignment_section_title" class="form-control-modal-infield" list="existing_sections_list_edit" placeholder="Наприклад: Тиждень 1, Модуль А">
                    <datalist id="existing_sections_list_edit"></datalist>
                    <small>Залиште порожнім, щоб додати завдання без розділу.</small>
                </div>
                <div class="form-row-modal">
                    <div class="form-group-modal half-width">
                        <label for="assignment_max_points_edit_modal">Макс. балів:</label>
                        <input type="number" id="assignment_max_points_edit_modal" name="assignment_max_points" min="0" max="100" class="form-control-modal" required>
                    </div>
                    <div class="form-group-modal half-width">
                        <label for="assignment_due_date_edit_modal">Дата та час здачі:</label>
                        <input type="datetime-local" id="assignment_due_date_edit_modal" name="assignment_due_date" class="form-control-modal" required>
                    </div>
                </div>
                <div id="existingFilesEditArea" class="form-group-modal-infield" style="margin-top: 15px; display: none;">
                    <label class="form-label-infield" style="top: -8px;">Прикріплені файли:</label>
                    <div id="existingFilesList" style="padding-top: 10px; max-height: 150px; overflow-y: auto; border: 1px solid #ced4da; border-radius: 5px; padding:10px;">
                        </div>
                    <small>Позначте файли, які потрібно видалити.</small>
                </div>
                <div class="form-group-modal-infield" style="margin-top: 15px;">
                    <label for="assignment_files_edit_modal" class="form-label-infield" style="top: -8px;">Додати нові файли (до 15MB кожен):</label>
                    <input type="file" id="assignment_files_edit_modal" name="assignment_files_edit[]" class="form-control-modal-infield" multiple style="padding-top: 12px; padding-bottom:12px; height: auto;">
                     <small>Ви можете вибрати декілька файлів. Дозволені типи: pdf, doc(x), ppt(x), xls(x), txt, zip, зображення, відео, аудіо.</small>
                </div>
                <button type="submit" class="submit-button-modal">Зберегти зміни</button>
            </form>
        </div>
    </div>

    <div id="deleteCourseModal" class="modal-overlay" style="display: none;">
        <div class="modal-content delete-course-modal-content">
            <button class="modal-close-btn" id="closeDeleteCourseModalBtn" aria-label="Закрити">&times;</button>
            <h2>Підтвердження видалення курсу</h2>
            <p class="delete-warning"><strong>Увага!</strong> Ця дія є незворотною. Всі дані курсу, включаючи завдання, оголошення, здані роботи студентів та їх зарахування, будуть остаточно видалені.</p>
            <p>Щоб підтвердити видалення, будь ласка, введіть повну назву курсу: <br>
                "<strong id="courseNameToConfirmDelete"><?php echo htmlspecialchars($course_data['course_name']); ?></strong>"
            </p>
            <form id="deleteCourseConfirmForm" style="margin-top: 15px;">
                <input type="hidden" name="course_id_to_delete" value="<?php echo htmlspecialchars($course_id_get); ?>">
                <div class="form-group-modal-infield">
                    <label for="deleteCourseNameInput" class="form-label-infield">Введіть назву курсу</label>
                    <input type="text" id="deleteCourseNameInput" name="delete_course_name_confirm" class="form-control-modal-infield" required autocomplete="off">
                </div>
                <button type="submit" id="confirmDeleteCourseBtn" class="submit-button-modal button-danger" disabled>Видалити остаточно</button>
            </form>
            <div id="deleteCourseError" class="message error" style="display:none; margin-top:15px;"></div>
        </div>
    </div>
<?php endif; ?>

</div>

<script>
    const CURRENT_COURSE_ID_FOR_JS = <?php echo $course_id_get ? json_encode((int)$course_id_get) : 'null'; ?>;
    const IS_CURRENT_USER_TEACHER_OF_THIS_COURSE = <?php echo json_encode($is_teacher_php); ?>;
    const CURRENT_USER_ID_PHP = <?php echo json_encode((int)$current_user_id_php); ?>;
    const WEB_ROOT_REL_FROM_HTML_CV_JS = '<?php echo defined('WEB_ROOT_REL_FROM_HTML_CV_PHP') ? WEB_ROOT_REL_FROM_HTML_CV_PHP : "../"; ?>';
    const ACTUAL_COURSE_NAME_PHP = <?php echo json_encode($actual_course_name_php); ?>;
    const COURSE_JOIN_CODE_FROM_DB_JS = <?php echo json_encode($course_join_code_from_db_php); ?>;
    const DEFAULT_AVATAR_REL_PATH_JS = <?php echo json_encode($default_avatar_rel_path_php); ?>;
</script>

<script src="../js/course_view.js"></script>

</body>
</html>