<?php
ob_start();
require_once 'config.php';

$profileImage = '';
$displayName = '';
$defaultSVG = '<div class="profile-svg-wrapper"><svg class="profile-svg" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><circle cx="32" cy="32" r="30" fill="#917BA5"/><path d="M32 36c-8 0-14 6-14 14h28c0-8-6-14-14-14zm0-4a8 8 0 100-16 8 8 0 000 16z" fill="#fff"/></svg></div>';

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $stmt = $conn->prepare("SELECT s.username, p.avatar FROM signup s LEFT JOIN user_profiles p ON s.email = p.email WHERE s.email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $displayName = $result['username'] ?? 'User';
    $profileImagePath = $result['avatar'] ?? '';

    // Check for uploaded avatar in /uploads/avatars/
    $avatarPath = '';
    if (!empty($profileImagePath) && $profileImagePath !== 'default.jpg') {
        $avatarPath = '/project/bookhive/uploads/avatars/' . htmlspecialchars($profileImagePath);
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/project/bookhive/uploads/avatars/' . $profileImagePath)) {
            $profileImage = '<img src="' . $avatarPath . '" alt="Profile" class="profile-img" />';
        } else {
            $profileImage = $defaultSVG;
        }
    } else {
        $profileImage = $defaultSVG;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>BookHive</title>
  <link rel="stylesheet" href="/project/bookhive/assets/css/style.css" />
  <link rel="stylesheet" href="/project/bookhive/assets/css/responsive.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
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

    body {
      font-family: 'Open Sans', sans-serif;
      margin: 0;
      background-color: var(--ivory);
      color: var(--deep-teal);
    }

    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: var(--parchment);
      padding: 10px 20px;
      border-bottom: 2px;
      position: relative;
      z-index: 10;
    }

    .logo {
      display: flex;
      align-items: center;
      font-size: 1.5rem;
      color: var(--deep-teal);
      font-family: 'Cormorant Garamond', serif;
      font-weight: 700;
    }

    .logo svg {
      height: 40px;
      margin-right: 10px;
      fill: var(--deep-teal);
    }

    .search-bar input {
      padding: 8px 12px;
      border: 1px solid var(--gold-leaf);
      border-radius: 5px 0 0 5px;
      background: var(--ivory);
      color: var(--deep-teal);
    }

    .search-bar button {
      padding: 8px 12px;
      background-color: var(--deep-teal);
      color: var(--ivory);
      border: none;
      border-radius: 0 5px 5px 0;
      cursor: pointer;
      font-weight: 600;
    }

    /* Suggestions dropdown styling */
    #header-search-suggestions {
      list-style: none;
      position: absolute;
      top: 38px;
      left: 0;
      right: 0;
      z-index: 100;
      background: #fff;
      border-radius: 0 0 12px 12px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.13);
      margin: 0;
      padding: 0;
      display: none;
      max-height: 260px;
      overflow-y: auto;
    }
    #header-search-suggestions li {
      padding: 10px 16px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
      border-bottom: 1px solid #eee;
    }
    #header-search-suggestions li:last-child {
      border-bottom: none;
    }
    #header-search-suggestions img {
      width: 32px;
      height: 44px;
      object-fit: contain;
      border-radius: 4px;
      background: #fff;
      /* matches index.php style for book images */
    }
    #header-search-suggestions .suggest-title {
      color: #0A3D54;
      font-weight: 600;
    }
    #header-search-suggestions .suggest-author {
      font-size: 0.95em;
      color: #006E8C;
    }
    #header-search-suggestions .suggest-category {
      font-size: 0.9em;
      color: #B88B4A;
    }
    #header-search-suggestions .suggest-icon {
      font-size: 1.1em;
      color: #006E8C;
      font-weight: 600;
    }

    .nav-links ul {
      list-style: none;
      display: flex;
      align-items: center;
      margin: 0;
      padding: 0;
    }

    .nav-links ul li {
      margin-left: 20px;
      position: relative;
    }

    .nav-links a {
      text-decoration: none;
      color: var(--deep-teal);
      font-weight: 500;
      transition: color 0.2s;
      padding: 6px 10px;
      border-radius: 5px;
    }

    .nav-links a:hover {
      color: var(--ivory);
      background-color:rgb(43, 57, 60);
    }

    .user-dropdown {
      position: relative;
      display: inline-block;
    }

    .user-profile {
      display: flex;
      align-items: center;
      cursor: pointer;
    }

    .profile-img,
    .profile-svg {
      height: 35px;
      width: 35px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 8px;
      border: 2px solid var(--deep-teal);
    }

    .profile-svg-wrapper {
      height: 35px;
      width: 35px;
      border-radius: 50%;
      overflow: hidden;
      margin-right: 8px;
    }

    .user-profile span {
      font-weight: 600;
      color: var(--ocean);
    }

    .dropdown-menu {
      display: none;
      position: absolute;
      top: 45px;
      right: 0;
      background-color: var(--ivory);
      border: 1px solid var(--deep-teal);
      padding: 10px;
      border-radius: 5px;
      z-index: 1000;
      flex-direction: column;
      min-width: 160px;
      box-shadow: 0 2px 10px var(--shadow);
    }

    .user-dropdown.active .dropdown-menu {
      display: flex;
    }

    .dropdown-menu a {
      padding: 6px 12px;
      color: var(--deep-teal);
      text-decoration: none;
      white-space: nowrap;
      border-radius: 4px;
      transition: background 0.2s, color 0.2s;
    }

    .dropdown-menu a:hover {
      background-color: var(--deep-teal);
      color: var(--ivory);
    }

    .mobile-menu {
      display: none;
    }

    @media (max-width: 768px) {
      .mobile-menu {
        display: block;
        font-size: 24px;
        cursor: pointer;
      }

      .nav-links {
        display: none;
      }
    }
  </style>
