<?php
include '../Connexion-Inscription/config.php'; // Adjust this path to where your config.php actually is
include 'auth_functions.php';

// Require login to access this API
require_login();

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Initialize response
$response = [
    'success' => false,
    'message' => 'Action non spécifiée'
];

// Function to get conversation ID between two users
function getConversationId($conn, $user1_id, $user2_id) {
    // Check if conversation already exists
    $check_query = "SELECT id FROM conversations 
                    WHERE (user1_id = $user1_id AND user2_id = $user2_id) 
                    OR (user1_id = $user2_id AND user2_id = $user1_id)";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $row = mysqli_fetch_assoc($check_result);
        return $row['id'];
    }
    
    // Create new conversation
    $insert_query = "INSERT INTO conversations (user1_id, user2_id, updated_at) 
                    VALUES ($user1_id, $user2_id, NOW())";
    
    if (mysqli_query($conn, $insert_query)) {
        return mysqli_insert_id($conn);
    }
    
    return false;
}

// Function to update conversation with last message
function updateConversation($conn, $conversation_id, $message_id) {
    $update_query = "UPDATE conversations 
                    SET last_message_id = $message_id, updated_at = NOW() 
                    WHERE id = $conversation_id";
    return mysqli_query($conn, $update_query);
}

// Format timestamp for display
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

// Process GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    switch ($action) {
        case 'get_conversation_count':
            // Get the count of active conversations for the user
            $count_query = "SELECT COUNT(*) as count FROM conversations 
                           WHERE user1_id = $user_id OR user2_id = $user_id";
            $count_result = mysqli_query($conn, $count_query);
            
            if ($count_result) {
                $count_data = mysqli_fetch_assoc($count_result);
                $response = [
                    'success' => true,
                    'count' => $count_data['count']
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Erreur lors de la récupération du nombre de conversations',
                    'count' => 0
                ];
            }
            break;
            
        case 'start_conversation':
            // Start a new conversation with a user
            if (isset($_GET['user_id'])) {
                $partner_id = (int)$_GET['user_id'];
                
                // Validate that partner is not the current user
                if ($partner_id == $user_id) {
                    header("Location: messagerie.php?error=cannot_message_self");
                    exit();
                }
                
                // Check if partner exists
                $user_check = "SELECT id FROM user_form WHERE id = $partner_id";
                $user_result = mysqli_query($conn, $user_check);
                
                if (mysqli_num_rows($user_result) > 0) {
                    $conversation_id = getConversationId($conn, $user_id, $partner_id);
                    
                    if ($conversation_id) {
                        // Redirect to conversation
                        header("Location: messagerie.php?conversation_id=$conversation_id");
                        exit();
                    } else {
                        // Error creating conversation
                        header("Location: messagerie.php?error=conversation_creation_failed");
                        exit();
                    }
                } else {
                    // User doesn't exist
                    header("Location: messagerie.php?error=user_not_found");
                    exit();
                }
            } else {
                // No user ID provided
                header("Location: messagerie.php?error=no_user_id");
                exit();
            }
            break;
            
        case 'check_new_messages':
            // Check for new messages in a conversation
            if (isset($_GET['conversation_id'])) {
                $conversation_id = (int)$_GET['conversation_id'];
                
                // Get last message timestamp from query parameters
                $last_timestamp = isset($_GET['last_timestamp']) ? 
                                  mysqli_real_escape_string($conn, $_GET['last_timestamp']) : 
                                  date('Y-m-d H:i:s');
                
                // Get conversation details
                $conv_query = "SELECT * FROM conversations 
                              WHERE id = $conversation_id 
                              AND (user1_id = $user_id OR user2_id = $user_id)";
                $conv_result = mysqli_query($conn, $conv_query);
                
                if (mysqli_num_rows($conv_result) > 0) {
                    $conversation = mysqli_fetch_assoc($conv_result);
                    
                    // Get partner ID
                    $partner_id = ($conversation['user1_id'] == $user_id) ? 
                                  $conversation['user2_id'] : $conversation['user1_id'];
                    
                    // Get new messages
                    $messages_query = "SELECT m.*, 
                                      CASE WHEN m.sender_id = $user_id THEN 1 ELSE 0 END as is_mine
                                      FROM messages m
                                      WHERE ((m.sender_id = $user_id AND m.receiver_id = $partner_id)
                                      OR (m.sender_id = $partner_id AND m.receiver_id = $user_id))
                                      AND m.timestamp > '$last_timestamp'
                                      ORDER BY m.timestamp ASC";
                    $messages_result = mysqli_query($conn, $messages_query);
                    
                    $new_messages = [];
                    while ($message = mysqli_fetch_assoc($messages_result)) {
                        // Format the timestamp for display
                        $message['formatted_time'] = formatMessageTime($message['timestamp']);
                        $new_messages[] = $message;
                        
                        // Mark as read if the message is for the current user
                        if ($message['receiver_id'] == $user_id && $message['is_read'] == 0) {
                            $update_read = "UPDATE messages SET is_read = 1 
                                          WHERE id = " . $message['id'];
                            mysqli_query($conn, $update_read);
                        }
                    }
                    
                    $response = [
                        'success' => true,
                        'new_messages' => $new_messages
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Conversation non trouvée'
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'ID de conversation non spécifié'
                ];
            }
            break;
            
        default:
            $response = [
                'success' => false,
                'message' => 'Action non reconnue'
            ];
            break;
    }
}

