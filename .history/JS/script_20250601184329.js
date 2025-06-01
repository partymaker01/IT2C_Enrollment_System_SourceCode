const container = document.querySelector('.container');
const registerBtn = document.querySelector('.register-btn');
const loginBtn = document.querySelectorAll('.login-btn')
const forgotLink = document.getElementById('forgotLink');

registerBtn.addEventListener('click', () => {
    container.classList.remove('forgot-mode');
    container.classList.add('active');
});

loginBtn.forEach(btn => {
    btn.addEventListener('click', () => {
        container.classList.remove('active');
        container.classList.remove('forgot-mode');
    });
});

forgotLink.addEventListener('click', (e) => {
    e.preventDefault();
    container.classList.remove('active');
    container.classList.add('forgot-mode');
});

$(document).ready(function() {
    $('.select2').select2({
    placeholder: "Select an option",
    allowClear: true
    });
});