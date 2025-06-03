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
$assignment_id_get = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);
$assignment_data = null;
$course_id_for_breadcrumb = null;
$course_name_for_breadcrumb = 'Курс';
$submissions = [];
$is_teacher_of_this_course = false;
$default_avatar_web_path = '../assets/default_avatar.png'; 

if (!$assignment_id_get) {
} else {
    $stmt_assignment = $conn->prepare(
        "SELECT a.assignment_id, a.title as assignment_title, a.course_id, a.due_date, c.course_name, c.author_id as course_author_id
         FROM assignments a
         JOIN courses c ON a.course_id = c.course_id
         WHERE a.assignment_id = ?"
    );
    if ($stmt_assignment) {
        $stmt_assignment->bind_param("i", $assignment_id_get);
        $stmt_assignment->execute();
        $result_assignment = $stmt_assignment->get_result();
        if ($row = $result_assignment->fetch_assoc()) {
            $assignment_data = $row;
            $course_id_for_breadcrumb = $row['course_id'];
            $course_name_for_breadcrumb = htmlspecialchars($row['course_name']);
            if ($current_user_id == $row['course_author_id']) {
                $is_teacher_of_this_course = true;
            }
        }
        $stmt_assignment->close();
    }

    if (!$assignment_data || !$is_teacher_of_this_course) {
        echo "<main class='page-content-wrapper'><div class='course-view-main-content'><div class='course-not-found'><h1>Доступ заборонено</h1><p>Ви не маєте прав для перегляду цієї сторінки або завдання не існує.</p><a href='home.php' class='button'>На головну</a></div></div></main>";
        exit();
    }

    $stmt_submissions = $conn->prepare(
        "SELECT u.user_id as student_id, u.username as student_username, u.first_name, u.last_name, u.avatar_path,
                s.submission_id, s.submission_date, s.file_path, s.submission_text, s.status, s.grade, s.graded_at
         FROM users u
         JOIN enrollments e ON u.user_id = e.student_id
         LEFT JOIN submissions s ON u.user_id = s.student_id AND s.assignment_id = ?
         WHERE e.course_id = ?
         ORDER BY u.last_name, u.first_name"
    );

    if ($stmt_submissions) {
        $stmt_submissions->bind_param("ii", $assignment_id_get, $assignment_data['course_id']);
        $stmt_submissions->execute();
        $result_submissions = $stmt_submissions->get_result();
        while ($row_sub = $result_submissions->fetch_assoc()) {
            $submissions[] = $row_sub;
        }
        $stmt_submissions->close();
    }
}

$page_title = "Здані роботи: " . ($assignment_data ? htmlspecialchars($assignment_data['assignment_title']) : 'Завдання');
?>

<title><?php echo $page_title; ?> - AssignNet</title>
<link rel="icon" href="public/assets/assignnet_logo.png" type="image/x-icon">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="../css/course_view_styles.css">
<link rel="stylesheet" href="../css/submissions_view.css">

<main class="page-content-wrapper"> 
    <div class="course-view-main-content">
        <?php if ($assignment_data && $is_teacher_of_this_course): ?>
            <div class="course-header-bar">
                <div class="breadcrumbs">
                    <a href="home.php">Мої курси</a> &gt;
                    <a href="course_view.php?course_id=<?php echo htmlspecialchars($assignment_data['course_id']); ?>"><?php echo $course_name_for_breadcrumb; ?></a> &gt;
                    <a href="course_view.php?course_id=<?php echo htmlspecialchars($assignment_data['course_id']); ?>#assignments">Завдання</a> &gt;
                    <a href="assignment_view.php?assignment_id=<?php echo $assignment_id_get; ?>"><?php echo htmlspecialchars($assignment_data['assignment_title']); ?></a> &gt;
                    <span>Здані роботи</span>
                </div>
            </div>

            <div class="submissions-container tab-pane active">
                <h2>Здані роботи для: "<?php echo htmlspecialchars($assignment_data['assignment_title']); ?>"</h2>

                <?php if (!empty($submissions)): ?>
                    <table class="submissions-table">
                        <thead>
                            <tr>
                                <th>Студент</th>
                                <th>Дата здачі</th>
                                <th>Статус</th>
                                <th>Оцінка</th>
                                <th>Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $sub): ?>
                                <tr>
                                    <td data-label="Студент" class="student-name-cell">
                                        <div class="student-name-cell-content-wrapper">
                                            <?php
                                                $avatar_path_display = $default_avatar_web_path; 
                                                if (!empty($sub['avatar_path'])) {
                                                    if ($sub['avatar_path'] === 'assets/default_avatar.png') { 
                                                        $avatar_path_display = '../' . $sub['avatar_path'];
                                                    } else { 
                                                        $avatar_path_display = '../' . htmlspecialchars($sub['avatar_path'], ENT_QUOTES, 'UTF-8');
                                                    }
                                                }
                                            ?>
                                            <img src="<?php echo $avatar_path_display; ?>?t=<?php echo time(); ?>" alt="Аватар" class="student-avatar-in-table">
                                            <div class="student-info-container">
                                                <?php echo htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']); ?><br>
                                                <small>@<?php echo htmlspecialchars($sub['student_username']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Дата здачі"><?php echo $sub['submission_date'] ? (new DateTime($sub['submission_date']))->format('d.m.Y H:i') : '(-)'; ?></td>
                                    <td data-label="Статус">
                                        <?php
                                        $statusText = 'Не здано'; $statusClass = 'pending';
                                        if ($sub['status']) {
                                            switch($sub['status']) {
                                                case 'submitted': $statusText = 'Здано'; $statusClass = 'submitted'; break;
                                                case 'graded': $statusText = 'Оцінено'; $statusClass = 'graded'; break;
                                                case 'missed': $statusText = 'Пропущено'; $statusClass = 'missed'; break; 
                                                default: $statusText = htmlspecialchars($sub['status']);
                                            }
                                        } elseif (!$sub['submission_id'] && $assignment_data['due_date'] && new DateTime($assignment_data['due_date']) < new DateTime()) {
                                            $statusText = 'Пропущено'; $statusClass = 'missed';
                                        }
                                        echo "<span class='submission-status-table {$statusClass}'>{$statusText}</span>";
                                        ?>
                                    </td>
                                    <td data-label="Оцінка"><?php echo $sub['grade'] !== null ? htmlspecialchars($sub['grade']) : '–'; ?></td>
                                    <td data-label="Дії">
                                        <?php if ($sub['submission_id']): ?>
                                            <a href="grade_submission.php?submission_id=<?php echo $sub['submission_id']; ?>" class="button-link grade-link">
                                                <i class="fas fa-edit"></i> <?php echo $sub['grade'] !== null ? 'Змінити оцінку' : 'Оцінити'; ?>
                                            </a>
                                        <?php else: ?>
                                            <span>–</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-submissions-message">Ще ніхто не здав це завдання, або в курсі немає студентів.</p>
                <?php endif; ?>
            </div>

        <?php elseif (!$assignment_data && $assignment_id_get): ?>
            <div class="course-not-found">
                <h1>Помилка</h1>
                <p>Завдання з ID <?php echo htmlspecialchars($assignment_id_get); ?> не знайдено.</p>
                <a href="home.php" class="button">На головну</a>
            </div>
        <?php elseif (!$assignment_id_get): ?>
             <div class="course-not-found">
                <h1>Помилка</h1>
                <p>ID завдання не було передано.</p>
                <a href="home.php" class="button">На головну</a>
            </div>
        <?php endif; ?>
    </div>
</main> 

</div> </body>
</html>