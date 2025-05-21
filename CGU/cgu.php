<?php
require_once('include/header.php');

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['user_id']);
?>

<div class="container mt-5">
    <h1 class="mb-4">Conditions Générales d'Utilisation</h1>

    <div class="faq-item">
        <h5>1. Acceptation des conditions</h5>
        <p>En accédant et en utilisant ce site, vous acceptez sans réserve les présentes Conditions Générales d'Utilisation.</p>
    </div>

    <div class="faq-item">
        <h5>2. Modification des conditions</h5>
        <p>Nous nous réservons le droit de modifier ces CGU à tout moment. Les modifications prennent effet dès leur mise en ligne.</p>
    </div>

    <div class="faq-item">
        <h5>3. Utilisation du site</h5>
        <p>Vous vous engagez à utiliser ce site de manière licite, dans le respect des lois en vigueur et des présentes CGU.</p>
    </div>

    <div class="faq-item">
        <h5>4. Propriété intellectuelle</h5>
        <p>Le contenu du site (textes, images, logo, etc.) est protégé par les lois sur la propriété intellectuelle. Toute reproduction est interdite sans autorisation.</p>
    </div>

    <div class="faq-item">
        <h5>5. Données personnelles</h5>
        <p>Les données collectées sont traitées conformément à notre politique de confidentialité.</p>
    </div>

    <div class="faq-item">
        <h5>6. Responsabilité</h5>
        <p>Nous ne saurions être tenus responsables en cas d’erreurs, d’interruptions ou d’indisponibilité du site.</p>
    </div>

    <div class="faq-item">
        <h5>7. Loi applicable</h5>
        <p>Les présentes CGU sont régies par la loi française. Tout litige sera soumis à la juridiction compétente.</p>
    </div>
</div>

<!-- Formulaire de contact (inchangé) -->
<div class="container mt-5">
    <h2>Une question concernant les CGU ?</h2>
    <form method="post" action="">
        <div class="form-group">
            <label for="name">Nom :</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="email">Adresse e-mail :</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="message">Message :</label>
            <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary mt-2">Envoyer</button>
    </form>
</div>

<?php
require_once('include/footer.php');
?>
