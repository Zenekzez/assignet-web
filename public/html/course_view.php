<?php
// File: public/html/course_view.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/../../src/connect.php';

$course_id_get = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
$current_user_id = $_SESSION['user_id'] ?? null;
$course_data = null;
$author_username = 'Невідомий';
$is_teacher = false;
$banner_color_hex = '#007bff'; 
$page_title_course = 'Курс не знайдено';
$join_code_visible_db = true; 
$course_join_code_from_db = null; 

if (!$current_user_id) {
    header("Location: login.php");
    exit();
}

if (!$course_id_get) {
    // $course_data залишиться null
} else {
    $stmt_course = $conn->prepare("SELECT course_name, author_id, color, join_code, description, join_code_visible FROM courses WHERE course_id = ?");
    if ($stmt_course) {
        $stmt_course->bind_param("i", $course_id_get);
        $stmt_course->execute();
        $result_course = $stmt_course->get_result();
        if ($course_data_row = $result_course->fetch_assoc()) {
            $course_data = $course_data_row;
            $page_title_course = htmlspecialchars($course_data['course_name']);
            $banner_color_hex = (!empty($course_data['color'])) ? htmlspecialchars($course_data['color']) : '#007bff';
            $join_code_visible_db = (bool)$course_data['join_code_visible'];
            $course_join_code_from_db = $course_data['join_code'];

            if ($current_user_id == $course_data['author_id']) {
                $is_teacher = true;
            } else {
                $stmt_check_enrollment = $conn->prepare("SELECT 1 FROM enrollments WHERE course_id = ? AND student_id = ?");
                if ($stmt_check_enrollment) {
                    $stmt_check_enrollment->bind_param("ii", $course_id_get, $current_user_id);
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

if (!defined('WEB_ROOT_REL_FROM_HTML_CV')) {
    define('WEB_ROOT_REL_FROM_HTML_CV', '../');
}
?>

<title><?php echo $page_title_course; ?> - Assignet</title>
<link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML_CV; ?>css/course_view_styles.css">
<link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML_CV; ?>css/course_people_styles.css">
<link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML_CV; ?>css/grades_tab_styles.css">

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
                if ( ($is_teacher && !empty($course_join_code_from_db)) || (!$is_teacher && $join_code_visible_db && !empty($course_join_code_from_db)) ) {
                    echo '<div class="course-join-code-container">';
                    echo '  <span class="course-join-code-label">Код курсу: </span>';
                    echo '  <strong id="courseJoinCodeTextForBanner">' . htmlspecialchars($course_join_code_from_db) . '</strong>';
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
                <?php if ($is_teacher): ?>
                    <a href="#" class="tab-link" data-tab="grades">Оцінки</a>
                    <a href="#" class="tab-link" data-tab="settings">Налаштування курсу</a>
                <?php else: ?>
                    <a href="#" class="tab-link" data-tab="my-grades">Мої оцінки</a>
                <?php endif; ?>
            </nav>

            <div id="course-tab-content" class="course-tab-content-area">
                <div id="tab-stream" class="tab-pane active">
                    <h2>Стрічка курсу</h2>
                    <?php if ($is_teacher): ?>
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
                    <div class="assignments-tab-content-wrapper"> 
                        <?php if ($is_teacher): ?>
                            <button id="showCreateAssignmentModalBtn" class="course-action-button">
                                <i class="fas fa-plus"></i> Створити завдання
                            </button>
                        <?php endif; ?>
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

                <?php if ($is_teacher): ?>
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
                                    echo !empty($course_join_code_from_db) ? htmlspecialchars($course_join_code_from_db) : 'N/A';
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
                    else echo "Курс з ID " . htmlspecialchars($_GET['course_id']) . " не знайдено.";
                    ?>
                </p>
                <a href="home.php" class="button">Повернутися на головну</a>
            </div>
        <?php endif; ?>
    </div> 
</main> 

<?php if ($is_teacher && $course_data): ?>
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
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.course-tab-navigation .tab-link');
    const tabPanes = document.querySelectorAll('.course-tab-content-area .tab-pane');
    const breadcrumbCurrentTab = document.getElementById('current-tab-breadcrumb');
    
    const createAnnouncementForm = document.getElementById('createAnnouncementForm');
    const announcementsArea = document.getElementById('announcementsArea');
    const currentCourseIdForJS = <?php echo $course_id_get ? json_encode((int)$course_id_get) : 'null'; ?>;
    let isCurrentUserTeacherOfThisCourse = <?php echo json_encode($is_teacher); ?>;

    const courseSettingsForm = document.getElementById('courseSettingsForm');
    const courseBannerTitleElement = document.querySelector('.course-banner-title');
    const breadcrumbCourseNameElement = document.querySelector('.breadcrumb-course-name');
    
    let currentJoinCodeForJS = <?php echo !empty($course_join_code_from_db) ? json_encode($course_join_code_from_db) : 'null'; ?>;
    const displayJoinCodeSettingsElement = document.getElementById('displayJoinCodeSettings'); 
    const courseJoinCodeTextBannerElement = document.getElementById('courseJoinCodeTextForBanner'); 

    const assignmentsListArea = document.getElementById('assignmentsListArea');
    const showCreateAssignmentModalBtn = document.getElementById('showCreateAssignmentModalBtn');
    const createAssignmentModal = document.getElementById('createAssignmentModal');
    const closeCreateAssignmentModalBtn = document.getElementById('closeCreateAssignmentModalBtn');
    const createAssignmentFormInternal = document.getElementById('createAssignmentFormInternal');
    const assignmentSortSelect = document.getElementById('assignmentSortSelect');

    const teacherInfoArea = document.getElementById('teacherInfoArea');
    const studentsListArea = document.getElementById('studentsListArea');
    const studentCountBadge = document.getElementById('studentCount');
    const defaultAvatarRelPath = 'assets/default_avatar.png';
    const baseAvatarUrl = '<?php echo WEB_ROOT_REL_FROM_HTML_CV; ?>';

    const myGradesArea = document.getElementById('myGradesArea');
    const teacherGradesSummaryArea = document.getElementById('teacherGradesSummaryArea');

    const editAssignmentModal = document.getElementById('editAssignmentModal');
    const closeEditAssignmentModalBtn = document.getElementById('closeEditAssignmentModalBtn');
    const editAssignmentFormInternal = document.getElementById('editAssignmentFormInternal');
    
    const assignmentIdEditInput = document.getElementById('assignment_id_edit');
    const assignmentTitleEditModal = document.getElementById('assignment_title_edit_modal');
    const assignmentDescriptionEditModal = document.getElementById('assignment_description_edit_modal');
    const assignmentSectionTitleEditModal = document.getElementById('assignment_section_edit_modal');
    const assignmentMaxPointsEditModal = document.getElementById('assignment_max_points_edit_modal');
    const assignmentDueDateEditModal = document.getElementById('assignment_due_date_edit_modal');


    let allExistingSections = [];

    const copyJoinCodeBtnBanner = document.getElementById('copyJoinCodeBtnBanner');
    const copyFeedbackElementBanner = document.getElementById('copyJoinCodeFeedbackBanner');

    if (copyJoinCodeBtnBanner && courseJoinCodeTextBannerElement && copyFeedbackElementBanner) {
        copyJoinCodeBtnBanner.addEventListener('click', function() {
            const codeToCopy = courseJoinCodeTextBannerElement.innerText;
            navigator.clipboard.writeText(codeToCopy).then(function() {
                copyFeedbackElementBanner.textContent = 'Скопійовано!';
                copyFeedbackElementBanner.style.backgroundColor = '#28a745'; 
                copyFeedbackElementBanner.classList.add('visible');
                setTimeout(() => { copyFeedbackElementBanner.classList.remove('visible'); }, 1900); 
            }).catch(function(err) {
                console.error('Помилка копіювання коду (банер): ', err);
                copyFeedbackElementBanner.textContent = 'Помилка!';
                copyFeedbackElementBanner.style.backgroundColor = '#dc3545'; 
                copyFeedbackElementBanner.classList.add('visible');
                setTimeout(() => { copyFeedbackElementBanner.classList.remove('visible'); }, 1900);
            });
        });
    }

    const regenerateJoinCodeBtnSettings = document.getElementById('regenerateJoinCodeBtnCourseSettings');
    if (regenerateJoinCodeBtnSettings && currentCourseIdForJS && isCurrentUserTeacherOfThisCourse) {
        regenerateJoinCodeBtnSettings.addEventListener('click', async function() {
            if (!confirm('Ви впевнені, що хочете згенерувати новий код приєднання? Старий код стане недійсним і буде збережено негайно.')) {
                return;
            }
            const originalButtonText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Генерація...';

            const formData = new FormData();
            formData.append('action', 'regenerate_join_code');
            formData.append('course_id', currentCourseIdForJS);

            try {
                const response = await fetch('../../src/course_actions.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success' && result.new_join_code) {
                    alert(result.message || 'Новий код приєднання згенеровано!');
                    currentJoinCodeForJS = result.new_join_code;
                    if (displayJoinCodeSettingsElement) displayJoinCodeSettingsElement.textContent = currentJoinCodeForJS;
                    if (courseJoinCodeTextBannerElement) courseJoinCodeTextBannerElement.textContent = currentJoinCodeForJS;
                } else {
                    alert(result.message || 'Не вдалося згенерувати новий код.');
                }
            } catch (error) {
                console.error('Помилка AJAX при генерації нового коду:', error);
                alert('Сталася помилка на клієнті під час генерації коду. Деталі в консолі.');
            } finally {
                this.disabled = false;
                this.innerHTML = originalButtonText;
            }
        });
    }
    
    const showDeleteCourseModalBtn = document.getElementById('showDeleteCourseModalBtn');
    const deleteCourseModal = document.getElementById('deleteCourseModal');
    const closeDeleteCourseModalBtn = document.getElementById('closeDeleteCourseModalBtn');
    const deleteCourseConfirmForm = document.getElementById('deleteCourseConfirmForm');
    const deleteCourseNameInput = document.getElementById('deleteCourseNameInput');
    const confirmDeleteCourseBtn = document.getElementById('confirmDeleteCourseBtn');
    const courseNameToConfirmDeleteSpan = document.getElementById('courseNameToConfirmDelete');
    const deleteCourseErrorDiv = document.getElementById('deleteCourseError');
    const actualCourseName = courseNameToConfirmDeleteSpan ? courseNameToConfirmDeleteSpan.innerText.trim() : "";

    if (showDeleteCourseModalBtn && deleteCourseModal) {
        showDeleteCourseModalBtn.addEventListener('click', () => {
            if(deleteCourseNameInput) deleteCourseNameInput.value = ''; 
            if(confirmDeleteCourseBtn) confirmDeleteCourseBtn.disabled = true; 
            if(deleteCourseErrorDiv) deleteCourseErrorDiv.style.display = 'none';
            deleteCourseModal.style.display = 'flex';
        });
    }
    if (closeDeleteCourseModalBtn && deleteCourseModal) {
        closeDeleteCourseModalBtn.addEventListener('click', () => { deleteCourseModal.style.display = 'none'; });
    }
    if (deleteCourseModal) { 
        deleteCourseModal.addEventListener('click', (event) => {
            if (event.target === deleteCourseModal) deleteCourseModal.style.display = 'none';
        });
    }
    if (deleteCourseNameInput && confirmDeleteCourseBtn && actualCourseName) {
        deleteCourseNameInput.addEventListener('input', function() {
            confirmDeleteCourseBtn.disabled = (this.value.trim() !== actualCourseName);
        });
    }
    if (deleteCourseConfirmForm) {
        deleteCourseConfirmForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            if (deleteCourseNameInput.value.trim() !== actualCourseName) {
                if(deleteCourseErrorDiv) {
                    deleteCourseErrorDiv.textContent = 'Введена назва не співпадає з назвою курсу.';
                    deleteCourseErrorDiv.style.display = 'block';
                }
                return;
            }
            if(confirmDeleteCourseBtn) {
                confirmDeleteCourseBtn.disabled = true;
                confirmDeleteCourseBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Видалення...';
            }
            if(deleteCourseErrorDiv) deleteCourseErrorDiv.style.display = 'none';

            const formData = new FormData();
            formData.append('action', 'delete_course');
            const courseIdToDeleteInput = this.querySelector('input[name="course_id_to_delete"]');
            if (courseIdToDeleteInput) formData.append('course_id', courseIdToDeleteInput.value);

            try {
                const response = await fetch('../../src/course_actions.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    alert(result.message || 'Курс успішно видалено!');
                    window.location.href = 'home.php'; 
                } else {
                    if(deleteCourseErrorDiv) {
                        deleteCourseErrorDiv.textContent = result.message || 'Не вдалося видалити курс.';
                        deleteCourseErrorDiv.style.display = 'block';
                    }
                    if(confirmDeleteCourseBtn) {
                        // Re-enable button only if the name was correct but server failed
                        if (deleteCourseNameInput.value.trim() === actualCourseName) {
                           confirmDeleteCourseBtn.disabled = false; 
                        }
                        confirmDeleteCourseBtn.innerHTML = 'Видалити остаточно';
                    }
                }
            } catch (error) {
                console.error('Помилка AJAX при видаленні курсу:', error);
                if(deleteCourseErrorDiv) {
                    deleteCourseErrorDiv.textContent = 'Сталася помилка на клієнті. Деталі в консолі.';
                    deleteCourseErrorDiv.style.display = 'block';
                }
                if(confirmDeleteCourseBtn) {
                    confirmDeleteCourseBtn.disabled = false;
                    confirmDeleteCourseBtn.innerHTML = 'Видалити остаточно';
                }
            }
        });
    }
    
    async function fetchAndPopulateExistingSections(courseId) {
        const datalistCreate = document.getElementById('existing_sections_list_create');
        const datalistEdit = document.getElementById('existing_sections_list_edit');

        if (!isCurrentUserTeacherOfThisCourse) return;
        if (!datalistCreate || !datalistEdit) return;

        try {
            const response = await fetch(`../../src/course_actions.php?action=get_assignments&course_id=${courseId}&sort_by=created_at_asc`);
            if (!response.ok) { console.error("Could not fetch assignments to get sections"); return; }
            const result = await response.json();
            if (result.status === 'success' && result.assignments) {
                const uniqueSections = new Set();
                result.assignments.forEach(asm => {
                    if (asm.section_title && asm.section_title.trim() !== '') {
                        uniqueSections.add(asm.section_title.trim());
                    }
                });
                allExistingSections = Array.from(uniqueSections).sort();
                datalistCreate.innerHTML = '';
                allExistingSections.forEach(section => {
                    const optionCreate = document.createElement('option'); optionCreate.value = section;
                    datalistCreate.appendChild(optionCreate);
                });
                datalistEdit.innerHTML = '';
                allExistingSections.forEach(section => {
                    const optionEdit = document.createElement('option'); optionEdit.value = section;
                    datalistEdit.appendChild(optionEdit);
                });
            }
        } catch (error) { console.error("Error fetching or populating existing sections:", error); }
    }

    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, m => map[m]);
    }

    function getStatusTextAndClass(statusCode, dueDateStr) {
        let statusText = 'Не здано'; let statusClass = 'pending';
        const dueDate = dueDateStr ? new Date(dueDateStr) : null; const now = new Date();
        switch (statusCode) {
            case 'submitted': statusText = 'Здано'; statusClass = 'submitted'; break;
            case 'graded': statusText = 'Оцінено'; statusClass = 'graded'; break;
            case 'missed': statusText = 'Пропущено'; statusClass = 'missed'; break;
            case 'pending_submission': default:
                if (dueDate && dueDate < now) { statusText = 'Пропущено'; statusClass = 'missed'; }
                else { statusText = 'Не здано'; statusClass = 'pending'; }
                break;
        }
        return { text: statusText, class: `submission-status-${statusClass}` };
    }

    async function loadMyGrades(courseId) {
        if (!courseId || !myGradesArea) return;
        myGradesArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження ваших оцінок...</p>';
        try {
            const response = await fetch(`../../src/grading_actions.php?action=get_my_grades_for_course&course_id=${courseId}`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: `HTTP помилка! Статус: ${response.status}` }));
                throw new Error(errorData.message);
            }
            const result = await response.json();
            if (result.status === 'success' && result.grades) {
                myGradesArea.innerHTML = '';
                if (result.grades.length > 0) {
                    const table = document.createElement('table'); table.classList.add('my-grades-table');
                    table.innerHTML = `<thead><tr><th>Назва завдання</th><th>Термін здачі</th><th>Статус</th><th>Оцінка</th><th>Макс. бали</th><th>Дії</th></tr></thead><tbody></tbody>`;
                    const tbody = table.querySelector('tbody');
                    result.grades.forEach(gradeItem => {
                        const row = tbody.insertRow();
                        const statusInfo = getStatusTextAndClass(gradeItem.submission_status, gradeItem.due_date);
                        row.insertCell().innerHTML = `<a href="assignment_view.php?assignment_id=${gradeItem.assignment_id}">${htmlspecialchars(gradeItem.assignment_title)}</a>`;
                        row.insertCell().textContent = gradeItem.due_date ? new Date(gradeItem.due_date).toLocaleString('uk-UA', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '–';
                        row.insertCell().innerHTML = `<span class="submission-status-badge ${statusInfo.class}">${statusInfo.text}</span>`;
                        row.insertCell().textContent = gradeItem.grade !== null ? gradeItem.grade : '–';
                        row.insertCell().textContent = gradeItem.max_points;
                        let actionsHTML = `<a href="assignment_view.php?assignment_id=${gradeItem.assignment_id}" class="button-link-small view-assignment-details-link"><i class="fas fa-eye"></i> Деталі</a>`;
                        const dueDateObj = gradeItem.due_date ? new Date(gradeItem.due_date) : null; const now = new Date();
                        if ((gradeItem.submission_status === 'pending_submission' || gradeItem.submission_status === 'submitted') && (!dueDateObj || dueDateObj >= now) ) {
                             actionsHTML += ` <a href="assignment_view.php?assignment_id=${gradeItem.assignment_id}#studentSubmissionArea" class="button-link-small submit-work-link"><i class="fas fa-upload"></i> ${gradeItem.submission_status === 'submitted' ? 'Змінити' : 'Здати'}</a>`;
                        }
                        row.insertCell().innerHTML = actionsHTML;
                    });
                    myGradesArea.appendChild(table);
                } else { myGradesArea.innerHTML = '<p>Для цього курсу ще немає завдань або оцінок.</p>'; }
            } else { myGradesArea.innerHTML = `<p>Не вдалося завантажити оцінки: ${result.message || 'Помилка сервера'}</p>`; }
        } catch (error) { console.error("Помилка завантаження оцінок студента:", error); myGradesArea.innerHTML = `<p>Сталася помилка: ${error.message}. Спробуйте оновити сторінку.</p>`; }
    }

    function getStatusTextAndClassForTeacher(statusCode, dueDateStr) {
        let statusText = '–'; let statusClass = 'status-not-submitted';
        const dueDate = dueDateStr ? new Date(dueDateStr) : null; const now = new Date();
        switch (statusCode) {
            case 'submitted': statusText = 'Здано'; statusClass = 'submission-status-submitted'; break;
            case 'graded': statusClass = 'submission-status-graded'; break; // Для тексту оцінки, тут просто клас
            case 'missed': statusText = 'Пропущено'; statusClass = 'submission-status-missed'; break;
            case 'pending_submission': default:
                if (dueDate && dueDate < now) { statusText = 'Пропущено'; statusClass = 'submission-status-missed'; }
                else { statusText = '–'; statusClass = 'status-not-submitted'; } // Не здано, термін не вийшов
                break;
        }
        return { text: statusText, class: statusClass };
    }

    async function loadTeacherGradesSummary(courseId) {
        if (!courseId || !teacherGradesSummaryArea || !isCurrentUserTeacherOfThisCourse) return;
        teacherGradesSummaryArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження журналу оцінок...</p>';
        try {
            const response = await fetch(`../../src/grading_actions.php?action=get_course_grades_summary&course_id=${courseId}`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: `HTTP помилка! Статус: ${response.status}` }));
                throw new Error(errorData.message);
            }
            const result = await response.json();
            if (result.status === 'success' && result.assignments && result.students_grades) {
                teacherGradesSummaryArea.innerHTML = '';
                if (result.students_grades.length === 0) { teacherGradesSummaryArea.innerHTML = '<p>У курсі ще немає студентів для відображення оцінок.</p>'; return; }
                if (result.assignments.length === 0) { teacherGradesSummaryArea.innerHTML = '<p>У курсі ще немає завдань для відображення оцінок.</p>'; return; }
                const table = document.createElement('table'); table.classList.add('teacher-grades-summary-table');
                const thead = table.createTHead(); const headerRow = thead.insertRow();
                const studentHeaderCell = document.createElement('th'); studentHeaderCell.textContent = 'Студент'; studentHeaderCell.classList.add('student-name-column'); headerRow.appendChild(studentHeaderCell);
                result.assignments.forEach(assignment => {
                    const th = document.createElement('th'); th.innerHTML = `${htmlspecialchars(assignment.title)}<br><small>(макс. ${assignment.max_points})</small>`; th.setAttribute('data-assignment-id', assignment.assignment_id); headerRow.appendChild(th);
                });
                const tbody = table.createTBody();
                result.students_grades.forEach(studentGradeInfo => {
                    const row = tbody.insertRow();
                    const studentNameCell = row.insertCell(); 
                    studentNameCell.classList.add('student-name-cell');
                    const studentAvatarSrc = studentGradeInfo.avatar_path ? (baseAvatarUrl + studentGradeInfo.avatar_path) : (baseAvatarUrl + defaultAvatarRelPath);
                    studentNameCell.innerHTML = `
                        <div class="student-info-grades-table">
                            <img src="${studentAvatarSrc}?t=${new Date().getTime()}" alt="Аватар" class="student-avatar-grades-table">
                            <div>
                                ${htmlspecialchars(studentGradeInfo.first_name)} ${htmlspecialchars(studentGradeInfo.last_name)}
                                <br><small>@${htmlspecialchars(studentGradeInfo.username)}</small>
                            </div>
                        </div>
                    `;

                    result.assignments.forEach(assignment => {
                        const cell = row.insertCell(); cell.classList.add('grade-cell');
                        const gradeData = studentGradeInfo.grades_by_assignment_id[assignment.assignment_id];
                        if (gradeData && gradeData.submission_id) {
                            const gradeValue = gradeData.grade !== null ? gradeData.grade : '–';
                            cell.innerHTML = `<a href="grade_submission.php?submission_id=${gradeData.submission_id}" title="Перейти до оцінювання">${gradeValue}</a>`;
                            if(gradeData.grade !== null) cell.classList.add('graded'); 
                            else if (gradeData.status === 'submitted') cell.classList.add('submitted-needs-grading');
                        } else {
                             const assignmentDetailsForStatus = result.assignments.find(a => a.assignment_id.toString() === assignment.assignment_id.toString());
                             const statusInfo = getStatusTextAndClassForTeacher(gradeData ? gradeData.status : 'pending_submission', assignmentDetailsForStatus ? assignmentDetailsForStatus.due_date : null);
                            cell.innerHTML = `<span class="${statusInfo.class}">${statusInfo.text}</span>`;
                        }
                    });
                });
                teacherGradesSummaryArea.appendChild(table);
            } else { teacherGradesSummaryArea.innerHTML = `<p>Не вдалося завантажити журнал оцінок: ${result.message || 'Помилка сервера'}</p>`;}
        } catch (error) { console.error("Помилка завантаження журналу оцінок для викладача:", error); teacherGradesSummaryArea.innerHTML = `<p>Сталася помилка: ${error.message}. Спробуйте оновити сторінку.</p>`; }
    }

    tabLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const targetTab = this.getAttribute('data-tab');
            tabLinks.forEach(l => l.classList.remove('active')); this.classList.add('active');
            tabPanes.forEach(pane => pane.classList.toggle('active', pane.id === 'tab-' + targetTab));
            if (breadcrumbCurrentTab) breadcrumbCurrentTab.textContent = this.textContent;
            
            if (targetTab === 'assignments' && currentCourseIdForJS && assignmentSortSelect) loadAssignments(currentCourseIdForJS, assignmentSortSelect.value);
            else if (targetTab === 'stream' && currentCourseIdForJS) loadAnnouncements(currentCourseIdForJS);
            else if (targetTab === 'people' && currentCourseIdForJS) loadCourseParticipants(currentCourseIdForJS);
            else if (targetTab === 'my-grades' && currentCourseIdForJS && !isCurrentUserTeacherOfThisCourse) loadMyGrades(currentCourseIdForJS);
            else if (targetTab === 'grades' && currentCourseIdForJS && isCurrentUserTeacherOfThisCourse) loadTeacherGradesSummary(currentCourseIdForJS);
        });
    });

    const activeTabOnInit = document.querySelector('.course-tab-navigation .tab-link.active');
    if (activeTabOnInit && currentCourseIdForJS) {
        const activeTabName = activeTabOnInit.dataset.tab;
        if (activeTabName === 'stream') loadAnnouncements(currentCourseIdForJS); 
        else if (activeTabName === 'assignments' && assignmentSortSelect) loadAssignments(currentCourseIdForJS, assignmentSortSelect.value);
        else if (activeTabName === 'people') loadCourseParticipants(currentCourseIdForJS);
        else if (activeTabName === 'my-grades' && !isCurrentUserTeacherOfThisCourse) loadMyGrades(currentCourseIdForJS);
        else if (activeTabName === 'grades' && isCurrentUserTeacherOfThisCourse) loadTeacherGradesSummary(currentCourseIdForJS);
    } else if (currentCourseIdForJS) {
        loadAnnouncements(currentCourseIdForJS);
    }


    if(assignmentSortSelect && currentCourseIdForJS) {
        assignmentSortSelect.addEventListener('change', function() { loadAssignments(currentCourseIdForJS, this.value); });
    }

    if (createAnnouncementForm) {
        createAnnouncementForm.addEventListener('submit', async function(event) {
            event.preventDefault(); const formData = new FormData(this); formData.append('action', 'create_announcement');
            const content = formData.get('announcement_content').trim(); if (!content) { alert('Вміст оголошення не може бути порожнім.'); return; }
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Публікація...';
            try {
                const response = await fetch('../../src/course_actions.php', { method: 'POST', body: formData });
                if (!response.ok) { const errorData = await response.json().catch(() => ({ message: 'Невідома помилка сервера' })); throw new Error(errorData.message || `HTTP помилка! Статус: ${response.status}`); }
                const result = await response.json();
                if (result.status === 'success') { this.reset(); if (currentCourseIdForJS) { loadAnnouncements(currentCourseIdForJS); } }
                else { alert(result.message || 'Помилка публікації оголошення.'); }
            } catch (error) { console.error('Помилка при публікації оголошення:', error); alert(`Сталася помилка: ${error.message}`); 
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }
    
    if(courseSettingsForm && courseBannerTitleElement && breadcrumbCourseNameElement) {
        courseSettingsForm.addEventListener('submit', async function(event) {
            event.preventDefault(); 
            const formData = new FormData(this); 
            formData.append('action', 'update_course_settings');
            if (!formData.has('join_code_visible')) { formData.append('join_code_visible', '0'); }
            
            const courseName = formData.get('course_name').trim(); 
            const color = formData.get('color').trim();
            if (!courseName) { alert('Назва курсу не може бути порожньою.'); return; }
            if (!/^#[0-9A-Fa-f]{6}$/i.test(color)) { alert('Некоректний формат кольору. Введіть HEX, наприклад, #RRGGBB.'); return; }
            
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Збереження...';

            try {
                const response = await fetch('../../src/course_actions.php', { method: 'POST', body: formData });
                if (!response.ok) { const errorData = await response.json().catch(() => ({ message: 'Невідома помилка сервера' })); throw new Error(errorData.message || `HTTP помилка! Статус: ${response.status}`);}
                const result = await response.json();
                if (result.status === 'success' && result.updated_data) {
                   alert(result.message || 'Налаштування збережено!'); 
                   const updatedData = result.updated_data;
                   courseBannerTitleElement.textContent = updatedData.course_name;
                   const bannerElement = document.querySelector('.course-banner');
                   if(bannerElement) bannerElement.style.backgroundColor = updatedData.color;
                   breadcrumbCourseNameElement.textContent = updatedData.course_name;
                   document.title = updatedData.course_name + ' - Assignet';
                   
                   const joinCodeContainerInBanner = document.querySelector('.course-banner .course-join-code-container');
                   const codeToDisplayInBanner = currentJoinCodeForJS || 'N/A';

                   if (updatedData.join_code_visible && codeToDisplayInBanner !== 'N/A') {
                       if (joinCodeContainerInBanner) {
                           const strongTag = joinCodeContainerInBanner.querySelector('#courseJoinCodeTextForBanner');
                           if(strongTag) strongTag.textContent = htmlspecialchars(codeToDisplayInBanner);
                           joinCodeContainerInBanner.style.display = 'inline-flex';
                       } else {
                           // Створення контейнера, якщо його немає (малоймовірно, якщо код існує)
                            const banner = document.querySelector('.course-banner');
                            if (banner) {
                                const newJoinCodeDiv = document.createElement('div');
                                newJoinCodeDiv.classList.add('course-join-code-container');
                                newJoinCodeDiv.style.display = 'inline-flex';
                                newJoinCodeDiv.innerHTML = `
                                    <span class="course-join-code-label">Код курсу: </span>
                                    <strong id="courseJoinCodeTextForBanner">${htmlspecialchars(codeToDisplayInBanner)}</strong>
                                    <button type="button" id="copyJoinCodeBtnBanner" class="copy-join-code-btn" title="Копіювати код">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <span id="copyJoinCodeFeedbackBanner" class="copy-feedback-message"></span>`;
                                banner.appendChild(newJoinCodeDiv);
                                // Переприв'язка обробника для нової кнопки копіювання
                                const newCopyBtn = newJoinCodeDiv.querySelector('#copyJoinCodeBtnBanner');
                                const newCodeTextEl = newJoinCodeDiv.querySelector('#courseJoinCodeTextForBanner');
                                const newFeedbackEl = newJoinCodeDiv.querySelector('#copyJoinCodeFeedbackBanner');
                                if (newCopyBtn && newCodeTextEl && newFeedbackEl) {
                                    newCopyBtn.addEventListener('click', function() {
                                        navigator.clipboard.writeText(newCodeTextEl.innerText).then(() => {
                                            newFeedbackEl.textContent = 'Скопійовано!'; newFeedbackEl.style.backgroundColor = '#28a745';
                                            newFeedbackEl.classList.add('visible');
                                            setTimeout(() => newFeedbackEl.classList.remove('visible'), 1900);
                                        }).catch(err => {
                                            console.error('Помилка копіювання: ', err); newFeedbackEl.textContent = 'Помилка!';
                                            newFeedbackEl.style.backgroundColor = '#dc3545'; newFeedbackEl.classList.add('visible');
                                            setTimeout(() => newFeedbackEl.classList.remove('visible'), 1900);
                                        });
                                    });
                                }
                            }
                       }
                   } else {
                       if (joinCodeContainerInBanner) joinCodeContainerInBanner.style.display = 'none';
                   }
                } else { 
                    alert(result.message || 'Помилка збереження налаштувань.');
                }
            } catch (error) { 
                console.error('Помилка при збереженні налаштувань курсу:', error); 
                alert(`Сталася помилка: ${error.message}`);
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }

    // ... (loadAssignments, createSectionFilter, filterAssignmentsBySection) ...
    // ... (Обробники для модальних вікон створення/редагування завдань) ...
    // ... (handleEditAssignmentClick, handleDeleteAssignmentClick) ...
    // ... (createUserListItem, loadCourseParticipants, handleRemoveStudent) ...
    // Повний JS код з попередніх відповідей, що стосується цих функцій, має бути тут.
    // Важливо переконатися, що всі посилання на DOM елементи правильні.

    // Повторно вставляю пропущені функції для повноти JS
    // (Припускаючи, що вони були в попередній версії, яку я не бачу повністю)

    if (showCreateAssignmentModalBtn && createAssignmentModal) {
        showCreateAssignmentModalBtn.addEventListener('click', () => { 
            if (createAssignmentFormInternal) createAssignmentFormInternal.reset(); 
            if (currentCourseIdForJS) { fetchAndPopulateExistingSections(currentCourseIdForJS); } 
            createAssignmentModal.style.display = 'flex'; 
        }); 
    }
    if (closeCreateAssignmentModalBtn && createAssignmentModal) { 
        closeCreateAssignmentModalBtn.addEventListener('click', () => { 
            createAssignmentModal.style.display = 'none'; 
            if(createAssignmentFormInternal) createAssignmentFormInternal.reset(); 
        }); 
    }
    if (createAssignmentModal) { 
        createAssignmentModal.addEventListener('click', (event) => { 
            if (event.target === createAssignmentModal) { 
                createAssignmentModal.style.display = 'none'; 
                if(createAssignmentFormInternal) createAssignmentFormInternal.reset(); 
            } 
        });
    }
    if (createAssignmentFormInternal) {
        createAssignmentFormInternal.addEventListener('submit', async function(event) {
            event.preventDefault(); 
            const formData = new FormData(this); 
            formData.append('action', 'create_assignment');
            const title = formData.get('assignment_title').trim(); 
            const maxPoints = formData.get('assignment_max_points'); 
            const dueDate = formData.get('assignment_due_date');
            if (!title || !maxPoints || !dueDate) { alert('Будь ласка, заповніть назву, бали та дату здачі.'); return; }
            if (parseInt(maxPoints) < 0 || parseInt(maxPoints) > 100) { alert('Кількість балів повинна бути від 0 до 100.'); return; }
            
            const submitButton = this.querySelector('button[type="submit"]'); 
            const originalButtonText = submitButton.innerHTML; 
            submitButton.disabled = true; 
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Створення...';
            try {
                const response = await fetch('../../src/course_actions.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') { 
                    alert(result.message); 
                    createAssignmentModal.style.display = 'none'; 
                    this.reset(); 
                    if (currentCourseIdForJS && assignmentSortSelect) { 
                        loadAssignments(currentCourseIdForJS, assignmentSortSelect.value); 
                    }
                } else { 
                    alert(`Помилка: ${result.message || 'Не вдалося створити завдання.'}`);
                }
            } catch (error) { 
                console.error('Помилка AJAX при створенні завдання:', error); 
                alert('Сталася помилка на клієнті при створенні завдання. Деталі в консолі.');
            } finally { 
                submitButton.disabled = false; 
                submitButton.innerHTML = originalButtonText; 
            }
        });
    }

    if (closeEditAssignmentModalBtn && editAssignmentModal) { 
        closeEditAssignmentModalBtn.addEventListener('click', () => { 
            editAssignmentModal.style.display = 'none'; 
            if(editAssignmentFormInternal) editAssignmentFormInternal.reset(); 
        }); 
    }
    if (editAssignmentModal) { 
        editAssignmentModal.addEventListener('click', (event) => { 
            if (event.target === editAssignmentModal) { 
                editAssignmentModal.style.display = 'none'; 
                if(editAssignmentFormInternal) editAssignmentFormInternal.reset(); 
            } 
        });
    }

    if (editAssignmentFormInternal) {
        editAssignmentFormInternal.addEventListener('submit', async function(event) {
            event.preventDefault(); 
            const formData = new FormData(this); 
            formData.append('action', 'update_assignment');
            const title = formData.get('assignment_title').trim(); 
            const maxPoints = formData.get('assignment_max_points'); 
            const dueDate = formData.get('assignment_due_date');
            if (!title || !maxPoints || !dueDate) { alert('Будь ласка, заповніть назву, бали та дату здачі.'); return; }
            if (parseInt(maxPoints) < 0 || parseInt(maxPoints) > 100) { alert('Кількість балів повинна бути від 0 до 100.'); return; }
            
            const submitButton = this.querySelector('button[type="submit"]'); 
            const originalButtonText = submitButton.innerHTML; 
            submitButton.disabled = true; 
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Збереження...';
            try {
                const response = await fetch('../../src/course_actions.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') { 
                    alert(result.message || 'Завдання успішно оновлено!'); 
                    editAssignmentModal.style.display = 'none'; 
                    this.reset(); 
                    if (currentCourseIdForJS && assignmentSortSelect) { 
                        loadAssignments(currentCourseIdForJS, assignmentSortSelect.value); 
                    }
                } else { 
                    alert(`Помилка оновлення завдання: ${result.message || 'Не вдалося оновити завдання.'}`);
                }
            } catch (error) { 
                console.error('AJAX error updating assignment:', error); 
                alert('Сталася помилка на клієнті при оновленні завдання.');
            } finally { 
                submitButton.disabled = false; 
                submitButton.innerHTML = originalButtonText; 
            }
        });
    }
    
    function createUserListItem(user, isTeacherContext = false, isCurrentUserTheTeacher = false) {
        const itemDiv = document.createElement('div'); itemDiv.classList.add('person-item'); itemDiv.dataset.userId = user.user_id;
        const avatarSrc = user.avatar_path ? (baseAvatarUrl + user.avatar_path) : (baseAvatarUrl + defaultAvatarRelPath);
        let removeButtonHTML = '';
        const currentUserIdFromPHP = <?php echo json_encode($current_user_id); ?>;
        if (!isTeacherContext && isCurrentUserTheTeacher && user.user_id != currentUserIdFromPHP) { 
            removeButtonHTML = `<button class="remove-student-btn" data-student-id="${user.user_id}" data-student-name="${htmlspecialchars(user.first_name) || ''} ${htmlspecialchars(user.last_name) || ''}"><i class="fas fa-user-minus"></i> Видалити</button>`;
        }
        itemDiv.innerHTML = `<img src="${avatarSrc}?t=${new Date().getTime()}" alt="Avatar" class="person-avatar"><div class="person-details"><span class="person-name">${htmlspecialchars(user.first_name) || ''} ${htmlspecialchars(user.last_name) || ''}</span><span class="person-username">@${htmlspecialchars(user.username)}</span></div>${isCurrentUserTheTeacher ? removeButtonHTML : ''}`;
        if (!isTeacherContext && isCurrentUserTheTeacher && user.user_id != currentUserIdFromPHP) { 
            const removeBtn = itemDiv.querySelector('.remove-student-btn'); 
            if(removeBtn) { removeBtn.addEventListener('click', handleRemoveStudent); }
        }
        return itemDiv;
    }

    async function loadCourseParticipants(courseId) {
        if (!courseId || !teacherInfoArea || !studentsListArea || !studentCountBadge) return;
        teacherInfoArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження...</p>'; 
        studentsListArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження списку студентів...</p>'; 
        studentCountBadge.textContent = '0';
        try {
            const response = await fetch(`../../src/course_participants_actions.php?action=get_course_participants&course_id=${courseId}`);
            if (!response.ok) { throw new Error(`HTTP помилка! Статус: ${response.status}`);}
            const result = await response.json();
            if (result.status === 'success') {
                teacherInfoArea.innerHTML = '';
                if (result.teacher) { 
                    const teacherItem = createUserListItem(result.teacher, true, result.is_current_user_teacher); 
                    teacherInfoArea.appendChild(teacherItem); 
                } else { 
                    teacherInfoArea.innerHTML = '<p>Інформація про викладача недоступна.</p>';
                }
                studentsListArea.innerHTML = '';
                if (result.students && result.students.length > 0) { 
                    result.students.forEach(student => { 
                        const studentItem = createUserListItem(student, false, result.is_current_user_teacher); 
                        studentsListArea.appendChild(studentItem); 
                    }); 
                    studentCountBadge.textContent = result.student_count || 0; 
                } else { 
                    studentsListArea.innerHTML = '<p>У цьому курсі ще немає студентів.</p>'; 
                    studentCountBadge.textContent = '0';
                }
            } else { 
                teacherInfoArea.innerHTML = `<p>Помилка: ${result.message}</p>`; 
                studentsListArea.innerHTML = `<p>Помилка: ${result.message}</p>`;
            }
        } catch (error) { 
            console.error("Помилка завантаження учасників курсу:", error); 
            teacherInfoArea.innerHTML = '<p>Не вдалося завантажити дані викладача.</p>'; 
            studentsListArea.innerHTML = '<p>Не вдалося завантажити список студентів.</p>';
        }
    }

    async function handleRemoveStudent(event) {
        const studentId = event.currentTarget.dataset.studentId; 
        const studentName = event.currentTarget.dataset.studentName;
        if (!confirm(`Ви впевнені, що хочете видалити студента ${studentName} з курсу?`)) { return; }
        const formData = new FormData(); 
        formData.append('action', 'remove_student_from_course'); 
        formData.append('course_id', currentCourseIdForJS); 
        formData.append('student_id', studentId);
        try {
            const response = await fetch('../../src/course_participants_actions.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') { 
                alert(result.message); 
                if (currentCourseIdForJS) { loadCourseParticipants(currentCourseIdForJS); }
            } else { 
                alert(`Помилка: ${result.message || 'Не вдалося видалити студента.'}`);
            }
        } catch (error) { 
            console.error('Помилка AJAX при видаленні студента:', error); 
            alert('Сталася помилка на клієнті. Деталі в консолі.');
        }
    }

});
</script>
</body>
</html>