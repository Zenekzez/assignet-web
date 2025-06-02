document.addEventListener('DOMContentLoaded', function() {
    // PHP змінні 'assignmentId' та 'isTeacher' тепер будуть глобальними JS змінними,
    // які ми визначимо в PHP файлі перед підключенням цього скрипта.
    // Наприклад, const assignmentId = PHP_ASSIGNMENT_ID; (див. крок 3)

    const studentSubmissionArea = document.getElementById('studentSubmissionArea');

    async function loadAssignmentAndSubmissionDetails(assId) {
        if (!assId) return;

        // Зверніть увагу: 'isTeacher' тепер глобальна JS змінна
        if (!IS_TEACHER && studentSubmissionArea) {
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
                            // Важливо: шлях до файлу для відображення має бути коректним відносно HTML сторінки.
                            // Якщо ASSIGNMENT_VIEW_ASSET_BASE_PATH визначено в PHP, використовуйте його.
                            // Або переконайтеся, що шлях `../${submission.file_path}` працює з вашою структурою.
                            // Для більшої надійності можна передати базовий шлях як ще одну JS змінну.
                            const fileDisplayPath = (typeof WEB_ROOT_REL_FROM_HTML_ASSIGNMENT_VIEW !== 'undefined' ? WEB_ROOT_REL_FROM_HTML_ASSIGNMENT_VIEW : '../') + submission.file_path;
                            filesHTML = `<p><strong>Прикріплений файл:</strong> <a href="${fileDisplayPath}" target="_blank" rel="noopener noreferrer">${htmlspecialchars(fileName)}</a></p>`;
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
                            <h4>Статус: <span class="submission-status ${statusClass}">${statusText}</span></h4>
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
        // 'isTeacher' та 'assignmentId' тут використовуються як глобальні змінні JS
        if (!studentSubmissionArea || IS_TEACHER) return;

        studentSubmissionArea.innerHTML = `
            <form id="submitAssignmentForm" enctype="multipart/form-data">
                <input type="hidden" name="assignment_id" value="${ASSIGNMENT_ID_GLOBAL}">
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
            const maxSize = 2 * 1024 * 1024; // 2MB
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
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, m => map[m]);
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
                if(ASSIGNMENT_ID_GLOBAL) loadAssignmentAndSubmissionDetails(ASSIGNMENT_ID_GLOBAL); // Використовуємо глобальну JS змінну
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

    // Перевіряємо, чи ASSIGNMENT_ID_GLOBAL визначено (буде визначено в PHP файлі)
    if (typeof ASSIGNMENT_ID_GLOBAL !== 'undefined' && ASSIGNMENT_ID_GLOBAL !== null) {
        loadAssignmentAndSubmissionDetails(ASSIGNMENT_ID_GLOBAL);
    }
});