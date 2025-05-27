// Function to toggle modal visibility using the 'is-hidden' class
function toggleModal(modalId, show) {
    console.log(`[toggleModal] Attempting to toggle: ${modalId}, show: ${show}`);
    const modal = document.getElementById(modalId);
    if (modal) {
        if (show) {
            modal.classList.remove('is-hidden');
            modal.style.display = 'block'; // Explicitly set display to block
            modal.style.visibility = 'visible'; // Ensure visibility
            console.log(`[toggleModal] ${modalId} class 'is-hidden' removed, display set to block, visibility set to visible. ClassList: ${modal.classList}`);
        } else {
            modal.classList.add('is-hidden');
            modal.style.display = 'none'; // Explicitly set display to none
            modal.style.visibility = 'hidden'; // Ensure hidden visibility
            console.log(`[toggleModal] ${modalId} class 'is-hidden' added, display set to none, visibility set to hidden. ClassList: ${modal.classList}`);
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

document.addEventListener('DOMContentLoaded', function() {
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
                    // location.reload(); // Reload page to show new category - Temporarily commented out for debugging
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
                    // location.reload(); // Reload page to show updated stock and logs - Temporarily commented out for debugging
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

        let rowClass = '';
        if (isLowStock) rowClass = 'alert alert--warning';
        if (isOutStock) rowClass = 'alert alert--error';
        newRow.classList.add('table__row', rowClass);

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
                    <a href="index.php?page=edit_item&id=${item.id}" class="btn btn--primary">Edit</a>
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
