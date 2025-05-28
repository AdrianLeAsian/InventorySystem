<?php
require_once '../config/db.php'; // Adjust path as needed

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $item_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($item_id === false) {
        $response['message'] = 'Invalid item ID.';
        echo json_encode($response);
        exit();
    }

    $sql = "SELECT i.id, i.name, i.category_id, i.barcode, i.quantity, i.unit, i.low_stock_threshold, i.description, i.location, i.updated_at, c.name as category_name
            FROM items i
            JOIN categories c ON i.category_id = c.id
            WHERE i.id = ?";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $item_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($item = mysqli_fetch_assoc($result)) {
            $response['success'] = true;
            $response['item'] = $item;
            // Add formatted_updated_at for consistency with addOrUpdateItemRow
            $response['item']['formatted_updated_at'] = format_last_activity($item['updated_at']);
        } else {
            $response['message'] = 'Item not found.';
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn);
    }
} else {
    $response['message'] = 'No item ID provided.';
}

echo json_encode($response);

// Function to format timestamp for "Last Activity" - simple version
function format_last_activity($timestamp) {
    if (empty($timestamp)) return 'N/A';
    $date = new DateTime($timestamp);
    $now = new DateTime();
    $interval = $now->diff($date);

    if ($interval->d == 0 && $interval->h < 24) { // Today
        return "Today, " . $date->format('g:i A');
    } elseif ($interval->d == 1) { // Yesterday
        return "Yesterday, " . $date->format('g:i A');
    } elseif ($interval->days < 7) { // Within a week
        return $interval->days . " days ago";
    } else {
        return $date->format('M j, Y');
    }
}
?>