</head>
<body>
  <header>
    <nav class="navbar">
      <div class="logo">
        <!-- Cool Book Logo SVG with new colors -->
        <svg width="40" height="40" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:block;">
          <rect x="8" y="12" width="20" height="40" rx="4" fill="#D4A017" stroke="#006E8C" stroke-width="2"/>
          <rect x="36" y="12" width="20" height="40" rx="4" fill="#00B4CC" stroke="#0A3D54" stroke-width="2"/>
          <path d="M28 16 Q32 20 36 16" stroke="#F9F6EF" stroke-width="2" fill="none"/>
          <path d="M28 24 Q32 28 36 24" stroke="#F9F6EF" stroke-width="2" fill="none"/>
          <path d="M28 32 Q32 36 36 32" stroke="#F9F6EF" stroke-width="2" fill="none"/>
          <path d="M28 40 Q32 44 36 40" stroke="#F9F6EF" stroke-width="2" fill="none"/>
          <rect x="8" y="12" width="48" height="40" rx="8" stroke="#B88B4A" stroke-width="2" fill="none"/>
          <ellipse cx="32" cy="52" rx="18" ry="3" fill="#0A3D54" opacity="0.18"/>
          <path d="M28 12 Q32 8 36 12" stroke="#006E8C" stroke-width="2" fill="none"/>
        </svg>
        <strong>BookHive</strong>
      </div>

      <div class="search-bar">
        <form action="/project/bookhive/search.php" method="get" autocomplete="off" style="position:relative;">
          <input type="text" id="header-search" name="q" placeholder="Search for books..." autocomplete="off" />
          <button type="submit"><i class="fas fa-search"></i></button>
          <ul id="header-search-suggestions"></ul>
        </form>
      </div>

      <div class="nav-links">
        <ul>
          <li><a href="/project/bookhive/"><i class="fas fa-home"></i> Home</a></li>
          <li><a href="/project/bookhive/categories.php"><i class="fas fa-list"></i> Categories</a></li>
          <?php if (isset($_SESSION['email'])): ?>
            <li class="user-dropdown">
              <div class="user-profile">
                <span><?php echo htmlspecialchars($displayName); ?></span>
                <?php echo $profileImage; ?>
              </div>
              <div class="dropdown-menu">
                <a href="/project/bookhive/user/dashboard.php"><i class="fas fa-user"></i> Dashboard</a>
                <a href="/project/bookhive/user/library.php"><i class="fas fa-book"></i> My Library</a>
                <a href="/project/bookhive/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
              </div>
            </li>
          <?php else: ?>
            <li><a href="/project/bookhive/auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
            <li><a href="/project/bookhive/auth/register.php"><i class="fas fa-user-plus"></i> Register</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="mobile-menu">
        <i class="fas fa-bars"></i>
      </div>
    </nav>
  </header>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const userDropdown = document.querySelector('.user-dropdown');
    
    userDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
        this.classList.toggle('active');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        userDropdown.classList.remove('active');
    });
    
    // --- Search Suggestion Logic for Header (like library.php, but also author/category) ---
    const headerSearchInput = document.getElementById('header-search');
    const headerSuggestionBox = document.getElementById('header-search-suggestions');

    headerSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim().toLowerCase();
        headerSuggestionBox.innerHTML = '';
        if (!searchTerm) {
            headerSuggestionBox.style.display = 'none';
            return;
        }
        fetch('/project/bookhive/search_suggest.php?q=' + encodeURIComponent(searchTerm))
            .then(res => res.json())
            .then(data => {
                headerSuggestionBox.innerHTML = '';
                if (!data.length) {
                    headerSuggestionBox.style.display = 'none';
                    return;
                }
                data.forEach(item => {
                    let html = '';
                    if (item.type === 'book') {
                        // Ensure correct cover path (like index.php)
                        let cover = item.cover;
                        if (cover && !cover.startsWith('/project/bookhive/')) {
                            cover = '/project/bookhive/' + cover.replace(/^\/+/, '');
                        }
                        html = `<li data-type="book" data-id="${item.id}">
                            <img src="${cover}" alt="" onerror="this.src='/project/bookhive/uploads/covers/default-cover.jpg'" />
                            <span>
                                <span class="suggest-title">${item.title}</span><br>
                                <span class="suggest-author">${item.author}</span><br>
                                <span class="suggest-category">${item.category}</span>
                            </span>
                        </li>`;
                    } else if (item.type === 'author') {
                        html = `<li data-type="author" data-id="0">
                            <span class="suggest-icon"><i class="fas fa-user"></i> ${item.author}</span>
                        </li>`;
                    } else if (item.type === 'category') {
                        html = `<li data-type="category" data-id="${item.id}">
                            <span class="suggest-icon"><i class="fas fa-folder-open"></i> ${item.category}</span>
                        </li>`;
                    }
                    headerSuggestionBox.innerHTML += html;
                });
                headerSuggestionBox.style.display = 'block';
            });
    });

    // Handle suggestion click
    headerSuggestionBox.addEventListener('mousedown', function(e) {
        let li = e.target.closest('li');
        if (!li) return;
        const type = li.getAttribute('data-type');
        const id = li.getAttribute('data-id');
        if (type === 'book') {
            window.location.href = '/project/bookhive/books/view.php?id=' + id;
        } else if (type === 'category') {
            window.location.href = '/project/bookhive/categories.php?id=' + id;
        } else if (type === 'author') {
            const author = li.textContent.trim();
            window.location.href = '/project/bookhive/search.php?q=' + encodeURIComponent(author);
        }
    });

    // Hide suggestions on blur (with delay for click)
    headerSearchInput.addEventListener('blur', function() {
        setTimeout(() => { headerSuggestionBox.style.display = 'none'; }, 150);
    });
    headerSearchInput.addEventListener('focus', function() {
        if (this.value.trim().length > 0) headerSuggestionBox.style.display = 'block';
    });
});
</script>
</body>
</html>
