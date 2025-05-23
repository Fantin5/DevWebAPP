<?php
// Newsletter functions for activity notifications - Using PHPMailer

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendActivityNotification($activityTitle, $activityId, $activityTags = []) {
    error_log("=== NEWSLETTER FUNCTION CALLED ===");
    error_log("Activity Title: " . $activityTitle);
    error_log("Activity ID: " . $activityId);
    error_log("Activity Tags: " . implode(', ', $activityTags));
    
    // Include PHPMailer files
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
    
    // Database connections
    $user_conn = new mysqli("localhost", "root", "", "user_db");
    $activity_conn = new mysqli("localhost", "root", "", "activity");
    
    if ($user_conn->connect_error) {
        error_log("USER DB CONNECTION ERROR: " . $user_conn->connect_error);
        return false;
    }
    
    if ($activity_conn->connect_error) {
        error_log("ACTIVITY DB CONNECTION ERROR: " . $activity_conn->connect_error);
        return false;
    }
    
    error_log("✓ Database connections successful");
    
    try {
        // Get activity details including creator information
        $activity_sql = "SELECT titre, description, prix FROM activites WHERE id = ?";
        $activity_stmt = $activity_conn->prepare($activity_sql);
        $activity_stmt->bind_param("i", $activityId);
        $activity_stmt->execute();
        $activity_result = $activity_stmt->get_result();
        $activity = $activity_result->fetch_assoc();
        $activity_stmt->close();
        
        if (!$activity) {
            error_log("ERROR: Activity not found for ID: " . $activityId);
            return false;
        }
        
        error_log("✓ Activity found: " . $activity['titre']);
        
        // Extract creator information from activity description
        $creator_user_id = null;
        if (preg_match('/<!--CREATOR:(.*?)-->/', $activity['description'], $matches)) {
            $creator_info = json_decode(base64_decode($matches[1]), true);
            if (isset($creator_info['user_id'])) {
                $creator_user_id = $creator_info['user_id'];
                error_log("✓ Activity creator ID: " . $creator_user_id);
            }
        } else {
            error_log("⚠ No creator info found in activity description");
        }
        
        // Get tag IDs for the activity from activity_tags table
        $tag_ids = [];
        $activity_tags_sql = "SELECT tag_definition_id FROM activity_tags WHERE activity_id = ?";
        $activity_tags_stmt = $activity_conn->prepare($activity_tags_sql);
        $activity_tags_stmt->bind_param("i", $activityId);
        $activity_tags_stmt->execute();
        $activity_tags_result = $activity_tags_stmt->get_result();
        
        while ($row = $activity_tags_result->fetch_assoc()) {
            $tag_ids[] = $row['tag_definition_id'];
        }
        $activity_tags_stmt->close();
        
        error_log("Activity tag IDs: " . implode(', ', $tag_ids));
        
        // Simple approach: Get ALL newsletter subscribers (ignore tags for now)
        $users_sql = "SELECT email, first_name, name, id FROM user_form WHERE newsletter_subscribed = 1 AND email_verified = 1";
        
        // Exclude creator if found
        if ($creator_user_id) {
            $users_sql .= " AND id != " . $creator_user_id;
            error_log("✓ Excluding creator ID: " . $creator_user_id);
        }
        
        error_log("SQL Query: " . $users_sql);
        
        $users_result = $user_conn->query($users_sql);
        
        if (!$users_result) {
            error_log("ERROR: User query failed: " . $user_conn->error);
            return false;
        }
        
        $total_users = $users_result->num_rows;
        error_log("Found " . $total_users . " newsletter subscribers");
        
        if ($total_users == 0) {
            error_log("⚠ No newsletter subscribers found!");
            return false;
        }
        
        $sent_count = 0;
        
        // Clean description for email
        $description = strip_tags($activity['description']);
        $description = preg_replace('/<!--CREATOR:.*?-->/', '', $description);
        $description = trim($description);
        
        while ($user = $users_result->fetch_assoc()) {
            error_log("Attempting to send email to: " . $user['email'] . " (ID: " . $user['id'] . ")");
            
            // Create PHPMailer instance
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'synapsentreprise@gmail.com';
                $mail->Password = 'zasd rssc mbsy rnag';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';
                
                // Recipients
                $mail->setFrom('synapsentreprise@gmail.com', 'Synapse');
                $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['name']);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Nouvelle activité: ' . $activity['titre'];
                
                $mail->Body = "
                    <html>
                    <head><title>Nouvelle Activité</title></head>
                    <body>
                        <h2>Bonjour " . htmlspecialchars($user['first_name']) . " " . htmlspecialchars($user['name']) . ",</h2>
                        
                        <p>Une nouvelle activité vient d'être ajoutée qui pourrait vous intéresser!</p>
                        
                        <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                            <h3 style='color: #007bff; margin-top: 0;'>" . htmlspecialchars($activity['titre']) . "</h3>
                            <p><strong>Description:</strong> " . htmlspecialchars(substr($description, 0, 200)) . (strlen($description) > 200 ? '...' : '') . "</p>
                            <p><strong>Prix:</strong> " . ($activity['prix'] > 0 ? number_format($activity['prix'], 2) . "€" : "Gratuit") . "</p>
                        </div>
                        
                        <p><a href='http://localhost/ProjetWebDev copy2/Testing grounds/main.php' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Voir l'activité</a></p>
                        
                        <hr>
                        <p style='font-size: 12px; color: #6c757d;'>
                            Vous recevez cet email car vous êtes abonné à notre newsletter.
                            <br>
                            <a href='http://localhost/ProjetWebDev copy2/Compte/mon-espace.php'>Gérer mon compte</a>
                        </p>
                    </body>
                    </html>
                ";
                
                $mail->AltBody = 'Bonjour ' . $user['first_name'] . ' ' . $user['name'] . ',
                
Une nouvelle activité vient d\'être ajoutée: ' . $activity['titre'] . '
Description: ' . substr($description, 0, 200) . '
Prix: ' . ($activity['prix'] > 0 ? number_format($activity['prix'], 2) . "€" : "Gratuit");
                
                $mail->send();
                $sent_count++;
                error_log("✓ Email sent successfully to: " . $user['email']);
                
            } catch (Exception $e) {
                error_log("✗ Failed to send email to: " . $user['email'] . " - Error: " . $mail->ErrorInfo);
            }
        }
        
        error_log("Newsletter sent to $sent_count out of $total_users users for activity: $activityTitle");
        
        if ($sent_count > 0) {
            error_log("✓ NEWSLETTER SUCCESS: " . $sent_count . " emails sent");
            return true;
        } else {
            error_log("✗ NEWSLETTER FAILED: No emails sent");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("NEWSLETTER EXCEPTION: " . $e->getMessage());
        return false;
    } finally {
        $user_conn->close();
        $activity_conn->close();
        error_log("=== NEWSLETTER FUNCTION END ===");
    }
}

