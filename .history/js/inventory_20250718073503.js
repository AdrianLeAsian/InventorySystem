// Function to toggle modal visibility using the 'is-hidden' class
function toggleModal(modalId, show) {
    console.log(`[toggleModal] Attempting to toggle: ${modalId}, show: ${show}`);
    const modal = document.getElementById(modalId);
    if (modal) {
        if (show) {
            modal.classList.add('modal--active');
            console.log(`[toggleModal] ${modalId} class 'modal--active' added. ClassList: ${modal.classList}`);
        } else {
            modal.classList.remove('modal--active');
            console.log(`[toggleModal] ${modalId} class 'modal--active' removed. ClassList: ${modal.classList}`);
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
                        addOrUpdateItemRow(data.item);
                    }
                    // location.reload(); // Reload page to show new item - Uncommented for now as a fallback
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

            const formData = new FormData(logStockForm);
            formData.append('stock_type', stockType); // Append stock_type based on button clicked

            fetch('ajax/log_stock.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayGUIMessage(data.message, 'success');
                    toggleModal('logStockModal', false);
                    if (data.updated_item && data.new_log) {
                        updateItemStockAndLogActivity(data.updated_item, data.new_log);
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

    const importItemsForm = document.getElementById('importItemsForm');
    const excelFileInput = document.getElementById('excelFile');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const fileUploadArea = document.getElementById('fileUploadArea');
    const importResultDiv = document.getElementById('importResult');
    const importProgressContainer = document.getElementById('importProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const importSubmitBtn = document.getElementById('importSubmitBtn');

    if (importItemsForm) {
        // Handle file selection via input click
        excelFileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileNameDisplay.textContent = this.files[0].name;
                fileNameDisplay.style.display = 'block';
            } else {
                fileNameDisplay.textContent = '';
                fileNameDisplay.style.display = 'none';
            }
        });

        // Handle drag and drop
        if (fileUploadArea) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileUploadArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.add('highlight'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.remove('highlight'), false);
            });

            fileUploadArea.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                excelFileInput.files = files; // Assign dropped files to the input
                if (files.length > 0) {
                    fileNameDisplay.textContent = files[0].name;
                    fileNameDisplay.style.display = 'block';
                }
            }
        }

        importItemsForm.addEventListener('submit', function(event) {
            event.preventDefault();

            if (excelFileInput.files.length === 0) {
                displayGUIMessage('Please select a file to import.', 'error');
                return;
            }

            const formData = new FormData(importItemsForm);
            
            // Reset and show progress bar
            importResultDiv.style.display = 'none';
            importResultDiv.classList.remove('alert--success', 'alert--error');
            progressBar.style.width = '0%';
            progressText.textContent = '0%';
            importProgressContainer.style.display = 'block';
            importSubmitBtn.disabled = true; // Disable button during import

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax/import_items.php', true);

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                    progressText.textContent = percent + '%';
                }
            });

            xhr.onload = function() {
                importSubmitBtn.disabled = false; // Re-enable button
                importProgressContainer.style.display = 'none'; // Hide progress bar

                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            importResultDiv.classList.add('alert--success');
                            importResultDiv.innerHTML = `<strong>Import Successful!</strong><br>${data.imported_count} items imported, ${data.skipped_count} duplicates skipped.`;
                            displayGUIMessage(data.message, 'success');
                            // Optionally reload or update table
                            location.reload(); 
                        } else {
                            importResultDiv.classList.add('alert--error');
                            importResultDiv.innerHTML = `<strong>Import Failed:</strong><br>${data.message}`;
                            displayGUIMessage('Error: ' + data.message, 'error');
                        }
                    } catch (e) {
                        importResultDiv.classList.add('alert--error');
                        importResultDiv.innerHTML = `<strong>Error:</strong> Invalid server response.`;
                        displayGUIMessage('An unexpected error occurred.', 'error');
                        console.error('Parsing error:', e, 'Response:', xhr.responseText);
                    }
                } else {
                    importResultDiv.classList.add('alert--error');
                    importResultDiv.innerHTML = `<strong>Error:</strong> Server responded with status ${xhr.status}.`;
                    displayGUIMessage('Server error during import.', 'error');
                }
                importResultDiv.style.display = 'block';
            };

            xhr.onerror = function() {
                importSubmitBtn.disabled = false; // Re-enable button
                importProgressContainer.style.display = 'none'; // Hide progress bar
                importResultDiv.classList.add('alert--error');
                importResultDiv.innerHTML = `<strong>Error:</strong> Network error during import.`;
                displayGUIMessage('Network error during import.', 'error');
                importResultDiv.style.display = 'block';
            };

            xhr.send(formData);
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
                        addOrUpdateItemRow(data.item); // Update the table row
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
        // Update existing row
        existingRow.cells[0].textContent = item.name;
        existingRow.cells[1].textContent = item.category_name;
        existingRow.cells[2].textContent = item.quantity;
        existingRow.cells[3].textContent = item.low_stock_threshold;
        existingRow.cells[4].textContent = item.unit;
        existingRow.cells[5].textContent = item.location || 'N/A';
        existingRow.cells[6].textContent = item.formatted_updated_at; // Use formatted date
        
        // Update status
        const statusCell = existingRow.cells[7];
        let status = 'OK';
        let status_class = 'btn btn--success';
        const isLowStock = (item.low_stock_threshold > 0 && item.quantity <= item.low_stock_threshold);
        const isOutStock = (item.quantity == 0);

        if (isLowStock) {
            status = 'Low Stock';
            status_class = 'btn btn--warning';
        }
        if (isOutStock) {
            status = 'Out of Stock';
            status_class = 'btn btn--danger';
        }
        statusCell.innerHTML = `<span class="${status_class}">${status}</span>`;

        // Update row class for styling
        existingRow.classList.remove('alert--warning', 'alert--error');
        if (isLowStock) existingRow.classList.add('alert--warning');
        if (isOutStock) existingRow.classList.add('alert--error');

    } else {
        console.log('[addOrUpdateItemRow] Creating new row for item:', item.name);
        // Create new row
        const newRow = document.createElement('tr');
        newRow.setAttribute('data-category-id', item.category_id);
        newRow.setAttribute('data-item-id', item.id); // Add item ID for future updates/deletes

        let status = 'OK';
        let status_class = 'btn btn--success';
        const isLowStock = (item.low_stock_threshold > 0 && item.quantity <= item.low_stock_threshold);
        const isOutStock = (item.quantity == 0);

        if (isLowStock) {
            status = 'Low Stock';
            status_class = 'btn btn--warning';
        }
        if (isOutStock) {
            status = 'Out of Stock';
            status_class = 'btn btn--danger';
        }

        newRow.classList.add('table__row');
        if (isLowStock) newRow.classList.add('alert', 'alert--warning');
        if (isOutStock) newRow.classList.add('alert', 'alert--error');

        newRow.innerHTML = `
            <td class="table__cell" title="${item.name}">${item.name}</td>
            <td class="table__cell">${item.category_name}</td>
            <td class="table__cell">${item.quantity}</td>
            <td class="table__cell">${item.low_stock_threshold}</td>
            <td class="table__cell">${item.unit}</td>
            <td class="table__cell">${item.location || 'N/A'}</td>
            <td class="table__cell">${item.formatted_updated_at}</td>
            <td class="table__cell">
                <span class="${status_class}">${status}</span>
            </td>
            <td class="table__cell">
                <div class="d-flex gap-2">
                    <button class="btn btn--primary" onclick="openEditItemModal(${item.id})">Edit</button>
                    <a href="index.php?page=delete_item&id=${item.id}" class="btn btn--danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                </div>
            </td>
        `;
        console.log('[addOrUpdateItemRow] Appending new row to table body.');
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
    const trackingTableBody = document.querySelector('#inventoryTrackingTable tbody');
    if (!trackingTableBody) {
        console.error('[updateItemStockAndLogActivity] Tracking table body not found.');
        return;
    }
    console.log('[updateItemStockAndLogActivity] Tracking table body found:', trackingTableBody);

    const newLogRow = document.createElement('tr');
    newLogRow.classList.add('table__row');
    newLogRow.setAttribute('data-log-id', newLog.id);

    const logTypeClass = (newLog.activity_type === 'stock_in') ? 'success' : 'danger';
    const formattedLogType = newLog.activity_type.replace('stock_', '').replace('_', ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');

    newLogRow.innerHTML = `
        <td class="table__cell">${newLog.id}</td>
        <td class="table__cell">${newLog.entity_name || 'N/A'}</td>
        <td class="table__cell">
            <span class="btn btn--${logTypeClass}">
                ${formattedLogType}
            </span>
        </td>
        <td class="table__cell">${newLog.quantity_change}</td>
        <td class="table__cell">${newLog.reason}</td>
        <td class="table__cell">${newLog.log_date}</td>
    `;

    // Add the new log row to the top of the table (most recent first)
    if (trackingTableBody.firstChild && trackingTableBody.firstChild.tagName === 'TR') {
        trackingTableBody.insertBefore(newLogRow, trackingTableBody.firstChild);
    } else {
        trackingTableBody.appendChild(newLogRow);
    }
    console.log('[updateItemStockAndLogActivity] New log row appended. Current tracking table body children count:', trackingTableBody.children.length);
}
