<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$email = $_SESSION['email'];
$book_id = sanitize($_POST['book_id']);
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

if ($rating < 0 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating value']);
    exit();
}

// Update user's rating
$sql = "INSERT INTO user_books (email, book_id, rating) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE rating = VALUES(rating)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $email, $book_id, $rating);

if ($stmt->execute()) {
    // Update book's average rating
    $sql = "UPDATE books b
            SET b.rating = (
                SELECT AVG(ub.rating)
                FROM user_books ub
                WHERE ub.book_id = b.book_id AND ub.rating IS NOT NULL
            )
            WHERE b.book_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $book_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update rating']);
}
?>