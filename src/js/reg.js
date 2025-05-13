function validateForm(gottenForm){
    var error = '';;
    
    var gottenFirstName = gottenForm.firstName.value;
    var gottenLastName = gottenForm.lastName.value;
    var gottenUsername = gottenForm.username.value;
    var gottenEmail = gottenForm.email.value;
    var gottenPassword = gottenForm.password.value;

    if(gottenFirstName.length < 2 || gottenFirstName.length > 50
        || gottenLastName.length < 2 || gottenLastName.length > 50)
        error = 'Ім\'я/прізвище повинно містити від 2 до 50 символів';
    else if(!gottenEmail.includes('@'))
        error = 'Електронна адреса повинна містити символ "@"';
    else if(gottenPassword.length < 8 || gottenPassword.length > 256)
        error = 'Пароль повинен містити від 8 до 256 символів';
    else if(gottenUsername.length < 2 || gottenUsername.length > 50)
        error = 'Логін повинен містити від 2 до 50 символів';
    
    if (error != ''){
        document.getElementById('error').innerHTML = error;
        return false;
    }

    return false;
}