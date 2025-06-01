<?php
// File: public/html/grade_submission.php
// ... (початок файлу без змін) ...
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../src/connect.php';
require_once __DIR__ . '/templates/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$submission_id_get = filter_input(INPUT_GET, 'submission_id', FILTER_VALIDATE_INT);
$page_title = "Оцінювання роботи";

define('WEB_ROOT_REL_FROM_HTML', '../');
$default_avatar_web_path = WEB_ROOT_REL_FROM_HTML . 'assets/default_avatar.png';

?>
<title><?php echo htmlspecialchars($page_title); ?> - Assignet</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML; ?>css/course_view_styles.css">
<link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML; ?>css/grading_styles.css">

<main class="page-content-wrapper"> 
    <div class="course-view-main-content" id="gradingPageContainer">
        <div class="course-header-bar" id="gradingBreadcrumbs" style="display:none;">
            <div class="breadcrumbs">
                <a href="home.php">Мої курси</a> &gt;
                <a id="breadcrumbCourseName" href="#">Курс</a> &gt;
                <a id="breadcrumbAssignmentName" href="#">Завдання</a> &gt;
                <a id="breadcrumbSubmissionsList" href="#">Здані роботи</a> &gt;
                <span>Оцінювання: <span id="breadcrumbStudentName">Студент</span></span>
            </div>
        </div>

        <div id="submissionDetailArea" class="submission-grading-container">
            <p class="loading-text"><i class="fas fa-spinner fa-spin"></i> Завантаження даних роботи...</p>
            <?php if (!$submission_id_get): ?>
                <p class="error-text">Помилка: ID зданої роботи не було передано.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

</div> <script>
const CURRENT_SUBMISSION_ID = <?php echo $submission_id_get ? json_encode((int)$submission_id_get) : 'null'; ?>;
const ASSET_BASE_PATH_FROM_HTML = '<?php echo WEB_ROOT_REL_FROM_HTML; ?>';
const DEFAULT_AVATAR_URL_JS = ASSET_BASE_PATH_FROM_HTML + 'assets/default_avatar.png';

