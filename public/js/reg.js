// Отримуємо доступ до форми та всіх полів і елементів для помилок
const registrationForm = document.getElementById('registrationForm');

const firstNameInput = document.getElementById('firstNid');
const lastNameInput = document.getElementById('lastNid');
const emailInput = document.getElementById('emailId');
const usernameInput = document.getElementById('userId');
const passwordInput = document.getElementById('passId');
const checkPasswordInput = document.getElementById('passCheckId');
const policyAgreementCheckbox = document.getElementById('policyAgreement');

// Функції для встановлення стану помилки/успіху (узагальнені)
function setError(inputElement, message) {
    const container = inputElement.closest('.input-container');
    const errorMessageElement = container.querySelector('small'); // Знаходимо <small> всередині
    container.classList.add('error');
    container.classList.remove('success');
    errorMessageElement.textContent = message;
    // Показуємо блок з правилами пароля, якщо це помилка пароля
    if (inputElement.id === 'passId' && message) {
        document.querySelector('.password-rules').style.display = 'block';
    }
}

function setSuccess(inputElement) {
    const container = inputElement.closest('.input-container');
    const errorMessageElement = container.querySelector('small');
    container.classList.add('success');
    container.classList.remove('error');
    errorMessageElement.textContent = '';
    // Можна приховувати блок правил пароля, якщо пароль валідний
    if (inputElement.id === 'passId') {
        document.querySelector('.password-rules').style.display = 'none';
    }
}

// --- Функції валідації для кожного поля ---

function validateFirstName() {
    const value = firstNameInput.value.trim();
    if (value === '') {
        setError(firstNameInput, "Ім'я не може бути порожнім.");
        return false;
    } else if (value.length < 2) {
        setError(firstNameInput, "Ім'я має містити принаймні 2 символи.");
        return false;
    } else if (!/^[a-zA-Zа-яА-ЯіІїЇєЄґҐ']+(-[a-zA-Zа-яА-ЯіІїЇєЄґҐ']+)?$/.test(value)) {
        setError(firstNameInput, "Ім'я може містити лише літери, дефіс або апостроф.");
        return false;
    } else {
        setSuccess(firstNameInput);
        return true;
    }
}

function validateLastName() {
    const value = lastNameInput.value.trim();
    if (value === '') {
        setError(lastNameInput, "Прізвище не може бути порожнім.");
        return false;
    } else if (value.length < 2) {
        setError(lastNameInput, "Прізвище має містити принаймні 2 символи.");
        return false;
    } else if (!/^[a-zA-Zа-яА-ЯіІїЇєЄґҐ']+(-[a-zA-Zа-яА-ЯіІїЇєЄґҐ']+)?$/.test(value)) {
        setError(lastNameInput, "Прізвище може містити лише літери, дефіс або апостроф.");
        return false;
    } else {
        setSuccess(lastNameInput);
        return true;
    }
}

function validateEmail() {
    const value = emailInput.value.trim();
    const emailRegex = /^([a-zA-Z0-9._%+-]+)@([a-zA-Z0-9.-]+)\.([a-zA-Z]{2,})$/;
    if (value === '') {
        setError(emailInput, "Пошта не може бути порожньою.");
        return false;
    } else if (!emailRegex.test(value)) {
        setError(emailInput, "Введіть коректну адресу електронної пошти.");
        return false;
    } else {
        setSuccess(emailInput);
        return true;
    }
}

function validateUsername() {
    const value = usernameInput.value.trim();
    const userRegex = /^[a-zA-Z0-9_]{3,15}$/;
    if (value === '') {
        setError(usernameInput, "Юзернейм не може бути порожнім.");
        return false;
    } else if (!userRegex.test(value)) {
        setError(usernameInput, "Юзернейм: 3-15 символів (літери, цифри, '_').");
        return false;
    } else {
        setSuccess(usernameInput);
        return true;
    }
}

