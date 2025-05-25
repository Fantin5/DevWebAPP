function swapStyles() {
    let box1 = document.getElementById("box1");
    let box2 = document.getElementById("box2");
    let loginSection = document.getElementById("login-section");
    let registerSection = document.getElementById("register-section");

    // Toggle active states
    box1.classList.toggle("filled");
    box1.classList.toggle("empty");
    box2.classList.toggle("filled");
    box2.classList.toggle("empty");

    // Smooth transition between forms
    if (box2.classList.contains("filled")) {
        // Switching to register
        loginSection.style.transform = "translateX(-100%)";
        loginSection.style.opacity = "0";
        
        setTimeout(() => {
            loginSection.style.display = "none";
            registerSection.style.display = "flex";
            registerSection.style.transform = "translateX(100%)";
            registerSection.style.opacity = "0";
            
            setTimeout(() => {
                registerSection.style.transform = "translateX(0)";
                registerSection.style.opacity = "1";
            }, 50);
        }, 200);
        
        // Update pointer events
        box1.style.pointerEvents = "auto";
        box2.style.pointerEvents = "none";
    } else {
        // Switching to login
        registerSection.style.transform = "translateX(100%)";
        registerSection.style.opacity = "0";
        
        setTimeout(() => {
            registerSection.style.display = "none";
            loginSection.style.display = "flex";
            loginSection.style.transform = "translateX(-100%)";
            loginSection.style.opacity = "0";
            
            setTimeout(() => {
                loginSection.style.transform = "translateX(0)";
                loginSection.style.opacity = "1";
            }, 50);
        }, 200);
        
        // Update pointer events
        box1.style.pointerEvents = "none";
        box2.style.pointerEvents = "auto";
    }
}

// Initialize page state and event listeners
document.addEventListener("DOMContentLoaded", function () {
    // Set initial state
    const loginSection = document.getElementById("login-section");
    const registerSection = document.getElementById("register-section");
    const box1 = document.getElementById("box1");
    const box2 = document.getElementById("box2");
    
    // Initialize login section as active
    loginSection.style.display = "flex";
    loginSection.style.transform = "translateX(0)";
    loginSection.style.opacity = "1";
    loginSection.style.transition = "all 0.3s cubic-bezier(0.4, 0, 0.2, 1)";
    
    // Initialize register section as hidden
    registerSection.style.display = "none";
    registerSection.style.transform = "translateX(100%)";
    registerSection.style.opacity = "0";
    registerSection.style.transition = "all 0.3s cubic-bezier(0.4, 0, 0.2, 1)";
    
    // Set initial button states
    box1.classList.add("filled");
    box1.classList.remove("empty");
    box2.classList.add("empty");
    box2.classList.remove("filled");
    
    // Set initial pointer events
    box1.style.pointerEvents = "none";
    box2.style.pointerEvents = "auto";

    // Add event listeners for switch buttons
    box1.addEventListener("click", swapStyles);
    box2.addEventListener("click", swapStyles);

    // Password toggle functionality
    const toggleConfigs = [
        { toggleId: "toggle-login-password", inputId: "login-password" },
        { toggleId: "toggle-register-password", inputId: "register-password" },
        { toggleId: "toggle-register-confirm", inputId: "register-confirm" },
    ];

    toggleConfigs.forEach(({ toggleId, inputId }) => {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        if (toggle && input) {
            toggle.addEventListener("click", function () {
                const isPassword = input.type === "password";
                input.type = isPassword ? "text" : "password";
                this.classList.toggle("fa-eye");
                this.classList.toggle("fa-eye-slash");
            });
        }
    });

    // Password validation functionality
    const passwordInput = document.getElementById("register-password");
    const confirmInput = document.getElementById("register-confirm");
    const passwordValidationMessage = document.getElementById("password-validation-message");
    const confirmValidationMessage = document.getElementById("confirm-validation-message");
    
    if (passwordInput && confirmInput && passwordValidationMessage && confirmValidationMessage) {
        passwordInput.addEventListener('input', validatePasswordFormat);
        passwordInput.addEventListener('blur', validatePasswordFormat);
        confirmInput.addEventListener('input', validateForm);
        confirmInput.addEventListener('blur', validateForm);

        function validateForm() {
            const password = passwordInput.value.trim();
            const confirmPassword = confirmInput.value.trim();
        
            if (password && confirmPassword && password !== confirmPassword) {
                confirmValidationMessage.textContent = "❌ Les mots de passe ne correspondent pas";
                confirmValidationMessage.style.color = "#ff4757";
                confirmInput.style.borderColor = "#ff4757";
                return false;
            } else if (confirmPassword && password === confirmPassword) {
                confirmValidationMessage.textContent = "✅ Les mots de passe correspondent";
                confirmValidationMessage.style.color = "#2ed573";
                confirmInput.style.borderColor = "#2ed573";
                return true;
            } else {
                confirmValidationMessage.textContent = "";
                confirmInput.style.borderColor = "";
                return false;
            }
        }

        function validatePasswordFormat() {
            const passwordValue = passwordInput.value.trim();
            const passwordRegex = /^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/;
        
            if (!passwordValue) {
                passwordValidationMessage.textContent = "⚠️ Ce champ est requis";
                passwordValidationMessage.style.color = "#ff4757";
                passwordInput.style.borderColor = "#ff4757";
            } else if (passwordRegex.test(passwordValue)) {
                passwordValidationMessage.textContent = "✅ Mot de passe valide";
                passwordValidationMessage.style.color = "#2ed573";
                passwordInput.style.borderColor = "#2ed573";
            } else {
                passwordValidationMessage.textContent = "⚠️ Minimum 8 caractères avec majuscule, minuscule, chiffre et caractère spécial";
                passwordValidationMessage.style.color = "#ff4757";
                passwordInput.style.borderColor = "#ff4757";
            }
        
            validateForm();
        }
    }
});



// cvq