// Function to toggle modal visibility using the 'is-hidden' class
function toggleModal(modalId, show) {
    console.log(`[toggleModal] Attempting to toggle: ${modalId}, show: ${show}`);
    const modal = document.getElementById(modalId);
    if (modal) {
        if (show) {
            modal.classList.add('modal--active');
            modal.classList.remove('is-hidden');
            modal.style.display = 'flex';
            console.log(`[toggleModal] ${modalId} shown. ClassList: ${modal.classList}`);
        } else {
            modal.classList.remove('modal--active');
            modal.classList.add('is-hidden');
            modal.style.display = 'none';
            console.log(`[toggleModal] ${modalId} hidden. ClassList: ${modal.classList}`);
        }
    } else {
        console.error(`[toggleModal] Error: Modal with ID ${modalId} not found.`);
    }
}

// Function to display GUI messages
function displayGUIMessage(message, type) {
    const msgContainer = document.getElementById('gui-message-container');
    const msgElement = document.getElementById('gui-message');
    const msgText = document.getElementById('gui-message-text');

    if (msgContainer && msgElement && msgText) {
        msgText.textContent = message;
        msgElement.classList.remove('alert--success', 'alert--error');
        if (type === 'success') {
            msgElement.classList.add('alert--success');
        } else if (type === 'error') {
            msgElement.classList.add('alert--error');
        }
        msgContainer.style.display = 'block';

        // Hide message after 5 seconds
        setTimeout(() => {
            msgContainer.style.display = 'none';
        }, 5000);
    }
}

