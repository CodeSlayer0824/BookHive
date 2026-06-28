<?php
header('Content-Type: application/json');

$host = "localhost";
$username = "root";
$password = "";
$dbname = "bookhive";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

$type = $_GET['type'] ?? '';
$options = [];

if ($type === 'author_name') {
    $result = $conn->query("SELECT author_id as id, author_name as name FROM authors ORDER BY author_name");
} elseif ($type === 'category_name') {
    $result = $conn->query("SELECT category_id as id, category_name as name FROM categories ORDER BY category_name");
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $options[] = $row;
    }
}

echo json_encode($options);
$conn->close();
?>