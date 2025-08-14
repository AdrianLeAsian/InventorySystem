<?php
session_start(); // Ensure session is started
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Check user role
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php'); // Redirect non-admins to dashboard
    exit;
}
include 'includes/db.php';
$error = '';
$success = '';

// Handle user addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']); // Get the role from the form

    // Input validation for add_user
    if (empty($username) || empty($password) || empty($role)) {
        $error = 'All fields are required for adding a user.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } elseif (strlen($password) < 6) { // Stronger password requirement
        $error = 'Password must be at least 6 characters long.';
    } elseif (!in_array($role, ['user', 'admin'])) {
        $error = 'Invalid role specified.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        // Check if username already exists
        $checkStmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
        $checkStmt->bind_param('s', $username);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $error = 'Username already exists.';
        } else {
            $stmt = $conn->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $username, $hash, $role);
            if ($stmt->execute()) {
                $success = 'User added successfully!';
            } else {
                $error = 'Failed to add user.';
            }
        }
        $checkStmt->close();
    }
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    $user_id = intval($_POST['user_id']); // Ensure user_id is an integer
    $new_role = trim($_POST['role']);

    // Input validation for update_role
    if ($user_id <= 0) {
        $error = 'Invalid user ID.';
    } elseif (!in_array($new_role, ['user', 'admin'])) {
        $error = 'Invalid role specified.';
    } else {
        $stmt = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->bind_param('si', $new_role, $user_id);
        if ($stmt->execute()) {
            $success = 'User role updated successfully!';
        } else {
            $error = 'Failed to update user role.';
        }
    }
}

// Fetch all users for display
$users = [];
$userStmt = $conn->prepare("SELECT id, username, role FROM users");
$userStmt->execute();
$userResult = $userStmt->get_result();
while ($row = $userResult->fetch_assoc()) {
    $users[] = $row;
}
$userStmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Styles to match the system's GUI */
        .main-content { /* Use main-content styling */
            padding: 20px; /* Adjust padding as needed */
            margin-left: 220px; /* Assuming sidebar width */
            background-color: var(--light-bg, #f4f7f6); /* Use system background */
            min-height: 100vh;
            box-sizing: border-box;
        }
        .main-content h2 {
            margin-bottom: 20px;
            color: var(--primary-dark, #34495E);
            font-size: 1.8em;
        }
        .user-management-section {
            background-color: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(52,73,94,0.08);
            margin-bottom: 24px;
        }
        .user-management-section h3 {
            margin-top: 0;
            margin-bottom: 16px;
            color: var(--primary-dark, #34495E);
            font-size: 1.4em;
        }
        .add-user-form {
            display: flex;
            gap: 16px;
            align-items: flex-end;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .add-user-form > div {
            flex: 1;
        }
        .add-user-form input[type="text"],
        .add-user-form input[type="password"],
        .add-user-form select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            font-family: 'Quicksand', sans-serif;
            margin-bottom: 0; /* Remove margin for form elements within flex container */
        }
        .add-user-form button {
            padding: 10px 20px;
            /* Use btn-primary style if available in style.css */
            background: var(--success, #7C9885); /* Default to success color if var not found */
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-family: 'Quicksand', sans-serif;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(52,73,94,0.08);
            transition: background 0.2s;
        }
        .add-user-form button:hover {
            background: var(--primary, #4CAF50); /* Default to primary color if var not found */
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .user-table th, .user-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .user-table th {
            background-color: var(--primary, #4CAF50); /* Use primary color for header */
            color: #fff;
            font-family: 'Quicksand', sans-serif;
        }
        .user-table td {
            vertical-align: middle;
        }
        .user-table select {
            margin-bottom: 0; /* Remove bottom margin for selects in table cells */
            width: auto; /* Allow select to size to content */
            padding: 8px 12px;
            border-radius: 4px;
        }
        .action-buttons button {
            margin-right: 5px;
            padding: 6px 12px;
            font-size: 0.9em;
            border-radius: 4px;
        }
        .action-buttons button:last-child {
            margin-right: 0;
        }
        /* Specific button styles if needed, otherwise rely on btn-primary etc. */
        .btn-save-role {
            background-color: var(--primary, #4CAF50);
            color: white;
        }
        .btn-save-role:hover {
            background-color: var(--primary-dark, #367c39);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>
    <div class="main-content">
        <div class="user-management-section">
            <h2>User Management</h2>

            <!-- Add New User Form -->
            <div class="add-user-form">
                <div>
                    <label>Username</label>
                    <input type="text" id="new-username" required autofocus>
                </div>
                <div>
                    <label>Password</label>
                    <input type="password" id="new-password" required>
                </div>
                <div>
                    <label>Role</label>
                    <select id="new-role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button onclick="addUser()" class="btn-primary">Add User</button>
            </div>

            <?php if ($error) echo '<div class="error">' . htmlspecialchars($error) . '</div>'; ?>
            <?php if ($success) echo '<div class="success">' . htmlspecialchars($success) . '</div>'; ?>

            <!-- User List Table -->
            <h3>Existing Users</h3>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td>
                                <select class="role-select" data-user-id="<?= $user['id'] ?>">
                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </td>
                            <td class="action-buttons">
                                <button onclick="updateUserRole(<?= $user['id'] ?>)" class="btn-primary btn-save-role">Save Role</button>
                                <!-- Add delete button if needed in future -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include 'includes/modals.php'; ?>

    <script>
        function addUser() {
            const username = document.getElementById('new-username').value;
            const password = document.getElementById('new-password').value;
            const role = document.getElementById('new-role').value;

            const formData = new FormData();
            formData.append('action', 'add_user');
            formData.append('username', username);
            formData.append('password', password);
            formData.append('role', role);

            fetch('users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Reload the page to show the new user and updated messages
                location.reload();
            });
        }

        function updateUserRole(userId) {
            const roleSelect = document.querySelector(`.role-select[data-user-id="${userId}"]`);
            const newRole = roleSelect.value;

            const formData = new FormData();
            formData.append('action', 'update_role');
            formData.append('user_id', userId);
            formData.append('role', newRole);

            fetch('users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Reload the page to reflect the change and show messages
                location.reload();
            });
        }
    </script>
</body>
</html>
