<?php
session_start(); 

$errors = $_SESSION['errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];

unset($_SESSION['errors']);
unset($_SESSION['form_data']);
?>

<!DOCTYPE html>
<html lang="uk"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реєстрація - AssignNet</title> 
    <link rel="icon" href="public/assets/assignnet_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/styles.css"> </head>
<body>

<div class="container">
    <h1 class="regHeader">Реєстрація</h1> <form action="../../src/auth/process_registration.php" method="post" autocomplete="on" onsubmit="return validateForm()" id="registrationForm">
        <div class="form-row">
            <div class="input-container <?php echo isset($errors['firstName']) ? 'error' : ''; ?>">
                <label for="firstNid" class="iftaLabel">Ім'я</label>
                <input type="text" id="firstNid" class="inputField" name="firstName" placeholder="Кевін"
                       value="<?php echo htmlspecialchars($formData['firstName'] ?? ''); ?>" autofocus required>
                <small class="error-message">
                    <?php echo htmlspecialchars($errors['firstName'] ?? ''); ?>
                </small>
            </div>

            <div class="input-container <?php echo isset($errors['lastName']) ? 'error' : ''; ?>">
                <label for="lastNid" class="iftaLabel">Прізвище</label>
                <input type="text" id="lastNid" class="inputField" name="lastName" placeholder="Джексон"
                       value="<?php echo htmlspecialchars($formData['lastName'] ?? ''); ?>" required>
                <small class="error-message">
                    <?php echo htmlspecialchars($errors['lastName'] ?? ''); ?>
                </small>
            </div>
        </div>

        <div class="form-row">
            <div class="input-container <?php echo isset($errors['email']) ? 'error' : ''; ?>">
                <label for="emailId" class="iftaLabel">Пошта</label>
                <input type="email" id="emailId" class="inputField" name="email" placeholder="kevinj@mail.com"
                       value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                <small class="error-message">
                    <?php echo htmlspecialchars($errors['email'] ?? ''); ?>
                </small>
            </div>
            <div class="input-container <?php echo isset($errors['username']) ? 'error' : ''; ?>">
                <label for="userId" class="iftaLabel">Придумайте юзернейм</label>
                <input type="text" id="userId" class="inputField" name="username" placeholder="jackSON47"
                       value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>" required>
                <small class="error-message">
                    <?php echo htmlspecialchars($errors['username'] ?? ''); ?>
                </small>
            </div>
        </div>

        <div class="form-row">
            <div class="input-container <?php echo isset($errors['userPassword']) ? 'error' : ''; ?>">
                <label for="passId" class="iftaLabel">Придумайте пароль</label>
                <input type="password" id="passId" class="inputField" name="userPassword" placeholder="Від 8 символів" required>
                <small class="error-message">
                    <?php echo htmlspecialchars($errors['userPassword'] ?? ''); ?>
                </small>
            </div>

            <div class="input-container <?php echo isset($errors['checkPassword']) ? 'error' : ''; ?>">
                <label for="passCheckId" class="iftaLabel">Повторіть пароль</label>
                <input type="password" id="passCheckId" class="inputField" name="checkPassword" placeholder="********" required>
                <small class="error-message">
                    <?php echo htmlspecialchars($errors['checkPassword'] ?? ''); ?>
                </small>
            </div>
        </div>

        <div class="password-rules"> <p>Пароль має містити:</p> <ul>
                <li>Мінімум 8 символів</li> <li>Принаймні одну велику літеру (A-Z)</li> <li>Принаймні одну маленьку літеру (a-z)</li> <li>Принаймні одну цифру (0-9)</li> </ul>
        </div>

        <div class="agreement-container <?php echo isset($errors['policyAgreement']) ? 'error' : ''; ?>">
            <input type="checkbox" id="policyAgreement" name="policyAgreement" value="agreed" required
                   <?php echo !empty($formData['policyAgreement']) ? 'checked' : ''; ?>>
            <label for="policyAgreement">
                Я погоджуюся з <a href="../../policy.html" target="_blank" rel="noopener noreferrer">політикою конфіденційності компанії</a>. </label>
            <small class="error-message policy-error-message">
                <?php echo htmlspecialchars($errors['policyAgreement'] ?? ''); ?>
            </small>
        </div>

        <?php if (isset($errors['db_error'])): ?>
            <div class="input-container error" style="text-align: center; border: 1px solid var(--red); padding: 10px; margin-bottom:15px; background-color: #ffebee; border-radius: 5px;">
                <small class="error-message" style="display:block; color: var(--red);"><?php echo htmlspecialchars($errors['db_error']); ?></small>
            </div>
        <?php endif; ?>

        <button type="submit" class="submit-button">Створити акаунт</button> <span id="alreadyHave">Вже маєте акаунт? <a href="login.php">Увійти</a></span> </form>
</div>

<script src="../js/reg.js"></script> </body>
</html>