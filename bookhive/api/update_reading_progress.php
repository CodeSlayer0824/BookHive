<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

if (!isLoggedIn()) {
    http_response_code(401);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$email = $_SESSION['email'];
$book_id = $data['book_id'];
$current_page = $data['current_page'];
$progress = $data['progress'];
$time_spent = $data['time_spent'];

// Check if record exists
$check = $conn->prepare("SELECT 1 FROM user_books WHERE email = ? AND book_id = ?");
$check->bind_param("si", $email, $book_id);
$check->execute();
$exists = $check->get_result()->num_rows > 0;

if ($exists) {
    // Update existing record
    $stmt = $conn->prepare("UPDATE user_books SET 
                          current_page = ?,
                          pages_read = ?,
                          progress = ?,
                          status = IF(? = 100, 'completed', IF(status = 'want_to_read', 'reading', status)),
                          started_at = IF(started_at IS NULL, NOW(), started_at),
                          last_read_at = NOW()
                          WHERE email = ? AND book_id = ?");
    $stmt->bind_param("iiiisi", $current_page, $pages_read, $progress, $progress, $email, $book_id);
} else {
    // Insert new record
    $stmt = $conn->prepare("INSERT INTO user_books 
                          (email, book_id, current_page, pages_read, progress, status, started_at, last_read_at) 
                          VALUES (?, ?, ?, ?, ?, 'reading', NOW(), NOW())");
    $stmt->bind_param("siiii", $email, $book_id, $current_page, $pages_read, $progress);
}

$stmt->execute();

// Update reading time (you might want to add a separate table for this)
http_response_code(200);