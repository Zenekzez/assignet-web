<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        // Якщо користувач не авторизований, перенаправляємо на сторінку входу
        // Важливо: шлях до login.php може потребувати корекції
        header("Location: login.php");
        exit();
    }
    $current_username_for_js = htmlspecialchars($_SESSION['username'] ?? 'Гість', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Скопіюйте сюди стилі з home.php, які стосуються: */
        /* body, html, .app-body-content, .app-header, .header-left, .header-menu-toggle, */
        /* .header-widgets-toggle-btn, .header-title, .header-right, .header-action-btn, */
        /* .dropdown-menu, .modal-overlay, .modal-content, .modal-close-btn, */
        /* .left-sidebar (і його вміст), .main-content (базові стилі), .right-sidebar */
        /* Це потрібно, щоб не дублювати стилі на кожній сторінці */
        /* Або ще краще - винести ці загальні стилі в окремий CSS-файл і підключати його */

        /* Поки що для простоти, припустимо, що основні стилі для layout тут або в глобальному CSS */
        body, html {
            margin: 0; padding: 0; font-family: Tahoma, Verdana, sans-serif;
            background-color: #f0f2f5; height: 100%; overflow-x: hidden;
        }
        .app-body-content {
            padding-top: 60px; /* Висота хедера */
            height: calc(100vh - 60px); /* Висота viewport мінус висота хедера */
            box-sizing: border-box;
            display: flex;
            position: relative;
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
        .header-widgets-toggle-btn { display: none !important; }
        .header-title { font-size: 22px; color: #5f6368; }
        .header-right { margin-left: auto; display: flex; align-items: center; }
        .header-action-btn { /* Для кнопки "Додати курс" */
            background-color: #4285f4; color: white; border: none;
            padding: 8px 16px; font-size: 14px; font-weight: 500;
            border-radius: 4px; cursor: pointer; display: flex;
            align-items: center; transition: background-color 0.2s ease;
            margin-right: 10px;
        }
        .header-action-btn:hover { background-color: #3367d6; }
        .dropdown-menu {
            display: none; position: absolute; top: 50px; right: 0;
            background-color: white; border: 1px solid #dadce0;
            border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1006; min-width: 200px;
        }
        .dropdown-menu a { display: block; padding: 12px 16px; text-decoration: none; color: #3c4043; font-size: 14px; }
        .dropdown-menu a:hover { background-color: #f1f3f4; }

        .left-sidebar {
            width: 250px; background-color: #fff; padding: 20px; box-sizing: border-box;
            border-right: 1px solid #e0e0e0; overflow-y: auto;
            /* position: fixed; top: 60px; left: 0; height: calc(100vh - 60px); - це буде в app-body-content */
            /* z-index: 1001; */
            transform: translateX(0); transition: transform 0.3s ease-in-out;
            height: 100%; /* Займатиме всю висоту батьківського .app-body-content */
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
        /* Стилі для основного контенту сторінки налаштувань */
        .settings-main-content {
            flex-grow: 1;
            padding: 25px;
            box-sizing: border-box;
            overflow-y: auto;
            height: 100%; /* Займатиме всю висоту батьківського .app-body-content */
        }
        .settings-container {
            background-color: #fff;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 700px; /* Обмеження ширини для кращого вигляду */
            margin: 0 auto; /* Центрування */
        }
        .settings-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .settings-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .settings-section h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.5em;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="file"] {
            width: calc(100% - 20px);
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
        }
        .form-group input[type="file"] {
            padding: 5px; /* Трохи інакше для поля файлу */
        }
        .form-group small {
            display: block;
            font-size: 0.85em;
            color: #777;
            margin-top: 3px;
        }
        .settings-button, .logout-button {
            background-color: #4285f4;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 1em;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin-top: 10px;
        }
        .settings-button:hover {
            background-color: #3367d6;
        }
        .logout-button {
            background-color: #d9534f;
            display: inline-block; /* Щоб не займав всю ширину */
            text-decoration: none; /* Якщо це буде посилання-кнопка */
            text-align: center;
        }
        .logout-button:hover {
            background-color: #c9302c;
        }
        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #eee;
            background-size: cover;
            background-position: center;
            margin-bottom: 10px;
            border: 2px solid #ddd;
        }
        /* Стилі для повідомлень */
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 0.95em;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            <button class="header-widgets-toggle-btn" id="headerWidgetsToggle" aria-label="Віджети">&#x25A6;</button>
        </div>
    </header>

    <div class="app-body-content">
        <aside class="left-sidebar" id="leftSidebar">
            <nav>
                 <ul>
                    <li><a href="home.php">Головна</a></li>
                    <li><a href="settings.php">Налаштування</a></li> <li><a href="#">Завдання</a></li> <li><a href="#">Оцінки</a></li> <li>
                        <div class="collapsible-header">Як викладач</div> <ul class="sub-menu" id="teacherCoursesList"> <li><a href="#" id="createCourseFromSidebar">+ Створити</a></li>
                        </ul>
                    </li>
                    <li>
                        <div class="collapsible-header">Як студент</div> <ul class="sub-menu" id="studentCoursesList"> </ul>
                    </li>
                </ul>
            </nav>
        </aside>
        <script>
    // Глобальний JavaScript, який керує хедером, сайдбаром тощо.
    const CURRENT_USER_USERNAME_GLOBAL = "<?php echo $current_username_for_js; ?>";

    document.addEventListener('DOMContentLoaded', function () {
        const collapsibles = document.querySelectorAll('.left-sidebar .collapsible-header');
        collapsibles.forEach(function(collapsible) {
            const content = collapsible.nextElementSibling;
            
            // Ініціалізація стану (чи активний елемент за замовчуванням)
            // Цю частину можна залишити, якщо ви хочете, щоб певні меню були розкриті одразу
            // Наприклад, якщо додати клас 'default-open' до .collapsible-header в HTML
            if (collapsible.classList.contains('default-open') && content) { // 'default-open' - новий клас для прикладу
                collapsible.classList.add('active');
                content.style.maxHeight = content.scrollHeight + "px";
            } else if (content) {
                content.style.maxHeight = '0px'; // За замовчуванням всі закриті, якщо немає 'default-open'
            }

            if (content) { // Додаємо анімацію тільки якщо content існує
                setTimeout(() => {
                    content.classList.add('animate-max-height');
                }, 0);
            }

            collapsible.addEventListener('click', function(event) {
                // event.preventDefault(); // НЕ ПОТРІБНО тут для collapsible-header, бо це div, а не посилання
                                        // Якщо б це було посилання <a> з класом collapsible-header, тоді б знадобилось
                this.classList.toggle('active');
                const currentContent = this.nextElementSibling;
                if (currentContent) {
                    if (currentContent.style.maxHeight && currentContent.style.maxHeight !== '0px') {
                        currentContent.style.maxHeight = '0px';
                    } else {
                        currentContent.style.maxHeight = currentContent.scrollHeight + "px";
                    }
                }
            });
        });

        // Обробка кліків на звичайних посиланнях у сайдбарі - НЕ ПОТРІБНА СПЕЦІАЛЬНА ОБРОБКА,
        // якщо вони не мають класів, що перехоплюють події (наприклад, collapsible-header).
        // Стандартна поведінка тега <a> (перехід за href) має працювати.
        // Якщо посилання "Налаштування" не працює, перевірте:
        // 1. Чи немає помилок JavaScript у консолі, які блокують подальше виконання.
        // 2. Чи правильний href="settings.php" і чи файл settings.php знаходиться у тій же директорії,
        //    що й поточна сторінка (наприклад, home.php).
        // 3. Чи не накладений якийсь прозорий елемент поверх посилання, який перехоплює клік.

        const headerMenuToggle = document.getElementById('headerMenuToggle');
        const leftSidebar = document.getElementById('leftSidebar');
        // Адаптуємо для різних сторінок, беремо або settings-main-content або mainContent (якщо є на home.php)
        const mainContentElement = document.querySelector('.settings-main-content') || document.getElementById('mainContent'); 

        const screenBreakpointLeftSidebar = 991.98;

        function updateMainContentLayoutGlobal() {
            if (!mainContentElement || !leftSidebar) return;
            let marginLeft = 0;
            
            if (!leftSidebar.classList.contains('hidden')) {
                marginLeft = leftSidebar.offsetWidth;
            }
            mainContentElement.style.marginLeft = marginLeft + 'px';
        }

        function toggleLeftSidebarVisibilityGlobal() {
            if (!leftSidebar) return;
            const isCurrentlyHidden = leftSidebar.classList.contains('hidden');
            leftSidebar.classList.toggle('hidden');
            if (isCurrentlyHidden) {
                leftSidebar.classList.add('js-forced-open');
            } else {
                leftSidebar.classList.remove('js-forced-open');
            }
            updateMainContentLayoutGlobal();
        }

        if (headerMenuToggle && leftSidebar) {
            headerMenuToggle.addEventListener('click', function() {
                toggleLeftSidebarVisibilityGlobal();
            });
        }

        function applyResponsiveBehaviorGlobal() {
            if (!leftSidebar) return;
            const screenWidth = window.innerWidth;
            if (screenWidth <= screenBreakpointLeftSidebar && !leftSidebar.classList.contains('js-forced-open')) {
                leftSidebar.classList.add('hidden');
            } else if (screenWidth > screenBreakpointLeftSidebar && leftSidebar.classList.contains('hidden') && !leftSidebar.classList.contains('js-forced-open')) {
                leftSidebar.classList.remove('hidden');
            } else if (screenWidth > screenBreakpointLeftSidebar && leftSidebar.classList.contains('js-forced-open')) {
                leftSidebar.classList.remove('hidden');
            }
            updateMainContentLayoutGlobal();
        }

        window.addEventListener('resize', applyResponsiveBehaviorGlobal);

        // Початкове налаштування стану сайдбару
        if (leftSidebar) {
            if (window.innerWidth > screenBreakpointLeftSidebar) {
                leftSidebar.classList.remove('hidden');
                // Якщо хочете, щоб сайдбар був завжди відкритий на великих екранах при завантаженні:
                // leftSidebar.classList.add('js-forced-open'); 
            } else {
                leftSidebar.classList.add('hidden');
                leftSidebar.classList.remove('js-forced-open');
            }
        }
        applyResponsiveBehaviorGlobal(); // Виклик для початкового стану


        // --- Код для модальних вікон "Додати курс", якщо вони викликаються звідси ---
        // Якщо ці кнопки/меню є ТІЛЬКИ на home.php, то цей JS має бути там, а не в глобальному header.php.
        // Припускаємо, що ці елементи можуть бути глобальними або на сторінках, що включають цей header.
        const addCourseToggleBtn = document.getElementById('headerAddCourseToggle'); // Можливо, цієї кнопки немає в хедері на всіх сторінках
        const addCourseDropdown = document.getElementById('addCourseDropdown');     // Аналогічно
        
        if (addCourseToggleBtn && addCourseDropdown) {
            addCourseToggleBtn.addEventListener('click', function(event) {
                event.stopPropagation();
                addCourseDropdown.style.display = addCourseDropdown.style.display === 'block' ? 'none' : 'block';
            });
        }
        
        // Закриття випадаючого меню при кліку поза ним
        document.addEventListener('click', function(event) {
            if (addCourseDropdown && addCourseToggleBtn) { // Перевірка на існування
                if (!addCourseToggleBtn.contains(event.target) && !addCourseDropdown.contains(event.target)) {
                    addCourseDropdown.style.display = 'none';
                }
            }
            // Аналогічно для інших випадаючих меню, якщо вони є
        });

        // Обробники для опцій в addCourseDropdown (Приєднатися/Створити курс)
        // Ці ID мають відповідати тим, що на home.php або де це меню використовується
        const joinCourseOption = document.getElementById('joinCourseOption');
        const createCourseOption = document.getElementById('createCourseOption');
        const createCourseModal = document.getElementById('createCourseModal'); // ID модального вікна
        const joinCourseModal = document.getElementById('joinCourseModal');     // ID модального вікна

        if (createCourseOption && createCourseModal && addCourseDropdown) {
            createCourseOption.addEventListener('click', function(event) {
                event.preventDefault();
                if(createCourseModal) createCourseModal.style.display = 'flex';
                if(addCourseDropdown) addCourseDropdown.style.display = 'none';
            });
        }

        // Посилання "+ Створити" в самому сайдбарі
        const createCourseFromSidebar = document.getElementById('createCourseFromSidebar');
        if (createCourseFromSidebar && createCourseModal) {
            createCourseFromSidebar.addEventListener('click', function(event) {
                event.preventDefault();
                if(createCourseModal) createCourseModal.style.display = 'flex';
            });
        }
        
        if (joinCourseOption && joinCourseModal && addCourseDropdown) {
            joinCourseOption.addEventListener('click', function(event) {
                event.preventDefault();
                if(joinCourseModal) joinCourseModal.style.display = 'flex';
                if(addCourseDropdown) addCourseDropdown.style.display = 'none'; 
            });
        }

        // Закриття модальних вікон
        const modalCloseBtns = document.querySelectorAll('.modal-close-btn'); // Для всіх кнопок закриття
        modalCloseBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Знаходимо батьківське модальне вікно і закриваємо його
                const modal = this.closest('.modal-overlay');
                if (modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // Закриття модальних вікон при кліку на оверлей
        const modalOverlays = document.querySelectorAll('.modal-overlay');
        modalOverlays.forEach(overlay => {
            overlay.addEventListener('click', function(event) {
                if (event.target === this) { // Якщо клік був саме на оверлеї, а не на вмісті
                    this.style.display = 'none';
                }
            });
        });

        // Завантаження списків курсів у сайдбар (можна перенести сюди, якщо потрібні глобально)
        // Функція loadSidebarCourses() має бути визначена або тут, або в specific page JS.
        // Якщо вона специфічна для home.php, то її виклик має бути там.
        // Якщо ж списки курсів у сайдбарі оновлюються на багатьох сторінках, тоді:
        // if (typeof loadSidebarCourses === 'function') {
        //     loadSidebarCourses(); 
        // }
    });
</script>