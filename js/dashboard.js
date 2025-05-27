document.addEventListener('DOMContentLoaded', function() {
    // Function to toggle modal visibility using the 'is-hidden' class
    function toggleModal(modalId, show) {
        console.log(`[toggleModal] Attempting to toggle: ${modalId}, show: ${show}`);
        const modal = document.getElementById(modalId);
        if (modal) {
            if (show) {
                modal.classList.remove('is-hidden');
                modal.style.visibility = 'visible'; // Ensure visibility
                console.log(`[toggleModal] ${modalId} class 'is-hidden' removed, visibility set to visible. ClassList: ${modal.classList}`);
            } else {
                modal.classList.add('is-hidden');
                modal.style.visibility = 'hidden'; // Ensure hidden visibility
                console.log(`[toggleModal] ${modalId} class 'is-hidden' added, visibility set to hidden. ClassList: ${modal.classList}`);
            }
        } else {
            console.error(`[toggleModal] Error: Modal with ID ${modalId} not found.`);
        }
    }

    // Main tab switching logic
    function showTab(tabId) {
        console.log(`Attempting to show tab: ${tabId}`); // Debug log

        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
            console.log(`Hiding content: ${content.id}`); // Debug log
        });

        // Deactivate all tab buttons
        document.querySelectorAll('.main-tabs .tab-button').forEach(button => {
            button.classList.remove('active');
            console.log(`Deactivating button: ${button.dataset.tabContent}`); // Debug log
        });

        // Hide all tab-specific buttons
        document.querySelectorAll('.tab-buttons').forEach(buttonContainer => {
            buttonContainer.style.display = 'none';
        });

        // Show the selected tab content
        const selectedTabContent = document.getElementById(tabId);
        if (selectedTabContent) {
            selectedTabContent.style.display = 'block';
            console.log(`Showing content: ${selectedTabContent.id}`); // Debug log
        } else {
            console.log(`Error: Tab content with ID ${tabId} not found.`); // Debug log
        }

        // Activate the clicked tab button
        const clickedTabButton = document.querySelector(`.main-tabs .tab-button[data-tab-content="${tabId}"]`);
        if (clickedTabButton) {
            clickedTabButton.classList.add('active');
            console.log(`Activating button: ${clickedTabButton.dataset.tabContent}`); // Debug log
        } else {
            console.log(`Error: Tab button with data-tab-content ${tabId} not found.`); // Debug log
        }

        // Show buttons relevant to the active tab
        if (tabId === 'items-content') {
            document.getElementById('items-buttons').style.display = 'flex';
        } else if (tabId === 'categories-content') {
            document.getElementById('categories-buttons').style.display = 'flex';
        } else if (tabId === 'tracking-content') {
            document.getElementById('tracking-buttons').style.display = 'flex';
        }

        // If switching to items tab, ensure "All Items" sub-tab is active and filter is applied
        if (tabId === 'items-content') {
            const categoryTabs = document.querySelectorAll('.category-tabs .tab-button');
            if (categoryTabs.length > 0) {
                categoryTabs.forEach(btn => btn.classList.remove('active'));
                const allItemsTab = document.querySelector(`.category-tabs .tab-button[data-tab="all"]`);
                if (allItemsTab) {
                    allItemsTab.classList.add('active');
                }
            }
            filterItems('all'); // Re-apply filter to show all items
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
                        console.error('Fetch error:', error);
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
                    // location.reload(); // Reload page to show new item - Temporarily commented out for debugging
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
