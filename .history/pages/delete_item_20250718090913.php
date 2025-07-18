<?php
// Include the database configuration file (assuming it's already included by index.php)
// require_once '../config/db.php'; 

$item_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$item_name = '';

if ($item_id > 0) {
    // Fetch item name for display in the confirmation modal
    $sql_fetch_name = "SELECT name FROM items WHERE id = ?";
    if ($stmt_fetch_name = mysqli_prepare($conn, $sql_fetch_name)) {
        mysqli_stmt_bind_param($stmt_fetch_name, "i", $item_id);
        mysqli_stmt_execute($stmt_fetch_name);
        mysqli_stmt_bind_result($stmt_fetch_name, $fetched_name);
        mysqli_stmt_fetch($stmt_fetch_name);
        $item_name = htmlspecialchars($fetched_name);
        mysqli_stmt_close($stmt_fetch_name);
    }
}

// If item_id is invalid or not found, redirect back to inventory page
if ($item_id === 0 || empty($item_name)) {
    header("Location: index.php?page=inventory&error=item_notfound");
    exit;
}
?>

<link rel="stylesheet" href="css/main.css">

<div class="container">
    <div class="page">
        <header class="d-flex justify-between align-center mb-4">
            <h1 class="page__title">Delete Item</h1>
        </header>

        <div class="card">
            <div class="card__header">
                <h2 class="card__title">Confirm Item Deletion</h2>
            </div>
            <div class="card__body">
                <p class="mb-3">Are you sure you want to delete the item: <strong><?php echo $item_name; ?></strong> (ID: <?php echo $item_id; ?>)?</p>
                <p class="mb-3 text-danger">
                    <strong>Warning:</strong> Deleting an item with existing inventory logs is not allowed to maintain data integrity.
                    If this item has logs, the deletion will fail.
                </p>

                <form class="form" id="deleteItemForm">
                    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                    <div class="form__group">
                        <label class="form__label">Reason for Deletion <span class="required-indicator">*</span></label>
                        <textarea name="reason" class="form__input" rows="3" required placeholder="e.g., Discontinued, Damaged beyond repair, Lost"></textarea>
                    </div>
                    <div class="d-flex justify-between mt-4">
                        <button type="submit" class="btn btn--danger">Confirm Delete</button>
                        <a href="index.php?page=inventory" class="btn btn--secondary">Cancel</a>
                    </div>
                </form>
                <div id="deleteMessage" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteItemForm = document.getElementById('deleteItemForm');
    const deleteMessageDiv = document.getElementById('deleteMessage');

    if (deleteItemForm) {
        deleteItemForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const formData = new FormData(deleteItemForm);
            const itemId = formData.get('item_id');
            const reason = formData.get('reason');

            if (!reason) {
                deleteMessageDiv.innerHTML = '<p class="error">Reason for deletion is required.</p>';
                return;
            }

            // Send AJAX request to the new delete_item.php endpoint
            fetch('ajax/delete_item.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    deleteMessageDiv.innerHTML = `<p class="success">${data.message}</p>`;
                    // Redirect to inventory page after successful deletion
                    window.location.href = 'index.php?page=inventory&status=item_deleted';
                } else {
                    deleteMessageDiv.innerHTML = `<p class="error">${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                deleteMessageDiv.innerHTML = '<p class="error">An unexpected error occurred. Please try again.</p>';
            });
        });
    }
});
</script>
