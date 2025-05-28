<?php
    // Файл: public/html/settings.php
    // Повністю оновлений файл
    require_once 'templates/header.php'; // Підключаємо хедер і сайдбар

    // Отримуємо актуальні дані користувача з сесії
    $current_first_name = htmlspecialchars($_SESSION['db_first_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $current_last_name = htmlspecialchars($_SESSION['db_last_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $current_username = htmlspecialchars($_SESSION['username'] ?? 'Невідомий користувач', ENT_QUOTES, 'UTF-8');
    
    // Формування шляху до аватарки
    // Припускаємо, що ваш веб-сервер налаштований так, що корінь сайту - це папка public/
    // Якщо ні, вам потрібно буде скоригувати $base_url_for_images
    $base_url_for_images = '../'; // Якщо settings.php в public/html/, то ../ веде до public/
                                  // Якщо ваш сайт налаштований так, що public/ є коренем, то можна залишити порожнім або '/'
    
    $default_avatar_rel_path = 'assets/default_avatar.png'; // Шлях відносно public/
    $current_avatar_display_path = $default_avatar_rel_path; // За замовчуванням

    if (!empty($_SESSION['db_avatar_path'])) {
        // Перевіряємо, чи шлях до аватарки не є шляхом до стандартного аватара,
        // щоб уникнути подвійного додавання $base_url_for_images
        if ($_SESSION['db_avatar_path'] !== $default_avatar_rel_path) {
             $current_avatar_display_path = htmlspecialchars($_SESSION['db_avatar_path'], ENT_QUOTES, 'UTF-8');
        }
    }
    // Повний шлях для тегу <img> або style background-image
    $avatar_src = $base_url_for_images . $current_avatar_display_path;

?>
<title>Налаштування - Assignet</title>
<main class="settings-main-content">
    <div class="settings-container">
        <h1>Налаштування профілю</h1>

        <div id="messageContainer"></div>

        <section class="settings-section">
            <h2>Змінити аватарку</h2>
            <form id="avatarForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="avatarFile">Виберіть файл (PNG, JPEG, до 2MB)</label>
                    <div class="avatar-preview" id="avatarPreview" style="background-image: url('<?php echo $avatar_src; ?>?t=<?php echo time(); ?>');">
                        </div>
                    <input type="file" id="avatarFile" name="avatarFile" accept="image/png, image/jpeg">
                    <small id="avatarError" style="color: red; display: none;"></small>
                </div>
                <button type="submit" class="settings-button">Завантажити аватарку</button>
            </form>
        </section>

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
                    <label for="username">Змінити юзернейм</label>
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
document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.getElementById('messageContainer');
    const avatarForm = document.getElementById('avatarForm');
    const avatarFile = document.getElementById('avatarFile');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarError = document.getElementById('avatarError');
    const profileInfoForm = document.getElementById('profileInfoForm');
    const passwordChangeForm = document.getElementById('passwordChangeForm');

    const displayFirstName = document.getElementById('displayFirstName');
    const displayLastName = document.getElementById('displayLastName');
    const displayUsername = document.getElementById('displayUsername');
    
    // Припускаємо, що шлях до public/ є коренем для веб-доступу
    // Якщо ваш settings.php знаходиться в public/html/, то шлях до public/ буде '../'
    const baseImageUrl = '../'; 
    const defaultAvatarRelPath = 'assets/default_avatar.png';


    function showMessage(type, text) {
        messageContainer.innerHTML = `<div class="message ${type}">${text}</div>`;
        setTimeout(() => { messageContainer.innerHTML = ''; }, 5000);
    }

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
        const maxSize = 2 * 1024 * 1024; // 2MB
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
        avatarForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const file = avatarFile.files[0];
            if (!validateAvatarFile(file)) {
                return;
            }

            const formData = new FormData();
            formData.append('avatarFile', file);
            
            showMessage('loading', 'Завантаження аватарки...');

            try {
                const response = await fetch('../../src/update_avatar.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    showMessage('success', result.message);
                    if (result.new_avatar_url) {
                        // result.new_avatar_url - це шлях відносно папки public/ (наприклад, 'uploads/avatars/файл.jpg')
                        const newAvatarDisplayUrl = baseImageUrl + result.new_avatar_url + `?t=${new Date().getTime()}`;
                        avatarPreview.style.backgroundImage = `url(${newAvatarDisplayUrl})`;
                        
                        // Оновлення аватарки в хедері, якщо вона там є
                        const headerUserAvatar = document.getElementById('headerUserAvatar'); // Потрібно додати цей ID в header.php
                        if (headerUserAvatar) {
                            headerUserAvatar.src = newAvatarDisplayUrl;
                        }
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
        profileInfoForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const dataToSend = Object.fromEntries(formData.entries());

            if (!dataToSend.firstName.trim() || !dataToSend.lastName.trim() || !dataToSend.username.trim()) {
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
                const response = await fetch('../../src/update_profile_info.php', {
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
                    
                    // Оновлення імені/прізвища в хедері, якщо вони там відображаються
                    const headerUserFullName = document.getElementById('headerUserFullName'); // Потрібно додати цей ID в header.php
                    if(headerUserFullName) {
                        headerUserFullName.textContent = `${dataToSend.firstName} ${dataToSend.lastName}`;
                    }
                    const headerUsernameDisplay = document.getElementById('headerUsernameDisplay'); // Якщо є окремий елемент для юзернейма
                     if (headerUsernameDisplay && result.new_username) {
                        headerUsernameDisplay.textContent = result.new_username;
                    }

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
        passwordChangeForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
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
                const response = await fetch('../../src/change_password.php', {
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
});
</script>

<?php
    // Якщо ви видалили require_once 'templates/footer.php'; раніше, 
    // то ці закриваючі теги мають бути тут:
?>
        </div> </body>
</html>