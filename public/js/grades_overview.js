document.addEventListener('DOMContentLoaded', function() {
    const gradesOverviewArea = document.getElementById('gradesOverviewArea');
    const loadingMessage = document.querySelector('.loading-grades-overview');
    const noGradesMessage = document.querySelector('.no-grades-overview-message');

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
            case 'pending_submission': default:
                if (dueDate && dueDate < now) {
                    statusText = 'Пропущено'; statusClass = 'missed';
                } else {
                    statusText = 'Не здано'; statusClass = 'pending';
                }
                break;
        }
        return { text: statusText, class: `submission-status-${statusClass}` }; 
    }


    function renderGradesOverview(coursesGrades) {
        if (loadingMessage) loadingMessage.style.display = 'none';
        gradesOverviewArea.innerHTML = ''; 

        if (!coursesGrades || coursesGrades.length === 0) {
            if (noGradesMessage) noGradesMessage.style.display = 'block';
            return;
        }
        if (noGradesMessage) noGradesMessage.style.display = 'none';

        coursesGrades.forEach(course => {
            const courseBlock = document.createElement('div');
            courseBlock.classList.add('course-grades-block');

            let teacherInfo = '';
            if(course.teacher_display_name && course.teacher_display_name.trim() !== '') {
                teacherInfo = `Викладач: ${course.teacher_display_name} (@${course.teacher_username})`;
            } else {
                teacherInfo = `Викладач: @${course.teacher_username}`;
            }


            let assignmentsTableHTML = `
                <div class="course-grades-block-header">
                    <h2><a href="course_view.php?course_id=${course.course_id}">${course.course_name}</a></h2>
                    <span class="teacher-info">${teacherInfo}</span>
                </div>`;
            
            if (course.assignments && course.assignments.length > 0) {
                assignmentsTableHTML += `
                    <table class="course-assignments-grades-table">
                        <thead>
                            <tr>
                                <th>Назва завдання</th>
                                <th>Термін здачі</th>
                                <th>Статус</th>
                                <th>Оцінка</th>
                                <th>Макс. бали</th>
                            </tr>
                        </thead>
                        <tbody>`;
                
                course.assignments.forEach(asm => {
                    const statusInfo = getStatusTextAndClass(asm.submission_status, asm.due_date);
                    const gradeDisplay = asm.grade !== null ? asm.grade : '–';
                    const dueDateDisplay = asm.due_date 
                        ? new Date(asm.due_date).toLocaleString('uk-UA', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) 
                        : '–';

                    assignmentsTableHTML += `
                        <tr>
                            <td data-label="Завдання"><a href="assignment_view.php?assignment_id=${asm.assignment_id}">${asm.assignment_title}</a></td>
                            <td data-label="Термін здачі">${dueDateDisplay}</td>
                            <td data-label="Статус"><span class="submission-status-badge ${statusInfo.class}">${statusInfo.text}</span></td>
                            <td data-label="Оцінка">${gradeDisplay}</td>
                            <td data-label="Макс. бали">${asm.max_points}</td>
                        </tr>`;
                });
                assignmentsTableHTML += `</tbody></table>`;
            } else {
                assignmentsTableHTML += '<p style="padding: 15px 20px; font-style: italic; color: #6c757d;">Для цього курсу ще немає завдань або оцінок.</p>';
            }
            courseBlock.innerHTML = assignmentsTableHTML;
            gradesOverviewArea.appendChild(courseBlock);
        });
    }

    async function loadUserGradesOverview() {
        if (loadingMessage) loadingMessage.style.display = 'block';
        if (noGradesMessage) noGradesMessage.style.display = 'none';
        gradesOverviewArea.innerHTML = '';

        try {
            const response = await fetch('../../src/get/get_student_grades_overview.php');
            if (!response.ok) {
                throw new Error(`HTTP помилка! Статус: ${response.status}`);
            }
            const result = await response.json();

            if (result.status === 'success') {
                renderGradesOverview(result.courses_grades);
            } else {
                if (loadingMessage) loadingMessage.style.display = 'none';
                gradesOverviewArea.innerHTML = `<p class="error-text">Не вдалося завантажити оцінки: ${result.message || 'Помилка сервера'}</p>`;
            }
        } catch (error) {
            console.error("Помилка завантаження загального огляду оцінок:", error);
            if (loadingMessage) loadingMessage.style.display = 'none';
            gradesOverviewArea.innerHTML = `<p class="error-text">Сталася помилка: ${error.message}. Спробуйте оновити сторінку.</p>`;
        }
    }

    loadUserGradesOverview();
});