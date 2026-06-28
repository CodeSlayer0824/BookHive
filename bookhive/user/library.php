<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header("Location:/project/bookhive/auth/login.php");
    exit();
}



$email = $_SESSION['email'];
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

// Get user's books with filtering
$books = [];
$sql = "SELECT b.*, ub.status, ub.progress, ub.current_page
        FROM books b 
        JOIN user_books ub ON b.book_id = ub.book_id 
        WHERE ub.email = ?";
        
if ($status_filter !== 'all') {
    $sql .= " AND ub.status = ?";
}

$sql .= " ORDER BY b.title ASC";

$stmt = $conn->prepare($sql);

if ($status_filter !== 'all') {
    $stmt->bind_param("ss", $email, $status_filter);
} else {
    $stmt->bind_param("s", $email);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

// Get counts for each status
$status_counts = [
    'all' => 0,
    'want_to_read' => 0,
    'reading' => 0,
    'completed' => 0,
    'dropped' => 0
];

$sql = "SELECT status, COUNT(*) as count FROM user_books WHERE email = ? GROUP BY status";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
while ($row = $result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
    $total += $row['count'];
}
$status_counts['all'] = $total;
?>
<style>
:root {
    --deep-teal: #0A3D54;
    --ocean: #006E8C;
    /* --sea-green: #00B4CC; */ /* Removed sea green */
    --light-cyan: #00E6D6;
    --ivory: #F9F6EF;
    --parchment: #F1ECE2;
    --gold-leaf: #D4A017;
    --bronze: #B88B4A;
    --shadow: rgba(0,0,0,0.15);
    --book-accent: #DB6753;
}

body {
    background: var(--ivory);
    color: var(--deep-teal);
}

.library-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
    background: var(--parchment);
    border-radius: 12px;
    box-shadow: 0 4px 15px var(--shadow);
}

.library-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.library-header h1 {
    color: var(--deep-teal);
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.2rem;
    margin: 0;
}

.btn-primary {
    background: linear-gradient(to right, var(--ocean), var(--deep-teal));
    color: #fff;
    border: none;
    border-radius: 25px;
    padding: 12px 28px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s, color 0.3s, box-shadow 0.3s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-primary:hover {
    background: linear-gradient(to right, var(--ocean), var(--deep-teal));
    color: #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,0.13);
    text-decoration: none;
    outline: none;
}

.library-filters {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 10px;
}

.status-filters {
    display: flex;
    gap: 10px;
    list-style: none;
    padding: 0;
    margin: 0;
}
.status-filters li {
    border-radius: 25px;
    overflow: hidden;
}
.status-filters li.active,
.status-filters li:hover {
    background: var(--ocean);
}
.status-filters li a {
    display: block;
    padding: 8px 18px;
    color: var(--deep-teal);
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s, color 0.2s;
}
.status-filters li.active a,
.status-filters li:hover a {
    color: #fff;
}
.status-filters span {
    background: var(--book-accent);
    color: #fff;
    border-radius: 10px;
    padding: 2px 8px;
    font-size: 0.85em;
    margin-left: 6px;
}

.search-filter input {
    padding: 8px 14px;
    border-radius: 5px;
    border: 1px solid var(--bronze);
    background: var(--ivory);
    color: var(--deep-teal);
    font-size: 1rem;
    outline: none;
    transition: border 0.2s;
}
.search-filter input:focus {
    border: 1.5px solid var(--ocean);
}

.library-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 24px;
}

.library-book {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    background: var(--ivory); /* changed from var(--light-cyan) to var(--ivory) */
    border-radius: 15px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.07);
    padding: 18px 10px 18px 10px;
    position: relative;
    min-height: 370px;
}

.book-cover {
    width: 100%;
    max-width: 170px;
    margin: 0 auto;
    position: relative;
}

.book-cover img {
    width: 100%;
    height: 240px;
    object-fit: contain;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.09);
    background: #fff;
}

.book-status {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 1.2rem;
    color: var(--book-accent);
    background: #fff;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.book-info {
    margin-top: 12px;
    width: 100%;
}

.book-info h3 {
    font-size: 1.08rem;
    font-weight: 700;
    color: var(--deep-teal);
    margin: 0 0 6px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.book-info h3 a {
    color: var(--deep-teal);
    text-decoration: none;
    transition: color 0.2s;
}
.book-info h3 a:hover {
    color: var(--ocean);
}

.progress-container {
    margin: 8px 0;
}
.progress-bar {
    width: 100%;
    background: var(--parchment);
    border-radius: 6px;
    height: 8px;
    overflow: hidden;
    margin-bottom: 3px;
}
.progress {
    background: linear-gradient(to right, var(--ocean), var(--deep-teal));
    height: 100%;
    border-radius: 6px;
    transition: width 0.3s;
}

.user-rating {
    margin: 6px 0;
    color: var(--book-accent);
    font-size: 1.1rem;
}

.book-actions {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin-top: 10px;
    flex-wrap: wrap;
}

.btn-small {
    display: inline-block;
    padding: 10px 18px;
    background: linear-gradient(to right, var(--ocean), var(--deep-teal));
    color: #fff;
    border-radius: 25px;
    text-align: center;
    font-size: 0.95rem;
    font-weight: 600;
    transition: background 0.3s, color 0.3s, box-shadow 0.3s;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    cursor: pointer;
    min-width: 90px;
    letter-spacing: 0.5px;
    text-decoration: none;
}
.btn-small:hover, .dropdown-toggle:hover {
    background: linear-gradient(to right, var(--ocean), var(--deep-teal));
    color: #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,0.13);
    text-decoration: none;
    outline: none;
}

.dropdown {
    position: relative;
    display: inline-block;
}
.dropdown-toggle {
    background: linear-gradient(to right, var(--ocean), var(--deep-teal));
    color: #fff;
    border: none;
    border-radius: 5px;
    padding: 10px 14px;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.3s;
}
.dropdown-menu {
    display: none;
    position: absolute;
    top: 110%;
    left: 0;
    min-width: 170px;
    background: #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,0.13);
    border-radius: 8px;
    z-index: 10;
    padding: 8px 0;
}
.dropdown:hover .dropdown-menu,
.dropdown:focus-within .dropdown-menu {
    display: block;
}
.dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    color: var(--deep-teal);
    text-decoration: none;
    font-size: 0.97rem;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
}
.dropdown-item:hover {
    background: var(--parchment);
    color: var(--ocean);
}
.dropdown-divider {
    height: 1px;
    background: var(--bronze);
    margin: 6px 0;
}
.text-danger {
    color: #d9534f !important;
}

