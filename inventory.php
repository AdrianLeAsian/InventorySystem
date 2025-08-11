<?php
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
        <h2>Items List</h2>
        <!-- Inventory table and modal triggers will go here -->
        <button class="btn-primary" onclick="showAddItemModal()">Add Item</button>
        <button class="btn-primary" onclick="showAddCategoryModal()">Add Category</button>
        <button class="btn-primary" onclick="showAddLocationModal()">Add Location</button>
        <!-- Inventory table -->
        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Stock</th>
                    <th>Perishable</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT items.*, categories.name AS category, locations.name AS location FROM items JOIN categories ON items.category_id=categories.id JOIN locations ON items.location_id=locations.id");
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['category']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['location']) . '</td>';
                    echo '<td>' . $row['current_stock'] . '</td>';
                    echo '<td>' . ($row['is_perishable'] ? 'Yes' : 'No') . '</td>';
                    echo '<td>';
                    echo '<button class="btn-warning" onclick="showEditItemModal(' . $row['id'] . ')">Edit</button> ';
                    echo '<button class="btn-danger" onclick="showDeleteItemModal(' . $row['id'] . ')">Delete</button> ';
                    echo '<button class="btn-primary" onclick="showUpdateStockModal(' . $row['id'] . ',' . $row['is_perishable'] . ')">Update Stock</button>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        <!-- Perishable FIFO Table -->
        <h3>Perishable Items (FIFO)</h3>
        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Expiry Date</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $fifo = $conn->query("SELECT items.name, item_batches.expiry_date, item_batches.quantity FROM item_batches JOIN items ON item_batches.item_id=items.id WHERE items.is_perishable=1 ORDER BY item_batches.expiry_date ASC");
                while ($row = $fifo->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['expiry_date']) . '</td>';
                    echo '<td>' . $row['quantity'] . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        <!-- Category Table -->
        <h3>Categories</h3>
        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr><th>Name</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php
                $cats = $conn->query("SELECT * FROM categories");
                while ($row = $cats->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                    echo '<td>';
                    echo '<button class="btn-warning" onclick="showEditCategoryModal(' . $row['id'] . ', \'' . addslashes($row['name']) . '\')">Edit</button> ';
                    echo '<button class="btn-danger" onclick="showDeleteCategoryModal(' . $row['id'] . ')">Delete</button>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
        <!-- Location Table -->
        <h3>Locations</h3>
        <table border="1" cellpadding="10" cellspacing="0">
            <thead>
                <tr><th>Name</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php
                $locs = $conn->query("SELECT * FROM locations");
                while ($row = $locs->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                    echo '<td>';
                    echo '<button class="btn-warning" onclick="showEditLocationModal(' . $row['id'] . ', \'' . addslashes($row['name']) . '\')">Edit</button> ';
                    echo '<button class="btn-danger" onclick="showDeleteLocationModal(' . $row['id'] . ')">Delete</button>';
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php include 'includes/modals.php'; ?>
    <script>
    // Modal helpers
    function showAddItemModal() {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('modal-body').innerHTML = `
            <h3>Add Item</h3>
            <form id="addItemForm">
                <input type="text" name="name" placeholder="Item Name" required><br>
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php $cats = $conn->query("SELECT * FROM categories"); while ($c = $cats->fetch_assoc()) echo '<option value="'.$c['id'].'">'.htmlspecialchars($c['name']).'</option>'; ?>
                </select><br>
                <select name="location_id" required>
                    <option value="">Select Location</option>
                    <?php $locs = $conn->query("SELECT * FROM locations"); while ($l = $locs->fetch_assoc()) echo '<option value="'.$l['id'].'">'.htmlspecialchars($l['name']).'</option>'; ?>
                </select><br>
                <input type="number" name="current_stock" placeholder="Stock" min="0" required>
                <input type="text" name="unit" placeholder="Unit (e.g. kg, gram, pieces)" required><br>
                <input type="number" name="low_stock" placeholder="Low Stock" min="0"><br>
                <input type="number" name="min_stock" placeholder="Minimum Stock" min="0"><br>
                <input type="number" name="max_stock" placeholder="Maximum Stock" min="0"><br>
                <label><input type="checkbox" name="is_perishable"> Perishable</label><br>
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
    }
    function showEditItemModal(id) {
        document.getElementById('modal').style.display = 'block';
        fetch('includes/item_actions.php', {method:'POST',body:new URLSearchParams({action:'get',id:id})})
        .then(r=>r.json()).then(item=>{
            document.getElementById('modal-body').innerHTML = `
                <h3>Edit Item</h3>
                <form id="editItemForm">
                    <input type="hidden" name="id" value="${item.id}">
                    <input type="text" name="name" value="${item.name}" required><br>
                    <select name="category_id" required>
                        <option value="">Select Category</option>
                        <?php $cats = $conn->query("SELECT * FROM categories"); while ($c = $cats->fetch_assoc()) echo '<option value="'.$c['id'].'">'.htmlspecialchars($c['name']).'</option>'; ?>
                    </select><br>
                    <select name="location_id" required>
                        <option value="">Select Location</option>
                        <?php $locs = $conn->query("SELECT * FROM locations"); while ($l = $locs->fetch_assoc()) echo '<option value="'.$l['id'].'">'.htmlspecialchars($l['name']).'</option>'; ?>
                    </select><br>
                    <input type="number" name="current_stock" value="${item.current_stock}" min="0" required>
                    <input type="text" name="unit" value="${item.unit||''}" placeholder="Unit (e.g. kg, gram, pieces)" required><br>
                    <input type="number" name="low_stock" value="${item.low_stock||''}" min="0"><br>
                    <input type="number" name="min_stock" value="${item.min_stock||''}" min="0"><br>
                    <input type="number" name="max_stock" value="${item.max_stock||''}" min="0"><br>
                    <label><input type="checkbox" name="is_perishable" ${item.is_perishable==1?'checked':''}> Perishable</label><br>
                    <button type="submit" class="btn-primary">Update</button>
                </form>
                <div id="editItemMsg"></div>
            `;
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
                ${is_perishable ? '<input type="date" name="expiry_date" required><br>' : ''}
                <button type="submit" class="btn-primary">Add Stock</button>
            </form>
            <div id="updateStockMsg"></div>
        `;
        document.getElementById('updateStockForm').onsubmit = function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            fd.append('action', 'update_stock');
            fd.append('is_perishable', is_perishable);
            fetch('includes/stock_actions.php', {method:'POST',body:fd})
            .then(r=>r.json()).then(d=>{
                document.getElementById('updateStockMsg').innerText = d.message || (d.status=='success'?'Stock updated!':'Error');
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
