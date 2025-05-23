<?php
// Simple test to check if PHP mail() function works
echo "<h1>Testing Email System</h1>";

// Test 1: Basic PHP mail() function
echo "<h2>Test 1: Basic PHP mail()</h2>";
$to = "fantinmoulind@gmail.com"; // CHANGE THIS TO YOUR EMAIL
$subject = "Test Email from Synapse";
$message = "This is a test email to check if the mail system works.";
$headers = "From: synapsentreprise@gmail.com\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

if (mail($to, $subject, $message, $headers)) {
    echo "<p style='color: green;'>✓ Basic mail() function works!</p>";
} else {
    echo "<p style='color: red;'>✗ Basic mail() function failed!</p>";
}

// Test 2: Check if newsletter function exists
echo "<h2>Test 2: Newsletter Function</h2>";
require_once '../includes/newsletter_functions.php';

if (function_exists('sendActivityNotification')) {
    echo "<p style='color: green;'>✓ sendActivityNotification function exists!</p>";
} else {
    echo "<p style='color: red;'>✗ sendActivityNotification function NOT found!</p>";
}

// Test 3: Check database connections
echo "<h2>Test 3: Database Connections</h2>";

$user_conn = new mysqli("localhost", "root", "", "user_db");
if ($user_conn->connect_error) {
    echo "<p style='color: red;'>✗ user_db connection failed: " . $user_conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✓ user_db connection works!</p>";
}

$activity_conn = new mysqli("localhost", "root", "", "activity");
if ($activity_conn->connect_error) {
    echo "<p style='color: red;'>✗ activity database connection failed: " . $activity_conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✓ activity database connection works!</p>";
}

// Test 4: Check newsletter subscribers
echo "<h2>Test 4: Newsletter Subscribers</h2>";
if (!$user_conn->connect_error) {
    $result = $user_conn->query("SELECT COUNT(*) as count FROM user_form WHERE newsletter_subscribed = 1 AND email_verified = 1");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>Newsletter subscribers: <strong>" . $row['count'] . "</strong></p>";
        
        // Show actual subscribers
        $subscribers = $user_conn->query("SELECT id, email, first_name, name FROM user_form WHERE newsletter_subscribed = 1 AND email_verified = 1");
        echo "<ul>";
        while ($sub = $subscribers->fetch_assoc()) {
            echo "<li>ID: " . $sub['id'] . " - " . $sub['first_name'] . " " . $sub['name'] . " (" . $sub['email'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Error checking subscribers: " . $user_conn->error . "</p>";
    }
}

// Test 5: Test newsletter function with a fake activity
echo "<h2>Test 5: Test Newsletter Function</h2>";
if (!$user_conn->connect_error && !$activity_conn->connect_error) {
    echo "<p>Testing newsletter function with fake data...</p>";
    
    // Create a test activity first
    $test_title = "Test Activity " . date('Y-m-d H:i:s');
    $test_description = "This is a test activity created for newsletter testing.";
    
    $stmt = $activity_conn->prepare("INSERT INTO activites (titre, description, prix) VALUES (?, ?, 0)");
    $stmt->bind_param("ss", $test_title, $test_description);
    
    if ($stmt->execute()) {
        $test_activity_id = $activity_conn->insert_id;
        echo "<p>Created test activity with ID: $test_activity_id</p>";
        
        // Try to send newsletter
        $result = sendActivityNotification($test_title, $test_activity_id, ['test']);
        if ($result) {
            echo "<p style='color: green;'>✓ Newsletter function executed successfully!</p>";
        } else {
            echo "<p style='color: red;'>✗ Newsletter function failed!</p>";
        }
        
        // Clean up - delete test activity
        $activity_conn->query("DELETE FROM activites WHERE id = $test_activity_id");
    } else {
        echo "<p style='color: red;'>✗ Could not create test activity: " . $stmt->error . "</p>";
    }
}

if ($user_conn) $user_conn->close();
if ($activity_conn) $activity_conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
h2 { color: #007bff; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
p { margin: 10px 0; }
</style>