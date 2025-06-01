<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        // Якщо header.php в public/html/templates/, а login.php в public/html/
        // то шлях для PHP header() має бути відносно поточного файлу (header.php)
        header("Location: ../login.php"); // Змінено шлях до login.php
        exit();
    }
    $current_username_for_js_header = htmlspecialchars($_SESSION['username'] ?? 'Гість', ENT_QUOTES, 'UTF-8');
    $current_page_header = basename($_SERVER['PHP_SELF']);

    // Посилання для HTML (атрибути href).
    // Оскільки header.php включається файлами з директорії public/html/,
    // ці відносні шляхи будуть правильними для браузера з точки зору цих файлів.
    $home_link = 'home.php';
    $settings_link = 'settings.php';
    // Замініть '#' на реальні шляхи, коли сторінки будуть готові
    // Наприклад, якщо assignments_list.php знаходиться в public/html/
    $tasks_link = 'assignments_list.php'; // Приклад
    $grades_link = 'grades_overview.php';   // Приклад

    // Шлях для виходу з системи (logout.php знаходиться в /src/)
    // Зі сторінки в public/html/, шлях до src/logout.php буде ../../src/logout.php
    // Цей шлях використовується в HTML, тому він має бути відносним до сторінки, що відображається.
    $logout_link = '../../src/logout.php';

