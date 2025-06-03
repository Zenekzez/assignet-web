<?php
    if (session_status() == PHP_SESSION_NONE) { 
        session_start();
    }
    require_once __DIR__ . '/templates/layout.php'; 

    if (!isset($_SESSION['user_id'])) { 
        header("Location: login.php");
        exit();
    }
?>
<title>Мої Завдання - AssignNet</title>
<link rel="icon" href="public/assets/assignnet_logo.png" type="image/x-icon">
<link rel="stylesheet" href="../css/course_view_styles.css"> 
<link rel="stylesheet" href="../css/assignments_list_styles.css">
<main class="page-content-wrapper">
    <div class="assignments-list-page-container"> <div class="course-header-bar"> <div class="breadcrumbs">
                <a href="home.php">Головна</a> &gt;
                <span>Мої завдання</span>
            </div>
        </div>
        
        <h1><i class="fas fa-tasks"></i> Мої завдання</h1>

        <div class="assignments-filters">
            <button class="filter-btn active" data-filter="all">Всі невиконані</button>
            <button class="filter-btn" data-filter="urgent"><i class="fas fa-bell"></i> Термінові</button>
            <button class="filter-btn" data-filter="pending"><i class="far fa-clock"></i> Невиконані</button>
            <button class="filter-btn" data-filter="overdue"><i class="fas fa-exclamation-circle"></i> Прострочені</button>
        </div>

        <div id="allStudentAssignmentsArea" class="assignments-grid-student-all">
            <p class="loading-assignments-global"><i class="fas fa-spinner fa-spin"></i> Завантаження завдань...</p>
            </div>
        <p class="no-assignments-global-message" style="display:none; text-align:center; padding: 20px; color: #6c757d;">Немає завдань для відображення за обраним фільтром.</p>
    </div>
</main>

</div> <script src="../js/assignments_list.js"></script> </body>
</html>