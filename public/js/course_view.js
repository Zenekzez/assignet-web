document.addEventListener('DOMContentLoaded', function() {
    // Глобальні змінні, які будуть визначені в PHP файлі
    // const CURRENT_COURSE_ID_FOR_JS;
    // const IS_CURRENT_USER_TEACHER_OF_THIS_COURSE;
    // const CURRENT_USER_ID_PHP;
    // const WEB_ROOT_REL_FROM_HTML_CV_JS;
    // const ACTUAL_COURSE_NAME_PHP;
    // const COURSE_JOIN_CODE_FROM_DB_JS;
    // const DEFAULT_AVATAR_REL_PATH_JS;

    const tabLinks = document.querySelectorAll('.course-tab-navigation .tab-link');
    const tabPanes = document.querySelectorAll('.course-tab-content-area .tab-pane');
    const breadcrumbCurrentTab = document.getElementById('current-tab-breadcrumb');

    const createAnnouncementForm = document.getElementById('createAnnouncementForm');
    const announcementsArea = document.getElementById('announcementsArea');
    // currentCourseIdForJS та isCurrentUserTeacherOfThisCourse тепер глобальні змінні

    const courseSettingsForm = document.getElementById('courseSettingsForm');
    const courseBannerTitleElement = document.querySelector('.course-banner-title');
    const breadcrumbCourseNameElement = document.querySelector('.breadcrumb-course-name');

    let currentJoinCodeForJS = COURSE_JOIN_CODE_FROM_DB_JS; // Використовуємо передану змінну
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
    const defaultAvatarRelPath = DEFAULT_AVATAR_REL_PATH_JS; // 'assets/default_avatar.png';
    const baseAvatarUrl = WEB_ROOT_REL_FROM_HTML_CV_JS; // '../';

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
    if (regenerateJoinCodeBtnSettings && CURRENT_COURSE_ID_FOR_JS && IS_CURRENT_USER_TEACHER_OF_THIS_COURSE) {
        regenerateJoinCodeBtnSettings.addEventListener('click', async function() {
            if (!confirm('Ви впевнені, що хочете згенерувати новий код приєднання? Старий код стане недійсним і буде збережено негайно.')) {
                return;
            }
            const originalButtonText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Генерація...';

            const formData = new FormData();
            formData.append('action', 'regenerate_join_code');
            formData.append('course_id', CURRENT_COURSE_ID_FOR_JS);

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
    const actualCourseName = ACTUAL_COURSE_NAME_PHP; // Використовуємо передану змінну

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

        if (!IS_CURRENT_USER_TEACHER_OF_THIS_COURSE) return;
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
        if (!courseId || !teacherGradesSummaryArea || !IS_CURRENT_USER_TEACHER_OF_THIS_COURSE) return;
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
    
    // Функція завантаження оголошень
    async function loadAnnouncements(courseId) {
        if (!courseId || !announcementsArea) return;
        announcementsArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження оголошень...</p>';
        try {
            const response = await fetch(`../../src/course_actions.php?action=get_announcements&course_id=${courseId}`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: `HTTP помилка! Статус: ${response.status}` }));
                throw new Error(errorData.message);
            }
            const result = await response.json();
            announcementsArea.innerHTML = '';
            if (result.status === 'success' && result.announcements) {
                if (result.announcements.length > 0) {
                    result.announcements.forEach(ann => {
                        const item = document.createElement('div'); item.classList.add('announcement-item');
                        const authorAvatar = ann.author_avatar_path ? (baseAvatarUrl + ann.author_avatar_path) : (baseAvatarUrl + defaultAvatarRelPath);
                        item.innerHTML = `
                            <div class="announcement-header">
                                <div class="announcement-author-info">
                                    <img src="${authorAvatar}?t=${new Date().getTime()}" alt="Аватар" class="announcement-author-avatar">
                                    <span class="announcement-author">${htmlspecialchars(ann.author_username)}</span>
                                </div>
                                <span class="announcement-date">${new Date(ann.created_at).toLocaleString('uk-UA', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                            </div>
                            <div class="announcement-content">${ann.content.replace(/\n/g, '<br>')}</div>
                        `;
                        announcementsArea.appendChild(item);
                    });
                } else { announcementsArea.innerHTML = '<p>Оголошень для цього курсу ще немає.</p>'; }
            } else { announcementsArea.innerHTML = `<p>Не вдалося завантажити оголошення: ${result.message || 'Помилка сервера'}</p>`; }
        } catch (error) { console.error("Помилка завантаження оголошень:", error); announcementsArea.innerHTML = '<p>Сталася помилка при завантаженні оголошень. Спробуйте оновити сторінку.</p>'; }
    }

    // Функція завантаження завдань
    async function loadAssignments(courseId, sortBy = 'due_date_asc') {
        if (!courseId || !assignmentsListArea) return;
        assignmentsListArea.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Завантаження завдань...</p>';
         if (IS_CURRENT_USER_TEACHER_OF_THIS_COURSE) { // Оновлюємо список секцій для викладача
            await fetchAndPopulateExistingSections(courseId);
        }
        try {
            const response = await fetch(`../../src/course_actions.php?action=get_assignments&course_id=${courseId}&sort_by=${sortBy}`);
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ message: `HTTP помилка! Статус: ${response.status}` }));
                throw new Error(errorData.message);
            }
            const result = await response.json();
            assignmentsListArea.innerHTML = ''; // Очищаємо перед рендерингом
            if (result.status === 'success' && result.assignments) {
                if (result.assignments.length === 0) {
                    assignmentsListArea.innerHTML = '<p>Для цього курсу ще не створено жодного завдання.</p>';
                } else {
                    const assignmentsBySection = {};
                    result.assignments.forEach(asm => {
                        const section = asm.section_title || 'Завдання без розділу';
                        if (!assignmentsBySection[section]) assignmentsBySection[section] = [];
                        assignmentsBySection[section].push(asm);
                    });
                    
                    const sectionOrder = allExistingSections.slice(); // Копіюємо, щоб не змінити оригінал
                    if (!sectionOrder.includes('Завдання без розділу') && assignmentsBySection['Завдання без розділу']) {
                        sectionOrder.push('Завдання без розділу'); // Додаємо розділ "без розділу" в кінець, якщо є такі завдання
                    }
                    
                    sectionOrder.forEach(sectionKey => {
                        if (assignmentsBySection[sectionKey]) {
                            const sectionContainer = document.createElement('div');
                            sectionContainer.classList.add('assignment-section-container');
                            if (sectionKey !== 'Завдання без розділу') {
                                const sectionTitle = document.createElement('h3');
                                sectionTitle.classList.add('section-title-header');
                                sectionTitle.textContent = htmlspecialchars(sectionKey);
                                sectionContainer.appendChild(sectionTitle);
                            }
                            const assignmentsGrid = document.createElement('div');
                            assignmentsGrid.classList.add('assignments-grid-internal');
                            assignmentsBySection[sectionKey].forEach(asm => {
                                const item = createAssignmentCard(asm, result.is_teacher_of_course);
                                assignmentsGrid.appendChild(item);
                            });
                            sectionContainer.appendChild(assignmentsGrid);
                            assignmentsListArea.appendChild(sectionContainer);
                        }
                    });
                }
            } else { assignmentsListArea.innerHTML = `<p>Не вдалося завантажити завдання: ${result.message || 'Помилка сервера'}</p>`; }
        } catch (error) { console.error("Помилка завантаження завдань:", error); assignmentsListArea.innerHTML = '<p>Сталася помилка. Спробуйте оновити сторінку.</p>'; }
    }
    
    function createAssignmentCard(assignment, isTeacherView) {
        const item = document.createElement('div'); item.classList.add('assignment-item-card-compact');
        item.dataset.assignmentId = assignment.assignment_id;
        
        let deadlineIndicator = '';
        if (assignment.due_date) {
            const dueDateObj = new Date(assignment.due_date); const now = new Date();
            const timeDiff = dueDateObj - now; const daysDiff = timeDiff / (1000 * 3600 * 24);
            if (timeDiff < 0) { deadlineIndicator = '<span class="deadline-indicator-compact past"><i class="fas fa-exclamation-circle"></i> Прострочено</span>'; }
            else if (daysDiff <= 3) { deadlineIndicator = '<span class="deadline-indicator-compact soon"><i class="fas fa-bell"></i> Терміново</span>'; }
        }
        
        const statusInfo = getStatusTextAndClass(assignment.submission_status, assignment.due_date);
        let statusBadge = '';
        if (!isTeacherView) { statusBadge = `<span class="submission-status-compact ${statusInfo.class}">${statusInfo.text}</span>`; }

        let teacherActionsMenu = '';
        if (isTeacherView) {
            teacherActionsMenu = `
                <div class="assignment-actions-menu-compact">
                    <button class="action-menu-toggle-compact" aria-label="Дії із завданням" data-assignment-id="${assignment.assignment_id}">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="action-dropdown-compact">
                        <a href="submissions_view.php?assignment_id=${assignment.assignment_id}" class="action-view-submissions">
                            <i class="fas fa-list-check"></i> Здані роботи
                        </a>
                        <a href="#" class="action-edit-assignment" data-assignment-id="${assignment.assignment_id}">
                            <i class="fas fa-edit"></i> Редагувати
                        </a>
                        <a href="#" class="action-delete-assignment" data-assignment-id="${assignment.assignment_id}" data-assignment-title="${htmlspecialchars(assignment.title)}">
                            <i class="fas fa-trash-alt"></i> Видалити
                        </a>
                    </div>
                </div>`;
        }
        
        item.innerHTML = `
            <div class="card-content-compact">
                <div class="card-title-line-compact">
                    <h4 class="assignment-title-compact">
                        <a href="assignment_view.php?assignment_id=${assignment.assignment_id}">${htmlspecialchars(assignment.title)}</a>
                        ${deadlineIndicator}
                    </h4>
                    ${isTeacherView ? teacherActionsMenu : ''}
                </div>
                ${assignment.description ? `<p style="font-size: 0.85em; color: #666; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${htmlspecialchars(assignment.description)}">${htmlspecialchars(assignment.description.substring(0,100))}${assignment.description.length > 100 ? '...' : ''}</p>` : ''}
                <div class="card-meta-line-compact">
                    ${assignment.created_at_formatted ? `<span>Опубліковано: ${assignment.created_at_formatted}</span>` : ''}
                    ${assignment.updated_at_formatted ? `<span class="meta-divider-compact">|</span><span>Оновлено: ${assignment.updated_at_formatted}</span>` : ''}
                </div>
                 <div class="card-meta-line-compact">
                    ${assignment.due_date_formatted ? `<span>Здати до: <strong>${assignment.due_date_formatted}</strong></span>` : '<span>Без терміну</span>'}
                    <span class="meta-divider-compact">|</span>
                    <span>Бали: ${assignment.max_points}</span>
                    ${!isTeacherView ? `<span class="meta-divider-compact">|</span>${statusBadge}` : ''}
                </div>
                ${isTeacherView ? `
                    <div class="card-teacher-actions-line-compact">
                        <a href="submissions_view.php?assignment_id=${assignment.assignment_id}" class="button-link-compact view-submissions-link-compact">
                            <i class="fas fa-list-check"></i> Переглянути здані роботи
                        </a>
                    </div>
                ` : ''}
            </div>
        `;
        if(isTeacherView) {
            item.querySelector('.action-menu-toggle-compact')?.addEventListener('click', toggleActionDropdown);
            item.querySelector('.action-edit-assignment')?.addEventListener('click', handleEditAssignmentClick);
            item.querySelector('.action-delete-assignment')?.addEventListener('click', handleDeleteAssignmentClick);
        }
        return item;
    }

    function toggleActionDropdown(event) {
        event.stopPropagation();
        const currentDropdown = this.nextElementSibling;
        document.querySelectorAll('.action-dropdown-compact.visible').forEach(openDropdown => {
            if (openDropdown !== currentDropdown) openDropdown.classList.remove('visible');
        });
        currentDropdown.classList.toggle('visible');
    }
    document.addEventListener('click', function (event) {
        document.querySelectorAll('.action-dropdown-compact.visible').forEach(dropdown => {
            if (!dropdown.previousElementSibling.contains(event.target)) { dropdown.classList.remove('visible'); }
        });
    });

    async function handleEditAssignmentClick(event) {
        event.preventDefault(); event.stopPropagation();
        const assignmentId = this.dataset.assignmentId;
        if (!assignmentId) return;
        if (editAssignmentModal && editAssignmentFormInternal) {
            editAssignmentFormInternal.reset();
            if (CURRENT_COURSE_ID_FOR_JS) { await fetchAndPopulateExistingSections(CURRENT_COURSE_ID_FOR_JS); }
            try {
                const response = await fetch(`../../src/course_actions.php?action=get_assignment_details_for_edit&assignment_id=${assignmentId}`);
                if (!response.ok) throw new Error('Failed to fetch assignment details.');
                const result = await response.json();
                if (result.status === 'success' && result.assignment) {
                    const data = result.assignment;
                    if(assignmentIdEditInput) assignmentIdEditInput.value = data.assignment_id;
                    if(assignmentTitleEditModal) assignmentTitleEditModal.value = data.title || '';
                    if(assignmentDescriptionEditModal) assignmentDescriptionEditModal.value = data.description || '';
                    if(assignmentSectionTitleEditModal) assignmentSectionTitleEditModal.value = data.section_title || '';
                    if(assignmentMaxPointsEditModal) assignmentMaxPointsEditModal.value = data.max_points || '';
                    if(assignmentDueDateEditModal && data.due_date) {
                        const localDueDate = new Date(data.due_date + 'Z').toISOString().slice(0, 16);
                        assignmentDueDateEditModal.value = localDueDate;
                    } else if (assignmentDueDateEditModal) { assignmentDueDateEditModal.value = ''; }
                    editAssignmentModal.style.display = 'flex';
                } else { alert(result.message || 'Не вдалося завантажити дані завдання.'); }
            } catch (error) { console.error("Error fetching assignment for edit:", error); alert('Помилка завантаження даних.'); }
        }
        document.querySelectorAll('.action-dropdown-compact.visible').forEach(d => d.classList.remove('visible'));
    }

    async function handleDeleteAssignmentClick(event) {
        event.preventDefault(); event.stopPropagation();
        const assignmentId = this.dataset.assignmentId; const assignmentTitle = this.dataset.assignmentTitle;
        if (!assignmentId || !confirm(`Ви впевнені, що хочете видалити завдання "${assignmentTitle}"? Ця дія незворотна і видалить всі пов'язані здані роботи.`)) {
            document.querySelectorAll('.action-dropdown-compact.visible').forEach(d => d.classList.remove('visible'));
            return;
        }
        const formData = new FormData(); formData.append('action', 'delete_assignment'); formData.append('assignment_id', assignmentId); formData.append('course_id', CURRENT_COURSE_ID_FOR_JS);
        try {
            const response = await fetch('../../src/course_actions.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                alert(result.message);
                if (CURRENT_COURSE_ID_FOR_JS && assignmentSortSelect) { loadAssignments(CURRENT_COURSE_ID_FOR_JS, assignmentSortSelect.value); }
            } else { alert(`Помилка: ${result.message || 'Не вдалося видалити завдання.'}`); }
        } catch (error) { console.error('AJAX error deleting assignment:', error); alert('Сталася помилка на клієнті.'); }
        document.querySelectorAll('.action-dropdown-compact.visible').forEach(d => d.classList.remove('visible'));
    }

    tabLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const targetTab = this.getAttribute('data-tab');
            tabLinks.forEach(l => l.classList.remove('active')); this.classList.add('active');
            tabPanes.forEach(pane => pane.classList.toggle('active', pane.id === 'tab-' + targetTab));
            if (breadcrumbCurrentTab) breadcrumbCurrentTab.textContent = this.textContent;

            if (targetTab === 'assignments' && CURRENT_COURSE_ID_FOR_JS && assignmentSortSelect) loadAssignments(CURRENT_COURSE_ID_FOR_JS, assignmentSortSelect.value);
            else if (targetTab === 'stream' && CURRENT_COURSE_ID_FOR_JS) loadAnnouncements(CURRENT_COURSE_ID_FOR_JS);
            else if (targetTab === 'people' && CURRENT_COURSE_ID_FOR_JS) loadCourseParticipants(CURRENT_COURSE_ID_FOR_JS);
            else if (targetTab === 'my-grades' && CURRENT_COURSE_ID_FOR_JS && !IS_CURRENT_USER_TEACHER_OF_THIS_COURSE) loadMyGrades(CURRENT_COURSE_ID_FOR_JS);
            else if (targetTab === 'grades' && CURRENT_COURSE_ID_FOR_JS && IS_CURRENT_USER_TEACHER_OF_THIS_COURSE) loadTeacherGradesSummary(CURRENT_COURSE_ID_FOR_JS);
        });
    });

    const activeTabOnInit = document.querySelector('.course-tab-navigation .tab-link.active');
    if (activeTabOnInit && CURRENT_COURSE_ID_FOR_JS) {
        const activeTabName = activeTabOnInit.dataset.tab;
        if (activeTabName === 'stream') loadAnnouncements(CURRENT_COURSE_ID_FOR_JS);
        else if (activeTabName === 'assignments' && assignmentSortSelect) loadAssignments(CURRENT_COURSE_ID_FOR_JS, assignmentSortSelect.value);
        else if (activeTabName === 'people') loadCourseParticipants(CURRENT_COURSE_ID_FOR_JS);
        else if (activeTabName === 'my-grades' && !IS_CURRENT_USER_TEACHER_OF_THIS_COURSE) loadMyGrades(CURRENT_COURSE_ID_FOR_JS);
        else if (activeTabName === 'grades' && IS_CURRENT_USER_TEACHER_OF_THIS_COURSE) loadTeacherGradesSummary(CURRENT_COURSE_ID_FOR_JS);
    } else if (CURRENT_COURSE_ID_FOR_JS) {
        loadAnnouncements(CURRENT_COURSE_ID_FOR_JS);
    }

    if(assignmentSortSelect && CURRENT_COURSE_ID_FOR_JS) {
        assignmentSortSelect.addEventListener('change', function() { loadAssignments(CURRENT_COURSE_ID_FOR_JS, this.value); });
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
                if (result.status === 'success') { this.reset(); if (CURRENT_COURSE_ID_FOR_JS) { loadAnnouncements(CURRENT_COURSE_ID_FOR_JS); } }
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

    if (showCreateAssignmentModalBtn && createAssignmentModal) {
        showCreateAssignmentModalBtn.addEventListener('click', () => {
            if (createAssignmentFormInternal) createAssignmentFormInternal.reset();
            if (CURRENT_COURSE_ID_FOR_JS) { fetchAndPopulateExistingSections(CURRENT_COURSE_ID_FOR_JS); }
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
                    if (CURRENT_COURSE_ID_FOR_JS && assignmentSortSelect) {
                        loadAssignments(CURRENT_COURSE_ID_FOR_JS, assignmentSortSelect.value);
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
                    if (CURRENT_COURSE_ID_FOR_JS && assignmentSortSelect) {
                        loadAssignments(CURRENT_COURSE_ID_FOR_JS, assignmentSortSelect.value);
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
        // const currentUserIdFromPHP = CURRENT_USER_ID_PHP; // Використовуємо передану змінну
        if (!isTeacherContext && isCurrentUserTheTeacher && user.user_id != CURRENT_USER_ID_PHP) {
            removeButtonHTML = `<button class="remove-student-btn" data-student-id="${user.user_id}" data-student-name="${htmlspecialchars(user.first_name) || ''} ${htmlspecialchars(user.last_name) || ''}"><i class="fas fa-user-minus"></i> Видалити</button>`;
        }
        itemDiv.innerHTML = `<img src="${avatarSrc}?t=${new Date().getTime()}" alt="Avatar" class="person-avatar"><div class="person-details"><span class="person-name">${htmlspecialchars(user.first_name) || ''} ${htmlspecialchars(user.last_name) || ''}</span><span class="person-username">@${htmlspecialchars(user.username)}</span></div>${isCurrentUserTheTeacher ? removeButtonHTML : ''}`;
        if (!isTeacherContext && isCurrentUserTheTeacher && user.user_id != CURRENT_USER_ID_PHP) {
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
        formData.append('course_id', CURRENT_COURSE_ID_FOR_JS);
        formData.append('student_id', studentId);
        try {
            const response = await fetch('../../src/course_participants_actions.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                alert(result.message);
                if (CURRENT_COURSE_ID_FOR_JS) { loadCourseParticipants(CURRENT_COURSE_ID_FOR_JS); }
            } else {
                alert(`Помилка: ${result.message || 'Не вдалося видалити студента.'}`);
            }
        } catch (error) {
            console.error('Помилка AJAX при видаленні студента:', error);
            alert('Сталася помилка на клієнті. Деталі в консолі.');
        }
    }
});