function validateUserPassword() {
    const value = passwordInput.value; // Для пароля trim() зазвичай не роблять, щоб зберегти пробіли, якщо вони є частиною пароля (хоча це спірно)
    const passwordRulesContainer = document.querySelector('.password-rules');
    // Складний регулярний вираз для пароля: мінімум 8 символів, одна велика, одна маленька, одна цифра.
    // Можна додати спецсимвол: (?=.*[!@#$%^&*])
    const passRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/;

    if (value === '') {
        setError(passwordInput, "Пароль не може бути порожнім.");
        passwordRulesContainer.style.display = 'block'; // Показуємо правила, якщо поле порожнє і намагаються вийти
        return false;
    } else if (!passRegex.test(value)) {
        setError(passwordInput, "Пароль не відповідає вимогам безпеки.");
        passwordRulesContainer.style.display = 'block'; // Показуємо правила
        return false;
    } else {
        setSuccess(passwordInput);
        passwordRulesContainer.style.display = 'none'; // Приховуємо правила, якщо пароль валідний
        return true;
    }
}

function validateCheckPassword() {
    const passValue = passwordInput.value;
    const checkPassValue = checkPasswordInput.value;
    if (checkPassValue === '') {
        setError(checkPasswordInput, "Повторіть пароль.");
        return false;
    } else if (checkPassValue !== passValue) {
        setError(checkPasswordInput, "Паролі не співпадають.");
        return false;
    } else {
        setSuccess(checkPasswordInput);
        return true;
    }
}

function validatePolicyAgreement() {
    const agreementContainer = policyAgreementCheckbox.closest('.agreement-container');
    const errorMessageElement = agreementContainer.querySelector('small'); // Або ваш .errorAgreement

    // Скидаємо попередні стани помилки/успіху
    agreementContainer.classList.remove('error', 'success');
    if (errorMessageElement) errorMessageElement.textContent = '';


    if (!policyAgreementCheckbox.checked) {
        setError(policyAgreementCheckbox, "Ви повинні погодитися з політикою конфіденційності.");
        // Для чекбокса setError може потребувати іншої логіки відображення помилки,
        // оскільки він не має типового .input-container. Ми можемо додати клас .error до .agreement-container
        agreementContainer.classList.add('error');
        if (errorMessageElement) errorMessageElement.textContent = "Ви повинні погодитися з політикою конфіденційності.";
        return false;
    } else {
        agreementContainer.classList.add('success');
        if (errorMessageElement) errorMessageElement.textContent = '';
        return true;
    }
}


// --- Додаємо обробники подій для поступової валідації ---
if (firstNameInput) firstNameInput.addEventListener('blur', validateFirstName);
if (lastNameInput) lastNameInput.addEventListener('blur', validateLastName);
if (emailInput) emailInput.addEventListener('blur', validateEmail);
if (usernameInput) usernameInput.addEventListener('blur', validateUsername);
if (passwordInput) {
    passwordInput.addEventListener('focus', function() {
        // Показуємо правила, коли користувач фокусується на полі пароля
        document.querySelector('.password-rules').style.display = 'block';
    });
    passwordInput.addEventListener('blur', validateUserPassword);
}
if (checkPasswordInput) checkPasswordInput.addEventListener('blur', validateCheckPassword);
if (policyAgreementCheckbox) policyAgreementCheckbox.addEventListener('change', validatePolicyAgreement);


// --- Головна функція валідації для події onsubmit ---
// Важливо: атрибут onsubmit="return validateForm()" у вашому HTML вже є.
// Ця функція буде викликана ним.
function validateForm() {
    // Викликаємо всі валідатори і збираємо результати
    const isFirstNameValid = validateFirstName();
    const isLastNameValid = validateLastName();
    const isEmailValid = validateEmail();
    const isUsernameValid = validateUsername();
    const isPasswordValid = validateUserPassword();
    const isCheckPasswordValid = validateCheckPassword();
    const isPolicyAgreed = validatePolicyAgreement();

    // Якщо хоча б одне поле невадідне, форма не відправиться
    if (isFirstNameValid && isLastNameValid && isEmailValid && isUsernameValid && isPasswordValid && isCheckPasswordValid && isPolicyAgreed) {
        alert('Форма валідна! Відправляємо дані...'); // Для тесту
        return true; // Дозволити відправку форми
    } else {
        alert('Будь ласка, виправте помилки у формі.'); // Для тесту
        return false; // Заборонити відправку форми
    }
}

// Початкове приховування блоку правил пароля
document.addEventListener('DOMContentLoaded', function() {
    const passwordRulesContainer = document.querySelector('.password-rules');
    if (passwordRulesContainer) {
        passwordRulesContainer.style.display = 'none';
    }
});