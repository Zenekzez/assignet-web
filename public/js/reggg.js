// Отримуємо доступ до форми та всіх полів і елементів для помилок
const registrationForm = document.getElementById('registrationForm');
const firstNameInput = document.getElementById('firstNid');
const lastNameInput = document.getElementById('lastNid');
const emailInput = document.getElementById('emailId');
const usernameInput = document.getElementById('userId');
const passwordInput = document.getElementById('passId');
const checkPasswordInput = document.getElementById('passCheckId');
const policyAgreementCheckbox = document.getElementById('policyAgreement');
const passwordRulesContainer = document.querySelector('.password-rules');

// --- Допоміжні функції для візуального стану ---

function setError(inputElement, message) {
    const container = inputElement.closest('.input-container') || inputElement.closest('.agreement-container');
    if (!container) return;

    const errorMessageElement = container.querySelector('small.error-message');
    container.classList.add('error');
    container.classList.remove('success');
    if (errorMessageElement) {
        errorMessageElement.textContent = message;
        errorMessageElement.style.display = 'block';
    }

    // Показуємо блок з правилами пароля, якщо це помилка пароля і є повідомлення
    if (inputElement.id === 'passId' && message && passwordRulesContainer) {
        passwordRulesContainer.style.display = 'block';
    }
}

function setSuccess(inputElement) {
    const container = inputElement.closest('.input-container') || inputElement.closest('.agreement-container');
    if (!container) return;

    const errorMessageElement = container.querySelector('small.error-message');
    container.classList.add('success');
    container.classList.remove('error');
    if (errorMessageElement) {
        errorMessageElement.textContent = '';
        errorMessageElement.style.display = 'none';
    }
    // Якщо це поле пароля і воно успішне, ховаємо правила
    // Ця логіка тепер в validateUserPassword для більшої ясності
}

function clearInputState(inputElement) {
    const container = inputElement.closest('.input-container');
    if (container) {
        container.classList.remove('error', 'success');
        const errorMessageElement = container.querySelector('small.error-message');
        if (errorMessageElement) {
            errorMessageElement.textContent = '';
            errorMessageElement.style.display = 'none';
        }
    }
}

// --- Функції валідації для кожного типу поля ---

function validateNameField(inputElement, fieldName, isSubmitting = false) {
    const value = inputElement.value.trim();
    const minLength = 2;
    const maxLength = 30;
    // Оновлений регулярний вираз для перевірки великої першої літери
    const nameRegex = /^[A-ZА-ЯІЇЄҐ][a-zA-Zа-яА-ЯіІїЇєЄґҐ']*(?:-[A-ZА-ЯІЇЄҐ][a-zA-Zа-яА-ЯіІїЇєЄґҐ']*)?$/;

    if (value === '') {
        if (isSubmitting) {
            setError(inputElement, `${fieldName} не може бути порожнім.`);
        } else {
            clearInputState(inputElement);
        }
        return false;
    } else if (value.length < minLength || value.length > maxLength) {
        setError(inputElement, `${fieldName} має містити від ${minLength} до ${maxLength} символів.`);
        return false;
    } else if (!nameRegex.test(value)) {
        // Оновлене повідомлення про помилку
        setError(inputElement, `${fieldName} має починатися з великої літери та може містити лише літери, дефіс або апостроф.`);
        return false;
    } else {
        setSuccess(inputElement);
        return true;
    }
}

function validateEmail(isSubmitting = false) {
    const value = emailInput.value.trim();
    const emailRegex = /^([a-zA-Z0-9._%+-]+)@([a-zA-Z0-9.-]+)\.([a-zA-Z]{2,})$/;

    if (value === '') {
        if (isSubmitting) {
            setError(emailInput, "Пошта не може бути порожньою.");
        } else {
            clearInputState(emailInput);
        }
        return false;
    } else if (!emailRegex.test(value)) {
        setError(emailInput, "Введіть коректну адресу електронної пошти.");
        return false;
    } else {
        setSuccess(emailInput);
        return true;
    }
}

function validateUsername(isSubmitting = false) {
    const value = usernameInput.value.trim();
    const userRegex = /^[a-zA-Z0-9_]{3,15}$/;

    if (value === '') {
        if (isSubmitting) {
            setError(usernameInput, "Юзернейм не може бути порожнім.");
        } else {
            clearInputState(usernameInput);
        }
        return false;
    } else if (!userRegex.test(value)) {
        setError(usernameInput, "Юзернейм: 3-15 символів (літери, цифри, '_').");
        return false;
    } else {
        setSuccess(usernameInput);
        return true;
    }
}

let isPasswordValidGlobal = false;

