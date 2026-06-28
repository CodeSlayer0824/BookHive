<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: /project/bookhive/user/dashboard.php");
    exit();
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    }

    // If no errors, attempt login
    if (empty($errors)) {
        $sql = "SELECT * FROM signup WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    // Valid hashed password
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['username'] = $user['username'];

                    $update = $conn->prepare("UPDATE signup SET last_active = NOW() WHERE email = ?");
                    $update->bind_param("s", $email);
                    $update->execute();

                    header("Location: /project/bookhive/user/dashboard.php");
                    exit();
                } elseif ($user['password'] === $password) {
                    // Handle plaintext password (legacy)
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_sql = "UPDATE signup SET password = ? WHERE email = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("ss", $hashed_password, $email);
                    $update_stmt->execute();

                    $_SESSION['email'] = $user['email'];
                    $_SESSION['username'] = $user['username'];
                    header("Location: /project/bookhive/user/dashboard.php");
                    exit();
                } else {
                    $errors[] = "Invalid email or password";
                }
            } else {
                $errors[] = "Invalid email or password";
            }
        }
    }
}
?>

<?php require_once '../includes/header.php'; ?>

<div class="auth-container">
    <div class="auth-form">
        <h2>Login to BookHive</h2>

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
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-primary">Login</button>
            </div>

            <div class="auth-footer">
                <p>Don't have an account? <a href="/project/bookhive/auth/register.php">Register here</a></p>
                <p><a href="/project/bookhive/auth/forgot_password.php">Forgot password?</a></p>
            </div>
        </form>
    </div>
</div>

<style>
    /* Base Color Palette */
    :root {
        --primary-bg: #F7F4F4;
        --secondary-bg: #9EA593;
        --accent-bg: #DB6753;
        --highlight-bg: #BD8070;
        --text-color: #444037;
        --button-bg: #DB6753;
        --button-text: white;
    }

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

    h2 {
        color: #006E8C; /* var(--ocean) */
        text-align: center;
        font-size: 24px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        font-size: 14px;
        font-weight: 600;
        color: #0A3D54; /* var(--deep-teal) */
    }

    input[type="email"],
    input[type="password"] {
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

    .alert {
        background-color: #DB6753; /* var(--book-accent) */
        color: #fff;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .auth-footer {
        text-align: center;
        font-size: 14px;
        margin-top: 20px;
    }

    .auth-footer a {
        color: #006E8C; /* var(--ocean) */
        text-decoration: none;
    }

    .auth-footer a:hover {
        text-decoration: underline;
    }
</style>

<?php require_once '../includes/footer.php'; ?>
