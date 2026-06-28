<?php
require_once '../includes/config.php';

// Check if headers can still be sent
if (headers_sent()) {
    die("Headers already sent, cannot redirect");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /project/bookhive/");
    exit();
}

$book_id = intval($_GET['id']);
$user_email = isLoggedIn() ? $_SESSION['email'] : null;

// Redirect to login after 1 minute if not logged in
if (!isLoggedIn()) {
    header("Refresh: 60; url=/project/bookhive/auth/login.php");
}

// Track book view
if (isLoggedIn()) {
    $stmt = $conn->prepare("INSERT INTO book_views (book_id, user_email) VALUES (?, ?)");
    $stmt->bind_param("is", $book_id, $user_email);
    $stmt->execute();
}

// Get book details
$stmt = $conn->prepare("SELECT b.*, a.author_name, 
                       ub.status, ub.progress as read_percentage,
                       ub.current_page, ub.started_at, ub.completed_at,
                       FLOOR(b.pages * ub.progress / 100) as pages_read
                       FROM books b
                       JOIN authors a ON b.author_id = a.author_id
                       LEFT JOIN user_books ub ON b.book_id = ub.book_id AND ub.email = ?
                       WHERE b.book_id = ?");
$stmt->bind_param("si", $user_email, $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: /project/bookhive/");
    exit();
}

$book = $result->fetch_assoc();
$pdf_url = '/project/bookhive/uploads/books/' . basename($book['pdf_url']);
$pdf_path = $_SERVER['DOCUMENT_ROOT'] . $pdf_url;

// Initialize default values
$book['current_page'] = $book['current_page'] ?? 1;
$book['read_percentage'] = $book['read_percentage'] ?? 0;
$book['pages_read'] = $book['pages_read'] ?? 0;
$book['status'] = $book['status'] ?? 'want_to_read';

// Update reading progress if page changed
if (isLoggedIn() && isset($_POST['current_page'])) {
    $current_page = intval($_POST['current_page']);
    $progress = min(100, round(($current_page / $book['pages']) * 100));
    
    // Determine the new status
    $new_status = $book['status'];
    $now = date('Y-m-d H:i:s');
    
    if ($progress >= 100) {
        $new_status = 'completed';
    } elseif ($book['status'] === 'want_to_read') {
        $new_status = 'reading';
    }
    
    // Prepare the update query based on status
    if ($new_status === 'completed') {
        $stmt = $conn->prepare("INSERT INTO user_books 
                              (email, book_id, status, progress, current_page, started_at, completed_at, last_read_at)
                              VALUES (?, ?, ?, ?, ?, IFNULL(?, NOW()), NOW(), NOW())
                              ON DUPLICATE KEY UPDATE 
                              status = VALUES(status),
                              progress = VALUES(progress),
                              current_page = VALUES(current_page),
                              started_at = IFNULL(started_at, VALUES(started_at)),
                              completed_at = VALUES(completed_at),
                              last_read_at = VALUES(last_read_at)");
        $stmt->bind_param("sssiis", $user_email, $book_id, $new_status, $progress, $current_page, $book['started_at']);
    } else {
        $stmt = $conn->prepare("INSERT INTO user_books 
                              (email, book_id, status, progress, current_page, started_at, last_read_at)
                              VALUES (?, ?, ?, ?, ?, IFNULL(?, NOW()), NOW())
                              ON DUPLICATE KEY UPDATE 
                              status = VALUES(status),
                              progress = VALUES(progress),
                              current_page = VALUES(current_page),
                              started_at = IFNULL(started_at, VALUES(started_at)),
                              last_read_at = VALUES(last_read_at)");
        $stmt->bind_param("sssiis", $user_email, $book_id, $new_status, $progress, $current_page, $book['started_at']);
    }
    
    $stmt->execute();
    
    // Update reading session
    $stmt = $conn->prepare("INSERT INTO reading_sessions (user_book_id, start_time, pages_read) 
                          VALUES ((SELECT user_book_id FROM user_books WHERE email = ? AND book_id = ?), NOW(), 1)
                          ON DUPLICATE KEY UPDATE end_time = NOW(), pages_read = pages_read + 1");
    $stmt->bind_param("si", $user_email, $book_id);
    $stmt->execute();
    
    // Refresh book data after update
    $stmt = $conn->prepare("SELECT b.*, a.author_name, 
                           ub.status, ub.progress as read_percentage,
                           ub.current_page, ub.started_at, ub.completed_at,
                           FLOOR(b.pages * ub.progress / 100) as pages_read
                           FROM books b
                           JOIN authors a ON b.author_id = a.author_id
                           LEFT JOIN user_books ub ON b.book_id = ub.book_id AND ub.email = ?
                           WHERE b.book_id = ?");
    $stmt->bind_param("si", $user_email, $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading: <?php echo htmlspecialchars($book['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <style>
    :root {
        --deep-teal: #0A3D54;
        --ocean: #006E8C;
        --light-cyan: #00E6D6;
        --ivory: #F9F6EF;
        --parchment: #F1ECE2;
        --bronze: #B88B4A;
        --shadow: rgba(0,0,0,0.15);
        --book-accent: #DB6753;
        --primary: var(--ocean);
        --secondary: var(--book-accent);
        --accent: var(--book-accent);
        --dark: var(--deep-teal);
        --light: #fff;
        --reader-bg: var(--ivory);
        --sidebar-bg: var(--parchment);
        --border-color: var(--bronze);
        --progress-color: var(--book-accent);
        --highlight-color: var(--book-accent);
        --toc-active-bg: rgba(0,110,140,0.08);
        --error-color: var(--book-accent);
    }

    body.dark-mode {
        --bg: #1E1E1E;
        --reader-bg: #121212;
        --sidebar-bg: #1E1E1E;
        --dark: #E0E0E0;
        --border-color: #333;
        --progress-color: #DB6753;
        --toc-active-bg: rgba(219,103,83,0.13);
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'Ranade', sans-serif;
        color: var(--dark);
        background-color: var(--reader-bg);
        overflow-x: hidden;
    }

    body.dark-mode .pdf-page {
        background-color: #1E1E1E;
        filter: invert(90%) hue-rotate(180deg);
    }

    body.dark-mode .sidebar {
        background-color: var(--sidebar-bg);
        color: var(--dark);
    }

    body.dark-mode .book-title,
    body.dark-mode .sidebar-title,
    body.dark-mode .toc-title {
        color: var(--dark);
    }

    body.dark-mode .toc-link {
        color: var(--dark);
    }

    .reader-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: linear-gradient(120deg, rgba(10,61,84,0.92) 60%, rgba(0,110,140,0.92) 100%);
        color: white;
        padding: 8px 16px; /* smaller padding */
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 1000;
        height: 40px; /* smaller height */
    }

    .reader-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 50%;
    }

    .reader-controls {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .reader-btn {
        background: transparent;
        border: none;
        color: white;
        font-size: 1rem;
        cursor: pointer;
        padding: 5px 10px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .reader-btn:hover {
        background: rgba(255,255,255,0.2);
    }

    .reader-container {
        height: 100vh;
        display: flex;
        flex-direction: column;
        background-color: var(--reader-bg);
    }

    .reader-content-container {
        margin-top: 50px; /* 40px banner + 10px gap */
        display: flex;
        flex: 1;
        height: calc(100vh - 50px);
        overflow: hidden;
    }

    .fullscreen .reader-content-container {
        margin-top: 10px; /* 0px banner + 10px gap in fullscreen */
        height: calc(100vh - 10px);
    }

    .sidebar {
        width: 300px;
        background: var(--sidebar-bg);
        border-right: 1px solid var(--border-color);
        overflow-y: auto;
        padding: 15px;
        box-sizing: border-box;
        transition: all 0.3s ease;
        scrollbar-width: thin;
        scrollbar-color: var(--primary) var(--sidebar-bg);
    }

    .sidebar.collapsed {
        margin-left: -300px;
    }

    .sidebar::-webkit-scrollbar {
        width: 6px;
    }
    .sidebar::-webkit-scrollbar-track {
        background: var(--sidebar-bg);
    }
    .sidebar::-webkit-scrollbar-thumb {
        background-color: var(--primary);
        border-radius: 3px;
    }

    .sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border-color);
    }

    .sidebar-title {
        font-size: 1.1rem;
        margin: 0;
        font-weight: 600;
        color: var(--dark);
    }

    .sidebar-close-btn {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: var(--dark);
        transition: transform 0.2s ease;
    }

    .sidebar-close-btn:hover {
        transform: rotate(90deg);
        color: var(--secondary);
    }

    .book-info {
        margin-bottom: 20px;
    }

    .book-cover {
        width: 100%;
        height: auto;
        max-height: 200px;
        object-fit: contain;
        margin-bottom: 10px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .book-title {
        font-size: 1.1rem;
        margin: 5px 0;
        font-weight: 600;
        color: var(--dark);
    }

    .book-author {
        font-size: 0.9rem;
        color: var(--primary);
        margin: 5px 0 15px;
    }

    .progress-container {
        margin: 20px 0;
    }

    .progress-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 0.9rem;
        color: var(--dark);
    }

    .progress-bar {
        height: 8px;
        background-color: var(--border-color);
        border-radius: 4px;
        overflow: hidden;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
    }

    .progress-fill {
        height: 100%;
        background-color: var(--progress-color);
        width: <?php echo $book['read_percentage']; ?>%;
        transition: width 0.3s ease;
    }

    .progress-details {
        font-size: 0.8rem;
        margin-top: 5px;
        text-align: right;
        color: var(--primary);
    }

    .toc-container {
        margin-top: 20px;
    }

    .toc-title {
        font-size: 1rem;
        margin: 0 0 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid var(--border-color);
        font-weight: 600;
        color: var(--dark);
    }

    .toc-list {
        list-style: none;
        padding: 0;
        margin: 0;
        max-height: 40vh;
        overflow-y: auto;
    }

    .toc-item {
        position: relative;
        margin-bottom: 3px;
        line-height: 1.4;
    }

    .toc-link {
        display: block;
        padding: 8px 10px 8px 28px;
        color: var(--dark);
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.2s ease;
        font-size: 0.9rem;
    }

    .toc-link:hover {
        background-color: var(--toc-active-bg);
        color: var(--primary);
    }

    .toc-link.active {
        background-color: var(--toc-active-bg);
        color: var(--primary);
        font-weight: 600;
    }

    .toc-toggle {
        position: absolute;
        left: 6px;
        top: 8px;
        width: 16px;
        height: 16px;
        cursor: pointer;
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .toc-toggle i {
        font-size: 10px;
        transition: transform 0.2s ease;
    }

    .toc-item.expanded > .toc-toggle i {
        transform: rotate(90deg);
    }

    .toc-sublist {
        padding-left: 15px;
        display: none;
    }

    .toc-item.expanded > .toc-sublist {
        display: block;
    }

    .toc-loading,
    .toc-empty,
    .toc-error {
        display: block;
        padding: 8px;
        color: var(--primary);
        font-style: italic;
        font-size: 0.9rem;
    }

    .reader-content {
        flex: 1;
        overflow: auto;
        padding: 20px;
        box-sizing: border-box;
        position: relative;
        scroll-behavior: smooth;
    }

    .pdf-viewer {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        background-color: var(--reader-bg);
        padding-bottom: 80px;
    }

    .pdf-page {
        margin-bottom: 20px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        background-color: white;
        max-width: 100%;
    }

    .pdf-controls {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(77, 51, 81, 0.9);
        padding: 10px 15px;
        border-radius: 30px;
        display: flex;
        gap: 10px;
        align-items: center;
        z-index: 50;
        backdrop-filter: blur(5px);
    }

    .pdf-control-btn {
        background: transparent;
        border: none;
        color: white;
        font-size: 1rem;
        cursor: pointer;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .pdf-control-btn:hover {
        background: rgba(255,255,255,0.2);
    }

    .page-counter {
        color: white;
        font-size: 0.9rem;
        margin: 0 5px;
        min-width: 80px;
        text-align: center;
    }

    .loading-message {
        text-align: center;
        padding: 50px;
        font-size: 1.2rem;
        color: var(--dark);
    }

    .error-message {
        text-align: center;
        padding: 50px;
        font-size: 1.2rem;
        color: var(--error-color);
    }

    .fullscreen-toolbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: linear-gradient(120deg, rgba(10,61,84,0.92) 60%, rgba(0,110,140,0.92) 100%);
        padding: 8px 16px;
        display: none;
        justify-content: space-between;
        align-items: center;
        z-index: 1001;
        height: 40px;
        backdrop-filter: blur(5px);
    }

    .fullscreen .fullscreen-toolbar {
        display: flex;
    }

    .fullscreen .reader-header {
        display: none;
    }

    .fullscreen-toolbar-controls {
        display: flex;
        gap: 15px;
    }

    .fullscreen-toolbar-btn {
        background: transparent;
        border: none;
        color: white;
        font-size: 1rem;
        cursor: pointer;
        padding: 5px 10px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .fullscreen-toolbar-btn:hover {
        background: rgba(255,255,255,0.2);
    }

    .fullscreen-page-info {
        color: white;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0,0,0,0.2);
        }

        .sidebar.collapsed {
            margin-left: -300px;
        }

        .reader-header {
            padding: 10px 15px;
        }

        .reader-title {
            font-size: 1rem;
            max-width: 60%;
        }

        .pdf-controls {
            bottom: 10px;
            padding: 8px 12px;
        }

        .pdf-control-btn {
            width: 36px;
            height: 36px;
            font-size: 0.9rem;
        }

        .page-counter {
            font-size: 0.8rem;
            min-width: 70px;
        }

        .toc-list {
            max-height: 50vh;
        }
    }

    @media (max-width: 480px) {
        .reader-controls {
            gap: 8px;
        }
        
        .reader-btn {
            padding: 5px;
        }
        
        .pdf-controls {
            gap: 5px;
            padding: 8px 10px;
        }
        
        .pdf-control-btn {
            width: 32px;
            height: 32px;
            font-size: 0.8rem;
        }
    }
</style>
</head>
<body>
    <div class="reader-container">
        <div class="reader-header">
            <h1 class="reader-title"><?php echo htmlspecialchars($book['title']); ?></h1>
            <div class="reader-controls">
                <button class="reader-btn" id="sidebar-toggle-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <button class="reader-btn" id="theme-btn">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="reader-btn" id="fullscreen-btn">
                    <i class="fas fa-expand"></i>
                </button>
                <button class="reader-btn" id="back-btn" onclick="window.location.href='/project/bookhive/'">
                    <i class="fas fa-arrow-left"></i>
                </button>
            </div>
        </div>

        <div class="fullscreen-toolbar">
            <div class="fullscreen-toolbar-controls">
                <button class="fullscreen-toolbar-btn" id="fs-sidebar-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <button class="fullscreen-toolbar-btn" id="fs-theme-btn">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="fullscreen-toolbar-btn" id="fs-zoom-in-btn">
                    <i class="fas fa-search-plus"></i>
                </button>
                <button class="fullscreen-toolbar-btn" id="fs-zoom-out-btn">
                    <i class="fas fa-search-minus"></i>
                </button>
            </div>
            <div class="fullscreen-page-info">
                Page <span id="fs-current-page">1</span> of <span id="fs-total-pages"><?php echo $book['pages']; ?></span>
            </div>
            <button class="fullscreen-toolbar-btn" id="fs-exit-btn">
                <i class="fas fa-compress"></i>
            </button>
        </div>

        <div class="reader-content-container">
            <div class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <h2 class="sidebar-title">Book Details</h2>
                    <button class="sidebar-close-btn" id="sidebar-close-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="book-info">
                    <?php if (!empty($book['cover_url'])): ?>
                    <img src="/project/bookhive/uploads/covers/<?php echo basename($book['cover_url']); ?>" alt="Book Cover" class="book-cover">
                    <?php else: ?>
                    <div class="book-cover-placeholder" style="height: 200px; background: #eee; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-book" style="font-size: 3rem; color: #aaa;"></i>
                    </div>
                    <?php endif; ?>
                    <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                    <p class="book-author">by <?php echo htmlspecialchars($book['author_name']); ?></p>
                </div>

                <div class="progress-container">
                    <div class="progress-label">
                        <span>Reading Progress</span>
                        <span id="progress-percentage"><?php echo $book['read_percentage']; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill"></div>
                    </div>
                    <div class="progress-details">
                        <span id="pages-read"><?php echo $book['pages_read']; ?></span> of 
                        <span><?php echo $book['pages']; ?></span> pages read
                    </div>
                </div>

                <div class="toc-container">
                    <h3 class="toc-title">Table of Contents</h3>
                    <ul class="toc-list" id="toc-list">
                        <li class="toc-item"><span class="toc-loading">Loading table of contents...</span></li>
                    </ul>
                </div>
            </div>

            <div class="reader-content">
                <div class="pdf-viewer" id="pdf-viewer">
                    <div class="loading-message">Loading PDF, please wait...</div>
                </div>
            </div>
        </div>

        <div class="pdf-controls">
            <button class="pdf-control-btn" id="prev-page-btn" title="Previous Page">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="page-counter">
                <span id="current-page"><?php echo $book['current_page']; ?></span> / 
                <span id="total-pages"><?php echo $book['pages']; ?></span>
            </div>
            <button class="pdf-control-btn" id="next-page-btn" title="Next Page">
                <i class="fas fa-chevron-right"></i>
            </button>
            <button class="pdf-control-btn" id="zoom-out-btn" title="Zoom Out">
                <i class="fas fa-search-minus"></i>
            </button>
            <button class="pdf-control-btn" id="zoom-in-btn" title="Zoom In">
                <i class="fas fa-search-plus"></i>
            </button>
            <button class="pdf-control-btn" id="rotate-btn" title="Rotate">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <script>
    // PDF rendering variables
    let currentPage = <?php echo $book['current_page']; ?>;
    let totalPages = <?php echo $book['pages'] ?? 0; ?>;
    let pdfDoc = null;
    let pdfScale = 1.0;
    let rotation = 0;
    let isFullscreen = false;
    let isDarkMode = false;
    let pagesRendered = new Set();
    let pageElements = [];
    let pagePositions = {};
    let scrollTimeout = null;
    let isScrollingProgrammatically = false;

    // Initialize PDF.js worker
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

    // Initialize theme from localStorage
    function initTheme() {
        const savedMode = localStorage.getItem('pdfViewerDarkMode') === 'true';
        if (savedMode) {
            isDarkMode = savedMode;
            document.body.classList.add('dark-mode');
            document.getElementById('theme-btn').innerHTML = '<i class="fas fa-sun"></i>';
            document.getElementById('fs-theme-btn').innerHTML = '<i class="fas fa-sun"></i>';
        }
    }

    // Handle PDF loading errors
    function handlePDFError(err) {
        console.error('PDF error:', err);
        const viewer = document.getElementById('pdf-viewer');
        viewer.innerHTML = `<div class="error-message">Error loading PDF: ${err.message}</div>`;
    }

    // Load PDF
    const loadingTask = pdfjsLib.getDocument('<?php echo $pdf_url; ?>');

    // Update page counters
    function updatePageCounters() {
        document.getElementById('current-page').textContent = currentPage;
        document.getElementById('fs-current-page').textContent = currentPage;
    }

    // Update reading progress
    async function updateReadingProgress() {
        const newProgress = Math.min(Math.round((currentPage / totalPages) * 100), 100);
        const pagesReadCount = Math.floor((newProgress / 100) * totalPages);
        
        // Update UI
        document.getElementById('progress-percentage').textContent = `${newProgress}%`;
        document.getElementById('progress-fill').style.width = `${newProgress}%`;
        document.getElementById('pages-read').textContent = pagesReadCount;
        
        <?php if (isLoggedIn()): ?>
        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `current_page=${currentPage}`
            });
            
            if (!response.ok) {
                console.error('Failed to save progress');
            }
        } catch (err) {
            console.error('Error saving progress:', err);
        }
        <?php endif; ?>
    }

    // Render all pages at once
    async function renderAllPages() {
        const container = document.getElementById('pdf-viewer');
        container.innerHTML = '';
        pageElements = [];
        pagePositions = {};
        pagesRendered.clear();
        
        // Create page containers first
        for (let i = 1; i <= totalPages; i++) {
            const pageDiv = document.createElement('div');
            pageDiv.className = 'pdf-page';
            pageDiv.dataset.pageNumber = i;
            pageDiv.id = `page-${i}`;
            container.appendChild(pageDiv);
            pageElements.push(pageDiv);
        }
        
        // Then render pages sequentially
        for (let i = 1; i <= totalPages; i++) {
            await renderPage(i);
        }
        
        // Set up scroll listener after rendering
        setupScrollListener();
        
        // Scroll to initial page after rendering
        setTimeout(() => {
            scrollToPage(currentPage);
        }, 100);
    }

    // Render a single page
    async function renderPage(pageNum) {
        try {
            const page = await pdfDoc.getPage(pageNum);
            const viewport = page.getViewport({ scale: pdfScale, rotation: rotation });
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            
            const pageDiv = document.getElementById(`page-${pageNum}`);
            pageDiv.style.width = `${viewport.width}px`;
            pageDiv.style.height = `${viewport.height}px`;
            pageDiv.innerHTML = '';
            pageDiv.appendChild(canvas);
            
            await page.render({
                canvasContext: context,
                viewport: viewport
            }).promise;
            
            pagesRendered.add(pageNum);
            updatePagePosition(pageNum);
            
            return true;
        } catch (err) {
            console.error('Error rendering page', pageNum, err);
            return false;
        }
    }

    // Set up scroll listener for page tracking
    function setupScrollListener() {
        const container = document.querySelector('.reader-content');
        
        container.addEventListener('scroll', () => {
            if (isScrollingProgrammatically) return;
            
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                updateCurrentPageFromScroll();
            }, 100);
        });
    }

    // Update current page based on scroll position
    function updateCurrentPageFromScroll() {
        const container = document.querySelector('.reader-content');
        const scrollPosition = container.scrollTop + (container.clientHeight / 2);
        
        let closestPage = 1;
        let minDistance = Infinity;
        
        // Find the page closest to the center of the viewport
        for (let i = 1; i <= totalPages; i++) {
            const pageElement = document.getElementById(`page-${i}`);
            if (!pageElement) continue;
            
            const pageTop = pageElement.offsetTop;
            const pageBottom = pageTop + pageElement.offsetHeight;
            const pageCenter = pageTop + (pageElement.offsetHeight / 2);
            
            const distance = Math.abs(scrollPosition - pageCenter);
            
            if (distance < minDistance) {
                minDistance = distance;
                closestPage = i;
            }
        }
        
        if (closestPage !== currentPage) {
            currentPage = closestPage;
            updatePageCounters();
            updateReadingProgress();
            updateActiveTOCItem();
        }
    }

    // Scroll to a specific page
    function scrollToPage(pageNum) {
        const pageElement = document.getElementById(`page-${pageNum}`);
        if (pageElement) {
            isScrollingProgrammatically = true;
            
            const container = document.querySelector('.reader-content');
            const targetPosition = pageElement.offsetTop - (container.clientHeight * 0.25);
            
            container.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
            
            currentPage = pageNum;
            updatePageCounters();
            updateReadingProgress();
            updateActiveTOCItem();
            
            setTimeout(() => {
                isScrollingProgrammatically = false;
            }, 500);
        }
    }

    // Update page position in the DOM
    function updatePagePosition(pageNum) {
        const pageElement = document.getElementById(`page-${pageNum}`);
        if (pageElement) {
            const rect = pageElement.getBoundingClientRect();
            const container = document.querySelector('.reader-content');
            pagePositions[pageNum] = container.scrollTop + rect.top;
        }
    }

    // Load table of contents with proper destination resolution
    async function loadTableOfContents() {
        const tocList = document.getElementById('toc-list');
        tocList.innerHTML = '<li class="toc-item"><span class="toc-loading">Loading table of contents...</span></li>';
        
        try {
            const outline = await pdfDoc.getOutline();
            
            if (!outline || outline.length === 0) {
                tocList.innerHTML = '<li class="toc-item"><span class="toc-empty">No table of contents available</span></li>';
                return;
            }
            
            const validTOCItems = await processOutlineItems(outline);
            
            if (validTOCItems.length === 0) {
                tocList.innerHTML = '<li class="toc-item"><span class="toc-empty">No valid entries found</span></li>';
                return;
            }
            
            renderTOC(validTOCItems, tocList);
            
        } catch (err) {
            console.error('TOC loading error:', err);
            tocList.innerHTML = '<li class="toc-item"><span class="toc-error">Error loading table of contents</span></li>';
        }
    }

    // Process outline items recursively
    async function processOutlineItems(items) {
        const result = [];
        
        for (const item of items) {
            try {
                const pageNum = await resolveDestinationToPageNumber(item.dest);
                
                if (pageNum >= 1 && pageNum <= totalPages) {
                    const tocItem = {
                        title: item.title || 'Untitled',
                        pageNum: pageNum,
                        items: item.items ? await processOutlineItems(item.items) : []
                    };
                    result.push(tocItem);
                }
            } catch (err) {
                console.warn('Skipping invalid TOC item:', item.title, err);
                continue;
            }
        }
        
        return result;
    }

    // Improved destination resolution function
    // Improved destination resolution function
async function resolveDestinationToPageNumber(dest) {
    if (!dest) return 1; // Fallback to the first page

    // Handle named destinations (string)
    if (typeof dest === 'string') {
        try {
            const destArray = await pdfDoc.getDestination(dest);
            return await resolveDestinationToPageNumber(destArray);
        } catch (err) {
            console.warn('Failed to resolve named destination:', dest, err);
            return 1;
        }
    }

    // Handle array destinations
    if (Array.isArray(dest)) {
        const pageRef = dest[0]; // First element is the page reference

        // Resolve the page index from the page reference
        try {
            const pageIndex = await pdfDoc.getPageIndex(pageRef);
            return pageIndex + 1; // Convert from 0-based to 1-based
        } catch (err) {
            console.warn('Could not resolve page index:', err);
            return 1;
        }
    }

    console.warn('Unsupported destination format:', dest);
    return 1; // Fallback to the first page
}

    // Render TOC items
    function renderTOC(items, parentEl, level = 0) {
        parentEl.innerHTML = '';
        
        items.forEach(item => {
            const li = document.createElement('li');
            li.className = `toc-item level-${level}`;
            
            const a = document.createElement('a');
            a.href = '#';
            a.className = 'toc-link';
            a.textContent = item.title;
            a.dataset.page = item.pageNum;
            
            a.addEventListener('click', (e) => {
                e.preventDefault();
                navigateToTOCPage(item.pageNum);
            });
            
            li.appendChild(a);
            
            if (item.items.length > 0) {
                const toggle = document.createElement('span');
                toggle.className = 'toc-toggle';
                toggle.innerHTML = '<i class="fas fa-chevron-right"></i>';
                toggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    li.classList.toggle('expanded');
                });
                li.insertBefore(toggle, a);
                
                const subList = document.createElement('ul');
                subList.className = 'toc-sublist';
                renderTOC(item.items, subList, level + 1);
                li.appendChild(subList);
            }
            
            parentEl.appendChild(li);
        });
    }

    // Update active TOC item
    function updateActiveTOCItem() {
        document.querySelectorAll('.toc-link').forEach(link => {
            const pageNum = parseInt(link.dataset.page);
            link.classList.toggle('active', pageNum === currentPage);
            
            // Auto-expand parent items if this is a child
            if (pageNum === currentPage) {
                let parentItem = link.closest('.toc-item');
                while (parentItem) {
                    parentItem.classList.add('expanded');
                    const toggle = parentItem.querySelector('.toc-toggle');
                    if (toggle) {
                        toggle.innerHTML = '<i class="fas fa-chevron-down"></i>';
                    }
                    parentItem = parentItem.parentElement.closest('.toc-item');
                }
            }
        });
    }

    // Improved TOC navigation function
    function navigateToTOCPage(pageNum) {
        if (pageNum < 1 || pageNum > totalPages) return;
        
        const pageElement = document.getElementById(`page-${pageNum}`);
        if (!pageElement) return;
        
        isScrollingProgrammatically = true;
        
        const container = document.querySelector('.reader-content');
        const containerHeight = container.clientHeight;
        const pageTop = pageElement.offsetTop;
        
        // Calculate target position to center the page in the viewport
        const targetPosition = pageTop - (containerHeight / 2) + (pageElement.offsetHeight / 2);
        
        container.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });
        
        currentPage = pageNum;
        updatePageCounters();
        updateReadingProgress();
        updateActiveTOCItem();
        
        setTimeout(() => {
            isScrollingProgrammatically = false;
        }, 500);
    }

    // Navigation functions
    function goToPrevPage() {
        if (currentPage > 1) {
            scrollToPage(currentPage - 1);
        }
    }

    function goToNextPage() {
        if (currentPage < totalPages) {
            scrollToPage(currentPage + 1);
        }
    }

    function zoomIn() {
        pdfScale = Math.min(pdfScale + 0.25, 3.0);
        renderAllPages();
    }

    function zoomOut() {
        pdfScale = Math.max(pdfScale - 0.25, 0.5);
        renderAllPages();
    }

    function rotatePDF() {
        rotation = (rotation + 90) % 360;
        renderAllPages();
    }

    function toggleDarkMode() {
        isDarkMode = !isDarkMode;
        document.body.classList.toggle('dark-mode', isDarkMode);
        
        // Update button icons
        const themeIcon = isDarkMode ? 'fa-sun' : 'fa-moon';
        document.getElementById('theme-btn').innerHTML = `<i class="fas ${themeIcon}"></i>`;
        document.getElementById('fs-theme-btn').innerHTML = `<i class="fas ${themeIcon}"></i>`;
        
        // Save preference
        localStorage.setItem('pdfViewerDarkMode', isDarkMode);
    }

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    }

    function toggleFullscreen() {
        if (!isFullscreen) {
            document.documentElement.requestFullscreen().catch(err => {
                console.error(`Error attempting to enable fullscreen: ${err.message}`);
            });
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    }

    function handleFullscreenChange() {
        isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement);
        document.body.classList.toggle('fullscreen', isFullscreen);
        
        const readerContent = document.querySelector('.reader-content');
        const pdfViewer = document.getElementById('pdf-viewer');
        
        if (isFullscreen) {
            readerContent.style.padding = '0';
            pdfViewer.style.width = '100vw';
            pdfViewer.style.height = '100vh';
            pdfViewer.style.maxWidth = 'none';
            document.getElementById('sidebar').classList.add('collapsed');
            
            // Re-render pages to fit new dimensions
            setTimeout(() => {
                renderAllPages();
            }, 100);
        } else {
            readerContent.style.padding = '20px';
            pdfViewer.style.width = '';
            pdfViewer.style.height = '';
            pdfViewer.style.maxWidth = '';
            
            // Re-render pages to fit normal dimensions
            setTimeout(() => {
                renderAllPages();
            }, 100);
        }
    }

    // Initialize the PDF viewer
    loadingTask.promise.then(function(pdf) {
        pdfDoc = pdf;
        totalPages = pdf.numPages;
        document.getElementById('total-pages').textContent = totalPages;
        document.getElementById('fs-total-pages').textContent = totalPages;
        
        // Render all pages
        renderAllPages();
        
        // Load table of contents
        loadTableOfContents();
    }).catch(handlePDFError);

    // Set up event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize theme
        initTheme();
        
        // Navigation controls
        document.getElementById('prev-page-btn').addEventListener('click', goToPrevPage);
        document.getElementById('next-page-btn').addEventListener('click', goToNextPage);
        document.getElementById('zoom-in-btn').addEventListener('click', zoomIn);
        document.getElementById('zoom-out-btn').addEventListener('click', zoomOut);
        document.getElementById('rotate-btn').addEventListener('click', rotatePDF);
        
        // UI controls
        document.getElementById('theme-btn').addEventListener('click', toggleDarkMode);
        document.getElementById('fs-theme-btn').addEventListener('click', toggleDarkMode);
        document.getElementById('sidebar-toggle-btn').addEventListener('click', toggleSidebar);
        document.getElementById('fs-sidebar-btn').addEventListener('click', toggleSidebar);
        document.getElementById('sidebar-close-btn').addEventListener('click', toggleSidebar);
        document.getElementById('fullscreen-btn').addEventListener('click', toggleFullscreen);
        document.getElementById('fs-exit-btn').addEventListener('click', toggleFullscreen);
        
        // Ensure fullscreen zoom in/out always works
        function bindFullscreenToolbarEvents() {
            const fsZoomInBtn = document.getElementById('fs-zoom-in-btn');
            const fsZoomOutBtn = document.getElementById('fs-zoom-out-btn');
            if (fsZoomInBtn) {
                fsZoomInBtn.onclick = function(e) {
                    e.preventDefault();
                    zoomIn();
                };
            }
            if (fsZoomOutBtn) {
                fsZoomOutBtn.onclick = function(e) {
                    e.preventDefault();
                    zoomOut();
                };
            }
        }

        bindFullscreenToolbarEvents();
        document.addEventListener('fullscreenchange', bindFullscreenToolbarEvents);
        document.addEventListener('webkitfullscreenchange', bindFullscreenToolbarEvents);
        document.addEventListener('msfullscreenchange', bindFullscreenToolbarEvents);
        
        // Fullscreen change events
        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
        document.addEventListener('msfullscreenchange', handleFullscreenChange);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' || e.key === 'PageUp') {
                goToPrevPage();
                e.preventDefault();
            } else if (e.key === 'ArrowRight' || e.key === 'PageDown' || e.key === ' ') {
                goToNextPage();
                e.preventDefault();
            } else if (e.key === '+' || e.key === '=') {
                zoomIn();
                e.preventDefault();
            } else if (e.key === '-') {
                zoomOut();
                e.preventDefault();
            } else if (e.key === 'r' || e.key === 'R') {
                rotatePDF();
                e.preventDefault();
            } else if (e.key === 'd' || e.key === 'D') {
                toggleDarkMode();
                e.preventDefault();
            } else if (e.key === 'f' || e.key === 'F') {
                toggleFullscreen();
                e.preventDefault();
            } else if (e.key === 's' || e.key === 'S') {
                toggleSidebar();
                e.preventDefault();
            }
        });
        
        // Update page positions on resize
        window.addEventListener('resize', () => {
            for (let i = 1; i <= totalPages; i++) {
                updatePagePosition(i);
            }
        });
    });
</script>
</body>
</html>