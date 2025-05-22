function swapStyles() {
    let box1 = document.getElementById("box1");
    let box2 = document.getElementById("box2");
    let loginSection = document.getElementById("login-section");
    let registerSection = document.getElementById("register-section");

    // Échanger les classes pour changer la couleur
    box1.classList.toggle("filled");
    box1.classList.toggle("empty");
    box2.classList.toggle("filled");
    box2.classList.toggle("empty");

    // Vérifier quel bouton est actif et afficher la section correspondante
    if (box1.classList.contains("filled")) {
        loginSection.style.display = "none";
        registerSection.style.display = "flex";
        box1.style.pointerEvents = "auto";
        box2.style.pointerEvents = "none";

    } else {
        loginSection.style.display = "flex";
        registerSection.style.display = "none";
        box1.style.pointerEvents = "none";
        box2.style.pointerEvents = "auto";
    }
}



// Masquer la section "Créer un compte" au chargement
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("register-section").style.display = "none";
    // Rendre le premier bouton non cliquable (par défaut actif)
    document.getElementById("box1").style.pointerEvents = "none";

    // Gestion des boutons de bascule
    document.getElementById("box1").addEventListener("click", swapStyles);
    document.getElementById("box2").addEventListener("click", swapStyles);

    // Affichage / masquage du mot de passe (connexion)
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



    // Variables globales pour la validation du mot de passe
        // Variables pour les champs de mot de passe
    const passwordInput = document.getElementById("register-password");
    const confirmInput = document.getElementById("register-confirm");
        // Variables pour les messages de validation
    const passwordValidationMessage = document.getElementById("password-validation-message");
    const confirmValidationMessage = document.getElementById("confirm-validation-message");
    
    // Ajout d'écouteurs d'événements pour la validation du mot de passe
    passwordInput.addEventListener('input', validatePasswordFormat);
    passwordInput.addEventListener('blur', validatePasswordFormat);
    confirmInput.addEventListener('input', validateForm);
    confirmInput.addEventListener('blur', validateForm);

    // Validation du formulaire d'inscription
    function validateForm() {
        console.log("confirmValidationMessage:", confirmValidationMessage);
        const password = passwordInput.value.trim();
        const confirmPassword = confirmInput.value.trim();
    
        if (password && confirmPassword && password !== confirmPassword) {
            confirmValidationMessage.textContent = "Les mots de passe ne correspondent pas.";
            confirmValidationMessage.style.color = "#e74c3c";
            confirmInput.style.borderColor = "#e74c3c";
            return false;
        } else if (confirmPassword && password === confirmPassword) {
            confirmValidationMessage.textContent = "✓ Les mots de passe correspondent";
            confirmValidationMessage.style.color = "#2ecc71";
            confirmInput.style.borderColor = "#2ecc71";
            return true;
        } else {
            confirmValidationMessage.textContent = "";
            confirmInput.style.borderColor = ""; // ou un style neutre
            return false;
        }
    }


    function validatePasswordFormat() {
        const passwordValue = passwordInput.value.trim();
        let isValid = false;
    
        const passwordRegex = /^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/;
    
        if (!passwordValue) {
            passwordValidationMessage.textContent = "Ce champ est requis.";
            passwordValidationMessage.style.color = "#e74c3c";
            passwordInput.style.borderColor = "#e74c3c";
            isValid = false;
        } else if (passwordRegex.test(passwordValue)) {
            passwordValidationMessage.textContent = "✓ Mot de passe valide";
            passwordValidationMessage.style.color = "#2ecc71";
            passwordInput.style.borderColor = "#2ecc71";
            isValid = true;
        } else {
            passwordValidationMessage.textContent = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
            passwordValidationMessage.style.color = "#e74c3c";
            passwordInput.style.borderColor = "#e74c3c";
            isValid = false;
        }
    
        // Toujours revalider la correspondance après le format
        validateForm();
    
        return isValid;
    }



});



// cvq