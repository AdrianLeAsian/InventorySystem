document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    const exportPdfButtons = document.querySelectorAll('.export-pdf');
    const exportCsvButtons = document.querySelectorAll('.export-csv');

    // Function to show a specific tab and hide others
    function showTab(tabId) {
        tabContents.forEach(content => {
            content.classList.remove('active');
        });
        tabButtons.forEach(button => {
            button.classList.remove('active');
        });

        document.getElementById(tabId).classList.add('active');
        document.querySelector(`.tab-button[data-tab="${tabId}"]`).classList.add('active');
        loadReportData(tabId);
    }

    // Event listeners for tab buttons
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            showTab(tabId);
        });
    });

    // Function to load report data via AJAX
    function loadReportData(reportType) {
        const reportDataContainer = document.getElementById(`${reportType}-data`);
        reportDataContainer.innerHTML = `<p>Loading ${reportType.replace('-', ' ')}...</p>`; // Loading indicator

        fetch(`ajax/reports_data.php?report_type=${reportType}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    renderReport(reportType, data.data, data.user_role);
                } else {
                    reportDataContainer.innerHTML = `<p class="error-message">${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('Error fetching report data:', error);
                reportDataContainer.innerHTML = `<p class="error-message">Failed to load ${reportType.replace('-', ' ')} data.</p>`;
            });
    }

    // Function to render report data into the DOM
    function renderReport(reportType, data, userRole) {
        const container = document.getElementById(`${reportType}-data`);
        let html = '';

        switch (reportType) {
            case 'stock-summary':
                html = `
                    <div class="dashboard-summary">
                        <div class="summary-card">
                            <div class="card-title">Total Items</div>
                            <div class="card-content">
                                <span class="counter">${data.total_items}</span>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="card-title">Total Stock Quantity</div>
                            <div class="card-content">
                                <span class="counter">${data.total_stock_quantity}</span>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="card-title">Low Stock Items</div>
                            <div class="card-content">
                                <span class="counter status-orange">${data.low_stock_items}</span>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="card-title">Out of Stock Items</div>
                            <div class="card-content">
                                <span class="counter status-red">${data.out_of_stock_items}</span>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="card-title">Perishable Items</div>
                            <div class="card-content">
                                <span class="counter">${data.perishable_items}</span>
                            </div>
                        </div>
                    </div>
                `;
                break;
            case 'detailed-inventory':
                if (data.length === 0) {
                    html = '<p>No detailed inventory data available.</p>';
                } else {
                    html = `
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Stock</th>
                                    <th>Unit</th>
                                    <th>Low Stock Threshold</th>
                                    <th>Max Stock</th>
                                    <th>Perishable</th>
                                    <th>Expiry Date</th>
                                    ${userRole === 'admin' ? '<th>Actions</th>' : ''}
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    data.forEach(item => {
                        html += `
                            <tr>
                                <td>${item.name}</td>
                                <td>${item.category_name}</td>
                                <td>${item.location_name}</td>
                                <td>${item.current_stock}</td>
                                <td>${item.unit || ''}</td>
                                <td>${item.low_stock || 'N/A'}</td>
                                <td>${item.max_stock || 'N/A'}</td>
                                <td>${item.is_perishable == 1 ? 'Yes' : 'No'}</td>
                                <td>${item.expiry_date || 'N/A'}</td>
                                ${userRole === 'admin' ? `
                                    <td>
                                        <button class="btn-warning" onclick="alert('Edit item ${item.id}')">Edit</button>
                                        <button class="btn-danger" onclick="alert('Delete item ${item.id}')">Delete</button>
                                    </td>
                                ` : ''}
                            </tr>
                        `;
                    });
                    html += `
                            </tbody>
                        </table>
                    `;
                }
                break;
            case 'transaction-logs':
                if (data.length === 0) {
                    html = '<p>No transaction logs available.</p>';
                } else {
                    html = `
                        <table>
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Transaction Date</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    data.forEach(log => {
                        html += `
                            <tr>
                                <td>${log.item_name}</td>
                                <td>${log.transaction_type}</td>
                                <td>${log.quantity}</td>
                                <td>${log.transaction_date}</td>
                                <td>${log.user_name}</td>
                            </tr>
                        `;
                    });
                    html += `
                            </tbody>
                        </table>
                    `;
                }
                break;
            case 'expiry-calendar':
                if (data.length === 0) {
                    html = '<p>No perishable items with expiry dates found.</p>';
                } else {
                    html = `
                        <table>
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Expiry Date</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    const now = new Date();
                    data.forEach(item => {
                        const expiryDate = new Date(item.expiry_date);
                        const diffTime = expiryDate.getTime() - now.getTime();
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                        let status = '';
                        let statusClass = '';

                        if (diffDays < 0) {
                            status = 'Expired';
                            statusClass = 'status-red';
                        } else if (diffDays <= 30) { // Example: Near expiry if within 30 days
                            status = 'Near Expiry';
                            statusClass = 'status-orange';
                        } else {
                            status = 'OK';
                            statusClass = 'status-green';
                        }

                        html += `
                            <tr>
                                <td>${item.item_name}</td>
                                <td>${item.expiry_date}</td>
                                <td>${item.quantity}</td>
                                <td><span class="${statusClass}">${status}</span></td>
                            </tr>
                        `;
                    });
                    html += `
                            </tbody>
                        </table>
                    `;
                }
                break;
            default:
                html = '<p>Select a report type to view data.</p>';
                break;
        }
        container.innerHTML = html;
    }

    // Event listeners for export buttons
    exportPdfButtons.forEach(button => {
        button.addEventListener('click', function() {
            const reportType = this.dataset.report;
            window.open(`export.php?report_type=${reportType}&format=pdf`, '_blank');
        });
    });

    exportCsvButtons.forEach(button => {
        button.addEventListener('click', function() {
            const reportType = this.dataset.report;
            window.open(`export.php?report_type=${reportType}&format=csv`, '_blank');
        });
    });

    // Initial load: show the first visible tab
    const initialTab = document.querySelector('.tab-button.active');
    if (initialTab) {
        showTab(initialTab.dataset.tab);
    } else {
        // If no tab is active (e.g., user role doesn't allow any tabs), display a message
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.innerHTML = '<h2>Reports</h2><p>You do not have permission to view any reports.</p>';
        }
    }
});