// Tab filtering for items (within the items-content tab)
function filterItems(categoryId) {
    var table = document.getElementById("inventoryItemsTable");
    if (!table) return; // Ensure table exists before trying to access it

    var tr = table.getElementsByTagName("tr");
    
    for (var i = 1; i < tr.length; i++) { // Start from 1 to skip header row
        var tdCategory = tr[i].getAttribute('data-category-id');
        if (categoryId === 'all' || tdCategory === categoryId) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Event listener for category filter select
    const categoryFilterSelect = document.getElementById('categoryFilterSelect');
    if (categoryFilterSelect) {
        categoryFilterSelect.addEventListener('change', function() {
            filterItems(this.value);
            // Also re-apply search filter if there's a search term
            const searchItemsInput = document.getElementById('searchItemsInput');
            if (searchItemsInput && searchItemsInput.value !== '') {
                searchItemsInput.dispatchEvent(new Event('keyup'));
            }
        });
    }

    // Search items (simple client-side search by name and category for now)
    const searchItemsInput = document.getElementById('searchItemsInput');
    if (searchItemsInput) {
        searchItemsInput.addEventListener('keyup', function() {
            var searchTerm = this.value.toLowerCase();
            var table = document.getElementById("inventoryItemsTable");
            if (!table) return; // Ensure table exists

            var tr = table.getElementsByTagName("tr");
            // Get the currently selected category from the dropdown
            const currentCategoryId = categoryFilterSelect ? categoryFilterSelect.value : 'all';

            for (var i = 1; i < tr.length; i++) { // Start from 1 to skip header row
                var row = tr[i];
                var nameCell = row.cells[0] ? row.cells[0].textContent.toLowerCase() : '';
                var categoryCell = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
                var itemCategoryId = row.getAttribute('data-category-id');

                var matchesSearch = nameCell.includes(searchTerm) || categoryCell.includes(searchTerm);
                var matchesCategory = (currentCategoryId === 'all' || itemCategoryId === currentCategoryId);

                if (matchesSearch && matchesCategory) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            }
        });
    }

    // Placeholder for Export button functionality
    const exportItemsBtn = document.getElementById('exportItemsBtn');
    if (exportItemsBtn) {
        exportItemsBtn.addEventListener('click', function() {
            alert('Export functionality to be implemented. This would typically export the currently visible items to CSV.');
        });
    }

    // Attach event listeners for modal toggle buttons
    const addItemBtn = document.getElementById('addItemBtn');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function(event) {
            event.stopPropagation(); // Prevent event from bubbling up
            toggleModal('addItemModal', true);
        });
    } else {
        console.log('Error: addItemBtn not found.');
    }

    const importItemsBtn = document.getElementById('importItemsBtn');
    if (importItemsBtn) {
        importItemsBtn.addEventListener('click', function(event) {
            event.stopPropagation();
            toggleModal('importItemsModal', true);
        });
    } else {
        console.log('Error: importItemsBtn not found.');
    }

    const logStockBtn = document.getElementById('logStockBtn');
    if (logStockBtn) {
        logStockBtn.addEventListener('click', function(event) {
            event.stopPropagation(); // Prevent event from bubbling up
            toggleModal('logStockModal', true);
        });
    } else {
        console.log('Error: logStockBtn not found.');
    }

    const addCategoryBtn = document.getElementById('addCategoryBtn');
    if (addCategoryBtn) {
        addCategoryBtn.addEventListener('click', function(event) {
            event.stopPropagation(); // Prevent event from bubbling up
            toggleModal('addCategoryModal', true);
        });
    } else {
        console.log('Error: addCategoryBtn not found.');
    }

    // Attach event listeners for modal cancel buttons
    document.querySelectorAll('.cancel-modal-btn').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.dataset.modalId;
            toggleModal(modalId, false);
        });
    });

    // When the user clicks anywhere outside of the modal, close it
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.add('is-hidden');
        }
    });

    // Barcode scanner logic
    const barcodeInput = document.getElementById('barcode_scanner_input');
    const itemSelect = document.querySelector('select[name="item_id"]'); // Select by name as ID might conflict if multiple forms
    const quantityInput = document.querySelector('input[name="quantity_change"]');
    const barcodeStatus = document.getElementById('barcode_status');

    if (barcodeInput) {
        barcodeInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter' || event.keyCode === 13) {
                event.preventDefault(); // Prevent form submission if it's part of one
                const barcode = barcodeInput.value.trim();
                if (barcode === '') {
                    barcodeStatus.textContent = 'Please enter a barcode.';
                    barcodeStatus.style.color = 'red';
                    return;
                }
                barcodeStatus.textContent = 'Searching...';
                barcodeStatus.style.color = 'orange';

                fetch('get_item_by_barcode.php?barcode=' + encodeURIComponent(barcode))
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.item) {
                            const item = data.item;
                            let itemFoundInSelect = false;
                            for (let i = 0; i < itemSelect.options.length; i++) {
                                if (itemSelect.options[i].value == item.id) {
                                    itemSelect.value = item.id;
                                    itemFoundInSelect = true;
                                    break;
                                }
                            }
                            if (itemFoundInSelect) {
                                barcodeStatus.textContent = 'Item found: ' + item.name;
                                barcodeStatus.style.color = 'green';
                                quantityInput.focus(); // Focus on quantity field
                                barcodeInput.value = ''; // Clear barcode input
                            } else {
                                barcodeStatus.textContent = 'Item found but not in dropdown (refresh page?)';
                                barcodeStatus.style.color = 'red';
                            }
                        } else {
                            barcodeStatus.textContent = data.message || 'Item not found or error.';
                            barcodeStatus.style.color = 'red';
                            itemSelect.value = ''; // Clear selection
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        barcodeStatus.textContent = 'Error fetching item. See console.';
                        barcodeStatus.style.color = 'red';
                    });
            }
        });
    }

    // AJAX Form Submissions
    const addItemForm = document.getElementById('addItemForm');
    if (addItemForm) {
        addItemForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(addItemForm);

            fetch('ajax/add_item.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayGUIMessage(data.message, 'success');
                    toggleModal('addItemModal', false);
                    if (data.item) {
                        addOrUpdateItemRow(data.item); // Update the table with the new item
                    }
                } else {
                    displayGUIMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                displayGUIMessage('An error occurred while adding the item.', 'error');
            });
        });
    }

    const addCategoryForm = document.getElementById('addCategoryForm');
    if (addCategoryForm) {
        addCategoryForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(addCategoryForm);

            fetch('ajax/add_category.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayGUIMessage(data.message, 'success');
                    toggleModal('addCategoryModal', false);
                    if (data.category) { // Check if category data is returned
                        addOrUpdateCategoryRow(data.category);
                    }
                } else {
                    displayGUIMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                displayGUIMessage('An error occurred while adding the category.', 'error');
            });
        });
    }

    const logStockForm = document.getElementById('logStockForm');
    if (logStockForm) {
        logStockForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const submitter = event.submitter; // Get the button that was clicked
            const stockType = submitter ? submitter.value : ''; // 'stock_in' or 'stock_out'

            const itemId = logStockForm.querySelector('[name="item_id"]').value;
            const quantityChange = parseInt(logStockForm.querySelector('[name="quantity_change"]').value);
            const reason = logStockForm.querySelector('[name="reason"]').value.trim();

            // Client-side validation for logStockForm
            if (!itemId || isNaN(quantityChange) || quantityChange <= 0 || !reason) {
                displayGUIMessage('Please select an item, enter a positive numeric quantity, and provide a reason.', 'error');
                return; // Stop form submission
            }

            const formData = new FormData(logStockForm);
            formData.append('stock_type', stockType); // Append stock_type based on button clicked

            fetch('ajax/log_stock.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('[logStockForm Success] Received data:', data); // Log received data
                if (data.success) {
                    displayGUIMessage(data.message, 'success');
                    toggleModal('logStockModal', false);
                    if (data.updated_item && data.new_log) {
                        console.log('[logStockForm Success] Calling updateItemStockAndLogActivity.');
                        updateItemStockAndLogActivity(data.updated_item, data.new_log);
                    } else {
                        console.error('[logStockForm Success] Missing updated_item or new_log in response.');
                    }
                } else {
                    displayGUIMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                displayGUIMessage('An error occurred during stock movement.', 'error');
            });
        });
    }

    const importItemsForm = document.getElementById('importForm'); // Corrected ID
    const excelFileInput = document.getElementById('excelFile');
    const importSummaryDiv = document.getElementById('importSummary');
    const totalProcessedSpan = document.getElementById('totalProcessed');
    const itemsAddedSpan = document.getElementById('itemsAdded');
    const itemsUpdatedSpan = document.getElementById('itemsUpdated');
    const itemsSkippedSpan = document.getElementById('itemsSkipped');
    const skippedLogLinkDiv = document.getElementById('skippedLogLink');
    const downloadLogAnchor = document.getElementById('downloadLog');
    const importErrorDiv = document.getElementById('importError');

    if (importItemsForm) {
        importItemsForm.addEventListener('submit', function(event) {
            event.preventDefault();

            if (excelFileInput.files.length === 0) {
                displayGUIMessage('Please select a file to import.', 'error');
                return;
            }

            const formData = new FormData(importItemsForm);
            formData.append('updateExisting', document.getElementById('updateExisting').checked ? 'true' : 'false');

            // Reset display areas
            importSummaryDiv.style.display = 'none';
            importErrorDiv.style.display = 'none';
            importErrorDiv.textContent = '';
            skippedLogLinkDiv.style.display = 'none';

            // Show a loading indicator or disable button if desired
            // importSubmitBtn.disabled = true; 

            fetch('ajax/import_items.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // importSubmitBtn.disabled = false; // Re-enable button

                if (data.success) {
                    totalProcessedSpan.textContent = data.totalProcessed;
                    itemsAddedSpan.textContent = data.itemsAdded;
                    itemsUpdatedSpan.textContent = data.itemsUpdated;
                    itemsSkippedSpan.textContent = data.itemsSkipped;
                    importSummaryDiv.style.display = 'block';

                    if (data.skippedLogFile) {
                        downloadLogAnchor.href = data.skippedLogFile;
                        skippedLogLinkDiv.style.display = 'block';
                    }
                    displayGUIMessage(data.message, 'success');
                    // Optionally close modal after successful import
                    // toggleModal('importItemsModal', false);
                } else {
                    importErrorDiv.textContent = data.message;
                    importErrorDiv.style.display = 'block';
                    displayGUIMessage('Error: ' + data.message, 'error');
                }
                // Refresh the inventory table to show newly added/updated items
                // This assumes a function exists to refresh the main inventory table
                // For now, a simple reload might be necessary if no such function exists.
                // If addOrUpdateItemRow is robust enough, we could iterate through data.addedItems and data.updatedItems
                location.reload(); // Temporarily reload to see changes
            })
            .catch(error => {
                // importSubmitBtn.disabled = false; // Re-enable button
                importErrorDiv.textContent = 'An unexpected error occurred during import: ' + error.message;
                importErrorDiv.style.display = 'block';
                displayGUIMessage('An unexpected error occurred during import.', 'error');
                console.error('Error:', error);
            });
        });
    }
});