function validateUserPassword(isSubmitting = false) {
    const value = passwordInput.value;
    const passRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/;

    if (value === '') {
        isPasswordValidGlobal = false;
        if (isSubmitting) {
            setError(passwordInput, "Пароль не може бути порожнім.");
            // Показ правил обробляється в setError
        } else {
            // Якщо не сабміт і поле порожнє, очищаємо стан
            clearInputState(passwordInput); // Використовуємо clearInputState
            // І ховаємо правила, якщо вони були показані при фокусі
            if (passwordRulesContainer) {
                passwordRulesContainer.style.display = 'none';
            }
        }
        return false;
    } else if (!passRegex.test(value)) {
        isPasswordValidGlobal = false;
        setError(passwordInput, "Пароль не відповідає вимогам безпеки.");
        // Показ правил обробляється в setError
        return false;
    } else {
        isPasswordValidGlobal = true;
        setSuccess(passwordInput);
        if (passwordRulesContainer) {
            passwordRulesContainer.style.display = 'none';
        }
        return true;
    }
}

function validateCheckPassword(isSubmitting = false) {
    const passValue = passwordInput.value;
    const checkPassValue = checkPasswordInput.value;

    if (checkPassValue === '') {
        if (isSubmitting) {
            setError(checkPasswordInput, "Повторіть пароль.");
        } else {
            clearInputState(checkPasswordInput);
        }
        return false;
    } else if (checkPassValue !== passValue) {
        setError(checkPasswordInput, "Паролі не співпадають.");
        return false;
    } else {
        setSuccess(checkPasswordInput);
        return true;
    }
}

function validatePolicyAgreement(isSubmitting = false) {
    const agreementContainer = policyAgreementCheckbox.closest('.agreement-container');
    agreementContainer.classList.remove('error', 'success');
    const errorMessageElement = agreementContainer.querySelector('small.error-message');
    if (errorMessageElement) {
        errorMessageElement.textContent = '';
        errorMessageElement.style.display = 'none';
    }

    if (!policyAgreementCheckbox.checked) {
        if (isSubmitting) {
            setError(policyAgreementCheckbox, "Ви повинні погодитися з політикою конфіденційності.");
        }
        return false;
    } else {
        setSuccess(policyAgreementCheckbox);
        return true;
    }
}

// --- Додаємо обробники подій ---
if (firstNameInput) firstNameInput.addEventListener('blur', () => validateNameField(firstNameInput, "Ім'я"));
if (lastNameInput) lastNameInput.addEventListener('blur', () => validateNameField(lastNameInput, "Прізвище"));
if (emailInput) emailInput.addEventListener('blur', () => validateEmail());
if (usernameInput) usernameInput.addEventListener('blur', () => validateUsername());

if (passwordInput) {
    passwordInput.addEventListener('focus', function() {
        // Показуємо правила, тільки якщо пароль ще НЕ валідний
        // Або якщо поле порожнє (навіть якщо isPasswordValidGlobal ще true з попереднього разу)
        if (!isPasswordValidGlobal || passwordInput.value === '') {
            if (passwordRulesContainer) {
                 passwordRulesContainer.style.display = 'block';
            }
        }
    });
    // При втраті фокусу викликаємо валідацію, яка також приховає правила, якщо поле порожнє і немає помилки
    passwordInput.addEventListener('blur', () => validateUserPassword());
    passwordInput.addEventListener('input', () => validateUserPassword());
}

if (checkPasswordInput) {
    checkPasswordInput.addEventListener('blur', () => validateCheckPassword());
    checkPasswordInput.addEventListener('input', () => validateCheckPassword());
}

if (policyAgreementCheckbox) policyAgreementCheckbox.addEventListener('change', () => validatePolicyAgreement());

// --- Головна функція валідації для події onsubmit ---
function validateForm() {
    const isFirstNameValid = validateNameField(firstNameInput, "Ім'я", true);
    const isLastNameValid = validateNameField(lastNameInput, "Прізвище", true);
    const isEmailValid = validateEmail(true);
    const isUsernameValid = validateUsername(true);
    const isPasswordValid = validateUserPassword(true);
    const isCheckPasswordValid = validateCheckPassword(true);
    const isPolicyAgreed = validatePolicyAgreement(true);

    if (isFirstNameValid && isLastNameValid && isEmailValid && isUsernameValid && isPasswordValid && isCheckPasswordValid && isPolicyAgreed) {
        return true;
    } else {
        const firstErrorField = document.querySelector('.input-container.error input, .agreement-container.error input');
        if (firstErrorField) {
            firstErrorField.focus();
        }
        return false;
    }
}

// Початкове приховування блоку правил пароля
document.addEventListener('DOMContentLoaded', function() {
    if (passwordRulesContainer) {
        passwordRulesContainer.style.display = 'none';
    }
    if (passwordInput && passwordInput.value === '') {
        isPasswordValidGlobal = false;
    } else if (passwordInput) {
        validateUserPassword();
    }
});

