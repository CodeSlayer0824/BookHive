<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$featured_books = [];
$sql = "SELECT b.*, a.author_name, c.category_name 
        FROM books b 
        JOIN authors a ON b.author_id = a.author_id
        JOIN categories c ON b.category_id = c.category_id 
        ORDER BY b.featured_date DESC LIMIT 12";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) $featured_books[] = $row;

$popular_books = [];
$sql = "SELECT b.*, a.author_name, c.category_name 
        FROM books b 
        JOIN authors a ON b.author_id = a.author_id 
        JOIN categories c ON b.category_id = c.category_id 
        ORDER BY b.views DESC LIMIT 8";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) $popular_books[] = $row;

$newest_books = [];
$sql = "SELECT b.*, a.author_name, c.category_name 
        FROM books b 
        JOIN authors a ON b.author_id = a.author_id 
        JOIN categories c ON b.category_id = c.category_id 
        ORDER BY b.created_at DESC LIMIT 8";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) $newest_books[] = $row;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BookHive - Your Literary Escape</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --deep-teal: #0A3D54;
            --ocean: #006E8C;
            --sea-green: #00B4CC;
            --light-cyan: #00E6D6;
            --ivory: #F9F6EF;
            --parchment: #F1ECE2;
            --gold-leaf: #D4A017;
            --bronze: #B88B4A;
            --shadow: rgba(0,0,0,0.15);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: var(--ivory);
            color: #333;
            line-height: 1.6;
            background-image: 
                linear-gradient(135deg, var(--parchment) 0%, var(--ivory) 100%),
                radial-gradient(circle at 10% 20%, rgba(var(--light-cyan), 0.03) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(var(--light-cyan), 0.03) 0%, transparent 20%);
        }

        h1, h2, h3, .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

         .hero-section {
            background: 
                linear-gradient(120deg, rgba(10,61,84,0.92) 60%, rgba(0,110,140,0.92) 100%),
                url('https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=1200&q=80') center center/cover no-repeat;
            padding: 100px 20px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px var(--shadow);
            min-height: 500px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .hero-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(120deg, rgba(0,180,204,0.10) 0%, rgba(0,230,214,0.10) 100%);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-section h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            position: relative;
            text-shadow: 0 2px 8px rgba(0,0,0,0.28);
            color: #fff;
        }

        .hero-section p {
            font-size: 1.3rem;
            margin-bottom: 30px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            position: relative;
            font-weight: 300;
            opacity: 0.96;
            color: #eafcff;
            text-shadow: 0 1px 6px rgba(0,0,0,0.22);
        }

        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 25px;
            position: relative;
            margin-top: 20px;
        }

        .btn-primary {
            background-color: var(--ocean);
            color: var(--ivory);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: 2px solid var(--ocean);
            padding: 14px 32px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            background-color: var(--sea-green);
            color: var(--deep-teal);
            border-color: var(--sea-green);
        }

        .btn-secondary {
            background-color: transparent;
            color: white;
            border: 2px solid rgba(255,255,255,0.7);
            padding: 14px 32px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-secondary:hover {
            background-color: rgba(255,255,255,0.15);
            border-color: white;
            transform: translateY(-3px);
        }

        .section-title {
            font-size: 2.4rem;
            text-align: center;
            margin: 70px 0 35px;
            color: var(--deep-teal);
            position: relative;
        }

        .section-title:after {
            content: "";
            display: block;
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, var(--gold-leaf), var(--sea-green));
            margin: 15px auto 0;
            border-radius: 3px;
        }

        .book-section {
            position: relative;
            padding: 0 60px;
            margin-bottom: 80px;
        }

        .book-row {
            display: flex;
            overflow-x: auto;
            gap: 25px;
            scroll-behavior: smooth;
            padding: 25px 10px;
            scrollbar-width: none;
        }

        .book-row::-webkit-scrollbar {
            display: none;
        }

        .book-card {
            flex: 0 0 auto;
            width: 200px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            height: 308px; /* 260px image + 48px title, no extra space */
            padding: 0;
        }

        .book-card a {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 0;
        }

        .book-card img {
            width: 100%;
            height: 260px;
            object-fit: contain;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            display: block;
            background: #fff;
            margin: 0;
            padding: 0;
            flex: 0 0 auto;
        }

        .book-title {
            background: linear-gradient(to right, var(--ocean), var(--deep-teal));
            color: white;
            text-align: center;
            padding: 0 10px;
            font-weight: 600;
            font-size: 1.05rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: all 0.3s ease;
            position: static;
            width: 100%;
            min-height: unset;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0 0 8px 8px;
            margin: 0;
            height: 48px;
            flex: 0 0 48px;
            /* Remove any extra margin or padding below */
            margin-top: 0;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .book-card:hover .book-title {
            background: linear-gradient(to right, var(--sea-green), var(--ocean));
        }

        /* Remove extra space below the title */
        .book-card a {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .book-card img {
            flex-shrink: 0;
        }

        .book-title {
            flex-shrink: 0;
        }

        .scroll-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: var(--deep-teal);
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.9;
            box-shadow: 0 3px 8px rgba(0,0,0,0.2);
        }

        .scroll-btn:hover {
            opacity: 1;
            background: var(--sea-green);
            transform: translateY(-50%) scale(1.1);
        }

        .scroll-btn.left {
            left: 5px;
        }

        .scroll-btn.right {
            right: 5px;
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 80px 20px;
                min-height: 400px;
                background-position: 60% center;
            }
            
            .hero-section h1 {
                font-size: 2.5rem;
            }

            .hero-section p {
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 2rem;
                margin: 50px 0 30px;
            }

            .book-section {
                padding: 0 25px;
                margin-bottom: 60px;
            }

            .book-card {
                width: 160px;
            }

            .book-card img {
                height: 240px;
            }

            .scroll-btn {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }

            .hero-buttons a {
                width: 80%;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<div class="hero-section">
    <div class="hero-content">
        <h1>Journey Through Pages</h1>
        <p>Discover worlds between the lines with our carefully curated collection of literary treasures</p>
        <div class="hero-buttons">
            <?php if (isLoggedIn()): ?>
                <a href="/project/bookhive/user/dashboard.php" class="btn-primary">Start Reading</a>
            <?php else: ?>
                <a href="/project/bookhive/auth/register.php" class="btn-primary">Start Reading</a>
            <?php endif; ?>
            <a href="/project/bookhive/categories.php" class="btn-secondary">Browse Collection</a>
        </div>
    </div>
</div>

<section class="book-section">
    <h2 class="section-title">Editor's Choice</h2>
    <button class="scroll-btn left" disabled>&#8249;</button>
    <div class="book-row" id="featured-books">
        <?php foreach ($featured_books as $book): ?>
            <div class="book-card">
                <a href="/project/bookhive/books/view.php?id=<?= $book['book_id']; ?>">
                    <img src="<?= getCoverUrl($book['cover_url']); ?>" alt="<?= htmlspecialchars($book['title']); ?>">
                    <div class="book-title"><?= htmlspecialchars($book['title']); ?></div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="scroll-btn right">&#8250;</button>
</section>

<section class="book-section">
    <h2 class="section-title">Popular</h2>
    <button class="scroll-btn left" disabled>&#8249;</button>
    <div class="book-row" id="popular-books">
        <?php foreach ($popular_books as $book): ?>
            <div class="book-card">
                <a href="/project/bookhive/books/view.php?id=<?= $book['book_id']; ?>">
                    <img src="<?= getCoverUrl($book['cover_url']); ?>" alt="<?= htmlspecialchars($book['title']); ?>">
                    <div class="book-title"><?= htmlspecialchars($book['title']); ?></div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="scroll-btn right">&#8250;</button>
</section>

<section class="book-section">
    <h2 class="section-title">New Arrivals</h2>
    <button class="scroll-btn left" disabled>&#8249;</button>
    <div class="book-row" id="new-books">
        <?php foreach ($newest_books as $book): ?>
            <div class="book-card">
                <a href="/project/bookhive/books/view.php?id=<?= $book['book_id']; ?>">
                    <img src="<?= getCoverUrl($book['cover_url']); ?>" alt="<?= htmlspecialchars($book['title']); ?>">
                    <div class="book-title"><?= htmlspecialchars($book['title']); ?></div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="scroll-btn right">&#8250;</button>
</section>

<?php require_once 'includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        initBookRow('featured-books');
        initBookRow('popular-books');
        initBookRow('new-books');
    });

    function initBookRow(rowId) {
        const container = document.getElementById(rowId);
        const leftBtn = container.previousElementSibling;
        const rightBtn = container.nextElementSibling;
        
        updateButtonStates(container, leftBtn, rightBtn);
        
        container.addEventListener('scroll', function() {
            updateButtonStates(container, leftBtn, rightBtn);
        });
        
        leftBtn.addEventListener('click', function() {
            container.scrollBy({ left: -300, behavior: 'smooth' });
        });
        
        rightBtn.addEventListener('click', function() {
            container.scrollBy({ left: 300, behavior: 'smooth' });
        });
    }

    function updateButtonStates(container, leftBtn, rightBtn) {
        leftBtn.disabled = container.scrollLeft <= 0;
        rightBtn.disabled = container.scrollLeft >= container.scrollWidth - container.clientWidth - 1;
    }
</script>

</body>
</html>