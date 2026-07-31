console.log("Login JS Loaded");

// Show / Hide Password

const togglePassword = document.getElementById("togglePassword");
const password = document.getElementById("password");

togglePassword.addEventListener("click", function () {

    if (password.type === "password") {

        password.type = "text";

    } else {

        password.type = "password";

    }

});

// Login Form Validation

const form = document.querySelector("form");

form.addEventListener("submit", function (e) {

    let enrollment = document.getElementById("enrollment").value;
    let passwordValue = document.getElementById("password").value;

    if (enrollment.length !== 12) {

        alert("Enrollment Number must be exactly 12 digits.");

        e.preventDefault();
        return;

    }

    if (passwordValue.trim() === "") {

        alert("Password is required.");

        e.preventDefault();
        return;

    }

});