<?php
session_start(); // Завжди на початку PHP файлів, що працюють з сесіями

// Отримуємо помилки та дані форми з сесії, якщо вони є
$php_form_errors = $_SESSION['registration_form_errors'] ?? [];
$php_form_data = $_SESSION['registration_form_data'] ?? [];

// Очищаємо їх з сесії, щоб вони не показувалися знову при оновленні сторінки
unset($_SESSION['registration_form_errors']);
unset($_SESSION['registration_form_data']);
?>
<!DOCTYPE html>
<html lang="uk"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реєстрація</title> <link rel="stylesheet" href="../css/styles.css">
    <style>
        .php-error-message {
            color: #D8000C; /* Темно-червоний */
            font-size: 0.8em;
            display: block;
            margin-top: 3px;
        }
        /* Можеш додати клас до input-container, якщо є PHP помилка для нього */
        .input-container.php-error .inputField {
            border-color: #D8000C;
        }
         .input-container.php-error .iftaLabel {
            color: #D8000C;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="regHeader">Реєстрація</h1>

    <?php if (!empty($php_form_errors) && isset($php_form_errors['general'])): ?>
        <div style="color: #D8000C; background-color: #FFD2D2; border: 1px solid #D8000C; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
            <?php echo htmlspecialchars($php_form_errors['general']); ?>
        </div>
    <?php endif; ?>
     <?php if (!empty($php_form_errors) && count(array_filter(array_keys($php_form_errors), 'is_string')) > (isset($php_form_errors['general']) ? 1:0) ): // Показати, якщо є помилки полів ?>
        <div style="color: #D8000C; background-color: #FFD2D2; border: 1px solid #D8000C; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
            <strong>Будь ласка, виправте наступні помилки:</strong>
            <ul>
                <?php foreach ($php_form_errors as $field => $message): ?>
                    <?php if($field !== 'general'): // Не дублювати загальну помилку ?>
                    <li><?php echo htmlspecialchars($message); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>


    <form action="../php/reg.php" method="post" autocomplete="on" onsubmit="return validateForm()" id="registrationForm">
        <div class="form-row">
            <div class="input-container <?php echo isset($php_form_errors['firstName']) ? 'php-error' : ''; ?>">
                <label for="firstNid" class="iftaLabel">Ім'я</label>
                <input type="text" id="firstNid" class="inputField" name="firstName" placeholder="Кевін"
                       value="<?php echo htmlspecialchars($php_form_data['firstName'] ?? ''); ?>" autofocus >
                <small class="error-message"></small> <?php if (isset($php_form_errors['firstName'])): ?>
                    <small class="php-error-message"><?php echo htmlspecialchars($php_form_errors['firstName']); ?></small>
                <?php endif; ?>
            </div>

            <div class="input-container <?php echo isset($php_form_errors['lastName']) ? 'php-error' : ''; ?>">
                <label for="lastNid" class="iftaLabel">Прізвище</label>
                <input type="text" id="lastNid" class="inputField" name="lastName" placeholder="Джексон"
                       value="<?php echo htmlspecialchars($php_form_data['lastName'] ?? ''); ?>" >
                <small class="error-message"></small>
                <?php if (isset($php_form_errors['lastName'])): ?>
                    <small class="php-error-message"><?php echo htmlspecialchars($php_form_errors['lastName']); ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="input-container <?php echo isset($php_form_errors['email']) ? 'php-error' : ''; ?>">
                <label for="emailId" class="iftaLabel">Пошта</label>
                <input type="email" id="emailId" class="inputField" name="email" placeholder="kevinj@mail.com"
                       value="<?php echo htmlspecialchars($php_form_data['email'] ?? ''); ?>" >
                <small class="error-message"></small>
                <?php if (isset($php_form_errors['email'])): ?>
                    <small class="php-error-message"><?php echo htmlspecialchars($php_form_errors['email']); ?></small>
                <?php endif; ?>
            </div>
            <div class="input-container <?php echo isset($php_form_errors['username']) ? 'php-error' : ''; ?>">
                <label for="userId" class="iftaLabel">Придумайте юзернейм</label>
                <input type="text" id="userId" class="inputField" name="username" placeholder="jackSON47"
                       value="<?php echo htmlspecialchars($php_form_data['username'] ?? ''); ?>" >
                <small class="error-message"></small>
                <?php if (isset($php_form_errors['username'])): ?>
                    <small class="php-error-message"><?php echo htmlspecialchars($php_form_errors['username']); ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="input-container <?php echo isset($php_form_errors['userPassword']) ? 'php-error' : ''; ?>">
                <label for="passId" class="iftaLabel">Придумайте пароль</label>
                <input type="password" id="passId" class="inputField" name="userPassword" placeholder="Від 8 символів" >
                <small class="error-message"></small>
                <?php if (isset($php_form_errors['userPassword'])): ?>
                    <small class="php-error-message"><?php echo htmlspecialchars($php_form_errors['userPassword']); ?></small>
                <?php endif; ?>
            </div>

            <div class="input-container <?php echo isset($php_form_errors['checkPassword']) ? 'php-error' : ''; ?>">
                <label for="passCheckId" class="iftaLabel">Повторіть пароль</label>
                <input type="password" id="passCheckId" class="inputField" name="checkPassword" placeholder="********" >
                <small class="error-message"></small>
                <?php if (isset($php_form_errors['checkPassword'])): ?>
                    <small class="php-error-message"><?php echo htmlspecialchars($php_form_errors['checkPassword']); ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="password-rules" style="display:none;"> <p>Пароль має містити:</p>
            <ul>
                <li>Мінімум 8 символів</li>
                <li>Принаймні одну велику літеру (A-Z)</li>
                <li>Принаймні одну маленьку літеру (a-z)</li>
                <li>Принаймні одну цифру (0-9)</li>
            </ul>
        </div>

        <div class="agreement-container <?php echo isset($php_form_errors['policyAgreement']) ? 'php-error' : ''; ?>">
            <input type="checkbox" id="policyAgreement" name="policyAgreement" value="agreed"
                   <?php echo (isset($php_form_data['policyAgreement']) && $php_form_data['policyAgreement'] === 'agreed') ? 'checked' : ''; ?>>
            <label for="policyAgreement">
                Я погоджуюся з <a href="policy.html" target="_blank" rel="noopener noreferrer">політикою конфіденційності компанії</a>.
            </label>
            <small class="error-message policy-error-message"></small> <?php if (isset($php_form_errors['policyAgreement'])): ?>
                <small class="php-error-message"><?php echo htmlspecialchars($php_form_errors['policyAgreement']); ?></small>
            <?php endif; ?>
        </div>

        <button type="submit" class="submit-button">Створити акаунт</button>
        <span id="alreadyHave">Вже маєте акаунт? <a href="login.html">Увійти</a></span>
    </form>
</div>

<script src = "../js/reg.js"></script>
</body>
</html>