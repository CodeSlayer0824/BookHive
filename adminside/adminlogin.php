<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$database = "bookhive";

// Establish a database connection
$conn = new mysqli($servername, $username, $password, $database);

// Check if connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Prepare a statement to fetch the stored hashed password
    $stmt = $conn->prepare("SELECT pasword FROM admins WHERE username = ?");
    if (!$stmt) {
        die("Prepare statement failed: " . $conn->error);
    }

    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($db_password);
        $stmt->fetch();

        // Verify password
        if ($db_password === $password || password_verify($password, $db_password)) {
            $_SESSION['username'] = $user;
            $_SESSION['admin_logged_in'] = true; // ✅ Add this line
        
            // Update is_logged_in in database
            $updateStmt = $conn->prepare("UPDATE admins SET is_logged_in = 1 WHERE username = ?");
            if ($updateStmt) {
                $updateStmt->bind_param("s", $user);
                $updateStmt->execute();
                $updateStmt->close();
            }
        
            header("Location: admin.php");
            exit();        
        } else {
            echo "<script>alert('Incorrect password!');</script>";
        }
    } else {
        echo "<script>alert('Username not found!');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>



<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        :root {
            --color-1: #0f1016;
            --text-color: #f0f0f0;
            --accent-color:rgb(48, 92, 203);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            color: var(--text-color);
            background: url('adminloginbackground.png') no-repeat center center/cover;
        }
        @media(max-width: 450px) {
            .links-container {
                flex-direction: column;
                align-items: flex-start;

                position: fixed;
                top: 0;
                right: -100%;
                z-index: 10;
                width: 300px;

                background-color: var(--color-1);
                box-shadow: -5px 0 5px rgba(0, 0, 0, 0.25);
                transition: 0.75s ease-out;
            }

            .open-sidebar-button,
            .close-sidebar-button {
                padding: 20px;
                display: block;
            }

            #sidebar-active:checked~.links-container {
                right: 0;
            }

            #sidebar-active:checked~#overlay {
                height: 100%;
                width: 100%;
                position: fixed;
                top: 0;
                left: 0;
                z-index: 9;
            }
        }

        .banner {
            width: 100%;
            background-color: var(--accent-color);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            font-size: 1.5rem;
            font-weight: bold;
            position: fixed;
            top: 0;
            left: 0;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 1rem;
            margin-right: 15px;
            font-weight: normal;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.81);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 400px;
            margin-top: 80px;
        }

        .form-container h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .form-container label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
            text-align: left;
        }

        .form-container input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            background-color: #f9f9f9;
            font-size: 14px;
            color: #555;
            transition: border-color 0.3s;
        }
        .form-container button:hover {
            background: linear-gradient(to right,rgb(48, 81, 229),rgb(115, 134, 237));
            transform: scale(1.05);
        }
        .form-container input:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 5px rgba(255, 87, 51, 0.5);
        }

        .form-container button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(to right,rgb(115, 134, 237),rgb(48, 81, 229));
            color: white;
            font-size: 18px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="banner">
        <div>BookHive</div>
        <div class="nav-links">
            <a href="../bookhive/index.php">User Home</a>
        </div>
    </div>
    <div class="form-container">
        <h2>Admin Login</h2>
        <form method="POST" action="">
            <label>Username:</label>
            <input type="text" name="username" placeholder="Enter your username" required><br>
            <label>Password:</label>
            <input type="password" name="password" placeholder="Enter your password" required><br>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
