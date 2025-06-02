<?php
    // File: public/html/home.php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    // Ця змінна буде використана header.php для відображення кнопки "Додати курс"
    $show_add_course_button_on_home = true;
    require_once __DIR__ . '/templates/header.php';

    // Отримуємо ім'я користувача для JS, специфічне для логіки карток курсу на домашній сторінці
    $current_username_for_js_home_php = htmlspecialchars($_SESSION['username'] ?? 'Автор', ENT_QUOTES, 'UTF-8'); // Змінено назву змінної
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
    const CURRENT_USER_USERNAME_HOME_JS = <?php echo json_encode($current_username_for_js_home_php); ?>;
</script>

<script src="../js/home.js"></script>

</body>
</html>