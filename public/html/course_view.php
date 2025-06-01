<?php
// File: public/html/course_view.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// $show_add_course_button_on_home = false; // Ця змінна не потрібна тут
require_once __DIR__ . '/templates/header.php';

require_once __DIR__ . '/../../src/connect.php'; // Переконайтеся, що connect.php підключається

$course_id_get = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
$current_user_id = $_SESSION['user_id'];
$course_data = null;
$author_username = 'Невідомий';
$is_teacher = false;
$banner_color_hex = '#007bff'; // Колір за замовчуванням
$page_title_course = 'Курс не знайдено';
$join_code_visible_db = true; // За замовчуванням


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

            if ($current_user_id == $course_data['author_id']) {
                $is_teacher = true;
            }
            // Отримати ім'я автора
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
        } else {
            $course_data = null; // Курс не знайдено
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
<link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML_CV; ?>css/course_view_styles.css"> <link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML_CV; ?>css/course_people_styles.css"> <link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML_CV; ?>css/grades_tab_styles.css"> <main class="page-content-wrapper">
    <div class="course-view-main-content">
        <?php if ($course_data): ?>
            <div class="course-header-bar">
                <div class="breadcrumbs">
                    <a href="home.php">Мої курси</a> &gt;
                    <span class="breadcrumb-course-name"><?php echo htmlspecialchars($course_data['course_name']); ?></span> &gt;
                    <span id="current-tab-breadcrumb">Стрічка</span>
                </div>
            </div>

            <?php // Банер курсу ?>
            <div class="course-banner" style="background-color: <?php echo $banner_color_hex; ?>;">
                <h1 class="course-banner-title"><?php echo htmlspecialchars($course_data['course_name']); ?></h1>
                <?php
                // Оновлений блок для коду курсу з кнопкою копіювання
                if ($is_teacher || $join_code_visible_db) {
                    if (isset($course_data['join_code']) && !empty($course_data['join_code'])) {
                        echo '<div class="course-join-code-container">';
                        echo '  <span class="course-join-code-label">Код курсу: </span>';
                        echo '  <strong id="courseJoinCodeText">' . htmlspecialchars($course_data['join_code']) . '</strong>';
                        echo '  <button type="button" id="copyJoinCodeBtn" class="copy-join-code-btn" title="Копіювати код">'; // Додано type="button"
                        echo '      <i class="fas fa-copy"></i>';
                        echo '  </button>';
                        echo '  <span id="copyJoinCodeFeedback" class="copy-feedback-message"></span>';
                        echo '</div>';
                    }
                }
                ?>
            </div>

            <?php // Блок опису курсу ВИЩЕ ВГОЛОВОК ?>
            <?php if (!empty($course_data['description'])): ?>
            <div class="course-description-section"> <?php // ЗМІНЕНО КЛАС (раніше був course-description-stream-section) ?>
                <h3><i class="fas fa-info-circle"></i> Про курс</h3>
                <p><?php echo nl2br(htmlspecialchars($course_data['description'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php // Навігація по вкладках ?>
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

            <?php // Контент вкладок ?>
            <div id="course-tab-content" class="course-tab-content-area">
                <div id="tab-stream" class="tab-pane active">
                    <?php // Блок опису курсу звідси видалено, він тепер вище ?>
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
                                Показувати код приєднання студентам на сторінці "Стрічка"
                            </label>
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
                <h1>Помилка</h1>
                <p>Курс з ID <?php echo htmlspecialchars($_GET['course_id'] ?? 'невідомим'); ?> не знайдено або у вас немає до нього доступу.</p>
                <a href="home.php" class="button">Повернутися на головну</a>
            </div>
        <?php endif; ?>
    </div> 
</main> 

<?php if ($is_teacher && $course_data): ?>
    <?php // ... існуючі модальні вікна для завдань ... ?>
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
// ... (JavaScript для course_view.php) ...
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.course-tab-navigation .tab-link');
    const tabPanes = document.querySelectorAll('.course-tab-content-area .tab-pane');
    const breadcrumbCurrentTab = document.getElementById('current-tab-breadcrumb');
    const courseBannerElement = document.querySelector('.course-banner');

    const createAnnouncementForm = document.getElementById('createAnnouncementForm');
    const announcementsArea = document.getElementById('announcementsArea');
    const currentCourseIdForJS = <?php echo $course_id_get ? json_encode((int)$course_id_get) : 'null'; ?>;
    let isCurrentUserTeacherOfThisCourse = <?php echo json_encode($is_teacher); ?>;

    const courseSettingsForm = document.getElementById('courseSettingsForm');
    const courseBannerTitleElement = document.querySelector('.course-banner-title');
    const breadcrumbCourseNameElement = document.querySelector('.breadcrumb-course-name');
    const joinCodeFromDB = <?php echo (isset($course_data['join_code']) && !empty($course_data['join_code'])) ? json_encode($course_data['join_code']) : 'null'; ?>;

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

    // JavaScript для кнопки копіювання
    const copyJoinCodeBtn = document.getElementById('copyJoinCodeBtn');
    const courseJoinCodeTextElement = document.getElementById('courseJoinCodeText');
    const copyFeedbackElement = document.getElementById('copyJoinCodeFeedback');

    if (copyJoinCodeBtn && courseJoinCodeTextElement && copyFeedbackElement) {
        copyJoinCodeBtn.addEventListener('click', function() {
            const codeToCopy = courseJoinCodeTextElement.innerText;
            
            navigator.clipboard.writeText(codeToCopy).then(function() {
                copyFeedbackElement.textContent = 'Скопійовано!';
                copyFeedbackElement.style.backgroundColor = '#28a745'; 
                copyFeedbackElement.classList.add('visible');

                setTimeout(() => {
                    copyFeedbackElement.classList.remove('visible');
                }, 1900); 
            }).catch(function(err) {
                console.error('Помилка копіювання коду: ', err);
                copyFeedbackElement.textContent = 'Помилка!';
                copyFeedbackElement.style.backgroundColor = '#dc3545'; 
                copyFeedbackElement.classList.add('visible');
                setTimeout(() => {
                    copyFeedbackElement.classList.remove('visible');
                }, 1900);
            });
        });
    }
    // Кінець JavaScript для кнопки копіювання

    // JavaScript для видалення курсу
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
        closeDeleteCourseModalBtn.addEventListener('click', () => {
            deleteCourseModal.style.display = 'none';
        });
    }
    if (deleteCourseModal) { 
        deleteCourseModal.addEventListener('click', (event) => {
            if (event.target === deleteCourseModal) {
                deleteCourseModal.style.display = 'none';
            }
        });
    }

    if (deleteCourseNameInput && confirmDeleteCourseBtn) {
        deleteCourseNameInput.addEventListener('input', function() {
            if (this.value.trim() === actualCourseName) {
                confirmDeleteCourseBtn.disabled = false;
            } else {
                confirmDeleteCourseBtn.disabled = true;
            }
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
            if (courseIdToDeleteInput) {
                 formData.append('course_id', courseIdToDeleteInput.value);
            }


            try {
                const response = await fetch('../../src/course_actions.php', {
                    method: 'POST',
                    body: formData
                });
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
                        confirmDeleteCourseBtn.disabled = false;
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
    // Кінець JavaScript для видалення курсу


    // ... (решта вашого JavaScript коду: fetchAndPopulateExistingSections, htmlspecialchars, тощо) ...

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
            case 'graded': statusClass = 'submission-status-graded'; break;
            case 'missed': statusText = 'Пропущено'; statusClass = 'submission-status-missed'; break;
            case 'pending_submission': default:
                if (dueDate && dueDate < now) { statusText = 'Пропущено'; statusClass = 'submission-status-missed'; }
                else { statusText = '–'; statusClass = 'status-not-submitted'; }
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
                    const studentNameCell = row.insertCell(); studentNameCell.innerHTML = `${htmlspecialchars(studentGradeInfo.first_name)} ${htmlspecialchars(studentGradeInfo.last_name)}<br><small>@${htmlspecialchars(studentGradeInfo.username)}</small>`; studentNameCell.classList.add('student-name-cell');
                    result.assignments.forEach(assignment => {
                        const cell = row.insertCell(); cell.classList.add('grade-cell');
                        const gradeData = studentGradeInfo.grades_by_assignment_id[assignment.assignment_id];
                        if (gradeData && gradeData.submission_id) {
                            const gradeValue = gradeData.grade !== null ? gradeData.grade : '–';
                            cell.innerHTML = `<a href="grade_submission.php?submission_id=${gradeData.submission_id}" title="Перейти до оцінювання">${gradeValue}</a>`;
                            if(gradeData.grade !== null) cell.classList.add('graded'); else if (gradeData.status === 'submitted') cell.classList.add('submitted-needs-grading');
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
            
            const isStreamTab = (targetTab === 'stream');
            // Банер і опис курсу тепер відображаються незалежно від вкладок, тому ця логіка не потрібна
            // if (courseBannerElement) courseBannerElement.style.display = 'flex'; 
            // const courseDescriptionBlock = document.querySelector('.course-description-section');
            // if (courseDescriptionBlock) courseDescriptionBlock.style.display = 'block';

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
        loadAnnouncements(currentCourseIdForJS); // За замовчуванням завантажуємо стрічку, якщо нічого не активно
    }

    if(assignmentSortSelect && currentCourseIdForJS) {
        assignmentSortSelect.addEventListener('change', function() { loadAssignments(currentCourseIdForJS, this.value); });
    }

    async function loadAnnouncements(courseId) {
        if (!courseId || !announcementsArea) return;
        announcementsArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження оголошень...</p>';
        try {
            const response = await fetch(`../../src/course_actions.php?action=get_announcements&course_id=${courseId}`);
            if (!response.ok) { throw new Error(`HTTP помилка! Статус: ${response.status}`);}
            const result = await response.json(); announcementsArea.innerHTML = '';
            if (result.status === 'success' && result.announcements) {
                if (result.announcements.length > 0) {
                    result.announcements.forEach(ann => {
                        const annElement = document.createElement('div'); annElement.classList.add('announcement-item');
                        const authorAvatarSrc = ann.author_avatar_path ? baseAvatarUrl + ann.author_avatar_path : baseAvatarUrl + defaultAvatarRelPath;
                        annElement.innerHTML = `<div class="announcement-header"><div class="announcement-author-info"><img src="${authorAvatarSrc}?t=${new Date().getTime()}" alt="${ann.author_username || 'Аватар'}" class="announcement-author-avatar"><span class="announcement-author">${ann.author_username || 'Викладач'}</span></div><span class="announcement-date">${new Date(ann.created_at).toLocaleString('uk-UA', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</span></div><div class="announcement-content">${ann.content.replace(/\n/g, '<br>')}</div>`;
                        announcementsArea.appendChild(annElement);
                    });
                } else { announcementsArea.innerHTML = '<p>Оголошень поки що немає.</p>'; }
            } else { announcementsArea.innerHTML = `<p>Не вдалося завантажити оголошення: ${result.message || 'Помилка сервера'}</p>`; }
        } catch (error) { console.error("Помилка завантаження оголошень:", error); if (announcementsArea) { announcementsArea.innerHTML = '<p>Не вдалося завантажити оголошення. Спробуйте оновити сторінку.</p>'; } }
    }
    if (createAnnouncementForm) {
        createAnnouncementForm.addEventListener('submit', async function(event) {
            event.preventDefault(); const formData = new FormData(this); formData.append('action', 'create_announcement');
            const content = formData.get('announcement_content').trim(); if (!content) { alert('Вміст оголошення не може бути порожнім.'); return; }
            try {
                const response = await fetch('../../src/course_actions.php', { method: 'POST', body: formData });
                if (!response.ok) { const errorData = await response.json().catch(() => ({ message: 'Невідома помилка сервера' })); throw new Error(errorData.message || `HTTP помилка! Статус: ${response.status}`); }
                const result = await response.json();
                if (result.status === 'success') { this.reset(); if (currentCourseIdForJS) { loadAnnouncements(currentCourseIdForJS); } }
                else { alert(result.message || 'Помилка публікації оголошення.'); }
            } catch (error) { console.error('Помилка при публікації оголошення:', error); alert(`Сталася помилка: ${error.message}`); }
        });
    }
    if(courseSettingsForm && courseBannerElement && courseBannerTitleElement && breadcrumbCourseNameElement) {
        courseSettingsForm.addEventListener('submit', async function(event) {
            event.preventDefault(); const formData = new FormData(this); formData.append('action', 'update_course_settings');
            if (!formData.has('join_code_visible')) { formData.append('join_code_visible', '0'); }
            const courseName = formData.get('course_name').trim(); const color = formData.get('color').trim();
            if (!courseName) { alert('Назва курсу не може бути порожньою.'); return; }
            if (!/^#[0-9A-Fa-f]{6}$/i.test(color)) { alert('Некоректний формат кольору. Введіть HEX, наприклад, #RRGGBB.'); return; }
            try {
                const response = await fetch('../../src/course_actions.php', { method: 'POST', body: formData });
                if (!response.ok) { const errorData = await response.json().catch(() => ({ message: 'Невідома помилка сервера' })); throw new Error(errorData.message || `HTTP помилка! Статус: ${response.status}`);}
                const result = await response.json();
                if (result.status === 'success' && result.updated_data) {
                   alert(result.message || 'Налаштування збережено!'); const updatedData = result.updated_data;
                   courseBannerTitleElement.textContent = updatedData.course_name;
                   if(courseBannerElement) courseBannerElement.style.backgroundColor = updatedData.color;
                   breadcrumbCourseNameElement.textContent = updatedData.course_name;
                   document.title = updatedData.course_name + ' - Assignet';
                   
                   const joinCodeContainer = courseBannerElement.querySelector('.course-join-code-container');
                   if (updatedData.join_code_visible && joinCodeFromDB) {
                       if (joinCodeContainer) {
                           joinCodeContainer.style.display = 'inline-flex';
                           const strongTag = joinCodeContainer.querySelector('#courseJoinCodeText');
                           if(strongTag) strongTag.textContent = joinCodeFromDB;
                       } else {
                           const newJoinCodeDiv = document.createElement('div');
                           newJoinCodeDiv.classList.add('course-join-code-container');
                           newJoinCodeDiv.innerHTML = `
                               <span class="course-join-code-label">Код курсу: </span>
                               <strong id="courseJoinCodeText">${joinCodeFromDB}</strong>
                               <button type="button" id="copyJoinCodeBtn" class="copy-join-code-btn" title="Копіювати код">
                                   <i class="fas fa-copy"></i>
                               </button>
                               <span id="copyJoinCodeFeedback" class="copy-feedback-message"></span>`;
                           courseBannerElement.appendChild(newJoinCodeDiv);
                           const newCopyBtn = newJoinCodeDiv.querySelector('#copyJoinCodeBtn');
                           const newCodeTextEl = newJoinCodeDiv.querySelector('#courseJoinCodeText');
                           const newFeedbackEl = newJoinCodeDiv.querySelector('#copyJoinCodeFeedback');
                           if (newCopyBtn && newCodeTextEl && newFeedbackEl) { // ЗМІНА: перевірка на існування нових елементів
                               newCopyBtn.addEventListener('click', function() { // ЗМІНА: Обробник для новоствореної кнопки
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
                   } else {
                       if (joinCodeContainer) joinCodeContainer.style.display = 'none';
                   }
                } else { alert(result.message || 'Помилка збереження налаштувань.');}
            } catch (error) { console.error('Помилка при збереженні налаштувань курсу:', error); alert(`Сталася помилка: ${error.message}`);}
        });
    }

    async function loadAssignments(courseId, sortBy = 'due_date_asc') {
        if (!courseId || !assignmentsListArea) { console.warn("loadAssignments: courseId або assignmentsListArea не знайдено."); return; }
        assignmentsListArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження завдань...</p>';
        try {
            const response = await fetch(`../../src/course_actions.php?action=get_assignments&course_id=${courseId}&sort_by=${sortBy}`);
            if (!response.ok) { const errorText = await response.text(); console.error("Server error response text for get_assignments:", errorText); throw new Error(`HTTP помилка! Статус: ${response.status}`); }
            const result = await response.json();
            if (result.status === 'success' && result.assignments) {
                isCurrentUserTeacherOfThisCourse = result.is_teacher_of_course; assignmentsListArea.innerHTML = '';
                const uniqueSectionsForDatalist = new Set();
                result.assignments.forEach(asm => { if (asm.section_title && asm.section_title.trim() !== '') { uniqueSectionsForDatalist.add(asm.section_title.trim()); } });
                allExistingSections = Array.from(uniqueSectionsForDatalist).sort();
                if (result.assignments.length > 0) {
                    const assignmentsBySection = {};
                    result.assignments.forEach(asm => { const sectionKey = asm.section_title || 'Інші завдання'; if (!assignmentsBySection[sectionKey]) { assignmentsBySection[sectionKey] = []; } assignmentsBySection[sectionKey].push(asm); });
                    createSectionFilter(Object.keys(assignmentsBySection));
                    for (const sectionTitle in assignmentsBySection) {
                        const sectionContainer = document.createElement('div'); sectionContainer.classList.add('assignment-section-container'); sectionContainer.dataset.sectionTitle = sectionTitle;
                        const sectionHeader = document.createElement('h3'); sectionHeader.classList.add('section-title-header'); sectionHeader.textContent = sectionTitle; sectionContainer.appendChild(sectionHeader);
                        const assignmentsGrid = document.createElement('div'); assignmentsGrid.classList.add('assignments-grid-internal');
                        assignmentsBySection[sectionTitle].forEach(asm => {
                            const asmElement = document.createElement('div'); asmElement.classList.add('assignment-item-card-compact'); asmElement.dataset.assignmentId = asm.assignment_id;
                            let deadlineLabel = ''; const dueDateObj = asm.due_date ? new Date(asm.due_date) : null; const now = new Date();
                            if (asm.is_deadline_soon && !(dueDateObj && dueDateObj < now && asm.submission_status !== 'submitted' && asm.submission_status !== 'graded')) { deadlineLabel = '<span class="deadline-indicator-compact soon"><i class="fas fa-bell"></i> Скоро</span>'; }
                            else if (dueDateObj && dueDateObj < now && asm.submission_status !== 'submitted' && asm.submission_status !== 'graded' && asm.submission_status !== 'missed') { deadlineLabel = '<span class="deadline-indicator-compact past"><i class="fas fa-exclamation-circle"></i> Прострочено</span>';}
                            let submissionInfoCompact = ''; if (!isCurrentUserTeacherOfThisCourse) { const statusInfo = getStatusTextAndClass(asm.submission_status, asm.due_date); submissionInfoCompact = `<span class="submission-status-compact ${statusInfo.class}">${statusInfo.text}</span>`;}
                            let teacherActionsMenu = ''; let teacherSubmissionsButton = '';
                            if (isCurrentUserTeacherOfThisCourse) {
                                teacherActionsMenu = `<div class="assignment-actions-menu-compact"><button class="action-menu-toggle-compact" aria-label="Дії із завданням"><i class="fas fa-ellipsis-v"></i></button><div class="action-dropdown-compact"><a href="#" class="edit-assignment-link-compact" data-assignment-id="${asm.assignment_id}"><i class="fas fa-edit"></i> Редагувати</a><a href="#" class="delete-assignment-link-compact" data-assignment-id="${asm.assignment_id}"><i class="fas fa-trash"></i> Видалити</a></div></div>`;
                                teacherSubmissionsButton = `<a href="submissions_view.php?assignment_id=${asm.assignment_id}" class="button-link-compact view-submissions-link-compact"><i class="fas fa-list-check"></i> Здані роботи</a>`;
                            }
                            asmElement.innerHTML = `<div class="card-content-compact"><div class="card-title-line-compact"><h4 class="assignment-title-compact"><a href="assignment_view.php?assignment_id=${asm.assignment_id}">${asm.title}</a>${deadlineLabel ? ` ${deadlineLabel}` : ''}</h4>${teacherActionsMenu}</div><div class="card-meta-line-compact">${asm.due_date_formatted !== 'Не вказано' ? `<span>Здати до: <strong>${asm.due_date_formatted}</strong></span>` : '<span>Без терміну</span>'}<span class="meta-divider-compact">|</span><span>Бали: ${asm.max_points}</span></div>${!isCurrentUserTeacherOfThisCourse && submissionInfoCompact ? `<div class="card-status-line-compact">${submissionInfoCompact}</div>` : ''}${isCurrentUserTeacherOfThisCourse ? `<div class="card-teacher-actions-line-compact">${teacherSubmissionsButton}</div>` : ''}</div>`;
                            assignmentsGrid.appendChild(asmElement);
                            if (isCurrentUserTeacherOfThisCourse) {
                                const menuToggle = asmElement.querySelector('.action-menu-toggle-compact'); const dropdown = asmElement.querySelector('.action-dropdown-compact');
                                if(menuToggle && dropdown){ menuToggle.addEventListener('click', (e) => { e.stopPropagation(); document.querySelectorAll('.action-dropdown-compact.visible').forEach(d => { if (d !== dropdown) d.classList.remove('visible'); }); dropdown.classList.toggle('visible'); });
                                    const editLink = asmElement.querySelector('.edit-assignment-link-compact'); const deleteLink = asmElement.querySelector('.delete-assignment-link-compact');
                                    if (editLink) { editLink.addEventListener('click', (e) => { e.preventDefault(); handleEditAssignmentClick(asm.assignment_id); dropdown.classList.remove('visible'); }); }
                                    if (deleteLink) { deleteLink.addEventListener('click', (e) => { e.preventDefault(); handleDeleteAssignmentClick(asm.assignment_id); dropdown.classList.remove('visible'); }); } } } });
                        sectionContainer.appendChild(assignmentsGrid); assignmentsListArea.appendChild(sectionContainer); }
                    document.addEventListener('click', function(event) { document.querySelectorAll('.action-dropdown-compact.visible').forEach(dropdown => { if (!dropdown.parentElement.contains(event.target)) { dropdown.classList.remove('visible');}}); });
                } else { assignmentsListArea.innerHTML = '<p>Завдань для цього курсу поки що немає.</p>'; createSectionFilter([]); }
            } else { assignmentsListArea.innerHTML = `<p>Не вдалося завантажити завдання: ${result.message || 'Помилка сервера'}</p>`; console.error("Error in result from get_assignments: ", result); createSectionFilter([]);}
        } catch (error) { console.error("Помилка AJAX при завантаженні завдань:", error); if (assignmentsListArea) assignmentsListArea.innerHTML = '<p>Сталася помилка при завантаженні завдань. Спробуйте оновити сторінку.</p>'; createSectionFilter([]);}
    }

    function createSectionFilter(sections) {
        let filterContainer = document.getElementById('courseSectionsFilter');
        if (!filterContainer) { filterContainer = document.createElement('div'); filterContainer.id = 'courseSectionsFilter'; filterContainer.classList.add('sections-filter-container');
            const assignmentsTabPane = document.getElementById('tab-assignments');
            const assignmentsControlsDiv = assignmentsTabPane.querySelector('.assignments-tab-content-wrapper .assignments-controls'); 
            if (assignmentsControlsDiv) { 
                 assignmentsControlsDiv.parentNode.insertBefore(filterContainer, assignmentsControlsDiv);
            } else if (assignmentsListArea) { 
                assignmentsListArea.parentNode.insertBefore(filterContainer, assignmentsListArea);
            } else if (assignmentsTabPane) { 
                const wrapper = assignmentsTabPane.querySelector('.assignments-tab-content-wrapper');
                if (wrapper) {
                    wrapper.insertBefore(filterContainer, wrapper.firstChild);
                } else {
                    assignmentsTabPane.insertBefore(filterContainer, assignmentsTabPane.firstChild);
                }
            }
        }
        filterContainer.innerHTML = '';
        if (sections.length <= 1 && (sections.length === 0 || sections[0] === 'Інші завдання')) { filterContainer.style.display = 'none'; return; }
        filterContainer.style.display = 'flex';
        const allSectionsButton = document.createElement('button'); allSectionsButton.textContent = 'Всі завдання'; allSectionsButton.classList.add('section-filter-btn', 'active');
        allSectionsButton.addEventListener('click', () => filterAssignmentsBySection('all')); filterContainer.appendChild(allSectionsButton);
        sections.forEach(sectionTitle => { if (sectionTitle === 'Інші завдання' && sections.length === 1) return;
            const button = document.createElement('button'); button.textContent = sectionTitle; button.classList.add('section-filter-btn');
            button.addEventListener('click', () => filterAssignmentsBySection(sectionTitle)); filterContainer.appendChild(button); });
    }

    function filterAssignmentsBySection(selectedSectionTitle) {
        const sectionContainers = assignmentsListArea.querySelectorAll('.assignment-section-container');
        const filterButtons = document.querySelectorAll('#courseSectionsFilter .section-filter-btn');
        filterButtons.forEach(btn => { if (selectedSectionTitle === 'all' && btn.textContent === 'Всі завдання') { btn.classList.add('active'); } else { btn.classList.toggle('active', btn.textContent === selectedSectionTitle); }});
        sectionContainers.forEach(container => { if (selectedSectionTitle === 'all' || container.dataset.sectionTitle === selectedSectionTitle) { container.style.display = 'block'; } else { container.style.display = 'none'; } });
    }

    if (showCreateAssignmentModalBtn && createAssignmentModal) {
         showCreateAssignmentModalBtn.addEventListener('click', () => { if (createAssignmentFormInternal) createAssignmentFormInternal.reset(); if (currentCourseIdForJS) { fetchAndPopulateExistingSections(currentCourseIdForJS); } createAssignmentModal.style.display = 'flex'; }); }
    if (closeCreateAssignmentModalBtn && createAssignmentModal) { closeCreateAssignmentModalBtn.addEventListener('click', () => { createAssignmentModal.style.display = 'none'; if(createAssignmentFormInternal) createAssignmentFormInternal.reset(); }); }
    if (createAssignmentModal) { createAssignmentModal.addEventListener('click', (event) => { if (event.target === createAssignmentModal) { createAssignmentModal.style.display = 'none'; if(createAssignmentFormInternal) createAssignmentFormInternal.reset(); } });}
    if (createAssignmentFormInternal) {
        createAssignmentFormInternal.addEventListener('submit', async function(event) {
            event.preventDefault(); const formData = new FormData(this); formData.append('action', 'create_assignment');
            const title = formData.get('assignment_title').trim(); const maxPoints = formData.get('assignment_max_points'); const dueDate = formData.get('assignment_due_date');
            if (!title || !maxPoints || !dueDate) { alert('Будь ласка, заповніть назву, бали та дату здачі.'); return; }
            if (parseInt(maxPoints) < 0 || parseInt(maxPoints) > 100) { alert('Кількість балів повинна бути від 0 до 100.'); return; }
            const submitButton = this.querySelector('button[type="submit"]'); const originalButtonText = submitButton.innerHTML; submitButton.disabled = true; submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Створення...';
            try {
                const response = await fetch('../../src/course_actions.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') { alert(result.message); createAssignmentModal.style.display = 'none'; this.reset(); if (currentCourseIdForJS && assignmentSortSelect) { loadAssignments(currentCourseIdForJS, assignmentSortSelect.value); }}
                else { alert(`Помилка: ${result.message || 'Не вдалося створити завдання.'}`);}
            } catch (error) { console.error('Помилка AJAX при створенні завдання:', error); alert('Сталася помилка на клієнті при створенні завдання. Деталі в консолі.');
            } finally { submitButton.disabled = false; submitButton.innerHTML = originalButtonText; }
        });
    }

    if (closeEditAssignmentModalBtn && editAssignmentModal) { closeEditAssignmentModalBtn.addEventListener('click', () => { editAssignmentModal.style.display = 'none'; if(editAssignmentFormInternal) editAssignmentFormInternal.reset(); }); }
    if (editAssignmentModal) { editAssignmentModal.addEventListener('click', (event) => { if (event.target === editAssignmentModal) { editAssignmentModal.style.display = 'none'; if(editAssignmentFormInternal) editAssignmentFormInternal.reset(); } });}

    async function handleEditAssignmentClick(assignmentId) {
        if (!editAssignmentModal || !isCurrentUserTeacherOfThisCourse || !assignmentIdEditInput) return;
        if (editAssignmentFormInternal) editAssignmentFormInternal.reset();
        if (currentCourseIdForJS) { await fetchAndPopulateExistingSections(currentCourseIdForJS); }
        try {
            const response = await fetch(`../../src/course_actions.php?action=get_assignment_details_for_edit&assignment_id=${assignmentId}`);
            if (!response.ok) { const errorText = await response.text(); console.error("Server error for get_assignment_details_for_edit:", errorText); throw new Error('Network response was not ok for fetching assignment details.'); }
            const result = await response.json();
            if (result.status === 'success' && result.assignment) {
                const asm = result.assignment; assignmentIdEditInput.value = asm.assignment_id; assignmentTitleEditModal.value = asm.title; assignmentDescriptionEditModal.value = asm.description || '';
                const sectionInputEdit = document.getElementById('assignment_section_edit_modal'); if (sectionInputEdit) sectionInputEdit.value = asm.section_title || '';
                assignmentMaxPointsEditModal.value = asm.max_points;
                if (asm.due_date) { const dateStr = asm.due_date.replace(' ', 'T'); const date = new Date(dateStr); const timezoneOffset = date.getTimezoneOffset() * 60000; const localISOTime = (new Date(date.getTime() - timezoneOffset)).toISOString().slice(0,16); assignmentDueDateEditModal.value = localISOTime; }
                else { assignmentDueDateEditModal.value = ''; }
                editAssignmentModal.style.display = 'flex';
            } else { alert(`Помилка завантаження даних завдання: ${result.message || 'Невідома помилка'}`);}
        } catch (error) { console.error('Error fetching assignment details for edit:', error); alert('Не вдалося завантажити дані завдання для редагування.');}
    }

    if (editAssignmentFormInternal) {
        editAssignmentFormInternal.addEventListener('submit', async function(event) {
            event.preventDefault(); const formData = new FormData(this); formData.append('action', 'update_assignment');
            const title = formData.get('assignment_title').trim(); const maxPoints = formData.get('assignment_max_points'); const dueDate = formData.get('assignment_due_date');
            if (!title || !maxPoints || !dueDate) { alert('Будь ласка, заповніть назву, бали та дату здачі.'); return; }
            if (parseInt(maxPoints) < 0 || parseInt(maxPoints) > 100) { alert('Кількість балів повинна бути від 0 до 100.'); return; }
            const submitButton = this.querySelector('button[type="submit"]'); const originalButtonText = submitButton.innerHTML; submitButton.disabled = true; submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Збереження...';
            try {
                const response = await fetch('../../src/course_actions.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') { alert(result.message || 'Завдання успішно оновлено!'); editAssignmentModal.style.display = 'none'; this.reset(); if (currentCourseIdForJS && assignmentSortSelect) { loadAssignments(currentCourseIdForJS, assignmentSortSelect.value); }}
                else { alert(`Помилка оновлення завдання: ${result.message || 'Не вдалося оновити завдання.'}`);}
            } catch (error) { console.error('AJAX error updating assignment:', error); alert('Сталася помилка на клієнті при оновленні завдання.');
            } finally { submitButton.disabled = false; submitButton.innerHTML = originalButtonText; }
        });
    }

    async function handleDeleteAssignmentClick(assignmentId) {
        if (!isCurrentUserTeacherOfThisCourse) return;
        if (confirm(`Ви впевнені, що хочете видалити це завдання? Цю дію неможливо буде скасувати, і всі пов'язані з ним здані роботи також будуть видалені.`)) {
            const cardToDelete = document.querySelector(`.assignment-item-card-compact[data-assignment-id="${assignmentId}"]`);
            try {
                const formData = new FormData(); formData.append('action', 'delete_assignment'); formData.append('assignment_id', assignmentId); formData.append('course_id', currentCourseIdForJS);
                const response = await fetch('../../src/course_actions.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') { alert(result.message || 'Завдання успішно видалено!'); if (currentCourseIdForJS && assignmentSortSelect) { loadAssignments(currentCourseIdForJS, assignmentSortSelect.value); }}
                else { alert(`Помилка видалення завдання: ${result.message || 'Не вдалося видалити завдання.'}`);}
            } catch (error) { console.error('AJAX error deleting assignment:', error); alert('Сталася помилка на клієнті при видаленні завдання.');}
        }
    }

     function createUserListItem(user, isTeacherContext = false, isCurrentUserTheTeacher = false) {
        const itemDiv = document.createElement('div'); itemDiv.classList.add('person-item'); itemDiv.dataset.userId = user.user_id;
        const avatarSrc = user.avatar_path ? (baseAvatarUrl + user.avatar_path) : (baseAvatarUrl + defaultAvatarRelPath);
        let removeButtonHTML = '';
        if (!isTeacherContext && isCurrentUserTheTeacher && user.user_id != <?php echo json_encode($current_user_id); ?>) { removeButtonHTML = `<button class="remove-student-btn" data-student-id="${user.user_id}" data-student-name="${user.first_name || ''} ${user.last_name || ''}"><i class="fas fa-user-minus"></i> Видалити</button>`;}
        itemDiv.innerHTML = `<img src="${avatarSrc}?t=${new Date().getTime()}" alt="Avatar" class="person-avatar"><div class="person-details"><span class="person-name">${user.first_name || ''} ${user.last_name || ''}</span><span class="person-username">@${user.username}</span></div>${isCurrentUserTheTeacher ? removeButtonHTML : ''}`;
        if (!isTeacherContext && isCurrentUserTheTeacher && user.user_id != <?php echo json_encode($current_user_id); ?>) { const removeBtn = itemDiv.querySelector('.remove-student-btn'); if(removeBtn) { removeBtn.addEventListener('click', handleRemoveStudent); }}
        return itemDiv;
    }
    async function loadCourseParticipants(courseId) {
        if (!courseId || !teacherInfoArea || !studentsListArea || !studentCountBadge) return;
        teacherInfoArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження...</p>'; studentsListArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження списку студентів...</p>'; studentCountBadge.textContent = '0';
        try {
            const response = await fetch(`../../src/course_participants_actions.php?action=get_course_participants&course_id=${courseId}`);
            if (!response.ok) { throw new Error(`HTTP помилка! Статус: ${response.status}`);}
            const result = await response.json();
            if (result.status === 'success') {
                teacherInfoArea.innerHTML = '';
                if (result.teacher) { const teacherItem = createUserListItem(result.teacher, true, result.is_current_user_teacher); teacherInfoArea.appendChild(teacherItem); }
                else { teacherInfoArea.innerHTML = '<p>Інформація про викладача недоступна.</p>';}
                studentsListArea.innerHTML = '';
                if (result.students && result.students.length > 0) { result.students.forEach(student => { const studentItem = createUserListItem(student, false, result.is_current_user_teacher); studentsListArea.appendChild(studentItem); }); studentCountBadge.textContent = result.student_count || 0; }
                else { studentsListArea.innerHTML = '<p>У цьому курсі ще немає студентів.</p>'; studentCountBadge.textContent = '0';}
            } else { teacherInfoArea.innerHTML = `<p>Помилка: ${result.message}</p>`; studentsListArea.innerHTML = `<p>Помилка: ${result.message}</p>`;}
        } catch (error) { console.error("Помилка завантаження учасників курсу:", error); teacherInfoArea.innerHTML = '<p>Не вдалося завантажити дані викладача.</p>'; studentsListArea.innerHTML = '<p>Не вдалося завантажити список студентів.</p>';}
    }
    async function handleRemoveStudent(event) {
        const studentId = event.currentTarget.dataset.studentId; const studentName = event.currentTarget.dataset.studentName;
        if (!confirm(`Ви впевнені, що хочете видалити студента ${studentName} з курсу?`)) { return; }
        const formData = new FormData(); formData.append('action', 'remove_student_from_course'); formData.append('course_id', currentCourseIdForJS); formData.append('student_id', studentId);
        try {
            const response = await fetch('../../src/course_participants_actions.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') { alert(result.message); if (currentCourseIdForJS) { loadCourseParticipants(currentCourseIdForJS); }}
            else { alert(`Помилка: ${result.message || 'Не вдалося видалити студента.'}`);}
        } catch (error) { console.error('Помилка AJAX при видаленні студента:', error); alert('Сталася помилка на клієнті. Деталі в консолі.');}
    }
});
</script>
</body>
</html>