<?php
$servername = "localhost";
$username = "root";
$password = ""; // Your database password
$dbname = "bookhive"; // Replace with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Active time threshold: last 15 minutes
$threshold = date('Y-m-d H:i:s', strtotime('-15 minutes'));

$sql = "SELECT username, email FROM signup WHERE last_active >= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $threshold);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Active Users | BookHive</title>
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
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-image: radial-gradient(circle at 10% 20%, rgba(67, 97, 238, 0.1) 0%, rgba(248, 249, 250, 1) 90%);
        }
        
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        h2 {
            color: var(--secondary);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            color: var(--dark);
            font-size: 1rem;
            opacity: 0.8;
        }
        
        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 500;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:hover {
            background-color: #e9f7fe;
        }
        
        .no-data {
            text-align: center;
            padding: 2rem;
            color: var(--dark);
            opacity: 0.7;
        }
        
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: var(--success);
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .active-user {
            display: flex;
            align-items: center;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            th, td {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Active Users</h2>
            <p class="subtitle">Users active in the last 15 minutes</p>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="active-user">
                                    <span class="status-indicator"></span>
                                    <?php echo htmlspecialchars($row['username']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="no-data">No active users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>