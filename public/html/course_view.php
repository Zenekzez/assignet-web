<?php
// File: public/html/course_view.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../src/connect.php';
require_once __DIR__ . '/templates/header.php'; //

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); //
    exit();
}

$course_id_get = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
$current_user_id = $_SESSION['user_id'];
$course_data = null;
$author_username = 'Невідомий';
$is_teacher = false;

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
            if ($current_user_id == $course_data['author_id']) {
                $is_teacher = true;
            }
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
            $course_data = null;
        }
        $stmt_course->close();
    } else {
        error_log("Failed to prepare statement for course data: " . $conn->error);
        $course_data = null;
    }
}

$banner_color_hex = (!empty($course_data['color'])) ? htmlspecialchars($course_data['color']) : '#007bff';
$page_title = $course_data ? htmlspecialchars($course_data['course_name']) : 'Курс не знайдено';
$join_code_visible_db = $course_data['join_code_visible'] ?? true;

if (!defined('WEB_ROOT_REL_FROM_HTML_CV')) {
    define('WEB_ROOT_REL_FROM_HTML_CV', '../'); // Для CSS та JS шляхів з course_view.php
}


?>

<title><?php echo $page_title; ?> - Assignet</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML_CV; ?>css/course_view_styles.css">
<link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML_CV; ?>css/course_people_styles.css">
<link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML_CV; ?>css/grades_tab_styles.css">


<div class="course-view-main-content">
    <?php if ($course_data): ?>
        <div class="course-header-bar">
            <div class="breadcrumbs">
                <a href="home.php">Мої курси</a> &gt;
                <span class="breadcrumb-course-name"><?php echo htmlspecialchars($course_data['course_name']); ?></span> &gt;
                <span id="current-tab-breadcrumb">Стрічка</span>
            </div>
        </div>

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

        <div class="course-banner" style="background-color: <?php echo $banner_color_hex; ?>;">
            <h1 class="course-banner-title"><?php echo htmlspecialchars($course_data['course_name']); ?></h1>
            <?php
            if ($is_teacher || $join_code_visible_db) {
                if (isset($course_data['join_code'])) {
                     echo '<p class="course-join-code">Код курсу: <strong>' . htmlspecialchars($course_data['join_code']) . '</strong></p>';
                }
            }
            ?>
        </div>

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

