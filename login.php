<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
include 'includes/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $stmt = $conn->prepare('SELECT id, password FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Inventory Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            max-width: 340px;
            margin: 80px auto;
            background: var(--primary);
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(52,73,94,0.18);
            padding: 36px 32px;
            color: #fff;
        }
        .login-container h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 24px;
        }
        .login-container .error {
            color: #FEC0AA;
            text-align: center;
            margin-bottom: 18px;
        }
        .login-container label {
            display: block;
            margin-bottom: 6px;
            color: #fff;
            font-weight: 700;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 18px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            font-family: 'Quicksand', sans-serif;
            background: #fff;
            color: #34495E;
        }
        .login-container input[type="text"]::placeholder,
        .login-container input[type="password"]::placeholder {
            color: #aaa;
        }
        .login-container button {
            width: 100%;
            background: #fff;
            color: var(--primary);
            border: none;
            border-radius: 8px;
            padding: 12px 0;
            font-size: 1em;
            font-family: 'Quicksand', sans-serif;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(52,73,94,0.08);
            transition: background 0.2s, color 0.2s;
        }
        .login-container button:hover {
            background: var(--primary-light);
            color: #fff;
        }
    </style>
</head>
<body>
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--light-bg);">
        <div class="login-container">
        <img src="assets/images/logo.png" alt="Logo" style="display:block;margin:0 auto 16px auto;width:90px;border-radius:8px;box-shadow:0 2px 8px rgba(52,73,94,0.08);">
    <div style="text-align:center; margin-bottom:24px; font-size:1.1em; font-weight:700; color:#fff; letter-spacing:1px;">AI Korean Buffet Restaurant</div>
        <h2>Login</h2>
        <?php if ($error) echo '<div class="error">' . htmlspecialchars($error) . '</div>'; ?>
        <form method="post">
            <label>Username</label>
            <input type="text" name="username" required autofocus>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit">Login</button>
        </form>
        <a href="signup.php" style="display:block;text-align:center;margin-top:18px;color:#fff;text-decoration:underline;font-size:0.98em;">Sign up for a new account</a>
        </div>
    </div>
</body>
</html>
