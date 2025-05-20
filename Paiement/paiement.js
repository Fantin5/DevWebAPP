document.addEventListener('DOMContentLoaded', function() {
    const cardInput = document.querySelector('input[name="card_number"]');
    const expiryInput = document.querySelector('input[name="expiry_date"]');
    const cvvInput = document.querySelector('input[name="cvv"]');
    const form = document.querySelector('form');
    let transactionMessage = null;

    // Placeholder pour la carte
    cardInput.placeholder = "XXXX XXXX XXXX XXXX";
    // Placeholder pour le CVV
    cvvInput.placeholder = "123";

    // Message d'erreur pour la carte
    let errorMsg = document.createElement('div');
    errorMsg.style = "color: #e74c3c; font-size: 0.95em; margin-bottom: 10px; display:none;";
    cardInput.parentNode.insertBefore(errorMsg, cardInput.nextSibling);

    // Message d'erreur pour le CVV
    let cvvErrorMsg = document.createElement('div');
    cvvErrorMsg.style = "color: #e74c3c; font-size: 0.95em; margin-bottom: 10px; display:none;";
    cvvInput.parentNode.insertBefore(cvvErrorMsg, cvvInput.nextSibling);

    // Affichage dynamique des chiffres avec espaces tous les 4 chiffres pour la carte
    cardInput.addEventListener('input', function() {
        let numbers = this.value.replace(/\D/g, '').slice(0, 16);
        let formatted = numbers.replace(/(.{4})/g, '$1 ').trim();
        this.value = formatted;

        // Affiche l'erreur si pas 16 chiffres
        if (numbers.length === 16 || numbers.length === 0) {
            errorMsg.style.display = "none";
        } else {
            errorMsg.textContent = "Le numéro de carte doit contenir 16 chiffres.";
            errorMsg.style.display = "block";
        }
    });

    // CVV : 3 chiffres uniquement
    cvvInput.addEventListener('input', function() {
        let numbers = this.value.replace(/\D/g, '').slice(0, 3);
        this.value = numbers;

        // Affiche l'erreur si pas 3 chiffres
        if (numbers.length === 3 || numbers.length === 0) {
            cvvErrorMsg.style.display = "none";
        } else {
            cvvErrorMsg.textContent = "Le CVV doit contenir 3 chiffres.";
            cvvErrorMsg.style.display = "block";
        }
    });

    // Date d'expiration : format MM/AA avec / automatique
    expiryInput.addEventListener('input', function() {
        let val = this.value.replace(/\D/g, '').slice(0, 4);
        if (val.length > 2) {
            val = val.slice(0,2) + '/' + val.slice(2);
        }
        this.value = val;
    });

    // Confirmation avant paiement
    form.addEventListener('submit', function(e) {
        let cardNumbers = cardInput.value.replace(/\D/g, '');
        let cvvNumbers = cvvInput.value.replace(/\D/g, '');
        let valid = true;

        if (cardNumbers.length !== 16) {
            errorMsg.textContent = "Le numéro de carte doit contenir 16 chiffres.";
            errorMsg.style.display = "block";
            cardInput.focus();
            valid = false;
        }
        if (cvvNumbers.length !== 3) {
            cvvErrorMsg.textContent = "Le CVV doit contenir 3 chiffres.";
            cvvErrorMsg.style.display = "block";
            if (valid) cvvInput.focus();
            valid = false;
        }
        if (!valid) {
            e.preventDefault();
            return;
        }
        e.preventDefault();
        if (!confirm('Confirmez-vous le paiement ?')) {
            return;
        }
        // Affiche le message de validation
        if (!transactionMessage) {
            transactionMessage = document.createElement('div');
            transactionMessage.textContent = "Transaction validée";
            transactionMessage.style = "color: #45cf91; font-weight: bold; font-size: 1.3em; margin: 20px 0; text-align:center;";
            form.parentNode.insertBefore(transactionMessage, form.nextSibling);
        }
        // Désactive le bouton
        form.querySelector('button[type="submit"]').disabled = true;
        // Redirige après 5 secondes
        setTimeout(function() {
            window.location.href = "../Testing grounds/main.php";
        }, 5000);
    });
});