<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../src/connect.php'; // Підключення до БД
require_once __DIR__ . '/templates/header.php'; // Підключення загального хедера (з сайдбаром)

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$course_id_get = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
$current_user_id = $_SESSION['user_id'];
$course_data = null;
$author_username = 'Невідомий';
$is_teacher = false; // Визначає, чи є поточний користувач АВТОРОМ курсу

if (!$course_id_get) {
    // $course_data залишиться null, і відобразиться блок "Курс не знайдено"
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

?>

<title><?php echo $page_title; ?> - Assignet</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="../css/course_view_styles.css">

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
                <h2>Учасники</h2>
                <p>Вміст вкладки "Учасники" буде тут.</p>
            </div>

            <?php if ($is_teacher): ?>
            <div id="tab-grades" class="tab-pane">
                <h2>Оцінки</h2>
                <p>Вміст вкладки "Оцінки" (для викладача) буде тут.</p>
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
                <p>Вміст вкладки "Мої оцінки" (для студента) буде тут.</p>
            </div>
            <?php endif; ?>
        </div> <?php else: ?>
        <div class="course-not-found">
            <h1>Помилка</h1>
            <p>Курс з ID <?php echo htmlspecialchars($_GET['course_id'] ?? 'невідомим'); ?> не знайдено або у вас немає до нього доступу.</p>
            <a href="home.php" class="button">Повернутися на головну</a>
        </div>
    <?php endif; ?>
</div> <?php if ($is_teacher): ?>
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
    const courseBannerElement = document.querySelector('.course-banner'); // Додано для керування банером

    tabLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const targetTab = this.getAttribute('data-tab');

            tabLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            tabPanes.forEach(pane => {
                if (pane.id === 'tab-' + targetTab) {
                    pane.classList.add('active');
                } else {
                    pane.classList.remove('active');
                }
            });
            if (breadcrumbCurrentTab) {
                breadcrumbCurrentTab.textContent = this.textContent;
            }

            // Керування видимістю банера
            if (courseBannerElement) {
                if (targetTab === 'stream') {
                    courseBannerElement.style.display = 'flex'; // Або 'block', як у тебе стилізовано
                } else {
                    courseBannerElement.style.display = 'none';
                }
            }

            // Завантаження контенту для активної вкладки
            if (targetTab === 'assignments' && currentCourseIdForJS) {
                loadAssignments(currentCourseIdForJS, assignmentSortSelect.value);
            } else if (targetTab === 'stream' && currentCourseIdForJS) {
                // Можливо, тут теж потрібно викликати loadAnnouncements, якщо вони не завантажуються при першому відкритті
                 loadAnnouncements(currentCourseIdForJS); // Додав, щоб оголошення завантажувались при переході на вкладку
            }
        });
    });
    
    // Початковий стан банера при завантаженні сторінки
    if (courseBannerElement) {
        const activeTabLinkInit = document.querySelector('.course-tab-navigation .tab-link.active');
        if (activeTabLinkInit && activeTabLinkInit.getAttribute('data-tab') === 'stream') {
            courseBannerElement.style.display = 'flex';
        } else {
            courseBannerElement.style.display = 'none';
        }
    }


    const createAnnouncementForm = document.getElementById('createAnnouncementForm');
    const announcementsArea = document.getElementById('announcementsArea');
    const currentCourseIdForJS = <?php echo $course_id_get ? json_encode((int)$course_id_get) : 'null'; ?>;
    
    let isCurrentUserTeacherOfThisCourse = <?php echo json_encode($is_teacher); ?>;

    async function loadAnnouncements(courseId) {
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
                        const baseAvatarPath = '../';
                        const defaultAvatar = baseAvatarPath + 'assets/default_avatar.png';
                        const authorAvatarSrc = ann.author_avatar_path ? baseAvatarPath + ann.author_avatar_path : defaultAvatar;
                        annElement.innerHTML = `
                            <div class="announcement-header">
                                <div class="announcement-author-info">
                                    <img src="${authorAvatarSrc}" alt="${ann.author_username || 'Аватар'}" class="announcement-author-avatar">
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

    // Завантажуємо оголошення, якщо вкладка "Стрічка" активна при завантаженні сторінки
    if (currentCourseIdForJS && document.querySelector('.tab-link[data-tab="stream"].active')) {
        loadAnnouncements(currentCourseIdForJS);
    }

    const courseSettingsForm = document.getElementById('courseSettingsForm');
    // const courseBannerElement = document.querySelector('.course-banner'); // Вже визначено вище
    const courseBannerTitleElement = document.querySelector('.course-banner-title');
    const breadcrumbCourseNameElement = document.querySelector('.breadcrumb-course-name');
    const courseJoinCodeElement = document.querySelector('.course-banner .course-join-code');
    const joinCodeFromDB = <?php echo isset($course_data['join_code']) ? json_encode($course_data['join_code']) : 'null'; ?>;

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
                   if(courseBannerElement) courseBannerElement.style.backgroundColor = updatedData.color; // Перевірка існування
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

    // --- JAVASCRIPT ДЛЯ ВКЛАДКИ "ЗАВДАННЯ" ---
    const assignmentsTabLink = document.querySelector('.tab-link[data-tab="assignments"]');
    const assignmentsListArea = document.getElementById('assignmentsListArea');
    const showCreateAssignmentModalBtn = document.getElementById('showCreateAssignmentModalBtn');
    const createAssignmentModal = document.getElementById('createAssignmentModal');
    const closeCreateAssignmentModalBtn = document.getElementById('closeCreateAssignmentModalBtn');
    const createAssignmentFormInternal = document.getElementById('createAssignmentFormInternal');
    const assignmentSortSelect = document.getElementById('assignmentSortSelect');

    async function loadAssignments(courseId, sortBy = 'due_date_asc') {
        if (!courseId || !assignmentsListArea) {
            console.warn("loadAssignments: courseId або assignmentsListArea не знайдено."); // Змінив на warn
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
                           } else if (dueDateObj && dueDateObj < now) { // Термін минув і не здано/не оцінено
                               submissionInfo = '<span class="submission-status missed"><i class="fas fa-times-circle"></i> Пропущено</span>';
                           } else { // Ще не здано, термін не минув
                               submissionInfo = '<span class="submission-status pending"><i class="fas fa-hourglass-half"></i> Не здано</span>';
                           }
                        }
                        
                        let shortDescription = asm.description || '';
                        if (shortDescription.length > 100) { // Зменшив довжину скороченого опису
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

    if (assignmentSortSelect) {
        assignmentSortSelect.addEventListener('change', function() {
            if (currentCourseIdForJS) {
                loadAssignments(currentCourseIdForJS, this.value);
            }
        });
    }
    
    // Обробники для модального вікна (тільки якщо користувач - викладач)
    // isCurrentUserTeacherOfThisCourse визначається після першого завантаження завдань,
    // тому краще перевіряти існування самих кнопок/модалки, які рендеряться PHP умовно
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
            if (parseInt(maxPoints) < 0) {
                alert('Кількість балів не може бути від\'ємною.');
                return;
            }
            if (parseInt(maxPoints) > 100) {
            alert('Максимальна кількість балів не може перевищувати 100.');
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
    
    // Ініціалізаційне завантаження для активної вкладки "Завдання"
    if (document.querySelector('.tab-link[data-tab="assignments"].active') && currentCourseIdForJS) {
        loadAssignments(currentCourseIdForJS, assignmentSortSelect.value);
    }
});
</script>