?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ... (ВСІ ВАШІ CSS СТИЛІ ДЛЯ HEADER ТА SIDEBAR ЗАЛИШАЮТЬСЯ ТУТ) ... */
        :root {
            --primary-text-color: #3c4043;
            --sidebar-link-color: #5f6368;
            --sidebar-link-hover-bg: #e8f0fe;
            --sidebar-link-active-bg: #d2e3fc;
            --sidebar-link-active-color: #1967d2;
            --sidebar-icon-color: #5f6368;
            --header-bg: #fff;
            --header-border-color: #dadce0;
            --header-title-color: #5f6368;
            --body-bg: #f0f2f5;
            --button-primary-bg: #1a73e8;
            --button-primary-hover-bg: #1765cf;
        }

        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', 'Arial', sans-serif;
            background-color: var(--body-bg);
            height: 100%;
            overflow-x: hidden;
        }

        .app-header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background-color: var(--header-bg);
            border-bottom: 1px solid var(--header-border-color);
            display: flex;
            align-items: center;
            padding: 0 20px; /* Increased padding */
            box-sizing: border-box;
            z-index: 1005;
        }

        .header-left {
            display: flex;
            align-items: center;
        }

        .header-title {
            font-size: 22px;
            color: var(--header-title-color);
            font-weight: 500; /* Slightly bolder title */
        }

        .header-right {
            margin-left: auto;
            display: flex;
            align-items: center;
        }

        .header-action-btn {
            background-color: var(--button-primary-bg);
            color: white;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background-color 0.2s ease;
            margin-right: 10px;
            text-decoration: none;
        }

        .header-action-btn:hover {
            background-color: var(--button-primary-hover-bg);
        }
        
        .header-action-btn i.fas {
            margin-right: 8px;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 50px; /* Adjust if header height changes */
            right: 0;
            background-color: white;
            border: 1px solid var(--header-border-color);
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1006;
            min-width: 200px;
            overflow: hidden; /* Ensures rounded corners are respected by items */
        }

        .dropdown-menu a {
            display: block;
            padding: 10px 15px; /* Consistent padding */
            text-decoration: none;
            color: var(--primary-text-color);
            font-size: 14px;
            line-height: 1.5;
        }

        .dropdown-menu a:hover {
            background-color: #f1f3f4;
        }
        
        .app-body-content {
            padding-top: 60px; /* Height of the header */
            height: calc(100vh - 60px);
            box-sizing: border-box;
            display: flex;
            position: relative;
        }

        .left-sidebar {
            width: 260px; /* Slightly wider sidebar */
            background-color: var(--header-bg); /* Same as header for consistency */
            padding: 15px 0; /* Vertical padding, horizontal handled by links */
            box-sizing: border-box;
            border-right: 1px solid var(--header-border-color);
            overflow-y: auto;
            height: 100%; /* Full height of .app-body-content */
            position: fixed; /* Fixed position */
            top: 60px; /* Below the header */
            left: 0;
            z-index: 1001;
        }

        .left-sidebar ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .left-sidebar ul li {
            margin: 0; /* Remove any default margins */
        }

        .left-sidebar ul li a {
            display: flex; /* Use flex for icon and text alignment */
            align-items: center;
            padding: 12px 20px; /* Consistent padding */
            text-decoration: none;
            color: var(--sidebar-link-color);
            font-weight: 500;
            font-size: 14px; /* Standardized font size */
            border-radius: 0 25px 25px 0; /* Rounded on the right, like Gmail/Classroom */
            margin-right: 15px; /* Space for the rounded edge effect */
            transition: background-color 0.2s ease, color 0.2s ease;
            white-space: nowrap; /* Prevent text wrapping */
        }

        .left-sidebar ul li a:hover {
            background-color: var(--sidebar-link-hover-bg);
            color: var(--primary-text-color); /* Darker text on hover */
        }

        .left-sidebar ul li a.active,
        .left-sidebar ul li a.active:hover { /* Style for active link */
            background-color: var(--sidebar-link-active-bg);
            color: var(--sidebar-link-active-color);
            font-weight: 700; /* Bolder active link */
        }
        
        .left-sidebar ul li a.active i.fas {
            color: var(--sidebar-link-active-color); /* Icon color matches active text */
        }

        .left-sidebar ul li a i.fas {
            margin-right: 20px; /* Increased space between icon and text */
            font-size: 18px; /* Slightly larger icons */
            width: 24px;     /* Fixed width for alignment */
            text-align: center;
            color: var(--sidebar-icon-color);
            transition: color 0.2s ease;
        }
        
        .page-content-wrapper {
            margin-left: 260px; /* Same as sidebar width */
            flex-grow: 1;
            padding: 25px;
            box-sizing: border-box;
            overflow-y: auto;
            height: 100%; 
        }
         /* Modal styles (basic, can be expanded) */
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
            position: absolute; top: 10px; right: 15px; background: none; border: none;
            font-size: 24px; cursor: pointer; color: #757575; padding: 5px; line-height: 1;
        }
        .modal-close-btn:hover { color: #333; }
        .modal-content h2 { margin-top: 0; margin-bottom: 20px; font-size: 20px; color: #333; }
        .modal-content .input-container { position: relative; margin: 10px 0 20px 0; }
        .modal-content .input-container .iftaLabel {
            position: absolute; top: 8px; left: 12px; font-size: 12px;
            color: #5f6368; pointer-events: none; background-color: #fff; padding: 0 4px;
        }
        .modal-content .input-container .inputField {
            border: 1px solid #dadce0; border-radius: 4px; width: 100%;
            padding: 20px 15px 8px 15px; font-size: 16px; box-sizing: border-box;
        }
        .modal-content .input-container .inputField:focus {
            border-color: var(--button-primary-bg); outline: none; box-shadow: 0 0 0 1px var(--button-primary-bg);
        }
        .modal-content .input-container textarea.inputField { min-height: 80px; resize: vertical; }
        .modal-content .submit-button {
            background-color: var(--button-primary-bg); color: var(--white, white);
            border-radius: 4px; border: none; font-size: 16px;
            padding: 10px 20px; cursor: pointer; display: block;
            margin: 20px auto 0 auto; transition: background-color 0.2s ease;
        }
        .modal-content .submit-button:hover { background-color: var(--button-primary-hover-bg); }
    </style>
</head>
<body>
    <header class="app-header">
        <div class="header-left">
            <span class="header-title">Assignet</span>
        </div>
        <div class="header-right">
            <?php if (isset($show_add_course_button_on_home) && $show_add_course_button_on_home): ?>
            <div style="position: relative;">
                <button class="header-action-btn" id="headerAddCourseToggle"><i class="fas fa-plus"></i> Додати курс </button>
                <div class="dropdown-menu" id="addCourseDropdown">
                    <a href="#" id="joinCourseOption">Приєднатися до курсу</a>
                    <a href="#" id="createCourseOption">Створити курс</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="app-body-content">
        <aside class="left-sidebar" id="leftSidebar">
            <nav>
                 <ul>
                    <li><a href="<?php echo $home_link; ?>" class="<?php echo ($current_page_header === 'home.php') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Головна</a></li>
                    <li><a href="<?php echo $settings_link; ?>" class="<?php echo ($current_page_header === 'settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Налаштування</a></li>
                    <li><a href="<?php echo $tasks_link; ?>" class="<?php echo ($current_page_header === 'assignments_list.php' || $current_page_header === basename($tasks_link)) ? 'active' : ''; ?>"><i class="fas fa-tasks"></i> Завдання</a></li>
                    <li><a href="<?php echo $grades_link; ?>" class="<?php echo ($current_page_header === 'grades_overview.php' || $current_page_header === basename($grades_link)) ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> Оцінки</a></li>
                </ul>
            </nav>
        </aside>
        
        <script>
    // ... (JavaScript ЗАЛИШАЄТЬСЯ БЕЗ ЗМІН, оскільки він не впливає на генерацію URL-адрес) ...
    const CURRENT_USER_USERNAME_HEADER = "<?php echo $current_username_for_js_header; ?>";
    const IS_HOME_PAGE_HEADER = <?php echo json_encode(isset($show_add_course_button_on_home) && $show_add_course_button_on_home); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        const sidebarLinks = document.querySelectorAll('.left-sidebar nav ul li a');
        const currentPageName = "<?php echo $current_page_header; ?>";
        const homeLinkHref = "<?php echo $home_link; ?>";
        const settingsLinkHref = "<?php echo $settings_link; ?>";
        const tasksLinkHref = "<?php echo $tasks_link; ?>";
        const gradesLinkHref = "<?php echo $grades_link; ?>";

        sidebarLinks.forEach(link => {
            const linkHref = link.getAttribute('href');
            // Перевіряємо, чи поточна сторінка (currentPageName) є частиною href посилання
            // Це більш надійний спосіб, якщо $tasks_link або $grades_link не просто '#'
            if (linkHref === currentPageName || (linkHref !== '#' && currentPageName === basename(linkHref))) {
                 link.classList.add('active');
            } else if (currentPageName === 'index.php' && linkHref === homeLinkHref) { // Особливий випадок для головної, якщо вона index.php
                 link.classList.add('active');
            }
            else {
                link.classList.remove('active');
            }
        });

        if (IS_HOME_PAGE_HEADER) {
            const addCourseToggleBtn = document.getElementById('headerAddCourseToggle');
            const addCourseDropdown = document.getElementById('addCourseDropdown');

            if (addCourseToggleBtn && addCourseDropdown) {
                addCourseToggleBtn.addEventListener('click', function(event) {
                    event.stopPropagation(); 
                    addCourseDropdown.style.display = addCourseDropdown.style.display === 'block' ? 'none' : 'block';
                });
            }
            document.addEventListener('click', function(event) {
                if (addCourseDropdown && addCourseToggleBtn) {
                    if (addCourseDropdown.style.display === 'block' &&
                        !addCourseToggleBtn.contains(event.target) &&
                        !addCourseDropdown.contains(event.target)) {
                        addCourseDropdown.style.display = 'none';
                    }
                }
            });
        }
    });

    // Допоміжна функція basename для JavaScript, якщо потрібно (для активних посилань)
    function basename(path) {
        if (typeof path !== 'string') return '';
        let base = path.substring(path.lastIndexOf('/') + 1);
        if (base.lastIndexOf(".") !== -1) {
            base = base.substring(0, base.lastIndexOf("."));
        }
        // Також видаляємо можливі параметри URL
        if (base.indexOf("?") !== -1) {
            base = base.substring(0, base.indexOf("?"));
        }
        return base;
    }
</script>