// Function to open the Edit Item Modal and populate it with data
function openEditItemModal(itemId) {
    console.log(`[openEditItemModal] Opening modal for item ID: ${itemId}`);
    fetch(`ajax/get_item.php?id=${itemId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.item) {
                const item = data.item;
                document.getElementById('edit_item_id').value = item.id;
                document.getElementById('edit_item_name').value = item.name;
                document.getElementById('edit_item_category_id').value = item.category_id;
                document.getElementById('edit_item_barcode').value = item.barcode;
                document.getElementById('edit_item_quantity').value = item.quantity;
                document.getElementById('edit_item_unit').value = item.unit;
                document.getElementById('edit_item_low_stock_threshold').value = item.low_stock_threshold;
                document.getElementById('edit_item_min_stock_level').value = item.min_stock_level; // New field
                document.getElementById('edit_item_max_stock_level').value = item.max_stock_level; // New field
                document.getElementById('edit_item_description').value = item.description;
                document.getElementById('edit_item_location').value = item.location;
                // The reason field for edit is for the update action, not to display previous reason
                document.getElementById('edit_item_reason').value = ''; 
                toggleModal('editItemModal', true);
            } else {
                displayGUIMessage('Error fetching item data: ' + data.message, 'error');
                console.error('Error fetching item data:', data.message);
            }
        })
        .catch(error => {
            displayGUIMessage('An error occurred while fetching item data.', 'error');
            console.error('Error:', error);
        });
}

// Function to open the Edit Category Modal and populate it with data
function openEditCategoryModal(categoryId) {
    console.log(`[openEditCategoryModal] Opening modal for category ID: ${categoryId}`);
    fetch(`ajax/get_category.php?id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.category) {
                const category = data.category;
                document.getElementById('edit_category_id').value = category.id;
                document.getElementById('edit_category_name').value = category.name;
                document.getElementById('edit_category_description').value = category.description;
                toggleModal('editCategoryModal', true);
            } else {
                displayGUIMessage('Error fetching category data: ' + data.message, 'error');
                console.error('Error fetching category data:', data.message);
            }
        })
        .catch(error => {
            displayGUIMessage('An error occurred while fetching category data.', 'error');
            console.error('Error:', error);
        });
}

