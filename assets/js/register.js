let eye = document.getElementById("eye");
let password = document.getElementById("password");


eye.onclick = function(){

    if(password.type === "password")
    {
        password.type = "text";
        eye.src="assets/icons/eye-off.png";
    }
    else
    {
        password.type = "password";
        eye.src="assets/icons/eye.png";
    }

}