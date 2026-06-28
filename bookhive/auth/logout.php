<?php
require_once '../includes/config.php';

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to home page
header("Location: /project/bookhive/index.php");
exit();
?>