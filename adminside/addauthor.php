<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$database = "bookhive";
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $author_id = trim($_POST['author_id']);
    $author_name = trim($_POST['author_name']);

    // Check if author already exists
    $check = $conn->prepare("SELECT * FROM authors WHERE author_id = ? OR author_name = ?");
    $check->bind_param("ss", $author_id, $author_name);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Author already exists!'); window.history.back();</script>";
        exit;
    }

    // Insert new author
    $insert = $conn->prepare("INSERT INTO authors (author_id, author_name) VALUES (?, ?)");
    $insert->bind_param("ss", $author_id, $author_name);

    if ($insert->execute()) {
        echo "<script>alert('Author added successfully!'); window.location.href='admin.php';</script>";
    } else {
        echo "<script>alert('Error adding author.'); window.history.back();</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Author | BookHive</title>
    <style>
        :root {
            --primary: #4361ee;       /* Royal Blue */
            --secondary: #3a0ca3;    /* Dark Blue */
            --accent: #f72585;       /* Pink */
            --light: #f8f9fa;        /* Light Gray */
            --dark: #212529;        /* Dark Gray */
            --complementary: #4cc9f0; /* Sky Blue */
            --success: #4ad66d;      /* Green */
            --warning: #f8961e;      /* Orange */
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: radial-gradient(circle at 10% 20%, rgba(67, 97, 238, 0.1) 0%, rgba(248, 249, 250, 1) 90%);
        }
        
        .form-container {
            width: 100%;
            max-width: 500px;
            margin: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .form-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .form-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.8rem;
        }
        
        .form-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary);
        }
        
        input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }
        
        input:focus {
            outline: none;
            border-color: var(--complementary);
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.2);
            background-color: white;
        }
        
        .info-message {
            background-color: #e7f5ff;
            color: var(--secondary);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--complementary);
            font-size: 0.9rem;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: var(--light);
            color: var(--dark);
            border: 1px solid #dee2e6;
        }
        
        .btn-secondary:hover {
            background-color: #e9ecef;
        }
        
        @media (max-width: 768px) {
            .form-container {
                margin: 1rem;
                border-radius: 12px;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h2>Add New Author</h2>
        </div>
        
        <div class="form-body">
            <form method="POST">
                <div class="info-message">
                    <strong>Note:</strong> Please ensure the author ID and name are unique.
                </div>
                
                <div class="form-group">
                    <label for="author_id">Author ID</label>
                    <input type="text" id="author_id" name="author_id" required placeholder="Enter unique author ID">
                </div>
                
                <div class="form-group">
                    <label for="author_name">Author Name</label>
                    <input type="text" id="author_name" name="author_name" required placeholder="Enter author's full name">
                </div>
                
                <div class="button-group">
                    <button type="button" onclick="window.location.href='admin.php'" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Author</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add loading state to form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="spinner"></span> Adding...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>