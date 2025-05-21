<?php

session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../Connexion-Inscription/login_form.php');
    exit;
}

// Traitement AJAX du paiement
if (isset($_POST['process_payment'])) {
    $response = ['success' => true, 'message' => 'Paiement traité avec succès'];
    
    // Enregistrer les informations de paiement si demandé
    if (isset($_POST['save_info']) && $_POST['save_info']) {
        $user_id = $_SESSION['user_id'];
        $card_number = substr(str_replace(' ', '', $_POST['card_number']), -4); // Stocker uniquement les 4 derniers chiffres
        $expiry_date = $_POST['expiry_date'];
        
        // On ne stocke pas le CVV pour des raisons de sécurité
        
        $conn = new mysqli('localhost', 'root', '', 'user_db');
        if ($conn->connect_error) {
            $response = ['success' => false, 'message' => 'Erreur de connexion à la base de données'];
            echo json_encode($response);
            exit;
        }
        
        // Vérifier si une table pour les infos de paiement existe, sinon la créer
        $conn->query("CREATE TABLE IF NOT EXISTS payment_info (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            card_last_four VARCHAR(4) NOT NULL,
            expiry_date VARCHAR(5) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user_form(id)
        )");
        
        // Vérifier si l'utilisateur a déjà des informations de paiement
        $stmt = $conn->prepare("SELECT id FROM payment_info WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Mettre à jour les informations existantes
            $stmt = $conn->prepare("UPDATE payment_info SET card_last_four = ?, expiry_date = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $card_number, $expiry_date, $user_id);
        } else {
            // Insérer de nouvelles informations
            $stmt = $conn->prepare("INSERT INTO payment_info (user_id, card_last_four, expiry_date) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $card_number, $expiry_date);
        }
        
        if (!$stmt->execute()) {
            $response = ['success' => false, 'message' => 'Erreur lors de l\'enregistrement des informations de paiement'];
        }
        
        $stmt->close();
        $conn->close();
    }
    
    // Traitement du panier
    if (isset($_POST['panier_json'])) {
        $panier = json_decode($_POST['panier_json'], true);
        
        if (is_array($panier)) { // Validation que $panier est bien un tableau
            // Enregistrer les achats dans la base de données
            $conn = new mysqli('localhost', 'root', '', 'activity');
            if (!$conn->connect_error) {
                $user_id = $_SESSION['user_id'];
                
                // Vérifier si la table activites_achats existe, sinon la créer
                $conn->query("CREATE TABLE IF NOT EXISTS activites_achats (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    activite_id INT NOT NULL,
                    date_achat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (activite_id) REFERENCES activites(id)
                )");
                
                $stmt = $conn->prepare("INSERT INTO activites_achats (user_id, activite_id) VALUES (?, ?)");
                
                foreach ($panier as $item) {
                    $activity_id = $item['id'];
                    
                    // Vérifier si l'utilisateur est déjà inscrit à cette activité
                    $check_stmt = $conn->prepare("SELECT id FROM activites_achats WHERE user_id = ? AND activite_id = ?");
                    $check_stmt->bind_param("ii", $user_id, $activity_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    // Seulement ajouter si l'utilisateur n'est pas déjà inscrit
                    if ($check_result->num_rows == 0) {
                        $stmt->bind_param("ii", $user_id, $activity_id);
                        $stmt->execute();
                    }
                    
                    $check_stmt->close();
                }
                
                $stmt->close();
                $conn->close();
            }
            
            // Vider le panier dans la session
            $_SESSION['panier'] = [];
        } else {
            $response = ['success' => false, 'message' => 'Format de panier invalide'];
        }
    }
    
    echo json_encode($response);
    exit;
}

// Validation du panier JSON et gestion du panier en session
if (isset($_POST['panier_json'])) {
    $panier = json_decode($_POST['panier_json'], true);
    
    if (is_array($panier)) { // Vérifier que $panier est bien un tableau
        $_SESSION['panier'] = array_map(function($item) {
            return $item['id'];
        }, $panier);
    } else {
        echo "Erreur: Format de panier invalide.";
        exit;
    }
}

if (!isset($_SESSION['panier']) || empty($_SESSION['panier'])) {
    echo "Votre panier est vide.";
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'activity');
if ($conn->connect_error) {
    die("Erreur de connexion à la base : " . $conn->connect_error);
}

$ids = $_SESSION['panier'];
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

$stmt = $conn->prepare("SELECT prix FROM activites WHERE id IN ($placeholders)");
if (!$stmt) {
    die("Erreur dans la requête SQL : " . $conn->error);
}
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
while ($row = $result->fetch_assoc()) {
    $total += floatval($row['prix']);
}

$stmt->close();
$conn->close();

// Vérifier si l'utilisateur a déjà des informations de paiement enregistrées
$user_id = $_SESSION['user_id'];
$payment_info = null;

// Connexion directe à la base de données des utilisateurs (user_db)
$user_conn = new mysqli('localhost', 'root', '', 'user_db');
if (!$user_conn->connect_error) {
    // Vérifier si la table payment_info existe
    $result = $user_conn->query("SHOW TABLES LIKE 'payment_info'");
    if ($result->num_rows > 0) {
        $stmt = $user_conn->prepare("SELECT card_last_four, expiry_date FROM payment_info WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $payment_info = $result->fetch_assoc();
            }
            $stmt->close();
        }
    }
    $user_conn->close();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement | Synapse</title>
    <link rel="stylesheet" href="../Testing grounds/Accueil.css" />
    <link rel="stylesheet" href="../TEMPLATE/Nouveauhead.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; }
        .paiement-container {
            width: 90%;
            max-width: 500px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 40px 30px;
        }
        .total { font-weight: bold; font-size: 1.2em; margin-bottom: 20px; color: #45cf91; }
        h2 { color: #828977; text-align: center; margin-bottom: 30px; }
        form label { font-weight: 600; display: block; margin-top: 15px; }
        form input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 16px;
        }
        .saved-card-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #45cf91;
        }
        .saved-card-info h3 {
            color: #333;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .saved-card-info p {
            margin: 5px 0;
            color: #333;
        }
        .saved-card-info i {
            color: #45cf91;
            margin-right: 10px;
        }
        .checkout-button {
            width: 100%;
            background-color: #45cf91;
            color: #fff;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            margin-top: 20px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .checkout-button:hover {
            background-color: #3abd7a;
        }
        .secondary-button {
            width: 100%;
            background-color: #f1f1f1;
            color: #333;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            margin-top: 15px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .secondary-button:hover {
            background-color: #e0e0e0;
        }
        .save-info-container {
            margin-top: 20px;
            display: flex;
            align-items: center;
        }
        .save-info-container input[type="checkbox"] {
            margin-right: 10px;
        }
        .payment-options {
            margin-bottom: 30px;
        }
        @media (max-width: 600px) {
            .paiement-container { padding: 20px 5px; }
        }
    </style>
</head>
<body>
<?php include '../TEMPLATE/Nouveauhead.php'; ?>

<div class="paiement-container">
    <h2>Paiement</h2>
    <div class="total">
        Montant à payer : <?= number_format($total, 2) ?> €
    </div>

    <?php if ($payment_info): ?>
    <div class="payment-options">
        <div class="saved-card-info">
            <h3>Carte enregistrée</h3>
            <p><i class="fa-solid fa-credit-card"></i> Carte se terminant par <?= $payment_info['card_last_four'] ?></p>
            <p><i class="fa-regular fa-calendar"></i> Expire le <?= $payment_info['expiry_date'] ?></p>
            
            <form action="#" method="post" id="use-saved-card-form">
                <input type="hidden" name="use_saved_card" value="1">
                <input type="hidden" name="montant" value="<?= $total ?>">
                <input type="hidden" name="panier_json" value='<?= htmlspecialchars($_POST['panier_json'] ?? '[]') ?>'>
                <button type="submit" class="checkout-button"><i class="fa-solid fa-check-circle"></i> Utiliser cette carte</button>
            </form>
            
            <button id="show-new-card-form" class="secondary-button"><i class="fa-solid fa-plus-circle"></i> Utiliser une autre carte</button>
        </div>
        
        <div id="new-card-form" style="display: none;">
            <h3 style="margin-bottom:20px;">Informations de carte bancaire</h3>
            
            <form action="#" method="post">
                <label>Numéro de carte :</label>
                <input type="text" name="card_number" maxlength="19" required autocomplete="cc-number">

                <label>Date d'expiration :</label>
                <input type="text" name="expiry_date" placeholder="MM/AA" maxlength="5" required autocomplete="cc-exp">

                <label>CVV :</label>
                <input type="text" name="cvv" maxlength="4" required autocomplete="cc-csc">

                <div class="save-info-container">
                    <input type="checkbox" id="save_payment_info" name="save_payment_info" value="1" checked>
                    <label for="save_payment_info">Mettre à jour mes informations pour mes prochains achats</label>
                </div>

                <input type="hidden" name="montant" value="<?= $total ?>">
                <input type="hidden" name="panier_json" value='<?= htmlspecialchars($_POST['panier_json'] ?? '[]') ?>'>
                <button type="submit" class="checkout-button"><i class="fa-solid fa-credit-card"></i> Payer maintenant</button>
            </form>
        </div>
    </div>
    <?php else: ?>
    <h3 style="margin-bottom:20px;">Informations de carte bancaire</h3>

    <form action="#" method="post">
        <label>Numéro de carte :</label>
        <input type="text" name="card_number" maxlength="19" required autocomplete="cc-number">

        <label>Date d'expiration :</label>
        <input type="text" name="expiry_date" placeholder="MM/AA" maxlength="5" required autocomplete="cc-exp">

        <label>CVV :</label>
        <input type="text" name="cvv" maxlength="4" required autocomplete="cc-csc">

        <div class="save-info-container">
            <input type="checkbox" id="save_payment_info" name="save_payment_info" value="1">
            <label for="save_payment_info">Enregistrer mes informations pour mes prochains achats</label>
        </div>

        <input type="hidden" name="montant" value="<?= $total ?>">
        <input type="hidden" name="panier_json" value='<?= htmlspecialchars($_POST['panier_json'] ?? '[]') ?>'>
        <button type="submit" class="checkout-button"><i class="fa-solid fa-credit-card"></i> Payer maintenant</button>
    </form>
    <?php endif; ?>
</div>

<?php include '../TEMPLATE/footer.php'; ?>
<script src="../TEMPLATE/Nouveauhead.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cardInput = document.querySelector('input[name="card_number"]');
    const expiryInput = document.querySelector('input[name="expiry_date"]');
    const cvvInput = document.querySelector('input[name="cvv"]');
    const form = document.querySelector('form');
    const saveInfoCheckbox = document.getElementById('save_payment_info');
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

    // Gestion de l'option "utiliser une autre carte"
    const showNewCardFormBtn = document.getElementById('show-new-card-form');
    const newCardForm = document.getElementById('new-card-form');
    
    if (showNewCardFormBtn && newCardForm) {
        showNewCardFormBtn.addEventListener('click', function() {
            document.querySelector('.saved-card-info').style.display = 'none';
            newCardForm.style.display = 'block';
        });
    }
    
    // Gestion du paiement avec carte enregistrée
    const useSavedCardForm = document.getElementById('use-saved-card-form');
    if (useSavedCardForm) {
        useSavedCardForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!confirm('Confirmez-vous le paiement avec votre carte enregistrée ?')) {
                return;
            }
            
            const formData = new FormData(useSavedCardForm);
            formData.append('process_payment', true);
            
            fetch(useSavedCardForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const transactionMessage = document.createElement('div');
                    transactionMessage.textContent = "Transaction validée";
                    transactionMessage.style = "color: #45cf91; font-weight: bold; font-size: 1.3em; margin: 20px 0; text-align:center;";
                    useSavedCardForm.parentNode.insertBefore(transactionMessage, useSavedCardForm.nextSibling);
                    
                    useSavedCardForm.querySelector('button[type="submit"]').disabled = true;
                    if (showNewCardFormBtn) showNewCardFormBtn.disabled = true;
                    
                    localStorage.setItem('synapse-cart', JSON.stringify([]));
                    
                    setTimeout(function() {
                        window.location.href = "../Testing grounds/main.php";
                    }, 3000);
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
            // Ne pas valider pour le formulaire de carte sauvegardée
            if (this.id === 'use-saved-card-form') return;
            
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
                e.preventDefault();
                return;
            }
            
            e.preventDefault();
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
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher le message de validation
                    if (!transactionMessage) {
                        transactionMessage = document.createElement('div');
                        transactionMessage.textContent = "Transaction validée";
                        transactionMessage.style = "color: #45cf91; font-weight: bold; font-size: 1.3em; margin: 20px 0; text-align:center;";
                        form.parentNode.insertBefore(transactionMessage, form.nextSibling);
                    }
                    // Désactiver le bouton
                    form.querySelector('button[type="submit"]').disabled = true;
                    
                    // Vider le localStorage pour le panier
                    localStorage.setItem('synapse-cart', JSON.stringify([]));
                    
                    // Redirige après 3 secondes
                    setTimeout(function() {
                        window.location.href = "../Testing grounds/main.php";
                    }, 3000);
                } else {
                    // Afficher un message d'erreur
                    alert(data.message || "Erreur lors du traitement du paiement");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Une erreur est survenue lors du traitement du paiement");
            });
        });
    }
});
</script>
</body>
</html>