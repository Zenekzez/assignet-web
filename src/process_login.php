<?php
session_start();

require_once 'connect.php'; // Підключення до бази даних

$errors = [];
$formData = []; // Для збереження введеного логіна/пошти

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_identifier = trim($_POST['login_identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    $formData['login_identifier'] = $login_identifier; // Зберігаємо для відновлення у формі

    // 1. Валідація на порожні поля
    if (empty($login_identifier)) {
        $errors['login_identifier'] = "Введіть вашу пошту або юзернейм.";
    }
    if (empty($password)) {
        $errors['password'] = "Введіть ваш пароль.";
    }

    // 2. Якщо поля не порожні, шукаємо користувача
    if (empty($errors)) {
        // Визначаємо, чи введено email чи username
        // Проста перевірка: якщо містить '@', вважаємо email
        $is_email = filter_var($login_identifier, FILTER_VALIDATE_EMAIL);

        if ($is_email) {
            $sql = "SELECT user_id, username, password_hash FROM users WHERE email = ?";
        } else {
            $sql = "SELECT user_id, username, password_hash FROM users WHERE username = ?";
        }

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $login_identifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                // 3. Перевірка пароля
                if (password_verify($password, $user['password_hash'])) {
                    // Пароль вірний, успішний вхід
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username']; // Зберігаємо юзернейм для відображення

                    // Перенаправлення на головну сторінку або кабінет
                    // Наприклад, на home.php, який ти створив
                    header("Location: ../public/html/home.php"); // Якщо home.php в тій же папці src
                                                  // Якщо home.php в public, то header("Location: ../public/home.php");
                    exit();
                } else {
                    // Невірний пароль
                    $errors['login_error'] = "Неправильна пошта/юзернейм або пароль.";
                }
            } else {
                // Користувача не знайдено
                $errors['login_error'] = "Неправильна пошта/юзернейм або пароль.";
            }
            $stmt->close();
        } else {
            $errors['login_error'] = "Помилка підготовки запиту до бази даних.";
            // Можна додати $conn->error для дебагу, але не для продакшену
            // $errors['db_debug'] = $conn->error;
        }
    }

    // 4. Якщо є помилки, повертаємо на сторінку входу
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $formData; // Зберігаємо введені дані
        header("Location: ../public/html/login.php");
        exit();
    }

} else {
    // Якщо хтось намагається отримати доступ до скрипту напряму
    header("Location: ../public/html/login.php");
    exit();
}

$conn->close();
?>