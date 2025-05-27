<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/html/login.php"); 
    exit();
}

// $username = $_SESSION['username'] ?? 'Користувач'; // Поки що не використовуємо
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Головна - Assignet</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/home_layout_styles.css">
</head>
<body class="home-layout-final-proto">

    <aside class="app-sidebar-final">
        <div class="logo-container">
            <h2>Assignet</h2>
        </div>
        <nav>
            <ul>
                <li><a href="home.php" class="active">Головна</a></li>
                <li><a href="#settings">Налаштування</a></li>
                <li><a href="#teaching">Як викладач</a></li>
                <li><a href="#enrolled">Як студент</a></li>
                <li><a href="#deadlines">Дедлайни</a></li>
                <li><a href="#grades">Оцінки</a></li>
            </ul>
        </nav>
        <div class="logout-link">
             <ul><li><a href="logout.php">Вийти</a></li></ul>
        </div>
    </aside>

    <div class="page-content-area-final"> 
        <main class="courses-column-final">
            <h2 class="column-title-final">Мої курси</h2>
            <div class="courses-placeholder-final">
                <p>(Простір для відображення курсів)</p>
                <p>Ця область має розтягуватися, якщо курсів багато, і скролитися разом зі сторінкою.</p>
                <?php for ($i = 0; $i < 30; $i++): ?>
                    <p>Елемент курсу <?php echo $i + 1; ?></p>
                <?php endfor; ?>
            </div>
        </main>

        <aside class="widgets-column-final">
            <h3 class="column-title-final">Віджети</h3>
            <div class="widget-item-final">
                <h4>Віджет 1</h4>
                <p>Контент віджета 1...</p>
            </div>
            <div class="widget-item-final">
                <h4>Віджет 2</h4>
                <p>Контент віджета 2...</p>
            </div>
            <div class="widget-item-final">
                <h4>Віджет 3</h4>
                <p>Контент віджета 3...</p>
            </div>
        </aside>
    </div>

</body>
</html>