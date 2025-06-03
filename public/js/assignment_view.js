document.addEventListener('DOMContentLoaded', function() {
    const studentSubmissionArea = document.getElementById('studentSubmissionArea');
    const teacherAttachmentsArea = document.getElementById('teacherAttachmentsArea'); 
    const teacherAttachmentsList = document.getElementById('teacherAttachmentsList'); 


    async function loadAssignmentAndSubmissionDetails(assId) {
        if (!assId) return;

        if (teacherAttachmentsArea) teacherAttachmentsArea.style.display = 'none'; 
        if (teacherAttachmentsList) teacherAttachmentsList.innerHTML = ''; 

        if (!IS_TEACHER && studentSubmissionArea) {
            studentSubmissionArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження вашої роботи...</p>';
        } else if (IS_TEACHER && document.getElementById('teacherAssignmentActions')) {
            // Teacher specific loading indicator could go here if needed
        }
        
        try {
            const response = await fetch(`../../src/actions/course_actions.php?action=get_assignment_submission_details&assignment_id=${assId}`);
            if (!response.ok) {
                 const errorText = await response.text();
                 console.error("Server error response text:", errorText);
                 throw new Error('Network response was not ok.');
            }
            const result = await response.json();

            if (result.status === 'success') {
                const assignmentDetails = result.assignment_details; 
                const assignmentMaxPoints = assignmentDetails ? assignmentDetails.max_points : 'N/A';

                // Display teacher attachments for everyone
                if (assignmentDetails && assignmentDetails.teacher_attachments && assignmentDetails.teacher_attachments.length > 0) {
                    if (teacherAttachmentsList && teacherAttachmentsArea) {
                        teacherAttachmentsList.innerHTML = ''; 
                        assignmentDetails.teacher_attachments.forEach(file => {
                            const listItem = document.createElement('li');
                            const fileDisplayPath = (typeof WEB_ROOT_REL_FROM_HTML_ASSIGNMENT_VIEW !== 'undefined' ? WEB_ROOT_REL_FROM_HTML_ASSIGNMENT_VIEW : '../') + file.file_path;
                            
                            let fileIcon = 'fa-file-alt'; 
                            const extension = file.file_name.split('.').pop().toLowerCase();
                            if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                                fileIcon = 'fa-file-image';
                            } else if (['pdf'].includes(extension)) {
                                fileIcon = 'fa-file-pdf';
                            } else if (['doc', 'docx'].includes(extension)) {
                                fileIcon = 'fa-file-word';
                            } else if (['ppt', 'pptx'].includes(extension)) {
                                fileIcon = 'fa-file-powerpoint';
                            } else if (['xls', 'xlsx'].includes(extension)) {
                                fileIcon = 'fa-file-excel';
                            } else if (['zip', 'rar', '7z'].includes(extension)) {
                                fileIcon = 'fa-file-archive';
                            } else if (['mp3', 'wav', 'ogg'].includes(extension)) {
                                fileIcon = 'fa-file-audio';
                            } else if (['mp4', 'mov', 'avi', 'mkv'].includes(extension)) {
                                fileIcon = 'fa-file-video';
                            }

                            listItem.innerHTML = `<a href="${fileDisplayPath}" target="_blank" rel="noopener noreferrer" title="Завантажити ${htmlspecialchars(file.file_name)}">
                                                    <i class="fas ${fileIcon}"></i> ${htmlspecialchars(file.file_name)}
                                                </a>`;
                            
                            if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                                const imgPreview = document.createElement('img');
                                imgPreview.src = fileDisplayPath;
                                imgPreview.alt = htmlspecialchars(file.file_name);
                                imgPreview.classList.add('attachment-thumbnail');
                                listItem.appendChild(imgPreview);
                            }
                            teacherAttachmentsList.appendChild(listItem);
                        });
                        teacherAttachmentsArea.style.display = 'block';
                    }
                }


                if (!IS_TEACHER && studentSubmissionArea) { 
                     if (result.submission_details) {
                        const submission = result.submission_details;
                        let filesHTML = '';
                        if (submission.file_path) {
                            const fileName = submission.file_path.split('/').pop();
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
                            ${ (submission.status === 'submitted' || submission.status === 'pending_submission' || submission.status === 'missed' || submission.status === null ) ? 
                                `<button id="resubmitBtn" class="button-link submit-assignment-btn-student">
                                    <i class="fas ${submission.status === 'submitted' ? 'fa-edit' : 'fa-upload'}"></i> 
                                    ${submission.status === 'submitted' ? 'Змінити роботу' : 'Здати роботу'}
                                 </button>` : '' }
                        `; 
                        const resubmitBtn = document.getElementById('resubmitBtn');
                        if(resubmitBtn) {
                            resubmitBtn.addEventListener('click', showSubmissionForm);
                        }
                    } else {
                        showSubmissionForm();
                    }
                }

            } else { 
                if (studentSubmissionArea && !IS_TEACHER) studentSubmissionArea.innerHTML = `<p>Не вдалося завантажити інформацію про здачу: ${result.message || 'Помилка сервера'}</p>`;
                console.error(`Error loading assignment/submission details: ${result.message}`);
            }
        } catch (error) {
            console.error('Помилка завантаження деталей завдання/здачі:', error);
            if (studentSubmissionArea && !IS_TEACHER) studentSubmissionArea.innerHTML = '<p>Помилка завантаження. Спробуйте пізніше.</p>';
        }
    }

    function showSubmissionForm() {
        if (!studentSubmissionArea || IS_TEACHER) return;

        studentSubmissionArea.innerHTML = `
            <form id="submitAssignmentForm" enctype="multipart/form-data">
                <input type="hidden" name="assignment_id" value="${ASSIGNMENT_ID_GLOBAL}">
                <div class="form-group-modal">
                    <label for="submission_file">Прикріпити файл (макс. 5MB, дозволені типи: pdf, doc, docx, txt, jpg, png, zip):</label>
                    <input type="file" id="submission_file" name="submission_file" class="form-control-modal">
                    <small id="fileValidationError" style="color:red; display:none;"></small>
                </div>
                <div class="form-group-modal">
                    <label for="submission_text">Коментар або текстова відповідь (необов'язково):</label>
                    <textarea id="submission_text" name="submission_text" rows="4" class="form-control-modal"></textarea>
                </div>
                <button type="submit" class="submit-button-modal submit-assignment-btn-student"><i class="fas fa-upload"></i> Здати роботу</button>
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
            const maxSize = 5 * 1024 * 1024; 
            if (file.size > maxSize) {
                errorElement.textContent = 'Файл занадто великий. Максимальний розмір - 5MB.';
                errorElement.style.display = 'block';
                fileInput.value = '';
                return false;
            }
            const allowedExtensions = /(\.pdf|\.doc|\.docx|\.txt|\.jpg|\.jpeg|\.png|\.zip)$/i;
            if (!allowedExtensions.exec(file.name)) {
                errorElement.textContent = 'Неприпустимий тип файлу. Дозволені: PDF, DOC(X), TXT, JPG, PNG, ZIP.';
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
            const response = await fetch('../../src/actions/course_actions.php', { 
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                alert('Роботу успішно здано!');
                if(ASSIGNMENT_ID_GLOBAL) loadAssignmentAndSubmissionDetails(ASSIGNMENT_ID_GLOBAL);
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

    if (typeof ASSIGNMENT_ID_GLOBAL !== 'undefined' && ASSIGNMENT_ID_GLOBAL !== null) {
        loadAssignmentAndSubmissionDetails(ASSIGNMENT_ID_GLOBAL);
    }
});