// AJAX Form Submissions for Edit Modals
document.addEventListener('DOMContentLoaded', function() {
    const editItemForm = document.getElementById('editItemForm');
    if (editItemForm) {
        editItemForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(editItemForm);

            // Client-side validation for editItemForm
            const itemName = editItemForm.querySelector('[name="item_name"]').value.trim();
            const itemQuantity = parseInt(editItemForm.querySelector('[name="item_quantity"]').value);
            const lowStockThreshold = parseInt(editItemForm.querySelector('[name="item_low_stock_threshold"]').value);
            const minStockLevel = parseInt(editItemForm.querySelector('[name="item_min_stock_level"]').value);
            const maxStockLevel = parseInt(editItemForm.querySelector('[name="item_max_stock_level"]').value);
            const reason = editItemForm.querySelector('[name="item_reason"]').value.trim();

            if (
                !itemName ||
                isNaN(itemQuantity) || itemQuantity < 0 ||
                isNaN(lowStockThreshold) || lowStockThreshold < 0 ||
                isNaN(minStockLevel) || minStockLevel < 0 ||
                isNaN(maxStockLevel) || maxStockLevel < 0 ||
                !reason
            ) {
                displayGUIMessage('Please fill all required fields correctly. Quantity, thresholds, and levels must be non-negative numbers, and reason is required.', 'error');
                return; // Stop form submission
            }

            fetch('ajax/update_item.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayGUIMessage(data.message, 'success');
                    toggleModal('editItemModal', false);
                    if (data.item) {
                        // Recalculate stock status before updating the row
                        const item = data.item;
                        const quantity = item.quantity;
                        const low_stock_threshold = item.low_stock_threshold;
                        const min_stock_level = item.min_stock_level;
                        const max_stock_level = item.max_stock_level;

                        let stock_status = 'normal';
                        if (quantity == 0) {
                            stock_status = 'out_of_stock';
                        } else if (quantity <= low_stock_threshold || quantity <= min_stock_level) {
                            stock_status = 'low_stock';
                        } else if (max_stock_level > 0 && quantity >= max_stock_level) {
                            stock_status = 'surplus';
                        }
                        item.stock_status = stock_status; // Add stock_status to the item object

                        addOrUpdateItemRow(item); // Update the table with the updated item
                    }
                } else {
                    displayGUIMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                displayGUIMessage('An error occurred while updating the item.', 'error');
            });
        });
    }

    const editCategoryForm = document.getElementById('editCategoryForm');
    if (editCategoryForm) {
        editCategoryForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(editCategoryForm);

            fetch('ajax/update_category.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayGUIMessage(data.message, 'success');
                    toggleModal('editCategoryModal', false);
                    if (data.category) {
                        addOrUpdateCategoryRow(data.category); // Update the table row
                    }
                } else {
                    displayGUIMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                displayGUIMessage('An error occurred while updating the category.', 'error');
            });
        });
    }
});

