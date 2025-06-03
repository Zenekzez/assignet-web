// Глобальні змінні, які будуть визначені в PHP файлі
// const BASE_AVATAR_URL_SETTINGS_JS;
// const CURRENT_USER_EMAIL_PHP_JS;

document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.getElementById('messageContainer');
    const avatarForm = document.getElementById('avatarForm');
    const avatarFile = document.getElementById('avatarFile');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarError = document.getElementById('avatarError');
    const profileInfoForm = document.getElementById('profileInfoForm');
    const passwordChangeForm = document.getElementById('passwordChangeForm');
    const emailChangeForm = document.getElementById('emailChangeForm');
    const newEmailInput = document.getElementById('newEmail');
    const newEmailError = document.getElementById('newEmailError');
    const currentEmailDisplayField = document.getElementById('currentEmailDisplay');

    const displayFirstName = document.getElementById('displayFirstName');
    const displayLastName = document.getElementById('displayLastName');
    const displayUsername = document.getElementById('displayUsername');

    // Використовуємо глобальну змінну BASE_AVATAR_URL_SETTINGS_JS
    const baseAvatarUrlSettings = BASE_AVATAR_URL_SETTINGS_JS;


    function showMessage(type, text) {
        messageContainer.innerHTML = `<div class="message ${type}">${text}</div>`;
        setTimeout(() => {
            if (messageContainer.firstChild && messageContainer.firstChild.textContent === text) {
                 messageContainer.innerHTML = '';
            }
        }, 5000);
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
                const response = await fetch('../../src/update/update_avatar.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    showMessage('success', result.message);
                    if (result.new_avatar_url) {
                        // Використовуємо глобальну змінну baseAvatarUrlSettings
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
                const response = await fetch('../../src/update/update_profile_info.php', {
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
                const response = await fetch('../../src/update/update_password.php', {
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

    if (newEmailInput) {
        newEmailInput.addEventListener('blur', async function() {
            newEmailError.style.display = 'none';
            newEmailError.textContent = '';
            const emailValue = this.value.trim();
            // Використовуємо глобальну змінну CURRENT_USER_EMAIL_PHP_JS
            const currentSessionEmail = CURRENT_USER_EMAIL_PHP_JS;

            if (!emailValue) return;

            const emailRegex = /^([a-zA-Z0-9._%+-]+)@([a-zA-Z0-9.-]+)\.([a-zA-Z]{2,})$/;
            if (!emailRegex.test(emailValue)) {
                newEmailError.textContent = "Некоректний формат електронної пошти.";
                newEmailError.style.display = 'block';
                return;
            }

            if (emailValue.toLowerCase() === currentSessionEmail.toLowerCase()) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('email', emailValue);
                const response = await fetch('../../src/auth/check_availability.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (!data.available) {
                    newEmailError.textContent = data.message || 'Ця електронна пошта вже використовується.';
                    newEmailError.style.display = 'block';
                }
            } catch (error) {
                console.error('Помилка перевірки доступності пошти:', error);
            }
        });
    }

    if (emailChangeForm) {
        emailChangeForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            newEmailError.style.display = 'none';

            const newEmailValue = newEmailInput.value.trim();
            const passwordValue = document.getElementById('passwordForEmailChange').value;

            if (!newEmailValue || !passwordValue) {
                showMessage('error', 'Нова електронна пошта та поточний пароль мають бути заповнені.');
                return;
            }

            const emailRegex = /^([a-zA-Z0-9._%+-]+)@([a-zA-Z0-9.-]+)\.([a-zA-Z]{2,})$/;
            if (!emailRegex.test(newEmailValue)) {
                showMessage('error', 'Некоректний формат нової електронної пошти.');
                newEmailError.textContent = "Некоректний формат електронної пошти.";
                newEmailError.style.display = 'block';
                return;
            }

            if (newEmailError.style.display === 'block' && newEmailError.textContent !== "Некоректний формат електронної пошти.") {
                 showMessage('error', newEmailError.textContent);
                 return;
            }

            showMessage('loading', 'Зміна електронної пошти...');

            try {
                const response = await fetch('../../src/update/update_email.php', {
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
                        currentEmailDisplayField.value = result.new_email_for_display;
                        // Оновлюємо глобальну змінну з поточним email, якщо вона використовується десь ще
                        // (якщо ця змінна динамічно не оновлюється з сесії на стороні PHP при перезавантаженні)
                        // CURRENT_USER_EMAIL_PHP_JS = result.new_email_for_display; // Можливо, це не потрібно, якщо сторінка перезавантажиться
                    }
                    this.reset();
                    newEmailInput.value = '';
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