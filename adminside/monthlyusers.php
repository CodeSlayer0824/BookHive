<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "bookhive";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch monthly registrations
$sql1 = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS count
         FROM signup
         GROUP BY month ORDER BY month";
$result1 = $conn->query($sql1);

$registrations = [["Month", "New Users"]];
while ($row = $result1->fetch_assoc()) {
    $registrations[] = [$row['month'], (int)$row['count']];
}

// Fetch monthly active users
$sql2 = "SELECT DATE_FORMAT(last_active, '%Y-%m') AS month, COUNT(*) AS count
         FROM signup
         WHERE last_active IS NOT NULL
         GROUP BY month ORDER BY month";
$result2 = $conn->query($sql2);

$activeUsers = [["Month", "Active Users"]];
while ($row = $result2->fetch_assoc()) {
    $activeUsers[] = [$row['month'], (int)$row['count']];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Users Overview | BookHive</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart', 'bar']});
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            var registrationData = google.visualization.arrayToDataTable(<?php echo json_encode($registrations); ?>);
            var activeUserData = google.visualization.arrayToDataTable(<?php echo json_encode($activeUsers); ?>);

            var regOptions = {
                title: 'Monthly New Users',
                titleTextStyle: {
                    color: '#3a0ca3',
                    fontSize: 18,
                    bold: true
                },
                legend: { position: 'none' },
                bars: 'vertical',
                colors: ['#4361ee'],
                hAxis: { 
                    title: 'Month',
                    titleTextStyle: {color: '#3a0ca3'},
                    textStyle: {color: '#212529'}
                },
                vAxis: { 
                    title: 'New Users',
                    titleTextStyle: {color: '#3a0ca3'},
                    textStyle: {color: '#212529'}
                },
                backgroundColor: '#f8f9fa',
                chartArea: {backgroundColor: '#ffffff'},
                bar: {groupWidth: '75%'}
            };

            var activeOptions = {
                title: 'Monthly Active Users',
                titleTextStyle: {
                    color: '#3a0ca3',
                    fontSize: 18,
                    bold: true
                },
                colors: ['#4361ee', '#4cc9f0', '#f72585', '#f8961e', '#4ad66d'],
                backgroundColor: '#f8f9fa',
                chartArea: {backgroundColor: '#ffffff'},
                legend: {
                    textStyle: {color: '#212529'}
                },
                pieHole: 0.4,
                is3D: true
            };

            var regChart = new google.visualization.ColumnChart(document.getElementById('registration_chart'));
            regChart.draw(registrationData, regOptions);

            var activeChart = new google.visualization.PieChart(document.getElementById('activeuser_chart'));
            activeChart.draw(activeUserData, activeOptions);
        }
    </script>
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
            width: 90%;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem;
            text-align: center;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .page-header h1 {
            margin: 0;
            font-weight: 600;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .chart-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
            margin-top: 1rem;
        }
        
        .chart-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            height: 500px;
        }
        
        #registration_chart, #activeuser_chart {
            width: 100%;
            height: 100%;
        }
        
        .chart-title {
            color: var(--secondary);
            margin-bottom: 1rem;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        @media (max-width: 768px) {
            .container {
                width: 95%;
            }
            
            .chart-container {
                grid-template-columns: 1fr;
            }
            
            .chart-card {
                height: 400px;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                Monthly Users Overview
            </h1>
        </div>
        
        <div class="chart-container">
            <div class="chart-card">
                <div id="registration_chart"></div>
            </div>
            <div class="chart-card">
                <div id="activeuser_chart"></div>
            </div>
        </div>
    </div>
</body>
</html>