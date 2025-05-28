<?php
    // Переконайтесь, що сесія запущена (зазвичай це робиться на початку файлу)
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    // Отримуємо ім'я користувача для використання в JS
    // Це важливо для відображення автора курсу, якщо він не приходить з відповіді сервера
    $current_username_for_js = htmlspecialchars($_SESSION['username'] ?? 'Автор', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Головна - Assignet</title>
    <style>
        body, html {
            margin: 0; padding: 0; font-family: Tahoma, Verdana, sans-serif;
            background-color: #f0f2f5; height: 100%; overflow-x: hidden;
        }
        .app-body-content {
            padding-top: 60px; height: 100vh; box-sizing: border-box;
            display: flex; position: relative;
        }
        .app-header {
            position: fixed; top: 0; left: 0; width: 100%; height: 60px;
            background-color: #fff; border-bottom: 1px solid #dadce0;
            display: flex; align-items: center; padding: 0 15px;
            box-sizing: border-box; z-index: 1005;
        }
        .header-left { display: flex; align-items: center; }
        .header-menu-toggle {
            background: none; border: none; font-size: 24px; cursor: pointer;
            padding: 8px; color: #5f6368; display: flex;
            align-items: center; justify-content: center; margin-right: 10px;
        }
        .header-menu-toggle:hover {
            background-color: rgba(60,64,67,0.08); border-radius: 50%;
        }
        .header-widgets-toggle-btn { /* Кнопка віджетів тепер буде прихована */
            display: none !important;
        }
        .header-title { font-size: 22px; color: #5f6368; }
        .header-right { margin-left: auto; display: flex; align-items: center; }
        .header-action-btn {
            background-color: #4285f4; color: white; border: none;
            padding: 8px 16px; font-size: 14px; font-weight: 500;
            border-radius: 4px; cursor: pointer; display: flex;
            align-items: center; transition: background-color 0.2s ease;
            margin-right: 10px;
        }
        .header-action-btn:hover { background-color: #3367d6; }

        .left-sidebar {
            width: 250px; background-color: #fff; padding: 20px; box-sizing: border-box;
            border-right: 1px solid #e0e0e0; overflow-y: auto;
            position: fixed; top: 60px; left: 0; height: calc(100vh - 60px);
            z-index: 1001;
            transform: translateX(0); transition: transform 0.3s ease-in-out;
        }
        .left-sidebar.hidden { transform: translateX(-100%); }
        .left-sidebar ul { list-style-type: none; padding: 0; margin: 0; }
        .left-sidebar > ul > li > a, .left-sidebar .collapsible-header {
            display: block; padding: 10px 0; text-decoration: none;
            color: #007bff; font-weight: 500; cursor: pointer;
        }
        .left-sidebar > ul > li > a:hover, .left-sidebar .collapsible-header:hover { text-decoration: underline; }
        .left-sidebar .collapsible-header::after { content: ' ▼'; font-size: 0.8em; float: right; }
        .left-sidebar .collapsible-header.active::after { content: ' ▲'; }
        .left-sidebar .sub-menu {
            list-style-type: none; padding-left: 20px; margin-top: 5px;
            max-height: 0; overflow: hidden;
        }
        .left-sidebar .sub-menu.animate-max-height {
            transition: max-height 0.3s ease-out;
        }

        .main-content {
            flex-grow: 1; padding: 25px; box-sizing: border-box; overflow-y: auto;
            margin-left: 250px;
            margin-right: 280px;
            transition: margin-left 0.3s ease-in-out, margin-right 0.3s ease-in-out;
            height: 100%; position: relative; z-index: 1;
        }

        /* Стилі для карток курсів */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); /* Адаптивна сітка */
            gap: 25px; /* Відстань між картками */
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
        .card-body h5 {
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

        /* Приклади кольорів для заголовків карток */
        .course-color-orange { background-color: #f0ad4e; }
        .course-color-green { background-color: #5cb85c; }
        .course-color-lblue { background-color: #5bc0de; }
        .course-color-red { background-color: #d9534f; }
        .course-color-purple { background-color: #ba68c8; }
        .course-color-indigo { background-color: #7986cb; }
        .course-color-teal { background-color: #4db6ac; }
        .course-color-brown { background-color: #a1887f; }
        .course-color-deeporange { background-color: #ff8a65; }
        .course-color-deeppurple { background-color: #9575cd; }
        .course-color-default { background-color: #78909c; }

        .courses-placeholder {
            padding: 20px; text-align: center; color: #777; font-style: italic;
            border: 2px dashed #ccc; min-height: 200px;
            display: flex; align-items: center; justify-content: center;
        }
        .courses-placeholder.hidden {
            display: none;
        }

        .right-sidebar {
            width: 280px; background-color: #fff; padding: 20px; box-sizing: border-box;
            border-left: 1px solid #e0e0e0; overflow-y: auto;
            position: fixed; top: 60px; right: 0; height: calc(100vh - 60px);
            z-index: 1000;
            transform: translateX(0);
            transition: transform 0.3s ease-in-out;
        }
        .right-sidebar.hidden { transform: translateX(100%); }
        .right-sidebar h3 { margin-top: 0; color: #555; font-size: 1.2em; margin-bottom: 20px; }
        .widget { margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #e7e7e7; border-radius: 5px; }
        .widget h4 { margin-top: 0; margin-bottom: 10px; font-size: 1em; }
        .widget p { font-size: 0.9em; color: #666; }

        .dropdown-menu {
            display: none; position: absolute; top: 50px; right: 0;
            background-color: white; border: 1px solid #dadce0;
            border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1006; min-width: 200px;
        }
        .dropdown-menu a { display: block; padding: 12px 16px; text-decoration: none; color: #3c4043; font-size: 14px; }
        .dropdown-menu a:hover { background-color: #f1f3f4; }

        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.6); z-index: 1010;
            justify-content: center; align-items: center;
        }
        .modal-content {
            background-color: white; padding: 25px 30px; border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3); width: 100%; max-width: 500px; position: relative;
        }
        .modal-close-btn {
            position: absolute; top: 15px; right: 15px; background: none; border: none;
            font-size: 24px; cursor: pointer; color: #757575;
        }
        .modal-content h2 { margin-top: 0; margin-bottom: 20px; font-size: 20px; color: #333; }
        .modal-content .input-container { position: relative; margin: 10px 0 20px 0; }
        .modal-content .input-container .iftaLabel {
            position: absolute; top: 8px; left: 7px; font-size: 12px;
            color: var(--black, black); pointer-events: none;
        }
        .modal-content .input-container .inputField {
            border: 2px solid var(--black, black); border-radius: 5px; width: calc(100% - 14px);
            padding: 25px 5px 5px 5px; font-size: 16px;
        }
         .modal-content .input-container textarea.inputField { min-height: 80px; resize: vertical; }
         .modal-content .input-container input[type="text"].inputField::placeholder { /* Стиль для плейсхолдера коду курсу */
            font-size: 15px; /* Можете налаштувати розмір */
         }
        .modal-content .submit-button {
            background-color: var(--blue, #007bff); color: var(--white, white);
            border-radius: 8px; border: none; font-size: 16px;
            padding: 12px 25px; cursor: pointer; display: block;
            margin: 20px auto 0 auto; transition: background-color 0.2s ease;
        }
    </style>
</head>
<body>
    <header class="app-header">
        <div class="header-left">
            <button class="header-menu-toggle" id="headerMenuToggle" aria-label="Меню">&#9776;</button>
            <span class="header-title">Assignet</span>
        </div>
        <div class="header-right">
            <div style="position: relative;">
                <button class="header-action-btn" id="headerAddCourseToggle"> Додати курс </button>
                <div class="dropdown-menu" id="addCourseDropdown">
                    <a href="#" id="joinCourseOption">Приєднатися до курсу</a>
                    <a href="#" id="createCourseOption">Створити курс</a>
                </div>
            </div>
            <button class="header-widgets-toggle-btn" id="headerWidgetsToggle" aria-label="Віджети">&#x25A6;</button>
        </div>
    </header>

    <div class="app-body-content">
        <aside class="left-sidebar" id="leftSidebar">
            <nav>
                 <ul>
                    <li><a href="#">Головна</a></li>
                    <li><a href="settings.php">Налаштування</a></li>
                    <li><a href="#">Завдання</a></li>
                    <li><a href="#">Оцінки</a></li>
                    <li>
                        <div class="collapsible-header active">Як викладач</div>
                        <ul class="sub-menu default-visible" id="teacherCoursesList">
                            <li><a href="#" id="createCourseFromSidebar">+ Створити</a></li>
                        </ul>
                    </li>
                    <li>
                        <div class="collapsible-header active">Як студент</div>
                        <ul class="sub-menu default-visible" id="studentCoursesList">
                            </ul>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content" id="mainContent">
            <div class="courses-grid" id="coursesGridContainer">
                </div>
            <div class="courses-placeholder" id="coursesPlaceholder" style="display: block;">
                Тут будуть відображатися ваші курси
            </div>
        </main>

        <aside class="right-sidebar" id="rightSidebar">
            <h3>Віджети</h3>
            <div class="widget"><h4>Віджет 1</h4><p>Контент...</p></div>
            <div class="widget"><h4>Віджет 2</h4><p>Контент...</p></div>
        </aside>
    </div>

    <div class="modal-overlay" id="createCourseModal">
        <div class="modal-content">
            <button class="modal-close-btn" id="modalCloseBtn">&times;</button>
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
            <button class="modal-close-btn" id="joinModalCloseBtn">&times;</button>
            <h2>Приєднатися до курсу</h2>
            <form id="joinCourseFormActual">
                <div class="input-container">
                    <label for="modalCourseCode" class="iftaLabel">Код курсу</label>
                    <input type="text" id="modalCourseCode" name="course_code" class="inputField" required
                           placeholder="Введіть код курсу">
                </div>
                <button type="submit" class="submit-button">Приєднатися</button>
            </form>
        </div>
    </div>

<script>
const CURRENT_USER_USERNAME = "<?php echo $current_username_for_js; ?>";

document.addEventListener('DOMContentLoaded', function () {
    const collapsibles = document.querySelectorAll('.left-sidebar .collapsible-header');
    collapsibles.forEach(function(collapsible) {
        const content = collapsible.nextElementSibling;
        if (content.classList.contains('default-visible')) {
            collapsible.classList.add('active');
            content.style.maxHeight = content.scrollHeight + "px";
        } else {
            content.style.maxHeight = '0px';
        }
        setTimeout(() => {
            content.classList.add('animate-max-height');
        }, 0);

        collapsible.addEventListener('click', function() {
            this.classList.toggle('active');
            const currentContent = this.nextElementSibling;
            if (currentContent.style.maxHeight && currentContent.style.maxHeight !== '0px') {
                currentContent.style.maxHeight = '0px';
            } else {
                currentContent.style.maxHeight = currentContent.scrollHeight + "px";
            }
        });
    });

    const headerMenuToggle = document.getElementById('headerMenuToggle');
    const leftSidebar = document.getElementById('leftSidebar');
    const mainContent = document.getElementById('mainContent');
    const rightSidebar = document.getElementById('rightSidebar');

    const screenBreakpointRightSidebar = 1199.98;
    const screenBreakpointLeftSidebar = 991.98;

    const coursesGridContainer = document.getElementById('coursesGridContainer');
    const coursesPlaceholder = document.getElementById('coursesPlaceholder');
    const teacherCoursesList = document.getElementById('teacherCoursesList');
    const studentCoursesList = document.getElementById('studentCoursesList');


    function updateMainContentLayout() {
        let marginLeft = 0;
        let marginRight = 0;
        if (leftSidebar && !leftSidebar.classList.contains('hidden')) {
            marginLeft = leftSidebar.offsetWidth;
        }
        if (rightSidebar && !rightSidebar.classList.contains('hidden')) {
            marginRight = rightSidebar.offsetWidth;
        }
        mainContent.style.marginLeft = marginLeft + 'px';
        mainContent.style.marginRight = marginRight + 'px';
    }

    function toggleLeftSidebarVisibility() {
        if (!leftSidebar) return;
        const isCurrentlyHidden = leftSidebar.classList.contains('hidden');
        leftSidebar.classList.toggle('hidden');
        if (isCurrentlyHidden) {
            leftSidebar.classList.add('js-forced-open');
        } else {
            leftSidebar.classList.remove('js-forced-open');
        }
        updateMainContentLayout();
    }

    if (headerMenuToggle && leftSidebar) {
        headerMenuToggle.addEventListener('click', function() {
            toggleLeftSidebarVisibility();
        });
    }

    function applyResponsiveBehavior() {
        const screenWidth = window.innerWidth;
        if (leftSidebar) {
            if (screenWidth <= screenBreakpointLeftSidebar && !leftSidebar.classList.contains('js-forced-open')) {
                leftSidebar.classList.add('hidden');
            } else if (screenWidth > screenBreakpointLeftSidebar && leftSidebar.classList.contains('hidden') && !leftSidebar.classList.contains('js-forced-open')) {
                leftSidebar.classList.remove('hidden');
            } else if (screenWidth > screenBreakpointLeftSidebar && leftSidebar.classList.contains('js-forced-open')) {
                leftSidebar.classList.remove('hidden');
            }
            leftSidebar.classList.remove('overlay-mode');
        }
        if (rightSidebar) {
            if (screenWidth <= screenBreakpointRightSidebar) {
                rightSidebar.classList.add('hidden');
            } else {
                rightSidebar.classList.remove('hidden');
            }
            rightSidebar.classList.remove('overlay-mode');
        }
        updateMainContentLayout();
    }

    window.addEventListener('resize', applyResponsiveBehavior);

    if (leftSidebar) {
        if (window.innerWidth > screenBreakpointLeftSidebar) {
            leftSidebar.classList.remove('hidden');
            leftSidebar.classList.add('js-forced-open');
        } else {
            leftSidebar.classList.add('hidden');
            leftSidebar.classList.remove('js-forced-open');
        }
    }
    applyResponsiveBehavior();

    const addCourseToggleBtn = document.getElementById('headerAddCourseToggle');
    const addCourseDropdown = document.getElementById('addCourseDropdown');
    const createCourseOption = document.getElementById('createCourseOption');
    const joinCourseOption = document.getElementById('joinCourseOption');
    const createCourseModal = document.getElementById('createCourseModal');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const createCourseFormActual = document.getElementById('createCourseFormActual');
    
    const joinCourseModal = document.getElementById('joinCourseModal');
    const joinModalCloseBtn = document.getElementById('joinModalCloseBtn');
    const joinCourseFormActual = document.getElementById('joinCourseFormActual');


    if (addCourseToggleBtn && addCourseDropdown) {
        addCourseToggleBtn.addEventListener('click', function(event) {
            event.stopPropagation();
            addCourseDropdown.style.display = addCourseDropdown.style.display === 'block' ? 'none' : 'block';
        });
    }
    document.addEventListener('click', function(event) {
        if (addCourseDropdown && addCourseToggleBtn) {
            if (!addCourseToggleBtn.contains(event.target) && !addCourseDropdown.contains(event.target)) {
                addCourseDropdown.style.display = 'none';
            }
        }
    });
    // Обробник для "Створити курс"
    if (createCourseOption && createCourseModal && addCourseDropdown) {
        createCourseOption.addEventListener('click', function(event) {
            event.preventDefault();
            createCourseModal.style.display = 'flex';
            addCourseDropdown.style.display = 'none';
        });
    }
    const createCourseFromSidebar = document.getElementById('createCourseFromSidebar');
    if (createCourseFromSidebar && createCourseModal) {
        createCourseFromSidebar.addEventListener('click', function(event) {
            event.preventDefault();
            createCourseModal.style.display = 'flex';
        });
    }
    if (modalCloseBtn && createCourseModal) {
        modalCloseBtn.addEventListener('click', function() { createCourseModal.style.display = 'none'; });
    }
    if (createCourseModal) {
        createCourseModal.addEventListener('click', function(event) {
            if (event.target === createCourseModal) { createCourseModal.style.display = 'none';}
        });
    }

    // Обробники для модального вікна "Приєднатися до курсу"
    if (joinCourseOption && joinCourseModal && addCourseDropdown) {
        joinCourseOption.addEventListener('click', function(event) {
            event.preventDefault();
            joinCourseModal.style.display = 'flex';
            addCourseDropdown.style.display = 'none'; 
        });
    }
    if (joinModalCloseBtn && joinCourseModal) {
        joinModalCloseBtn.addEventListener('click', function() {
            joinCourseModal.style.display = 'none';
        });
    }
    if (joinCourseModal) {
        joinCourseModal.addEventListener('click', function(event) {
            if (event.target === joinCourseModal) {
                joinCourseModal.style.display = 'none';
            }
        });
    }


    // --- Функції для оновлення UI ---
    function updateCoursesPlaceholderVisibility() {
        if (coursesGridContainer && coursesPlaceholder) { 
            if (coursesGridContainer.children.length > 0) {
                coursesPlaceholder.classList.add('hidden');
            } else {
                coursesPlaceholder.classList.remove('hidden');
            }
        }
    }

    function addCourseCardToDashboard(course) {
        if (!coursesGridContainer) return;

        const cardLink = document.createElement('a');
        cardLink.href = `course_view.php?course_id=${course.id}`; 
        cardLink.classList.add('course-card');
        cardLink.setAttribute('data-course-id', course.id);

        const header = document.createElement('div');
        header.classList.add('card-header', course.color_class || 'course-color-default');

        const title = document.createElement('h4');
        title.textContent = course.name;

        const author = document.createElement('span');
        author.classList.add('course-author');
        author.textContent = course.author_username || CURRENT_USER_USERNAME;

        header.appendChild(title);
        header.appendChild(author);

        const body = document.createElement('div');
        body.classList.add('card-body');

        const descriptionTitle = document.createElement('h5');
        descriptionTitle.textContent = 'Опис курсу';

        const descriptionText = document.createElement('p');
        descriptionText.classList.add('description-text');
        descriptionText.textContent = course.description || 'Немає опису.';

        body.appendChild(descriptionTitle);
        body.appendChild(descriptionText);

        cardLink.appendChild(header);
        cardLink.appendChild(body);

        coursesGridContainer.appendChild(cardLink);
    }

    function addCourseToSidebar(course, listElement) {
        if (!listElement) return;

        const listItem = document.createElement('li');
        const link = document.createElement('a');
        link.href = `course_view.php?course_id=${course.id}`; 
        link.textContent = course.name;
        link.setAttribute('data-course-id', course.id);

        listItem.appendChild(link);

        if (listElement === teacherCoursesList) {
            const createCourseLinkItem = document.getElementById('createCourseFromSidebar')?.parentElement;
            if (createCourseLinkItem) {
                listElement.insertBefore(listItem, createCourseLinkItem);
            } else {
                listElement.appendChild(listItem);
            }
        } else {
            listElement.appendChild(listItem); 
        }
    }
    
    async function loadUserCourses() {
        try {
            const response = await fetch('../../src/get_user_courses.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                console.error(`Помилка завантаження курсів: ${response.status} ${response.statusText}`);
                const errorText = await response.text();
                console.error("Тіло помилки:", errorText);
                throw new Error(`Помилка завантаження курсів: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.status === 'success') {
                if(teacherCoursesList) {
                    const createButtonLi = document.getElementById('createCourseFromSidebar')?.parentElement;
                    while (teacherCoursesList.firstChild && teacherCoursesList.firstChild !== createButtonLi) {
                        teacherCoursesList.removeChild(teacherCoursesList.firstChild);
                    }
                }
                if(studentCoursesList) { 
                    studentCoursesList.innerHTML = '';
                }

                if(coursesGridContainer) {
                    coursesGridContainer.innerHTML = '';
                }

                if (data.teaching_courses && Array.isArray(data.teaching_courses)) {
                    data.teaching_courses.forEach(course => {
                        const courseDataForUI = {
                            id: course.id,
                            name: course.name,
                            description: course.description,
                            color_class: course.color_class,
                            author_username: course.author_username || CURRENT_USER_USERNAME
                        };
                        addCourseToSidebar(courseDataForUI, teacherCoursesList);
                        addCourseCardToDashboard(courseDataForUI);
                    });
                }
                
                if (data.enrolled_courses && Array.isArray(data.enrolled_courses)) {
                    data.enrolled_courses.forEach(course => {
                        const courseDataForUI = {
                            id: course.id,
                            name: course.name,
                            description: course.description,
                            color_class: course.color_class,
                            author_username: course.author_username 
                        };
                        let existingCard = coursesGridContainer.querySelector(`.course-card[data-course-id="${course.id}"]`);
                        if (!existingCard) {
                             addCourseCardToDashboard(courseDataForUI);
                        }
                        addCourseToSidebar(courseDataForUI, studentCoursesList); 
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

    // Обробник форми створення курсу
    if (createCourseFormActual && createCourseModal) {
        createCourseFormActual.addEventListener('submit', async function(event) {
            event.preventDefault();
            // ... (код валідації та відправки даних форми створення курсу, як раніше) ...
            const courseNameInput = document.getElementById('modalCourseName');
            const courseDescriptionInput = document.getElementById('modalCourseDescription');
            const courseName = courseNameInput.value.trim();
            const courseDescription = courseDescriptionInput.value.trim();

            if (!courseName) {
                alert('Назва курсу не може бути порожньою.');
                courseNameInput.focus();
                return;
            }
            const formData = new FormData();
            formData.append('course_name', courseName);
            formData.append('description', courseDescription);

            try {
                const response = await fetch('../../src/create_course_process.php', {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) {
                    const errorData = await response.json().catch(() => null);
                    throw new Error(errorData?.message || `Помилка сервера: ${response.statusText}`);
                }
                const data = await response.json();
                if (data.status === 'success' && data.course) {
                    const newCourseDataForUI = {
                        id: data.course.id,
                        name: data.course.name,
                        description: data.course.description,
                        color_class: data.course.color_class,
                        author_username: data.course.author_username || CURRENT_USER_USERNAME
                    };
                    addCourseToSidebar(newCourseDataForUI, teacherCoursesList);
                    addCourseCardToDashboard(newCourseDataForUI);
                    createCourseModal.style.display = 'none';
                    this.reset();
                } else {
                    alert(data.message || 'Не вдалося створити курс.');
                }
            } catch (error) {
                console.error('Помилка при створенні курсу:', error);
                alert(`Сталася помилка: ${error.message}`);
            }
        });
    }

    // Обробник форми приєднання до курсу
    if (joinCourseFormActual && joinCourseModal) {
        joinCourseFormActual.addEventListener('submit', async function(event) {
            event.preventDefault();
            const courseCodeInput = document.getElementById('modalCourseCode');
            const courseCode = courseCodeInput.value.trim();

            if (!courseCode) {
                alert('Будь ласка, введіть код курсу.');
                courseCodeInput.focus();
                return;
            }

            const formData = new FormData();
            formData.append('course_code', courseCode);

            try {
                const response = await fetch('../../src/join_course_process.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => null);
                    throw new Error(errorData?.message || `Помилка сервера: ${response.statusText}`);
                }

                const data = await response.json();

                if (data.status === 'success' && data.course) {
                    let existingCard = coursesGridContainer.querySelector(`.course-card[data-course-id="${data.course.id}"]`);
                    if (!existingCard) {
                         addCourseCardToDashboard(data.course);
                    }
                    let existingStudentLink = studentCoursesList.querySelector(`a[data-course-id="${data.course.id}"]`);
                    if (!existingStudentLink) {
                        addCourseToSidebar(data.course, studentCoursesList);
                    }
                    
                    joinCourseModal.style.display = 'none';
                    this.reset(); 
                    alert(data.message); 
                } else {
                    alert(data.message || 'Не вдалося приєднатися до курсу.');
                }
            } catch (error) {
                console.error('Помилка при приєднанні до курсу:', error);
                alert(`Сталася помилка: ${error.message}`);
            }
        });
    }
    
    loadUserCourses(); 
});
</script>
</body>
</html>