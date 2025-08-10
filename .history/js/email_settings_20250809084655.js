$(document).ready(function() {
    // Save SMTP Settings
    $('#smtp-settings-form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../ajax/save_email_settings.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                alert('SMTP settings saved successfully.');
            },
            error: function() {
                alert('Failed to save SMTP settings.');
            }
        });
    });

    // Send Test Email
    $('#send-test-email').on('click', function() {
        const testEmail = prompt("Enter the email address to send a test email to:");
        if (testEmail) {
            $.ajax({
                url: '../ajax/send_test_email.php',
                type: 'POST',
                data: { email: testEmail },
                success: function(response) {
                    alert('Test email sent successfully.');
                },
                error: function() {
                    alert('Failed to send test email.');
                }
            });
        }
    });

    // Save New Recipient
    $('#save-recipient-btn').on('click', function() {
        $.ajax({
            url: '../ajax/add_email_recipient.php',
            type: 'POST',
            data: $('#add-recipient-form').serialize(),
            success: function(response) {
                alert('Recipient added successfully.');
                location.reload();
            },
            error: function() {
                alert('Failed to add recipient.');
            }
        });
    });

    // Edit Recipient
    $('.edit-recipient-btn').on('click', function() {
        const row = $(this).closest('tr');
        const id = row.data('id');
        const name = row.find('td:eq(0)').text();
        const email = row.find('td:eq(1)').text();

        $('#edit_recipient_id').val(id);
        $('#edit_recipient_name').val(name);
        $('#edit_recipient_email').val(email);

        $('#edit-recipient-modal').modal('show');
    });

    // Update Recipient
    $('#update-recipient-btn').on('click', function() {
        $.ajax({
            url: '../ajax/update_email_recipient.php',
            type: 'POST',
            data: $('#edit-recipient-form').serialize(),
            success: function(response) {
                alert('Recipient updated successfully.');
                location.reload();
            },
            error: function() {
                alert('Failed to update recipient.');
            }
        });
    });

    // Delete Recipient
    $('.delete-recipient-btn').on('click', function() {
        if (confirm('Are you sure you want to delete this recipient?')) {
            const id = $(this).closest('tr').data('id');
            $.ajax({
                url: '../ajax/delete_email_recipient.php',
                type: 'POST',
                data: { id: id },
                success: function(response) {
                    alert('Recipient deleted successfully.');
                    location.reload();
                },
                error: function() {
                    alert('Failed to delete recipient.');
                }
            });
        }
    });
});
