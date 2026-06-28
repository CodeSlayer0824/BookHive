<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$author_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$author_name = '';
$authors = [];
$books = [];

// Get all authors
$sql = "SELECT * FROM authors ORDER BY author_name ASC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $authors[] = $row;
}

// If a specific author is selected, get author name and their books
if ($author_id) {
    // Get author name
    $stmt = $conn->prepare("SELECT author_name FROM authors WHERE author_id = ?");
    $stmt->bind_param("i", $author_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $author = $result->fetch_assoc();
    $author_name = $author['author_name'];

    // Get books by this author
    $sql = "SELECT b.*, c.category_name 
            FROM books b 
            JOIN categories c ON b.category_id = c.category_id 
            WHERE b.author_id = ? 
            ORDER BY b.title ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $author_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Use getCoverUrl for consistent image path logic
        // Ensure getCoverUrl is available
        $row['cover_full_path'] = getCoverUrl($row['cover_url']);
        $books[] = $row;
    }
}
?>

<div class="authors-container">
    <div class="authors-content">
        <h1>Browse Books by Author</h1>
        
        <!-- Author dropdown -->
        <div class="author-dropdown-container">
            <select class="form-control author-dropdown" onchange="window.location.href=this.value">
                <option value="/project/bookhive/authors.php">Select an author...</option>
                <?php foreach ($authors as $author): ?>
                    <option value="/project/bookhive/authors.php?id=<?php echo $author['author_id']; ?>"
                        <?php echo $author_id == $author['author_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($author['author_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if ($author_id): ?>
            <h2 class="selected-author">Books by <?php echo htmlspecialchars($author_name); ?></h2>
            
            <?php if (!empty($books)): ?>
                <div class="book-grid">
                    <?php foreach ($books as $book): ?>
                        <div class="book-card">
                            <a href="/project/bookhive/books/view.php?id=<?php echo $book['book_id']; ?>">
                                <div class="book-cover">
                                    <img src="<?php echo htmlspecialchars($book['cover_full_path']); ?>"
                                         alt="<?php echo htmlspecialchars($book['title']); ?>"
                                         onerror="this.src='/project/bookhive/uploads/covers/default-cover.jpg'">
                                </div>
                                <div class="book-info">
                                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                                    <p class="category"><?php echo htmlspecialchars($book['category_name']); ?></p>
                                    <div class="book-meta">
                                        <span class="rating"><i class="fas fa-star"></i> <?php echo number_format($book['rating'], 1); ?></span>
                                        <span class="pages"><i class="fas fa-book-open"></i> <?php echo $book['pages']; ?> pages</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-books">
                    <p>No books found by this author.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="author-intro">
                <p>Please select an author from the dropdown above to view their books.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
:root {
    --deep-teal: #0A3D54;
    --ocean: #006E8C;
    --light-cyan: #00E6D6;
    --ivory: #F9F6EF;
    --parchment: #F1ECE2;
    --gold-leaf: #D4A017;
    --bronze: #B88B4A;
    --shadow: rgba(0,0,0,0.15);
    --book-accent: #DB6753;
}

.authors-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
    font-family: 'Poppins', sans-serif;
    background-color: var(--ivory);
}

.authors-content {
    width: 100%;
}

.author-dropdown-container {
    margin-bottom: 30px;
}

.author-dropdown {
    width: 100%;
    max-width: 400px;
    padding: 10px 15px;
    font-size: 16px;
    border: 1px solid var(--bronze);
    border-radius: 4px;
    background-color: var(--parchment);
    color: var(--deep-teal);
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 1em;
}

.selected-author {
    margin-top: 20px;
    color: var(--deep-teal);
    border-bottom: 2px solid var(--bronze);
    padding-bottom: 10px;
    font-size: 20px;
}

.book-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.book-card {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
    text-align: center;
    border: 1px solid rgba(0,0,0,0.05);
}

.book-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.book-cover img {
    width: 100%;
    height: 250px;
    object-fit: contain;
    background: #fff;
    border-bottom: 1px solid rgba(0,0,0,0.08);
}

.book-info {
    padding: 12px;
    background: var(--parchment);
}

.book-info h3 {
    margin: 10px 0 5px 0;
    font-size: 16px;
    color: var(--deep-teal);
    font-family: 'Cormorant Garamond', serif;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.book-info .category {
    font-size: 14px;
    color: var(--ocean);
    margin-bottom: 8px;
}

.book-meta {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: var(--bronze);
    margin-top: 8px;
}

.book-meta .rating {
    color: #f39c12;
}

.no-books, .author-intro {
    padding: 20px;
    background: var(--parchment);
    border-radius: 4px;
    text-align: center;
    color: #666;
}

@media (max-width: 768px) {
    .book-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
    .book-cover img {
        height: 200px;
    }
    .book-info h3 {
        font-size: 14px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>