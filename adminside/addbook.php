<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$database = "bookhive";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all authors for dropdown
$authors = [];
$author_query = $conn->query("SELECT author_name FROM authors ORDER BY author_name");
while ($row = $author_query->fetch_assoc()) {
    $authors[] = $row['author_name'];
}

// Fetch all categories for dropdown
$categories = [];
$category_query = $conn->query("SELECT category_name FROM categories ORDER BY category_name");
while ($row = $category_query->fetch_assoc()) {
    $categories[] = $row['category_name'];
}

$message = '';
$message_type = ''; // 'success' or 'error'

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_id'])) {
    $book_id = $_POST['book_id'];
    $author_name = $_POST['author_name'];
    $title = $_POST['title'];
    $category_name = $_POST['category'];
    $description = $_POST['description'];
    $pages = $_POST['pages'];
    $rating = $_POST['rating'];
    $featured_date = $_POST['featured_date'];
    
    try {
        // Check if book ID or title already exists
        $check = $conn->prepare("SELECT * FROM books WHERE book_id = ? OR title = ?");
        $check->bind_param("ss", $book_id, $title);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('Book ID or Title already exists!');
        }
        
        // Handle Author
        $author_id = null;
        $check_author = $conn->prepare("SELECT author_id FROM authors WHERE author_name = ?");
        $check_author->bind_param("s", $author_name);
        $check_author->execute();
        $result_author = $check_author->get_result();
        
        if ($result_author->num_rows > 0) {
            $author_row = $result_author->fetch_assoc();
            $author_id = $author_row['author_id'];
        } else {
            $insert_author = $conn->prepare("INSERT INTO authors (author_name) VALUES (?)");
            $insert_author->bind_param("s", $author_name);
            $insert_author->execute();
            $author_id = $insert_author->insert_id;
        }
        
        // Handle Category
        $category_id = null;
        $check_category = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ?");
        $check_category->bind_param("s", $category_name);
        $check_category->execute();
        $result_category = $check_category->get_result();
        
        if ($result_category->num_rows > 0) {
            $cat_row = $result_category->fetch_assoc();
            $category_id = $cat_row['category_id'];
        } else {
            $insert_category = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
            $insert_category->bind_param("s", $category_name);
            $insert_category->execute();
            $category_id = $insert_category->insert_id;
        }
        
        // Handle file uploads
        $cover_url = '';
        $pdf_url = '';
        
        if ($_FILES["cover_url"]["error"] == UPLOAD_ERR_OK) {
            $target_dir = "uploads/covers/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $cover_url = $target_dir . basename($_FILES["cover_url"]["name"]);
            move_uploaded_file($_FILES["cover_url"]["tmp_name"], $cover_url);
        }
        
        if ($_FILES["book_pdf"]["error"] == UPLOAD_ERR_OK) {
            $target_dir = "uploads/pdfs/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $pdf_url = $target_dir . basename($_FILES["book_pdf"]["name"]);
            move_uploaded_file($_FILES["book_pdf"]["tmp_name"], $pdf_url);
        }
        
        // Insert Book Data
        $insert_book = $conn->prepare("INSERT INTO books 
            (book_id, title, cover_url, author_id, category_id, description, pages, featured_date, rating, pdf_url, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        $insert_book->bind_param(
            "sssiisssss",
            $book_id, $title, $cover_url, $author_id, $category_id, 
            $description, $pages, $featured_date, $rating, $pdf_url
        );
        
        if ($insert_book->execute()) {
            $message = 'Book added successfully!';
            $message_type = 'success';
        } else {
            throw new Exception('Error adding book: ' . $insert_book->error);
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Book | BookHive</title>
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
            max-width: 800px;
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
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            flex: 1;
            min-width: 250px;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary);
        }
        
        input, textarea, select {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
            max-width: 100%;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--complementary);
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.2);
            background-color: white;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .full-width {
            flex: 0 0 100%;
        }
        
        .file-input {
            position: relative;
            overflow: hidden;
        }
        
        .file-input input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            background-color: #f8f9fa;
            border: 2px dashed #e9ecef;
            border-radius: 8px;
            color: #6c757d;
        }
        
        .file-input-label:hover {
            border-color: var(--complementary);
        }
        
        .file-name {
            font-size: 0.9rem;
            color: var(--dark);
            margin-top: 0.5rem;
            display: none;
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
            text-decoration: none;
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
        
        .btn-accent {
            background-color: var(--complementary);
            color: white;
        }
        
        .btn-accent:hover {
            background-color: #3aa8d6;
        }
        
        @media (max-width: 768px) {
            .form-container {
                margin: 1rem;
                border-radius: 12px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .form-group {
                min-width: 100%;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
        :root {
            --primary: #4361ee;       /* Royal Blue */
            --secondary: #3a0ca3;    /* Dark Blue */
            --accent: #f72585;       /* Pink */
            --light: #f8f9fa;        /* Light Gray */
            --dark: #212529;        /* Dark Gray */
            --complementary: #4cc9f0; /* Sky Blue */
            --success: #4ad66d;      /* Green */
            --warning: #f8961e;      /* Orange */
            --danger: #ef233c;      /* Red */
        }
        
        /* [Previous CSS styles remain the same] */
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success {
            background-color: rgba(74, 214, 109, 0.2);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .alert-error {
            background-color: rgba(239, 35, 60, 0.2);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        
        .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
<div class="form-container">
        <div class="form-header">
            <h2>Add New Book</h2>
        </div>
        
        <div class="form-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <?php if ($message_type == 'success'): ?>
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        <?php else: ?>
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        <?php endif; ?>
                    </svg>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="book_id">Book ID</label>
                        <input type="text" id="book_id" name="book_id" required placeholder="Enter unique book ID">
                    </div>
                    
                    <div class="form-group">
                        <label for="author_name">Author Name</label>
                        <input type="text" id="author_name" name="author_name" required placeholder="Search or select author" list="authors-list">
                        <datalist id="authors-list">
                            <?php foreach ($authors as $author): ?>
                                <option value="<?php echo htmlspecialchars($author); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" required placeholder="Search or select category" list="categories-list">
                        <datalist id="categories-list">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Book Title</label>
                        <input type="text" id="title" name="title" required placeholder="Title of the book">
                    </div>
                    
                    <div class="form-group">
                        <label for="pages">Number of Pages</label>
                        <input type="number" id="pages" name="pages" min="1" required placeholder="Total pages">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" required placeholder="Brief description of the book"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="rating">Rating</label>
                        <select id="rating" name="rating" required>
                            <option value="NA">Not Rated</option>
                            <option value="1">★☆☆☆☆ (1)</option>
                            <option value="2">★★☆☆☆ (2)</option>
                            <option value="3">★★★☆☆ (3)</option>
                            <option value="4">★★★★☆ (4)</option>
                            <option value="5">★★★★★ (5)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Cover Image</label>
                        <div class="file-input">
                            <div class="file-input-label">
                                <span>Choose cover image</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                            </div>
                            <input type="file" name="cover_url" accept="image/*" required>
                            <div class="file-name" id="cover-file-name"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Featured Date</label>
                        <input type="date" name="featured_date" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Upload Book PDF</label>
                        <div class="file-input">
                            <div class="file-input-label">
                                <span>Choose PDF file</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                            </div>
                            <input type="file" name="book_pdf" accept="application/pdf" required>
                            <div class="file-name" id="pdf-file-name"></div>
                        </div>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Add Book</button>
                    <button type="reset" class="btn btn-secondary">Reset Form</button>
                </div>
                
                <div class="button-group" style="margin-top: 1rem;">
                    <a href="admin.php" class="btn btn-accent">Back to Admin Panel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show selected file names
        document.querySelector('input[name="cover_url"]').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file selected';
            const fileNameElement = document.getElementById('cover-file-name');
            fileNameElement.textContent = fileName;
            fileNameElement.style.display = 'block';
        });
        
        document.querySelector('input[name="book_pdf"]').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file selected';
            const fileNameElement = document.getElementById('pdf-file-name');
            fileNameElement.textContent = fileName;
            fileNameElement.style.display = 'block';
        });
        
        // Add animation to form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="spinner"></span> Adding Book...';
            submitBtn.disabled = true;
        });
    </script>

    <script>
        // Enhance dropdown search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const authorInput = document.getElementById('author_name');
            const categoryInput = document.getElementById('category');
            
            // Focus on search when clicking dropdown
            authorInput.addEventListener('focus', function() {
                this.select();
            });
            
            categoryInput.addEventListener('focus', function() {
                this.select();
            });
        });
    </script>
</body>
</html>
