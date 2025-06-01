<?php
// File: src/process_login.php
// Додайте вибірку first_name, last_name, avatar_path, email та збереження їх у сесію

session_start();
require_once 'connect.php'; // Підключення до бази даних

$errors = [];
$formData = []; // Для збереження введеного логіна/пошти

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_identifier = trim($_POST['login_identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    $formData['login_identifier'] = $login_identifier;

    if (empty($login_identifier)) {
        $errors['login_identifier'] = "Введіть вашу пошту або юзернейм.";
    }
    if (empty($password)) {
        $errors['password'] = "Введіть ваш пароль.";
    }

    if (empty($errors)) {
        $is_email = filter_var($login_identifier, FILTER_VALIDATE_EMAIL);

        // Додаємо вибірку first_name, last_name, avatar_path ТА email
        if ($is_email) {
            // ЗМІНЕНО: додано `email` до запиту
            $sql = "SELECT user_id, username, email, password_hash, first_name, last_name, avatar_path FROM users WHERE email = ?";
        } else {
            // ЗМІНЕНО: додано `email` до запиту
            $sql = "SELECT user_id, username, email, password_hash, first_name, last_name, avatar_path FROM users WHERE username = ?";
        }

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $login_identifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    // Зберігаємо додаткові дані в сесію
                    $_SESSION['db_first_name'] = $user['first_name'];
                    $_SESSION['db_last_name'] = $user['last_name'];
                    $_SESSION['db_avatar_path'] = $user['avatar_path'];
                    $_SESSION['email'] = $user['email']; // ДОДАНО: Зберігаємо пошту в сесію

                    header("Location: ../public/html/home.php");
                    exit();
                } else {
                    $errors['login_error'] = "Неправильна пошта/юзернейм або пароль.";
                }
            } else {
                $errors['login_error'] = "Неправильна пошта/юзернейм або пароль.";
            }
            $stmt->close();
        } else {
            $errors['login_error'] = "Помилка підготовки запиту до бази даних.";
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $formData;
        header("Location: ../public/html/login.php");
        exit();
    }

} else {
    header("Location: ../public/html/login.php");
    exit();
}

$conn->close();
?>