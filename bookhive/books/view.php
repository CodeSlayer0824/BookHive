<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location:/project/bookhive/");
    exit();
}

$book_id = intval($_GET['id']);
$user_email = isLoggedIn() ? $_SESSION['email'] : null;

// Get book details with author and category info
$stmt = $conn->prepare("SELECT b.*, a.author_name, c.category_name 
                       FROM books b
                       JOIN authors a ON b.author_id = a.author_id
                       JOIN categories c ON b.category_id = c.category_id
                       WHERE b.book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: /project/bookhive/");
    exit();
}

$book = $result->fetch_assoc();

// Track view
$stmt = $conn->prepare("INSERT INTO book_views (book_id, user_email) VALUES (?, ?)");
$stmt->bind_param("is", $book_id, $user_email);
$stmt->execute();

// Update total views count
$stmt = $conn->prepare("UPDATE books SET views = views + 1 WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();

// Get user's book status if logged in
$user_book = null;
if (isLoggedIn()) {
    $stmt = $conn->prepare("SELECT * FROM user_books WHERE book_id = ? AND email = ?");
    $stmt->bind_param("is", $book_id, $_SESSION['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_book = $result->fetch_assoc();
}

// Handle review submission (rating + comment)
if (isLoggedIn() && isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    try {
        // Check if user already has a review
        $check_stmt = $conn->prepare("SELECT review_id FROM book_reviews WHERE book_id = ? AND email = ?");
        $check_stmt->bind_param("is", $book_id, $_SESSION['email']);
        $check_stmt->execute();
        $exists = $check_stmt->get_result()->num_rows > 0;
        
        if ($exists) {
            // Update existing review
            $stmt = $conn->prepare("UPDATE book_reviews 
                                  SET rating = ?, comment = ?, updated_at = NOW() 
                                  WHERE book_id = ? AND email = ?");
            $stmt->bind_param("isis", $rating, $comment, $book_id, $_SESSION['email']);
        } else {
            // Create new review
            $stmt = $conn->prepare("INSERT INTO book_reviews 
                                  (book_id, email, rating, comment) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isis", $book_id, $_SESSION['email'], $rating, $comment);
        }
        
        $stmt->execute();
        
        // Recalculate average rating
        $avg_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM book_reviews WHERE book_id = ?");
        $avg_stmt->bind_param("i", $book_id);
        $avg_stmt->execute();
        $avg_result = $avg_stmt->get_result();
        $avg_row = $avg_result->fetch_assoc();
        
        // Update book's average rating
        $update_book_stmt = $conn->prepare("UPDATE books SET rating = ? WHERE book_id = ?");
        $update_book_stmt->bind_param("di", $avg_row['avg_rating'], $book_id);
        $update_book_stmt->execute();
        
        // Refresh page to show updated review
        header("Location: view.php?id=$book_id");
        exit();
        
    } catch (Exception $e) {
        $review_error = "Error submitting review: " . $e->getMessage();
    }
}

// Handle status change (want to read/reading)
if (isLoggedIn() && isset($_GET['status'])) {
    $status = $_GET['status'];
    if (in_array($status, ['want_to_read', 'reading', 'completed', 'dropped'])) {
        if ($user_book) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE user_books 
                                  SET status = ?, 
                                  last_read_at = NOW(),
                                  current_page = IF(? = 'reading', 1, current_page)
                                  WHERE book_id = ? AND email = ?");
            $stmt->bind_param("ssis", $status, $status, $book_id, $_SESSION['email']);
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO user_books 
                                  (email, book_id, status, last_read_at, current_page) 
                                  VALUES (?, ?, ?, NOW(), IF(? = 'reading', 1, 0))");
            $stmt->bind_param("siss", $_SESSION['email'], $book_id, $status, $status);
        }
        $stmt->execute();
        
        // Refresh user book data
        $stmt = $conn->prepare("SELECT * FROM user_books WHERE book_id = ? AND email = ?");
        $stmt->bind_param("is", $book_id, $_SESSION['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_book = $result->fetch_assoc();
    }
}

// Get all reviews for this book
$reviews = [];
$stmt = $conn->prepare("SELECT r.*, s.username FROM book_reviews r 
                       JOIN signup s ON r.email = s.email
                       WHERE r.book_id = ? ORDER BY r.created_at DESC");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}

// Check if current user has already reviewed this book
$user_review = null;
if (isLoggedIn()) {
    $stmt = $conn->prepare("SELECT * FROM book_reviews WHERE book_id = ? AND email = ?");
    $stmt->bind_param("is", $book_id, $_SESSION['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_review = $result->fetch_assoc();
}

// Get cover path
$cover_url = $book['cover_url'];
if (strpos($cover_url, 'uploads/covers/') !== 0) {
    $cover_url = 'uploads/covers/' . $cover_url;
}
$cover_url = '/project/bookhive/' . $cover_url;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - BookHive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    :root {
        --deep-teal: #0A3D54;
        --ocean: #006E8C;
        --sea-green: #00B4CC;
        --light-cyan: #00E6D6;
        --ivory: #F9F6EF;
        --parchment: #F1ECE2;
        --bronze: #B88B4A;
        --shadow: rgba(0,0,0,0.15);
        --book-accent: #DB6753;
    }
    body {
        background: var(--ivory);
        color: #333;
    }
    .book-view-container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background: var(--parchment);
        color: var(--deep-teal);
        border-radius: 8px;
        box-shadow: 0 4px 15px var(--shadow);
    }
    .book-header {
        display: flex;
        gap: 30px;
        margin-bottom: 30px;
    }
    .book-cover-large {
        flex: 0 0 300px;
        position: relative;
    }
    .book-cover-large img {
        width: 100%;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 4px 15px var(--shadow);
        background: var(--ivory);
    }
    .book-meta {
        flex: 1;
    }
    .book-info-header h1 {
        margin: 0 0 15px 0;
        font-size: 2em;
        color: var(--deep-teal);
    }
    .book-stats {
        display: flex;
        gap: 20px;
        margin-bottom: 15px;
    }
    .stat {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--ocean);
        font-size: 0.9em;
    }
    .book-details {
        margin: 25px 0;
        padding: 20px 0;
        border-top: 1px solid var(--bronze);
        border-bottom: 1px solid var(--bronze);
    }
    .detail-item {
        display: flex;
        margin-bottom: 15px;
    }
    .detail-item .label {
        flex: 0 0 120px;
        color: var(--ocean);
        font-weight: bold;
    }
    .detail-item .value {
        color: var(--deep-teal);
    }
    .book-content {
        background: var(--ivory);
        padding: 25px;
        border-radius: 8px;
        margin-top: 20px;
    }
    .book-description h3 {
        margin: 0 0 20px 0;
        color: var(--ocean);
        font-size: 1.5em;
    }
    .description-text {
        color: var(--deep-teal);
        line-height: 1.8;
        margin-bottom: 30px;
    }
    .book-actions {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
        align-items: center;
    }
    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 25px;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }
    .action-btn.primary {
        background: linear-gradient(to right, var(--ocean), var(--deep-teal));
        color: white;
    }
    .action-btn.primary:hover {
        background: linear-gradient(to right, var(--sea-green), var(--ocean));
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px var(--shadow);
    }
    .action-btn.secondary {
        background: var(--ocean);
        color: var(--ivory);
    }
    .action-btn.secondary:hover {
        background: var(--sea-green);
        color: var(--deep-teal);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px var(--shadow);
    }
    .reading-progress {
        font-size: 0.9em;
        color: var(--ocean);
        margin-left: 15px;
    }
    .reviews-section {
        margin-top: 40px;
        background: var(--parchment);
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px var(--shadow);
    }
    .review-form {
        margin-bottom: 30px;
        padding: 20px;
        background: var(--ivory);
        border-radius: 6px;
    }
    .rating-input {
        margin-bottom: 15px;
    }
    .stars {
        display: flex;
        gap: 5px;
        margin-top: 5px;
    }
    .stars input {
        display: none;
    }
    .stars label {
        font-size: 24px;
        color: #bbb; /* gray by default */
        cursor: pointer;
        transition: color 0.2s;
    }
    .stars input:checked ~ label,
    .stars label:hover,
    .stars label:hover ~ label {
        color: var(--book-accent); /* #DB6753 */
    }
    .stars input:checked + label {
        color: var(--book-accent);
    }
    .review-rating .fa-star {
        color: #bbb;
    }
    .review-rating .filled {
        color: var(--book-accent);
    }
    .comment-input textarea {
        width: 100%;
        padding: 15px;
        border: 1px solid var(--book-accent);
        border-radius: 6px;
        min-height: 100px;
        margin-top: 5px;
        font-family: inherit;
        resize: vertical;
        background: var(--parchment);
        color: var(--deep-teal);
    }
    .submit-review {
        padding: 10px 20px;
        background: var(--ocean);
        color: var(--ivory);
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.3s;
        margin-top: 15px;
    }
    .submit-review:hover {
        background: var(--sea-green);
        color: var(--deep-teal);
    }
    .reviews-list {
        margin-top: 20px;
    }
    .review {
        padding: 20px;
        border-bottom: 1px solid var(--bronze);
    }
    .review:last-child {
        border-bottom: none;
    }
    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .review-author {
        font-weight: bold;
        color: var(--ocean);
    }
    .review-rating {
        display: flex;
        gap: 2px;
    }
    .review-rating .filled {
        color: var(--book-accent);
    }
    .review-date {
        color: var(--sea-green);
        font-size: 0.9em;
    }
    .review-comment {
        line-height: 1.6;
        margin-top: 10px;
        color: var(--deep-teal);
    }
    .review-actions {
        margin-top: 15px;
        text-align: right;
    }
    .delete-review {
        background: #f44336;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 3px;
        cursor: pointer;
    }
    .no-reviews {
        color: var(--book-accent);
        text-align: center;
        padding: 20px;
    }
    .login-prompt {
        color: var(--ocean);
        margin: 20px 0;
    }
    .login-prompt a {
        color: var(--ocean);
        font-weight: bold;
        text-decoration: underline;
    }
    @media (max-width: 768px) {
        .book-header {
            flex-direction: column;
        }
        .book-cover-large {
            max-width: 250px;
            margin: 0 auto;
        }
        .book-actions {
            flex-direction: column;
            align-items: flex-start;
        }
        .action-btn {
            width: 100%;
            justify-content: center;
        }
        .reading-progress {
            margin-left: 0;
            margin-top: 10px;
        }
        .book-stats {
            flex-wrap: wrap;
        }
    }
    </style>
</head>
<body>
    <div class="book-view-container">
        <div class="book-header">
            <div class="book-cover-large">
                <img src="<?php echo $cover_url; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
            </div>
            
            <div class="book-meta">
                <div class="book-info-header">
                    <h1><?php echo htmlspecialchars($book['title']); ?></h1>
                    <div class="book-stats">
                        <span class="stat">
                            <i class="fas fa-eye"></i>
                            <?php echo number_format($book['views']); ?> views
                        </span>
                        <span class="stat">
                            <i class="fas fa-star"></i>
                            <?php echo number_format($book['rating'], 1); ?> (average)
                        </span>
                        <span class="stat">
                            <i class="fas fa-book"></i>
                            <?php echo $book['pages']; ?> pages
                        </span>
                    </div>
                </div>
                
                <div class="book-details">
                    <div class="detail-item">
                        <span class="label">Author</span>
                        <span class="value"><?php echo htmlspecialchars($book['author_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Category</span>
                        <span class="value"><?php echo htmlspecialchars($book['category_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Added</span>
                        <span class="value"><?php echo date('M d, Y', strtotime($book['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="book-content">
            <div class="book-description">
                <h3>Description</h3>
                <div class="description-text">
                    <?php echo nl2br(htmlspecialchars($book['description'] ?? 'No description available.')); ?>
                </div>
            </div>
            
            <div class="book-actions">
                <?php if (!empty($book['pdf_url'])): ?>
                    <a href="read.php?id=<?php echo $book_id; ?>" class="action-btn primary">
                        <i class="fas fa-book-open"></i>
                        <span>Read Now</span>
                    </a>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php if (!$user_book || $user_book['status'] !== 'want_to_read'): ?>
                            <a href="?id=<?php echo $book_id; ?>&status=want_to_read" class="action-btn secondary">
                                <i class="fas fa-bookmark"></i>
                                <span>Want to Read</span>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($user_book && $user_book['status'] === 'reading'): ?>
                            <span class="reading-progress">
                                Currently reading (page <?php echo $user_book['current_page']; ?> of <?php echo $book['pages']; ?>)
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="reviews-section">
                <h3>Reviews</h3>
                
                <?php if (isLoggedIn()): ?>
                <div class="review-form">
                    <h4><?php echo $user_review ? 'Update Your Review' : 'Add Your Review'; ?></h4>
                    <?php if (isset($review_error)): ?>
                        <div class="error-message"><?php echo htmlspecialchars($review_error); ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="rating-input">
    <label>Your Rating:</label>
    <div class="stars">
        <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" 
                <?php if ($user_review && $user_review['rating'] == $i) echo 'checked'; ?>>
            <label for="star<?php echo $i; ?>" style="cursor: pointer;">
                <i class="fas fa-star"></i>
            </label>
        <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="comment-input">
                            <label for="comment">Your Thoughts:</label>
                            <textarea id="comment" name="comment" placeholder="Share your thoughts about this book..." 
                                required><?php echo $user_review ? htmlspecialchars($user_review['comment']) : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" name="submit_review" class="submit-review">
                            <i class="fas fa-paper-plane"></i>
                            <?php echo $user_review ? 'Update Review' : 'Submit Review'; ?>
                        </button>
                    </form>
                </div>
                <?php else: ?>
                    <div class="login-prompt">
                        <p>Please <a href="/project/bookhive/auth/login.php">login</a> to submit a review.</p>
                    </div>
                <?php endif; ?>
                
                <div class="reviews-list">
                    <?php if (empty($reviews)): ?>
                        <p class="no-reviews">No reviews yet. Be the first to share your thoughts!</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review">
                                <div class="review-header">
                                    <div class="review-author"><?php echo htmlspecialchars($review['username']); ?></div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="review-date"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></div>
                                </div>
                                <div class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></div>
                                
                                <?php if (isLoggedIn() && $review['email'] === $_SESSION['email']): ?>
                                    <div class="review-actions">
                                        <form method="post" class="delete-review-form" style="display:inline;">
                                            <input type="hidden" name="delete_review_id" value="<?php echo $review['review_id']; ?>">
                                            <button type="submit" class="delete-review">Delete</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Star rating interaction
        document.querySelectorAll('.stars input').forEach(star => {
            star.addEventListener('change', function() {
                const rating = this.value;
                document.querySelectorAll('.stars label').forEach((label, index) => {
                    label.style.color = index < rating ? '#FFD700' : '#d4d2cd';
                });
            });
        });

        // Initialize stars for existing review
        <?php if ($user_review): ?>
        document.querySelectorAll('.stars label').forEach((label, index) => {
            label.style.color = index < <?php echo $user_review['rating']; ?> ? '#FFD700' : '#d4d2cd';
        });
        <?php endif; ?>

        // AJAX review delete
        document.querySelectorAll('.delete-review-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!confirm('Are you sure you want to delete this review?')) return;
                const formData = new FormData(form);
                fetch(window.location.pathname + window.location.search, {
                    method: 'POST',
                    body: formData,
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                })
                .then(res => {
                    // Try to parse JSON, fallback to reload if not JSON
                    return res.json().catch(() => null);
                })
                .then(data => {
                    if (data && data.success) {
                        // Remove review from DOM
                        form.closest('.review').remove();
                    } else {
                        window.location.reload();
                    }
                })
                .catch(() => {
                    window.location.reload();
                });
            });
        });

        // Auto-expand textarea as user types
        const commentTextarea = document.querySelector('.comment-input textarea');
        if (commentTextarea) {
            commentTextarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
            // Trigger initial resize
            commentTextarea.dispatchEvent(new Event('input'));
        }
    });
    </script>
    <?php require_once '../includes/footer.php'; ?>
</body>
</html>

<?php
// Handle review deletion (AJAX or POST)
if (isLoggedIn() && isset($_POST['delete_review_id'])) {
    $review_id = intval($_POST['delete_review_id']);

    // Get the review's book_id and email to ensure ownership and correct book
    $stmt = $conn->prepare("SELECT email, book_id FROM book_reviews WHERE review_id = ?");
    $stmt->bind_param("i", $review_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $review = $result->fetch_assoc();

    if ($review && $review['email'] === $_SESSION['email'] && $review['book_id'] == $book_id) {
        // Delete the review
        $stmt = $conn->prepare("DELETE FROM book_reviews WHERE review_id = ?");
        $stmt->bind_param("i", $review_id);
        $stmt->execute();

        // Recalculate average rating
        $avg_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM book_reviews WHERE book_id = ?");
        $avg_stmt->bind_param("i", $book_id);
        $avg_stmt->execute();
        $avg_result = $avg_stmt->get_result();
        $avg_row = $avg_result->fetch_assoc();
        $new_rating = $avg_row && $avg_row['avg_rating'] !== null ? $avg_row['avg_rating'] : 0;

        // Update book's average rating
        $update_book_stmt = $conn->prepare("UPDATE books SET rating = ? WHERE book_id = ?");
        $update_book_stmt->bind_param("di", $new_rating, $book_id);
        $update_book_stmt->execute();

        // If AJAX, return success and exit
        if (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        // Refresh page to show updated reviews
        header("Location: view.php?id=$book_id");
        exit();
    } else {
        // Not allowed or not found
        if (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Not allowed']);
            exit;
        }
        header("Location: view.php?id=$book_id");
        exit();
    }
}