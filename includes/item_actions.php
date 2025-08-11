
<?php
include 'db.php';

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'get') {
	$id = intval($_POST['id']);
	$stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$result = $stmt->get_result();
	$item = $result->fetch_assoc();
	echo json_encode($item);
	exit;
}

if ($action == 'add') {
	$name = trim($_POST['name']);
	$category_id = intval($_POST['category_id']);
	$location_id = intval($_POST['location_id']);
	$stock = intval($_POST['current_stock']);
	$unit = trim($_POST['unit']);
	$low_stock = intval($_POST['low_stock']);
	// min_stock removed
	$max_stock = intval($_POST['max_stock']);
	$is_perishable = isset($_POST['is_perishable']) ? 1 : 0;

	// Duplicate check
	$check = $conn->prepare("SELECT id FROM items WHERE name = ?");
	$check->bind_param('s', $name);
	$check->execute();
	$check->store_result();
	if ($check->num_rows > 0) {
		echo json_encode(['status' => 'error', 'message' => 'Item name already exists.']);
		exit;
	}

	$stmt = $conn->prepare("INSERT INTO items (name, category_id, location_id, current_stock, unit, low_stock, max_stock, is_perishable) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
	$stmt->bind_param('sissiiii', $name, $category_id, $location_id, $stock, $unit, $low_stock, $max_stock, $is_perishable);
	if ($stmt->execute()) {
		echo json_encode(['status' => 'success']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Failed to add item.']);
	}
	exit;
}

if ($action == 'edit') {
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

	// Duplicate check (exclude current item)
	$check = $conn->prepare("SELECT id FROM items WHERE name = ? AND id != ?");
	$check->bind_param('si', $name, $id);
	$check->execute();
	$check->store_result();
	if ($check->num_rows > 0) {
		echo json_encode(['status' => 'error', 'message' => 'Item name already exists.']);
		exit;
	}

	$stmt = $conn->prepare("UPDATE items SET name=?, category_id=?, location_id=?, current_stock=?, unit=?, low_stock=?, max_stock=?, is_perishable=? WHERE id=?");
	$stmt->bind_param('sissiiiii', $name, $category_id, $location_id, $stock, $unit, $low_stock, $max_stock, $is_perishable, $id);
	if ($stmt->execute()) {
		echo json_encode(['status' => 'success']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Failed to update item.']);
	}
	exit;
}

if ($action == 'delete') {
	$id = intval($_POST['id']);
	$stmt = $conn->prepare("DELETE FROM items WHERE id=?");
	$stmt->bind_param('i', $id);
	if ($stmt->execute()) {
		echo json_encode(['status' => 'success']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Failed to delete item.']);
	}
	exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
exit;
?>
