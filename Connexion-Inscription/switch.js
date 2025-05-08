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

// Initialisation : ajouter le clic uniquement sur le premier conteneur rempli
document.getElementById("box1").addEventListener("click", swapStyles);
document.getElementById("box2").addEventListener("click", swapStyles);

// Masquer la section "Créer un compte" au chargement
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("register-section").style.display = "none";
    // Rendre le premier bouton non cliquable (par défaut actif)
    document.getElementById("box1").style.pointerEvents = "none";
});