.empty-library {
    text-align: center;
    color: var(--deep-teal);
    padding: 60px 0;
}
.empty-library i {
    font-size: 3rem;
    color: var(--book-accent);
    margin-bottom: 10px;
}
.empty-library h3 {
    margin: 10px 0 5px 0;
    font-size: 1.3rem;
    color: var(--deep-teal);
}
.empty-library p {
    color: var(--ocean);
    margin-bottom: 18px;
}
.empty-library .btn-primary {
    font-size: 1.05rem;
    padding: 12px 32px;
}
</style>
<div class="library-container">
    <div class="library-header">
        <h1>My Library</h1>
        <div class="library-actions">
            <a href="/project/bookhive/categories.php" class="btn-primary">
                <i class="fas fa-plus"></i> Add Books
            </a>
        </div>
    </div>
    
    <div class="library-filters">
        <ul class="status-filters">
            <li class="<?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                <a href="?status=all">All <span><?php echo $status_counts['all']; ?></span></a>
            </li>
            <li class="<?php echo $status_filter === 'want_to_read' ? 'active' : ''; ?>">
                <a href="?status=want_to_read">Want to Read <span><?php echo $status_counts['want_to_read']; ?></span></a>
            </li>
            <li class="<?php echo $status_filter === 'reading' ? 'active' : ''; ?>">
                <a href="?status=reading">Reading <span><?php echo $status_counts['reading']; ?></span></a>
            </li>
            <li class="<?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
                <a href="?status=completed">Completed <span><?php echo $status_counts['completed']; ?></span></a>
            </li>
            <li class="<?php echo $status_filter === 'dropped' ? 'active' : ''; ?>">
                <a href="?status=dropped">Dropped <span><?php echo $status_counts['dropped']; ?></span></a>
            </li>
        </ul>
        
        <div class="search-filter">
            <input type="text" id="library-search" placeholder="Search your library...">
        </div>
    </div>
    
    <?php if (!empty($books)): ?>
    <div class="library-grid">
        <?php foreach ($books as $book): ?>
        <div class="library-book" data-status="<?php echo $book['status']; ?>">
            <div class="book-cover">
                <a href="/project/bookhive/books/view.php?id=<?php echo $book['book_id']; ?>">
                    <img src="<?php echo getCoverUrl($book['cover_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                </a>
                <div class="book-status <?php echo $book['status']; ?>">
                    <?php 
                    switch ($book['status']) {
                        case 'want_to_read': echo '<i class="fas fa-bookmark"></i>'; break;
                        case 'reading': echo '<i class="fas fa-book-open"></i>'; break;
                        case 'completed': echo '<i class="fas fa-check-circle"></i>'; break;
                        case 'dropped': echo '<i class="fas fa-times-circle"></i>'; break;
                    }
                    ?>
                </div>
            </div>
            <div class="book-info">
                <h3><a href="/project/bookhive/books/view.php?id=<?php echo $book['book_id']; ?>"><?php echo $book['title']; ?></a></h3>
                
                <?php if ($book['status'] === 'reading'): ?>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $book['progress']; ?>%"></div>
                    </div>
                    <span><?php echo $book['progress']; ?>% (Page <?php echo $book['current_page']; ?> of <?php echo $book['pages']; ?>)</span>
                </div>
                <?php endif; ?>
                
                <?php if ($book['rating']): ?>
                <div class="user-rating">
                    <?php
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $book['rating']) {
                            echo '<i class="fas fa-star"></i>';
                        } else {
                            echo '<i class="far fa-star"></i>';
                        }
                    }
                    ?>
                </div>
                <?php endif; ?>
                
                <div class="book-actions">
                    <a href="/project/bookhive/books/read.php?id=<?php echo $book['book_id']; ?>" class="btn-small">
                        <i class="fas fa-book-open"></i> Read
                    </a>
                    <!-- Removed dropdown/options button -->
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-library">
        <i class="fas fa-book-open"></i>
        <h3>Your library is empty</h3>
        <p>Start by adding books you want to read</p>
        <a href="/project/bookhive/categories.php" class="btn-primary">Browse Books</a>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Library search functionality
    const searchInput = document.getElementById('library-search');
    const books = document.querySelectorAll('.library-book');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        books.forEach(book => {
            const title = book.querySelector('h3 a').textContent.toLowerCase();
            if (title.includes(searchTerm)) {
                book.style.display = 'flex';
            } else {
                book.style.display = 'none';
            }
        });
    });
    
    // Update book status
    const statusButtons = document.querySelectorAll('.update-status');
    statusButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const bookId = this.getAttribute('data-book');
            const status = this.getAttribute('data-status');
            
            fetch('../../api/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `book_id=${bookId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });
    });
    
    // Remove book from library
    const removeButtons = document.querySelectorAll('.remove-book');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove this book from your library?')) {
                const bookId = this.getAttribute('data-book');
                
                fetch('../../api/update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `book_id=${bookId}&status=remove`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>