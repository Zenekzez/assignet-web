// File: public/js/assignments_list.js
document.addEventListener('DOMContentLoaded', function() {
    const assignmentsArea = document.getElementById('allStudentAssignmentsArea');
    const loadingMessage = document.querySelector('.loading-assignments-global');
    const noAssignmentsMessage = document.querySelector('.no-assignments-global-message');
    const filterButtons = document.querySelectorAll('.assignments-filters .filter-btn');
    let allFetchedAssignments = []; // Для зберігання всіх завантажених завдань

    // Функція для отримання тексту та класу статусу (можна винести в спільний файл)
    function getStatusTextAndClass(statusCode, dueDateStr) {
        let statusText = 'Не здано'; 
        let statusClass = 'pending'; // За замовчуванням
        const dueDate = dueDateStr ? new Date(dueDateStr) : null;
        const now = new Date();

        // Спочатку визначаємо статус на основі statusCode з БД
        switch (statusCode) {
            // 'submitted' та 'graded' не повинні сюди потрапляти згідно логіки бекенду
            case 'missed': 
                statusText = 'Пропущено'; 
                statusClass = 'missed'; 
                break;
            case 'pending_submission':
            default: // Якщо статус null або невідомий
                if (dueDate && dueDate < now) {
                    statusText = 'Пропущено'; 
                    statusClass = 'missed';
                } else {
                    statusText = 'Не здано'; 
                    statusClass = 'pending';
                }
                break;
        }
        return { text: statusText, class: `submission-status-${statusClass}` };
    }
    
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, m => map[m]);
    }

    function renderAssignments(assignmentsToRender) {
        assignmentsArea.innerHTML = ''; // Очищуємо попередні картки

        if (!assignmentsToRender || assignmentsToRender.length === 0) {
            if (noAssignmentsMessage) noAssignmentsMessage.style.display = 'block';
            return;
        }
        if (noAssignmentsMessage) noAssignmentsMessage.style.display = 'none';

        assignmentsToRender.forEach(asm => {
            const asmElement = document.createElement('div');
            asmElement.classList.add('assignment-item-card-compact'); // Використовуємо стиль з course_view
            asmElement.dataset.assignmentId = asm.assignment_id;
            asmElement.dataset.category = asm.category_slug; // Для фільтрації, якщо потрібно

            let deadlineLabel = '';
            const dueDateObj = asm.due_date ? new Date(asm.due_date) : null;
            const now = new Date();
            
            // Визначення "терміновості" для мітки
            let isUrgent = false;
            if (dueDateObj && dueDateObj >= now) {
                const urgentThreshold = new Date();
                urgentThreshold.setDate(now.getDate() + 3); // 3 дні для термінових
                if (dueDateObj <= urgentThreshold) {
                    isUrgent = true;
                }
            }

            if (asm.category_slug === 'overdue') {
                 deadlineLabel = '<span class="deadline-indicator-compact past"><i class="fas fa-exclamation-circle"></i> Прострочено</span>';
            } else if (isUrgent) { // Використовуємо isUrgent, оскільки asm.category_slug для "urgent" вже враховано вище
                 deadlineLabel = '<span class="deadline-indicator-compact soon"><i class="fas fa-bell"></i> Терміново</span>';
            }


            const statusInfo = getStatusTextAndClass(asm.submission_status, asm.due_date);

            // Форматування дат
            const dueDateFormatted = dueDateObj ? dueDateObj.toLocaleString('uk-UA', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'Не вказано';
            
            asmElement.innerHTML = `
                <div class="card-content-compact">
                    <div class="card-title-line-compact">
                        <h4 class="assignment-title-compact">
                            <a href="assignment_view.php?assignment_id=${asm.assignment_id}">${htmlspecialchars(asm.assignment_title)}</a>
                            ${deadlineLabel}
                        </h4>
                        </div>
                    <p class="assignment-course-name">
                        Курс: <a href="course_view.php?course_id=${asm.course_id}">${htmlspecialchars(asm.course_name)}</a>
                    </p>
                    <div class="card-meta-line-compact">
                        ${asm.due_date ? `<span>Здати до: <strong>${dueDateFormatted}</strong></span>` : '<span>Без терміну</span>'}
                        <span class="meta-divider-compact">|</span>
                        <span>Бали: ${asm.max_points}</span>
                        <span class="meta-divider-compact">|</span>
                        <span class="assignment-status-badge ${statusInfo.class}">${statusInfo.text}</span>
                    </div>
                     <div class="card-teacher-actions-line-compact" style="margin-top: 8px;">
                        <a href="assignment_view.php?assignment_id=${asm.assignment_id}" class="button-link-compact view-submissions-link-compact" style="font-size: 0.85em; padding: 6px 10px;">
                            <i class="fas fa-eye"></i> Переглянути / Здати
                        </a>
                    </div>
                </div>
            `;
            assignmentsArea.appendChild(asmElement);
        });
    }

    async function loadAllStudentAssignments() {
        if (loadingMessage) loadingMessage.style.display = 'block';
        if (noAssignmentsMessage) noAssignmentsMessage.style.display = 'none';
        assignmentsArea.innerHTML = ''; // Очищаємо, поки завантажується

        try {
            const response = await fetch('../../src/get/get_student_all_assignments.php');
            if (!response.ok) {
                throw new Error(`HTTP помилка! Статус: ${response.status}`);
            }
            const result = await response.json();

            if (loadingMessage) loadingMessage.style.display = 'none';

            if (result.status === 'success' && result.assignments) {
                allFetchedAssignments = result.assignments;
                // За замовчуванням показуємо всі невиконані
                const currentFilter = document.querySelector('.assignments-filters .filter-btn.active').dataset.filter || 'all';
                filterAndRenderAssignments(currentFilter);
            } else {
                assignmentsArea.innerHTML = `<p class="error-text">Не вдалося завантажити завдання: ${result.message || 'Помилка сервера'}</p>`;
            }
        } catch (error) {
            console.error("Помилка завантаження всіх завдань студента:", error);
            if (loadingMessage) loadingMessage.style.display = 'none';
            assignmentsArea.innerHTML = `<p class="error-text">Сталася помилка: ${error.message}. Спробуйте оновити сторінку.</p>`;
        }
    }

    function filterAndRenderAssignments(filterType) {
        let filtered = [];
        if (filterType === 'all') {
            filtered = allFetchedAssignments;
        } else {
            filtered = allFetchedAssignments.filter(asm => asm.category_slug === filterType);
        }
        renderAssignments(filtered);
    }

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            const filterType = this.dataset.filter;
            filterAndRenderAssignments(filterType);
        });
    });

    // Початкове завантаження завдань
    loadAllStudentAssignments();
});