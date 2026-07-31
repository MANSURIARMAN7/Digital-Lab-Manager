console.log("JS Loaded");

// Password Show / Hide

const togglePassword = document.getElementById("togglePassword");
const password = document.getElementById("password");

togglePassword.addEventListener("click", function () {

    if (password.type === "password") {
        password.type = "text";
    } else {
        password.type = "password";
    }

});

// Confirm Password Show / Hide

const toggleConfirmPassword = document.getElementById("toggleConfirmPassword");
const confirmPassword = document.getElementById("confirmPassword");

toggleConfirmPassword.addEventListener("click", function () {

    if (confirmPassword.type === "password") {
        confirmPassword.type = "text";
    } else {
        confirmPassword.type = "password";
    }

});

// Form Validation

const form = document.querySelector("form");

form.addEventListener("submit", function (e) {

    console.log("Form Submitted");

    let enrollment = document.getElementById("enrollment").value;
    let mobile = document.getElementById("mobile").value;
    let passwordValue = document.getElementById("password").value;
    let confirmPasswordValue = document.getElementById("confirmPassword").value;

    if (enrollment.length !== 12) {
        alert("Enrollment Number must be exactly 12 digits.");
        e.preventDefault();
        return;
    }

    if (mobile.length !== 10) {
        alert("Mobile Number must be exactly 10 digits.");
        e.preventDefault();
        return;
    }

    if (passwordValue !== confirmPasswordValue) {
        alert("Password and Confirm Password do not match.");
        e.preventDefault();
        return;
    }

});