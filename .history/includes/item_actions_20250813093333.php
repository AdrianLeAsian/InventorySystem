
<?php
session_start(); // Ensure session is started
include 'db.php';
include 'auth.php'; // Include auth.php for check_admin_role()

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'get') {
	// No admin check needed for read operation
	$id = intval($_POST['id']);
	$stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$result = $stmt->get_result();
	$item = $result->fetch_assoc();
	echo json_encode($item);
	exit;
}

// For 'add', 'edit', 'delete' actions, admin access is required
if ($action == 'add') {
    check_admin_role(); // Enforce admin role for adding items
	$name = trim($_POST['name']);
	$category_id = intval($_POST['category_id']);
	$location_id = intval($_POST['location_id']);
	$stock = intval($_POST['current_stock']);
	$unit = trim($_POST['unit']);
	$low_stock = intval($_POST['low_stock']);
	// min_stock removed
	$max_stock = intval($_POST['max_stock']);
	$is_perishable = isset($_POST['is_perishable']) ? 1 : 0;
	$expiry_date = ($is_perishable && !empty($_POST['expiry_date'])) ? $_POST['expiry_date'] : NULL;

	// Duplicate check
	$check = $conn->prepare("SELECT id FROM items WHERE name = ?");
	$check->bind_param('s', $name);
	$check->execute();
	$check->store_result();
	if ($check->num_rows > 0) {
		echo json_encode(['status' => 'error', 'message' => 'Item name already exists.']);
		exit;
	}

	$stmt = $conn->prepare("INSERT INTO items (name, category_id, location_id, current_stock, unit, low_stock, max_stock, is_perishable, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
	$stmt->bind_param('sissiiiis', $name, $category_id, $location_id, $stock, $unit, $low_stock, $max_stock, $is_perishable, $expiry_date);
	if ($stmt->execute()) {
		$item_id = $conn->insert_id; // Get the ID of the newly inserted item

		// Log the action
		$category_name_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
		$category_name_stmt->bind_param('i', $category_id);
		$category_name_stmt->execute();
		$category_result = $category_name_stmt->get_result();
		$category_row = $category_result->fetch_assoc();
		$category_name = $category_row['name'];

		$log_stmt = $conn->prepare("INSERT INTO logs (item_id, action, category) VALUES (?, ?, ?)");
		$log_action = 'Added';
		$log_stmt->bind_param('iss', $item_id, $log_action, $category_name);
		$log_stmt->execute();

		echo json_encode(['status' => 'success']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Failed to add item.']);
	}
	exit;
}

if ($action == 'edit') {
    check_admin_role(); // Enforce admin role for editing items
	$id = intval($_POST['id']);
	$name = trim($_POST['name']);
	$category_id = intval($_POST['category_id']);
	$location_id = intval($_POST['location_id']);
	$stock = intval($_POST['current_stock']);
	$unit = trim($_POST['unit']);
	$low_stock = intval($_POST['low_stock']);
	// min_stock removed
	$max_stock = intval($_POST['max_stock']);
	$is_perishable = isset($_POST['is_perishable']) ? 1 : 0;
	$expiry_date = ($is_perishable && !empty($_POST['expiry_date'])) ? $_POST['expiry_date'] : NULL;

	// Duplicate check (exclude current item)
	$check = $conn->prepare("SELECT id FROM items WHERE name = ? AND id != ?");
	$check->bind_param('si', $name, $id);
	$check->execute();
	$check->store_result();
	if ($check->num_rows > 0) {
		echo json_encode(['status' => 'error', 'message' => 'Item name already exists.']);
		exit;
	}

	// Get old item details for logging
	$old_item_stmt = $conn->prepare("SELECT current_stock, category_id FROM items WHERE id = ?");
	$old_item_stmt->bind_param('i', $id);
	$old_item_stmt->execute();
	$old_item_result = $old_item_stmt->get_result();
	$old_item_data = $old_item_result->fetch_assoc();
	$old_stock = $old_item_data['current_stock'];
	$old_category_id = $old_item_data['category_id'];

	$stmt = $conn->prepare("UPDATE items SET name=?, category_id=?, location_id=?, current_stock=?, unit=?, low_stock=?, max_stock=?, is_perishable=?, expiry_date=? WHERE id=?");
	$stmt->bind_param('sissiiiisi', $name, $category_id, $location_id, $stock, $unit, $low_stock, $max_stock, $is_perishable, $expiry_date, $id);
	if ($stmt->execute()) {
		// Log the action
		$category_name_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
		$category_name_stmt->bind_param('i', $category_id);
		$category_name_stmt->execute();
		$category_result = $category_name_stmt->get_result();
		$category_row = $category_result->fetch_assoc();
		$category_name = $category_row['name'];

		$log_action = 'Updated';
		if ($stock > $old_stock) {
			$log_action = 'Stocked In';
		} elseif ($stock < $old_stock) {
			$log_action = 'Stocked Out';
		}

		$log_stmt = $conn->prepare("INSERT INTO logs (item_id, action, category) VALUES (?, ?, ?)");
		$log_stmt->bind_param('iss', $id, $log_action, $category_name);
		$log_stmt->execute();

		echo json_encode(['status' => 'success']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Failed to update item.']);
	}
	exit;
}

if ($action == 'delete') {
    check_admin_role(); // Enforce admin role for deleting items
	$id = intval($_POST['id']);

	// Get item details for logging before deleting
	$item_details_stmt = $conn->prepare("SELECT name, category_id FROM items WHERE id = ?");
	$item_details_stmt->bind_param('i', $id);
	$item_details_stmt->execute();
	$item_details_result = $item_details_stmt->get_result();
	$item_data = $item_details_result->fetch_assoc();
	$item_name = $item_data['name'];
	$category_id = $item_data['category_id'];

	$stmt = $conn->prepare("DELETE FROM items WHERE id=?");
	$stmt->bind_param('i', $id);
	if ($stmt->execute()) {
		// Log the action
		$category_name_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
		$category_name_stmt->bind_param('i', $category_id);
		$category_name_stmt->execute();
		$category_result = $category_name_stmt->get_result();
		$category_row = $category_result->fetch_assoc();
		$category_name = $category_row['name'];

		$log_stmt = $conn->prepare("INSERT INTO logs (item_id, action, category) VALUES (?, ?, ?)");
		$log_action = 'Deleted';
		$log_stmt->bind_param('iss', $id, $log_action, $category_name);
		$log_stmt->execute();

		echo json_encode(['status' => 'success']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Failed to delete item.']);
	}
	exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
exit;
?>
