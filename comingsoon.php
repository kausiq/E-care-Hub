<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E Care Hub - Coming Soon</title>
    <link rel="icon" type="image/x-icon" href="image/heart-removebg-preview.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* CSS Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #333;
            line-height: 1.6;
        }

        .container {
            text-align: center;
            max-width: 800px;
            padding: 200px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo {
            font-size: 3rem;
            font-weight: 700;
            color: #4a6bff;
            margin-bottom: 1rem;
        }

        .logo span {
            color: #333;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            color: #555;
        }

        .countdown {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .countdown-item {
            background: #fff;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            min-width: 80px;
        }

        .countdown-number {
            font-size: 2rem;
            font-weight: 700;
            color: #4a6bff;
        }

        .countdown-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #777;
            letter-spacing: 1px;
        }

        .notify-form {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }

        .notify-input {
            padding: 0.8rem 1rem;
            border: 2px solid #ddd;
            border-radius: 50px 0 0 50px;
            width: 60%;
            font-size: 1rem;
            outline: none;
            transition: border 0.3s;
        }

        .notify-input:focus {
            border-color: #4a6bff;
        }

        .notify-button {
            padding: 0.8rem 1.5rem;
            background: #4a6bff;
            color: white;
            border: none;
            border-radius: 0 50px 50px 0;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.3s;
        }

        .notify-button:hover {
            background: #3a5bef;
        }

        .social-links {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: #fff;
            border-radius: 50%;
            color: #4a6bff;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .social-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            color: #3a5bef;
        }

        @media (max-width: 600px) {
            .container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .logo {
                font-size: 2.5rem;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .countdown {
                gap: 1rem;
            }
            
            .notify-form {
                flex-direction: column;
                align-items: center;
            }
            
            .notify-input {
                width: 100%;
                border-radius: 50px;
                margin-bottom: 0.5rem;
            }
            
            .notify-button {
                width: 100%;
                border-radius: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">E Care Hub</div>
        <h1>Coming Soon!</h1>
        <div class="text-center mt-5 mb-4">
            <div class="d-flex justify-content-center gap-3">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>