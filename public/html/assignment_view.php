<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../src/connect.php';
require_once __DIR__ . '/templates/header.php'; // Підключаємо загальний хедер

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$assignment_id_get = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);
$current_user_id = $_SESSION['user_id'];
$assignment_data = null;
$course_name_for_breadcrumb = 'Курс';
$is_teacher_of_this_course = false; 

if (!$assignment_id_get) {
    // $assignment_data залишиться null
} else {
    $stmt_assignment = $conn->prepare(
        "SELECT a.*, c.course_name, c.author_id as course_author_id
         FROM assignments a
         JOIN courses c ON a.course_id = c.course_id
         WHERE a.assignment_id = ?"
    );
    if ($stmt_assignment) {
        $stmt_assignment->bind_param("i", $assignment_id_get);
        $stmt_assignment->execute();
        $result_assignment = $stmt_assignment->get_result();
        if ($assignment_data_row = $result_assignment->fetch_assoc()) {
            $assignment_data = $assignment_data_row;
            $course_name_for_breadcrumb = htmlspecialchars($assignment_data['course_name']);
            if ($current_user_id == $assignment_data['course_author_id']) {
                $is_teacher_of_this_course = true;
            }
        }
        $stmt_assignment->close();
    } else {
        error_log("Failed to prepare statement for assignment data: " . $conn->error);
    }
}

$page_title = $assignment_data ? htmlspecialchars($assignment_data['title']) : 'Завдання не знайдено';

?>

