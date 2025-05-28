<?php
    require_once 'templates/header.php'; // Підключаємо хедер і сайдбар
    // Тут можна отримати поточні дані користувача з БД для заповнення полів форми
    // require_once '../../src/connect.php'; // Якщо ще не підключено в header.php для даних сесії
    // $user_id = $_SESSION['user_id'];
    // $stmt = $conn->prepare("SELECT first_name, last_name, username, email, avatar_path FROM users WHERE user_id = ?");
    // $stmt->bind_param("i", $user_id);
    // $stmt->execute();
    // $result = $stmt->get_result();
    // $user_data = $result->fetch_assoc();
    // $stmt->close();
    // $conn->close(); // Закривати з'єднання, якщо воно більше не потрібне на цій сторінці

    // Поки що просто заглушки для значень
    $current_first_name = htmlspecialchars($_SESSION['db_first_name'] ?? '', ENT_QUOTES, 'UTF-8'); // Припустимо, ці дані є в сесії після логіну
    $current_last_name = htmlspecialchars($_SESSION['db_last_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $current_username = htmlspecialchars($_SESSION['username'] ?? '', ENT_QUOTES, 'UTF-8'); // Це вже є
    $current_avatar_path = $_SESSION['db_avatar_path'] ?? 'path/to/default/avatar.png'; // Припустимо
?>
<title>Налаштування - Assignet</title> <main class="settings-main-content">
    <div class="settings-container">
        <h1>Налаштування профілю</h1>

        <div id="messageContainer"></div> <section class="settings-section">
            <h2>Змінити аватарку</h2>
            <form id="avatarForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="avatarFile">Виберіть файл (PNG, JPG, GIF, до 2MB)</label>
                    <div class="avatar-preview" id="avatarPreview" style="background-image: url('<?php echo htmlspecialchars($current_avatar_path, ENT_QUOTES, 'UTF-8'); ?>');"></div>
                    <input type="file" id="avatarFile" name="avatarFile" accept="image/png, image/jpeg, image/gif">
                </div>
                <button type="submit" class="settings-button">Завантажити аватарку</button>
            </form>
        </section>

        <section class="settings-section">
            <h2>Особиста інформація</h2>
            <form id="profileInfoForm">
                <div class="form-group">
                    <label for="firstName">Ім'я</label>
                    <input type="text" id="firstName" name="firstName" value="<?php echo $current_first_name; ?>" required>
                </div>
                <div class="form-group">
                    <label for="lastName">Прізвище</label>
                    <input type="text" id="lastName" name="lastName" value="<?php echo $current_last_name; ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Юзернейм</label>
                    <input type="text" id="username" name="username" value="<?php echo $current_username; ?>" required>
                    <small>Від 3 до 20 символів (літери, цифри, '_')</small>
                </div>
                <button type="submit" class="settings-button">Зберегти інформацію</button>
            </form>
        </section>

        <section class="settings-section">
            <h2>Змінити пароль</h2>
            <form id="passwordChangeForm">
                <div class="form-group">
                    <label for="currentPassword">Поточний пароль</label>
                    <input type="password" id="currentPassword" name="currentPassword" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">Новий пароль</label>
                    <input type="password" id="newPassword" name="newPassword" required>
                    <small>Мінімум 8 символів, одна велика, одна маленька літера, одна цифра.</small>
                </div>
                <div class="form-group">
                    <label for="confirmNewPassword">Підтвердіть новий пароль</label>
                    <input type="password" id="confirmNewPassword" name="confirmNewPassword" required>
                </div>
                <button type="submit" class="settings-button">Змінити пароль</button>
            </form>
        </section>

        <section class="settings-section">
            <h2>Вихід з акаунту</h2>
            <a href="../../src/logout.php" class="logout-button">Вийти</a>
        </section>
    </div>
</main>

<script>
// JavaScript специфічний для сторінки settings.php
document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.getElementById('messageContainer');

    // Функція для відображення повідомлень
    function showMessage(type, text) {
        messageContainer.innerHTML = `<div class="message ${type}">${text}</div>`;
        // Очищення повідомлення через деякий час
        setTimeout(() => {
            messageContainer.innerHTML = '';
        }, 5000);
    }

    // Обробка форми зміни аватарки (поки що без реального завантаження, тільки фронтенд)
    const avatarForm = document.getElementById('avatarForm');
    const avatarFile = document.getElementById('avatarFile');
    const avatarPreview = document.getElementById('avatarPreview');

    if (avatarFile) {
        avatarFile.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    avatarPreview.style.backgroundImage = `url(${event.target.result})`;
                }
                reader.readAsDataURL(file);
            }
        });
    }
    if (avatarForm) {
        avatarForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            // Тут буде логіка відправки файлу на сервер
            // const formData = new FormData();
            // if (avatarFile.files[0]) {
            //     formData.append('avatar', avatarFile.files[0]);
            //     // AJAX запит на src/update_avatar.php
            //     // ...
            //     // try {
            //     //     const response = await fetch('../../src/update_avatar.php', { method: 'POST', body: formData });
            //     //     const data = await response.json();
            //     //     if (data.status === 'success') {
            //     //         showMessage('success', data.message);
            //     //         // Оновити аватарку в хедері/сайдбарі, якщо вона там відображається
            //     //     } else {
            //     //         showMessage('error', data.message);
            //     //     }
            //     // } catch (error) {
            //     //     showMessage('error', 'Помилка завантаження аватарки.');
            //     // }
            // } else {
            //     showMessage('error', 'Будь ласка, виберіть файл.');
            // }
            showMessage('success', 'Функціонал завантаження аватарки в розробці.'); // Заглушка
        });
    }


    // Обробка форми зміни особистої інформації
    const profileInfoForm = document.getElementById('profileInfoForm');
    if (profileInfoForm) {
        profileInfoForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const dataToSend = Object.fromEntries(formData.entries());

            // Клієнтська валідація (приклади)
            if (!dataToSend.firstName.trim() || !dataToSend.lastName.trim() || !dataToSend.username.trim()) {
                showMessage('error', 'Ім\'я, прізвище та юзернейм не можуть бути порожніми.');
                return;
            }
            const usernameRegex = /^[a-zA-Z0-9_]{3,20}<span class="math-inline">/;
if \(\!usernameRegex\.test\(dataToSend\.username\)\) \{
showMessage\('error', "Юзернейм\: 3\-20 символів \(літери, цифри, '\_'\)\."\);
return;
\}
try \{
const response \= await fetch\('\.\./\.\./src/update\_profile\_info\.php', \{
<25\>method\: 'POST',
headers\: \{'Content\-Type'\: 'application/json'\},
body\: JSON\.stringify\(dataToSend\)
\}\);
const result \= await response\.json\(\);
if \(result\.status</25\> \=\=\= 'success'\) \{
showMessage\('success', result\.message\);
// Оновити юзернейм в хедері, якщо він там відображається і змінився
if \(result\.new\_username && document\.getElementById\('headerUserDisplay'\)\) \{ // Припустимо, є такий елемент
document\.getElementById\('headerUserDisplay'\)\.textContent \= result\.new\_username;
\}
\} else \{
showMessage\('error', result\.message\);
\}
\} catch \(error\) \{
showMessage\('error', 'Помилка збереження інформації\.'\);
console\.error\("Update profile info error\:", error\);
\}
\}\);
\}
// Обробка форми зміни пароля
<26\>const passwordChangeForm \= document\.getElementById\('passwordChangeForm'\);
if \(passwordChangeForm\) \{
passwordChangeForm\.addEventListener\('submit',</26\> async function\(<27\>e\) \{
e\.preventDefault\(\);
const currentPassword \= document\.getElementById\('currentPassword'\)\.value;
const newPassword \= <28\>document\.getElementById\('newPassword'\)\.value;</27\>
const confirmNewPassword \= document\.getElementById\('confirmNewPassword'\)\.value;
if \(\!currentPassword \|\| \!newPassword \|\| \!confirmNewPassword\)</28\> \{
showMessage\('error', 'Всі поля для зміни пароля мають бути заповнені\.'\);
return;
\}
if \(newPassword \!\=\= confirmNewPassword\) \{
showMessage\('error', 'Новий пароль та підтвердження не співпадають\.'\);
return;
\}
// Клієнтська валідація нового пароля \(приклад\)
const passwordRegex \= /^\(?\=\.\*\[a\-z\]\)\(?\=\.\*\[A\-Z\]\)\(?\=\.\*\\d\)\[A\-Za\-z\\d@</span>!%*?&]{8,}$/;
            if (!passwordRegex.test(newPassword)) {
                showMessage('error', 'Новий пароль не відповідає вимогам безпеки.');
                return;
            }

            try {
                const response = await fetch('../../src/change_password.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ currentPassword, newPassword })
                });
                const result = await response.json();
                if (result.status === 'success') {
                    showMessage('success', result.message);
                    this.reset(); // Очистити форму
                } else {
                    showMessage('error', result.message);
                }
            } catch (error) {
                showMessage('error', 'Помилка зміни пароля.');
                console.error("Change password error:", error);
            }
        });
    }
});
</script>

<?php
    require_once 'templates/footer.php'; // Підключаємо футер
?>