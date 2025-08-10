<?php
include_once __DIR__ . '/../includes/header.php';
// Fetch current email settings
$settings_stmt = $conn->prepare("SELECT * FROM email_settings ORDER BY id DESC LIMIT 1");
$settings_stmt->execute();
$settings_result = $settings_stmt->get_result();
$settings = $settings_result->fetch_assoc();

// Fetch all email recipients
$recipients_stmt = $conn->prepare("SELECT * FROM email_recipients ORDER BY name ASC");
$recipients_stmt->execute();
$recipients_result = $recipients_stmt->get_result();
$recipients = $recipients_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Email Notification Settings</h1>

    <!-- SMTP Settings Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">SMTP Configuration</h6>
        </div>
        <div class="card-body">
            <form id="smtp-settings-form">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="smtp_host">SMTP Host</label>
                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="smtp_port">SMTP Port</label>
                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="smtp_username">SMTP Username</label>
                        <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="smtp_password">SMTP Password</label>
                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="sender_email">Sender Email</label>
                        <input type="email" class="form-control" id="sender_email" name="sender_email" value="<?= htmlspecialchars($settings['sender_email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="sender_name">Sender Name</label>
                        <input type="text" class="form-control" id="sender_name" name="sender_name" value="<?= htmlspecialchars($settings['sender_name'] ?? '') ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Settings</button>
                <button type="button" id="send-test-email" class="btn btn-info">Send Test Email</button>
            </form>
        </div>
    </div>

    <!-- Email Recipients Management -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Notification Recipients</h6>
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#add-recipient-modal">Add Recipient</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="recipients-table" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recipients as $recipient): ?>
                            <tr data-id="<?= $recipient['id'] ?>">
                                <td><?= htmlspecialchars($recipient['name']) ?></td>
                                <td><?= htmlspecialchars($recipient['email']) ?></td>
                                <td>
                                    <button class="btn btn-info btn-sm edit-recipient-btn">Edit</button>
                                    <button class="btn btn-danger btn-sm delete-recipient-btn">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Recipient Modal -->
<div class="modal fade" id="add-recipient-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Recipient</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="add-recipient-form">
                    <div class="form-group">
                        <label for="recipient_name">Name</label>
                        <input type="text" class="form-control" id="recipient_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="recipient_email">Email</label>
                        <input type="email" class="form-control" id="recipient_email" name="email" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="save-recipient-btn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Recipient Modal -->
<div class="modal fade" id="edit-recipient-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Recipient</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="edit-recipient-form">
                    <input type="hidden" id="edit_recipient_id" name="id">
                    <div class="form-group">
                        <label for="edit_recipient_name">Name</label>
                        <input type="text" class="form-control" id="edit_recipient_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_recipient_email">Email</label>
                        <input type="email" class="form-control" id="edit_recipient_email" name="email" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="update-recipient-btn">Update</button>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
