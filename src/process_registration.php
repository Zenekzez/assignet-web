<?php
session_start(); // Розпочинаємо сесію на самому початку

// Підключення до бази даних
require_once 'connect.php'; // Переконайся, що шлях правильний

// Масив для зберігання помилок валідації
$errors = [];

// Перевіряємо, чи дані надіслані методом POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Отримання та базова санітизація даних
    // Використовуємо trim() для видалення зайвих пробілів
    // Подальша санітизація буде відбуватися перед запитом до БД
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['userPassword'] ?? ''; // trim не потрібен для пароля перед хешуванням
    $checkPassword = $_POST['checkPassword'] ?? '';
    $policyAgreement = isset($_POST['policyAgreement']); // true, якщо галочка стоїть

    // 2. Серверна валідація (приклади, розшир за потребою)

    // Ім'я
    if (empty($firstName)) {
        $errors['firstName'] = "Ім'я не може бути порожнім.";
    } elseif (mb_strlen($firstName) < 2 || mb_strlen($firstName) > 30) {
        $errors['firstName'] = "Ім'я має містити від 2 до 30 символів.";
    } elseif (!preg_match("/^[A-ZА-ЯІЇЄҐ][a-zA-Zа-яА-ЯіІїЇєЄґҐ']*(?:-[A-ZА-ЯІЇЄҐ][a-zA-Zа-яА-ЯіІїЇєЄґҐ']*)?$/u", $firstName)) {
        $errors['firstName'] = "Ім'я має починатися з великої літери та може містити лише літери, дефіс або апостроф.";
    }

    // Прізвище (аналогічно до імені)
    if (empty($lastName)) {
        $errors['lastName'] = "Прізвище не може бути порожнім.";
    } elseif (mb_strlen($lastName) < 2 || mb_strlen($lastName) > 30) {
        $errors['lastName'] = "Прізвище має містити від 2 до 30 символів.";
    } elseif (!preg_match("/^[A-ZА-ЯІЇЄҐ][a-zA-Zа-яА-ЯіІїЇєЄґҐ']*(?:-[A-ZА-ЯІЇЄҐ][a-zA-Zа-яА-ЯіІїЇєЄґҐ']*)?$/u", $lastName)) {
        $errors['lastName'] = "Прізвище має починатися з великої літери та може містити лише літери, дефіс або апостроф.";
    }

    // Пошта
    if (empty($email)) {
        $errors['email'] = "Пошта не може бути порожньою.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Введіть коректну адресу електронної пошти.";
    } elseif (mb_strlen($email) > 100) { // Відповідно до структури БД
        $errors['email'] = "Адреса електронної пошти занадто довга (максимум 100 символів).";
    }


    // Юзернейм
    if (empty($username)) {
        $errors['username'] = "Юзернейм не може бути порожнім.";
    } elseif (!preg_match("/^[a-zA-Z0-9_]{3,20}$/", $username)) { // Змінив 15 на 20, як у БД
        $errors['username'] = "Юзернейм: 3-20 символів (літери, цифри, '_').";
    }

    // Пароль
    if (empty($password)) {
        $errors['userPassword'] = "Пароль не може бути порожнім.";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
        $errors['userPassword'] = "Пароль не відповідає вимогам безпеки (мін. 8 символів, одна велика, одна маленька літера, одна цифра).";
    } elseif (mb_strlen($password) > 255) { // Хоча хеш буде фіксованої довжини, вихідний пароль теж варто обмежити
         $errors['userPassword'] = "Пароль занадто довгий.";
    }


    // Перевірка пароля
    if (empty($checkPassword)) {
        $errors['checkPassword'] = "Повторіть пароль.";
    } elseif ($password !== $checkPassword) {
        $errors['checkPassword'] = "Паролі не співпадають.";
    }

    // Політика конфіденційності
    if (!$policyAgreement) {
        $errors['policyAgreement'] = "Ви повинні погодитися з політикою конфіденційності.";
    }

    // 3. Перевірка унікальності пошти та юзернейма (тільки якщо попередні валідації пройшли)
    // Важливо: якщо на цьому етапі $errors вже не порожній (наприклад, через невірний формат імені),
    // то ця перевірка унікальності може не виконатися. Це може бути бажаною поведінкою,
    // щоб спочатку виправити помилки форматування.
    // Якщо ж потрібно перевіряти унікальність незалежно від інших помилок полів,
    // цю умову if (empty($errors)) потрібно прибрати або переглянути.
    // Але для поточної логіки залишимо так: спочатку валідація формату, потім унікальність.
    
    // Перевірка пошти на унікальність (виконується, навіть якщо є інші помилки, КРІМ помилок email-формату)
    if (empty($errors['email'])) { // Перевіряємо унікальність, якщо сам email пройшов базову валідацію
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
            // $errors['db_error'] = "Помилка підготовки запиту для перевірки пошти: " . $conn->error;
            // Замість загальної помилки, можна додати специфічну, якщо потрібно
             $errors['email_unique_check_fail'] = "Серверна помилка перевірки пошти.";
        }
    }

    // Перевірка юзернейма на унікальність (аналогічно)
    if (empty($errors['username'])) { // Перевіряємо унікальність, якщо сам username пройшов базову валідацію
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
            // $errors['db_error'] = "Помилка підготовки запиту для перевірки юзернейма: " . $conn->error;
             $errors['username_unique_check_fail'] = "Серверна помилка перевірки юзернейма.";
        }
    }


    // 4. Якщо помилок НЕМАЄ (після всіх перевірок, включаючи унікальність), хешуємо пароль і додаємо користувача в БД
    if (empty($errors)) {
        // Хешування пароля
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // SQL-запит для вставки даних
        $sql_insert_user = "INSERT INTO users (username, password_hash, email, first_name, last_name) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert_user = $conn->prepare($sql_insert_user);

        if ($stmt_insert_user) {
            $stmt_insert_user->bind_param("sssss", $username, $password_hash, $email, $firstName, $lastName);

            if ($stmt_insert_user->execute()) {
                $_SESSION['user_id'] = $stmt_insert_user->insert_id;
                $_SESSION['username'] = $username;
                // Виріши, чи login.html чи login.php
                header("Location: ../public/html/login.php?registration=success"); // Або login.html
                exit();
            } else {
                $errors['db_error'] = "Помилка реєстрації: " . $stmt_insert_user->error;
            }
            $stmt_insert_user->close();
        } else {
            $errors['db_error'] = "Помилка підготовки запиту для реєстрації: " . $conn->error;
        }
    }

    // 5. Якщо є помилки (будь-які), зберігаємо їх у сесію та повертаємо користувача на форму реєстрації
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST; // Зберігаємо введені дані, щоб заповнити форму
        header("Location: ../public/html/reg.php"); // ВИПРАВЛЕНО
        exit();
    }

} else {
    // Якщо хтось намагається отримати доступ до скрипту напряму без POST-запиту
    header("Location: ../public/html/reg.php"); // ВИПРАВЛЕНО
    exit();
}

// Закриваємо з'єднання з БД
$conn->close();
?>