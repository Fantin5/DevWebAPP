<?php

session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../Connexion-Inscription/login_form.php');
    exit;
}

// Traitement AJAX pour supprimer une carte
if (isset($_POST['delete_card'])) {
    $card_id = intval($_POST['card_id']);
    $user_id = $_SESSION['user_id'];
    
    $user_conn = new mysqli('localhost', 'root', '', 'user_db');
    if (!$user_conn->connect_error) {
        // Vérifier que la carte appartient à l'utilisateur
        $stmt = $user_conn->prepare("DELETE FROM payment_info WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $card_id, $user_id);
        $success = $stmt->execute();
        
        // Si on supprime la carte par défaut, définir une autre carte comme défaut
        if ($success) {
            $stmt = $user_conn->prepare("SELECT COUNT(*) as count FROM payment_info WHERE user_id = ? AND is_default = 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] == 0) {
                // Définir la première carte restante comme défaut
                $stmt = $user_conn->prepare("UPDATE payment_info SET is_default = 1 WHERE user_id = ? LIMIT 1");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
            }
        }
        
        $stmt->close();
        $user_conn->close();
        
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// Traitement AJAX pour définir une carte par défaut
if (isset($_POST['set_default_card'])) {
    $card_id = intval($_POST['card_id']);
    $user_id = $_SESSION['user_id'];
    
    $user_conn = new mysqli('localhost', 'root', '', 'user_db');
    if (!$user_conn->connect_error) {
        // Retirer le statut par défaut de toutes les cartes de l'utilisateur
        $user_conn->query("UPDATE payment_info SET is_default = 0 WHERE user_id = $user_id");
        
        // Définir la carte sélectionnée comme défaut
        $stmt = $user_conn->prepare("UPDATE payment_info SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $card_id, $user_id);
        $success = $stmt->execute();
        
        $stmt->close();
        $user_conn->close();
        
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// Traitement AJAX du paiement
if (isset($_POST['process_payment'])) {
    $response = ['success' => true, 'message' => 'Paiement traité avec succès'];
    
    // Enregistrer les informations de paiement si demandé
    if (isset($_POST['save_info']) && $_POST['save_info']) {
        $user_id = $_SESSION['user_id'];
        $card_number = substr(str_replace(' ', '', $_POST['card_number']), -4);
        $expiry_date = $_POST['expiry_date'];
        $card_name = isset($_POST['card_name']) && !empty($_POST['card_name']) ? $_POST['card_name'] : 'Ma carte';
        
        $conn = new mysqli('localhost', 'root', '', 'user_db');
        if ($conn->connect_error) {
            $response = ['success' => false, 'message' => 'Erreur de connexion à la base de données'];
            echo json_encode($response);
            exit;
        }
        
        // Créer la table si elle n'existe pas
        $conn->query("CREATE TABLE IF NOT EXISTS payment_info (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            card_name VARCHAR(100) DEFAULT 'Ma carte',
            card_last_four VARCHAR(4) NOT NULL,
            expiry_date VARCHAR(5) NOT NULL,
            is_default TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user_form(id)
        )");
        
        // Vérifier s'il s'agit de la première carte (sera par défaut)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM payment_info WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $is_default = ($count == 0) ? 1 : 0;
        
        // Insérer la nouvelle carte
        $stmt = $conn->prepare("INSERT INTO payment_info (user_id, card_name, card_last_four, expiry_date, is_default) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $user_id, $card_name, $card_number, $expiry_date, $is_default);
        
        if (!$stmt->execute()) {
            $response = ['success' => false, 'message' => 'Erreur lors de l\'enregistrement des informations de paiement'];
        }
        
        $stmt->close();
        $conn->close();
    }
    
    // Traitement du panier (inchangé)
    if (isset($_POST['panier_json'])) {
        $panier = json_decode($_POST['panier_json'], true);
        
        if (is_array($panier)) {
            $conn = new mysqli('localhost', 'root', '', 'activity');
            if (!$conn->connect_error) {
                $user_id = $_SESSION['user_id'];
                
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
                    
                    $check_stmt = $conn->prepare("SELECT id FROM activites_achats WHERE user_id = ? AND activite_id = ?");
                    $check_stmt->bind_param("ii", $user_id, $activity_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows == 0) {
                        $stmt->bind_param("ii", $user_id, $activity_id);
                        $stmt->execute();
                    }
                    
                    $check_stmt->close();
                }
                
                $stmt->close();
                $conn->close();
            }
            
            $_SESSION['panier'] = [];
        } else {
            $response = ['success' => false, 'message' => 'Format de panier invalide'];
        }
    }
    
    echo json_encode($response);
    exit;
}

// Traitement AJAX du paiement avec carte sélectionnée
if (isset($_POST['pay_with_selected_card'])) {
    $card_id = intval($_POST['selected_card_id']);
    $user_id = $_SESSION['user_id'];
    
    // Vérifier que la carte appartient à l'utilisateur
    $user_conn = new mysqli('localhost', 'root', '', 'user_db');
    if ($user_conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion']);
        exit;
    }
    
    $stmt = $user_conn->prepare("SELECT id FROM payment_info WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $card_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $user_conn->close();
        echo json_encode(['success' => false, 'message' => 'Carte non trouvée']);
        exit;
    }
    
    $stmt->close();
    $user_conn->close();
    
    // Traiter le panier
    if (isset($_POST['panier_json'])) {
        // Decode HTML entities before parsing JSON
        $panier_json = html_entity_decode($_POST['panier_json'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $panier = json_decode($panier_json, true);
        
        if (is_array($panier)) {
            $conn = new mysqli('localhost', 'root', '', 'activity');
            if ($conn->connect_error) {
                echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
                exit;
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                $stmt = $conn->prepare("INSERT INTO activites_achats (user_id, activite_id) VALUES (?, ?)");
                
                foreach ($panier as $item) {
                    $activity_id = intval($item['id']);
                    
                    // Check if already purchased
                    $check_stmt = $conn->prepare("SELECT id FROM activites_achats WHERE user_id = ? AND activite_id = ?");
                    $check_stmt->bind_param("ii", $user_id, $activity_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows == 0) {
                        $stmt->bind_param("ii", $user_id, $activity_id);
                        if (!$stmt->execute()) {
                            throw new Exception("Erreur lors de l'insertion de l'activité");
                        }
                    }
                    
                    $check_stmt->close();
                }
                
                // If we get here, all inserts were successful
                $conn->commit();
                $_SESSION['panier'] = [];
                
                echo json_encode(['success' => true, 'message' => 'Paiement traité avec succès']);
                exit;
                
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Erreur lors du traitement: ' . $e->getMessage()]);
                exit;
            } finally {
                if (isset($stmt)) $stmt->close();
                $conn->close();
            }
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Format de panier invalide']);
    exit;
}

// Validation du panier JSON et gestion du panier en session (inchangé)
if (isset($_POST['panier_json'])) {
    $panier = json_decode($_POST['panier_json'], true);
    
    if (is_array($panier)) {
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

// Calcul du total (inchangé)
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

// Récupérer toutes les cartes enregistrées de l'utilisateur
$user_id = $_SESSION['user_id'];
$payment_cards = [];

$user_conn = new mysqli('localhost', 'root', '', 'user_db');
if (!$user_conn->connect_error) {
    $result = $user_conn->query("SHOW TABLES LIKE 'payment_info'");
    if ($result->num_rows > 0) {
        $stmt = $user_conn->prepare("SELECT id, card_name, card_last_four, expiry_date, is_default FROM payment_info WHERE user_id = ? ORDER BY is_default DESC, created_at ASC");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $payment_cards[] = $row;
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
        .saved-cards-container {
            margin-bottom: 30px;
        }
        .saved-card {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        .saved-card:hover {
            border-color: #45cf91;
            box-shadow: 0 2px 8px rgba(69, 207, 145, 0.2);
        }
        .saved-card.selected {
            border-color: #45cf91;
            background-color: #f0fdf4;
        }
        .saved-card.default {
            border-left: 4px solid #45cf91;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .card-info h3 {
            color: #333;
            margin: 0 0 5px 0;
            font-size: 1.1em;
        }
        .card-info p {
            margin: 5px 0;
            color: #666;
        }
        .card-info i {
            color: #45cf91;
            margin-right: 10px;
        }
        .card-actions {
            display: flex;
            gap: 10px;
        }
        .card-action-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .card-action-btn:hover {
            background-color: #e0e0e0;
        }
        .card-action-btn.delete:hover {
            color: #e74c3c;
            background-color: #ffeaea;
        }
        .card-action-btn.default:hover {
            color: #45cf91;
            background-color: #f0fdf4;
        }
        .default-badge {
            background-color: #45cf91;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
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
        .checkout-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
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
        .new-card-form {
            display: none;
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

    <?php if (!empty($payment_cards)): ?>
    <div class="saved-cards-container">
        <h3 style="margin-bottom: 20px;">Mes cartes enregistrées</h3>
        
        <?php foreach ($payment_cards as $card): ?>
        <div class="saved-card <?= $card['is_default'] ? 'default' : '' ?>" data-card-id="<?= $card['id'] ?>">
            <div class="card-header">
                <div class="card-info">
                    <h3>
                        <?= htmlspecialchars($card['card_name']) ?>
                        <?php if ($card['is_default']): ?>
                            <span class="default-badge">Par défaut</span>
                        <?php endif; ?>
                    </h3>
                    <p><i class="fa-solid fa-credit-card"></i> Carte se terminant par <?= $card['card_last_four'] ?></p>
                    <p><i class="fa-regular fa-calendar"></i> Expire le <?= $card['expiry_date'] ?></p>
                </div>
                <div class="card-actions">
                    <?php if (!$card['is_default']): ?>
                        <button class="card-action-btn default" onclick="setDefaultCard(<?= $card['id'] ?>)" title="Définir par défaut">
                            <i class="fa-solid fa-star"></i>
                        </button>
                    <?php endif; ?>
                    <button class="card-action-btn delete" onclick="deleteCard(<?= $card['id'] ?>)" title="Supprimer">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <button id="pay-with-selected-card" class="checkout-button" disabled>
            <i class="fa-solid fa-check-circle"></i> Payer avec la carte sélectionnée
        </button>
        
        <button id="show-new-card-form" class="secondary-button">
            <i class="fa-solid fa-plus-circle"></i> Ajouter une nouvelle carte
        </button>
    </div>
    <?php endif; ?>

    <div id="new-card-form" class="new-card-form">
        <h3 style="margin-bottom:20px;">
            <?= empty($payment_cards) ? 'Informations de carte bancaire' : 'Ajouter une nouvelle carte' ?>
        </h3>
        
        <form action="#" method="post">
            <label>Nom de la carte :</label>
            <input type="text" name="card_name" placeholder="ex: Carte principale, Carte travail..." maxlength="100">

            <label>Numéro de carte :</label>
            <input type="text" name="card_number" maxlength="19" required autocomplete="cc-number">

            <label>Date d'expiration :</label>
            <input type="text" name="expiry_date" placeholder="MM/AA" maxlength="5" required autocomplete="cc-exp">

            <label>CVV :</label>
            <input type="text" name="cvv" maxlength="4" required autocomplete="cc-csc">

            <div class="save-info-container">
                <input type="checkbox" id="save_payment_info" name="save_payment_info" value="1" checked>
                <label for="save_payment_info">Enregistrer cette carte pour mes prochains achats</label>
            </div>

            <input type="hidden" name="montant" value="<?= $total ?>">
            <input type="hidden" name="panier_json" value='<?= htmlspecialchars($_POST['panier_json'] ?? '[]') ?>'>
            <button type="submit" class="checkout-button"><i class="fa-solid fa-credit-card"></i> Payer maintenant</button>
        </form>
    </div>
</div>

<?php include '../TEMPLATE/footer.php'; ?>
<script src="../TEMPLATE/Nouveauhead.js"></script>
<script>
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

    // Si aucune carte enregistrée, afficher directement le formulaire
    if (savedCards.length === 0) {
        if (newCardForm) {
            newCardForm.style.display = 'block';
        }
    }

    // Gestion de la sélection des cartes
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
            
            console.log('Carte sélectionnée:', selectedCardId); // Debug
            
            // Activer le bouton de paiement
            if (payWithSelectedCardBtn) {
                payWithSelectedCardBtn.disabled = false;
                payWithSelectedCardBtn.style.opacity = '1';
            }
        });
    });

    // Paiement avec carte sélectionnée
    if (payWithSelectedCardBtn) {
        payWithSelectedCardBtn.addEventListener('click', function() {
            console.log('Tentative de paiement avec carte:', selectedCardId); // Debug
            
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
            formData.append('panier_json', '<?= htmlspecialchars($_POST['panier_json'] ?? '[]') ?>');
            
            // Désactiver le bouton pendant le traitement
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Traitement...';
            
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
                    // Réactiver le bouton en cas d'erreur
                    this.disabled = false;
                    this.innerHTML = '<i class="fa-solid fa-check-circle"></i> Payer avec la carte sélectionnée';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Une erreur est survenue lors du traitement du paiement");
                // Réactiver le bouton en cas d'erreur
                this.disabled = false;
                this.innerHTML = '<i class="fa-solid fa-check-circle"></i> Payer avec la carte sélectionnée';
            });
        });
    }

    // Afficher le formulaire de nouvelle carte
    if (showNewCardFormBtn) {
        showNewCardFormBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Affichage du formulaire de nouvelle carte'); // Debug
            
            if (newCardForm) {
                newCardForm.style.display = 'block';
                this.style.display = 'none';
                
                // Défaire la sélection des cartes existantes
                savedCards.forEach(c => c.classList.remove('selected'));
                selectedCardId = null;
                if (payWithSelectedCardBtn) {
                    payWithSelectedCardBtn.disabled = true;
                    payWithSelectedCardBtn.style.opacity = '0.5';
                }
            }
        });
    }

    // Validation des champs de la nouvelle carte
    if (cardInput) {
        cardInput.placeholder = "XXXX XXXX XXXX XXXX";
        
        let errorMsg = document.createElement('div');
        errorMsg.style = "color: #e74c3c; font-size: 0.95em; margin-bottom: 10px; display:none;";
        cardInput.parentNode.insertBefore(errorMsg, cardInput.nextSibling);

        cardInput.addEventListener('input', function() {
            let numbers = this.value.replace(/\D/g, '').slice(0, 16);
            let formatted = numbers.replace(/(.{4})/g, '$1 ').trim();
            this.value = formatted;

            if (numbers.length === 16 || numbers.length === 0) {
                errorMsg.style.display = "none";
            } else {
                errorMsg.textContent = "Le numéro de carte doit contenir 16 chiffres.";
                errorMsg.style.display = "block";
            }
        });
    }

    if (cvvInput) {
        cvvInput.placeholder = "123";
        
        let cvvErrorMsg = document.createElement('div');
        cvvErrorMsg.style = "color: #e74c3c; font-size: 0.95em; margin-bottom: 10px; display:none;";
        cvvInput.parentNode.insertBefore(cvvErrorMsg, cvvInput.nextSibling);

        cvvInput.addEventListener('input', function() {
            let numbers = this.value.replace(/\D/g, '').slice(0, 3);
            this.value = numbers;

            if (numbers.length === 3 || numbers.length === 0) {
                cvvErrorMsg.style.display = "none";
            } else {
                cvvErrorMsg.textContent = "Le CVV doit contenir 3 chiffres.";
                cvvErrorMsg.style.display = "block";
            }
        });
    }

    if (expiryInput) {
        expiryInput.addEventListener('input', function() {
            let val = this.value.replace(/\D/g, '').slice(0, 4);
            if (val.length > 2) {
                val = val.slice(0,2) + '/' + val.slice(2);
            }
            this.value = val;
        });
    }

    // Soumission du formulaire de nouvelle carte
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

            const formData = new FormData(form);
            formData.append('process_payment', true);
            
            if (saveInfoCheckbox && saveInfoCheckbox.checked) {
                formData.append('save_info', true);
            }
            
            // Désactiver le bouton pendant le traitement
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Traitement...';
            }
            
            fetch(form.action || window.location.href, {
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
                    // Réactiver le bouton en cas d'erreur
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-credit-card"></i> Payer maintenant';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Une erreur est survenue lors du traitement du paiement");
                // Réactiver le bouton en cas d'erreur
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-credit-card"></i> Payer maintenant';
                }
            });
        });
    }

    function showTransactionSuccess() {
        if (!transactionMessage) {
            transactionMessage = document.createElement('div');
            transactionMessage.textContent = "Transaction validée";
            transactionMessage.style = "color: #45cf91; font-weight: bold; font-size: 1.3em; margin: 20px 0; text-align:center;";
            document.querySelector('.paiement-container').appendChild(transactionMessage);
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
</script>
</body>
</html>