<?php
include 'includes/auth.php';
include 'includes/db.php';

$current_page = basename($_SERVER['PHP_SELF']); // Get the current page filename

include 'includes/header.php'; // Include header for consistent layout
include 'includes/sidebar.php'; // Include sidebar for navigation

// Check if the logged-in user is an admin
$is_admin = ($_SESSION['role'] === 'admin');

$error = '';
$success = '';

// Handle Add User form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    if (!$is_admin) {
        // Non-admin users cannot create accounts
        $error = 'You do not have permission to add new users.';
    } else {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $role = trim($_POST['role']); // Get role from form

        if (strlen($username) < 3 || strlen($password) < 4) {
            $error = 'Username must be at least 3 characters and password at least 4.';
        } else {
            $stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error = 'Username already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
                $stmt->bind_param('sss', $username, $hash, $role);
                if ($stmt->execute()) {
                    $success = 'User added successfully!';
                } else {
                    $error = 'Failed to create account.';
                }
            }
        }
    }
}

// Fetch users for display
$users = [];
if ($is_admin) {
    // Admins can see all users
    $stmt = $conn->prepare('SELECT id, username, role FROM users');
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
} else {
    // Non-admins can only see their own account
    $stmt = $conn->prepare('SELECT id, username, role FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $users[] = $row;
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
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .user-management-container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .user-management-container h2 {
            color: var(--primary);
            margin-bottom: 25px;
            text-align: center;
        }
        .user-management-container .error {
            color: var(--danger);
            text-align: center;
            margin-bottom: 15px;
        }
        .user-management-container .success {
            color: var(--success);
            text-align: center;
            margin-bottom: 15px;
        }
        .add-user-form {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .add-user-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .add-user-form input[type="text"],
        .add-user-form input[type="password"],
        .add-user-form select {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .add-user-form button {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .add-user-form button:hover {
            background: var(--primary-dark);
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .users-table th, .users-table td {
            border: 1px solid #eee;
            padding: 12px 15px;
            text-align: left;
        }
        .users-table th {
            background-color: var(--secondary);
            color: #fff;
            font-weight: bold;
        }
        .users-table tr:nth-child(even) {
            background-color: #f8f8f8;
        }
        .users-table tr:hover {
            background-color: #f1f1f1;
        }
        .users-table .actions button {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            margin-right: 5px;
            transition: background-color 0.3s ease;
        }
        .users-table .actions button.delete {
            background: var(--danger);
        }
        .users-table .actions button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="user-management-container">
            <h2>User Management</h2>

            <?php if ($error) echo '<div class="error">' . htmlspecialchars($error) . '</div>'; ?>
            <?php if ($success) echo '<div class="success">' . htmlspecialchars($success) . '</div>'; ?>

            <?php if ($is_admin): // Only show add user form if admin ?>
            <div class="add-user-form">
                <h3>Add New User</h3>
                <form method="post">
                    <input type="hidden" name="action" value="add_user">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                    <button type="submit">Add User</button>
                </form>
            </div>
            <?php endif; ?>

            <h3>Existing Users</h3>
            <?php if (empty($users)): ?>
                <p>No users found.</p>
            <?php else: ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td class="actions">
                                <?php if ($is_admin || $_SESSION['user_id'] == $user['id']): ?>
                                <button class="edit-user-btn" data-id="<?php echo $user['id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>" data-role="<?php echo htmlspecialchars($user['role']); ?>">Edit</button>
                                <?php endif; ?>
                                <?php if ($is_admin && $_SESSION['user_id'] != $user['id']): // Admins can delete others, but not themselves ?>
                                <button class="delete-user-btn delete" data-id="<?php echo $user['id']; ?>">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Edit User</h2>
            <form id="editUserForm">
                <input type="hidden" id="editUserId" name="id">
                <label for="editUsername">Username</label>
                <input type="text" id="editUsername" name="username" required>
                <label for="editPassword">New Password (leave blank to keep current)</label>
                <input type="password" id="editPassword" name="password">
                <label for="editRole">Role</label>
                <select id="editRole" name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editUserModal = document.getElementById('editUserModal');
            const closeButton = editUserModal.querySelector('.close-button');
            const editUserButtons = document.querySelectorAll('.edit-user-btn');
            const editUserForm = document.getElementById('editUserForm');

            editUserButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.dataset.id;
                    const username = this.dataset.username;
                    const role = this.dataset.role;

                    document.getElementById('editUserId').value = userId;
                    document.getElementById('editUsername').value = username;
                    document.getElementById('editRole').value = role;

                    // Disable role selection for non-admins editing their own account
                    const isAdmin = <?php echo json_encode($is_admin); ?>;
                    const loggedInUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
                    if (!isAdmin && userId == loggedInUserId) {
                        document.getElementById('editRole').disabled = true;
                    } else {
                        document.getElementById('editRole').disabled = false;
                    }

                    editUserModal.style.display = 'block';
                });
            });

            closeButton.addEventListener('click', function() {
                editUserModal.style.display = 'none';
            });

            window.addEventListener('click', function(event) {
                if (event.target == editUserModal) {
                    editUserModal.style.display = 'none';
                }
            });

            editUserForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(editUserForm);
                formData.append('action', 'update_user');

                // Re-enable role select before sending if it was disabled for non-admin
                if (document.getElementById('editRole').disabled) {
                    document.getElementById('editRole').disabled = false;
                }

                fetch('includes/user_actions.php', { // Assuming user_actions.php will handle user CRUD
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload(); // Reload page to show updated list
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the user.');
                });
            });

            // Delete User functionality
            document.querySelectorAll('.delete-user-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.dataset.id;
                    if (confirm('Are you sure you want to delete this user?')) {
                        const formData = new FormData();
                        formData.append('action', 'delete_user');
                        formData.append('id', userId);

                        fetch('includes/user_actions.php', { // Assuming user_actions.php will handle user CRUD
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert(data.message);
                                location.reload(); // Reload page to show updated list
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while deleting the user.');
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
