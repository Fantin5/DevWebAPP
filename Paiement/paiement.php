<?php

session_start();

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
        form label { font-weight: 600; }
        form input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 16px;
        }
        .checkout-button {
            width: 100%;
            background-color: #45cf91;
            color: #111;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .checkout-button:hover {
            background-color: #3abd7a;
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

    <h3 style="margin-bottom:20px;">Informations de carte bancaire (paiement simulé)</h3>

    <form action="#" method="post">
        <label>Numéro de carte :</label>
        <input type="text" name="card_number" maxlength="19" required autocomplete="cc-number">

        <label>Date d'expiration :</label>
        <input type="text" name="expiry_date" placeholder="MM/AA" maxlength="5" required autocomplete="cc-exp">

        <label>CVV :</label>
        <input type="text" name="cvv" maxlength="4" required autocomplete="cc-csc">

        <input type="hidden" name="montant" value="<?= $total ?>">
        <button type="submit" class="checkout-button"><i class="fa-solid fa-credit-card"></i> Payer maintenant</button>
    </form>
</div>

<?php include '../TEMPLATE/footer.php'; ?>
<script src="../TEMPLATE/Nouveauhead.js"></script>
<script src="paiement.js"></script>
</body>
</html>
<?php