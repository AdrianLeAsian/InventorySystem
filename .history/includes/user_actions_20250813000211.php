<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized access.';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$is_admin = ($_SESSION['role'] === 'admin');
$logged_in_user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_user':
            $user_id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            $role = trim($_POST['role']);

            if (!$user_id || empty($username)) {
                $response['message'] = 'Invalid user ID or username.';
                break;
            }

            // Authorization check for update
            if (!$is_admin && $user_id != $logged_in_user_id) {
                $response['message'] = 'Forbidden: You can only update your own account.';
                http_response_code(403);
                break;
            }

            // Non-admin users cannot change their role or other users' roles
            if (!$is_admin && $role !== 'user') { // Assuming 'user' is the only role non-admins can have
                $response['message'] = 'Forbidden: You cannot change your role.';
                http_response_code(403);
                break;
            }
            
            // Prevent non-admins from changing other users' roles
            if (!$is_admin && $user_id != $logged_in_user_id && $role !== 'user') {
                $response['message'] = 'Forbidden: You cannot change other users\' roles.';
                http_response_code(403);
                break;
            }

            $sql = 'UPDATE users SET username = ?';
            $params = 's';
            $values = [$username];

            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ', password = ?';
                $params .= 's';
                $values[] = $hash;
            }

            // Only allow admin to change role
            if ($is_admin) {
                $sql .= ', role = ?';
                $params .= 's';
                $values[] = $role;
            }

            $sql .= ' WHERE id = ?';
            $params .= 'i';
            $values[] = $user_id;

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($params, ...$values);
                if ($stmt->execute()) {
                    $response = ['status' => 'success', 'message' => 'User updated successfully.'];
                } else {
                    $response['message'] = 'Failed to update user: ' . $stmt->error;
                }
            } else {
                $response['message'] = 'Failed to prepare statement: ' . $conn->error;
            }
            break;

        case 'delete_user':
            $user_id = filter_var($_POST['id'], FILTER_VALIDATE_INT);

            if (!$user_id) {
                $response['message'] = 'Invalid user ID.';
                break;
            }

            // Authorization check for delete
            if (!$is_admin) {
                $response['message'] = 'Forbidden: You do not have permission to delete users.';
                http_response_code(403);
                break;
            }

            // Prevent admin from deleting their own account
            if ($user_id == $logged_in_user_id) {
                $response['message'] = 'Forbidden: You cannot delete your own account.';
                http_response_code(403);
                break;
            }

            $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $user_id);
                if ($stmt->execute()) {
                    $response = ['status' => 'success', 'message' => 'User deleted successfully.'];
                } else {
                    $response['message'] = 'Failed to delete user: ' . $stmt->error;
                }
            } else {
                $response['message'] = 'Failed to prepare statement: ' . $conn->error;
            }
            break;
    }
}

echo json_encode($response);
?>
