<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login into Assignet</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="login-container">
        <h1>Вхід до Assignet</h1>
        <form action="/login.php" method="post">
            <div class="form-group">
                <label for="username">Логін або Email:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="button">Увійти</button>
        </form>
        <p>Ще не зареєстровані? <a href="/reg">Зареєструватися</a></p>
    </div>
</body>
</html>