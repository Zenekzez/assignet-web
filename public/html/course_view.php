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
$is_teacher = false;

if (!$course_id_get) {
    // header("Location: home.php?error=invalid_course_id");
    // exit();
} else {
    $stmt_course = $conn->prepare("SELECT course_name, author_id, color, join_code, description, join_code_visible FROM courses WHERE course_id = ?");
    if ($stmt_course) {
        $stmt_course->bind_param("i", $course_id_get);
        $stmt_course->execute();
        $result_course = $stmt_course->get_result();
        if ($course_data_row = $result_course->fetch_assoc()) {
            $course_data = $course_data_row;

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

            if ($current_user_id == $course_data['author_id']) {
                $is_teacher = true;
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
$join_code_visible_db = $course_data['join_code_visible'] ?? true; // За замовчуванням true, якщо в БД немає

?>

<title><?php echo $page_title; ?> - Assignet</title>
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
            if ($is_teacher || $join_code_visible_db) { // Перевірка видимості коду
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
                        <input type="hidden" name="course_id" value="<?php echo $course_id_get; ?>">
                        <div>
                            <label for="announcement_content">Нове оголошення:</label>
                            <textarea id="announcement_content" name="announcement_content" rows="4" required placeholder="Напишіть щось для курсу..."></textarea>
                        </div>
                        <button type="submit">Опублікувати</button>
                    </form>
                <?php endif; ?>
                <div id="announcementsArea">
                    <p>Завантаження оголошень...</p>
                </div>
            </div>

            <div id="tab-assignments" class="tab-pane">
                <h2>Завдання</h2>
                <p>Вміст вкладки "Завдання" буде тут.</p>
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
                     <input type="hidden" name="course_id_settings" value="<?php echo $course_id_get; ?>">
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
        </div>

    <?php else: ?>
        <div class="course-not-found">
            <h1>Помилка</h1>
            <p>Курс з ID <?php echo htmlspecialchars($_GET['course_id'] ?? 'невідомим'); ?> не знайдено або у вас немає до нього доступу.</p>
            <a href="home.php" class="button">Повернутися на головну</a>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.course-tab-navigation .tab-link');
    const tabPanes = document.querySelectorAll('.course-tab-content-area .tab-pane'); // Більш точний селектор
    const breadcrumbCurrentTab = document.getElementById('current-tab-breadcrumb');

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
        });
    });

    // --- ОГОЛОШЕННЯ ---
    const createAnnouncementForm = document.getElementById('createAnnouncementForm');
    const announcementsArea = document.getElementById('announcementsArea');
    // Передаємо ID курсу з PHP в JavaScript безпечно
    const currentCourseIdForJS = <?php echo $course_id_get ? json_encode((int)$course_id_get) : 'null'; ?>;
    const isTeacherForJS = <?php echo $is_teacher ? 'true' : 'false'; ?>;


    async function loadAnnouncements(courseId) {
        if (!courseId || !announcementsArea) return;
        announcementsArea.innerHTML = '<p>Завантаження оголошень...</p>';
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
                        // `ann.content` вже екрановано на сервері
                        annElement.innerHTML = `
                            <div class="announcement-header">
                                <span class="announcement-author">${ann.author_username || 'Викладач'}</span>
                                <span class="announcement-date">${new Date(ann.created_at).toLocaleString()}</span>
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
                    // alert('Оголошення опубліковано!'); // Можна прибрати, якщо оновлення миттєве
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

    if (currentCourseIdForJS) {
        loadAnnouncements(currentCourseIdForJS);
    }

    // --- НАЛАШТУВАННЯ КУРСУ ---
    const courseSettingsForm = document.getElementById('courseSettingsForm');
    const courseBannerElement = document.querySelector('.course-banner');
    const courseBannerTitleElement = document.querySelector('.course-banner-title');
    const breadcrumbCourseNameElement = document.querySelector('.breadcrumb-course-name');
    const courseJoinCodeElement = document.querySelector('.course-banner .course-join-code');
    const joinCodeFromDB = <?php echo isset($course_data['join_code']) ? json_encode($course_data['join_code']) : 'null'; ?>;


    if(courseSettingsForm && courseBannerElement && courseBannerTitleElement && breadcrumbCourseNameElement) {
        courseSettingsForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'update_course_settings');
            
            // Додаємо значення чекбоксу, якщо він не відмічений (FormData не надсилає невідмічені чекбокси)
            if (!formData.has('join_code_visible')) {
                formData.append('join_code_visible', '0');
            }

            const courseName = formData.get('course_name').trim();
            const color = formData.get('color').trim();
            if (!courseName) {
                alert('Назва курсу не може бути порожньою.');
                return;
            }
            if (!/^#[0-9A-Fa-f]{6}$/i.test(color)) { // Додав i для нечутливості до регістру
                alert('Некоректний формат кольору. Введіть HEX, наприклад, #RRGGBB.');
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

                if (result.status === 'success' && result.updated_data) {
                   alert(result.message || 'Налаштування збережено!');
                   const updatedData = result.updated_data;

                   courseBannerTitleElement.textContent = updatedData.course_name;
                   courseBannerElement.style.backgroundColor = updatedData.color;
                   breadcrumbCourseNameElement.textContent = updatedData.course_name;
                   document.title = updatedData.course_name + ' - Assignet';

                   // Оновлення видимості коду курсу
                   if (courseJoinCodeElement) {
                       if (updatedData.join_code_visible && joinCodeFromDB) {
                           courseJoinCodeElement.innerHTML = `Код курсу: <strong>${joinCodeFromDB}</strong>`; // Використовуємо код з БД
                           courseJoinCodeElement.style.display = 'inline-block';
                       } else {
                           courseJoinCodeElement.style.display = 'none';
                       }
                   } else if (updatedData.join_code_visible && joinCodeFromDB && courseBannerElement) { 
                        // Якщо елемента не було, а тепер код видимий - створюємо його
                        const newJoinCodeP = document.createElement('p');
                        newJoinCodeP.classList.add('course-join-code');
                        newJoinCodeP.innerHTML = `Код курсу: <strong>${joinCodeFromDB}</strong>`;
                        // Вставляємо після h1 або в кінець банера
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
});
</script>