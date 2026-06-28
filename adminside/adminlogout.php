<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$database = "bookhive";

// Establish a database connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// If the admin is logged in, update is_logged_in to 0
if (isset($_SESSION['username'])) {
    $user = $_SESSION['username'];

    $update = $conn->prepare("UPDATE admins SET is_logged_in = 0 WHERE username = ?");
    if ($update) {
        $update->bind_param("s", $user);
        $update->execute();
        $update->close();
    }
}

// Destroy the session and redirect to login page
session_unset();
session_destroy();
header("Location: adminlogin.php?logout=success");
exit;
?>