// Function to add or update an item row in the inventory table
function addOrUpdateItemRow(item) {
    console.log('[addOrUpdateItemRow] Called with item:', item);
    const tableBody = document.querySelector('#inventoryItemsTable tbody');
    if (!tableBody) {
        console.error('[addOrUpdateItemRow] Inventory table body not found.');
        return;
    }
    console.log('[addOrUpdateItemRow] Table body found:', tableBody);

    let existingRow = document.querySelector(`tr[data-item-id="${item.id}"]`);
    if (existingRow) {
        console.log('[addOrUpdateItemRow] Updating existing row:', existingRow);
        // Update existing row to match the current HTML structure in pages/inventory.php
        existingRow.cells[0].textContent = item.name;
        existingRow.cells[0].title = item.name;
        existingRow.cells[1].textContent = item.category_name;
        existingRow.cells[2].textContent = item.barcode || 'N/A';
        existingRow.cells[3].textContent = item.quantity;
        existingRow.cells[4].textContent = item.unit;
        existingRow.cells[5].textContent = item.location || 'N/A';
        
        // Update status and color-coded indicators
        const statusCell = existingRow.cells[6];
        let status_display = 'Normal';
        let status_class_btn = 'btn btn--success';
        let row_status_class = 'stock-normal';

        if (item.stock_status === 'out_of_stock') {
            status_display = 'Out of Stock';
            status_class_btn = 'btn btn--danger';
            row_status_class = 'stock-out_of_stock';
        } else if (item.stock_status === 'low_stock') {
            status_display = 'Low Stock';
            status_class_btn = 'btn btn--warning';
            row_status_class = 'stock-low_stock';
        } else if (item.stock_status === 'surplus') {
            status_display = 'Surplus';
            status_class_btn = 'btn btn--info';
            row_status_class = 'stock-surplus';
        }
        
        statusCell.innerHTML = `<span class="${status_class_btn}">${status_display}</span>`;

        // Update row class for styling
        existingRow.className = ''; // Clear existing classes
        existingRow.classList.add('table__row', `stock-${item.stock_status}`);

    } else {
        console.log('[addOrUpdateItemRow] Creating new row for item:', item.name);
        // Create new row to match the current HTML structure in pages/inventory.php
        const newRow = document.createElement('tr');
        newRow.setAttribute('data-category-id', item.category_id);
        newRow.setAttribute('data-item-id', item.id);

        let status_display = 'Normal';
        let status_class_btn = 'btn btn--success';

        if (item.stock_status === 'out_of_stock') {
            status_display = 'Out of Stock';
            status_class_btn = 'btn btn--danger';
        } else if (item.stock_status === 'low_stock') {
            status_display = 'Low Stock';
            status_class_btn = 'btn btn--warning';
        } else if (item.stock_status === 'surplus') {
            status_display = 'Surplus';
            status_class_btn = 'btn btn--info';
        }

        newRow.classList.add('table__row', `stock-${item.stock_status}`);

        newRow.innerHTML = `
            <td class="table__cell" title="${item.name}">${item.name}</td>
            <td class="table__cell">${item.category_name}</td>
            <td class="table__cell">${item.barcode || 'N/A'}</td>
            <td class="table__cell">${item.quantity}</td>
            <td class="table__cell">${item.unit}</td>
            <td class="table__cell">${item.location || 'N/A'}</td>
            <td class="table__cell">
                <span class="${status_class_btn}">${status_display}</span>
            </td>
            <td class="table__cell">
                <div class="d-flex gap-2">
                    <button class="btn btn--primary" onclick="openEditItemModal(${item.id})">Edit</button>
                    <a href="index.php?page=delete_item&id=${item.id}" class="btn btn--danger">Delete</a>
                </div>
            </td>
        `;
        tableBody.appendChild(newRow);
        console.log('[addOrUpdateItemRow] New row appended. Current table body children count:', tableBody.children.length);
    }

    // Re-apply current filters to ensure the new item is visible if it matches
    console.log('[addOrUpdateItemRow] Re-applying filters and search.');
    const currentCategoryId = document.getElementById('categoryFilterSelect').value;
    filterItems(currentCategoryId);
    const searchItemsInput = document.getElementById('searchItemsInput');
    if (searchItemsInput && searchItemsInput.value !== '') {
        searchItemsInput.dispatchEvent(new Event('keyup'));
    }
    console.log('[addOrUpdateItemRow] Filters and search re-applied.');
}

