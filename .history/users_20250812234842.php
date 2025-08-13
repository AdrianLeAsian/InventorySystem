<?php
include 'includes/auth.php';
include 'includes/db.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    if ($username && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
        $stmt->bind_param('ss', $username, $hash);
        if ($stmt->execute()) {
            $success = 'User added successfully!';
        } else {
            $error = 'Username already exists.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .user-container {
            max-width: 400px;
            margin: 60px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(52,73,94,0.18);
            padding: 36px 32px;
        }
        .user-container h2 {
            text-align: center;
            color: #34495E;
            margin-bottom: 24px;
        }
        .user-container .error {
            color: #D33F49;
            text-align: center;
            margin-bottom: 18px;
        }
        .user-container .success {
            color: #7C9885;
            text-align: center;
            margin-bottom: 18px;
        }
        .user-container label {
            display: block;
            margin-bottom: 6px;
            color: #34495E;
            font-weight: 700;
        }
        .user-container input[type="text"],
        .user-container input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 18px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            font-family: 'Quicksand', sans-serif;
        }
        .user-container button {
            width: 100%;
            background: var(--success);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 0;
            font-size: 1em;
            font-family: 'Quicksand', sans-serif;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(52,73,94,0.08);
            transition: background 0.2s;
        }
        .user-container button:hover {
            background: var(--primary);
        }
    </style>
</head>
<body>
    <div class="user-container">
        <h2>Add New User</h2>
        <?php if ($error) echo '<div class="error">' . htmlspecialchars($error) . '</div>'; ?>
        <?php if ($success) echo '<div class="success">' . htmlspecialchars($success) . '</div>'; ?>
        <form method="post">
            <label>Username</label>
            <input type="text" name="username" required autofocus>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit">Add User</button>
        </form>
    </div>
</body>
</html>
