<?php
// admin_cgu.php
include 'adminVerify.php'; // Vérification de la session admin + connexion à la base de données
// Tables :
// cgu_rules (id, title, content, is_deleted, updated_at)
// cgu_rule_versions (version_id, rule_id, title, content, action, changed_at)


// --- Gestion des actions create/update/delete ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action  = $_POST['action'];
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $id      = intval($_POST['id'] ?? 0);

    $authorInfo = '';
    // Récupération des informations de l'admin pour les lister dans le versioning/historique
    if (isset($_SESSION['user_id'])) {
        $authorId = intval($_SESSION['user_id']);
        $authorInfo = '';
        $stmt = mysqli_prepare($conn, "SELECT CONCAT(CAST(id AS CHAR), ' - ', first_name, ' ', name) FROM user_form WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $authorId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $authorInfo);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }
    if ($action === 'add' && $title && $content) {
        // Insertion règle
        $stmt = mysqli_prepare($conn, "INSERT INTO cgu_rules (title, content, is_deleted, updated_at) VALUES (?, ?, 0, NOW())");
        mysqli_stmt_bind_param($stmt, 'ss', $title, $content);
        mysqli_stmt_execute($stmt);
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        // Versioning
        $v = mysqli_prepare($conn, "INSERT INTO cgu_rule_versions (rule_id, title, content, action, changed_at, author_info) VALUES (?, ?, ?, 'add', NOW(), ?)");
        mysqli_stmt_bind_param($v, 'isss', $newId, $title, $content, $authorInfo);
        mysqli_stmt_execute($v);
        mysqli_stmt_close($v);
    } elseif ($action === 'edit' && $id && $title && $content) {
        // Mise à jour règle
        $stmt = mysqli_prepare($conn, "UPDATE cgu_rules SET title = ?, content = ?, updated_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'ssi', $title, $content, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        // Versioning
        $v = mysqli_prepare($conn, "INSERT INTO cgu_rule_versions (rule_id, title, content, action, changed_at, author_info) VALUES (?, ?, ?, 'edit', NOW(), ?)");
        mysqli_stmt_bind_param($v, 'isss', $id, $title, $content, $authorInfo);
        mysqli_stmt_execute($v);
        mysqli_stmt_close($v);
    } elseif ($action === 'delete' && $id) {
        // Récupérer l'état avant suppression
        $s = mysqli_prepare($conn, "SELECT title, content FROM cgu_rules WHERE id = ?");
        mysqli_stmt_bind_param($s, 'i', $id);
        mysqli_stmt_execute($s);
        mysqli_stmt_bind_result($s, $oldTitle, $oldContent);
        mysqli_stmt_fetch($s);
        mysqli_stmt_close($s);
        // Marquer comme supprimé
        $stmt = mysqli_prepare($conn, "UPDATE cgu_rules SET is_deleted = 1, updated_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        // Versioning
        $v = mysqli_prepare($conn, "INSERT INTO cgu_rule_versions (rule_id, title, content, action, changed_at, author_info) VALUES (?, ?, ?, 'delete', NOW(), ?)");
        mysqli_stmt_bind_param($v, 'isss', $id, $oldTitle, $oldContent, $authorInfo);
        mysqli_stmt_execute($v);
        mysqli_stmt_close($v);
    }
    header('Location: admin_cgu.php?view=' . htmlspecialchars($_GET['view'] ?? 'actives'));
    exit;
}

// Vue courante (actives ou supprimees)
$view = $_GET['view'] ?? 'actives';

// Récupération des règles selon la vue
if ($view === 'supprimees') {
    $res = mysqli_query($conn, "SELECT DISTINCT rule_id FROM cgu_rule_versions WHERE action = 'delete'");
    $deletedIds = [];
    while ($row = mysqli_fetch_assoc($res)) $deletedIds[] = $row['rule_id'];
    mysqli_free_result($res);
} else {
    $res = mysqli_query($conn, "SELECT * FROM cgu_rules WHERE is_deleted = 0 ORDER BY updated_at DESC");
    $rules = [];
    while ($row = mysqli_fetch_assoc($res)) $rules[] = $row;
    mysqli_free_result($res);
}

// Gestion AJAX historique
if (isset($_GET['ajax_history'])) {
    $ruleId = intval($_GET['ajax_history']);
    $hstmt = mysqli_prepare($conn, "SELECT version_id, action, title, content, changed_at, author_info FROM cgu_rule_versions WHERE rule_id = ? ORDER BY changed_at DESC");
    mysqli_stmt_bind_param($hstmt, 'i', $ruleId);
    mysqli_stmt_execute($hstmt);
    mysqli_stmt_bind_result($hstmt, $verId, $act, $hTitle, $hContent, $chgAt, $authorInfo);
    $history = [];
    while (mysqli_stmt_fetch($hstmt)) {
        $history[] = ['version_id'=>$verId, 'action'=>$act, 'title'=>$hTitle, 'content'=>$hContent, 'changed_at'=>$chgAt, 'author_info'=>$authorInfo];
    }
    mysqli_stmt_close($hstmt);
    header('Content-Type: application/json');
    echo json_encode($history);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin CGU</title>
    <!-- Charger le style commun -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            margin-top: 20px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #828977;
            padding-bottom: 10px;
        }
        .admin-nav { 
            background: none; 
            border-bottom: 1px solid #dcdcdc; 
        }
        .nav-buttons {
            margin-bottom: 20px;
        }
        .nav-buttons a {
            display: inline-block;
            padding: 8px 15px;
            margin-right: 10px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .nav-buttons a:hover {
            background-color: #45a049;
        }
        .filter-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .filter-button {
            padding: 8px 15px;
            background-color: #f0f0f0;
            color: #333;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .filter-button.active {
            background-color: #828977;
            color: white;
        }
        .admin-main { padding: 20px; }
        .section-form, .section-list, .section-history { margin-bottom: 30px; }
        .form-inline { 
            display: flex; 
            flex-direction: column;
            gap: 10px; 
            flex-wrap: wrap; 
        }
        .form-input, .form-textarea { 
            flex: 1; 
            padding: 8px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
        }
        .form-input {
            box-sizing: border-box; 
        }
        .form-textarea { resize: vertical; min-height: 60px; }
        .btn { 
            display: inline-block; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none; 
        }
        .btn-primary { 
            background: #3498db; 
            color: #fff; 
        }
        .btn-secondary { 
            background: #95a5a6; 
            color: #fff; 
        }
        .btn-danger { 
            background: #e74c3c; 
            color: #fff; 
        }
        .btn-link { 
            background: transparent; 
            color: #3498db; 
            padding: 0; 
        }
        btn-toggle-history { 
            background: #f0f0f0; 
            color: #333; 
            padding: 5px 10px; 
            border-radius: 4px; 
            cursor: pointer;
            width: 100%;
        }
        .item-list { 
            list-style: none; 
            margin: 0; 
            padding: 0; 
        }
        .item { 
            background: #fff; 
            border: 1px solid #e0e0e0; 
            border-radius: 4px; 
            margin-bottom: 10px; 
            padding: 15px;
        }
        .item:hover { 
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .item-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 10px; 
        }
        .item-actions a { 
            margin-left: 8px; 
        }
        .table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        .table th, .table td { 
            border: 1px solid #ccc; 
            padding: 8px; 
            text-align: left; 
        }
        .table th { 
            background: #ecf0f1; 
        }

        .inline-edit-form { display: inline; }
        
        .edit-btn {
            margin-right: 10px;
            cursor: pointer;
        }
        .confirm-btn {
            display: none;
            background-color: #2ecc71;
            color: white;
        }
        .form-readonly input,
        .form-readonly textarea {
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            color: #555;
        }

        /* Affichage par défaut de l'historique */
        .history-container {
            display: none;
        }
        .history-container.open {
            display: block;
        }


        .editor-toolbar {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .editor-toolbar button {
            padding: 4px 8px;
        }
        .editor-area {
            border: 1px solid #ccc;
            min-height: 150px;
            padding: 10px;
            background-color: #fff;
        }
    </style>
    <!-- Font Awersome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script>
        function enableEdit(formId) {
            const form = document.getElementById(formId);
            // Enregistre les valeurs originales si ce n'est pas déjà fait
            if (!form.dataset.originalValues) {
                const inputs = form.querySelectorAll('input[name], textarea[name]');
                let originalValues = {};
                inputs.forEach(input => {
                    originalValues[input.name] = input.value;
                });
                form.dataset.originalValues = JSON.stringify(originalValues);
            }

            const inputs = form.querySelectorAll('input, textarea');
            inputs.forEach(input => input.removeAttribute('readonly'));
            form.classList.remove('form-readonly');

            form.querySelector('.confirm-btn').style.display = 'inline-block';
            form.querySelector('.cancel-btn').style.display = 'inline-block';
            form.querySelector('.edit-btn').style.display = 'none';
        }
        document.querySelectorAll('.cancel-btn').forEach(btn => {
            btn.addEventListener('click', function(){
                const form = this.closest('form');
                cancelEdit(form.id);
            });
        });
        function cancelEdit(formId) {
            const form = document.getElementById(formId);
            if (!form.dataset.originalValues) return; // rien à annuler

            const originalValues = JSON.parse(form.dataset.originalValues);
            const inputs = form.querySelectorAll('input[name], textarea[name]');
            inputs.forEach(input => {
                if (originalValues.hasOwnProperty(input.name)) {
                    input.value = originalValues[input.name];
                }
            });

            inputs.forEach(input => input.setAttribute('readonly', ''));
            form.classList.add('form-readonly');

            form.querySelector('.confirm-btn').style.display = 'none';
            form.querySelector('.cancel-btn').style.display = 'none';
            form.querySelector('.edit-btn').style.display = 'inline-block';

            // Supprime les valeurs stockées en mode édition lorsque l'on repasse en mode readonly
            delete form.dataset.originalValues;
        }

        function validateEditForm(form) {
            const ruleId = form.id.split('-')[1];
            const originalTitle = form.getAttribute('data-original-title');
            const originalContent = form.getAttribute('data-original-content');

            const currentTitle = form.querySelector('input[name="title"]').value.trim();

            // Synchroniser le contenu HTML actuel de l'éditeur visuel dans la textarea cachée
            const editableDiv = document.getElementById("editContent_" + ruleId);
            const textarea = form.querySelector('textarea[name="content"]');
            textarea.value = editableDiv.innerHTML.trim();  // injecte le HTML dans la textarea

            const currentContent = textarea.value;

            if (currentTitle === originalTitle && currentContent === originalContent) {
                alert("Aucune modification détectée. Veuillez modifier le titre ou le contenu avant de confirmer.");
                return false; // bloque l'envoi du formulaire
            }
            return true; // autorise la soumission
        }


        function execCmd(command, value = null) {
            document.execCommand(command, false, value);
        }
        // Avant soumission du formulaire, injecter le HTML dans la textarea cachée
        function syncContent() {
            document.getElementById('content').value = document.getElementById('editableContent').innerHTML;
        }
        // Exécuter avant le submit du formulaire
        document.querySelector("form").addEventListener("submit", syncContent);

        function execCmd(command, value = null, editorId) {
            const editor = document.getElementById(editorId);
            editor.focus();
            document.execCommand(command, false, value);
        }
        function syncEditor(editorId, textareaId) {
            const editor = document.getElementById(editorId);
            const textarea = document.getElementById(textareaId);
            if (editor && textarea) {
            textarea.value = editor.innerHTML;
            }
        }

        // Active l’édition : affiche la barre d’outils et le div contenteditable
        function enableEdit(formId) {
            const form = document.getElementById(formId);
            const ruleId = formId.split('-')[1];

            form.querySelector('input[name="title"]').readOnly = false;
            // Cache textarea en lecture seule
            form.querySelector('textarea.form-textarea').style.display = "none";
            // Affiche éditeur visuel
            document.getElementById("editContent_" + ruleId).style.display = "block";
            document.getElementById("toolbar-" + ruleId).style.display = "block";
            // Affiche textarea cachée pour soumission
            document.getElementById("editTextarea_" + ruleId).style.display = "none";

            // Affiche boutons Annuler et Confirmer les modifications
            form.querySelector(".cancel-btn").style.display = "inline-block";
            form.querySelector(".confirm-btn").style.display = "inline-block";
            // Cache bouton Modifier
            form.querySelector(".edit-btn").style.display = "none";
        }

        function cancelEdit(formId) {
            const form = document.getElementById(formId);
            const ruleId = formId.split('-')[1];
            // Remettre les valeurs d'origine
            const originalTitle = form.getAttribute("data-original-title");
            const originalContent = form.getAttribute("data-original-content");
            form.querySelector('input[name="title"]').value = originalTitle;
            form.querySelector('input[name="title"]').readOnly = true;
            document.getElementById("editContent_" + ruleId).innerHTML = originalContent;
            document.querySelector("#editTextarea_" + ruleId).value = originalContent;
            // Cache éditeur visuel
            document.getElementById("editContent_" + ruleId).style.display = "none";
            document.getElementById("toolbar-" + ruleId).style.display = "none";
            // Réaffiche textarea readonly
            form.querySelector('textarea.form-textarea').style.display = "block";

            // Cache boutons Annuler et Confirmer les modifications
            form.querySelector(".cancel-btn").style.display = "none";
            form.querySelector(".confirm-btn").style.display = "none";
            // Affiche bouton Modifier
            form.querySelector(".edit-btn").style.display = "inline-block";
        }
    </script>
</head>
<body>
    

    <div class="container">
        <h1>Gestion des CGU</h1>
        <div class="nav-buttons">
            <a href="admin.php">Tableau de bord</a>
            <a href="../Testing grounds/main.php">Site utilisateur</a>
            <a href="../CGU/cgu.php">Voir les CGU</a>
        </div>
        <div class="filter-buttons">
            <a href="?view=actives" class="filter-button <?= $view==='actives' ? 'active':'' ?>">Actives</a>
            <a href="?view=supprimees" class="filter-button <?= $view==='supprimees' ? 'active':'' ?>">Supprimées</a>
        </div>
        <main class="admin-main">
            <?php if ($view==='actives'): ?>
                <section class="section-form">
                    <div>
                    <h2>Ajouter une nouvelle règle</h2>
                    <form class="form-inline" method="POST" onsubmit="syncEditor('addContent', 'addTextarea')">
                        <input type="hidden" name="action" value="add">
                        <div>
                            <input class="form-input" type="text" name="title" placeholder="Titre" required>
                            <button class="btn btn-primary" type="submit">Ajouter</button>
                            <!-- Barre d'outils traitement de texte -->
                            <div class="editor-toolbar" data-editor-for="addContent">
                                <button type="button" onclick="execCmd('bold', null, 'addContent')"><strong>Gras</strong></button>
                                <button type="button" onclick="execCmd('insertUnorderedList', null, 'addContent')">• Liste</button>
                                <button type="button" onclick="execCmd('insertOrderedList', null, 'addContent')">1. Liste</button>
                                <button type="button" onclick="execCmd('insertHTML', '<br>', 'addContent')">↵ Saut de ligne</button>
                            </div>
                        </div>
                        <!-- Editeur visuel -->
                        <div id="addContent" class="editor-area" contenteditable="true" style="border:1px solid #ccc; padding:8px; min-height:120px;"></div>
                        <!-- Textarea cachée (contenu HTML utilisé dans la BDD pour affichage sur le site) -->
                        <textarea name="content" id="addTextarea" style="display:none;"></textarea>
                    </form>
                    </div>
                </section>
                <section class="section-list">
                    <h2>Règles actives</h2>
                    <ul class="item-list">
                        <?php foreach ($rules as $rule): ?>
                            <li class="item">
                            <div class="item" data-rule-id="<?= $rule['id'] ?>">
                            
                                <form id="del-<?= $rule['id'] ?>" method="POST" style="display:none;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $rule['id'] ?>">
                                </form>
                                <!--
                                Formulaire d'édition des règles CGU
                                (On n'enregistre les modifs/poste le formulaire que si le contenu de la règle a subi une modifications)
                                -->
                                <form id="edit-<?= $rule['id'] ?>" class="form-inline form-readonly" method="POST"
                                onsubmit="syncEditor('editContent_<?= $rule['id'] ?>', 'editTextarea_<?= $rule['id'] ?>');
                                return validateEditForm(this);"   
                                data-original-title="<?= htmlspecialchars($rule['title'], ENT_QUOTES) ?>"
                                data-original-content="<?= htmlspecialchars($rule['content'], ENT_QUOTES) ?>"
                                >
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?= $rule['id'] ?>">
                                    <div>
                                        <!-- Label titre -->
                                        <strong><?= htmlspecialchars($rule['title']) ?></strong>
                                        <div class="item-header">
                                            <div>
                                                <!-- Input titre -->
                                                <input class="form-input" type="text" name="title" value="<?= htmlspecialchars($rule['title']) ?>" readonly required>
                                                <!-- Barre d'outils traitement de texte (visible uniquement lorsque "Modifier" est cliqué) -->
                                                <div class="editor-toolbar" id="toolbar-<?= $rule['id'] ?>" style="display:none;" data-editor-for="editContent_<?= $rule['id'] ?>">
                                                    <button type="button" onclick="execCmd('bold', null, 'editContent_<?= $rule['id'] ?>')"><strong>Gras</strong></button>
                                                    <button type="button" onclick="execCmd('insertUnorderedList', null, 'editContent_<?= $rule['id'] ?>')">• Liste</button>
                                                    <button type="button" onclick="execCmd('insertOrderedList', null, 'editContent_<?= $rule['id'] ?>')">1. Liste</button>
                                                    <button type="button" onclick="execCmd('insertHTML', '<br>', 'editContent_<?= $rule['id'] ?>')">↵ Saut de ligne</button>
                                                </div>
                                            </div>
                                            <div>
                                                <!-- Boutons modifier et supprimer -->
                                                <div class="item-actions">
                                                    <button type="button" class="btn btn-secondary cancel-btn" style="display:none;" onclick="cancelEdit('edit-<?= $rule['id'] ?>')">
                                                        <i class="fas fa-times"></i> Annuler
                                                    </button>
                                                    <button class="btn confirm-btn" type="submit">
                                                        <i class="fas fa-check"></i> Confirmer les modifications
                                                    </button>
                                                    <button type="button" class="btn btn-primary edit-btn" onclick="enableEdit('edit-<?= $rule['id'] ?>')">Modifier</button>
                                                    <a class="btn btn-danger" href="#" onclick="if(confirm('Êtes-vous sûr.e de vouloir supprimer ?')) document.getElementById('del-<?= $rule['id'] ?>').submit();">
                                                        <i class="fas fa-trash"></i> Supprimer
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Input contenu -->
                                    <!-- Editeur visuel -->
                                    <div id="editContent_<?= $rule['id'] ?>" class="editor-area" contenteditable="true" style="display:none; border:1px solid #ccc; padding:8px; min-height:100px;">
                                    <?= $rule['content'] ?>
                                    </div>
                                    <!-- Textarea visible en lecture seule -->
                                    <textarea class="form-textarea" name="content" readonly required style="display:block;"><?= htmlspecialchars($rule['content']) ?></textarea>
                                    <!-- Textarea cachée pour édition -->
                                    <textarea name="content" id="editTextarea_<?= $rule['id'] ?>" style="display:none;"></textarea>
                                    <div style="margin-top:3px">
                                        <button type="button" class="btn btn-secondary btn-toggle-history">Voir l'historique</button>
                                        <div class="history-container"></div>
                                    </div>
                                </form>
                                
                            </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                </section>
            <?php else: ?>
                <section class="section-list">
                    <h2>Règles supprimées</h2>
                    <?php foreach ($deletedIds as $rid): ?>
                    <div class="item" data-rule-id="<?= $rid ?>">
                        <span>Règle #<?= $rid ?></span>
                        <button class="btn btn-link btn-toggle-history">Voir l'historique</button>
                        <div class="history-container"></div>
                    </div>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </main>
    </div>
    <script>
        document.querySelectorAll('.btn-toggle-history').forEach(btn => {
            btn.addEventListener('click', function(){
                const item = this.closest('.item');
                const container = item.querySelector('.history-container');
                const ruleId = item.getAttribute('data-rule-id');

                if (container.classList.contains('open')) {
                    // Si le conteneur est déjà ouvert, on le ferme
                    container.classList.remove('open');
                    this.textContent = "Voir l'historique";
                } else {
                    if (!container.innerHTML.trim()) {
                        fetch(`admin_cgu.php?ajax_history=${ruleId}`)
                            .then(res => res.json())
                            .then(data => {
                                let html = '<table class="table"><thead><tr><th>Version ID</th><th>Action</th><th>Titre</th><th>Contenu</th><th>Date</th><th>Auteur</th></tr></thead><tbody>';
                                data.forEach(h => {
                                    html += `<tr><td>${h.version_id}</td><td>${h.action}</td><td>${h.title}</td><td>${h.content}</td><td>${h.changed_at}</td><td>${h.author_info}</td></tr>`;
                                });
                                html += '</tbody></table>';
                                container.innerHTML = html;
                                container.classList.add('open');
                                btn.textContent = "Fermer l'historique";
                            });
                    } else {
                        container.classList.add('open');
                        this.textContent = "Fermer l'historique";
                    }
                }
            });
        });
        // Désactiver le bouton de soumission lors de l'envoi du formulaire (pour éviter les doubles envois)
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', e => {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = "Envoi en cours...";
                }
            });
        });
    </script>

</body>
</html>