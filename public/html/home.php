<?php
    // File: public/html/home.php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    // Ця змінна буде використана header.php для відображення кнопки "Додати курс"
    $show_add_course_button_on_home = true;
    require_once __DIR__ . '/templates/header.php';

    // Отримуємо ім'я користувача для JS, специфічне для логіки карток курсу на домашній сторінці
    $current_username_for_js_home = htmlspecialchars($_SESSION['username'] ?? 'Автор', ENT_QUOTES, 'UTF-8');
?>
<title>Головна - Assignet</title>
<style>
    /* Стилі, специфічні для контенту home.php (сітка курсів, плейсхолдери, правий сайдбар, модальні вікна) */
    .main-content-home {
        flex-grow: 1;
        padding: 0; 
        box-sizing: border-box;
        overflow-y: auto;
        height: 100%;
        position: relative;
        z-index: 1;
        display: flex; 
    }

    .courses-area {
        flex-grow: 1;
        padding: 25px; 
        overflow-y: auto;
    }

    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 25px;
    }

    .course-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: box-shadow 0.2s ease-in-out;
        text-decoration: none;
        color: #333;
    }
    .course-card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    }
    .card-header {
        padding: 20px 16px 16px 16px;
        color: #fff;
        min-height: 80px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .card-header h4 {
        margin: 0 0 5px 0;
        font-size: 1.4em;
        font-weight: 500;
        line-height: 1.2;
    }
    .card-header .course-author {
        font-size: 0.85em;
        font-weight: 300;
        opacity: 0.9;
    }
    .card-body {
        padding: 16px;
        flex-grow: 1;
        background-color: #fff;
    }
    .card-body h5 { /* Стиль для заголовка "Опис курсу" якщо він буде відновлений */
        margin-top: 0;
        margin-bottom: 8px;
        font-size: 1em;
        color: #5f6368;
        font-weight: 500;
    }
    .card-body .description-text {
        font-size: 0.9em;
        color: #3c4043;
        line-height: 1.5;
        margin-bottom: 0;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        min-height: calc(1.5em * 3); 
    }
    .course-color-default { background-color: #78909c; } 

    .courses-placeholder {
        padding: 20px; text-align: center; color: #777; font-style: italic;
        border: 2px dashed #ccc; min-height: 200px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 8px;
    }
    .courses-placeholder.hidden {
        display: none;
    }

    .right-sidebar {
        width: 280px; 
        background-color: #fff;
        padding: 20px; 
        box-sizing: border-box;
        border-left: 1px solid #e0e0e0;
        overflow-y: auto;
        height: 100%; 
        z-index: 1000;
        transition: width 0.3s ease-in-out, opacity 0.3s ease-in-out, padding 0.3s ease-in-out;
    }
    .right-sidebar.hidden { /* Цей клас тепер буде керувати видимістю */
        width: 0; 
        padding: 0;
        border-left: none;
        opacity: 0; 
        overflow: hidden; 
    }
    /* Видалені стилі .right-sidebar h3 та .widget */

</style>

<main class="page-content-wrapper">
    <div class="main-content-home" id="mainContentHome">
        <div class="courses-area">
            <div class="courses-grid" id="coursesGridContainer">
                </div>
            <div class="courses-placeholder" id="coursesPlaceholder">
                Тут будуть відображатися ваші курси
            </div>
        </div>

        <aside class="right-sidebar hidden" id="rightSidebar"> 
            </aside>
    </div>
</main>

<div class="modal-overlay" id="createCourseModal">
    <div class="modal-content">
        <button class="modal-close-btn" id="modalCloseBtnCreate">&times;</button>
        <h2>Створити новий курс</h2>
        <form id="createCourseFormActual">
            <div class="input-container"><label for="modalCourseName" class="iftaLabel">Назва курсу</label><input type="text" id="modalCourseName" name="course_name" class="inputField" required></div>
            <div class="input-container"><label for="modalCourseDescription" class="iftaLabel">Опис курсу (необов'язково)</label><textarea id="modalCourseDescription" name="description" class="inputField"></textarea></div>
            <button type="submit" class="submit-button">Створити</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="joinCourseModal">
    <div class="modal-content">
        <button class="modal-close-btn" id="modalCloseBtnJoin">&times;</button>
        <h2>Приєднатися до курсу</h2>
        <form id="joinCourseFormActual">
            <div class="input-container">
                <label for="modalCourseCode" class="iftaLabel">Код курсу</label>
                <input type="text" id="modalCourseCode" name="course_code" class="inputField" required placeholder="Введіть код курсу">
            </div>
            <button type="submit" class="submit-button">Приєднатися</button>
        </form>
    </div>
</div>

</div> 
<script>
const CURRENT_USER_USERNAME_HOME = "<?php echo $current_username_for_js_home; ?>";

document.addEventListener('DOMContentLoaded', function () {
    const coursesGridContainer = document.getElementById('coursesGridContainer');
    const coursesPlaceholder = document.getElementById('coursesPlaceholder');

    const createCourseOption = document.getElementById('createCourseOption');
    const joinCourseOption = document.getElementById('joinCourseOption');
    const createCourseModal = document.getElementById('createCourseModal');
    const modalCloseBtnCreate = document.getElementById('modalCloseBtnCreate');
    const createCourseFormActual = document.getElementById('createCourseFormActual');
    
    const joinCourseModal = document.getElementById('joinCourseModal');
    const modalCloseBtnJoin = document.getElementById('modalCloseBtnJoin');
    const joinCourseFormActual = document.getElementById('joinCourseFormActual');

    const rightSidebar = document.getElementById('rightSidebar');
    // const mainContentHome = document.getElementById('mainContentHome'); // Не використовується для логіки сайдбару без віджетів
    // const coursesArea = document.querySelector('.courses-area'); // Не використовується для логіки сайдбару без віджетів


    function updateCoursesPlaceholderVisibility() {
        if (coursesGridContainer && coursesPlaceholder) { 
            coursesPlaceholder.classList.toggle('hidden', coursesGridContainer.children.length > 0);
        }
    }

    function addCourseCardToDashboard(course) {
        if (!coursesGridContainer) return;
        
        const cardLink = document.createElement('a');
        cardLink.href = `course_view.php?course_id=${course.id}`;
        cardLink.classList.add('course-card');
        cardLink.setAttribute('data-course-id', course.id);

        const header = document.createElement('div');
        header.classList.add('card-header');
        if (course.color_hex) { 
            header.style.backgroundColor = course.color_hex;
        } else {
            header.classList.add(course.color_class || 'course-color-default'); 
        }
            
        const title = document.createElement('h4');
        title.textContent = course.name;
        
        const author = document.createElement('span');
        author.classList.add('course-author');
        author.textContent = course.author_username || CURRENT_USER_USERNAME_HOME; 
        
        header.appendChild(title);
        header.appendChild(author);

        const body = document.createElement('div');
        body.classList.add('card-body');
                
        const descriptionText = document.createElement('p');
        descriptionText.classList.add('description-text');
        descriptionText.textContent = course.description || 'Немає опису.';
        
        body.appendChild(descriptionText);
        
        cardLink.appendChild(header);
        cardLink.appendChild(body);
        
        coursesGridContainer.appendChild(cardLink);
    }
    
    async function loadUserCourses() {
        try {
            const response = await fetch('../../src/get_user_courses.php', { 
                method: 'GET',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'}
            });

            if (!response.ok) {
                console.error(`Помилка завантаження курсів: ${response.status} ${response.statusText}`);
                const errorText = await response.text(); console.error("Тіло помилки:", errorText);
                throw new Error(`Помилка завантаження курсів: ${response.statusText}`);
            }
            const data = await response.json();

            if (data.status === 'success') { 
                if(coursesGridContainer) coursesGridContainer.innerHTML = '';

                if (data.teaching_courses && Array.isArray(data.teaching_courses)) { 
                    data.teaching_courses.forEach(course => {
                        addCourseCardToDashboard({
                            id: course.id, name: course.name, description: course.description,
                            color_hex: course.color_hex,
                            author_username: course.author_username || CURRENT_USER_USERNAME_HOME
                        });
                    });
                }
                
                if (data.enrolled_courses && Array.isArray(data.enrolled_courses)) { 
                    data.enrolled_courses.forEach(course => {
                        let existingCard = coursesGridContainer.querySelector(`.course-card[data-course-id="${course.id}"]`);
                        if (!existingCard) { 
                             addCourseCardToDashboard({
                                id: course.id, name: course.name, description: course.description,
                                color_hex: course.color_hex, 
                                author_username: course.author_username 
                            });
                        }
                    });
                }
            } else {
                console.error("Помилка отримання курсів з сервера:", data.message);
            }
        } catch (error) {
            console.error("Критична помилка при завантаженні курсів:", error);
        }
        updateCoursesPlaceholderVisibility();
    }

    if (createCourseOption && createCourseModal) {
        createCourseOption.addEventListener('click', function(event) {
            event.preventDefault();
            createCourseModal.style.display = 'flex';
            const addCourseDropdown = document.getElementById('addCourseDropdown'); 
            if(addCourseDropdown) addCourseDropdown.style.display = 'none';
        });
    }
    if (modalCloseBtnCreate && createCourseModal) {
        modalCloseBtnCreate.addEventListener('click', function() { createCourseModal.style.display = 'none'; });
    }
    if (createCourseModal) {
        createCourseModal.addEventListener('click', function(event) {
            if (event.target === createCourseModal) { createCourseModal.style.display = 'none';}
        });
    }
    if (joinCourseOption && joinCourseModal) {
        joinCourseOption.addEventListener('click', function(event) {
            event.preventDefault();
            joinCourseModal.style.display = 'flex';
            const addCourseDropdown = document.getElementById('addCourseDropdown');
            if(addCourseDropdown) addCourseDropdown.style.display = 'none'; 
        });
    }
    if (modalCloseBtnJoin && joinCourseModal) {
        modalCloseBtnJoin.addEventListener('click', function() { joinCourseModal.style.display = 'none'; });
    }
    if (joinCourseModal) {
        joinCourseModal.addEventListener('click', function(event) {
            if (event.target === joinCourseModal) { joinCourseModal.style.display = 'none'; }
        });
    }

    if (createCourseFormActual && createCourseModal) {
        createCourseFormActual.addEventListener('submit', async function(event) {
            event.preventDefault();
            const courseNameInput = document.getElementById('modalCourseName');
            const courseDescriptionInput = document.getElementById('modalCourseDescription');
            const courseName = courseNameInput.value.trim();
            const courseDescription = courseDescriptionInput.value.trim();

            if (!courseName) { alert('Назва курсу не може бути порожньою.'); courseNameInput.focus(); return; }
            
            const formData = new FormData();
            formData.append('course_name', courseName);
            formData.append('description', courseDescription);

            try {
                const response = await fetch('../../src/create_course_process.php', { method: 'POST', body: formData }); 
                if (!response.ok) {
                    const errorData = await response.json().catch(() => null);
                    throw new Error(errorData?.message || `Помилка сервера: ${response.statusText}`);
                }
                const data = await response.json();
                if (data.status === 'success' && data.course) { 
                    addCourseCardToDashboard({ 
                        id: data.course.id, name: data.course.name, description: data.course.description,
                        color_hex: data.course.color_hex, 
                        author_username: data.course.author_username || CURRENT_USER_USERNAME_HOME
                    });
                    updateCoursesPlaceholderVisibility();
                    createCourseModal.style.display = 'none';
                    this.reset();
                } else {
                    alert(data.message || 'Не вдалося створити курс.');
                }
            } catch (error) {
                console.error('Помилка при створенні курсу:', error); alert(`Сталася помилка: ${error.message}`);
            }
        });
    }

    if (joinCourseFormActual && joinCourseModal) {
        joinCourseFormActual.addEventListener('submit', async function(event) {
            event.preventDefault();
            const courseCodeInput = document.getElementById('modalCourseCode');
            const courseCode = courseCodeInput.value.trim();
            if (!courseCode) { alert('Будь ласка, введіть код курсу.'); courseCodeInput.focus(); return; }

            const formData = new FormData();
            formData.append('course_code', courseCode);

            try {
                const response = await fetch('../../src/join_course_process.php', { method: 'POST', body: formData }); 
                if (!response.ok) {
                    const errorData = await response.json().catch(() => null);
                    throw new Error(errorData?.message || `Помилка сервера: ${response.statusText}`);
                }
                const data = await response.json();
                if (data.status === 'success' && data.course) { 
                    let existingCard = coursesGridContainer.querySelector(`.course-card[data-course-id="${data.course.id}"]`);
                    if (!existingCard) {
                         addCourseCardToDashboard({ 
                            id: data.course.id,
                            name: data.course.name,
                            description: data.course.description,
                            color_hex: data.course.color_hex, 
                            author_username: data.course.author_username
                         }); 
                    }
                    updateCoursesPlaceholderVisibility();
                    joinCourseModal.style.display = 'none';
                    this.reset(); 
                    alert(data.message); 
                } else {
                    alert(data.message || 'Не вдалося приєднатися до курсу.');
                }
            } catch (error) {
                console.error('Помилка при приєднанні до курсу:', error); alert(`Сталася помилка: ${error.message}`);
            }
        });
    }
    
    // Логіка для адаптивності правого сайдбару
    const screenBreakpointRightSidebar = 1199.98; 
    function applyRightSidebarResponsive() {
        if (!rightSidebar) return; // Якщо сайдбар видалено з HTML, нічого не робимо
        const screenWidth = window.innerWidth;
        if (screenWidth <= screenBreakpointRightSidebar) {
            rightSidebar.classList.add('hidden');
        } else {
            // Якщо сайдбар має клас 'hidden' (як зараз за замовчуванням), 
            // то він залишиться прихованим на великих екранах.
            // Якщо ви хочете, щоб він був видимий на десктопі (і приховувався тільки на мобільних),
            // то приберіть клас 'hidden' з тегу <aside> в HTML
            // і розкоментуйте рядок нижче:
            // rightSidebar.classList.remove('hidden');
        }
    }
    if (rightSidebar) { // Виконуємо тільки якщо сайдбар існує в DOM
        window.addEventListener('resize', applyRightSidebarResponsive);
        applyRightSidebarResponsive(); // Застосувати при завантаженні
    }
    

    loadUserCourses(); 
});
</script>
</body>
</html>