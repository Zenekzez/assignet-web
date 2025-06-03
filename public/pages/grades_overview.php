<?php
    if (session_status() == PHP_SESSION_NONE) { 
        session_start();
    }
 
    $current_page_header = basename($_SERVER['PHP_SELF']); 
    require_once __DIR__ . '/templates/layout.php'; 

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
?>
<title>Мої Оцінки - AssignNet</title>
<link rel="icon" href="public/assets/assignnet_logo.png" type="image/x-icon">
<link rel="stylesheet" href="../css/grades_tab_styles.css">
<link rel="stylesheet" href="../css/course_view_styles.css"> 
<link rel="stylesheet" href="../css/grades_overview_styles.css">
<main class="page-content-wrapper">
    <div class="grades-overview-page-container">
        <div class="course-header-bar">
             <div class="breadcrumbs">
                <a href="home.php">Головна</a> &gt;
                <span>Мої оцінки</span>
            </div>
        </div>
        
        <h1><i class="fas fa-chart-bar"></i> Загальний огляд оцінок</h1>

        <div id="gradesOverviewArea">
            <p class="loading-grades-overview"><i class="fas fa-spinner fa-spin"></i> Завантаження ваших оцінок...</p>
            </div>
        <p class="no-grades-overview-message" style="display:none; text-align:center; padding: 20px; color: #6c757d;">
            У вас ще немає оцінок для відображення або ви не записані на жоден курс.
        </p>
    </div>
</main>

</div> <script src="../js/grades_overview.js"></script> </body>
</html>