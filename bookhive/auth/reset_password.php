<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

if (!isset($_SESSION['reset_email'])) {
    echo "<p style='color:red;'>Unauthorized access!</p>";
    exit();
}

$email = $_SESSION['reset_email'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE signup SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            echo "<script>
                alert('Password has been successfully updated!');
                window.location.href = 'login.php';
            </script>";
            session_destroy();
            exit();
        } else {
            $errors[] = "Error updating password";
        }
        $stmt->close();
    } else {
        $errors[] = "Password and Confirm Password must be the same";
    }
}
?>

<style>
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
    font-weight: 600;
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

.btn-secondary {
    background-color: #0A3D54; /* var(--deep-teal) */
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 16px;
    text-decoration: none;
    display: inline-block;
    width: 100%;
    text-align: center;
    margin-top: 10px;
    transition: background 0.3s, color 0.3s;
    font-weight: 600;
}

.btn-secondary:hover {
    background-color: #006E8C; /* var(--ocean) */
    color: #fff;
}
</style>

<div class="auth-container">
    <div class="auth-form">
        <h2>Reset Password</h2>
        
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
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-primary">Reset Password</button>
                <a href="login.php" class="btn-secondary">Back to Login</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
