<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$books = [];
$total_results = 0;

if (!empty($query)) {
    // Search by book title, author name, or category name
    $sql = "SELECT SQL_CALC_FOUND_ROWS b.*, a.author_name, c.category_name 
            FROM books b 
            JOIN authors a ON b.author_id = a.author_id 
            JOIN categories c ON b.category_id = c.category_id 
            WHERE b.title LIKE ? OR a.author_name LIKE ? OR c.category_name LIKE ?
            ORDER BY b.title ASC 
            LIMIT ? OFFSET ?";
    
    $search_term = "%{$query}%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $search_term, $search_term, $search_term, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
    
    $total_results = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results - BookHive</title>
    <link href="https://fonts.googleapis.com/css2?family=Ranade:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #F5F2F4;
            --primary: #917BA5;
            --secondary: #ED7479;
            --accent: #A38AAD;
            --dark: #4C3351;
            --light: #fff;
        }

        body {
            margin: 0;
            font-family: 'Ranade', sans-serif;
            background: var(--bg);
            color: var(--dark);
        }

        .search-container {
            padding: 40px;
        }

        h1 {
            font-size: 2.4rem;
            margin-bottom: 10px;
        }

        .search-meta {
            font-size: 1rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 20px;
        }

        .book-card {
            background: var(--light);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .book-card:hover {
            transform: translateY(-5px);
        }

        .book-cover img {
            width: 100%;
            height: 240px;
            object-fit: cover;
        }

        .book-info {
            padding: 10px 12px;
        }

        .book-info h3 {
            font-size: 1rem;
            margin: 0;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .book-info .author,
        .book-info .category {
            font-size: 0.85rem;
            color: var(--accent);
        }

        .pagination {
            margin-top: 30px;
            text-align: center;
        }

        .page-link {
            display: inline-block;
            padding: 8px 14px;
            margin: 0 5px;
            background: var(--primary);
            color: white;
            border-radius: 6px;
            text-decoration: none;
        }

        .page-link:hover {
            background: var(--dark);
        }

        .page-link.active {
            background: var(--dark);
        }

        .no-results, .no-query {
            margin-top: 40px;
            text-align: center;
        }

        .btn-primary {
            padding: 10px 20px;
            background: var(--primary);
            color: var(--light);
            text-decoration: none;
            border-radius: 6px;
            margin-top: 10px;
            display: inline-block;
        }

        .btn-primary:hover {
            background: var(--dark);
        }
    </style>
</head>
<body>

<div class="search-container">
    <h1>Search Results for "<?php echo htmlspecialchars($query); ?>"</h1>

    <?php if (!empty($query)): ?>
        <div class="search-meta">
            <p><?php echo $total_results; ?> result<?php echo ($total_results != 1) ? 's' : ''; ?> found</p>
        </div>

        <?php if (!empty($books)): ?>
            <div class="book-grid">
                <?php foreach ($books as $book): ?>
                    <div class="book-card">
                        <a href="/project/bookhive/books/view.php?id=<?php echo $book['book_id']; ?>">
                            <div class="book-cover">
                                <img src="<?= getCoverUrl($book['cover_url']); ?>" alt="<?= htmlspecialchars($book['title']); ?>">
                            </div>
                            <div class="book-info">
                                <h3><?= htmlspecialchars($book['title']); ?></h3>
                                <p class="author"><?= htmlspecialchars($book['author_name']); ?></p>
                                <p class="category"><?= htmlspecialchars($book['category_name']); ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_results > $limit): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?q=<?= urlencode($query); ?>&page=<?= $page - 1; ?>" class="page-link">Prev</a>
                    <?php endif; ?>
                    <?php
                        $total_pages = ceil($total_results / $limit);
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        for ($i = $start; $i <= $end; $i++):
                    ?>
                        <a href="?q=<?= urlencode($query); ?>&page=<?= $i; ?>" class="page-link <?= ($i == $page) ? 'active' : ''; ?>"><?= $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?q=<?= urlencode($query); ?>&page=<?= $page + 1; ?>" class="page-link">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-results">
                <p>No books found matching your search.</p>
                <a href="/project/bookhive/index.php" class="btn-primary">Browse All Books</a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-query">
            <p>Please enter a search term.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
