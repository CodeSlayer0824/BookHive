<?php
session_start();
session_destroy();
header("Location: adminlogin.php"); // or redirect to homepage
exit;
