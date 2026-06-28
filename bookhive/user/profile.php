<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header("Location: /project/bookhive/auth/login.php");
    exit();
}

$email = $_SESSION['email'];
$error = '';
$success = '';

// Get current profile data
$profile = [
    'full_name' => '',
    'bio' => '',
    'avatar' => 'default.jpg'
];

$stmt = $conn->prepare("SELECT full_name, bio, avatar FROM user_profiles WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $profile = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If avatar removal is requested, only process removal and skip other updates
    if (isset($_POST['remove_avatar']) && $_POST['remove_avatar'] == '1') {
        $upload_dir = '../uploads/avatars/';
        if ($profile['avatar'] !== 'default.jpg' && !empty($profile['avatar']) && file_exists($upload_dir . $profile['avatar'])) {
            unlink($upload_dir . $profile['avatar']);
        }
        $avatar = 'default.jpg';
        // Only update avatar, keep full_name and bio unchanged
        $stmt = $conn->prepare("UPDATE user_profiles SET avatar = ? WHERE email = ?");
        $stmt->bind_param("ss", $avatar, $email);
        if ($stmt->execute()) {
            $success = "Profile photo removed successfully!";
            $profile['avatar'] = $avatar;
        } else {
            $error = "Error removing profile photo: " . $conn->error;
        }
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        // Handle file upload
        $avatar = $profile['avatar'];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/avatars/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array(strtolower($file_ext), $allowed_ext)) {
                $avatar = uniqid('avatar_', true) . '.' . $file_ext;
                $destination = $upload_dir . $avatar;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                    // Delete old avatar if it's not the default
                    if ($profile['avatar'] !== 'default.jpg' && file_exists($upload_dir . $profile['avatar'])) {
                        unlink($upload_dir . $profile['avatar']);
                    }
                } else {
                    $error = "Failed to upload avatar image.";
                    $avatar = $profile['avatar'];
                }
            } else {
                $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            }
        }
        if (empty($error)) {
            // Check if profile exists
            $stmt = $conn->prepare("SELECT email FROM user_profiles WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                // Update existing profile
                $stmt = $conn->prepare("UPDATE user_profiles SET full_name = ?, bio = ?, avatar = ? WHERE email = ?");
                $stmt->bind_param("ssss", $full_name, $bio, $avatar, $email);
            } else {
                // Insert new profile
                $joined_date = date('Y-m-d');
                $stmt = $conn->prepare("INSERT INTO user_profiles (email, full_name, bio, avatar, joined_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $email, $full_name, $bio, $avatar, $joined_date);
            }
            if ($stmt->execute()) {
                $success = "Profile updated successfully!";
                // Update profile data for display
                $profile['full_name'] = $full_name;
                $profile['bio'] = $bio;
                $profile['avatar'] = $avatar;
            } else {
                $error = "Error updating profile: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BookHive - My Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --deep-teal: #0A3D54;
            --ocean: #006E8C;
            --ivory: #F9F6EF;
            --parchment: #F1ECE2;
            --bronze: #B88B4A;
            --shadow: rgba(0,0,0,0.15);
            --book-accent: #DB6753;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--ivory);
            color: var(--deep-teal);
            margin: 0;
            padding: 0;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: linear-gradient(to right, var(--ocean), var(--deep-teal));
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }

        .back-button:hover {
            background: var(--deep-teal);
            color: var(--ivory);
        }

        .profile-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .profile-form-container {
            background: var(--parchment);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow);
            width: 100%;
            max-width: 500px;
            position: relative;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .avatar-upload {
            position: relative;
            display: inline-block;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--ocean);
            background: #fff;
        }

        .avatar-upload:hover .profile-avatar {
            opacity: 0.7;
        }

        .avatar-upload-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #fff;
            background: var(--deep-teal);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .avatar-upload:hover .avatar-upload-text {
            opacity: 1;
        }

        #avatar-input {
            display: none;
        }

        h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
            color: var(--deep-teal);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--ocean);
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--bronze);
            border-radius: 4px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            background: var(--ivory);
            color: var(--deep-teal);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--ocean), var(--deep-teal));
            color: #fff;
            border: none;
            padding: 12px 20px;
            width: 100%;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: var(--deep-teal);
            color: var(--ivory);
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .avatar-remove-btn {
            display: inline-block;
            margin-top: 10px;
            background: #f44336;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 7px 16px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .avatar-remove-btn:hover {
            background: #c0392b;
        }

        @media (max-width: 576px) {
            .profile-form-container {
                padding: 20px;
            }
            .profile-avatar {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <a href="/project/bookhive/user/dashboard.php" class="back-button">← Back to Dashboard</a>
    
    <div class="profile-container">
        <div class="profile-form-container">
            <div class="profile-header">
                <h1>Edit Profile</h1>
                
                <label class="avatar-upload">
                    <img src="/project/bookhive/uploads/avatars/<?php echo htmlspecialchars($profile['avatar']); ?>" 
                         alt="Profile Avatar" 
                         class="profile-avatar"
                         onerror="this.onerror=null;this.src='/project/bookhive/assets/images/default-avatar.jpg'">
                    <span class="avatar-upload-text">Change Photo</span>
                </label>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="remove_avatar" value="1">
                    <button type="submit" class="avatar-remove-btn" onclick="return confirm('Remove profile photo?')">Remove Photo</button>
                </form>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="profile-form">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($profile['full_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio"><?php echo htmlspecialchars($profile['bio']); ?></textarea>
                </div>
                <!-- Move avatar input inside the form so it is submitted -->
                <input type="file" id="avatar-input" name="avatar" accept="image/*" style="display:none;">
                <button type="submit" class="btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        // Make avatar clickable to trigger file input
        document.querySelector('.avatar-upload').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('avatar-input').click();
        });
        
        // Preview image when selected
        document.getElementById('avatar-input').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.querySelector('.profile-avatar').src = event.target.result;
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html>