<?php
include '../Connexion-Inscription/config.php';
include 'auth_functions.php';

// Require login to access this page
require_login();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Initialize variables
$conversations = [];
$users = [];
$selected_conversation = null;
$conversation_partner = null;
$messages = [];
$search_term = '';
$new_conversation = false;
$error_message = '';

// Check for error messages
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'user_not_found':
            $error_message = 'Utilisateur introuvable.';
            break;
        case 'conversation_creation_failed':
            $error_message = 'Erreur lors de la création de la conversation.';
            break;
        case 'no_user_id':
            $error_message = 'Aucun utilisateur sélectionné.';
            break;
        case 'cannot_message_self':
            $error_message = 'Vous ne pouvez pas envoyer un message à vous-même.';
            break;
        default:
            $error_message = 'Une erreur s\'est produite.';
    }
}

// Check if starting a new conversation
if (isset($_GET['new']) && $_GET['new'] == 1) {
    $new_conversation = true;
    
    // Search for users if a search term is provided
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search_term = mysqli_real_escape_string($conn, $_GET['search']);
        $user_query = "SELECT id, name, first_name, email FROM user_form 
                      WHERE (name LIKE '%$search_term%' OR first_name LIKE '%$search_term%' OR email LIKE '%$search_term%')
                      AND id != $user_id 
                      ORDER BY name, first_name 
                      LIMIT 10";
        $user_result = mysqli_query($conn, $user_query);
        
        if ($user_result) {
            while ($user = mysqli_fetch_assoc($user_result)) {
                $users[] = $user;
            }
        } else {
            $error_message = "Erreur de recherche: " . mysqli_error($conn);
        }
    }
}
// If not a new conversation, load existing conversations
else {
    // Get selected conversation if any
    if (isset($_GET['conversation_id'])) {
        $conversation_id = mysqli_real_escape_string($conn, $_GET['conversation_id']);
        
        // Get conversation details
        $conv_query = "SELECT * FROM conversations 
                      WHERE id = $conversation_id 
                      AND (user1_id = $user_id OR user2_id = $user_id)";
        $conv_result = mysqli_query($conn, $conv_query);
        
        if (mysqli_num_rows($conv_result) > 0) {
            $selected_conversation = mysqli_fetch_assoc($conv_result);
            
            // Get conversation partner info
            $partner_id = ($selected_conversation['user1_id'] == $user_id) ? 
                          $selected_conversation['user2_id'] : $selected_conversation['user1_id'];
            
            $partner_query = "SELECT id, name, first_name FROM user_form WHERE id = $partner_id";
            $partner_result = mysqli_query($conn, $partner_query);
            
            if (mysqli_num_rows($partner_result) > 0) {
                $conversation_partner = mysqli_fetch_assoc($partner_result);
            }
            
            // Get messages for this conversation
            $messages_query = "SELECT m.*, 
                             CASE WHEN m.sender_id = $user_id THEN 1 ELSE 0 END as is_mine
                             FROM messages m
                             WHERE (m.sender_id = $user_id AND m.receiver_id = $partner_id)
                             OR (m.sender_id = $partner_id AND m.receiver_id = $user_id)
                             ORDER BY m.timestamp ASC";
            $messages_result = mysqli_query($conn, $messages_query);
            
            while ($message = mysqli_fetch_assoc($messages_result)) {
                $messages[] = $message;
            }
            
            // Mark unread messages as read
            $update_read = "UPDATE messages SET is_read = 1 
                          WHERE receiver_id = $user_id 
                          AND sender_id = $partner_id
                          AND is_read = 0";
            mysqli_query($conn, $update_read);
        }
    }
    
    // Get all conversations for sidebar
    $conversations_query = "SELECT c.*, 
                         CASE 
                             WHEN c.user1_id = $user_id THEN c.user2_id 
                             ELSE c.user1_id 
                         END as partner_id,
                         (SELECT COUNT(*) FROM messages 
                          WHERE receiver_id = $user_id 
                          AND sender_id = partner_id
                          AND is_read = 0) as unread_count
                         FROM conversations c
                         WHERE c.user1_id = $user_id OR c.user2_id = $user_id
                         ORDER BY c.updated_at DESC";
    $conversations_result = mysqli_query($conn, $conversations_query);
    
    while ($conversation = mysqli_fetch_assoc($conversations_result)) {
        // Get partner name
        $partner_id = $conversation['partner_id'];
        $partner_query = "SELECT name, first_name FROM user_form WHERE id = $partner_id";
        $partner_result = mysqli_query($conn, $partner_query);
        
        if (mysqli_num_rows($partner_result) > 0) {
            $partner = mysqli_fetch_assoc($partner_result);
            $conversation['partner_name'] = $partner['first_name'] . ' ' . $partner['name'];
        } else {
            $conversation['partner_name'] = 'Utilisateur inconnu';
        }
        
        // Get last message
        if ($conversation['last_message_id']) {
            $last_message_query = "SELECT message_content, timestamp, sender_id FROM messages WHERE id = " . $conversation['last_message_id'];
            $last_message_result = mysqli_query($conn, $last_message_query);
            
            if (mysqli_num_rows($last_message_result) > 0) {
                $last_message = mysqli_fetch_assoc($last_message_result);
                $conversation['last_message'] = $last_message['message_content'];
                $conversation['last_message_time'] = $last_message['timestamp'];
                $conversation['is_last_from_me'] = ($last_message['sender_id'] == $user_id);
            }
        }
        
        $conversations[] = $conversation;
    }
}