document.addEventListener('DOMContentLoaded', function() {
    const submissionDetailArea = document.getElementById('submissionDetailArea');
    const gradingBreadcrumbs = document.getElementById('gradingBreadcrumbs');
    const breadcrumbCourseName = document.getElementById('breadcrumbCourseName');
    const breadcrumbAssignmentName = document.getElementById('breadcrumbAssignmentName');
    const breadcrumbSubmissionsList = document.getElementById('breadcrumbSubmissionsList');
    const breadcrumbStudentName = document.getElementById('breadcrumbStudentName');
    const pageTitleElement = document.querySelector('title');

    async function loadSubmissionForGrading() {
        // ... (код функції без змін, як у попередній відповіді) ...
        if (!CURRENT_SUBMISSION_ID) {
            if (submissionDetailArea.querySelector('.loading-text')) {
                 submissionDetailArea.querySelector('.loading-text').style.display = 'none';
            }
             // Якщо ID не передано, можна також відобразити помилку тут, а не лише через PHP
            if (!submissionDetailArea.querySelector('.error-text') && !document.querySelector('.error-text')) { // Перевірка, чи PHP вже не вивів помилку
                submissionDetailArea.innerHTML = '<p class="error-text">Помилка: ID зданої роботи не було передано.</p>';
            }
            return;
        }
        
        // Очищаємо область перед завантаженням, якщо там немає повідомлення про помилку з PHP
        if (!submissionDetailArea.querySelector('.error-text')) {
            submissionDetailArea.innerHTML = '<p class="loading-text"><i class="fas fa-spinner fa-spin"></i> Завантаження даних роботи...</p>';
        }


        try {
            const response = await fetch(`../../src/grading_actions.php?action=get_submission_for_grading&submission_id=${CURRENT_SUBMISSION_ID}`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: `HTTP помилка! Статус: ${response.status}` }));
                throw new Error(errorData.message);
            }
            const result = await response.json();

            if (result.status === 'success' && result.submission_details) {
                const details = result.submission_details;
                displaySubmissionDetails(details);
                updateBreadcrumbsAndTitle(details);
                gradingBreadcrumbs.style.display = 'block'; // Показуємо breadcrumbs після успішного завантаження
            } else {
                submissionDetailArea.innerHTML = `<p class="error-text">Не вдалося завантажити дані: ${result.message || 'Помилка сервера'}</p>`;
            }
        } catch (error) {
            console.error("Помилка завантаження даних для оцінювання:", error);
            submissionDetailArea.innerHTML = `<p class="error-text">Сталася помилка: ${error.message}. Спробуйте оновити сторінку.</p>`;
        }
    }

    function htmlspecialchars(str) {
        // ... (код функції без змін) ...
        if (typeof str !== 'string') return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, m => map[m]);
    }

    function nl2br(str) {
        // ... (код функції без змін) ...
        if (typeof str === 'undefined' || str === null) return '';
        return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
    }

    function displaySubmissionDetails(details) {
        // ... (початок функції без змін) ...
        const studentAvatarSrc = details.student_avatar_path ? (ASSET_BASE_PATH_FROM_HTML + details.student_avatar_path) : DEFAULT_AVATAR_URL_JS;
        let fileLinkHTML = 'Файл не прикріплено.';
        if (details.file_path) {
            const fileName = details.file_path.split('/').pop();
            const fileAccessPath = ASSET_BASE_PATH_FROM_HTML + details.file_path;
            fileLinkHTML = `<a href="${fileAccessPath}" target="_blank" rel="noopener noreferrer" class="submission-file-link">
                                <i class="fas fa-file-alt"></i> ${htmlspecialchars(fileName)}
                            </a>`;
        }

        submissionDetailArea.innerHTML = `
            <div class="grading-header">
                <h1>Оцінювання роботи: ${htmlspecialchars(details.assignment_title)}</h1>
                <div class="student-info-grading">
                    <img src="${studentAvatarSrc}?t=${new Date().getTime()}" alt="Аватар студента" class="student-avatar-grading">
                    <div>
                        <span>Студент: <strong>${htmlspecialchars(details.student_first_name)} ${htmlspecialchars(details.student_last_name)}</strong></span>
                        <span>(@${htmlspecialchars(details.student_username)})</span>
                    </div>
                </div>
                <p>Дата здачі: ${new Date(details.submission_date).toLocaleString('uk-UA')}</p>
            </div>

            <div class="submission-content-review">
                <h3>Зміст роботи студента:</h3>
                <div class="submission-file-block">
                    <strong>Прикріплений файл:</strong> ${fileLinkHTML}
                </div>
                ${details.submission_text ? `
                    <div class="submission-text-block">
                        <strong>Текстова відповідь/коментар студента:</strong>
                        <div class="text-content-display">${nl2br(htmlspecialchars(details.submission_text))}</div>
                    </div>
                ` : '<p><em>Текстова відповідь відсутня.</em></p>'}
            </div>

            <form id="gradingForm" class="grading-form-fields">
                <input type="hidden" name="submission_id" value="${details.submission_id}">
                <div class="form-group-grading">
                    <label for="gradeInput">Оцінка (максимум ${details.max_points}):</label>
                    <input type="number" id="gradeInput" name="grade" class="form-control-grading"
                           min="0" max="${details.max_points}" step="1" value="${details.grade !== null ? htmlspecialchars(String(parseInt(details.grade))) : ''}" placeholder="0-${details.max_points}">
                </div>
                
                <button type="submit" class="button-save-grade">
                    <i class="fas fa-save"></i> Зберегти оцінку
                </button>
                 <div id="gradingMessage" class="grading-message" style="display:none;"></div>
            </form>
        `;

        const gradingForm = document.getElementById('gradingForm');
        if (gradingForm) {
            gradingForm.addEventListener('submit', handleSaveGrade);
        }
    }
    
    function updateBreadcrumbsAndTitle(details) {
        // ... (код функції без змін) ...
        if (pageTitleElement) {
            pageTitleElement.textContent = `Оцінювання: ${htmlspecialchars(details.assignment_title)} (${htmlspecialchars(details.student_first_name)} ${htmlspecialchars(details.student_last_name)}) - Assignet`;
        }
        if (breadcrumbCourseName) {
            breadcrumbCourseName.textContent = htmlspecialchars(details.course_name);
            breadcrumbCourseName.href = `course_view.php?course_id=${details.course_id}`;
        }
        if (breadcrumbAssignmentName) {
            breadcrumbAssignmentName.textContent = htmlspecialchars(details.assignment_title);
            breadcrumbAssignmentName.href = `assignment_view.php?assignment_id=${details.assignment_id}`;
        }
        if (breadcrumbSubmissionsList) {
            breadcrumbSubmissionsList.href = `submissions_view.php?assignment_id=${details.assignment_id}`;
        }
        if (breadcrumbStudentName) {
            breadcrumbStudentName.textContent = `${htmlspecialchars(details.student_first_name)} ${htmlspecialchars(details.student_last_name)}`;
        }
    }

    async function handleSaveGrade(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'save_grade_and_feedback');

        const gradeInput = document.getElementById('gradeInput');
        const gradingMessage = document.getElementById('gradingMessage');
        const saveButton = form.querySelector('button[type="submit"]');
        const originalButtonHtml = saveButton.innerHTML;

        gradingMessage.style.display = 'none';
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Збереження...';

        const gradeValue = gradeInput.value.trim();
        const maxPoints = parseFloat(gradeInput.max); // Використовуємо parseFloat для max, але перевірятимемо на ціле
        
        if (gradeValue !== "") {
            if (!/^\d+$/.test(gradeValue)) { // ЗМІНЕНО: Перевірка, що це тільки цифри (ціле число)
                gradingMessage.textContent = 'Оцінка повинна бути цілим числом.';
                gradingMessage.className = 'grading-message error';
                gradingMessage.style.display = 'block';
                saveButton.disabled = false;
                saveButton.innerHTML = originalButtonHtml;
                return;
            }
            const grade = parseInt(gradeValue, 10); // ЗМІНЕНО: Парсимо як ціле число
            if (grade < 0 || grade > maxPoints) {
                gradingMessage.textContent = `Оцінка повинна бути в межах від 0 до ${parseInt(maxPoints)}.`; // Показуємо ціле maxPoints
                gradingMessage.className = 'grading-message error';
                gradingMessage.style.display = 'block';
                saveButton.disabled = false;
                saveButton.innerHTML = originalButtonHtml;
                return;
            }
        }

        try {
            const response = await fetch('../../src/grading_actions.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                gradingMessage.textContent = result.message || 'Оцінку збережено успішно!';
                gradingMessage.className = 'grading-message success';
                 // Оновити відображену оцінку в полі, якщо вона змінилася
                const currentGradeInDb = result.updated_grade; // Припускаємо, що сервер поверне оновлену оцінку
                if (currentGradeInDb !== undefined && currentGradeInDb !== null) {
                    gradeInput.value = parseInt(currentGradeInDb);
                } else if (gradeValue === "") { // Якщо оцінку було стерто
                    gradeInput.value = "";
                }

            } else {
                gradingMessage.textContent = result.message || 'Помилка збереження оцінки.';
                gradingMessage.className = 'grading-message error';
            }
        } catch (error) {
            console.error('Помилка AJAX при збереженні оцінки:', error);
            gradingMessage.textContent = 'Сталася помилка на клієнті. Деталі в консолі.';
            gradingMessage.className = 'grading-message error';
        } finally {
            gradingMessage.style.display = 'block';
            saveButton.disabled = false;
            saveButton.innerHTML = originalButtonHtml;
            setTimeout(() => { gradingMessage.style.display = 'none'; }, 5000);
        }
    }

    if (CURRENT_SUBMISSION_ID) {
        loadSubmissionForGrading();
    } else {
        // Якщо PHP вже вивів помилку, не перезаписуємо її
        if (!document.querySelector('.error-text')) { // Перевірка, чи PHP вже не вивів помилку
             submissionDetailArea.innerHTML = '<p class="error-text">Помилка: ID зданої роботи не вказано.</p>';
        }
        // Приховуємо індикатор завантаження, якщо він ще є
        const loadingTextElement = submissionDetailArea.querySelector('.loading-text');
        if(loadingTextElement) loadingTextElement.style.display = 'none';
    }
});
</script>
</body>
</html>