<?php if ($is_teacher): ?>
<div id="createAssignmentModal" class="modal-overlay" style="display: none;">
    <div class="modal-content create-assignment-modal-content">
        <button class="modal-close-btn" id="closeCreateAssignmentModalBtn" aria-label="Закрити">&times;</button>
        <h2>Створити нове завдання</h2>
        <form id="createAssignmentFormInternal" class="course-form">
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id_get); ?>">
            <div class="form-group-modal">
                <label for="assignment_title_modal">Назва завдання:</label>
                <input type="text" id="assignment_title_modal" name="assignment_title" class="form-control-modal" required>
            </div>
            <div class="form-group-modal">
                <label for="assignment_description_modal">Опис:</label>
                <textarea id="assignment_description_modal" name="assignment_description" rows="5" class="form-control-modal"></textarea>
            </div>
            <div class="form-group-modal">
                <label for="assignment_section_title_modal">Розділ/Тема (необов'язково):</label>
                <input type="text" id="assignment_section_title_modal" name="assignment_section_title" class="form-control-modal" placeholder="Наприклад: Тиждень 1, Модуль А">
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
<?php endif; ?>

<script>
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
    const courseJoinCodeElement = document.querySelector('.course-banner .course-join-code');
    const joinCodeFromDB = <?php echo isset($course_data['join_code']) ? json_encode($course_data['join_code']) : 'null'; ?>;

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


    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, m => map[m]);
    }

    function getStatusTextAndClass(statusCode, dueDateStr) {
        let statusText = 'Не здано';
        let statusClass = 'pending';
        const dueDate = dueDateStr ? new Date(dueDateStr) : null;
        const now = new Date();

        switch (statusCode) {
            case 'submitted': statusText = 'Здано'; statusClass = 'submitted'; break;
            case 'graded': statusText = 'Оцінено'; statusClass = 'graded'; break;
            case 'missed': statusText = 'Пропущено'; statusClass = 'missed'; break;
            case 'pending_submission':
            default:
                if (dueDate && dueDate < now) {
                    statusText = 'Пропущено'; statusClass = 'missed';
                } else {
                    statusText = 'Не здано'; statusClass = 'pending';
                }
                break;
        }
        return { text: statusText, class: `submission-status-${statusClass}` };
    }

    async function loadMyGrades(courseId) {
        if (!courseId || !myGradesArea) return;
        myGradesArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження ваших оцінок...</p>';
        try {
            const response = await fetch(`../../src/grades_actions.php?action=get_my_grades_for_course&course_id=${courseId}`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: `HTTP помилка! Статус: ${response.status}` }));
                throw new Error(errorData.message);
            }
            const result = await response.json();
            if (result.status === 'success' && result.grades) {
                myGradesArea.innerHTML = '';
                if (result.grades.length > 0) {
                    const table = document.createElement('table');
                    table.classList.add('my-grades-table');
                    table.innerHTML = `
                        <thead>
                            <tr>
                                <th>Назва завдання</th>
                                <th>Термін здачі</th>
                                <th>Статус</th>
                                <th>Оцінка</th>
                                <th>Макс. бали</th>
                                <th>Дії</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    `;
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
                        const dueDateObj = gradeItem.due_date ? new Date(gradeItem.due_date) : null;
                        const now = new Date();
                        if ((gradeItem.submission_status === 'pending_submission' || gradeItem.submission_status === 'submitted') && (!dueDateObj || dueDateObj >= now) ) {
                             actionsHTML += ` <a href="assignment_view.php?assignment_id=${gradeItem.assignment_id}#studentSubmissionArea"
                                                class="button-link-small submit-work-link">
                                                <i class="fas fa-upload"></i> ${gradeItem.submission_status === 'submitted' ? 'Змінити' : 'Здати'}
                                              </a>`;
                        }
                        row.insertCell().innerHTML = actionsHTML;
                    });
                    myGradesArea.appendChild(table);
                } else {
                    myGradesArea.innerHTML = '<p>Для цього курсу ще немає завдань або оцінок.</p>';
                }
            } else {
                myGradesArea.innerHTML = `<p>Не вдалося завантажити оцінки: ${result.message || 'Помилка сервера'}</p>`;
            }
        } catch (error) {
            console.error("Помилка завантаження оцінок студента:", error);
            myGradesArea.innerHTML = `<p>Сталася помилка: ${error.message}. Спробуйте оновити сторінку.</p>`;
        }
    }

    function getStatusTextAndClassForTeacher(statusCode, dueDateStr) {
        let statusText = '–';
        let statusClass = 'status-not-submitted';
        const dueDate = dueDateStr ? new Date(dueDateStr) : null;
        const now = new Date();
        switch (statusCode) {
            case 'submitted': statusText = 'Здано'; statusClass = 'submission-status-submitted'; break;
            case 'graded': statusClass = 'submission-status-graded'; break;
            case 'missed': statusText = 'Пропущено'; statusClass = 'submission-status-missed'; break;
            case 'pending_submission':
            default:
                if (dueDate && dueDate < now) {
                    statusText = 'Пропущено'; statusClass = 'submission-status-missed';
                } else {
                    statusText = '–'; statusClass = 'status-not-submitted';
                }
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
                if (result.students_grades.length === 0) {
                    teacherGradesSummaryArea.innerHTML = '<p>У курсі ще немає студентів для відображення оцінок.</p>';
                    return;
                }
                if (result.assignments.length === 0) {
                    teacherGradesSummaryArea.innerHTML = '<p>У курсі ще немає завдань для відображення оцінок.</p>';
                    return;
                }
                const table = document.createElement('table');
                table.classList.add('teacher-grades-summary-table');
                const thead = table.createTHead();
                const headerRow = thead.insertRow();
                const studentHeaderCell = document.createElement('th');
                studentHeaderCell.textContent = 'Студент';
                studentHeaderCell.classList.add('student-name-column');
                headerRow.appendChild(studentHeaderCell);
                result.assignments.forEach(assignment => {
                    const th = document.createElement('th');
                    th.innerHTML = `${htmlspecialchars(assignment.title)}<br><small>(макс. ${assignment.max_points})</small>`;
                    th.setAttribute('data-assignment-id', assignment.assignment_id);
                    headerRow.appendChild(th);
                });
                const tbody = table.createTBody();
                result.students_grades.forEach(studentGradeInfo => {
                    const row = tbody.insertRow();
                    const studentNameCell = row.insertCell();
                    studentNameCell.innerHTML = `${htmlspecialchars(studentGradeInfo.first_name)} ${htmlspecialchars(studentGradeInfo.last_name)}<br><small>@${htmlspecialchars(studentGradeInfo.username)}</small>`;
                    studentNameCell.classList.add('student-name-cell');
                    result.assignments.forEach(assignment => {
                        const cell = row.insertCell();
                        cell.classList.add('grade-cell');
                        const gradeData = studentGradeInfo.grades_by_assignment_id[assignment.assignment_id];
                        if (gradeData && gradeData.submission_id) {
                            const gradeValue = gradeData.grade !== null ? gradeData.grade : '–';
                            cell.innerHTML = `<a href="grade_submission.php?submission_id=${gradeData.submission_id}"
                                                 title="Перейти до оцінювання">${gradeValue}</a>`;
                            if(gradeData.grade !== null) cell.classList.add('graded');
                            else if (gradeData.status === 'submitted') cell.classList.add('submitted-needs-grading');
                        } else {
                            const assignmentDetails = result.assignments.find(a => a.assignment_id === assignment.assignment_id);
                            const statusInfo = getStatusTextAndClassForTeacher(null, assignmentDetails ? assignmentDetails.due_date : null);
                             cell.innerHTML = `<span class="${statusInfo.class}">${statusInfo.text}</span>`;
                        }
                    });
                });
                teacherGradesSummaryArea.appendChild(table);
            } else {
                teacherGradesSummaryArea.innerHTML = `<p>Не вдалося завантажити журнал оцінок: ${result.message || 'Помилка сервера'}</p>`;
            }
        } catch (error) {
            console.error("Помилка завантаження журналу оцінок для викладача:", error);
            teacherGradesSummaryArea.innerHTML = `<p>Сталася помилка: ${error.message}. Спробуйте оновити сторінку.</p>`;
        }
    }

    // Tab switching logic
    tabLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const targetTab = this.getAttribute('data-tab');
            tabLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            tabPanes.forEach(pane => {
                pane.classList.toggle('active', pane.id === 'tab-' + targetTab);
            });
            if (breadcrumbCurrentTab) breadcrumbCurrentTab.textContent = this.textContent;
            if (courseBannerElement) courseBannerElement.style.display = (targetTab === 'stream') ? 'flex' : 'none';

            if (targetTab === 'assignments' && currentCourseIdForJS) loadAssignments(currentCourseIdForJS, assignmentSortSelect.value);
            else if (targetTab === 'stream' && currentCourseIdForJS) loadAnnouncements(currentCourseIdForJS);
            else if (targetTab === 'people' && currentCourseIdForJS) loadCourseParticipants(currentCourseIdForJS);
            else if (targetTab === 'my-grades' && currentCourseIdForJS && !isCurrentUserTeacherOfThisCourse) loadMyGrades(currentCourseIdForJS);
            else if (targetTab === 'grades' && currentCourseIdForJS && isCurrentUserTeacherOfThisCourse) loadTeacherGradesSummary(currentCourseIdForJS);
        });
    });

    // Initial load for active tab
    const activeTabOnInit = document.querySelector('.course-tab-navigation .tab-link.active');
    if (activeTabOnInit && currentCourseIdForJS) {
        const activeTabName = activeTabOnInit.dataset.tab;
        if (activeTabName === 'stream') loadAnnouncements(currentCourseIdForJS);
        else if (activeTabName === 'assignments') loadAssignments(currentCourseIdForJS, assignmentSortSelect.value);
        else if (activeTabName === 'people') loadCourseParticipants(currentCourseIdForJS);
        else if (activeTabName === 'my-grades' && !isCurrentUserTeacherOfThisCourse) loadMyGrades(currentCourseIdForJS);
        else if (activeTabName === 'grades' && isCurrentUserTeacherOfThisCourse) loadTeacherGradesSummary(currentCourseIdForJS);
    }

    // Functions for other tabs (loadAnnouncements, loadAssignments, loadCourseParticipants, etc.)
    // Ensure they are defined as in previous responses or integrated here if not already.
    // For brevity, I'm assuming they are present from previous steps.
    // Example:
    async function loadAnnouncements(courseId) { /* ... implementation ... */ 
        if (!courseId || !announcementsArea) return;
        announcementsArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження оголошень...</p>';
        try {
            const response = await fetch(`../../src/course_actions.php?action=get_announcements&course_id=${courseId}`);
            if (!response.ok) {
                throw new Error(`HTTP помилка! Статус: ${response.status}`);
            }
            const result = await response.json();
            announcementsArea.innerHTML = '';
            if (result.status === 'success' && result.announcements) {
                if (result.announcements.length > 0) {
                    result.announcements.forEach(ann => {
                        const annElement = document.createElement('div');
                        annElement.classList.add('announcement-item');
                        const authorAvatarSrc = ann.author_avatar_path ? baseAvatarUrl + ann.author_avatar_path : baseAvatarUrl + defaultAvatarRelPath;
                        annElement.innerHTML = `
                            <div class="announcement-header">
                                <div class="announcement-author-info">
                                    <img src="${authorAvatarSrc}?t=${new Date().getTime()}" alt="${ann.author_username || 'Аватар'}" class="announcement-author-avatar">
                                    <span class="announcement-author">${ann.author_username || 'Викладач'}</span>
                                </div>
                                <span class="announcement-date">${new Date(ann.created_at).toLocaleString('uk-UA', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                            </div>
                            <div class="announcement-content">
                                ${ann.content.replace(/\n/g, '<br>')}
                            </div>
                        `;
                        announcementsArea.appendChild(annElement);
                    });
                } else {
                    announcementsArea.innerHTML = '<p>Оголошень поки що немає.</p>';
                }
            } else {
                announcementsArea.innerHTML = `<p>Не вдалося завантажити оголошення: ${result.message || 'Помилка сервера'}</p>`;
            }
        } catch (error) {
            console.error("Помилка завантаження оголошень:", error);
            if (announcementsArea) {
                announcementsArea.innerHTML = '<p>Не вдалося завантажити оголошення. Спробуйте оновити сторінку.</p>';
            }
        }
    }
    if (createAnnouncementForm) {
        createAnnouncementForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'create_announcement');
            const content = formData.get('announcement_content').trim();
            if (!content) {
                alert('Вміст оголошення не може бути порожнім.');
                return;
            }
            try {
                const response = await fetch('../../src/course_actions.php', {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: 'Невідома помилка сервера' }));
                    throw new Error(errorData.message || `HTTP помилка! Статус: ${response.status}`);
                }
                const result = await response.json();
                if (result.status === 'success') {
                    this.reset();
                    if (currentCourseIdForJS) {
                       loadAnnouncements(currentCourseIdForJS);
                    }
                } else {
                    alert(result.message || 'Помилка публікації оголошення.');
                }
            } catch (error) {
                console.error('Помилка при публікації оголошення:', error);
                alert(`Сталася помилка: ${error.message}`);
            }
        });
    }
    if(courseSettingsForm && courseBannerElement && courseBannerTitleElement && breadcrumbCourseNameElement) {
        courseSettingsForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_course_settings');
            if (!formData.has('join_code_visible')) {
                formData.append('join_code_visible', '0');
            }
            const courseName = formData.get('course_name').trim();
            const color = formData.get('color').trim();
            if (!courseName) {
                alert('Назва курсу не може бути порожньою.'); return;
            }
            if (!/^#[0-9A-Fa-f]{6}$/i.test(color)) {
                alert('Некоректний формат кольору. Введіть HEX, наприклад, #RRGGBB.'); return;
            }
            try {
                const response = await fetch('../../src/course_actions.php', {
                   method: 'POST',
                   body: formData
                });
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: 'Невідома помилка сервера' }));
                    throw new Error(errorData.message || `HTTP помилка! Статус: ${response.status}`);
                }
                const result = await response.json();
                if (result.status === 'success' && result.updated_data) {
                   alert(result.message || 'Налаштування збережено!');
                   const updatedData = result.updated_data;
                   courseBannerTitleElement.textContent = updatedData.course_name;
                   if(courseBannerElement) courseBannerElement.style.backgroundColor = updatedData.color;
                   breadcrumbCourseNameElement.textContent = updatedData.course_name;
                   document.title = updatedData.course_name + ' - Assignet';
                   if (courseJoinCodeElement) {
                       if (updatedData.join_code_visible && joinCodeFromDB) {
                           courseJoinCodeElement.innerHTML = `Код курсу: <strong>${joinCodeFromDB}</strong>`;
                           courseJoinCodeElement.style.display = 'inline-block';
                       } else {
                           courseJoinCodeElement.style.display = 'none';
                       }
                   } else if (updatedData.join_code_visible && joinCodeFromDB && courseBannerElement) {
                        const newJoinCodeP = document.createElement('p');
                        newJoinCodeP.classList.add('course-join-code');
                        newJoinCodeP.innerHTML = `Код курсу: <strong>${joinCodeFromDB}</strong>`;
                        if(courseBannerTitleElement.nextSibling) {
                            courseBannerElement.insertBefore(newJoinCodeP, courseBannerTitleElement.nextSibling);
                        } else {
                            courseBannerElement.appendChild(newJoinCodeP);
                        }
                   }
                } else {
                   alert(result.message || 'Помилка збереження налаштувань.');
                }
            } catch (error) {
                console.error('Помилка при збереженні налаштувань курсу:', error);
                alert(`Сталася помилка: ${error.message}`);
            }
        });
    }
    async function loadAssignments(courseId, sortBy = 'due_date_asc') {
         if (!courseId || !assignmentsListArea) {
            console.warn("loadAssignments: courseId або assignmentsListArea не знайдено.");
            return;
        }
        assignmentsListArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження завдань...</p>';
        try {
            const response = await fetch(`../../src/course_actions.php?action=get_assignments&course_id=${courseId}&sort_by=${sortBy}`);
            if (!response.ok) {
                const errorText = await response.text();
                console.error("Server error response text for get_assignments:", errorText);
                throw new Error(`HTTP помилка! Статус: ${response.status}`);
            }
            const result = await response.json();
            if (result.status === 'success' && result.assignments) {
                isCurrentUserTeacherOfThisCourse = result.is_teacher_of_course;
                assignmentsListArea.innerHTML = '';
                if (result.assignments.length > 0) {
                    result.assignments.forEach(asm => {
                        const asmElement = document.createElement('div');
                        asmElement.classList.add('assignment-item-card');
                        let deadlineLabel = '';
                        const dueDateObj = asm.due_date ? new Date(asm.due_date) : null;
                        const now = new Date();
                        if (asm.is_deadline_soon && !(dueDateObj && dueDateObj < now && asm.submission_status !== 'submitted' && asm.submission_status !== 'graded')) {
                             asmElement.classList.add('deadline-approaching');
                             deadlineLabel = '<span class="deadline-soon-label"><i class="fas fa-bell"></i> Термін здачі скоро!</span>';
                        }
                        if (dueDateObj && dueDateObj < now && asm.submission_status !== 'submitted' && asm.submission_status !== 'graded' && asm.submission_status !== 'missed') {
                             deadlineLabel = '<span class="deadline-past-label"><i class="fas fa-exclamation-circle"></i> Термін здачі минув</span>';
                        }
                        let submissionInfo = '';
                        if (!isCurrentUserTeacherOfThisCourse) {
                           if (asm.submission_status === 'submitted') {
                               submissionInfo = '<span class="submission-status submitted"><i class="fas fa-check-circle"></i> Здано</span>';
                           } else if (asm.submission_status === 'graded') {
                               submissionInfo = `<span class="submission-status graded"><i class="fas fa-award"></i> Оцінено</span>`;
                           } else if (dueDateObj && dueDateObj < now) {
                               submissionInfo = '<span class="submission-status missed"><i class="fas fa-times-circle"></i> Пропущено</span>';
                           } else {
                               submissionInfo = '<span class="submission-status pending"><i class="fas fa-hourglass-half"></i> Не здано</span>';
                           }
                        }
                        let shortDescription = asm.description || '';
                        if (shortDescription.length > 100) {
                            shortDescription = shortDescription.substring(0, 100) + '...';
                        }
                        asmElement.innerHTML = `
                            <div class="assignment-card-header">
                                <h3 class="assignment-title"><a href="assignment_view.php?assignment_id=${asm.assignment_id}">${asm.title}</a></h3>
                                ${deadlineLabel}
                            </div>
                            <div class="assignment-card-body">
                                ${asm.section_title ? `<p class="assignment-section"><i class="fas fa-folder-open"></i> Розділ: <strong>${asm.section_title}</strong></p>` : ''}
                                <p class="assignment-dates">
                                    <i class="fas fa-calendar-plus"></i> Опубліковано: ${asm.created_at_formatted}
                                    ${asm.updated_at_formatted ? `( <i class="fas fa-edit"></i> Змінено: ${asm.updated_at_formatted})` : ''}
                                </p>
                                <p class="assignment-due"><i class="fas fa-calendar-times"></i> Здати до: <strong>${asm.due_date_formatted}</strong></p>
                                <p class="assignment-points"><i class="fas fa-star"></i> Макс. балів: ${asm.max_points}</p>
                                ${shortDescription ? `<p class="assignment-description-short">${shortDescription}</p>` : ''}
                            </div>
                            <div class="assignment-card-footer">
                                ${isCurrentUserTeacherOfThisCourse ? `<a href="submissions_view.php?assignment_id=${asm.assignment_id}" class="button-link view-submissions-link"><i class="fas fa-list-check"></i> Здані роботи</a>` : submissionInfo}
                                <a href="assignment_view.php?assignment_id=${asm.assignment_id}" class="button-link view-assignment-link"><i class="fas fa-eye"></i> Детальніше</a>
                            </div>
                        `;
                        assignmentsListArea.appendChild(asmElement);
                    });
                } else {
                    assignmentsListArea.innerHTML = '<p>Завдань для цього курсу поки що немає.</p>';
                }
            } else {
                assignmentsListArea.innerHTML = `<p>Не вдалося завантажити завдання: ${result.message || 'Помилка сервера'}</p>`;
                console.error("Error in result from get_assignments: ", result);
            }
        } catch (error) {
            console.error("Помилка AJAX при завантаженні завдань:", error);
            if (assignmentsListArea) assignmentsListArea.innerHTML = '<p>Сталася помилка при завантаженні завдань. Спробуйте оновити сторінку.</p>';
        }
    }
    if (showCreateAssignmentModalBtn && createAssignmentModal) {
         showCreateAssignmentModalBtn.addEventListener('click', () => {
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
            if (!title || !maxPoints || !dueDate) {
                alert('Будь ласка, заповніть назву, бали та дату здачі.');
                return;
            }
            if (parseInt(maxPoints) < 0 || parseInt(maxPoints) > 100) { // Added check for > 100
                alert('Кількість балів повинна бути від 0 до 100.'); // Updated message
                return;
            }
            try {
                const response = await fetch('../../src/course_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    alert(result.message);
                    createAssignmentModal.style.display = 'none';
                    this.reset();
                    if (currentCourseIdForJS) {
                        loadAssignments(currentCourseIdForJS, assignmentSortSelect.value);
                    }
                } else {
                    alert(`Помилка: ${result.message || 'Не вдалося створити завдання.'}`);
                }
            } catch (error) {
                console.error('Помилка AJAX при створенні завдання:', error);
                alert('Сталася помилка на клієнті при створенні завдання. Деталі в консолі.');
            }
        });
    }
     function createUserListItem(user, isTeacherContext = false, isCurrentUserTheTeacher = false) {
        const itemDiv = document.createElement('div');
        itemDiv.classList.add('person-item');
        itemDiv.dataset.userId = user.user_id;
        const avatarSrc = user.avatar_path ? (baseAvatarUrl + user.avatar_path) : (baseAvatarUrl + defaultAvatarRelPath);
        let removeButtonHTML = '';
        if (!isTeacherContext && isCurrentUserTheTeacher && user.user_id != <?php echo json_encode($current_user_id); ?>) {
            removeButtonHTML = `<button class="remove-student-btn" data-student-id="${user.user_id}" data-student-name="${user.first_name || ''} ${user.last_name || ''}"><i class="fas fa-user-minus"></i> Видалити</button>`;
        }
        itemDiv.innerHTML = `
            <img src="${avatarSrc}?t=${new Date().getTime()}" alt="Avatar" class="person-avatar">
            <div class="person-details">
                <span class="person-name">${user.first_name || ''} ${user.last_name || ''}</span>
                <span class="person-username">@${user.username}</span>
            </div>
            ${isCurrentUserTheTeacher ? removeButtonHTML : ''}
        `;
        if (!isTeacherContext && isCurrentUserTheTeacher && user.user_id != <?php echo json_encode($current_user_id); ?>) {
            const removeBtn = itemDiv.querySelector('.remove-student-btn');
            if(removeBtn) {
                removeBtn.addEventListener('click', handleRemoveStudent);
            }
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
            if (!response.ok) {
                throw new Error(`HTTP помилка! Статус: ${response.status}`);
            }
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
        if (!confirm(`Ви впевнені, що хочете видалити студента ${studentName} з курсу?`)) {
            return;
        }
        const formData = new FormData();
        formData.append('action', 'remove_student_from_course');
        formData.append('course_id', currentCourseIdForJS);
        formData.append('student_id', studentId);
        try {
            const response = await fetch('../../src/course_participants_actions.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                alert(result.message);
                if (currentCourseIdForJS) {
                    loadCourseParticipants(currentCourseIdForJS);
                }
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