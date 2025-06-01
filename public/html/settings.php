<?php
    // File: public/html/settings.php
    if (session_status() == PHP_SESSION_NONE) { 
        session_start();
    }
    require_once __DIR__ . '/templates/header.php'; 

    $current_first_name = htmlspecialchars($_SESSION['db_first_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $current_last_name = htmlspecialchars($_SESSION['db_last_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $current_username = htmlspecialchars($_SESSION['username'] ?? 'Невідомий користувач', ENT_QUOTES, 'UTF-8');
    $current_user_email = htmlspecialchars($_SESSION['email'] ?? 'Не вказано', ENT_QUOTES, 'UTF-8'); // ДОДАНО
    
    $default_avatar_web_path = '../assets/default_avatar.png'; 
    $avatar_display_path = $default_avatar_web_path;

    if (!empty($_SESSION['db_avatar_path']) && $_SESSION['db_avatar_path'] !== 'assets/default_avatar.png') {
         $avatar_display_path = '../' . htmlspecialchars($_SESSION['db_avatar_path'], ENT_QUOTES, 'UTF-8');
    } else if (!empty($_SESSION['db_avatar_path']) && $_SESSION['db_avatar_path'] === 'assets/default_avatar.png'){
        $avatar_display_path = '../' . htmlspecialchars($_SESSION['db_avatar_path'], ENT_QUOTES, 'UTF-8');
    }
?>
<title>Налаштування - Assignet</title>
<link rel="stylesheet" href="../css/settings_styles.css"> <main class="page-content-wrapper settings-main-content">
    <div class="settings-container">
        <h1>Налаштування профілю</h1>

        <div id="messageContainer"></div>

        <section class="settings-section">
            <h2>Змінити аватарку</h2>
            <form id="avatarForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="avatarFile">Виберіть файл (PNG, JPEG, до 2MB)</label>
                    <div class="avatar-preview" id="avatarPreview" style="background-image: url('<?php echo $avatar_display_path; ?>?t=<?php echo time(); ?>');">
                    </div>
                    <input type="file" id="avatarFile" name="avatarFile" accept="image/png, image/jpeg">
                    <small id="avatarError" class="error-message" style="color: red; display: none;"></small> </div>
                <button type="submit" class="settings-button">Завантажити аватарку</button>
            </form>
        </section>

        <hr>

        <section class="settings-section">
            <h2>Особиста інформація</h2>
            <p><strong>Ім'я:</strong> <span id="displayFirstName"><?php echo $current_first_name ?: 'Не вказано'; ?></span></p>
            <p><strong>Прізвище:</strong> <span id="displayLastName"><?php echo $current_last_name ?: 'Не вказано'; ?></span></p>
            <p><strong>Юзернейм:</strong> <span id="displayUsername"><?php echo $current_username; ?></span></p>
            <hr>
            <form id="profileInfoForm">
                <div class="form-group">
                    <label for="firstName">Змінити ім'я</label>
                    <input type="text" id="firstName" name="firstName" value="<?php echo $current_first_name; ?>" required>
                </div>
                <div class="form-group">
                    <label for="lastName">Змінити прізвище</label>
                    <input type="text" id="lastName" name="lastName" value="<?php echo $current_last_name; ?>" required>
                </div>
                <div class="form-group">
                    <label for="usernameInputSettings">Змінити юзернейм</label>
                    <input type="text" id="usernameInputSettings" name="username" value="<?php echo $current_username; ?>" required>
                    <small>Від 3 до 20 символів (літери, цифри, '_')</small>
                </div>
                <button type="submit" class="settings-button">Зберегти інформацію</button>
            </form>
        </section>

        <hr> <section class="settings-section">
            <h2>Змінити електронну пошту</h2>
            <form id="emailChangeForm">
                <div class="form-group">
                    <label for="currentEmailDisplay">Поточна пошта</label>
                    <input type="email" id="currentEmailDisplay" name="currentEmailDisplay" value="<?php echo $current_user_email; ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="newEmail">Нова електронна пошта</label>
                    <input type="email" id="newEmail" name="newEmail" required>
                    <small id="newEmailError" class="error-message" style="color: red; display: none;"></small> </div>
                <div class="form-group">
                    <label for="passwordForEmailChange">Ваш поточний пароль (для підтвердження)</label>
                    <input type="password" id="passwordForEmailChange" name="passwordForEmailChange" required>
                </div>
                <button type="submit" class="settings-button">Змінити пошту</button>
            </form>
        </section>
        <hr>

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

        <hr>
        
        <section class="settings-section">
            <h2>Вихід з акаунту</h2>
            <a href="../../src/logout.php" class="logout-button"><i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i>Вийти</a>
        </section>
    </div>
</main>

</div> <script>
document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.getElementById('messageContainer');
    const avatarForm = document.getElementById('avatarForm');
    const avatarFile = document.getElementById('avatarFile');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarError = document.getElementById('avatarError');
    const profileInfoForm = document.getElementById('profileInfoForm');
    const passwordChangeForm = document.getElementById('passwordChangeForm');
    const emailChangeForm = document.getElementById('emailChangeForm'); // НОВА ФОРМА
    const newEmailInput = document.getElementById('newEmail'); // НОВЕ ПОЛЕ
    const newEmailError = document.getElementById('newEmailError'); // ДЛЯ ПОМИЛОК ВВОДУ НОВОЇ ПОШТИ
    const currentEmailDisplayField = document.getElementById('currentEmailDisplay'); // ПОЛЕ ДЛЯ ВІДОБРАЖЕННЯ ПОТОЧНОЇ ПОШТИ

    const displayFirstName = document.getElementById('displayFirstName');
    const displayLastName = document.getElementById('displayLastName');
    const displayUsername = document.getElementById('displayUsername');
    
    const baseAvatarUrlSettings = '../'; 


    function showMessage(type, text) {
        messageContainer.innerHTML = `<div class="message ${type}">${text}</div>`;
        setTimeout(() => { 
            if (messageContainer.firstChild && messageContainer.firstChild.textContent === text) {
                 messageContainer.innerHTML = ''; 
            }
        }, 5000);
    }

    // Функція для валідації файлу аватарки (залишається без змін)
    function validateAvatarFile(file) {
        avatarError.style.display = 'none';
        avatarError.textContent = '';

        if (!file) {
            avatarError.textContent = 'Будь ласка, виберіть файл.';
            avatarError.style.display = 'block';
            return false;
        }
        const allowedTypes = ['image/png', 'image/jpeg'];
        if (!allowedTypes.includes(file.type)) {
            avatarError.textContent = 'Неприпустимий тип файлу. Дозволено лише PNG та JPEG.';
            avatarError.style.display = 'block';
            return false;
        }
        const maxSize = 2 * 1024 * 1024; 
        if (file.size > maxSize) {
            avatarError.textContent = 'Файл занадто великий. Максимальний розмір - 2MB.';
            avatarError.style.display = 'block';
            return false;
        }
        return true;
    }

    if (avatarFile) {
        avatarFile.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (validateAvatarFile(file)) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        avatarPreview.style.backgroundImage = `url(${event.target.result})`;
                    }
                    reader.readAsDataURL(file);
                } else {
                    avatarFile.value = ''; 
                }
            }
        });
    }

    if (avatarForm) {
        // Обробник для форми аватарки (залишається без змін)
        avatarForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const file = avatarFile.files[0];
            if (!file) { 
                 showMessage('error', 'Будь ласка, виберіть файл для завантаження.');
                 return;
            }
            if (!validateAvatarFile(file)) {
                return;
            }

            const formData = new FormData();
            formData.append('avatarFile', file);
            
            showMessage('loading', 'Завантаження аватарки...');

            try {
                const response = await fetch('../../src/update_avatar.php', { //
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    showMessage('success', result.message);
                    if (result.new_avatar_url) {
                        const newAvatarDisplayUrl = baseAvatarUrlSettings + result.new_avatar_url + `?t=${new Date().getTime()}`;
                        avatarPreview.style.backgroundImage = `url(${newAvatarDisplayUrl})`;
                    }
                } else {
                    showMessage('error', result.message || 'Помилка завантаження аватарки.');
                }
            } catch (error) {
                showMessage('error', 'Помилка мережі або сервера при завантаженні аватарки.');
                console.error("Avatar upload error:", error);
            }
        });
    }

    if (profileInfoForm) {
        // Обробник для форми інформації профілю (залишається без змін)
        profileInfoForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const dataToSend = {
                firstName: document.getElementById('firstName').value.trim(),
                lastName: document.getElementById('lastName').value.trim(),
                username: document.getElementById('usernameInputSettings').value.trim()
            };

            if (!dataToSend.firstName || !dataToSend.lastName || !dataToSend.username) {
                showMessage('error', 'Ім\'я, прізвище та юзернейм не можуть бути порожніми.');
                return;
            }
            const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
            if (!usernameRegex.test(dataToSend.username)) {
                showMessage('error', "Юзернейм: 3-20 символів (літери, цифри, '_').");
                return;
            }
            
            showMessage('loading', 'Збереження інформації...');

            try {
                const response = await fetch('../../src/update_profile_info.php', { //
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(dataToSend)
                });
                const result = await response.json();
                if (result.status === 'success') {
                    showMessage('success', result.message);
                    if (displayFirstName) displayFirstName.textContent = dataToSend.firstName || 'Не вказано';
                    if (displayLastName) displayLastName.textContent = dataToSend.lastName || 'Не вказано';
                    if (displayUsername) displayUsername.textContent = dataToSend.username;
                } else {
                    showMessage('error', result.message);
                }
            } catch (error) {
                showMessage('error', 'Помилка збереження інформації.');
                console.error("Update profile info error:", error);
            }
        });
    }
    
    if (passwordChangeForm) {
        // Обробник для форми зміни пароля (залишається без змін)
        passwordChangeForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const currentPassword = document.getElementById('currentPassword').value;
            const newPasswordInput = document.getElementById('newPassword');
            const newPassword = newPasswordInput.value;
            const confirmNewPassword = document.getElementById('confirmNewPassword').value;

            if (!currentPassword || !newPassword || !confirmNewPassword) {
                showMessage('error', 'Всі поля для зміни пароля мають бути заповнені.');
                return;
            }
            if (newPassword !== confirmNewPassword) {
                showMessage('error', 'Новий пароль та підтвердження не співпадають.');
                return;
            }
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/;
            if (!passwordRegex.test(newPassword)) {
                showMessage('error', 'Новий пароль не відповідає вимогам безпеки.');
                return;
            }
            
            showMessage('loading', 'Зміна пароля...');

            try {
                const response = await fetch('../../src/change_password.php', { //
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ currentPassword, newPassword })
                });
                const result = await response.json();
                if (result.status === 'success') {
                    showMessage('success', result.message);
                    this.reset(); 
                } else {
                    showMessage('error', result.message);
                }
            } catch (error) {
                showMessage('error', 'Помилка зміни пароля.');
                console.error("Change password error:", error);
            }
        });
    }

    // НОВИЙ ОБРОБНИК ДЛЯ ПЕРЕВІРКИ ДОСТУПНОСТІ ПОШТИ ПРИ ВТРАТІ ФОКУСУ
    if (newEmailInput) {
        newEmailInput.addEventListener('blur', async function() {
            newEmailError.style.display = 'none';
            newEmailError.textContent = '';
            const emailValue = this.value.trim();
            const currentSessionEmail = "<?php echo $current_user_email; ?>"; // Отримуємо поточну пошту з PHP

            if (!emailValue) return; // Якщо поле порожнє, нічого не робимо

            const emailRegex = /^([a-zA-Z0-9._%+-]+)@([a-zA-Z0-9.-]+)\.([a-zA-Z]{2,})$/;
            if (!emailRegex.test(emailValue)) {
                newEmailError.textContent = "Некоректний формат електронної пошти.";
                newEmailError.style.display = 'block';
                return;
            }

            // Не перевіряємо доступність, якщо введена пошта ідентична поточній (ігноруючи регістр)
            if (emailValue.toLowerCase() === currentSessionEmail.toLowerCase()) {
                return;
            }

            // Перевірка доступності нової пошти
            try {
                const formData = new FormData();
                formData.append('email', emailValue);
                // Шлях до check_availability.php відносно public/html/settings.php
                const response = await fetch('../../src/check_availability.php', { //
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (!data.available) { // Якщо пошта НЕ доступна (тобто зайнята)
                    newEmailError.textContent = data.message || 'Ця електронна пошта вже використовується.';
                    newEmailError.style.display = 'block';
                }
            } catch (error) {
                console.error('Помилка перевірки доступності пошти:', error);
                // Можна показати загальну помилку, але зазвичай краще покладатися на серверну валідацію при відправці форми
            }
        });
    }

    // НОВИЙ ОБРОБНИК ДЛЯ ФОРМИ ЗМІНИ ПОШТИ
    if (emailChangeForm) {
        emailChangeForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            newEmailError.style.display = 'none'; // Спершу ховаємо попередню помилку поля

            const newEmailValue = newEmailInput.value.trim();
            const passwordValue = document.getElementById('passwordForEmailChange').value;

            if (!newEmailValue || !passwordValue) {
                showMessage('error', 'Нова електронна пошта та поточний пароль мають бути заповнені.');
                return;
            }

            const emailRegex = /^([a-zA-Z0-9._%+-]+)@([a-zA-Z0-9.-]+)\.([a-zA-Z]{2,})$/;
            if (!emailRegex.test(newEmailValue)) {
                showMessage('error', 'Некоректний формат нової електронної пошти.');
                newEmailError.textContent = "Некоректний формат електронної пошти."; // Показуємо помилку біля поля
                newEmailError.style.display = 'block';
                return;
            }

            // Якщо клієнтська перевірка доступності вже показала помилку (і це не помилка формату), не відправляємо
            if (newEmailError.style.display === 'block' && newEmailError.textContent !== "Некоректний формат електронної пошти.") {
                 showMessage('error', newEmailError.textContent); // Показуємо помилку доступності в головному контейнері
                 return;
            }
            
            showMessage('loading', 'Зміна електронної пошти...');

            try {
                const response = await fetch('../../src/update_email.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        newEmail: newEmailValue, 
                        passwordForEmailChange: passwordValue 
                    })
                });
                const result = await response.json();

                if (result.status === 'success') {
                    showMessage('success', result.message);
                    if (result.new_email_for_display && currentEmailDisplayField) {
                        currentEmailDisplayField.value = result.new_email_for_display; // Оновлюємо поле поточної пошти
                    }
                    this.reset(); // Очищаємо поля форми
                    newEmailInput.value = ''; // Додатково очищаємо поле нової пошти
                    document.getElementById('passwordForEmailChange').value = '';
                } else {
                    showMessage('error', result.message || 'Не вдалося змінити електронну пошту.');
                     if (result.message.toLowerCase().includes("пошта вже використовується")) {
                        newEmailError.textContent = result.message;
                        newEmailError.style.display = 'block';
                    }
                }
            } catch (error) {
                showMessage('error', 'Помилка підключення до сервера при зміні пошти.');
                console.error("Change email error:", error);
            }
        });
    }
});
</script>
</body>
</html>