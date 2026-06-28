<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        footer {
            background-color: var(--ivory);
            color: #0A3D54;
            padding: 40px 0 20px;
            font-family: 'Open Sans', sans-serif;
            line-height: 1.6;
            width: 100%;
            margin: 0;
            border-top: 2px ;
        }

        .footer-container {
            display: flex;
            justify-content: space-between;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            flex-wrap: wrap;
            gap: 30px;
        }

        .footer-section {
            flex: 1;
            min-width: 200px;
            max-width: 300px;
        }

        footer h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--ocean);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .footer-about p {
            font-size: 14px;
            margin-bottom: 0;
            color: #0A3D54;
        }

        .footer-links ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: #0A3D54;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
            display: block;
        }

        .footer-links a:hover {
            color: var(--sea-green);
        }

        .social-icons {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .social-icons a {
            color: var(--deep-teal);
            font-size: 22px;
            transition: color 0.3s;
        }

        .social-icons a:hover {
            color: var(--sea-green);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid var(--parchment);
            font-size: 14px;
            width: 100%;
            color: var(--ocean);
            background: none;
        }

        @media (max-width: 768px) {
            .footer-container {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .footer-section {
                margin-bottom: 30px;
                max-width: 100%;
            }

            .social-icons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <footer>
        <div class="footer-container">
            <div class="footer-section footer-about">
                <h3>About BookHive</h3>
                <p>BookHive is your ultimate online destination for reading and discovering books. Explore thousands of titles across various genres.</p>
            </div>
            <div class="footer-section footer-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="/project/bookhive/index.php">Home</a></li>
                    <li><a href="/project/bookhive/categories.php">Categories</a></li>
                    <li><a href="/project/bookhive/authors.php">Authors</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
            <div class="footer-section footer-social">
                <h3>Connect With Us</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-goodreads"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 BookHive. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>