// Function to add or update a category row in the categories table
function addOrUpdateCategoryRow(category) {
    console.log('[addOrUpdateCategoryRow] Called with category:', category);
    const tableBody = document.querySelector('#inventoryCategoriesTable tbody');
    if (!tableBody) {
        console.error('[addOrUpdateCategoryRow] Categories table body not found.');
        return;
    }
    console.log('[addOrUpdateCategoryRow] Categories table body found:', tableBody);

    let existingRow = document.querySelector(`tr[data-category-id="${category.id}"]`);
    if (existingRow) {
        console.log('[addOrUpdateCategoryRow] Updating existing category row:', existingRow);
        existingRow.cells[0].textContent = category.id;
        existingRow.cells[1].textContent = category.name;
        existingRow.cells[2].innerHTML = category.description ? category.description.replace(/\n/g, '<br>') : ''; // Handle newlines
        existingRow.cells[3].textContent = category.created_at || 'N/A';
    } else {
        console.log('[addOrUpdateCategoryRow] Creating new category row for category:', category.name);
        const newRow = document.createElement('tr');
        newRow.classList.add('table__row');
        newRow.setAttribute('data-category-id', category.id);

        newRow.innerHTML = `
            <td class="table__cell">${category.id}</td>
            <td class="table__cell">${category.name}</td>
            <td class="table__cell">${category.description ? category.description.replace(/\n/g, '<br>') : ''}</td>
            <td class="table__cell">${category.created_at || 'N/A'}</td>
            <td class="table__cell">
                <div class="d-flex gap-2">
                    <button class="btn btn--primary" onclick="openEditCategoryModal(${category.id})">Edit</button>
                    <a href="index.php?page=delete_category&id=${category.id}" class="btn btn--danger" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                </div>
            </td>
        `;
        console.log('[addOrUpdateCategoryRow] Appending new category row to table body.');
        tableBody.appendChild(newRow);
        console.log('[addOrUpdateCategoryRow] New category row appended. Current table body children count:', tableBody.children.length);
    }
}

// Function to update item stock in inventory table and add new log entry to tracking table
function updateItemStockAndLogActivity(updatedItem, newLog) {
    console.log('[updateItemStockAndLogActivity] Called with updatedItem:', updatedItem, 'and newLog:', newLog);

    // 1. Update item in inventoryItemsTable
    addOrUpdateItemRow(updatedItem); // Reuse existing function to update item row

    // 2. Add new log entry to inventoryTrackingTable
    console.log('[updateItemStockAndLogActivity] Calling addOrUpdateLogEntryRow with newLog:', newLog);
    addOrUpdateLogEntryRow(newLog);
}

// Polling for real-time updates
// Polling for real-time updates
const POLLING_INTERVAL = 3000; // Poll every 3 seconds (3000 milliseconds)
let last_item_poll_timestamp = ''; // Stores the timestamp of the last successful item poll
let last_log_poll_timestamp = ''; // Stores the timestamp of the last successful log poll

