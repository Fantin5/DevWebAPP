<?php
session_start();
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synapse - Conditions Générales d'Utilisation</title>
    <link rel="stylesheet" href="faq.css"> <!-- ou renomme à cgu.css si tu veux séparer -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>

    <div class="page-container">
        <div class="faq-page-title">
            <h1>Conditions Générales d'Utilisation</h1>
        </div>

        <section class="faq">
            <div class="faq-item">
                <h2 class="question">1. Objet</h2>
                <p class="answer">Les présentes Conditions Générales d'Utilisation (CGU) ont pour objet de définir les modalités de mise à disposition des services de la plateforme Synapse et les conditions d’utilisation de ces services par l’utilisateur.</p>
            </div>

            <div class="faq-item">
                <h2 class="question">2. Acceptation des conditions</h2>
                <p class="answer">L'utilisation de la plateforme implique l'acceptation pleine et entière des présentes CGU. En vous inscrivant sur le site, vous reconnaissez les avoir lues, comprises et acceptées sans réserve.</p>
            </div>

            <div class="faq-item">
                <h2 class="question">3. Accès au service</h2>
                <p class="answer">Le site est accessible gratuitement à tout utilisateur disposant d’un accès à Internet. Certains services peuvent toutefois être réservés aux utilisateurs identifiés.</p>
            </div>

            <div class="faq-item">
                <h2 class="question">4. Obligations de l'utilisateur</h2>
                <p class="answer">L’utilisateur s’engage à utiliser la plateforme de manière conforme à la loi et aux présentes CGU. Toute utilisation abusive ou frauduleuse pourra entraîner la suspension ou suppression du compte.</p>
            </div>

            <div class="faq-item">
                <h2 class="question">5. Propriété intellectuelle</h2>
                <p class="answer">Tous les contenus présents sur le site (textes, images, logos, etc.) sont la propriété exclusive de Synapse ou de ses partenaires. Toute reproduction ou utilisation sans autorisation est interdite.</p>
            </div>

            <div class="faq-item">
                <h2 class="question">6. Données personnelles</h2>
                <p class="answer">Synapse s'engage à respecter la vie privée de ses utilisateurs. Les données collectées sont traitées conformément à notre politique de confidentialité.</p>
            </div>

            <div class="faq-item">
                <h2 class="question">7. Modifications des CGU</h2>
                <p class="answer">Synapse se réserve le droit de modifier les présentes CGU à tout moment. Les utilisateurs seront informés par email ou lors de leur prochaine connexion.</p>
            </div>

            <div class="faq-item">
                <h2 class="question">8. Loi applicable</h2>
                <p class="answer">Les présentes CGU sont soumises au droit français. En cas de litige, les tribunaux compétents seront ceux du ressort du siège de Synapse.</p>
            </div>
        </section>
    </div>
</body>
</html>
