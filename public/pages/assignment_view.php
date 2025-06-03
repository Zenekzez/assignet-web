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

$assignment_id_get = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);
$current_user_id = $_SESSION['user_id'];
$assignment_data = null;
$course_name_for_breadcrumb = 'Курс';
$is_teacher_of_this_course = false; 

define('WEB_ROOT_REL_FROM_HTML_ASSIGNMENT_VIEW', '../');

if (!$assignment_id_get) {

} else {
    $stmt_assignment = $conn->prepare(
        "SELECT a.*, c.course_name, c.author_id as course_author_id
         FROM assignments a
         JOIN courses c ON a.course_id = c.course_id
         WHERE a.assignment_id = ?"
    );
    if ($stmt_assignment) {
        $stmt_assignment->bind_param("i", $assignment_id_get);
        $stmt_assignment->execute();
        $result_assignment = $stmt_assignment->get_result();
        if ($assignment_data_row = $result_assignment->fetch_assoc()) {
            $assignment_data = $assignment_data_row;
            $course_name_for_breadcrumb = htmlspecialchars($assignment_data['course_name']);
            if ($current_user_id == $assignment_data['course_author_id']) {
                $is_teacher_of_this_course = true;
            }
        }
        $stmt_assignment->close();
    } else {
        error_log("Failed to prepare statement for assignment data: " . $conn->error);
    }
}

$page_title = $assignment_data ? htmlspecialchars($assignment_data['title']) : 'Завдання не знайдено';

?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?> - AssignNet</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="public/assets/assignnet_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/layout_styles.css">
    <link rel="stylesheet" href="../css/assignment_view_styles.css">
    <link rel="stylesheet" href="../css/course_view_styles.css">
</head>
<body>

    <main class="page-content-wrapper">
        <div class="course-view-main-content">
            <?php if ($assignment_data): ?>
                <div class="course-header-bar">
                    <div class="breadcrumbs">
                        <a href="home.php">Мої курси</a> &gt;
                        <a href="course_view.php?course_id=<?php echo htmlspecialchars($assignment_data['course_id']); ?>"><?php echo $course_name_for_breadcrumb; ?></a> &gt;
                        <a href="course_view.php?course_id=<?php echo htmlspecialchars($assignment_data['course_id']); ?>#assignments">Завдання</a> &gt; 
                        <span id="current-assignment-breadcrumb"><?php echo htmlspecialchars($assignment_data['title']); ?></span>
                    </div>
                </div>

                <div class="assignment-detail-wrapper-for-centering">
                    <div class="assignment-detail-container">
                        <div class="assignment-header-details">
                            <h1><?php echo htmlspecialchars($assignment_data['title']); ?></h1>
                            <div class="assignment-meta">
                                <?php if($assignment_data['section_title']): ?>
                                    <span class="meta-item section"><i class="fas fa-folder-open"></i> Розділ: <?php echo htmlspecialchars($assignment_data['section_title']); ?></span>
                                <?php endif; ?>
                                <span class="meta-item points"><i class="fas fa-star"></i> Макс. балів: <?php echo htmlspecialchars($assignment_data['max_points']); ?></span>
                                <?php if ($assignment_data['due_date']): ?>
                                    <span class="meta-item due-date <?php
                                        $due_date_obj = new DateTime($assignment_data['due_date']);
                                        $now = new DateTime();
                                        if ($due_date_obj < $now) echo 'past-due';
                                        elseif (($now->diff($due_date_obj))->days <=3 && !$now->diff($due_date_obj)->invert) echo 'due-soon';
                                    ?>">
                                        <i class="fas fa-calendar-times"></i> Здати до: <?php echo $due_date_obj->format('d.m.Y H:i'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="assignment-description-full">
                            <h3>Опис завдання:</h3>
                            <p><?php echo nl2br(htmlspecialchars($assignment_data['description'] ?? 'Опис відсутній.')); ?></p>
                        </div>

                        <div id="teacherAttachmentsArea" class="teacher-attachments-section" style="margin-top: 20px; display: none;">
                            <h4><i class="fas fa-paperclip"></i> Прикріплені файли:</h4>
                            <ul id="teacherAttachmentsList" class="attachments-list"> 
                            </ul>
                        </div>

                        <hr class="assignment-divider">

                        <?php if (!$is_teacher_of_this_course):?>
                            <div id="studentSubmissionArea">
                                <p>Завантаження інформації про здачу...</p>
                            </div>
                        <?php else: ?>
                            <div id="teacherAssignmentActions">
                                <h2>Дії викладача</h2>
                                <a href="submissions_view.php?assignment_id=<?php echo $assignment_id_get; ?>" class="button-link view-submissions-link">
                                    <i class="fas fa-list-check"></i> Переглянути здані роботи
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="course-not-found">
                    <h1>Помилка</h1>
                    <p>Завдання з ID <?php echo htmlspecialchars($_GET['assignment_id'] ?? 'невідомим'); ?> не знайдено або у вас немає до нього доступу.</p>
                    <a href="home.php" class="button">Повернутися на головну</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        const ASSIGNMENT_ID_GLOBAL = <?php echo $assignment_id_get ? json_encode((int)$assignment_id_get) : 'null'; ?>;
        const IS_TEACHER = <?php echo json_encode($is_teacher_of_this_course); ?>;
        const WEB_ROOT_REL_FROM_HTML_ASSIGNMENT_VIEW = '<?php echo WEB_ROOT_REL_FROM_HTML_ASSIGNMENT_VIEW; ?>';
    </script>
    <script src="../js/assignment_view.js"></script>
</body>
</html>