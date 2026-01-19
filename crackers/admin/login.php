<?php
session_start();
require '../inc/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $stmt = $pdo->prepare("SELECT * FROM login WHERE username=? AND password=?");
    $stmt->execute([$username, $password]);

    if ($stmt->rowCount() > 0) {
        $_SESSION["admin"] = $username;
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --amazon-orange: #ff9900;
            --amazon-dark: #232f3e;
            --amazon-blue: #146eb4;
            --amazon-light: #fafafa;
            --amazon-text: #0f1111;
        }
        
        body {
            background-color: var(--amazon-light);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #d5dbdb;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            background: var(--amazon-dark);
            color: white;
            padding: 2rem;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        
        .logo {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--amazon-orange);
        }
        
        .login-title {
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--amazon-text);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 1px solid #a6a6a6;
            border-radius: 4px;
            padding: 0.75rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--amazon-orange);
            box-shadow: 0 0 0 3px rgba(255, 153, 0, 0.1);
        }
        
        .btn-login {
            background-color: var(--amazon-orange);
            color: white;
            border: 1px solid var(--amazon-orange);
            padding: 0.75rem;
            font-weight: 500;
            width: 100%;
            transition: background-color 0.2s;
        }
        
        .btn-login:hover {
            background-color: #e68900;
            border-color: #e68900;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-store logo"></i>
            <h1 class="login-title">Admin Login</h1>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required placeholder="Enter username">
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control" required placeholder="Enter password">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>