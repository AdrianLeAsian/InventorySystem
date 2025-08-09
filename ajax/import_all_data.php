<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_POST['csv_data']) || empty($_POST['csv_data'])) {
    $response['message'] = 'No CSV data received.';
    echo json_encode($response);
    exit();
}

$csv_data = $_POST['csv_data'];
$lines = preg_split("/\r\n|\n|\r/", $csv_data);

$current_table = '';
$columns = [];
$data_rows = [];
$table_data = [];

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) {
        continue;
    }

    // Check for table header
    if (strpos($line, '--- Table:') === 0) {
        if (!empty($current_table) && !empty($data_rows)) {
            $table_data[$current_table] = ['columns' => $columns, 'rows' => $data_rows];
        }
        $current_table = trim(str_replace(['--- Table:', '---'], '', $line));
        $columns = [];
        $data_rows = [];
        continue;
    }

    // Parse CSV line
    $row = str_getcsv($line);

    // Skip empty rows that might result from str_getcsv on empty lines or malformed data
    if (empty($row) || (count($row) === 1 && trim($row[0]) === '')) {
        continue;
    }

    if (empty($current_table)) {
        // This should not happen if the format is strictly followed, but as a safeguard
        continue;
    }

    if (empty($columns)) {
        // First non-table header line after a table header is the column names
        $columns = $row;
    } else {
        // Subsequent lines are data rows
        $data_rows[] = $row;
    }
}

// Add the last table's data
if (!empty($current_table) && !empty($data_rows)) {
    $table_data[$current_table] = ['columns' => $columns, 'rows' => $data_rows];
}

if (empty($table_data)) {
    $response['message'] = 'No valid table data found in CSV.';
    echo json_encode($response);
    exit();
}

mysqli_begin_transaction($conn);
$errors = [];

foreach ($table_data as $table_name => $data) {
    $cols = $data['columns'];
    $rows = $data['rows'];

    if (empty($cols)) {
        $errors[] = "Skipping table '$table_name': No columns found.";
        continue;
    }

    // Clear existing data in the table (TRUNCATE or DELETE ALL)
    // TRUNCATE is faster but resets AUTO_INCREMENT. DELETE FROM is safer for preserving IDs.
    // For a full rebuild, TRUNCATE is often desired.
    // If you want to update/add, you'd need more complex logic (UPSERT/INSERT IGNORE).
    // For this task, "update or add new records" implies a merge, but a full rebuild is simpler
    // given the "rebuild the import" context and no external libraries.
    // Let's assume a full replacement for simplicity, or a simple INSERT for new records.
    // Given "update or add new records", we'll try to insert and handle duplicates.

    // Option 1: Simple INSERT (will fail on duplicate primary keys)
    // Option 2: REPLACE INTO (deletes and re-inserts on duplicate primary key)
    // Option 3: INSERT ... ON DUPLICATE KEY UPDATE (most robust for merge)

    // Without external libraries, parsing CSV and handling database interactions
    // for "update or add" can be tricky. Let's implement a basic INSERT and
    // then consider how to handle updates if necessary.
    // For now, we'll assume new records are added. If a primary key exists, it will error.
    // A more robust solution would involve checking for existence and then UPDATE or INSERT.

    // Let's try to implement INSERT ... ON DUPLICATE KEY UPDATE for tables with primary keys.
    // This requires knowing the primary key column(s).
    // For simplicity, we'll assume 'id' is the primary key for relevant tables.

    $primary_key_col = null;
    $sql_pk = "SHOW KEYS FROM `$table_name` WHERE Key_name = 'PRIMARY'";
    $result_pk = mysqli_query($conn, $sql_pk);
    if ($result_pk && mysqli_num_rows($result_pk) > 0) {
        $pk_row = mysqli_fetch_assoc($result_pk);
        $primary_key_col = $pk_row['Column_name'];
    }
    mysqli_free_result($result_pk);

    $placeholders = implode(', ', array_fill(0, count($cols), '?'));
    $col_names_quoted = implode(', ', array_map(function($c) { return "`$c`"; }, $cols));

    $update_parts = [];
    if ($primary_key_col && in_array($primary_key_col, $cols)) {
        foreach ($cols as $col) {
            if ($col !== $primary_key_col) {
                $update_parts[] = "`$col` = VALUES(`$col`)";
            }
        }
    }

    $sql_insert = "INSERT INTO `$table_name` ($col_names_quoted) VALUES ($placeholders)";
    if (!empty($update_parts)) {
        $sql_insert .= " ON DUPLICATE KEY UPDATE " . implode(', ', $update_parts);
    }

    $stmt = mysqli_prepare($conn, $sql_insert);

    if (!$stmt) {
        $errors[] = "Failed to prepare statement for table '$table_name': " . mysqli_error($conn);
        continue;
    }

    foreach ($rows as $row_data) {
        if (count($row_data) !== count($cols)) {
            $errors[] = "Skipping row in table '$table_name' due to column count mismatch. Expected " . count($cols) . ", got " . count($row_data) . ". Row: " . implode(',', $row_data);
            continue;
        }

        // Determine types for bind_param
        $types = '';
        $bind_params = [];
        foreach ($row_data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's'; // Default to string
            }
            $bind_params[] = $value;
        }

        // Use call_user_func_array to bind parameters dynamically
        array_unshift($bind_params, $types);
        call_user_func_array([$stmt, 'bind_param'], refValues($bind_params));

        if (!mysqli_stmt_execute($stmt)) {
            $errors[] = "Error inserting/updating row in table '$table_name': " . mysqli_stmt_error($stmt) . " (Row: " . implode(',', $row_data) . ")";
        }
    }
    mysqli_stmt_close($stmt);
}

if (empty($errors)) {
    mysqli_commit($conn);
    $response['success'] = true;
    $response['message'] = 'All data imported successfully.';
} else {
    mysqli_rollback($conn);
    $response['message'] = 'Errors occurred during import: ' . implode('; ', $errors);
}

mysqli_close($conn);

echo json_encode($response);

// Helper function for bind_param by reference
function refValues($arr){
    if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
    {
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
}
?>