// Process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'send_message':
            // Send a new message
            if (isset($_POST['conversation_id']) && isset($_POST['receiver_id']) && isset($_POST['message_content'])) {
                $conversation_id = (int)$_POST['conversation_id'];
                $receiver_id = (int)$_POST['receiver_id'];
                $message_content = mysqli_real_escape_string($conn, $_POST['message_content']);
                
                // Validation
                if (empty(trim($message_content))) {
                    $response = [
                        'success' => false,
                        'message' => 'Le message ne peut pas être vide'
                    ];
                    break;
                }
                
                // Verify conversation belongs to the user
                $conv_query = "SELECT * FROM conversations 
                              WHERE id = $conversation_id 
                              AND (user1_id = $user_id OR user2_id = $user_id)";
                $conv_result = mysqli_query($conn, $conv_query);
                
                if (mysqli_num_rows($conv_result) > 0) {
                    // Insert message
                    $insert_query = "INSERT INTO messages (sender_id, receiver_id, message_content, timestamp) 
                                    VALUES ($user_id, $receiver_id, '$message_content', NOW())";
                    
                    if (mysqli_query($conn, $insert_query)) {
                        $message_id = mysqli_insert_id($conn);
                        
                        // Update conversation with last message
                        if (updateConversation($conn, $conversation_id, $message_id)) {
                            $response = [
                                'success' => true,
                                'message_id' => $message_id
                            ];
                        } else {
                            $response = [
                                'success' => false,
                                'message' => 'Erreur lors de la mise à jour de la conversation'
                            ];
                        }
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Erreur lors de l\'envoi du message'
                        ];
                    }
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Conversation non trouvée'
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Paramètres manquants'
                ];
            }
            break;
            
        case 'delete_conversation':
            // Delete a conversation
            if (isset($_POST['conversation_id'])) {
                $conversation_id = (int)$_POST['conversation_id'];
                
                // Verify conversation belongs to the user
                $conv_query = "SELECT * FROM conversations 
                              WHERE id = $conversation_id 
                              AND (user1_id = $user_id OR user2_id = $user_id)";
                $conv_result = mysqli_query($conn, $conv_query);
                
                if (mysqli_num_rows($conv_result) > 0) {
                    // Delete messages first (due to foreign key constraints)
                    $conv_data = mysqli_fetch_assoc($conv_result);
                    $other_user_id = ($conv_data['user1_id'] == $user_id) ? $conv_data['user2_id'] : $conv_data['user1_id'];
                    
                    $delete_messages = "DELETE FROM messages 
                                      WHERE (sender_id = $user_id AND receiver_id = $other_user_id)
                                      OR (sender_id = $other_user_id AND receiver_id = $user_id)";
                    
                    if (mysqli_query($conn, $delete_messages)) {
                        // Now delete the conversation
                        $delete_conv = "DELETE FROM conversations WHERE id = $conversation_id";
                        
                        if (mysqli_query($conn, $delete_conv)) {
                            $response = [
                                'success' => true,
                                'message' => 'Conversation supprimée avec succès'
                            ];
                        } else {
                            $response = [
                                'success' => false,
                                'message' => 'Erreur lors de la suppression de la conversation: ' . mysqli_error($conn)
                            ];
                        }
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Erreur lors de la suppression des messages: ' . mysqli_error($conn)
                        ];
                    }
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Conversation non trouvée ou vous n\'avez pas les droits pour la supprimer'
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'ID de conversation non spécifié'
                ];
            }
            break;
            
        default:
            $response = [
                'success' => false,
                'message' => 'Action non reconnue'
            ];
            break;
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
// cvq