function getUserTagPreferences($userId) {
    $userConn = new mysqli("localhost", "root", "", "user_db");
    
    if ($userConn->connect_error) {
        return [];
    }
    
    $query = "SELECT tag_id FROM user_newsletter_tags WHERE user_id = ?";
    $stmt = $userConn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tagIds = [];
    while ($row = $result->fetch_assoc()) {
        $tagIds[] = $row['tag_id'];
    }
    
    $stmt->close();
    $userConn->close();
    
    return $tagIds;
}

function updateUserTagPreferences($userId, $selectedTagIds) {
    $userConn = new mysqli("localhost", "root", "", "user_db");
    
    if ($userConn->connect_error) {
        return false;
    }
    
    $userConn->begin_transaction();
    
    try {
        // Remove existing preferences
        $deleteQuery = "DELETE FROM user_newsletter_tags WHERE user_id = ?";
        $deleteStmt = $userConn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $userId);
        $deleteStmt->execute();
        $deleteStmt->close();
        
        // Insert new preferences
        if (!empty($selectedTagIds)) {
            $insertQuery = "INSERT INTO user_newsletter_tags (user_id, tag_id) VALUES (?, ?)";
            $insertStmt = $userConn->prepare($insertQuery);
            
            foreach ($selectedTagIds as $tagId) {
                $insertStmt->bind_param("ii", $userId, $tagId);
                $insertStmt->execute();
            }
            $insertStmt->close();
        }
        
        $userConn->commit();
        return true;
        
    } catch (Exception $e) {
        $userConn->rollback();
        error_log("Tag preference update error: " . $e->getMessage());
        return false;
    } finally {
        $userConn->close();
    }
}

function getUserSelectedTags($userId) {
    $userConn = new mysqli("localhost", "root", "", "user_db");
    $activityConn = new mysqli("localhost", "root", "", "activity");
    
    if ($userConn->connect_error || $activityConn->connect_error) {
        return [];
    }
    
    $query = "SELECT unt.tag_id, td.display_name 
              FROM user_newsletter_tags unt 
              JOIN activity.tag_definitions td ON unt.tag_id = td.id 
              WHERE unt.user_id = ?";
    
    $stmt = $userConn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tags = [];
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row['display_name'];
    }
    
    $stmt->close();
    $userConn->close();
    $activityConn->close();
    
    return $tags;
}
?>