// Function to fetch and update inventory items
function pollForUpdates() {
    // Poll for item updates
    fetch(`ajax/get_latest_item_updates.php?last_timestamp=${encodeURIComponent(last_item_poll_timestamp)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response for items was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.items.length > 0) {
                console.log('[pollForUpdates] Fetched updated items:', data.items);
                data.items.forEach(item => {
                    addOrUpdateItemRow(item); // Update or add the item row in the table
                });
                last_item_poll_timestamp = data.server_time; // Update the last_item_poll_timestamp
            } else if (!data.success) {
                console.error('[pollForUpdates] Error fetching item updates:', data.message);
            }
        })
        .catch(error => {
            console.error('[pollForUpdates] Item fetch error:', error);
            // Optionally display a non-intrusive error message to the user
            // displayGUIMessage('Failed to get real-time item updates. Please refresh the page.', 'error');
        });

    // Poll for activity log updates
    fetch(`ajax/get_latest_activity_logs.php?last_timestamp=${encodeURIComponent(last_log_poll_timestamp)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response for logs was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.logs.length > 0) {
                console.log('[pollForUpdates] Fetched updated logs:', data.logs);
                // Add new logs to the top of the tracking table
                data.logs.reverse().forEach(log => { // Reverse to add oldest first, so newest appears at top
                    addOrUpdateLogEntryRow(log);
                });
                last_log_poll_timestamp = data.server_time; // Update the last_log_poll_timestamp
            } else if (!data.success) {
                console.error('[pollForUpdates] Error fetching log updates:', data.message);
            }
        })
        .catch(error => {
            console.error('[pollForUpdates] Log fetch error:', error);
            // Optionally display a non-intrusive error message to the user
            // displayGUIMessage('Failed to get real-time log updates. Please refresh the page.', 'error');
        });
}

// Function to add or update a log entry row in the tracking table
function addOrUpdateLogEntryRow(log) {
    const trackingTableBody = document.querySelector('#inventoryTrackingTable tbody');
    if (!trackingTableBody) {
        console.error('[addOrUpdateLogEntryRow] Tracking table body not found.');
        return;
    }

    // Check if log entry already exists (optional, but good for preventing duplicates on re-poll)
    let existingRow = document.querySelector(`tr[data-log-id="${log.id}"]`);
    if (existingRow) {
        // If the log already exists, no need to re-add it.
        // For logs, updates are rare, so we primarily add new ones.
        return;
    }

    const newLogRow = document.createElement('tr');
    newLogRow.classList.add('table__row');
    newLogRow.setAttribute('data-log-id', log.id);

    const logTypeClass = (log.activity_type === 'stock_in') ? 'success' : 'danger';
    const formattedLogType = log.activity_type.replace('stock_', '').replace('_', ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');

    newLogRow.innerHTML = `
        <td class="table__cell">${log.id}</td>
        <td class="table__cell">${log.entity_name || 'N/A'}</td>
        <td class="table__cell">
            <span class="btn btn--${logTypeClass}">
                ${formattedLogType}
            </span>
        </td>
        <td class="table__cell">${log.quantity_change}</td>
        <td class="table__cell">${log.reason}</td>
        <td class="table__cell">${log.log_date}</td>
    `;

    // Add the new log row to the top of the table (most recent first)
    if (trackingTableBody.firstChild && trackingTableBody.firstChild.tagName === 'TR') {
        trackingTableBody.insertBefore(newLogRow, trackingTableBody.firstChild);
    } else {
        trackingTableBody.appendChild(newLogRow);
    }
    console.log('[addOrUpdateLogEntryRow] New log row appended. Current tracking table body children count:', trackingTableBody.children.length);
}


// Initial call to set the last_poll_timestamp and start polling
document.addEventListener('DOMContentLoaded', function() {
    // Initialize last_item_poll_timestamp and last_log_poll_timestamp
    // For simplicity, we'll let the first poll fetch everything if timestamps are empty.
    // In a more robust app, you might fetch the latest updated_at/log_date from the initially rendered data.

    // Start polling after the DOM is fully loaded
    setInterval(pollForUpdates, POLLING_INTERVAL);
});
