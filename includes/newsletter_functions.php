<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to get all users subscribed to the newsletter
function getSubscribedUsers($conn) {
    $sql = "SELECT id, name, first_name, email FROM user_form WHERE newsletter_subscribed = 1";
    $result = $conn->query($sql);
    
    $subscribers = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subscribers[] = $row;
        }
    }
    
    return $subscribers;
} // <-- This closes getSubscribedUsers function

// Function to send activity notification emails
function sendActivityNotification($activity_title, $activity_id) {
    // Database configuration
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "user_db";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        return false;
    }
    
    // Get all subscribers
    $subscribers = getSubscribedUsers($conn);
    
    if (empty($subscribers)) {
        $conn->close();
        return false; // No subscribers to notify
    }
    require 'PHPMailer/Exception.php';
    require 'PHPMailer/PHPMailer.php';
    require 'PHPMailer/SMTP.php';
    
    // Website URL - change this to your actual website URL
    $website_url = "http://localhost"; // Update with your actual website URL
    
    // Website URL - change this to your actual website URL
    $website_url = "http://localhost"; // Update with your actual website URL
    
    // Send email to each subscriber
    foreach ($subscribers as $subscriber) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'synapsentreprise@gmail.com'; // Your Gmail address
            $mail->Password = 'zasd rssc mbsy rnag'; // Your app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Recipients
            $mail->setFrom('synapsentreprise@gmail.com', 'Synapse');
            $mail->addAddress($subscriber['email'], $subscriber['first_name'] . ' ' . $subscriber['name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Nouvelle activité chez Synapse !';
            
            // Email body
            $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #828977; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; background-color: #f9f9f9; }
                    .button { display: inline-block; padding: 10px 20px; background-color: #45a163; color: white; text-decoration: none; border-radius: 5px; }
                    .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Nouvelle activité disponible !</h2>
                    </div>
                    <div class='content'>
                        <p>Bonjour " . htmlspecialchars($subscriber['first_name']) . ",</p>
                        <p>Une nouvelle activité vient d'être ajoutée sur notre site :</p>
                        <h3>" . htmlspecialchars($activity_title) . "</h3>
                        <p>Venez la découvrir dès maintenant !</p>
                        <p><a href='" . $website_url . "/activite.php?id=" . $activity_id . "' class='button'>Voir l'activité</a></p>
                    </div>
                    <div class='footer'>
                        <p>Vous recevez cet email car vous êtes abonné à notre newsletter. Pour vous désabonner, rendez-vous sur votre espace personnel.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Plain text version for non-HTML mail clients
            $mail->AltBody = "Bonjour " . $subscriber['first_name'] . ",\n\n" .
                            "Une nouvelle activité vient d'être ajoutée sur notre site : " . $activity_title . "\n\n" .
                            "Venez la découvrir dès maintenant : " . $website_url . "/activite.php?id=" . $activity_id . "\n\n" .
                            "Vous recevez cet email car vous êtes abonné à notre newsletter. Pour vous désabonner, rendez-vous sur votre espace personnel.";
            
            $mail->send();
            error_log("Email notification sent to " . $subscriber['email']);
        } catch (Exception $e) {
            error_log("Error sending email to " . $subscriber['email'] . ": " . $mail->ErrorInfo);
        }
    }
    
    $conn->close();
    return true;
}
?>