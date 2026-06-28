<?php
require_once 'includes/config.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$suggestions = [];

if ($q !== '') {
    $qLike = '%' . $q . '%';

    // Book title, author, or category match in one query for books
    $stmt = $conn->prepare(
        "SELECT b.book_id, b.title, a.author_name, c.category_name, b.cover_url 
         FROM books b 
         JOIN authors a ON b.author_id = a.author_id 
         JOIN categories c ON b.category_id = c.category_id 
         WHERE b.title LIKE ? OR a.author_name LIKE ? OR c.category_name LIKE ?
         LIMIT 7"
    );
    $stmt->bind_param("sss", $qLike, $qLike, $qLike);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = [
            'type' => 'book',
            'id' => $row['book_id'],
            'title' => $row['title'],
            'author' => $row['author_name'],
            'category' => $row['category_name'],
            'cover' => getCoverUrl($row['cover_url'])
        ];
    }

    // Author match (distinct, not already in books above)
    $stmt = $conn->prepare("SELECT DISTINCT author_name FROM authors WHERE author_name LIKE ? LIMIT 5");
    $stmt->bind_param("s", $qLike);
    $stmt->execute();
    $result = $stmt->get_result();
    foreach ($result as $row) {
        // Avoid duplicate author suggestions if already in books
        $already = false;
        foreach ($suggestions as $s) {
            if ($s['type'] === 'book' && strtolower($s['author']) === strtolower($row['author_name'])) {
                $already = true;
                break;
            }
        }
        if (!$already) {
            $suggestions[] = [
                'type' => 'author',
                'id' => 0,
                'title' => '',
                'author' => $row['author_name'],
                'category' => '',
                'cover' => ''
            ];
        }
    }

    // Category match (distinct, not already in books above)
    $stmt = $conn->prepare("SELECT category_id, category_name FROM categories WHERE category_name LIKE ? LIMIT 5");
    $stmt->bind_param("s", $qLike);
    $stmt->execute();
    $result = $stmt->get_result();
    foreach ($result as $row) {
        $already = false;
        foreach ($suggestions as $s) {
            if ($s['type'] === 'book' && strtolower($s['category']) === strtolower($row['category_name'])) {
                $already = true;
                break;
            }
        }
        if (!$already) {
            $suggestions[] = [
                'type' => 'category',
                'id' => $row['category_id'],
                'title' => '',
                'author' => '',
                'category' => $row['category_name'],
                'cover' => ''
            ];
        }
    }
}

header('Content-Type: application/json');
echo json_encode($suggestions);
