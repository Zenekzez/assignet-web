const registrationForm = document.getElementById('registrationForm'); 
const firstNameInput = document.getElementById('firstNid'); 
const lastNameInput = document.getElementById('lastNid'); 
const emailInput = document.getElementById('emailId'); 
const usernameInput = document.getElementById('userId'); 
const passwordInput = document.getElementById('passId'); 
const checkPasswordInput = document.getElementById('passCheckId'); 
const policyAgreementCheckbox = document.getElementById('policyAgreement'); 
const passwordRulesContainer = document.querySelector('.password-rules'); 


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


function validateNameField(inputElement, fieldName, isSubmitting = false) { 
    const value = inputElement.value.trim(); 
    const minLength = 2; 
    const maxLength = 30; 
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
    const container = emailInput.closest('.input-container');

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
        if (isSubmitting && container && container.classList.contains('error')) {
            return false; 
        }
        if (isSubmitting) {
             setSuccess(emailInput); 
        }
        return true; 
    }
}

function validateUsername(isSubmitting = false) {
    const value = usernameInput.value.trim(); 
    const userRegex = /^[a-zA-Z0-9_]{3,20}$/; 
    const container = usernameInput.closest('.input-container');

    if (value === '') { 
        if (isSubmitting) { 
            setError(usernameInput, "Юзернейм не може бути порожнім."); 
        } else {
            clearInputState(usernameInput); 
        }
        return false; 
    } else if (!userRegex.test(value)) { 
        setError(usernameInput, "Юзернейм: 3-20 символів (літери, цифри, '_')."); 
        return false; 
    } else {
        if (isSubmitting && container && container.classList.contains('error')) {
            return false;
        }
        if (isSubmitting) {
            setSuccess(usernameInput); 
        }
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
        } else {
            clearInputState(passwordInput); 
            if (passwordRulesContainer) { 
                passwordRulesContainer.style.display = 'none'; 
            }
        }
        return false; 
    } else if (!passRegex.test(value)) { 
        isPasswordValidGlobal = false; 
        setError(passwordInput, "Пароль не відповідає вимогам безпеки."); 
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


async function checkAvailability(field, value) {
    const inputElement = field === 'email' ? emailInput : usernameInput;
    if (!inputElement) return;

    if (!value.trim()) {
        if (field === 'email') validateEmail(false); 
        if (field === 'username') validateUsername(false); 
        return;
    }

    let isFormatValid = false;
    if (field === 'email') {
        const emailRegex = /^([a-zA-Z0-9._%+-]+)@([a-zA-Z0-9.-]+)\.([a-zA-Z]{2,})$/; 
        isFormatValid = emailRegex.test(value.trim());
        if (!isFormatValid) {
             setError(inputElement, "Введіть коректну адресу електронної пошти."); 
             return;
        }
    } else if (field === 'username') {
        const userRegex = /^[a-zA-Z0-9_]{3,20}$/; 
        isFormatValid = userRegex.test(value.trim());
        if (!isFormatValid) {
            setError(inputElement, "Юзернейм: 3-20 символів (літери, цифри, '_')."); 
            return;
        }
    }

    if (isFormatValid) {
        try {
            const formData = new FormData();
            formData.append(field, value);

            const response = await fetch('../../src/auth/check_availability.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                setError(inputElement, `Помилка сервера: ${response.statusText}`);
                return;
            }

            const data = await response.json();

            if (data.available) {
                setSuccess(inputElement);
            } else {
                if (data.message) {
                    setError(inputElement, data.message);
                } else {
                    setError(inputElement, field === 'email' ? 'Ця пошта вже використовується.' : 'Цей юзернейм вже зайнятий.');
                }
            }
        } catch (error) {
            console.error('Помилка AJAX-запиту:', error);
            setError(inputElement, 'Не вдалося перевірити доступність. Спробуйте пізніше.');
        }
    }
}


if (firstNameInput) firstNameInput.addEventListener('blur', () => validateNameField(firstNameInput, "Ім'я")); 
if (lastNameInput) lastNameInput.addEventListener('blur', () => validateNameField(lastNameInput, "Прізвище")); 

if (emailInput) {
    emailInput.addEventListener('blur', () => {
        if (validateEmail(false)) { 
            checkAvailability('email', emailInput.value);
        }
    });
    emailInput.addEventListener('input', () => validateEmail(false)); 
}

if (usernameInput) {
    usernameInput.addEventListener('blur', () => {
        if (validateUsername(false)) { 
            checkAvailability('username', usernameInput.value);
        }
    });
    usernameInput.addEventListener('input', () => validateUsername(false)); 
}


if (passwordInput) { 
    passwordInput.addEventListener('focus', function() { 
        if (!isPasswordValidGlobal || passwordInput.value === '') { 
            if (passwordRulesContainer) { 
                 passwordRulesContainer.style.display = 'block'; 
            }
        }
    });
    passwordInput.addEventListener('blur', () => validateUserPassword(false)); 
    passwordInput.addEventListener('input', () => validateUserPassword(false)); 
}

if (checkPasswordInput) { 
    checkPasswordInput.addEventListener('blur', () => validateCheckPassword(false)); 
    checkPasswordInput.addEventListener('input', () => validateCheckPassword(false)); 
}

if (policyAgreementCheckbox) policyAgreementCheckbox.addEventListener('change', () => validatePolicyAgreement(false)); 

function validateForm() {
    const isFirstNameValid = validateNameField(firstNameInput, "Ім'я", true); 
    const isLastNameValid = validateNameField(lastNameInput, "Прізвище", true); 
    const isEmailValid = validateEmail(true); 
    const isUsernameValid = validateUsername(true); 
    const isPasswordValid = validateUserPassword(true); 
    const isCheckPasswordValid = validateCheckPassword(true); 
    const isPolicyAgreed = validatePolicyAgreement(true); 

    if (isFirstNameValid && isLastNameValid && isEmailValid && isUsernameValid &&
        isPasswordValid && isCheckPasswordValid && isPolicyAgreed) {
        return true; 
    } else {
        const firstErrorField = document.querySelector('.input-container.error input, .agreement-container.error input'); 
        if (firstErrorField) { 
            firstErrorField.focus(); 
        }
        return false; 
    }
}

document.addEventListener('DOMContentLoaded', function() { 
    if (passwordRulesContainer) { 
        passwordRulesContainer.style.display = 'none'; 
    }
    if (passwordInput && passwordInput.value === '') { 
        isPasswordValidGlobal = false; 
    } else if (passwordInput) { 
        validateUserPassword(false); 
    }
});