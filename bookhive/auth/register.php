<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

$errors = [];
$username = $email = $securityq = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $securityq = sanitize($_POST['securityq']);
    
    // Validate inputs
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email exists
        $sql = "SELECT email FROM signup WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already registered";
        }
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($securityq)) {
        $errors[] = "Security question is required";
    }
    
    // If no errors, register user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Use 'password' (with single 's') to match your database column
        $sql = "INSERT INTO signup (username, email, password, confirm_password, securityq) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $username, $email, $hashed_password, $hashed_password, $securityq);
        
        if ($stmt->execute()) {
            // Create user profile
            $sql = "INSERT INTO user_profiles (email, joined_date) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $join_date = date('Y-m-d');
            $stmt->bind_param("ss", $email, $join_date);
            $stmt->execute();
            
            // Log user in
            $_SESSION['email'] = $email;
            $_SESSION['username'] = $username;
            
            // Redirect to dashboard
            header("Location: /project/bookhive/user/dashboard.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-form">
        <h2>Create Your BookHub Account</h2>
        
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="securityq">Security Question (What's your favorite book?)</label>
                <input type="text" id="securityq" name="securityq" value="<?php echo htmlspecialchars($securityq); ?>" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-primary">Register</button>
            </div>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="/project/bookhive/auth/login.php">Login here</a></p>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<style>
/* Apply the same CSS as login.php */
body {
    font-family: 'Poppins', sans-serif;
    background-color: #F9F6EF; /* var(--ivory) */
    color: #0A3D54; /* var(--deep-teal) */
}

.auth-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
}

.auth-form {
    background-color: #fff;
    padding: 30px;
    border-radius: 8px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.auth-form h2 {
    color: #006E8C; /* var(--ocean) */
    text-align: center;
    font-size: 24px;
}

.alert-danger {
    background-color: #DB6753; /* var(--book-accent) */
    color: #fff;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-danger ul {
    list-style-type: none;
    margin: 0;
    padding: 0;
}

.alert-danger li {
    margin-bottom: 5px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    font-weight: bold;
    color: #0A3D54; /* var(--deep-teal) */
}

.form-group input {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid #B88B4A; /* var(--bronze) */
    border-radius: 4px;
    font-size: 14px;
    background: #F1ECE2; /* var(--parchment) */
    color: #0A3D54; /* var(--deep-teal) */
}

.btn-primary {
    background: linear-gradient(to right, #006E8C, #0A3D54); /* var(--ocean), var(--deep-teal) */
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 16px;
    width: 100%;
    cursor: pointer;
    transition: background 0.3s, color 0.3s;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.btn-primary:hover {
    background: linear-gradient(to right, #006E8C, #0A3D54);
    color: #fff;
}

.auth-footer {
    text-align: center;
    margin-top: 15px;
}

.auth-footer a {
    color: #006E8C; /* var(--ocean) */
    text-decoration: none;
}

.auth-footer a:hover {
    text-decoration: underline;
}
</style>
