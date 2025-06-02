<?php
session_start(); // Потрібно для доступу до змінних сесії

// Отримуємо помилки з сесії, якщо вони є
$errors = $_SESSION['errors'] ?? [];
// Отримуємо дані форми з сесії (якщо потрібно відновлювати логін/пошту)
$formData = $_SESSION['form_data'] ?? [];

// Очищаємо помилки та дані з сесії, щоб вони не з'являлися знову
unset($_SESSION['errors']);
unset($_SESSION['form_data']);

// Перевірка, чи є повідомлення про успішну реєстрацію
$registration_success_message = '';
if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
    $registration_success_message = "Реєстрація успішна! Тепер ви можете увійти.";
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вхід - Assignet</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Додатковий стиль для повідомлення про успішну реєстрацію */
        .success-message-box {
            background-color: #e6ffed;
            border: 1px solid var(--green);
            color: var(--green);
            padding: 15px;
            margin: 10px 10px 20px 10px;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container" style="max-height: fit-content; height: auto; min-height: 450px;"> <h1 class="regHeader">Вхід</h1>

    <?php if ($registration_success_message): ?>
        <div class="success-message-box">
            <?php echo htmlspecialchars($registration_success_message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($errors['login_error'])): ?>
        <div class="input-container error" style="text-align: center; border: 1px solid var(--red); padding: 10px; margin: 10px 10px 15px 10px; background-color: #ffebee; border-radius: 5px;">
            <small class="error-message" style="display:block; color: var(--red);"><?php echo htmlspecialchars($errors['login_error']); ?></small>
        </div>
    <?php endif; ?>

    <form action="../../src/process_login.php" method="post" autocomplete="on" id="loginForm">
        <div class="input-container login-field-spacing <?php echo isset($errors['login_identifier']) ? 'error' : ''; ?>">
            <label for="loginIdentifierId" class="iftaLabel">Пошта або юзернейм</label>
            <input type="text" id="loginIdentifierId" class="inputField" name="login_identifier" placeholder="Ваша пошта або юзернейм"
                   value="<?php echo htmlspecialchars($formData['login_identifier'] ?? ''); ?>" autofocus required>
            <small class="error-message">
                <?php echo htmlspecialchars($errors['login_identifier'] ?? ''); ?>
            </small>
        </div>

        <div class="input-container login-field-spacing <?php echo isset($errors['password']) ? 'error' : ''; ?>">
            <label for="passId" class="iftaLabel">Пароль</label>
            <input type="password" id="passId" class="inputField" name="password" placeholder="Ваш пароль" required>
            <small class="error-message">
                <?php echo htmlspecialchars($errors['password'] ?? ''); ?>
            </small>
        </div>

        <button type="submit" class="submit-button">Увійти</button>
        <span id="alreadyHave">Ще не зареєстровані? <a href="reg.php">Створити акаунт</a></span>
    </form>
</div>

</body>
</html>