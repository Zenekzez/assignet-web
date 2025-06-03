<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    require_once __DIR__ . '/templates/layout.php';

    $current_first_name = htmlspecialchars($_SESSION['db_first_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $current_last_name = htmlspecialchars($_SESSION['db_last_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $current_username_php = htmlspecialchars($_SESSION['username'] ?? 'Невідомий користувач', ENT_QUOTES, 'UTF-8'); 
    $current_user_email_php = htmlspecialchars($_SESSION['email'] ?? 'Не вказано', ENT_QUOTES, 'UTF-8');

    $default_avatar_web_path = '../assets/default_avatar.png';
    $avatar_display_path = $default_avatar_web_path;

    if (!empty($_SESSION['db_avatar_path']) && $_SESSION['db_avatar_path'] !== 'assets/default_avatar.png') {
         $avatar_display_path = '../' . htmlspecialchars($_SESSION['db_avatar_path'], ENT_QUOTES, 'UTF-8');
    } else if (!empty($_SESSION['db_avatar_path']) && $_SESSION['db_avatar_path'] === 'assets/default_avatar.png'){
        $avatar_display_path = '../' . htmlspecialchars($_SESSION['db_avatar_path'], ENT_QUOTES, 'UTF-8');
    }

    $base_avatar_url_for_js = '../';
?>

<title>Налаштування - AssignNet</title>
<link rel="icon" href="public/assets/assignnet_logo.png" type="image/x-icon">
<link rel="stylesheet" href="../css/settings_styles.css">
<main class="page-content-wrapper settings-main-content">
    <div class="settings-container">
        <h1>Налаштування профілю</h1>

        <div id="messageContainer"></div>

        <section class="settings-section">
            <h2>Змінити аватарку</h2>
            <form id="avatarForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="avatarFile">Виберіть файл (PNG, JPEG, до 2MB)</label>
                    <div class="avatar-preview" id="avatarPreview" style="background-image: url('<?php echo $avatar_display_path; ?>?t=<?php echo time(); ?>');">
                    </div>
                    <input type="file" id="avatarFile" name="avatarFile" accept="image/png, image/jpeg">
                    <small id="avatarError" class="error-message" style="color: red; display: none;"></small>
                </div>
                <button type="submit" class="settings-button">Завантажити аватарку</button>
            </form>
        </section>

        <hr>

        <section class="settings-section">
            <h2>Особиста інформація</h2>
            <p><strong>Ім'я:</strong> <span id="displayFirstName"><?php echo $current_first_name ?: 'Не вказано'; ?></span></p>
            <p><strong>Прізвище:</strong> <span id="displayLastName"><?php echo $current_last_name ?: 'Не вказано'; ?></span></p>
            <p><strong>Юзернейм:</strong> <span id="displayUsername"><?php echo $current_username_php; ?></span></p>
            <hr>
            <form id="profileInfoForm">
                <div class="form-group">
                    <label for="firstName">Змінити ім'я</label>
                    <input type="text" id="firstName" name="firstName" value="<?php echo $current_first_name; ?>" required>
                </div>
                <div class="form-group">
                    <label for="lastName">Змінити прізвище</label>
                    <input type="text" id="lastName" name="lastName" value="<?php echo $current_last_name; ?>" required>
                </div>
                <div class="form-group">
                    <label for="usernameInputSettings">Змінити юзернейм</label>
                    <input type="text" id="usernameInputSettings" name="username" value="<?php echo $current_username_php; ?>" required>
                    <small>Від 3 до 20 символів (літери, цифри, '_')</small>
                </div>
                <button type="submit" class="settings-button">Зберегти інформацію</button>
            </form>
        </section>

        <hr>
        <section class="settings-section">
            <h2>Змінити електронну пошту</h2>
            <form id="emailChangeForm">
                <div class="form-group">
                    <label for="currentEmailDisplay">Поточна пошта</label>
                    <input type="email" id="currentEmailDisplay" name="currentEmailDisplay" value="<?php echo $current_user_email_php; ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="newEmail">Нова електронна пошта</label>
                    <input type="email" id="newEmail" name="newEmail" required>
                    <small id="newEmailError" class="error-message" style="color: red; display: none;"></small>
                </div>
                <div class="form-group">
                    <label for="passwordForEmailChange">Ваш поточний пароль (для підтвердження)</label>
                    <input type="password" id="passwordForEmailChange" name="passwordForEmailChange" required>
                </div>
                <button type="submit" class="settings-button">Змінити пошту</button>
            </form>
        </section>
        <hr>

        <section class="settings-section">
            <h2>Змінити пароль</h2>
            <form id="passwordChangeForm">
                <div class="form-group">
                    <label for="currentPassword">Поточний пароль</label>
                    <input type="password" id="currentPassword" name="currentPassword" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">Новий пароль</label>
                    <input type="password" id="newPassword" name="newPassword" required>
                    <small>Мінімум 8 символів, одна велика, одна маленька літера, одна цифра.</small>
                </div>
                <div class="form-group">
                    <label for="confirmNewPassword">Підтвердіть новий пароль</label>
                    <input type="password" id="confirmNewPassword" name="confirmNewPassword" required>
                </div>
                <button type="submit" class="settings-button">Змінити пароль</button>
            </form>
        </section>

        <hr>

        <section class="settings-section">
            <h2>Вихід з акаунту</h2>
            <a href="../../src/auth/logout.php" class="logout-button"><i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i>Вийти</a>
        </section>
    </div>
</main>

</div>

<script>
    const BASE_AVATAR_URL_SETTINGS_JS = <?php echo json_encode($base_avatar_url_for_js); ?>;
    const CURRENT_USER_EMAIL_PHP_JS = <?php echo json_encode($current_user_email_php); ?>;
</script>

<script src="../js/settings.js"></script>

</body>
</html>