<title><?php echo $page_title; ?> - Assignet</title>
<link rel="stylesheet" href="../css/assignment_view_styles.css">
<div class="course-view-main-content"> 
        <?php if ($assignment_data): ?>
            <div class="course-header-bar">
                <div class="breadcrumbs">
                    <a href="home.php">Мої курси</a> &gt;
                    <a href="course_view.php?course_id=<?php echo htmlspecialchars($assignment_data['course_id']); ?>"><?php echo $course_name_for_breadcrumb; ?></a> &gt;
                    <a href="course_view.php?course_id=<?php echo htmlspecialchars($assignment_data['course_id']); ?>#assignments">Завдання</a> &gt; <span id="current-assignment-breadcrumb"><?php echo htmlspecialchars($assignment_data['title']); ?></span>
                </div>
            </div>

            <div class="assignment-detail-container">
                <div class="assignment-header-details">
                    <h1><?php echo htmlspecialchars($assignment_data['title']); ?></h1>
                    <div class="assignment-meta">
                        <?php if($assignment_data['section_title']): ?>
                            <span class="meta-item section"><i class="fas fa-folder-open"></i> Розділ: <?php echo htmlspecialchars($assignment_data['section_title']); ?></span>
                        <?php endif; ?>
                        <span class="meta-item points"><i class="fas fa-star"></i> Макс. балів: <?php echo htmlspecialchars($assignment_data['max_points']); ?></span>
                        <?php if ($assignment_data['due_date']): ?>
                            <span class="meta-item due-date <?php
                                $due_date_obj = new DateTime($assignment_data['due_date']);
                                $now = new DateTime();
                                if ($due_date_obj < $now) echo 'past-due';
                                elseif (($now->diff($due_date_obj))->days <=3 && !$now->diff($due_date_obj)->invert) echo 'due-soon';
                            ?>">
                                <i class="fas fa-calendar-times"></i> Здати до: <?php echo $due_date_obj->format('d.m.Y H:i'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="assignment-description-full">
                    <h3>Опис завдання:</h3>
                    <p><?php echo nl2br(htmlspecialchars($assignment_data['description'] ?? 'Опис відсутній.')); ?></p>
                </div>

                <hr class="assignment-divider">

                <?php if (!$is_teacher_of_this_course): ?>
                    <div id="studentSubmissionArea">
                        <h2>Ваша робота</h2>
                        <p>Завантаження інформації про здачу...</p>
                    </div>
                <?php else: ?>
                    <div id="teacherAssignmentActions">
                        <h2>Дії викладача</h2>
                        <a href="submissions_view.php?assignment_id=<?php echo $assignment_id_get; ?>" class="button-link view-submissions-link">
                            <i class="fas fa-list-check"></i> Переглянути здані роботи
                        </a>
                    </div>
                <?php endif; ?>

            </div>

        <?php else: ?>
            <div class="course-not-found"> 
                <h1>Помилка</h1>
                <p>Завдання з ID <?php echo htmlspecialchars($_GET['assignment_id'] ?? 'невідомим'); ?> не знайдено або у вас немає до нього доступу.</p>
                <a href="home.php" class="button">Повернутися на головну</a>
            </div>
        <?php endif; ?>
    </div>

</main> </div> <script>
// ... (ваш JavaScript для assignment_view.php) ...
document.addEventListener('DOMContentLoaded', function() {
    const assignmentId = <?php echo $assignment_id_get ? json_encode((int)$assignment_id_get) : 'null'; ?>;
    const studentSubmissionArea = document.getElementById('studentSubmissionArea');
    const isTeacher = <?php echo json_encode($is_teacher_of_this_course); ?>;

    async function loadAssignmentAndSubmissionDetails(assId) {
        if (!assId) return;
        if (!isTeacher && studentSubmissionArea) { 
            studentSubmissionArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження вашої роботи...</p>';
            try {
                const response = await fetch(`../../src/course_actions.php?action=get_assignment_submission_details&assignment_id=${assId}`);
                if (!response.ok) {
                     const errorText = await response.text();
                     console.error("Server error response text:", errorText);
                     throw new Error('Network response was not ok.');
                }
                const result = await response.json();
                if (result.status === 'success') {
                    const assignmentMaxPoints = result.assignment_details ? result.assignment_details.max_points : 'N/A';
                    if (result.submission_details) {
                        const submission = result.submission_details;
                        let filesHTML = '';
                        if (submission.file_path) {
                            const fileName = submission.file_path.split('/').pop();
                            const fileDisplayPath = `../${submission.file_path}`;
                            filesHTML = `<p><strong>Прикріплений файл:</strong> <a href="<span class="math-inline">\{fileDisplayPath\}" target\="\_blank" rel\="noopener noreferrer"\></span>{htmlspecialchars(fileName)}</a></p>`;
                        }
                        let statusText = 'Невідомо';
                        let statusClass = '';
                        switch(submission.status) {
                            case 'submitted': statusText = 'Здано'; statusClass = 'submitted'; break;
                            case 'graded': statusText = 'Оцінено'; statusClass = 'graded'; break;
                            case 'pending_submission': statusText = 'Не здано'; statusClass = 'pending'; break;
                            case 'missed': statusText = 'Пропущено'; statusClass = 'missed'; break;
                            default: statusText = submission.status;
                        }
                        studentSubmissionArea.innerHTML = `
                            <h4>Статус: <span class="submission-status <span class="math-inline">\{statusClass\}"\></span>{statusText}</span></h4>
                            <p><strong>Дата останньої здачі/зміни:</strong> ${submission.submission_date ? new Date(submission.submission_date).toLocaleString('uk-UA') : 'N/A'}</p>
                            ${filesHTML}
                            ${submission.submission_text ? `<p><strong>Ваш коментар/текст:</strong><br>${nl2br(htmlspecialchars(submission.submission_text))}</p>` : ''}
                            ${submission.grade !== null ? `<p><strong>Оцінка:</strong> ${submission.grade} / ${assignmentMaxPoints}</p>` : ''}
                            ${submission.feedback ? `<p><strong>Коментар викладача:</strong><br>${nl2br(htmlspecialchars(submission.feedback))}</p>` : ''}
                            ${ (submission.status === 'submitted' || submission.status === 'pending_submission') ?
                                `<button id="resubmitBtn" class="button-link" style="margin-top:10px;">Здати/Змінити роботу</button>` : '' }
                        `;
                        const resubmitBtn = document.getElementById('resubmitBtn');
                        if(resubmitBtn) {
                            resubmitBtn.addEventListener('click', showSubmissionForm);
                        }
                    } else { 
                        showSubmissionForm();
                    }
                } else {
                    studentSubmissionArea.innerHTML = `<p>Не вдалося завантажити інформацію про здачу: ${result.message || 'Помилка сервера'}</p>`;
                }
            } catch (error) {
                console.error('Помилка завантаження деталей завдання/здачі:', error);
                studentSubmissionArea.innerHTML = '<p>Помилка завантаження. Спробуйте пізніше.</p>';
            }
        }
    }

    function showSubmissionForm() {
        if (!studentSubmissionArea || isTeacher) return;
        studentSubmissionArea.innerHTML = `
            <form id="submitAssignmentForm" enctype="multipart/form-data">
                <input type="hidden" name="assignment_id" value="${assignmentId}">
                <div class="form-group-modal">
                    <label for="submission_file">Прикріпити файл (макс. 2MB, дозволені типи: pdf, doc, docx, txt, jpg, png, zip):</label>
                    <input type="file" id="submission_file" name="submission_file" class="form-control-modal">
                    <small id="fileValidationError" style="color:red; display:none;"></small>
                </div>
                <div class="form-group-modal">
                    <label for="submission_text">Коментар або текстова відповідь (необов'язково):</label>
                    <textarea id="submission_text" name="submission_text" rows="4" class="form-control-modal"></textarea>
                </div>
                <button type="submit" class="submit-button-modal"><i class="fas fa-upload"></i> Здати роботу</button>
            </form>
        `;
        const submitForm = document.getElementById('submitAssignmentForm');
        if(submitForm) {
            submitForm.addEventListener('submit', handleAssignmentSubmit);
        }
        const submissionFileElement = document.getElementById('submission_file');
        if(submissionFileElement) {
            submissionFileElement.addEventListener('change', validateSubmissionFile);
        }
    }

    function validateSubmissionFile(event) {
        const fileInput = event.target;
        const file = fileInput.files[0];
        const errorElement = document.getElementById('fileValidationError');
        errorElement.style.display = 'none';
        errorElement.textContent = '';
        if (file) {
            const maxSize = 2 * 1024 * 1024; 
            if (file.size > maxSize) {
                errorElement.textContent = 'Файл занадто великий. Максимальний розмір - 2MB.';
                errorElement.style.display = 'block';
                fileInput.value = ''; 
                return false;
            }
            const allowedExtensions = /(\.pdf|\.doc|\.docx|\.txt|\.jpg|\.jpeg|\.png|\.zip)$/i;
            if (!allowedExtensions.exec(file.name)) {
                errorElement.textContent = 'Неприпустимий тип файлу. Дозволені: PDF, DOC, DOCX, TXT, JPG, PNG, ZIP.';
                errorElement.style.display = 'block';
                fileInput.value = '';
                return false;
            }
        }
        return true;
    }

    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/[&<>"']/g, function (match) {
            const map = {
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            };
            return map[match];
        });
    }

    function nl2br(str) { 
        if (typeof str === 'undefined' || str === null) {
            return '';
        }
        return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
    }

    async function handleAssignmentSubmit(event) {
        event.preventDefault();
        const fileInput = document.getElementById('submission_file');
        if (fileInput && fileInput.files.length > 0 && !validateSubmissionFile({target: fileInput})) {
             alert('Будь ласка, виправте помилки у файлі перед відправкою.');
             return;
        }
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'submit_assignment');
        const submitButton = form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Надсилання...';
        try {
            const response = await fetch('../../src/course_actions.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                alert('Роботу успішно здано!');
                if(assignmentId) loadAssignmentAndSubmissionDetails(assignmentId);
            } else {
                alert(`Помилка: ${result.message || 'Не вдалося здати роботу.'}`);
            }
        } catch (error) {
            console.error('Помилка AJAX при здачі завдання:', error);
            alert('Сталася помилка. Спробуйте пізніше.');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    }

    if (assignmentId) { 
        loadAssignmentAndSubmissionDetails(assignmentId);
    }
});
</script>

</body> </html> 

