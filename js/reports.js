// js/reports.js

document.addEventListener('DOMContentLoaded', function() {
    const userRole = document.getElementById('userRole').value;

    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tab = button.dataset.tab;

            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            document.getElementById(tab).classList.add('active');

            // Load data for the active tab
            loadTabContent(tab);
        });
    });

    // Initial load of the active tab (Stock Summary)
    loadTabContent('stock-summary');

    function loadTabContent(tab) {
        switch (tab) {
            case 'stock-summary':
                loadStockSummary();
                break;
            case 'detailed-inventory':
                loadDetailedInventoryFilters(); // Load filters first
                loadDetailedInventory();
                break;
            case 'transaction-logs':
                loadTransactionLogsFilters(); // Load filters first
                loadTransactionLogs();
                break;
            case 'expiry-calendar':
                loadCalendarFilters(); // Load filters first
                loadExpiryCalendar();
                break;
        }
    }

    // --- Stock Summary Section ---
    function loadStockSummary() {
        fetch('ajax/reports_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'report_type=stock_summary'
        })
        .then(response => response.json())
        .then(data => {
            const container = document.querySelector('#stock-summary .summary-cards-container');
            container.innerHTML = ''; // Clear previous content

            if (data.success) {
                const summary = data.data;

                const cards = [
                    { title: 'Total Items', count: summary.total_items, description: 'All items in inventory', color: 'green' },
                    { title: 'Low Stock Items', count: summary.low_stock_items, description: 'Items below threshold', color: 'orange' },
                    { title: 'Out of Stock Items', count: summary.out_of_stock_items, description: 'Items with zero stock', color: 'red' },
                    { title: 'Nearing Expiry', count: summary.nearing_expiry_items, description: 'Items expiring within 7 days', color: 'red' }
                ];

                cards.forEach(card => {
                    const cardHtml = `
                        <div class="summary-card ${card.color}">
                            <svg class="status-indicator" viewBox="0 0 20 20">
                                <circle cx="10" cy="10" r="8" class="${card.color}"></circle>
                            </svg>
                            <h4>${card.title}</h4>
                            <div class="count">${card.count}</div>
                            <div class="description">${card.description}</div>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', cardHtml);
                });
            } else {
                container.innerHTML = `<p>${data.message}</p>`;
            }
        })
        .catch(error => {
            console.error('Error loading stock summary:', error);
            document.querySelector('#stock-summary .summary-cards-container').innerHTML = '<p>Error loading stock summary.</p>';
        });
    }

    // --- Detailed Inventory Section ---
    let detailedInventoryCurrentPage = 1;
    let detailedInventorySortBy = 'name';
    let detailedInventorySortOrder = 'ASC';
    const detailedInventoryLimit = 10; // Items per page

    function loadDetailedInventoryFilters() {
        fetch('ajax/reports_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'report_type=get_filters_data'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateFilterDropdown('detailedInventoryCategoryFilter', data.categories);
                populateFilterDropdown('detailedInventoryLocationFilter', data.locations);
            } else {
                console.error('Failed to load filter data:', data.message);
            }
        })
        .catch(error => console.error('Error fetching filter data:', error));
    }

    function populateFilterDropdown(selectId, items) {
        const selectElement = document.getElementById(selectId);
        const currentSelectedValue = selectElement.value; // Preserve current selection
        selectElement.innerHTML = `<option value="">All ${selectId.includes('Category') ? 'Categories' : 'Locations'}</option>`;
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            selectElement.appendChild(option);
        });
        selectElement.value = currentSelectedValue; // Restore selection
    }

    function getDetailedInventoryFilters() {
        return {
            category_id: document.getElementById('detailedInventoryCategoryFilter').value,
            location_id: document.getElementById('detailedInventoryLocationFilter').value,
            stock_status: document.getElementById('detailedInventoryStockStatusFilter').value,
            is_perishable: document.getElementById('detailedInventoryPerishableFilter').value,
            expiry_start: document.getElementById('detailedInventoryExpiryStart').value,
            expiry_end: document.getElementById('detailedInventoryExpiryEnd').value,
            search_term: document.getElementById('detailedInventorySearch').value,
            sort_by: detailedInventorySortBy,
            sort_order: detailedInventorySortOrder,
            limit: detailedInventoryLimit,
            offset: (detailedInventoryCurrentPage - 1) * detailedInventoryLimit
        };
    }

    function loadDetailedInventory() {
        const filters = getDetailedInventoryFilters();
        const formData = new URLSearchParams();
        formData.append('report_type', 'detailed_inventory');
        for (const key in filters) {
            formData.append(key, filters[key]);
        }

        fetch('ajax/reports_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('#detailedInventoryTable tbody');
            tableBody.innerHTML = ''; // Clear previous content

            if (data.success) {
                data.data.forEach(item => {
                    let rowClass = '';
                    if (item.current_stock == 0) {
                        rowClass = 'table-row-out-of-stock';
                    } else if (item.current_stock <= item.low_stock) {
                        rowClass = 'table-row-low-stock';
                    } else {
                        rowClass = 'table-row-healthy';
                    }

                    let rowHtml = `
                        <tr class="${rowClass}">
                            <td>${item.name}</td>
                            <td>${item.category_name}</td>
                            <td>${item.location_name}</td>
                            <td>${item.current_stock}</td>
                            <td>${item.unit}</td>
                    `;
                    // Conditionally add low_stock and max_stock for admin/staff
                    if (userRole === 'admin' || userRole === 'staff') {
                        rowHtml += `
                            <td>${item.low_stock}</td>
                            <td>${item.max_stock}</td>
                        `;
                    }
                    rowHtml += `
                            <td>${item.is_perishable == 1 ? 'Yes' : 'No'}</td>
                            <td>${item.expiry_date ? item.expiry_date : 'N/A'}</td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', rowHtml);
                });
                updatePagination('detailedInventoryPagination', data.total_records, detailedInventoryCurrentPage, detailedInventoryLimit, loadDetailedInventory);
                toggleAdminStaffColumns('detailedInventoryTable');
            } else {
                tableBody.innerHTML = `<tr><td colspan="9">${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading detailed inventory:', error);
            document.querySelector('#detailedInventoryTable tbody').innerHTML = '<tr><td colspan="9">Error loading detailed inventory.</td></tr>';
        });
    }

    document.getElementById('applyDetailedInventoryFilters').addEventListener('click', () => {
        detailedInventoryCurrentPage = 1; // Reset to first page on filter apply
        loadDetailedInventory();
    });

    document.getElementById('resetDetailedInventoryFilters').addEventListener('click', () => {
        document.getElementById('detailedInventorySearch').value = '';
        document.getElementById('detailedInventoryCategoryFilter').value = '';
        document.getElementById('detailedInventoryLocationFilter').value = '';
        document.getElementById('detailedInventoryStockStatusFilter').value = '';
        document.getElementById('detailedInventoryPerishableFilter').value = '';
        document.getElementById('detailedInventoryExpiryStart').value = '';
        document.getElementById('detailedInventoryExpiryEnd').value = '';
        detailedInventoryCurrentPage = 1;
        detailedInventorySortBy = 'name';
        detailedInventorySortOrder = 'ASC';
        loadDetailedInventory();
    });

    document.querySelectorAll('#detailedInventoryTable th[data-sort]').forEach(header => {
        header.addEventListener('click', function() {
            const sortBy = this.dataset.sort;
            if (detailedInventorySortBy === sortBy) {
                detailedInventorySortOrder = detailedInventorySortOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                detailedInventorySortBy = sortBy;
                detailedInventorySortOrder = 'ASC';
            }
            // Remove existing sort indicators
            document.querySelectorAll('#detailedInventoryTable th').forEach(th => {
                th.classList.remove('asc', 'desc');
            });
            // Add current sort indicator
            this.classList.add(detailedInventorySortOrder.toLowerCase());
            loadDetailedInventory();
        });
    });

    // --- Transaction Logs Section ---
    let transactionLogsCurrentPage = 1;
    const transactionLogsLimit = 10; // Logs per page

    function loadTransactionLogsFilters() {
        fetch('ajax/reports_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'report_type=get_filters_data'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateFilterDropdown('logCategoryFilter', data.categories);
                populateFilterDropdown('logItemFilter', data.items, true); // Pass true for items dropdown
            } else {
                console.error('Failed to load log filter data:', data.message);
            }
        })
        .catch(error => console.error('Error fetching log filter data:', error));
    }

    function populateFilterDropdown(selectId, items, isItem = false) {
        const selectElement = document.getElementById(selectId);
        const currentSelectedValue = selectElement.value;
        selectElement.innerHTML = `<option value="">All ${isItem ? 'Items' : (selectId.includes('Category') ? 'Categories' : 'Locations')}</option>`;
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            selectElement.appendChild(option);
        });
        selectElement.value = currentSelectedValue;
    }

    function getTransactionLogsFilters() {
        return {
            date_start: document.getElementById('logDateStart').value,
            date_end: document.getElementById('logDateEnd').value,
            action_type: document.getElementById('logActionTypeFilter').value,
            category_id: document.getElementById('logCategoryFilter').value,
            item_id: document.getElementById('logItemFilter').value,
            limit: transactionLogsLimit,
            offset: (transactionLogsCurrentPage - 1) * transactionLogsLimit
        };
    }

    function loadTransactionLogs() {
        if (userRole === 'viewer') {
            document.querySelector('#transaction-logs tbody').innerHTML = '<tr><td colspan="4">Permission denied for transaction logs.</td></tr>';
            return;
        }

        const filters = getTransactionLogsFilters();
        const formData = new URLSearchParams();
        formData.append('report_type', 'transaction_logs');
        for (const key in filters) {
            formData.append(key, filters[key]);
        }

        fetch('ajax/reports_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const tableBody = document.querySelector('#transactionLogsTable tbody');
            tableBody.innerHTML = ''; // Clear previous content

            if (data.success) {
                data.data.forEach(log => {
                    const rowHtml = `
                        <tr>
                            <td>${log.date_time}</td>
                            <td>${log.item_name}</td>
                            <td>${log.action}</td>
                            <td>${log.item_category}</td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', rowHtml);
                });
                updatePagination('transactionLogsPagination', data.total_records, transactionLogsCurrentPage, transactionLogsLimit, loadTransactionLogs);
            } else {
                tableBody.innerHTML = `<tr><td colspan="4">${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading transaction logs:', error);
            document.querySelector('#transactionLogsTable tbody').innerHTML = '<tr><td colspan="4">Error loading transaction logs.</td></tr>';
        });
    }

    document.getElementById('applyTransactionLogsFilters').addEventListener('click', () => {
        transactionLogsCurrentPage = 1;
        loadTransactionLogs();
    });

    document.getElementById('resetTransactionLogsFilters').addEventListener('click', () => {
        document.getElementById('logDateStart').value = '';
        document.getElementById('logDateEnd').value = '';
        document.getElementById('logActionTypeFilter').value = '';
        document.getElementById('logCategoryFilter').value = '';
        document.getElementById('logItemFilter').value = '';
        transactionLogsCurrentPage = 1;
        loadTransactionLogs();
    });

    // --- Expiry Calendar Section ---
    let calendar; // FullCalendar instance
    let calendarEvents = []; // Store events for export

    function loadCalendarFilters() {
        fetch('ajax/reports_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'report_type=get_filters_data'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateFilterDropdown('calendarCategoryFilter', data.categories);
                populateFilterDropdown('calendarLocationFilter', data.locations);
            } else {
                console.error('Failed to load calendar filter data:', data.message);
            }
        })
        .catch(error => console.error('Error fetching calendar filter data:', error));
    }

    function getCalendarFilters() {
        return {
            category_id: document.getElementById('calendarCategoryFilter').value,
            location_id: document.getElementById('calendarLocationFilter').value
        };
    }

    function loadExpiryCalendar() {
        if (userRole === 'viewer') {
            document.getElementById('calendar').innerHTML = '<p>Permission denied for expiry calendar.</p>';
            return;
        }

        const calendarEl = document.getElementById('calendar');
        if (calendar) {
            calendar.destroy(); // Destroy existing calendar instance
        }

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            events: function(fetchInfo, successCallback, failureCallback) {
                const filters = getCalendarFilters();
                const formData = new URLSearchParams();
                formData.append('report_type', 'expiry_calendar_events');
                for (const key in filters) {
                    formData.append(key, filters[key]);
                }

                fetch('ajax/reports_data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        calendarEvents = data.data; // Store for export
                        successCallback(data.data);
                    } else {
                        console.error('Failed to load calendar events:', data.message);
                        failureCallback(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading calendar events:', error);
                    failureCallback(error);
                });
            },
            eventClick: function(info) {
                const event = info.event;
                const props = event.extendedProps;
                document.getElementById('modalItemName').textContent = event.title.split('(')[0].trim();
                document.getElementById('modalCategory').textContent = props.category;
                document.getElementById('modalLocation').textContent = props.location;
                document.getElementById('modalBatchQuantity').textContent = props.batch_quantity + ' ' + event.title.split('(')[1].split(')')[0].split(' ')[1]; // Extract unit
                document.getElementById('modalDaysUntilExpiry').textContent = props.days_until_expiry;
                document.getElementById('expiryDetailsModal').style.display = 'block';
            }
        });
        calendar.render();
    }

    document.getElementById('applyCalendarFilters').addEventListener('click', () => {
        if (calendar) {
            calendar.refetchEvents();
        } else {
            loadExpiryCalendar();
        }
    });

    document.getElementById('resetCalendarFilters').addEventListener('click', () => {
        document.getElementById('calendarCategoryFilter').value = '';
        document.getElementById('calendarLocationFilter').value = '';
        if (calendar) {
            calendar.refetchEvents();
        } else {
            loadExpiryCalendar();
        }
    });

    // Close modal functionality
    document.querySelector('#expiryDetailsModal .close-button').addEventListener('click', () => {
        document.getElementById('expiryDetailsModal').style.display = 'none';
    });
    window.addEventListener('click', (event) => {
        if (event.target == document.getElementById('expiryDetailsModal')) {
            document.getElementById('expiryDetailsModal').style.display = 'none';
        }
    });

    // --- General Pagination Function ---
    function updatePagination(paginationId, totalRecords, currentPage, limit, loadFunction) {
        const paginationContainer = document.getElementById(paginationId);
        paginationContainer.innerHTML = '';

        const totalPages = Math.ceil(totalRecords / limit);

        if (totalPages > 1) {
            const prevButton = document.createElement('button');
            prevButton.textContent = 'Previous';
            prevButton.disabled = currentPage === 1;
            prevButton.addEventListener('click', () => {
                currentPage--;
                loadFunction();
            });
            paginationContainer.appendChild(prevButton);

            const pageInfo = document.createElement('span');
            pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
            paginationContainer.appendChild(pageInfo);

            const nextButton = document.createElement('button');
            nextButton.textContent = 'Next';
            nextButton.disabled = currentPage === totalPages;
            nextButton.addEventListener('click', () => {
                currentPage++;
                loadFunction();
            });
            paginationContainer.appendChild(nextButton);
        }
    }

    // --- Role-based UI Adjustments ---
    function toggleAdminStaffColumns(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const headers = table.querySelectorAll('th.admin-staff-only');
        const rows = table.querySelectorAll('tbody tr');

        if (userRole === 'viewer') {
            headers.forEach(th => th.style.display = 'none');
            rows.forEach(row => {
                // Assuming low_stock and max_stock are 6th and 7th columns (index 5 and 6)
                // This needs to be robust if column order changes.
                // A better way would be to add a class to the td elements as well.
                const cells = row.querySelectorAll('td');
                if (cells[5]) cells[5].style.display = 'none'; // Low Stock Threshold
                if (cells[6]) cells[6].style.display = 'none'; // Max Stock
            });
        } else {
            headers.forEach(th => th.style.display = '');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells[5]) cells[5].style.display = '';
                if (cells[6]) cells[6].style.display = '';
            });
        }
    }

    // --- Export Functionality ---
    document.getElementById('exportDetailedInventory').addEventListener('click', () => {
        const filters = getDetailedInventoryFilters();
        const exportType = prompt("Export Detailed Inventory as (PDF, Excel, CSV)?", "CSV");
        if (exportType) {
            triggerExport('detailed_inventory', filters, exportType.toUpperCase());
        }
    });

    document.getElementById('exportTransactionLogs').addEventListener('click', () => {
        const filters = getTransactionLogsFilters();
        const exportType = prompt("Export Transaction Logs as (PDF, Excel, CSV)?", "CSV");
        if (exportType) {
            triggerExport('transaction_logs', filters, exportType.toUpperCase());
        }
    });

    document.getElementById('exportCalendarList').addEventListener('click', () => {
        const exportType = prompt("Export Expiry Calendar List as (PDF, Excel, CSV)?", "CSV");
        if (exportType) {
            // For calendar, we export the currently loaded events
            triggerCalendarExport(exportType.toUpperCase());
        }
    });

    function triggerExport(reportType, filters, format) {
        const formData = new URLSearchParams();
        formData.append('report_type', reportType);
        formData.append('format', format);
        for (const key in filters) {
            formData.append(key, filters[key]);
        }

        // Open in new tab to download file
        window.open('export.php?' + formData.toString(), '_blank');
    }

    function triggerCalendarExport(format) {
        // Prepare data for export. This might need a dedicated backend endpoint
        // if the export logic is complex or requires server-side processing.
        // For simplicity, we'll pass the current calendarEvents array.
        // A more robust solution would be to refetch events with filters on the backend for export.
        const filters = getCalendarFilters();
        const formData = new URLSearchParams();
        formData.append('report_type', 'expiry_calendar_list'); // A new report type for calendar list export
        formData.append('format', format);
        for (const key in filters) {
            formData.append(key, filters[key]);
        }

        window.open('export.php?' + formData.toString(), '_blank');
    }
});
