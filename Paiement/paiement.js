document.addEventListener('DOMContentLoaded', function() {
    const cardInput = document.querySelector('input[name="card_number"]');
    const expiryInput = document.querySelector('input[name="expiry_date"]');
    const cvvInput = document.querySelector('input[name="cvv"]');
    const form = document.querySelector('#new-card-form form');
    const saveInfoCheckbox = document.getElementById('save_payment_info');
    const payWithSelectedCardBtn = document.getElementById('pay-with-selected-card');
    const showNewCardFormBtn = document.getElementById('show-new-card-form');
    const newCardForm = document.getElementById('new-card-form');
    const savedCards = document.querySelectorAll('.saved-card');
    let selectedCardId = null;
    let transactionMessage = null;

    // Si les éléments de formulaire existent
    if (cardInput) {
        // Placeholder pour la carte
        cardInput.placeholder = "XXXX XXXX XXXX XXXX";
        
        // Message d'erreur pour la carte
        let errorMsg = document.createElement('div');
        errorMsg.style = "color: #e74c3c; font-size: 0.95em; margin-bottom: 10px; display:none;";
        cardInput.parentNode.insertBefore(errorMsg, cardInput.nextSibling);

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
    }

    if (cvvInput) {
        // Placeholder pour le CVV
        cvvInput.placeholder = "123";
        
        // Message d'erreur pour le CVV
        let cvvErrorMsg = document.createElement('div');
        cvvErrorMsg.style = "color: #e74c3c; font-size: 0.95em; margin-bottom: 10px; display:none;";
        cvvInput.parentNode.insertBefore(cvvErrorMsg, cvvInput.nextSibling);

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
    }

    if (expiryInput) {
        // Date d'expiration : format MM/AA avec / automatique
        expiryInput.addEventListener('input', function() {
            let val = this.value.replace(/\D/g, '').slice(0, 4);
            if (val.length > 2) {
                val = val.slice(0,2) + '/' + val.slice(2);
            }
            this.value = val;
        });
    }

    // Gestion de la sélection des cartes enregistrées
    savedCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Éviter de déclencher lors du clic sur les boutons d'action
            if (e.target.closest('.card-actions') || e.target.closest('button')) {
                return;
            }
            
            // Retirer la sélection des autres cartes
            savedCards.forEach(c => c.classList.remove('selected'));
            // Sélectionner la carte cliquée
            this.classList.add('selected');
            selectedCardId = parseInt(this.dataset.cardId);
            
            // Activer le bouton de paiement
            if (payWithSelectedCardBtn) {
                payWithSelectedCardBtn.disabled = false;
            }
        });
    });

    // Gestion de l'affichage du formulaire de nouvelle carte
    if (showNewCardFormBtn) {
        showNewCardFormBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (newCardForm) {
                newCardForm.style.display = 'block';
                this.style.display = 'none';
                
                // Défaire la sélection des cartes existantes
                savedCards.forEach(c => c.classList.remove('selected'));
                selectedCardId = null;
                if (payWithSelectedCardBtn) {
                    payWithSelectedCardBtn.disabled = true;
                }
            }
        });
    }

    // Gestion du paiement avec carte sélectionnée
    if (payWithSelectedCardBtn) {
        payWithSelectedCardBtn.addEventListener('click', function() {
            if (!selectedCardId) {
                alert('Veuillez sélectionner une carte');
                return;
            }
            
            if (!confirm('Confirmez-vous le paiement avec cette carte ?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('pay_with_selected_card', true);
            formData.append('selected_card_id', selectedCardId);
            formData.append('panier_json', document.querySelector('input[name="panier_json"]')?.value || '[]');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showTransactionSuccess();
                    localStorage.setItem('synapse-cart', JSON.stringify([]));
                    setTimeout(() => window.location.href = "../Testing grounds/main.php", 3000);
                } else {
                    alert(data.message || "Erreur lors du traitement du paiement");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Une erreur est survenue lors du traitement du paiement");
            });
        });
    }

    // Confirmation pour nouveau paiement
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            let valid = true;
            
            if (cardInput && cvvInput) {
                let cardNumbers = cardInput.value.replace(/\D/g, '');
                let cvvNumbers = cvvInput.value.replace(/\D/g, '');
                
                const cardErrorMsg = cardInput.nextElementSibling;
                const cvvErrorMsg = cvvInput.nextElementSibling;

                if (cardNumbers.length !== 16) {
                    cardErrorMsg.textContent = "Le numéro de carte doit contenir 16 chiffres.";
                    cardErrorMsg.style.display = "block";
                    cardInput.focus();
                    valid = false;
                }
                if (cvvNumbers.length !== 3) {
                    cvvErrorMsg.textContent = "Le CVV doit contenir 3 chiffres.";
                    cvvErrorMsg.style.display = "block";
                    if (valid) cvvInput.focus();
                    valid = false;
                }
            }
            
            if (!valid) {
                return;
            }
            
            if (!confirm('Confirmez-vous le paiement ?')) {
                return;
            }

            // Préparation des données du formulaire pour l'envoi AJAX
            const formData = new FormData(form);
            formData.append('process_payment', true);
            
            // Si la case "Enregistrer mes informations" est cochée
            if (saveInfoCheckbox && saveInfoCheckbox.checked) {
                formData.append('save_info', true);
            }
            
            // Envoyer la requête AJAX pour traiter le paiement
            fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showTransactionSuccess();
                    form.querySelector('button[type="submit"]').disabled = true;
                    localStorage.setItem('synapse-cart', JSON.stringify([]));
                    setTimeout(() => window.location.href = "../Testing grounds/main.php", 3000);
                } else {
                    alert(data.message || "Erreur lors du traitement du paiement");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Une erreur est survenue lors du traitement du paiement");
            });
        });
    }

    function showTransactionSuccess() {
        if (!transactionMessage) {
            transactionMessage = document.createElement('div');
            transactionMessage.textContent = "Transaction validée";
            transactionMessage.style = "color: #45cf91; font-weight: bold; font-size: 1.3em; margin: 20px 0; text-align:center;";
            
            const container = document.querySelector('.paiement-container');
            if (container) {
                container.appendChild(transactionMessage);
            }
        }
    }
});

// Fonctions globales pour la gestion des cartes
function deleteCard(cardId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette carte ?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('delete_card', true);
    formData.append('card_id', cardId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur lors de la suppression de la carte');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur est survenue');
    });
}

function setDefaultCard(cardId) {
    const formData = new FormData();
    formData.append('set_default_card', true);
    formData.append('card_id', cardId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur lors de la définition de la carte par défaut');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur est survenue');
    });
}
// cvq