<?php
session_start();
session_unset(); // Видаляє всі змінні сесії
session_destroy(); // Знищує сесію

// Перенаправлення на сторінку входу
// Переконайтесь, що шлях правильний відносно розташування logout.php
header("Location: ../html/login.php");
exit();
?>