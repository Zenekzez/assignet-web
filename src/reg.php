<?php
session_start();

require_once 'connect.php';
require_once 'password_hasher.php';

$form_errors = []; // Масив для помилок, які покажемо на формі

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Отримання даних
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $userPassword = $_POST['userPassword'] ?? ''; // Не trim() для паролів
    $checkPassword = $_POST['checkPassword'] ?? '';
    $policyAgreement = isset($_POST['policyAgreement']) && $_POST['policyAgreement'] === 'agreed';

    // --- СЕРВЕРНА ВАЛІДАЦІЯ (ОСТАННІЙ РУБІЖ) ---

    // Ім'я
    if (empty($firstName)) {
        $form_errors['firstName'] = "Ім'я не може бути порожнім.";
    } elseif (mb_strlen($firstName) < 2 || mb_strlen($firstName) > 30) {
        $form_errors['firstName'] = "Ім'я має містити від 2 до 30 символів.";
    } elseif (!preg_match("/^[A-ZА-ЯІЇЄҐ][a-zA-Zа-яА-ЯіІїЇєЄґҐ']*(?:-[A-ZА-ЯІЇЄҐ][a-zA-Zа-яА-ЯіІїЇєЄґҐ']*)?$/u", $firstName)) {
        $form_errors['firstName'] = "Ім'я має починатися з великої літери та може містити лише літери, дефіс або апостроф.";
    }

    // Прізвище (аналогічно до імені)
    if (empty($lastName)) {
        $form_errors['lastName'] = "Прізвище не може бути порожнім.";
    } elseif (mb_strlen($lastName) < 2 || mb_strlen($lastName) > 30) {
        $form_errors['lastName'] = "Прізвище має містити від 2 до 30 символів.";
    } elseif (!preg_match("/^[A-ZА-ЯІЇЄҐ][a-zA-Zа-яА-ЯіІїЇєЄґҐ']*(?:-[A-ZА-ЯІЇЄҐ][a-zA-Zа-яА-ЯіІїЇєЄґҐ']*)?$/u", $lastName)) {
        $form_errors['lastName'] = "Прізвище має починатися з великої літери та може містити лише літери, дефіс або апостроф.";
    }

    // Email
    if (empty($email)) {
        $form_errors['email'] = "Пошта не може бути порожньою.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_errors['email'] = "Введіть коректну адресу електронної пошти.";
    } elseif (mb_strlen($email) > 100) { // Згідно таблиці БД
        $form_errors['email'] = "Email занадто довгий (макс. 100).";
    }

    // Юзернейм
    if (empty($username)) {
        $form_errors['username'] = "Юзернейм не може бути порожнім.";
    } elseif (!preg_match("/^[a-zA-Z0-9_]{3,20}$/", $username)) { // Довжина згідно БД
        $form_errors['username'] = "Юзернейм: 3-20 символів (літери, цифри, '_').";
    }

    // Пароль
    if (empty($userPassword)) {
        $form_errors['userPassword'] = "Пароль не може бути порожнім.";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/", $userPassword)) {
        $form_errors['userPassword'] = "Пароль не відповідає вимогам безпеки.";
    }

    // Повтор пароля
    if (empty($checkPassword)) {
        $form_errors['checkPassword'] = "Повторіть пароль.";
    } elseif ($userPassword !== $checkPassword) {
        $form_errors['checkPassword'] = "Паролі не співпадають.";
    }

    // Згода з політикою
    if (!$policyAgreement) {
        $form_errors['policyAgreement'] = "Ви повинні погодитися з політикою конфіденційності.";
    }

    // Якщо немає базових помилок валідації, перевіряємо унікальність в БД (остаточна перевірка)
    if (empty($form_errors)) {
        // Перевірка унікальності username
        $stmt_user = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        if ($stmt_user) {
            $stmt_user->bind_param("s", $username);
            $stmt_user->execute();
            $stmt_user->store_result();
            if ($stmt_user->num_rows > 0) {
                $form_errors['username'] = "Користувач з таким юзернеймом вже існує (серверна перевірка).";
            }
            $stmt_user->close();
        } else {
            $form_errors['general'] = "Помилка сервера при перевірці юзернейма.";
        }


        // Перевірка унікальності email (тільки якщо ще немає помилок)
        if (empty($form_errors['username']) && empty($form_errors['general'])) { // Не перевіряти, якщо вже є помилка юзернейма або загальна
            $stmt_email = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            if ($stmt_email) {
                $stmt_email->bind_param("s", $email);
                $stmt_email->execute();
                $stmt_email->store_result();
                if ($stmt_email->num_rows > 0) {
                    $form_errors['email'] = "Користувач з таким email вже існує (серверна перевірка).";
                }
                $stmt_email->close();
            } else {
                 $form_errors['general'] = "Помилка сервера при перевірці email.";
            }
        }
    }

    // Якщо після всіх перевірок помилок немає, реєструємо користувача
    if (empty($form_errors)) {
        $hashedPassword = hashPassword($userPassword);

        if ($hashedPassword === false) {
            $form_errors['general'] = "Помилка системи безпеки. Спробуйте пізніше.";
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO users (username, password_hash, email, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
            if ($stmt_insert) {
                $stmt_insert->bind_param("sssss", $username, $hashedPassword, $email, $firstName, $lastName);
                if ($stmt_insert->execute()) {
                    // Успішна реєстрація
                    $_SESSION['user_id'] = $stmt_insert->insert_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['logged_in'] = true;

                    // Перенаправлення на сторінку входу або головну з повідомленням про успіх
                    // Важливо, щоб ../html/login.html міг обробляти GET-параметр ?status=reg_success
                    header("Location: ../html/login.html?status=reg_success");
                    $conn->close();
                    exit();
                } else {
                    $form_errors['general'] = "Помилка реєстрації. Спробуйте пізніше. Код: DB_EXEC";
                    // Для себе можеш логувати: error_log("MySQL execute error: " . $stmt_insert->error);
                }
                $stmt_insert->close();
            } else {
                $form_errors['general'] = "Помилка сервера. Спробуйте пізніше. Код: DB_PREP_INS";
                 // Для себе можеш логувати: error_log("MySQL prepare insert error: " . $conn->error);
            }
        }
    }

    // Якщо дісталися сюди, значить є помилки. Зберігаємо їх та дані форми в сесію.
    if (!empty($form_errors)) {
        $_SESSION['registration_form_errors'] = $form_errors;
        $formDataToReturn = $_POST; // Повертаємо всі дані, щоб заповнити форму
        unset($formDataToReturn['userPassword']); // Крім паролів
        unset($formDataToReturn['checkPassword']);
        $_SESSION['registration_form_data'] = $formDataToReturn;

        header("Location: ../html/registration_form.php"); // Перенаправляємо назад на форму
        $conn->close();
        exit();
    }

} else {
    // Якщо це не POST запит, просто перенаправляємо на форму реєстрації
    header("Location: ../html/registration_form.php");
    exit();
}
?>