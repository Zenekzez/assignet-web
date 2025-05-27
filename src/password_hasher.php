<?php
// src/php/password_hasher.php
if (!function_exists('hashPassword')) {
    /**
     * Хешує пароль.
     *
     * @param string $password Пароль для хешування.
     * @return string|false Захешований пароль або false у випадку помилки.
     */
    function hashPassword(string $password): string|false {
        // Використовуємо стандартний алгоритм PHP, який є безпечним
        // і може оновлюватися з новими версіями PHP.
        // Коефіцієнт 'cost' визначає "важкість" хешування. 12 - хороший баланс.
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }
}

if (!function_exists('verifyPassword')) {
    /**
     * Перевіряє, чи відповідає наданий пароль збереженому хешу.
     * (Ця функція знадобиться для логіну, для реєстрації не використовується)
     *
     * @param string $password Введений пароль.
     * @param string $hashedPassword Збережений хеш пароля.
     * @return bool True, якщо пароль вірний, інакше false.
     */
    function verifyPassword(string $password, string $hashedPassword): bool {
        return password_verify($password, $hashedPassword);
    }
}
?>