<?php
session_start();
// Optional: Redirect to login if not logged in
if (!isset($_SESSION['admin_logged_in'])) {
   header("Location: adminlogin.php");
   exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        :root {
            --primary: #4361ee;       /* Royal Blue */
            --secondary: #3a0ca3;    /* Dark Blue */
            --accent: #f72585;       /* Pink */
            --light: #f8f9fa;        /* Light Gray */
            --dark: #212529;        /* Dark Gray */
            --complementary: #4cc9f0; /* Sky Blue */
            --success: #4ad66d;      /* Green */
            --warning: #f8961e;      /* Orange */
            --card-color: #fff;
            --sidebar-hover: rgba(255,255,255,0.1);
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            display: flex;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar h2 {
            font-size: 1.5rem;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
            color: white;
        }

        .sidebar button {
            background: none;
            border: none;
            color: white;
            font-size: 1rem;
            margin: 8px 0;
            cursor: pointer;
            text-align: left;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            font-weight: 500;
        }

        .sidebar button:hover {
            background-color: var(--sidebar-hover);
            transform: translateX(5px);
        }

        .sidebar svg {
            width: 20px;
            height: 20px;
            transition: transform 0.3s ease;
        }

        .main {
            margin-left: 270px;
            padding: 30px;
            flex-grow: 1;
            width: calc(100% - 270px);
        }

        h1 {
            text-align: center;
            margin-bottom: 40px;
            color: var(--primary);
            font-weight: 600;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: var(--card-color);
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 30px;
            transition: all 0.3s ease;
            cursor: pointer;
            color: var(--dark);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            border-color: var(--complementary);
        }

        .card svg {
            width: 60px;
            height: 60px;
            margin-bottom: 20px;
        }

        .label {
            font-weight: 600;
            font-size: 1rem;
            color: var(--dark);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .logout-btn {
            background-color: rgba(239, 35, 60, 0.1) !important;
            color: #ef233c !important;
            margin-top: 20px !important;
        }

        .logout-btn:hover {
            background-color: rgba(239, 35, 60, 0.2) !important;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
                padding: 15px;
            }
            .main {
                margin-left: 210px;
                padding: 15px;
                width: calc(100% - 210px);
            }
            .grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }
        }
    </style>
</head>
<body class="light-mode">
    <div class="sidebar">
        <div>
            <h2>Admin Dashboard</h2> 

            <form action="adminlogout.php" method="POST" style="margin: 0;">
                <button type="submit" class="logout-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M16 17v-3h-9v-4h9V7l5 5-5 5zm-9-9h9V4H4v16h11v-4h2v5H2V3h15v5h-2z"/>
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>

    <div class="main">
        <h1>Admin Dashboard</h1>
        <div class="grid">
            <a href="addbook.php">
                <div class="card">
                    <svg viewBox="0 0 24 24" fill="#4CAF50">
                        <path d="M18 2h-12a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-16a2 2 0 0 0-2-2zm-1 9h-4v4h-2v-4h-4v-2h4v-4h2v4h4v2z"/>
                    </svg>
                    <div class="label">Add Book</div>
                </div>
            </a>

           

            <a href="updatebook.php">
                <div class="card">
                    <svg viewBox="0 0 24 24" fill="#9C27B0">
                        <path d="M3 17.25v3.75h3.75l11.06-11.06-3.75-3.75-11.06 11.06zm17.71-10.21a1.003 1.003 0 0 0 0-1.42l-2.34-2.34a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z"/>
                    </svg>
                    <div class="label">Update Book</div>
                </div>
            </a>

            <a href="addcategory.php">
                <div class="card">
                    <svg viewBox="0 0 24 24" fill="#FF9800">
                        <path d="M19 13h-6v6h-2v-6h-6v-2h6v-6h2v6h6v2z"/>
                        <path d="M4 4h6v6h-6zM14 4h6v6h-6zM4 14h6v6h-6zM14 14h6v6h-6z"/>
                    </svg>
                    <div class="label">Add Category</div>
                </div>
            </a>

            <a href="addauthor.php">
                <div class="card">
                    <svg viewBox="0 0 24 24" fill="#673AB7">
                        <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-3.33 0-10 1.67-10 5v2h20v-2c0-3.33-6.67-5-10-5z"/>
                    </svg>
                    <div class="label">Add Author</div>
                </div>
            </a>

            <a href="activeusers.php">
                <div class="card">
                    <svg viewBox="0 0 24 24" fill="#03A9F4">
                        <path d="M16 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm0 2c-1.33 0-4 .67-4 2v1h8v-1c0-1.33-2.67-2-4-2zm-8-3a3 3 0 1 0-3-3 3 3 0 0 0 3 3zm0 2c-1.33 0-4 .67-4 2v1h8v-1c0-1.33-2.67-2-4-2z"/>
                    </svg>
                    <div class="label">Active Users</div>
                </div>
            </a>

            <a href="monthlyusers.php">
                <div class="card">
                    <svg viewBox="0 0 24 24" fill="#4CAF50">
                        <path d="M19 3h-1v1h1v1h-1v1h1v1h-1v1h1v1h-1v1h1v1h-1v1h1v1h-1v1h1v1h-14v-1h-1v-1h1v-1h-1v-1h1v-1h-1v-1h1v-1h-1v-1h1v-1h-1v-1h1v-1h-1v-1h14v1h1v1h-1v1zm-1 16h-12v-14h12v14z"/>
                        <path d="M8 7h2v2h-2zM11 7h2v2h-2zM14 7h2v2h-2zM8 10h2v2h-2zM11 10h2v2h-2zM14 10h2v2h-2zM8 13h2v2h-2zM11 13h2v2h-2zM14 13h2v2h-2zM8 16h2v2h-2zM11 16h2v2h-2zM14 16h2v2h-2z"/>
                    </svg>
                    <div class="label">Monthly Users</div>
                </div>
            </a>
        </div>
    </div>
</body>
</html>