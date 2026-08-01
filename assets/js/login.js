const togglePassword = document.querySelector('#togglePassword');
const password = document.querySelector('#password');

togglePassword.addEventListener('click', function () {
    // Check current type and swap
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    
    // Toggle icon looks (aankh khuli / aankh band)
    this.classList.toggle('fa-eye');
    this.classList.toggle('fa-eye-slash');
});