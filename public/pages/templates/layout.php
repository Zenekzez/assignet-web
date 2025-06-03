<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php"); 
        exit();
    }

    $current_username_for_js_header = htmlspecialchars($_SESSION['username'] ?? 'Гість', ENT_QUOTES, 'UTF-8');
    $current_page_header = basename($_SERVER['PHP_SELF']);

    $home_link = 'home.php';
    $settings_link = 'settings.php';
    $tasks_link = 'assignments_list.php'; 
    $grades_link = 'grades_overview.php';   
    $logout_link = '../../src/logout.php';

?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/layout_styles.css">
</head>
<body>
    <header class="app-header">
        <div class="header-left">
            <span class="header-title">AssignNet</span>
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
            if (linkHref === currentPageName || (linkHref !== '#' && currentPageName === basename(linkHref))) {
                 link.classList.add('active');
            } else if (currentPageName === 'index.php' && linkHref === homeLinkHref) { 
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


    function basename(path) {
        if (typeof path !== 'string') return '';
        let base = path.substring(path.lastIndexOf('/') + 1);
        if (base.lastIndexOf(".") !== -1) {
            base = base.substring(0, base.lastIndexOf("."));
        }
        if (base.indexOf("?") !== -1) {
            base = base.substring(0, base.indexOf("?"));
        }
        return base;
    }
</script>
</body>
</html>