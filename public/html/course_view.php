<?php
session_start();
$course_id = htmlspecialchars($_GET['course_id'] ?? 'Невідомий ID');
// Тут має бути логіка підключення до БД та отримання інформації про курс за $course_id
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Перегляд курсу</title>
    <link rel="stylesheet" href="../css/styles.css"> <style>
        body { padding: 20px; background: #f0f2f5; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Сторінка курсу</h1>
        <p>ID Запрошеного курсу: <strong><?php echo $course_id; ?></strong></p>
        <p><em>(Ця сторінка є заглушкою. Тут буде відображатися детальна інформація про курс, завдання тощо)</em></p>
        <a href="home.php">Повернутися на головну</a>
    </div>
</body>
</html>