<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "bookhive";

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Function to sanitize input
function sanitize($data) {
    global $conn;
    return htmlspecialchars(strip_tags($conn->real_escape_string(trim($data))));
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['email']);
}

// Get user data
function getUserData($email) {
    global $conn;
    $sql = "SELECT s.*, p.* FROM signup s LEFT JOIN user_profiles p ON s.email = p.email WHERE s.email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}


/**
 * Increment the view count for a book
 * @param int $book_id The ID of the book
 * @param string|null $email Optional email of the logged-in user
 * @return bool True if successful, false otherwise
 */
function incrementBookViews($book_id, $email = null) {
    global $conn;
    
    // First update the book's total views
    $sql = "UPDATE books SET views = views + 1 WHERE book_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $book_id);
    $success = $stmt->execute();
    
    // If user is logged in, track their view in reading_history
    if ($email && $success) {
        $sql = "INSERT INTO reading_history (email, book_id, last_read) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE last_read = NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $book_id);
        $stmt->execute();
    }
    
    return $success;
}


/**
 * Get the full URL for a book cover image
 * @param string $cover_url The cover image filename
 * @return string The complete URL to the cover image
 */
function getCoverUrl($cover_url) {
    if (empty($cover_url)) {
        return '/project/bookhive/assets/images/default-cover.jpg';
    }
    // If the path already includes 'uploads/', don't add it again
    if (strpos($cover_url, 'uploads/') === 0) {
        return '/project/bookhive/' . $cover_url;
    }
    return '/project/bookhive/uploads/covers/' . $cover_url;
}

/**
 * Get the full URL for a book PDF
 * @param string $pdf_url The PDF filename
 * @return string|false The complete URL to the PDF file or false if empty
 */
function getPdfUrl($pdf_url) {
    if (empty($pdf_url)) {
        return false;
    }
    // If the path already includes 'uploads/', don't add it again
    if (strpos($pdf_url, 'uploads/') === 0) {
        return '/project/bookhive/' . $pdf_url;
    }
    return '/project/bookhive/uploads/pdfs/' . $pdf_url;
}
