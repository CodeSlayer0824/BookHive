<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cancel'])) {
    header("Location: admin.php");
    exit;
}

$host = "localhost";
$username = "root";
$password = "";
$dbname = "bookhive";

$conn = new mysqli($host, $username, $password, $dbname);

if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['cancel'])) {
    $book_id = $_POST['book_id'];
    $field = $_POST['field'];

    if ($field === "featured_date" || $field === "created_at") {
        echo "<script>alert('This field cannot be updated.');</script>";
    } else {
        if ($field === "cover_url" || $field === "pdf_url") {
            // Handle file upload
            if (isset($_FILES['new_value']) && isset($_FILES['confirm_value'])) {
                $newFile = $_FILES['new_value'];
                $confirmFile = $_FILES['confirm_value'];

                if ($newFile['name'] === $confirmFile['name']) {
                    $target_dir = "uploads/";
                    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                    $target_file = $target_dir . basename($newFile["name"]);

                    if (move_uploaded_file($newFile["tmp_name"], $target_file)) {
                        $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
                        $stmt->bind_param("s", $book_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows === 0) {
                            echo "<script>alert('Incorrect Book ID');</script>";
                        } else {
                            $path = $target_file;
                            $update = $conn->prepare("UPDATE books SET $field = ? WHERE book_id = ?");
                            $update->bind_param("ss", $path, $book_id);
                            if ($update->execute()) {
                                echo "<script>alert('Book updated successfully');</script>";
                            } else {
                                echo "<script>alert('Update failed');</script>";
                            }
                            $update->close();
                        }
                        $stmt->close();
                    } else {
                        echo "<script>alert('File upload failed');</script>";
                    }
                } else {
                    echo "<script>alert('File names do not match');</script>";
                }
            } else {
                echo "<script>alert('Please upload both files');</script>";
            }
        } else {
            // Handle text inputs
            $new_value = $_POST['new_value'];
            $confirm_value = $_POST['confirm_value'];

            if ($new_value === $confirm_value) {
                $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
                $stmt->bind_param("s", $book_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    echo "<script>alert('Incorrect Book ID');</script>";
                } else {
                    // Create a whitelist of allowed fields
                    $allowedFields = ['title', 'cover_url', 'author_id', 'category_id', 'pages', 'description', 'pdf_url', 'author_name', 'category_name'];
                    
                    if (in_array($field, $allowedFields)) {
                        // Handle special cases for author_name and category_name
                        $updateField = $field;
                        $updateValue = $new_value;
                        
                        if ($field === 'author_name') {
                            $updateField = 'author_id';
                        } elseif ($field === 'category_name') {
                            $updateField = 'category_id';
                        }
                        
                        $query = "UPDATE books SET `$updateField` = ? WHERE book_id = ?";
                        $update = $conn->prepare($query);
                        $update->bind_param("ss", $updateValue, $book_id);
                        if ($update->execute()) {
                            echo "<script>alert('Book updated successfully');</script>";
                        } else {
                            echo "<script>alert('Update failed: " . $conn->error . "');</script>";
                        }
                        $update->close();
                    } else {
                        echo "<script>alert('Invalid field selected');</script>";
                    }
                }
                $stmt->close();
            } else {
                echo "<script>alert('Values do not match');</script>";
            }
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Book | BookHive</title>
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
            --danger: #ef233c;       /* Red */
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
            max-width: 600px;
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
        
        input, select, textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--complementary);
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.2);
            background-color: white;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .file-input {
            position: relative;
            overflow: hidden;
            margin-bottom: 1rem;
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
        
        .warning-message {
            background-color: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #ffc107;
            font-size: 0.9rem;
        }
        
        select option:disabled {
            color: #adb5bd;
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
            <h2>Update Book Details</h2>
        </div>
        
        <div class="form-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="warning-message">
                    <strong>Note:</strong> Please double-check the Book ID and ensure the new values match in both fields.
                </div>
                
                <div class="form-group">
                    <label for="book_id">Book ID</label>
                    <input type="text" id="book_id" name="book_id" required placeholder="Enter the book ID to update">
                </div>
                
                <div class="form-group">
                    <label for="field">Field to Update</label>
                    <select name="field" id="field" required>
                        <option value="">-- Select Field --</option>
                        <option value="title">Title</option>
                        <option value="cover_url">Cover Image</option>
                        <option value="author_name">Author Name</option>
                        <option value="category_name">Category Name</option>
                        <option value="pages">Pages</option>
                        <option value="description">Description</option>
                        <option value="featured_date" disabled>Featured Date (Cannot update)</option>
                        <option value="created_at" disabled>Created At (Cannot update)</option>
                        <option value="pdf_url">PDF File</option>
                    </select>
                </div>
                
                <div id="value-container"></div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Update Book</button>
                    <button type="submit" name="cancel" class="btn btn-secondary" formnovalidate>Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const fieldSelect = document.getElementById('field');
        const valueContainer = document.getElementById('value-container');

        function renderInputFields(selected) {
            let html = '';
            if (selected === 'cover_url' || selected === 'pdf_url') {
                html = `
                    <div class="form-group">
                        <label>New File</label>
                        <div class="file-input">
                            <div class="file-input-label">
                                <span>Choose ${selected === 'cover_url' ? 'cover image' : 'PDF file'}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                            </div>
                            <input type="file" name="new_value" id="new_value" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm New File</label>
                        <div class="file-input">
                            <div class="file-input-label">
                                <span>Confirm ${selected === 'cover_url' ? 'cover image' : 'PDF file'}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                            </div>
                            <input type="file" name="confirm_value" id="confirm_value" required>
                        </div>
                    </div>
                `;
            } else if (selected === 'author_name' || selected === 'category_name') {
                // Fetch options from server via AJAX
                html = `
                    <div class="form-group">
                        <label for="new_value">New ${selected.replace('_', ' ')}</label>
                        <select name="new_value" id="new_value" required>
                            <option value="">-- Select ${selected.replace('_', ' ')} --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="confirm_value">Confirm New ${selected.replace('_', ' ')}</label>
                        <select name="confirm_value" id="confirm_value" required>
                            <option value="">-- Select ${selected.replace('_', ' ')} --</option>
                        </select>
                    </div>
                `;
            } else if (selected === 'description') {
                html = `
                    <div class="form-group">
                        <label for="new_value">New Description</label>
                        <textarea name="new_value" id="new_value" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="confirm_value">Confirm New Description</label>
                        <textarea name="confirm_value" id="confirm_value" required></textarea>
                    </div>
                `;
            } else if (selected === 'featured_date' || selected === 'created_at') {
                html = `
                    <div class="warning-message">
                        This field cannot be updated. Please select a different field.
                    </div>
                `;
            } else if (selected) {
                html = `
                    <div class="form-group">
                        <label for="new_value">New Value</label>
                        <input type="text" name="new_value" id="new_value" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_value">Confirm New Value</label>
                        <input type="text" name="confirm_value" id="confirm_value" required>
                    </div>
                `;
            }
            valueContainer.innerHTML = html;
            
            // If dropdown, fetch options
            if (selected === 'author_name' || selected === 'category_name') {
                fetchOptions(selected);
            }
        }

        fieldSelect.addEventListener('change', (e) => {
            renderInputFields(e.target.value);
        });

        // Initial render
        renderInputFields(fieldSelect.value);

        // Add confirmation before submission
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!e.submitter.hasAttribute('formnovalidate')) {
                const bookId = document.getElementById('book_id').value;
                const field = document.getElementById('field').value;
                
                if (!confirm(`Are you sure you want to update ${field} for book ID: ${bookId}?`)) {
                    e.preventDefault();
                }
            }
        });
  

function fetchOptions(type) {
    fetch('get_options.php?type=' + type)
        .then(response => response.json())
        .then(data => {
            const newSelect = document.getElementById('new_value');
            const confirmSelect = document.getElementById('confirm_value');
            
            // Clear existing options
            newSelect.innerHTML = '<option value="">-- Select ' + type.replace('_', ' ') + ' --</option>';
            confirmSelect.innerHTML = '<option value="">-- Select ' + type.replace('_', ' ') + ' --</option>';
            
            // Add new options
            data.forEach(item => {
                const option1 = document.createElement('option');
                option1.value = item.id;
                option1.textContent = item.name;
                newSelect.appendChild(option1);
                
                const option2 = document.createElement('option');
                option2.value = item.id;
                option2.textContent = item.name;
                confirmSelect.appendChild(option2);
            });
        });
}
</script>
</body>
</html>