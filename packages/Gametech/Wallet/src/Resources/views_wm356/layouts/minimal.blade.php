<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 - Page Not Found</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: #f2f2f2;
            font-family: 'Segoe UI', sans-serif;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
        }

        .svg-img {
            width: 100%;
            max-width: 300px;
            margin: 0 auto 20px;
        }

        h1 {
            font-size: 4rem;
            color: #ff6b6b;
        }

        h2 {
            font-size: 2rem;
            margin: 10px 0;
        }

        p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            color: #666;
        }

        a {
            text-decoration: none;
            background-color: #ff6b6b;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        a:hover {
            background-color: #e55050;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="svg-img">
        <!-- SVG รูปเครื่องบินชนภูเขาเล็กน้อย -->
        <svg viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M256 32C132.3 32 32 132.3 32 256s100.3 224 224 224 224-100.3 224-224S379.7 32 256 32zm108 244h-72v72c0 6.6-5.4 12-12 12h-48c-6.6 0-12-5.4-12-12v-72h-72c-6.6 0-12-5.4-12-12v-48c0-6.6 5.4-12 12-12h72v-72c0-6.6 5.4-12 12-12h48c6.6 0 12 5.4 12 12v72h72c6.6 0 12 5.4 12 12v48c0 6.6-5.4 12-12 12z" fill="#ff6b6b"/>
        </svg>
    </div>
    <h1>@yield('code')</h1>
    <h2>Oops! Page Not Found</h2>
    <p>It looks like the page you're looking for doesn't exist or has been moved.</p>
    <a href="/">Return to Homepage</a>
</div>
</body>
</html>