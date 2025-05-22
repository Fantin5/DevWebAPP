<?php
require_once 'tag_setup.php';

header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "activity";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

$tagManager = new TagManager($conn);
$tags = $tagManager->getAllTags();

echo json_encode($tags);
$conn->close();