// Function to format timestamp for display
function formatMessageTime($timestamp) {
    $date = new DateTime($timestamp);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days == 0) {
        // Today, show only time
        return $date->format('H:i');
    } elseif ($diff->days == 1) {
        // Yesterday
        return 'Hier à ' . $date->format('H:i');
    } elseif ($diff->days < 7) {
        // Less than a week, show day name
        return $date->format('l') . ' à ' . $date->format('H:i');
    } else {
        // More than a week, show full date
        return $date->format('d/m/Y H:i');
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie | Synapse</title>
    <link rel="stylesheet" href="../TEMPLATE/Nouveauhead.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #45a163;
            --secondary-color: #828977;
            --accent-color: #ff9f67;
            --danger-color: #e74c3c;
            --background-color: #f8f9fa;
            --card-background: #ffffff;
            --text-primary: #333333;
            --text-secondary: #666666;
            --border-radius: 15px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition-speed: 0.3s;
        }
        
        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .messaging-container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            display: flex;
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            height: calc(100vh - 200px);
            min-height: 500px;
        }
        
        /* Sidebar styles */
        .conversations-sidebar {
            width: 300px;
            border-right: 1px solid #eaeaea;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #eaeaea;
            position: sticky;
            top: 0;
            background-color: var(--card-background);
            z-index: 10;
        }
        
        .sidebar-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 10px 0;
            color: var(--secondary-color);
        }
        
        .search-box {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border: 1px solid #eaeaea;
            border-radius: 20px;
        }
        
        .search-box input {
            border: none;
            outline: none;
            width: 100%;
            margin-left: 10px;
            font-size: 14px;
        }
        
        .conversation-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f5f5f5;
            cursor: pointer;
            transition: background-color 0.2s;
            position: relative;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .conversation-item:hover {
            background-color: #f9f9f9;
        }
        
        .conversation-item.active {
            background-color: rgba(69, 161, 99, 0.1);
            border-left: 3px solid var(--primary-color);
        }
        
        .conversation-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-primary);
        }
        
        .conversation-last-message {
            font-size: 13px;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 230px;
        }
        
        .conversation-time {
            font-size: 12px;
            color: var(--text-secondary);
            position: absolute;
            right: 15px;
            top: 15px;
        }
        
        .unread-indicator {
            position: absolute;
            right: 15px;
            top: 40px;
            background-color: var(--primary-color);
            color: white;
            font-size: 11px;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
        }
        
        /* Message area styles */
        .message-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .message-header {
            padding: 20px;
            border-bottom: 1px solid #eaeaea;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            background-color: var(--card-background);
            z-index: 10;
        }
        
        .back-to-conversations {
            display: none;
            margin-right: 15px;
            cursor: pointer;
            color: var(--secondary-color);
        }
        
        .partner-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .message-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 12px 15px;
            border-radius: 18px;
            margin-bottom: 15px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message-bubble.mine {
            background-color: rgba(69, 161, 99, 0.1);
            border-bottom-right-radius: 5px;
            align-self: flex-end;
        }
        
        .message-bubble.other {
            background-color: #f1f1f1;
            border-bottom-left-radius: 5px;
            align-self: flex-start;
        }
        
        .message-time {
            font-size: 11px;
            color: var(--text-secondary);
            text-align: right;
            margin-top: 5px;
        }
        
        .message-input-container {
            padding: 15px 20px;
            border-top: 1px solid #eaeaea;
            display: flex;
            align-items: center;
        }
        
        .message-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #eaeaea;
            border-radius: 20px;
            outline: none;
            font-size: 14px;
            resize: none;
            max-height: 100px;
            min-height: 44px;
        }
        
        .send-button {
            margin-left: 10px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }
        
        .send-button:hover {
            background-color: #3abd7a;
        }
        
        .no-conversation-selected {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            padding: 20px;
            text-align: center;
        }
        
        .no-conversation-icon {
            font-size: 50px;
            color: var(--secondary-color);
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .user-search-container {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .user-search-header {
            margin-bottom: 20px;
        }
        
        .user-search-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 15px;
        }
        
        .user-search-form {
            display: flex;
            margin-bottom: 20px;
        }
        
        .user-search-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #eaeaea;
            border-radius: 20px 0 0 20px;
            outline: none;
            font-size: 14px;
        }
        
        .user-search-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0 20px 20px 0;
            padding: 0 20px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .user-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .user-item {
            padding: 15px;
            border: 1px solid #eaeaea;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .user-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
        }
        
        .user-email {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .start-conversation-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .start-conversation-button:hover {
            background-color: #3abd7a;
        }
        
        .empty-state {
            text-align: center;
            padding: 20px;
            color: var(--text-secondary);
        }
        
        .empty-state-icon {
            font-size: 40px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .empty-state-message {
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .empty-state-submessage {
            font-size: 14px;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .messaging-container {
                flex-direction: column;
                height: auto;
            }
            
            .conversations-sidebar {
                width: 100%;
                height: 300px;
                border-right: none;
                border-bottom: 1px solid #eaeaea;
            }
            
            .back-to-conversations {
                display: block;
            }
            
            .message-area {
                display: none;
            }
            
            .message-area.active {
                display: flex;
            }
            
            .conversations-sidebar.hidden {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>
    
    <div class="messaging-container">
        <!-- Conversations Sidebar -->
        <div class="conversations-sidebar" id="conversations-sidebar">
            <div class="sidebar-header">
                <h2 class="sidebar-title">Conversations</h2>
                <div class="search-box">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" placeholder="Rechercher...">
                </div>
            </div>
            
            <div class="conversation-list">
                <?php if ($new_conversation): ?>
                    <a href="messagerie.php" class="conversation-item">
                        <div class="conversation-name">
                            <i class="fa-solid fa-arrow-left"></i> Retour aux conversations
                        </div>
                    </a>
                <?php elseif (empty($conversations)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fa-solid fa-comments"></i>
                        </div>
                        <div class="empty-state-message">Aucune conversation</div>
                        <div class="empty-state-submessage">Commencez à discuter en créant une nouvelle conversation</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $conversation): ?>
                        <a href="messagerie.php?conversation_id=<?php echo $conversation['id']; ?>" 
                           class="conversation-item <?php echo (isset($_GET['conversation_id']) && $_GET['conversation_id'] == $conversation['id']) ? 'active' : ''; ?>">
                            <div class="conversation-name"><?php echo htmlspecialchars($conversation['partner_name']); ?></div>
                            <?php if (isset($conversation['last_message'])): ?>
                                <div class="conversation-last-message">
                                    <?php if ($conversation['is_last_from_me']): ?>
                                        <span style="color: var(--text-secondary);">Vous: </span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars(substr($conversation['last_message'], 0, 50)) . (strlen($conversation['last_message']) > 50 ? '...' : ''); ?>
                                </div>
                                <div class="conversation-time">
                                    <?php echo formatMessageTime($conversation['last_message_time']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($conversation['unread_count'] > 0): ?>
                                <div class="unread-indicator"><?php echo $conversation['unread_count']; ?></div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div style="padding: 15px;">
                <a href="messagerie.php?new=1" class="start-conversation-button" style="display: block; width: 100%; text-align: center; text-decoration: none;">
                    <i class="fa-solid fa-plus"></i> Nouvelle conversation
                </a>
            </div>
        </div>
        
        <!-- Message Area -->
        <div class="message-area <?php echo (isset($selected_conversation) || $new_conversation) ? 'active' : ''; ?>" id="message-area">
            <?php if ($new_conversation): ?>
                <!-- User Search Interface -->
                <div class="user-search-container">
                    <div class="user-search-header">
                        <h2 class="user-search-title">Nouvelle conversation</h2>
                        <p>Recherchez un utilisateur pour commencer une conversation</p>
                        
                        <?php if (!empty($error_message)): ?>
                            <div style="color: #e74c3c; background-color: #fdf0ee; padding: 10px; border-radius: 5px; margin: 10px 0;">
                                <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form action="messagerie.php" method="get" class="user-search-form">
                        <input type="hidden" name="new" value="1">
                        <input type="text" name="search" class="user-search-input" placeholder="Nom, prénom ou email..." value="<?php echo htmlspecialchars($search_term); ?>" required>
                        <button type="submit" class="user-search-button">Rechercher</button>
                    </form>
                    
                    <div class="user-list">
                        <?php if (empty($users) && !empty($search_term)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fa-solid fa-user-slash"></i>
                                </div>
                                <div class="empty-state-message">Aucun utilisateur trouvé</div>
                                <div class="empty-state-submessage">Essayez avec un autre terme de recherche</div>
                            </div>
                        <?php elseif (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <div class="user-item">
                                    <div class="user-info">
                                        <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['name']); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                    <a href="message_api.php?action=start_conversation&user_id=<?php echo $user['id']; ?>" class="start-conversation-button">
                                        <i class="fa-solid fa-message"></i> Contacter
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php elseif (empty($search_term)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fa-solid fa-search"></i>
                                </div>
                                <div class="empty-state-message">Commencez par rechercher un utilisateur</div>
                                <div class="empty-state-submessage">Entrez un nom, prénom ou email dans le champ de recherche</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif (isset($selected_conversation) && isset($conversation_partner)): ?>
                <!-- Conversation View -->
                <div class="message-header">
                    <div class="back-to-conversations" id="back-to-conversations">
                        <i class="fa-solid fa-arrow-left"></i>
                    </div>
                    <div class="partner-name"><?php echo htmlspecialchars($conversation_partner['first_name'] . ' ' . $conversation_partner['name']); ?></div>
                </div>
                
                <div class="message-content" id="message-content">
                    <?php if (empty($messages)): ?>
                        <div class="empty-state" style="margin: auto;">
                            <div class="empty-state-icon">
                                <i class="fa-solid fa-comment-dots"></i>
                            </div>
                            <div class="empty-state-message">Aucun message</div>
                            <div class="empty-state-submessage">Commencez la conversation en envoyant un message</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message-bubble <?php echo $message['is_mine'] ? 'mine' : 'other'; ?>"
                                 data-id="<?php echo $message['id']; ?>"
                                 data-timestamp="<?php echo $message['timestamp']; ?>">
                                <?php echo nl2br(htmlspecialchars($message['message_content'])); ?>
                                <div class="message-time"><?php echo formatMessageTime($message['timestamp']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="message-input-container">
                    <form id="message-form" action="message_api.php" method="post" style="display: flex; width: 100%; align-items: center;">
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="conversation_id" value="<?php echo $selected_conversation['id']; ?>">
                        <input type="hidden" name="receiver_id" value="<?php echo $conversation_partner['id']; ?>">
                        <textarea id="message-input" name="message_content" class="message-input" placeholder="Tapez votre message..." rows="1" style="flex: 1;"></textarea>
                        <button type="submit" class="send-button" style="margin-left: 10px;">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- No Conversation Selected -->
                <div class="no-conversation-selected">
                    <div class="no-conversation-icon">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <h3>Sélectionnez une conversation</h3>
                    <p>Ou commencez une nouvelle conversation</p>
                    <a href="messagerie.php?new=1" class="start-conversation-button" style="margin-top: 20px;">
                        <i class="fa-solid fa-plus"></i> Nouvelle conversation
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../TEMPLATE/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto resize textarea
            const textarea = document.getElementById('message-input');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            }
            
            // Scroll to bottom of messages
            const messageContent = document.getElementById('message-content');
            if (messageContent) {
                messageContent.scrollTop = messageContent.scrollHeight;
            }
            
            // Mobile view - back button
            const backButton = document.getElementById('back-to-conversations');
            const conversationsSidebar = document.getElementById('conversations-sidebar');
            const messageArea = document.getElementById('message-area');
            
            if (backButton && conversationsSidebar && messageArea) {
                backButton.addEventListener('click', function() {
                    conversationsSidebar.classList.remove('hidden');
                    messageArea.classList.remove('active');
                });
            }
            
            // Form submission with AJAX
            const messageForm = document.getElementById('message-form');
            if (messageForm) {
                messageForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const messageInput = document.getElementById('message-input');
                    
                    // Simple validation
                    const messageContent = messageInput.value.trim();
                    if (!messageContent) {
                        return false;
                    }
                    
                    fetch('message_api.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Add message to UI
                            const messageContentDiv = document.getElementById('message-content');
                            const messageBubble = document.createElement('div');
                            messageBubble.className = 'message-bubble mine';
                            messageBubble.setAttribute('data-id', data.message_id);
                            
                            // Format message with line breaks
                            const messageText = messageContent.replace(/\n/g, '<br>');
                            
                            messageBubble.innerHTML = `
                                ${messageText}
                                <div class="message-time">À l'instant</div>
                            `;
                            
                            // Add to content and scroll to bottom
                            messageContentDiv.appendChild(messageBubble);
                            messageContentDiv.scrollTop = messageContentDiv.scrollHeight;
                            
                            // Clear input
                            messageInput.value = '';
                            messageInput.style.height = 'auto';
                            
                            // Remove empty state if it exists
                            const emptyState = messageContentDiv.querySelector('.empty-state');
                            if (emptyState) {
                                emptyState.remove();
                            }
                        } else {
                            alert('Erreur lors de l\'envoi du message: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Une erreur est survenue lors de l\'envoi du message.');
                    });
                });
            }
            
            // Check for new messages periodically
            function checkNewMessages() {
                const conversationId = new URLSearchParams(window.location.search).get('conversation_id');
                if (!conversationId) return;
                
                const messageContentDiv = document.getElementById('message-content');
                if (!messageContentDiv) return;
                
                // Get the timestamp of the last message
                const messages = messageContentDiv.querySelectorAll('.message-bubble');
                let lastTimestamp = '';
                if (messages.length > 0) {
                    // Try to get the timestamp from the last message's data attribute
                    // If not set, use the current server time which will be handled server-side
                    lastTimestamp = messages[messages.length - 1].getAttribute('data-timestamp') || '';
                }
                
                fetch(`message_api.php?action=check_new_messages&conversation_id=${conversationId}&last_timestamp=${lastTimestamp}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.new_messages && data.new_messages.length > 0) {
                        // Remove empty state if it exists
                        const emptyState = messageContentDiv.querySelector('.empty-state');
                        if (emptyState) {
                            emptyState.remove();
                        }
                        
                        // Add each new message
                        data.new_messages.forEach(message => {
                            // Check if this message already exists
                            if (!document.querySelector(`.message-bubble[data-id="${message.id}"]`)) {
                                const messageBubble = document.createElement('div');
                                messageBubble.className = `message-bubble ${message.is_mine ? 'mine' : 'other'}`;
                                messageBubble.setAttribute('data-id', message.id);
                                messageBubble.setAttribute('data-timestamp', message.timestamp);
                                
                                messageBubble.innerHTML = `
                                    ${message.message_content.replace(/\n/g, '<br>')}
                                    <div class="message-time">${message.formatted_time}</div>
                                `;
                                
                                messageContentDiv.appendChild(messageBubble);
                                messageContentDiv.scrollTop = messageContentDiv.scrollHeight;
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error checking new messages:', error);
                });
            }
            
            // Check for new messages every 5 seconds
            if (new URLSearchParams(window.location.search).get('conversation_id')) {
                setInterval(checkNewMessages, 5000);
            }
        });
    </script>
</body>
</html>