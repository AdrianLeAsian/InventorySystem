<?php
session_start(); // Ensure session is started
// Check if user is logged in (already handled by includes/auth.php, but good practice to have here too)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Check user role
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php'); // Redirect users to dashboard
    exit;
}
$page_title = 'Inventory Management';
include 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>
    <div class="main-content">
        <h2 style="margin-bottom:0;">Items List</h2>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:18px;">
            <div style="display:flex;gap:8px;">
                <button type="button" class="btn-primary" onclick="showAddItemModal()">Add Item</button>
                <button type="button" class="btn-primary" onclick="showAddCategoryModal()">Add Category</button>
                <button type="button" class="btn-primary" onclick="showAddLocationModal()">Add Location</button>
            </div>
            <form method="get" style="display:flex;align-items:center;gap:12px;margin:0;">
                <input type="text" name="search" placeholder="Search item name..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" style="max-width:220px;">
                <select name="category_filter" style="max-width:180px;">
                    <option value="">All Categories</option>
                    <?php
                    $catStmt = $conn->prepare("SELECT id, name FROM categories");
                    $catStmt->execute();
                    $catRes = $catStmt->get_result();
                    while ($cat = $catRes->fetch_assoc()) {
                        $sel = (isset($_GET['category_filter']) && $_GET['category_filter'] == $cat['id']) ? 'selected' : '';
                        echo '<option value="'.$cat['id'].'" '.$sel.'>'.htmlspecialchars($cat['name']).'</option>';
                    }
                    $catStmt->close();
                    ?>
                </select>
                <button type="submit" class="btn-primary">Filter</button>
            </form>
        </div>
        <!-- Inventory table -->
        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Perishable</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Filtering logic
                $where = [];
                $params = [];
                $types = '';
                if (!empty($_GET['search'])) {
                    $where[] = "items.name LIKE ?";
                    $params[] = "%" . $_GET['search'] . "%";
                    $types .= 's';
                }
                if (!empty($_GET['category_filter'])) {
                    $where[] = "items.category_id = ?";
                    $params[] = $_GET['category_filter'];
                    $types .= 'i';
                }
                $sql = "SELECT items.*, categories.name AS category, locations.name AS location FROM items JOIN categories ON items.category_id=categories.id JOIN locations ON items.location_id=locations.id";
                if ($where) $sql .= " WHERE " . implode(" AND ", $where);
                if ($where) {
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $result = $conn->query($sql);
                }
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['category']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['location']) . '</td>';
                    echo '<td>' . $row['current_stock'] . '</td>';
                    // Status logic
                    $status = '';
                    $statusColor = '';
                    if ($row['current_stock'] == 0) {
                        $status = 'NO STOCK';
                        $statusColor = 'status-red';
                    } elseif ($row['current_stock'] <= $row['low_stock']) {
                        $status = 'LOW STOCK';
                        $statusColor = 'status-orange';
                    } elseif ($row['current_stock'] >= $row['max_stock']) {
                        $status = 'FULL';
                        $statusColor = 'status-green';
                    } else {
                        $status = 'OK';
                        $statusColor = 'status-green';
                    }
                    echo '<td><span class="' . $statusColor . '">' . $status . '</span></td>';
                    echo '<td>' . ($row['is_perishable'] ? 'Yes' : 'No') . '</td>';
                    echo '<td>';
                    echo '<button class="btn-warning" onclick="showEditItemModal(' . $row['id'] . ')">Edit</button> ';
                    echo '<button class="btn-danger" onclick="showDeleteItemModal(' . $row['id'] . ')">Delete</button> ';
