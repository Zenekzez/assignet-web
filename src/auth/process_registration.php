<?php
session_start(); 
require_once __DIR__ . '/../connect.php';

$errors = [];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['userPassword'] ?? '';
    $checkPassword = $_POST['checkPassword'] ?? '';
    $policyAgreement = isset($_POST['policyAgreement']); 


    if (empty($firstName)) {
        $errors['firstName'] = "Ім'я не може бути порожнім.";
    } elseif (mb_strlen($firstName) < 2 || mb_strlen($firstName) > 30) {
        $errors['firstName'] = "Ім'я має містити від 2 до 30 символів.";
    } elseif (!preg_match("/^[A-ZА-ЯІЇЄҐ][a-zA-Zа-яА-ЯіІїЇєЄґҐ']*(?:-[A-ZА-ЯІЇЄҐ][a-zA-Zа-яА-ЯіІїЇєЄґҐ']*)?$/u", $firstName)) {
        $errors['firstName'] = "Ім'я має починатися з великої літери та може містити лише літери, дефіс або апостроф.";
    }


    if (empty($lastName)) {
        $errors['lastName'] = "Прізвище не може бути порожнім.";
    } elseif (mb_strlen($lastName) < 2 || mb_strlen($lastName) > 30) {
        $errors['lastName'] = "Прізвище має містити від 2 до 30 символів.";
    } elseif (!preg_match("/^[A-ZА-ЯІЇЄҐ][a-zA-Zа-яА-ЯіІїЇєЄґҐ']*(?:-[A-ZА-ЯІЇЄҐ][a-zA-Zа-яА-ЯіІїЇєЄґҐ']*)?$/u", $lastName)) {
        $errors['lastName'] = "Прізвище має починатися з великої літери та може містити лише літери, дефіс або апостроф.";
    }

    
    if (empty($email)) {
        $errors['email'] = "Пошта не може бути порожньою.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Введіть коректну адресу електронної пошти.";
    } elseif (mb_strlen($email) > 100) { 
        $errors['email'] = "Адреса електронної пошти занадто довга (максимум 100 символів).";
    }


    if (empty($username)) {
        $errors['username'] = "Юзернейм не може бути порожнім.";
    } elseif (!preg_match("/^[a-zA-Z0-9_]{3,20}$/", $username)) { 
        $errors['username'] = "Юзернейм: 3-20 символів (літери, цифри, '_').";
    }

    
    if (empty($password)) {
        $errors['userPassword'] = "Пароль не може бути порожнім.";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
        $errors['userPassword'] = "Пароль не відповідає вимогам безпеки (мін. 8 символів, одна велика, одна маленька літера, одна цифра).";
    } elseif (mb_strlen($password) > 255) { 
         $errors['userPassword'] = "Пароль занадто довгий.";
    }

    
    if (empty($checkPassword)) {
        $errors['checkPassword'] = "Повторіть пароль.";
    } elseif ($password !== $checkPassword) {
        $errors['checkPassword'] = "Паролі не співпадають.";
    }

    
    if (!$policyAgreement) {
        $errors['policyAgreement'] = "Ви повинні погодитися з політикою конфіденційності.";
    }


    if (empty($errors['email'])) { 
        $sql_check_email = "SELECT user_id FROM users WHERE email = ?";
        $stmt_check_email = $conn->prepare($sql_check_email);
        if ($stmt_check_email) {
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();
            if ($stmt_check_email->num_rows > 0) {
                $errors['email'] = "Ця електронна пошта вже зареєстрована.";
            }
            $stmt_check_email->close();
        } else {
             $errors['email_unique_check_fail'] = "Серверна помилка перевірки пошти.";
        }
    }


    if (empty($errors['username'])) { 
        $sql_check_username = "SELECT user_id FROM users WHERE username = ?";
        $stmt_check_username = $conn->prepare($sql_check_username);
        if ($stmt_check_username) {
            $stmt_check_username->bind_param("s", $username);
            $stmt_check_username->execute();
            $stmt_check_username->store_result();
            if ($stmt_check_username->num_rows > 0) {
                $errors['username'] = "Цей юзернейм вже зайнятий.";
            }
            $stmt_check_username->close();
        } else {
             $errors['username_unique_check_fail'] = "Серверна помилка перевірки юзернейма.";
        }
    }


    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql_insert_user = "INSERT INTO users (username, password_hash, email, first_name, last_name) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert_user = $conn->prepare($sql_insert_user);

        if ($stmt_insert_user) {
            $stmt_insert_user->bind_param("sssss", $username, $password_hash, $email, $firstName, $lastName);

            if ($stmt_insert_user->execute()) {
                $_SESSION['user_id'] = $stmt_insert_user->insert_id;
                $_SESSION['username'] = $username;
                header("Location: ../public/pages/login.php?registration=success");
                exit();
            } else {
                $errors['db_error'] = "Помилка реєстрації: " . $stmt_insert_user->error;
            }
            $stmt_insert_user->close();
        } else {
            $errors['db_error'] = "Помилка підготовки запиту для реєстрації: " . $conn->error;
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST; 
        header("Location: ../public/pages/reg.php"); 
        exit();
    }

} else {
    header("Location: ../public/pages/reg.php"); 
    exit();
}

$conn->close();
?>