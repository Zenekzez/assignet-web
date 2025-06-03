<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $show_add_course_button_on_home = true;
    require_once __DIR__ . '/templates/layout.php';

    $current_username_for_js_home_php = htmlspecialchars($_SESSION['username'] ?? 'Автор', ENT_QUOTES, 'UTF-8'); 
?>

<title>Головна - AssignNet</title>
<link rel="icon" href="public/assets/assignnet_logo.png" type="image/x-icon">
<link rel="stylesheet" href="../css/home_styles.css">
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
    const CURRENT_USER_USERNAME_HOME_JS = <?php echo json_encode($current_username_for_js_home_php); ?>;
</script>

<script src="../js/home.js"></script>

</body>
</html>