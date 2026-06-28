<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header("Location: /project/bookhive/auth/login.php");
    exit();
}

$email = $_SESSION['email'];
$user = getUserData($email);

// User profile details
$full_name = $user['username'];
$bio = '';
$stmt = $conn->prepare("SELECT full_name, bio FROM user_profiles WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $profile = $res->fetch_assoc();
    if (!empty($profile['full_name'])) $full_name = $profile['full_name'];
    $bio = $profile['bio'];
}

// Stats
$stats = ['want_to_read' => 0, 'reading' => 0, 'completed' => 0, 'dropped' => 0];
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM user_books WHERE email = ? GROUP BY status");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
}

// Recently Added Books (Want to Read or Currently Reading)
$stmt = $conn->prepare("
    SELECT DISTINCT b.*, a.author_name as author_name 
    FROM books b 
    JOIN user_books ub ON b.book_id = ub.book_id 
    JOIN authors a ON b.author_id = a.author_id 
    WHERE ub.email = ? AND (ub.status = 'want_to_read' OR ub.status = 'reading')
    ORDER BY ub.started_at DESC
    LIMIT 5
");
$stmt->bind_param("s", $email);
$stmt->execute();
$recent_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Reading History
$stmt = $conn->prepare("
    SELECT b.*, a.author_name as author_name, ub.progress, ub.current_page, ub.last_read_at, ub.status 
    FROM user_books ub 
    JOIN books b ON ub.book_id = b.book_id 
    JOIN authors a ON b.author_id = a.author_id
    WHERE ub.email = ? AND (ub.status = 'reading' OR ub.status = 'completed') 
    ORDER BY ub.last_read_at DESC
    LIMIT 5
");
$stmt->bind_param("s", $email);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Function to get cover URL (similar to your index.php)

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BookHive - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Ranade:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        <?php include '../assets/styles/index.css'; ?>
        
        :root {
            --bg: #F5F2F4;
            --primary: #917BA5;
            --secondary: #ED7479;
            --accent: #A38AAD;
            --dark: #4C3351;
            --light: #fff;
            --ocean: #0077B6;
            --deep-teal: #005F73;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            background: var(--light);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--accent);
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-info h1 {
            margin: 0;
            color: var(--dark);
            font-size: 1.8rem;
        }

        .user-info p {
            margin: 5px 0 0;
            color: var(--dark);
            opacity: 0.8;
        }

        .user-bio {
            margin-top: 10px !important;
            font-style: italic;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--light);
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card a {
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--dark);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(155, 123, 165, 0.1);
            border-radius: 8px;
        }

        .stat-info h3 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--primary);
        }

        .stat-info p {
            margin: 3px 0 0;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .dashboard-sections {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .dashboard-section {
            background: var(--light);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .dashboard-section h2 {
            margin-top: 0;
            color: var(--dark);
            font-size: 1.5rem;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .book-row {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding: 10px 0;
            scrollbar-width: none;
        }

        .book-row::-webkit-scrollbar {
            display: none;
        }

        .book-card {
            flex: 0 0 auto;
            width: 180px;
            background: var(--light);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
        }

        .book-card:hover {
            transform: translateY(-6px);
        }

        .book-card img {
            width: 100%;
            height: 260px;
            object-fit: cover;
        }

        .book-title {
            padding: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--dark);
        }

        .book-author {
            padding: 0 10px 10px;
            font-size: 0.85rem;
            color: var(--primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .book-meta {
            padding: 0 10px 10px;
            font-size: 0.8rem;
            color: var(--dark);
            opacity: 0.7;
        }

        .btn-small {
            display: block;
            margin: 10px auto 10px auto;
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
            width: 95%;
            max-width: 200px;
            position: relative;
            z-index: 2;
            letter-spacing: 0.5px;
        }

        .btn-small:hover {
            background: linear-gradient(to right, #00B4CC, var(--ocean));
            color: #fff;
            box-shadow: 0 4px 16px rgba(0,0,0,0.13);
            text-decoration: none;
            outline: none;
        }

        .no-books {
            text-align: center;
            color: var(--dark);
            opacity: 0.7;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .book-card {
                width: 140px;
            }
            
            .book-card img {
                height: 200px;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="user-profile">
            <a href="/project/bookhive/user/profile.php" class="avatar">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="/project/bookhive/uploads/avatars/<?php echo $user['avatar']; ?>" alt="Profile">
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="#BD8070" viewBox="0 0 24 24" width="64" height="64"><path d="M12 12c2.7 0 4.9-2.2 4.9-4.9S14.7 2.2 12 2.2 7.1 4.4 7.1 7.1 9.3 12 12 12zm0 2.1c-3.2 0-9.6 1.6-9.6 4.9v2.9h19.2v-2.9c0-3.3-6.4-4.9-9.6-4.9z"/></svg>
                <?php endif; ?>
            </a>
            <div class="user-info">
                <h1>Welcome, <?php echo htmlspecialchars($full_name); ?></h1>
                <p>Member since <?php echo date('F Y', strtotime($user['joined_date'])); ?></p>
                <?php if (!empty($bio)): ?><p class="user-bio"><?php echo htmlspecialchars($bio); ?></p><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="dashboard-stats">
        <?php
        $icons = [
            'want_to_read' => '<svg fill="#DB6753" viewBox="0 0 24 24" width="28" height="28"><path d="M6 4v16l6-4 6 4V4z"/></svg>',
            'reading' => '<svg fill="#9EA593" viewBox="0 0 24 24" width="28" height="28"><path d="M3 4v16h18V4zm2 2h14v12H5z"/></svg>',
            'completed' => '<svg fill="#BD8070" viewBox="0 0 24 24" width="28" height="28"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>',
            'dropped' => '<svg fill="#444037" viewBox="0 0 24 24" width="28" height="28"><path d="M18.3 5.71L12 12l6.3 6.29-1.41 1.42L12 13.41l-6.29 6.3-1.42-1.42L10.59 12 4.29 5.71 5.71 4.29 12 10.59l6.29-6.3z"/></svg>'
        ];
        $labels = [
            'want_to_read' => 'Want to Read',
            'reading' => 'Currently Reading',
            'completed' => 'Completed',
            'dropped' => 'Dropped'
        ];
        foreach ($stats as $key => $count): ?>
            <div class="stat-card">
                <a href="/project/bookhive/user/library.php?status=<?php echo $key; ?>">
                    <div class="stat-icon"><?php echo $icons[$key]; ?></div>
                    <div class="stat-info">
                        <h3><?php echo $count; ?></h3>
                        <p><?php echo $labels[$key]; ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="dashboard-sections">
        <!-- Recently Added Section -->
        <div class="dashboard-section">
            <h2>Recently Added</h2>
            <div class="book-row">
                <?php if (!empty($recent_books)): ?>
                    <?php foreach ($recent_books as $book): ?>
                        <div class="book-card">
                            <a href="/project/bookhive/books/view.php?id=<?php echo $book['book_id']; ?>">
                                <img src="<?php echo getCoverUrl($book['cover_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>"
                                     onerror="this.onerror=null;this.src='/project/bookhive/assets/images/default-book-cover.jpg'">
                                <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                <div class="book-author"><?php echo htmlspecialchars($book['author_name']); ?></div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-books">No recently added books found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reading History Section -->
        <div class="dashboard-section">
            <h2>Reading History</h2>
            <div class="book-row">
                <?php if (!empty($history)): ?>
                    <?php foreach ($history as $book): ?>
                        <div class="book-card">
                            <a href="/project/bookhive/books/view.php?id=<?php echo $book['book_id']; ?>">
                                <img src="<?php echo getCoverUrl($book['cover_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>"
                                     onerror="this.onerror=null;this.src='/project/bookhive/assets/images/default-book-cover.jpg'">
                                <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                <div class="book-author"><?php echo htmlspecialchars($book['author_name']); ?></div>
                                <div class="book-meta">
                                    <?php echo isset($book['progress']) ? $book['progress'] . '% read' : 'Not started'; ?>
                                    <?php echo isset($book['current_page']) ? '| Page ' . $book['current_page'] : ''; ?>
                                </div>
                                <a class="btn-small" href="/project/bookhive/books/read.php?id=<?php echo $book['book_id']; ?>">
                                    <?php echo ($book['status'] === 'reading') ? 'Continue' : 'Review'; ?>
                                </a>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-books">No reading history found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<script>
    // Add horizontal scrolling functionality similar to index.php
    document.addEventListener('DOMContentLoaded', function() {
        const bookRows = document.querySelectorAll('.book-row');
        
        bookRows.forEach(row => {
            let isDown = false;
            let startX;
            let scrollLeft;
            
            row.addEventListener('mousedown', (e) => {
                isDown = true;
                startX = e.pageX - row.offsetLeft;
                scrollLeft = row.scrollLeft;
            });
            
            row.addEventListener('mouseleave', () => {
                isDown = false;
            });
            
            row.addEventListener('mouseup', () => {
                isDown = false;
            });
            
            row.addEventListener('mousemove', (e) => {
                if(!isDown) return;
                e.preventDefault();
                const x = e.pageX - row.offsetLeft;
                const walk = (x - startX) * 2;
                row.scrollLeft = scrollLeft - walk;
            });
        });
    });
</script>
</body>
</html>