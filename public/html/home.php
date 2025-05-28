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
        .header-menu-toggle, .header-widgets-toggle-btn {
            background: none; border: none; font-size: 24px; cursor: pointer;
            padding: 8px; color: #5f6368; display: flex;
            align-items: center; justify-content: center;
        }
        .header-menu-toggle { margin-right: 10px; }
        .header-widgets-toggle-btn { margin-left: 10px; }
        .header-menu-toggle:hover, .header-widgets-toggle-btn:hover {
            background-color: rgba(60,64,67,0.08); border-radius: 50%;
        }
        .header-title { font-size: 22px; color: #5f6368; }
        .header-right { margin-left: auto; display: flex; align-items: center; }
        .header-action-btn { /* Оновлено для кнопки "Додати курс" */
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
            z-index: 1001; /* Вище main-content, якщо буде overlay */
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
            /* transition: max-height 0.3s ease-out; -- Анімація додається JS */
        }
        .left-sidebar .sub-menu.animate-max-height { /* Клас для анімації */
            transition: max-height 0.3s ease-out;
        }
        
        .main-content {
            flex-grow: 1; padding: 25px; box-sizing: border-box; overflow-y: auto;
            margin-left: 250px; 
            margin-right: 280px;
            transition: margin-left 0.3s ease-in-out, margin-right 0.3s ease-in-out;
            height: 100%; position: relative; z-index: 1;
        }
        .main-content.left-sidebar-hidden { margin-left: 0; }
        .main-content.right-sidebar-hidden-pushes { /* Коли правий сайдбар ховається і перестає пхати */
             margin-right: 0;
        }
        .main-content.right-sidebar-is-overlay { /* Коли правий сайдбар поверх, він не має пхати */
            margin-right: 0; 
        }
        
        .courses-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 25px; }
        .courses-placeholder {
            padding: 20px; text-align: center; color: #777; font-style: italic;
            border: 2px dashed #ccc; min-height: 200px;
            display: flex; align-items: center; justify-content: center;
        }

        .right-sidebar {
            width: 280px; background-color: #fff; padding: 20px; box-sizing: border-box;
            border-left: 1px solid #e0e0e0; overflow-y: auto;
            position: fixed; top: 60px; right: 0; height: calc(100vh - 60px);
            z-index: 1000; /* За замовчуванням */
            transform: translateX(0); /* Початково видимий */
            transition: transform 0.3s ease-in-out;
        }
        .right-sidebar.hidden { transform: translateX(100%); }
        .right-sidebar.overlay-mode { 
            z-index: 1002; /* Вище, ніж main-content */
            box-shadow: -3px 0 10px rgba(0,0,0,0.15); 
        }
        .right-sidebar h3 { margin-top: 0; color: #555; font-size: 1.2em; margin-bottom: 20px; }
        .widget { margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #e7e7e7; border-radius: 5px; }
        .widget h4 { margin-top: 0; margin-bottom: 10px; font-size: 1em; }
        .widget p { font-size: 0.9em; color: #666; }

        /* Адаптивність: Media Queries */
        @media (max-width: 1199.98px) { /* Правий сайдбар за замовчуванням ховається і стає overlay при відкритті */
            .right-sidebar:not(.js-forced-open) {
                transform: translateX(100%); /* Ховаємо */
            }
            .main-content:not(.js-right-sidebar-forced-push) { /* Якщо правий не примусово пхає */
                margin-right: 0;
            }
             .header-widgets-toggle-btn { display: inline-flex; }
        }

        @media (max-width: 991.98px) { /* Лівий сайдбар ховається */
            .left-sidebar:not(.js-forced-open) {
                transform: translateX(-100%);
            }
            .main-content:not(.js-left-sidebar-forced-push) {
                margin-left: 0;
            }
        }

        /* Стилі для випадаючого меню та модального вікна з попереднього повідомлення залишаються тут */
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
                    <li><a href="#">Налаштування</a></li>
                    <li><a href="#">Завдання</a></li>
                    <li><a href="#">Оцінки</a></li>
                    <li>
                        <div class="collapsible-header active">Як викладач</div>
                        <ul class="sub-menu default-visible">
                            <li><a href="#">Створений курс 1</a></li>
                            <li><a href="#" id="createCourseFromSidebar">+ Створити</a></li>
                        </ul>
                    </li>
                    <li>
                        <div class="collapsible-header active">Як студент</div>
                        <ul class="sub-menu default-visible">
                            <li><a href="#">Записаний курс A</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content" id="mainContent">
            <div class="courses-placeholder">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const collapsibles = document.querySelectorAll('.left-sidebar .collapsible-header');
    collapsibles.forEach(function(collapsible) {
        const content = collapsible.nextElementSibling;
        if (content.classList.contains('default-visible')) {
            collapsible.classList.add('active');
            content.style.maxHeight = content.scrollHeight + "px"; // Встановлюємо без анімації
        } else { 
            content.style.maxHeight = '0px';
        }
        // Додаємо клас для анімації ПІСЛЯ початкового налаштування
        // щоб уникнути "вильоту" при завантаженні
        setTimeout(() => { // невелика затримка, щоб браузер встиг застосувати початковий max-height
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
    const headerWidgetsToggle = document.getElementById('headerWidgetsToggle');
    const rightSidebar = document.getElementById('rightSidebar');
    
    const screenBreakpointRightSidebar = 1199.98;
    const screenBreakpointLeftSidebar = 991.98;

    // --- Функція для оновлення відступів головного контенту ---
    function updateMainContentLayout() {
        let marginLeft = 0;
        let marginRight = 0;
        const screenWidth = window.innerWidth;

        if (leftSidebar && !leftSidebar.classList.contains('hidden')) {
            marginLeft = leftSidebar.offsetWidth;
        }

        if (rightSidebar && !rightSidebar.classList.contains('hidden')) {
            // Якщо правий сайдбар видимий І екран малий, він не пхає контент (бо стає overlay)
            if (screenWidth <= screenBreakpointRightSidebar && rightSidebar.classList.contains('overlay-mode')) {
                marginRight = 0;
            } else {
                marginRight = rightSidebar.offsetWidth; // На великих екранах або якщо не overlay, він пхає
            }
        }
        
        mainContent.style.marginLeft = marginLeft + 'px';
        mainContent.style.marginRight = marginRight + 'px';
    }

    // --- Функція для перемикання стану сайдбару (лівого або правого) ---
    function toggleSidebarVisibility(sidebarElement, isForcedOpenClass, isOverlayOnSmallScreen = false) {
        const screenWidth = window.innerWidth;
        const isCurrentlyHidden = sidebarElement.classList.contains('hidden');

        sidebarElement.classList.toggle('hidden'); // Перемикаємо основний клас видимості

        if (isCurrentlyHidden) { // Якщо сайдбар БУВ прихований, а тепер ВІДКРИВАЄТЬСЯ
            sidebarElement.classList.add(isForcedOpenClass); // Позначаємо, що користувач його відкрив
            if (isOverlayOnSmallScreen && screenWidth <= screenBreakpointRightSidebar && sidebarElement === rightSidebar) {
                sidebarElement.classList.add('overlay-mode');
            } else {
                sidebarElement.classList.remove('overlay-mode'); // На великих екранах або для лівого - не overlay
            }
        } else { // Якщо сайдбар БУВ видимий, а тепер ЗАКРИВАЄТЬСЯ
            sidebarElement.classList.remove(isForcedOpenClass); // Користувач його закрив
            sidebarElement.classList.remove('overlay-mode'); // При закритті завжди знімаємо overlay
        }
        updateMainContentLayout();
    }

    if (headerMenuToggle) {
        headerMenuToggle.addEventListener('click', function() {
            toggleSidebarVisibility(leftSidebar, 'js-forced-open');
        });
    }
    if (headerWidgetsToggle) {
        headerWidgetsToggle.addEventListener('click', function() {
            // Правий сайдбар має ставати overlay на малих екранах при відкритті кнопкою
            toggleSidebarVisibility(rightSidebar, 'js-forced-open', true); 
        });
    }
    
    // ... (код для випадаючого меню та модального вікна залишається без змін) ...
    const addCourseToggleBtn = document.getElementById('headerAddCourseToggle');
    const addCourseDropdown = document.getElementById('addCourseDropdown');
    const createCourseOption = document.getElementById('createCourseOption');
    const joinCourseOption = document.getElementById('joinCourseOption');
    const createCourseModal = document.getElementById('createCourseModal');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const createCourseFormActual = document.getElementById('createCourseFormActual');
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
    if (createCourseFormActual && createCourseModal) {
        createCourseFormActual.addEventListener('submit', function(event) {
            event.preventDefault(); 
            const courseName = document.getElementById('modalCourseName').value;
            const courseDescription = document.getElementById('modalCourseDescription').value;
            console.log('Назва курсу:', courseName); console.log('Опис курсу:', courseDescription);
            alert('Курс "' + courseName + '" нібито створено! Дані в консолі.');
            createCourseModal.style.display = 'none'; this.reset(); 
        });
    }
    if (joinCourseOption) {
        joinCourseOption.addEventListener('click', function(event) {
            event.preventDefault();
            alert('Функціонал "Приєднатися до курсу" буде реалізовано пізніше.');
            if (addCourseDropdown) addCourseDropdown.style.display = 'none';
        });
    }

    // --- Адаптивна поведінка при зміні розміру вікна ---
    function applyResponsiveBehavior() {
        const screenWidth = window.innerWidth;

        // Лівий сайдбар
        if (leftSidebar) {
            // Якщо екран малий І сайдбар НЕ був явно відкритий користувачем
            if (screenWidth <= screenBreakpointLeftSidebar && !leftSidebar.classList.contains('js-forced-open')) {
                leftSidebar.classList.add('hidden');
            } 
            // Якщо екран великий І сайдбар НЕ був явно закритий користувачем (тобто не має 'hidden' АЛЕ має 'js-forced-open' якщо кнопка була натиснута)
            // Або якщо екран великий і немає 'js-forced-open' (тобто показуємо за замовчуванням, бо 'hidden' знімається)
            else if (screenWidth > screenBreakpointLeftSidebar && !leftSidebar.classList.contains('hidden') /* Якщо він не закритий кнопкою */ ) {
                 leftSidebar.classList.remove('hidden');
            }
            leftSidebar.classList.remove('overlay-mode'); // Лівий сайдбар ніколи не overlay
        }

        // Правий сайдбар
        if (rightSidebar) {
            if (screenWidth <= screenBreakpointRightSidebar && !rightSidebar.classList.contains('js-forced-open')) {
                rightSidebar.classList.add('hidden'); // Ховаємо
                rightSidebar.classList.remove('overlay-mode'); // Якщо ховається автоматично, він не overlay
                mainContent.classList.add('right-sidebar-auto-hidden'); // Допоміжний клас для CSS
            } else if (screenWidth > screenBreakpointRightSidebar && !rightSidebar.classList.contains('hidden') /* Якщо він не закритий кнопкою */) {
                rightSidebar.classList.remove('hidden'); // Показуємо
                rightSidebar.classList.remove('overlay-mode'); // На великих екранах він пхає
                mainContent.classList.remove('right-sidebar-auto-hidden');
            } else if (screenWidth <= screenBreakpointRightSidebar && rightSidebar.classList.contains('js-forced-open') && !rightSidebar.classList.contains('hidden')) {
                // Якщо екран малий І сайдбар примусово відкритий І він не прихований -> робимо overlay
                rightSidebar.classList.add('overlay-mode');
                mainContent.classList.remove('right-sidebar-auto-hidden'); // Він не auto-hidden, а forced-open
            } else if (screenWidth > screenBreakpointRightSidebar && rightSidebar.classList.contains('js-forced-open')) {
                 // Якщо екран великий і він forced-open, він не overlay
                 rightSidebar.classList.remove('overlay-mode');
                 mainContent.classList.remove('right-sidebar-auto-hidden');
            }
        }
        updateMainContentLayout(); // Оновлюємо марджини після всіх адаптивних змін
    }
    
    window.addEventListener('resize', applyResponsiveBehavior);
    
    // Початкове налаштування: робимо сайдбари видимими (якщо екран великий)
    // і встановлюємо 'js-forced-open', щоб вони не сховались одразу через @media
    // і щоб правильно спрацював applyResponsiveBehavior
    if (window.innerWidth > screenBreakpointLeftSidebar && leftSidebar) {
        leftSidebar.classList.add('js-forced-open');
    }
    if (window.innerWidth > screenBreakpointRightSidebar && rightSidebar) {
       rightSidebar.classList.add('js-forced-open');
    }
    applyResponsiveBehavior(); // Застосувати для початкового стану
});
</script>
</body>
</html>