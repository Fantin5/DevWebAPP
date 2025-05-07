<?php
$host = "localhost";
$dbname = "activity";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$imagePath = "";
if (isset($_FILES['image']) && $FILES['image']['error'] == 0) {
    $imageName = time() . '' . basename($_FILES['image']['name']);
    $targetDir = "uploads/";
    $targetPath = $targetDir . $imageName;

    // créer le dossier si nécessaire
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
    $imagePath = $targetPath;
}

$sql = "INSERT INTO activites (titre, description, image_url, prix, date_ou_periode)
        VALUES (:titre, :description, :image, :prix, :periode)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':titre' => $_POST['titre'],
    ':description' => $_POST['description'],
    ':image' => $imagePath,
    ':prix' => $_POST['prix'] ?: null,
    ':periode' => $_POST['periode']
]);

exit;
?>