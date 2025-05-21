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
    }
    
    echo json_encode($response);
    exit;
}

// Synchronisation du panier JS → PHP
if (isset($_POST['panier_json'])) {
    $panier = json_decode($_POST['panier_json'], true);
    $_SESSION['panier'] = array_map(function($item) {
        return $item['id'];
    }, $panier);
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

// Vérifier si l'utilisateur a déjà des informations de paiement enregistrées
$user_id = $_SESSION['user_id'];
$payment_info = null;

// Vérifier si la table payment_info existe
$result = $conn->query("SHOW TABLES LIKE 'payment_info'");
if ($result->num_rows > 0) {
    // Créer une connexion à la base de données des utilisateurs
    $user_conn = new mysqli('localhost', 'root', '', 'user_db');
    if (!$user_conn->connect_error) {
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
        $user_conn->close();
    }
}

$conn->close();
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
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #45cf91;
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
        .save-info-container {
            margin-top: 20px;
            display: flex;
            align-items: center;
        }
        .save-info-container input[type="checkbox"] {
            margin-right: 10px;
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
    <div class="saved-card-info">
        <p><i class="fa-solid fa-credit-card"></i> Carte enregistrée se terminant par <?= $payment_info['card_last_four'] ?></p>
        <p><i class="fa-regular fa-calendar"></i> Expire le <?= $payment_info['expiry_date'] ?></p>
    </div>
    <?php endif; ?>

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
</div>

<?php include '../TEMPLATE/footer.php'; ?>
<script src="../TEMPLATE/Nouveauhead.js"></script>
<script src="paiement.js"></script>
</body>
</html>