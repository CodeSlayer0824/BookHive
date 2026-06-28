<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$categories = [];
$books = [];

// Get all categories
$sql = "SELECT * FROM categories ORDER BY category_name ASC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Get books based on selected category or all
if ($category_id > 0) {
    $stmt = $conn->prepare("SELECT b.*, a.author_name 
                            FROM books b 
                            JOIN authors a ON b.author_id = a.author_id 
                            WHERE b.category_id = ? 
                            ORDER BY b.title ASC");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }

    $current_category = $conn->query("SELECT category_name FROM categories WHERE category_id = $category_id")->fetch_assoc();
} else {
    // All Genres
    $sql = "SELECT b.*, a.author_name 
            FROM books b 
            JOIN authors a ON b.author_id = a.author_id 
            ORDER BY b.title ASC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}
?>

<div class="categories-container">
    <div class="categories-content">
        <h1>Browse Books by Genres</h1>

        <!-- Category buttons -->
        <div class="category-buttons">
            <a href="/project/bookhive/categories.php" class="category-btn <?php echo ($category_id == 0) ? 'active' : ''; ?>">All Genres</a>
            <?php foreach ($categories as $category): ?>
                <a href="/project/bookhive/categories.php?id=<?php echo $category['category_id']; ?>"
                   class="category-btn <?php echo ($category_id == $category['category_id']) ? 'active' : ''; ?>">
                   <?php echo $category['category_name']; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($category_id > 0): ?>
            <h2 class="selected-category"><?php echo htmlspecialchars($current_category['category_name']); ?></h2>
        <?php endif; ?>

        <?php if (!empty($books)): ?>
            <div class="book-grid">
                <?php foreach ($books as $book): ?>
                    <div class="book-card">
                        <a href="/project/bookhive/books/view.php?id=<?php echo $book['book_id']; ?>">
                            <div class="book-cover">
                                <img src="<?php echo getCoverUrl($book['cover_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                     onerror="this.src='/project/bookhive/uploads/covers/default-cover.jpg'">
                            </div>
                            <div class="book-info">
                                <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p class="author"><?php echo htmlspecialchars($book['author_name']); ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-books">
                <p>No books found in this category.</p>
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

.categories-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
    font-family: 'Poppins', sans-serif;
    background-color: var(--ivory);
}

h1 {
    font-size: 28px;
    color: var(--deep-teal);
    margin-bottom: 25px;
}

.category-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 30px;
}

.category-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 25px;
    background: linear-gradient(to right, var(--ocean), var(--deep-teal));
    color: #fff;
    font-size: 14px;
    text-decoration: none;
    transition: 0.3s ease;
}

.category-btn:hover {
    background: var(--ocean);
}

.category-btn.active {
    background: var(--deep-teal);
    pointer-events: none;
}

.selected-category {
    font-size: 20px;
    margin-bottom: 20px;
    color: var(--deep-teal);
    border-bottom: 2px solid var(--bronze);
    padding-bottom: 5px;
}

.book-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 25px;
}

.book-card {
    background-color: #fff;
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
    margin: 10px 0 5px;
    font-size: 16px;
    color: var(--deep-teal);
    font-family: 'Cormorant Garamond', serif;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.book-info .author {
    font-size: 14px;
    color: var(--ocean);
}

.no-books {
    text-align: center;
    padding: 30px;
    background: #fff;
    border-radius: 10px;
    color: #999;
    font-size: 16px;
}
</style>

<?php require_once 'includes/footer.php'; ?>
