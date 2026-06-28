<?php
require_once 'config.php';

// Start output buffering to prevent header issues
ob_start();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed']);
    ob_end_flush();
    exit();
}

// Get and validate JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    ob_end_flush();
    exit();
}

// Validate required fields
$required_fields = ['email', 'book_id'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        ob_end_flush();
        exit();
    }
}

// Sanitize and validate input
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$book_id = filter_var($data['book_id'], FILTER_VALIDATE_INT);
$time_spent = isset($data['time_spent']) ? filter_var($data['time_spent'], FILTER_VALIDATE_INT) : 0;

if (!$email || !$book_id || $time_spent === false) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    ob_end_flush();
    exit();
}

try {
    // Check if the record exists first
    $check_stmt = $conn->prepare("SELECT 1 FROM user_books WHERE email = ? AND book_id = ?");
    $check_stmt->bind_param("si", $email, $book_id);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->num_rows > 0;
    
    if (!$exists) {
        // Create a new record if it doesn't exist
        $insert_stmt = $conn->prepare("INSERT INTO user_books 
                                     (email, book_id, time_spent, last_read_at) 
                                     VALUES (?, ?, ?, NOW())");
        $insert_stmt->bind_param("sii", $email, $book_id, $time_spent);
        $insert_stmt->execute();
    } else {
        // Update existing record
        $update_stmt = $conn->prepare("UPDATE user_books 
                                     SET time_spent = time_spent + ?, 
                                     last_read_at = NOW() 
                                     WHERE email = ? AND book_id = ?");
        $update_stmt->bind_param("isi", $time_spent, $email, $book_id);
        $update_stmt->execute();
    }

    // Update was successful
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Reading time updated successfully',
        'time_spent' => $time_spent
    ]);

} catch (Exception $e) {
    // Handle database errors
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}

// Flush output buffer
ob_end_flush();
?>