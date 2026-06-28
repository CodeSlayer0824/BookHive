<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

$errors = [];
$email = '';

// Handle forgot password submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $security_answer = sanitize($_POST['security_answer']);

    // Check if email exists in the database
    $stmt = $conn->prepare("SELECT securityq FROM signup WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($correct_answer);
        $stmt->fetch();
        
        if (strtolower($security_answer) == strtolower($correct_answer)) {
            $_SESSION['reset_email'] = $email;
            header("Location: reset_password.php");
            exit();
        } else {
            $errors[] = "Wrong security answer";
        }
    } else {
        $errors[] = "No such email found";
    }
    $stmt->close();
}
?>

<div class="auth-container">
    <div class="auth-form">
        <h2>Forgot Password</h2>
        
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
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="security_answer">What's your favorite book?</label>
                <input type="text" id="security_answer" name="security_answer" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-primary">Submit</button>
                <a href="/project/bookhive/auth/login.php" class="btn-secondary">Back to Login</a>
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