echo '<button class="btn-update-stock" onclick="showUpdateStockModal(' . $row['id'] . ',' . $row['is_perishable'] . ')">Stock In</button>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        <!-- Perishable/Batched Items Table -->
        <h3>Perishable/Batched Items</h3>
        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Quantity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $fifoStmt = $conn->prepare("SELECT item_batches.id, items.name, item_batches.expiry_date, item_batches.quantity, items.is_perishable FROM item_batches JOIN items ON item_batches.item_id=items.id WHERE item_batches.quantity > 0 ORDER BY item_batches.expiry_date ASC, items.is_perishable DESC");
                $fifoStmt->execute();
                $fifoRes = $fifoStmt->get_result();
                $now = strtotime(date('Y-m-d'));
                while ($row = $fifoRes->fetch_assoc()) {
                    $exp = strtotime($row['expiry_date']);
                    $days_total = ($exp - $now) / 86400;
                    $status = '';
                    $color = '';

                    if ($row['is_perishable']) {
                        if ($days_total < 0) {
                            $status = 'Expired';
                            $color = '#D33F49';
                        } else {
                            $fifo_life = $exp - $now;
                            $half_life = $fifo_life / 2;
                            if ($days_total <= $half_life / 86400) {
                                $status = 'Near';
                                $color = '#FFA500';
                            } else {
                                $status = 'Fresh';
                                $color = '#27ae60';
                            }
                        }
                    } else {
                        $status = 'N/A'; // Not Applicable for non-perishable
                        $color = '#6c757d'; // Grey color for N/A
                    }
                    
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                    echo '<td>' . ($row['expiry_date'] ? htmlspecialchars($row['expiry_date']) : 'N/A') . '</td>';
                    echo '<td><span style="color:' . $color . ';font-weight:bold;">' . $status . '</span></td>';
                    echo '<td>' . $row['quantity'] . '</td>';
                    echo '<td>';
                    echo '<button class="btn-warning" onclick="showEditBatchModal(' . $row['id'] . ', \'' . htmlspecialchars($row['name']) . '\', \'' . htmlspecialchars($row['expiry_date'] ?? '') . '\', ' . $row['quantity'] . ', ' . $row['is_perishable'] . ')">Edit</button> ';
                    echo '<button class="btn-danger" onclick="showDeleteBatchModal(' . $row['id'] . ', \'' . htmlspecialchars($row['name']) . '\')">Delete</button> ';
                    echo '<button class="btn-update-stock" onclick="showStockOutBatchModal(' . $row['id'] . ', \'' . htmlspecialchars($row['name']) . '\', ' . $row['quantity'] . ')">Stock Out</button>';
                    echo '</td>';
                    echo '</tr>';
                }
                $fifoStmt->close();
                ?>
            </tbody>
        </table>
        <!-- Categories and Locations in one row -->
        <div style="display:flex; justify-content:space-between; gap:20px; margin-top:20px;">
            <div style="flex:1;">
                <h3>Categories</h3>
                <table border="1" cellpadding="10" cellspacing="0" style="width:100%;">
                    <thead>
                        <tr><th>Name</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $catsStmt = $conn->prepare("SELECT id, name FROM categories");
                        $catsStmt->execute();
                        $catsRes = $catsStmt->get_result();
                        while ($row = $catsRes->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                            echo '<td>';
                            echo '<button class="btn-warning" onclick="showEditCategoryModal(' . $row['id'] . ', \'' . addslashes($row['name']) . '\')">Edit</button> ';
                            echo '<button class="btn-danger" onclick="showDeleteCategoryModal(' . $row['id'] . ')">Delete</button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        $catsStmt->close();
                        ?>
                    </tbody>
                </table>
            </div>
            <div style="flex:1;">
                <h3>Locations</h3>
                <table border="1" cellpadding="10" cellspacing="0" style="width:100%;">
                    <thead>
                        <tr><th>Name</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $locsStmt = $conn->prepare("SELECT id, name FROM locations");
                        $locsStmt->execute();
                        $locsRes = $locsStmt->get_result();
                        while ($row = $locsRes->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                            echo '<td>';
                            echo '<button class="btn-warning" onclick="showEditLocationModal(' . $row['id'] . ', \'' . addslashes($row['name']) . '\')">Edit</button> ';
                            echo '<button class="btn-danger" onclick="showDeleteLocationModal(' . $row['id'] . ')">Delete</button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        $locsStmt->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php include 'includes/modals.php'; ?>
    <script>
    // Modal helpers
    function showAddItemModal() {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal-body').innerHTML = `
            <h3>Add Item</h3>
            <form id="addItemForm">
                <label>Item Name<span class="required-asterisk">*</span></label>
                <input type="text" name="name" placeholder="Item Name" required><br>
                <div style="margin-bottom:16px;">
                    <label>Category<span class="required-asterisk">*</span></label>
                    <select name="category_id" required style="width:100%;margin-top:2px;">
                        <option value="">Select Category</option>
                        <?php
                        $catsStmt = $conn->prepare("SELECT id, name FROM categories");
                        $catsStmt->execute();
                        $catsRes = $catsStmt->get_result();
                        while ($c = $catsRes->fetch_assoc()) {
                            echo '<option value="'.$c['id'].'">'.htmlspecialchars($c['name']).'</option>';
                        }
                        $catsStmt->close();
                        ?>
                    </select>
                </div>
                <div style="margin-bottom:16px;">
                    <label>Location<span class="required-asterisk">*</span></label>
                    <select name="location_id" required style="width:100%;margin-top:2px;">
                        <option value="">Select Location</option>
                        <?php
                        $locsStmt = $conn->prepare("SELECT id, name FROM locations");
                        $locsStmt->execute();
                        $locsRes = $locsStmt->get_result();
                        while ($l = $locsRes->fetch_assoc()) {
                            echo '<option value="'.$l['id'].'">'.htmlspecialchars($l['name']).'</option>';
                        }
                        $locsStmt->close();
                        ?>
                    </select>
                </div>
                <div style="display:flex;gap:12px;align-items:center;">
                    <div style="flex:1;">
                        <label>Stock<span class="required-asterisk">*</span></label>
                        <input type="number" name="current_stock" placeholder="Stock" min="0" required>
                    </div>
                    <div style="flex:1;">
                        <label>Unit<span class="required-asterisk">*</span></label>
                        <input type="text" name="unit" placeholder="Unit (e.g. kg, gram, pieces)" required>
                    </div>
                </div><br>
                <div style="display:flex;gap:12px;align-items:center;">
                    <div style="flex:1;">
                        <label>Low Stock</label>
                        <input type="number" name="low_stock" placeholder="Low Stock" min="0">
                    </div>
                    <div style="flex:1;">
                        <label>Maximum Stock</label>
                        <input type="number" name="max_stock" placeholder="Maximum Stock" min="0">
                    </div>
                </div><br>
                <label><input type="checkbox" name="is_perishable" id="isPerishableAdd" onchange="toggleExpiry('add')"> Perishable</label><br>
                <div id="expiryDateAdd" style="display:none;">
                    <label>Expiry Date<span class="required-asterisk">*</span></label>
                    <input type="date" name="expiry_date" id="addExpiryDate" placeholder="Expiry Date"><br>
                </div>
                <button type="submit" class="btn-primary">Add</button>
            </form>
            <div id="addItemMsg"></div>
        `;
        document.getElementById('addItemForm').onsubmit = function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'add');
            fetch('includes/item_actions.php', {method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{
                document.getElementById('addItemMsg').innerText = d.message || (d.status=='success'?'Item added!':'Error');
                if(d.status=='success') setTimeout(()=>location.reload(),800);
            });
        };
        document.getElementById('isPerishableAdd').addEventListener('change', function() {
            var expiryDateInput = document.getElementById('addExpiryDate');
            if (this.checked) {
                document.getElementById('expiryDateAdd').style.display = 'block';
                expiryDateInput.setAttribute('required', 'required');
            } else {
                document.getElementById('expiryDateAdd').style.display = 'none';
                expiryDateInput.removeAttribute('required');
            }
        });
    }
    function showEditItemModal(id) {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal-body').innerHTML = '<div style="text-align:center;padding:32px 0;">Loading item details...</div>';
        fetch('includes/item_actions.php', {method:'POST',body:new URLSearchParams({action:'get',id:id})})
        .then(r=>r.json()).then(item=>{
            if(!item || !item.id) {
                document.getElementById('modal-body').innerHTML = '<div style="text-align:center;padding:32px 0;color:#D33F49;">Error: Item details not found.</div>';
                return;
            }
            document.getElementById('modal-body').innerHTML = `
                <h3>Edit Item</h3>
                <form id="editItemForm">
                    <input type="hidden" name="id" value="${item.id}">
                    <label>Item Name<span class="required-asterisk">*</span></label>
                    <input type="text" name="name" value="${item.name}" required><br>
                    <div style="margin-bottom:16px;">
                        <label>Category<span class="required-asterisk">*</span></label>
                        <select name="category_id" id="editCategorySelect" required style="width:100%;margin-top:2px;">
                            <option value="">Select Category</option>
                            <?php
                            $catsStmt = $conn->prepare("SELECT id, name FROM categories");
                            $catsStmt->execute();
                            $catsRes = $catsStmt->get_result();
                            while ($c = $catsRes->fetch_assoc()) {
                                echo '<option value="'.$c['id'].'">'.htmlspecialchars($c['name']).'</option>';
                            }
                            $catsStmt->close();
                            ?>
                        </select>
                    </div>
                    <div style="margin-bottom:16px;">
                        <label>Location<span class="required-asterisk">*</span></label>
                        <select name="location_id" id="editLocationSelect" required style="width:100%;margin-top:2px;">
                            <option value="">Select Location</option>
                            <?php
                            $locsStmt = $conn->prepare("SELECT id, name FROM locations");
                            $locsStmt->execute();
                            $locsRes = $locsStmt->get_result();
                            while ($l = $locsRes->fetch_assoc()) {
                                echo '<option value="'.$l['id'].'">'.htmlspecialchars($l['name']).'</option>';
                            }
                            $locsStmt->close();
                            ?>
                        </select>
                    </div>
                    <div style="display:flex;gap:12px;align-items:center;">
                        <div style="flex:1;">
                            <label>Stock<span class="required-asterisk">*</span></label>
                            <input type="number" name="current_stock" value="${item.current_stock}" min="0" required>
                        </div>
                        <div style="flex:1;">
                            <label>Unit<span class="required-asterisk">*</span></label>
                            <input type="text" name="unit" value="${item.unit||''}" placeholder="Unit (e.g. kg, gram, pieces)" required>
                        </div>
                    </div><br>
                    <div style="display:flex;gap:12px;align-items:center;">
                        <div style="flex:1;">
                            <label>Low Stock</label>
                            <input type="number" name="low_stock" value="${item.low_stock||''}" min="0">
                        </div>
                        <div style="flex:1;">
                            <label>Maximum Stock</label>
                            <input type="number" name="max_stock" value="${item.max_stock||''}" min="0">
                        </div>
                    </div><br>
                    <label><input type="checkbox" name="is_perishable" id="isPerishableEdit" ${item.is_perishable==1?'checked':''} onchange="toggleExpiry('edit')"> Perishable</label><br>
                    <div id="expiryDateEdit" style="display:${item.is_perishable==1?'block':'none'};">
                        <label>Expiry Date<span class="required-asterisk">*</span></label>
                        <input type="date" name="expiry_date" id="editExpiryDate" value="${item.expiry_date||''}" placeholder="Expiry Date"><br>
                    </div>
                    <button type="submit" class="btn-primary">Update</button>
                </form>
                <div id="editItemMsg"></div>
            `;
            // Pre-select category and location
            setTimeout(function() {
                var catSelect = document.getElementById('editCategorySelect');
                if(catSelect) catSelect.value = item.category_id;
                var locSelect = document.getElementById('editLocationSelect');
                if(locSelect) locSelect.value = item.location_id;
                // Set expiry date if perishable
                if(item.is_perishable==1 && item.expiry_date) {
                    var expInput = document.getElementById('editExpiryDate');
                    if(expInput) expInput.value = item.expiry_date;
                }
                // Ensure expiry date is required if perishable is checked on edit
                var isPerishableEdit = document.getElementById('isPerishableEdit');
                var editExpiryDateInput = document.getElementById('editExpiryDate');
                if (isPerishableEdit && editExpiryDateInput) {
                    if (isPerishableEdit.checked) {
                        editExpiryDateInput.setAttribute('required', 'required');
                    } else {
                        editExpiryDateInput.removeAttribute('required');
                    }
                }
            }, 100);
            document.getElementById('editItemForm').onsubmit = function(e) {
                e.preventDefault();
                var fd = new FormData(this);
                fd.append('action', 'edit');
                fetch('includes/item_actions.php', {method:'POST',body:fd})
                .then(r=>r.json()).then(d=>{
                    document.getElementById('editItemMsg').innerText = d.message || (d.status=='success'?'Item updated!':'Error');
                    if(d.status=='success') setTimeout(()=>location.reload(),800);
                });
            };
            document.getElementById('isPerishableEdit').addEventListener('change', function() {
                var expiryDateInput = document.getElementById('editExpiryDate');
                if (this.checked) {
                    document.getElementById('expiryDateEdit').style.display = 'block';
                    expiryDateInput.setAttribute('required', 'required');
                } else {
                    document.getElementById('expiryDateEdit').style.display = 'none';
                    expiryDateInput.removeAttribute('required');
                }
            });
            // The toggleExpiry function is no longer needed as the logic is now directly in the event listeners
            // function toggleExpiry(type) {
            //     if(type === 'add') {
            //         document.getElementById('expiryDateAdd').style.display = document.getElementById('isPerishableAdd').checked ? 'block' : 'none';
            //     } else {
            //         document.getElementById('expiryDateEdit').style.display = document.getElementById('isPerishableEdit').checked ? 'block' : 'none';
            //     }
            // }
        })
        .catch(function(){
            document.getElementById('modal-body').innerHTML = '<div style="text-align:center;padding:32px 0;color:#D33F49;">Error loading item details.</div>';
        });
    }
    function showDeleteItemModal(id) {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal-body').innerHTML = `
            <h3>Delete Item</h3>
            <form id="deleteItemForm">
                <input type="hidden" name="id" value="${id}">
                <p>Are you sure you want to delete this item?</p>
                <button type="submit" class="btn-danger">Delete</button>
            </form>
            <div id="deleteItemMsg"></div>
        `;
        document.getElementById('deleteItemForm').onsubmit = function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'delete');
            fetch('includes/item_actions.php', {method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{
                document.getElementById('deleteItemMsg').innerText = d.message || (d.status=='success'?'Item deleted!':'Error');
                if(d.status=='success') setTimeout(()=>location.reload(),800);
            });
        };
    }
    function showUpdateStockModal(id, is_perishable) {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal-body').innerHTML = `
            <h3>Update Stock</h3>
            <form id="updateStockForm">
                <input type="hidden" name="item_id" value="${id}">
                <input type="number" name="quantity" placeholder="Quantity" min="1" required><br>
                ${is_perishable ? '<label>Expiry Date<span class="required-asterisk">*</span></label><input type="date" name="expiry_date" required><br>' : ''}
                <button type="button" class="btn-primary" id="addStockBtn">Add Stock</button>
            </form>
            <div id="updateStockMsg"></div>
        `;
        document.getElementById('addStockBtn').onclick = function() {
            var form = document.getElementById('updateStockForm');
            var fd = new FormData(form);
            fd.append('action', 'update_stock');
            fd.append('is_perishable', is_perishable);
            fetch('includes/stock_actions.php', {method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{
                document.getElementById('updateStockMsg').innerText = d.message || (d.status=='success'?'Stock updated!':'Error');
                if(d.status=='success') setTimeout(()=>location.reload(),800);
            });
        };
        document.getElementById('removeStockBtn').onclick = function() {
            var form = document.getElementById('updateStockForm');
            var fd = new FormData(form);
            fd.append('action', 'reduce_stock');
            fd.append('is_perishable', is_perishable);
            fetch('includes/stock_actions.php', {method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{
                document.getElementById('updateStockMsg').innerText = d.message || (d.status=='success'?'Stock removed!':'Error');
                if(d.status=='success') setTimeout(()=>location.reload(),800);
            });
        };
    }
    function showEditBatchModal(id, itemName, expiryDate, quantity) {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal-body').innerHTML = `
            <h3>Edit Batch: ${itemName}</h3>
            <form id="editBatchForm">
                <input type="hidden" name="batch_id" value="${id}">
                <label>Expiry Date<span class="required-asterisk">*</span></label>
                <input type="date" name="expiry_date" value="${expiryDate}" required><br>
                <label>Quantity<span class="required-asterisk">*</span></label>
                <input type="number" name="quantity" value="${quantity}" min="0" required><br>
                <button type="submit" class="btn-primary">Update Batch</button>
            </form>
            <div id="editBatchMsg"></div>
        `;
        document.getElementById('editBatchForm').onsubmit = function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'edit_batch');
            fetch('includes/stock_actions.php', {method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{
                document.getElementById('editBatchMsg').innerText = d.message || (d.status=='success'?'Batch updated!':'Error');
                if(d.status=='success') setTimeout(()=>location.reload(),800);
            });
        };
    }
    function showDeleteBatchModal(id, itemName) {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal-body').innerHTML = `
            <h3>Delete Batch: ${itemName}</h3>
            <form id="deleteBatchForm">
                <input type="hidden" name="batch_id" value="${id}">
                <p>Are you sure you want to delete this batch? This will remove the entire batch quantity from the item's total stock.</p>
                <button type="submit" class="btn-danger">Delete Batch</button>
            </form>
            <div id="deleteBatchMsg"></div>
        `;
        document.getElementById('deleteBatchForm').onsubmit = function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'delete_batch');
            fetch('includes/stock_actions.php', {method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{
                document.getElementById('deleteBatchMsg').innerText = d.message || (d.status=='success'?'Batch deleted!':'Error');
                if(d.status=='success') setTimeout(()=>location.reload(),800);
            });
        };
    }
    function showStockOutBatchModal(id, itemName, maxQuantity) {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal-body').innerHTML = `
            <h3>Stock Out Batch: ${itemName}</h3>
            <form id="stockOutBatchForm">
                <input type="hidden" name="batch_id" value="${id}">
                <label>Quantity to Stock Out (Max: ${maxQuantity})<span class="required-asterisk">*</span></label>
                <input type="number" name="quantity" placeholder="Quantity" min="1" max="${maxQuantity}" required><br>
                <button type="submit" class="btn-danger">Stock Out</button>
            </form>
            <div id="stockOutBatchMsg"></div>
        `;
        document.getElementById('stockOutBatchForm').onsubmit = function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'stock_out_batch');
            fetch('includes/stock_actions.php', {method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{
                document.getElementById('stockOutBatchMsg').innerText = d.message || (d.status=='success'?'Stock out successful!':'Error');
                if(d.status=='success') setTimeout(()=>location.reload(),800);
            });
        };
    }
    // Category modals
    function showAddCategoryModal() {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal-body').innerHTML = `
            <h3>Add Category</h3>
            <form id="addCategoryForm">
                <input type="text" name="name" placeholder="Category Name" required><br>
                <button type="submit" class="btn-primary">Add</button>
            </form>
            <div id="addCategoryMsg"></div>
        `;
        document.getElementById('addCategoryForm').onsubmit = function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'add');
            fd.append('type', 'category');
            fetch('includes/category_location_actions.php', {method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{
                document.getElementById('addCategoryMsg').innerText = d.message || (d.status=='success'?'Category added!':'Error');
                if(d.status=='success') setTimeout(()=>location.reload(),800);
            });
        };
    }
    function showEditCategoryModal(id, name) {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal-body').innerHTML = `
            <h3>Edit Category</h3>
            <form id="editCategoryForm">
                <input type="hidden" name="id" value="${id}">
                <input type="text" name="name" value="${name}" required><br>
                <button type="submit" class="btn-primary">Update</button>
            </form>
            <div id="editCategoryMsg"></div>
        `;
        document.getElementById('editCategoryForm').onsubmit = function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'edit');
            fd.append('type', 'category');
            fetch('includes/category_location_actions.php', {method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{
                document.getElementById('editCategoryMsg').innerText = d.message || (d.status=='success'?'Category updated!':'Error');
                if(d.status=='success') setTimeout(()=>location.reload(),800);
            });
        };
    }
    function showDeleteCategoryModal(id) {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal-body').innerHTML = `
            <h3>Delete Category</h3>
            <form id="deleteCategoryForm">
                <input type="hidden" name="id" value="${id}">
                <button type="submit" class="btn-danger">Delete</button>
            </form>
            <div id="deleteCategoryMsg"></div>
        `;
        document.getElementById('deleteCategoryForm').onsubmit = function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'delete');
            fd.append('type', 'category');
            fetch('includes/category_location_actions.php', {method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{
                document.getElementById('deleteCategoryMsg').innerText = d.message || (d.status=='success'?'Category deleted!':'Error');
                if(d.status=='success') setTimeout(()=>location.reload(),800);
            });
        };
    }
    // Location modals
    function showAddLocationModal() {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal-body').innerHTML = `
            <h3>Add Location</h3>
            <form id="addLocationForm">
                <input type="text" name="name" placeholder="Location Name" required><br>
                <button type="submit" class="btn-primary">Add</button>
            </form>
            <div id="addLocationMsg"></div>
        `;
        document.getElementById('addLocationForm').onsubmit = function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'add');
            fd.append('type', 'location');
            fetch('includes/category_location_actions.php', {method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{
                document.getElementById('addLocationMsg').innerText = d.message || (d.status=='success'?'Location added!':'Error');
                if(d.status=='success') setTimeout(()=>location.reload(),800);
            });
        };
    }
    function showEditLocationModal(id, name) {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal-body').innerHTML = `
            <h3>Edit Location</h3>
            <form id="editLocationForm">
                <input type="hidden" name="id" value="${id}">
                <input type="text" name="name" value="${name}" required><br>
                <button type="submit" class="btn-primary">Update</button>
            </form>
            <div id="editLocationMsg"></div>
        `;
        document.getElementById('editLocationForm').onsubmit = function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'edit');
            fd.append('type', 'location');
            fetch('includes/category_location_actions.php', {method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{
                document.getElementById('editLocationMsg').innerText = d.message || (d.status=='success'?'Location updated!':'Error');
                if(d.status=='success') setTimeout(()=>location.reload(),800);
            });
        };
    }
    function showDeleteLocationModal(id) {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal-body').innerHTML = `
            <h3>Delete Location</h3>
            <form id="deleteLocationForm">
                <input type="hidden" name="id" value="${id}">
                <button type="submit" class="btn-danger">Delete</button>
            </form>
            <div id="deleteLocationMsg"></div>
        `;
        document.getElementById('deleteLocationForm').onsubmit = function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'delete');
            fd.append('type', 'location');
            fetch('includes/category_location_actions.php', {method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{
                document.getElementById('deleteLocationMsg').innerText = d.message || (d.status=='success'?'Location deleted!':'Error');
                if(d.status=='success') setTimeout(()=>location.reload(),800);
            });
        };
    }
    </script>
</body>
</html>
