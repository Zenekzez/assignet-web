<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../src/connect.php';
require_once __DIR__ . '/templates/layout.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$submission_id_get_php = filter_input(INPUT_GET, 'submission_id', FILTER_VALIDATE_INT); 
$page_title = "Оцінювання роботи";

if (!defined('WEB_ROOT_REL_FROM_HTML_GRADE_SUBMISSION')) {
    define('WEB_ROOT_REL_FROM_HTML_GRADE_SUBMISSION', '../');
}
$default_avatar_web_path_php = WEB_ROOT_REL_FROM_HTML_GRADE_SUBMISSION . 'assets/default_avatar.png'; 

?>
<title><?php echo htmlspecialchars($page_title); ?> - AssignNet</title>
<link rel="icon" href="public/assets/assignnet_logo.png" type="image/x-icon">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML_GRADE_SUBMISSION; ?>css/course_view_styles.css">
<link rel="stylesheet" href="<?php echo WEB_ROOT_REL_FROM_HTML_GRADE_SUBMISSION; ?>css/grading_styles.css">

<main class="page-content-wrapper">
    <div class="course-view-main-content" id="gradingPageContainer">
        <div class="course-header-bar" id="gradingBreadcrumbs" style="display:none;">
            <div class="breadcrumbs">
                <a href="home.php">Мої курси</a> &gt;
                <a id="breadcrumbCourseName" href="#">Курс</a> &gt;
                <a id="breadcrumbAssignmentName" href="#">Завдання</a> &gt;
                <a id="breadcrumbSubmissionsList" href="#">Здані роботи</a> &gt;
                <span>Оцінювання: <span id="breadcrumbStudentName">Студент</span></span>
            </div>
        </div>

        <div id="submissionDetailArea" class="submission-grading-container">
            <p class="loading-text"><i class="fas fa-spinner fa-spin"></i> Завантаження даних роботи...</p>
            <?php if (!$submission_id_get_php): ?>
                <p class="error-text">Помилка: ID зданої роботи не було передано.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

</div>

<script>
    const CURRENT_SUBMISSION_ID_JS = <?php echo $submission_id_get_php ? json_encode((int)$submission_id_get_php) : 'null'; ?>;
    const ASSET_BASE_PATH_FROM_HTML_JS = '<?php echo WEB_ROOT_REL_FROM_HTML_GRADE_SUBMISSION; ?>';
    const DEFAULT_AVATAR_URL_JS_GLOBAL = '<?php echo $default_avatar_web_path_php; ?>';
</script>

<script src="<?php echo WEB_ROOT_REL_FROM_HTML_GRADE_SUBMISSION; ?>js/grade_submission.js"></script>

</body>
</html>