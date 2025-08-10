document.addEventListener('DOMContentLoaded', function() {
    const filterItemsForm = document.getElementById('filterItemsForm');
    const filteredItemsTableBody = document.getElementById('filteredItemsTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    const exportFilteredPdfBtn = document.getElementById('exportFilteredPdfBtn');
    const exportFilteredCsvBtn = document.getElementById('exportFilteredCsvBtn');

    let currentPage = 1;
    const itemsPerPage = 10; // You can make this configurable if needed

    // Function to fetch and display filtered items
    function fetchFilteredItems() {
        const formData = new FormData(filterItemsForm);
        const params = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            if (value !== '') { // Only add non-empty values to params
                params.append(key, value);
            }
        }
        params.append('page', currentPage);
        params.append('limit', itemsPerPage);

        fetch(`ajax/get_filtered_items.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderFilteredItems(data.data);
                    updatePaginationControls(data.total_records);
                } else {
                    filteredItemsTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">${data.message || 'Error fetching data.'}</td></tr>`;
                    updatePaginationControls(0);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                filteredItemsTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">An error occurred while fetching data.</td></tr>`;
                updatePaginationControls(0);
            });
    }

    // Function to render items in the table
    function renderFilteredItems(items) {
        filteredItemsTableBody.innerHTML = '';
        if (items.length === 0) {
            filteredItemsTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">No items found matching your criteria.</td></tr>`;
            return;
        }
        items.forEach(item => {
            const row = `
                <tr class="table__row">
                    <td class="table__cell">${item.name}</td>
                    <td class="table__cell">${item.category_name}</td>
                    <td class="table__cell">${item.barcode}</td>
                    <td class="table__cell">${item.quantity}</td>
                    <td class="table__cell">${item.low_stock_threshold}</td>
                    <td class="table__cell">${item.created_at}</td>
                </tr>
            `;
            filteredItemsTableBody.insertAdjacentHTML('beforeend', row);
        });
    }

    // Function to update pagination controls
    function updatePaginationControls(totalRecords) {
        const totalPages = Math.ceil(totalRecords / itemsPerPage);
        paginationInfo.textContent = `Page ${currentPage} of ${totalPages} (${totalRecords} total items)`;

        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;
    }

    // Event Listeners
    filterItemsForm.addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1; // Reset to first page on new filter
        fetchFilteredItems();
    });

    resetFiltersBtn.addEventListener('click', function() {
        filterItemsForm.reset();
        currentPage = 1;
        fetchFilteredItems();
    });

    prevPageBtn.addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            fetchFilteredItems();
        }
    });

    nextPageBtn.addEventListener('click', function() {
        const totalRecords = parseInt(paginationInfo.textContent.match(/\((\d+) total items\)/)[1]);
        const totalPages = Math.ceil(totalRecords / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            fetchFilteredItems();
        }
    });

    exportFilteredPdfBtn.addEventListener('click', function() {
        const formData = new FormData(filterItemsForm);
        const params = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            if (value !== '') {
                params.append(key, value);
            }
        }
        window.open(`export.php?type=filtered_items_pdf&${params.toString()}`, '_blank');
    });

    exportFilteredCsvBtn.addEventListener('click', function() {
        const formData = new FormData(filterItemsForm);
        const params = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            if (value !== '') {
                params.append(key, value);
            }
        }
        window.open(`export.php?type=filtered_items_csv&${params.toString()}`, '_blank');
    });

    // Chart.js instance for Usage Trends
    let usageTrendsChart;
    const usageTrendsCtx = document.getElementById('monthlyUsageChart');
    const usageTrendsForm = document.getElementById('usageTrendsForm');
    const usageViewTypeSelect = document.getElementById('usage_view_type');
    const usageCustomDates = document.querySelectorAll('.usage-custom-dates');
    const usageStartDateInput = document.getElementById('usage_start_date');
    const usageEndDateInput = document.getElementById('usage_end_date');

    // Function to fetch and display usage trends
    function fetchUsageTrends() {
        const viewType = usageViewTypeSelect.value;
        const params = new URLSearchParams();
        params.append('view_type', viewType);

        if (viewType === 'custom') {
            if (usageStartDateInput.value) {
                params.append('start_date', usageStartDateInput.value);
            }
            if (usageEndDateInput.value) {
                params.append('end_date', usageEndDateInput.value);
            }
        }

        fetch(`ajax/get_usage_trends.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderUsageTrendsChart(data.data, data.chart_x_axis_label, viewType);
                } else {
                    // Ensure the canvas is re-created if it was cleared or removed
                    resetUsageTrendsCanvas();
                    usageTrendsCtx.insertAdjacentHTML('afterend', `<p class="text-center text-muted usage-trends-message">${data.message || 'No \'stock out\' data available for the selected period to display usage trends.'}</p>`);
                    console.error('Error fetching usage trends:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Ensure the canvas is re-created if it was cleared or removed
                resetUsageTrendsCanvas();
                usageTrendsCtx.insertAdjacentHTML('afterend', `<p class="text-center text-muted usage-trends-message">An error occurred while fetching usage trends data.</p>`);
            });
    }

    // Function to reset/re-create the usage trends canvas
    function resetUsageTrendsCanvas() {
        if (usageTrendsChart) {
            usageTrendsChart.destroy();
            usageTrendsChart = null; // Nullify the chart instance
        }
        const parentDiv = usageTrendsCtx.parentNode;
        // Remove any existing messages
        const existingMessage = parentDiv.querySelector('.usage-trends-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        // Remove the old canvas
        if (usageTrendsCtx) {
            usageTrendsCtx.remove();
        }
        // Create a new canvas element
        const newCanvas = document.createElement('canvas');
        newCanvas.id = 'monthlyUsageChart';
        newCanvas.width = '400'; // Maintain original dimensions
        newCanvas.height = '200'; // Maintain original dimensions
        parentDiv.appendChild(newCanvas);
        // Update the global reference
        usageTrendsCtx = newCanvas;
    }

    // Initial fetch for filtered items and usage trends when the page loads
    fetchFilteredItems();
    fetchUsageTrends(); // Load default daily trends on page load
});
    }

    // Function to render/update the Usage Trends chart
    function renderUsageTrendsChart(usageData, chartXAxisLabel, viewType) {
        const chartLabels = [];
        const itemDataByPeriod = {};
        const colors = ['rgba(255, 99, 132, 0.8)', 'rgba(54, 162, 235, 0.8)', 'rgba(255, 206, 86, 0.8)', 'rgba(75, 192, 192, 0.8)', 'rgba(153, 102, 255, 0.8)', 'rgba(255, 159, 64, 0.8)'];
        let colorIndex = 0;

        // Collect all unique date labels and item names
        usageData.forEach(record => {
            if (!chartLabels.includes(record.date_label)) {
                chartLabels.push(record.date_label);
            }
            if (!itemDataByPeriod[record.item_name]) {
                itemDataByPeriod[record.item_name] = {};
            }
            itemDataByPeriod[record.item_name][record.date_label] = parseInt(record.total_quantity_out);
        });

        // Sort labels chronologically
        chartLabels.sort();

        // Format labels for display
        const formattedLabels = chartLabels.map(label => {
            if (viewType === 'daily' || viewType === 'custom') {
                // YYYY-MM-DD to MM-DD
                const parts = label.split('-');
                return `${parts[1]}-${parts[2]}`;
            } else if (viewType === 'weekly') {
                // YYYY-WW to Week WW (YYYY)
                const year = label.substring(0, 4);
                const week = label.substring(4);
                return `Week ${week} (${year})`;
            } else if (viewType === 'monthly') {
                // YYYY-MM to MMM YYYY
                const [year, month] = label.split('-');
                const date = new Date(year, month - 1, 1);
                return date.toLocaleString('default', { month: 'short', year: 'numeric' });
            } else if (viewType === 'yearly') {
                // YYYY
                return label;
            }
            return label;
        });

        const chartDatasets = [];
        for (const itemName in itemDataByPeriod) {
            const dataPoints = [];
            chartLabels.forEach(label => {
                dataPoints.push(itemDataByPeriod[itemName][label] || 0);
            });

            chartDatasets.push({
                label: itemName,
                data: dataPoints,
                borderColor: colors[colorIndex % colors.length],
                backgroundColor: colors[colorIndex % colors.length],
                fill: false,
                tension: 0.1
            });
            colorIndex++;
        }

        if (usageTrendsChart) {
            usageTrendsChart.destroy(); // Destroy existing chart before creating a new one
        }

        usageTrendsChart = new Chart(usageTrendsCtx, {
            type: 'line',
            data: {
                labels: formattedLabels,
                datasets: chartDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Quantity Out'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: chartXAxisLabel
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Item Usage (Stock Out)'
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                const label = context[0].label;
                                if (viewType === 'daily' || viewType === 'custom') {
                                    // Assuming label is MM-DD, need to reconstruct YYYY-MM-DD for full date
                                    const year = new Date().getFullYear(); // Use current year for display
                                    return `${label}-${year}`;
                                } else if (viewType === 'weekly') {
                                    return label; // Already formatted as "Week WW (YYYY)"
                                } else if (viewType === 'monthly') {
                                    return label; // Already formatted as "MMM YYYY"
                                } else if (viewType === 'yearly') {
                                    return label; // Already formatted as "YYYY"
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    // Event listener for view type dropdown
    usageViewTypeSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            usageCustomDates.forEach(el => el.style.display = 'block');
        } else {
            usageCustomDates.forEach(el => el.style.display = 'none');
            // Clear custom date inputs when switching away from custom
            usageStartDateInput.value = '';
            usageEndDateInput.value = '';
        }
        fetchUsageTrends(); // Fetch data immediately on view type change
    });

    // Event listener for custom date form submission
    usageTrendsForm.addEventListener('submit', function(e) {
        e.preventDefault();
        fetchUsageTrends();
    });

    // Initial fetch for filtered items and usage trends when the page loads
    fetchFilteredItems();
    fetchUsageTrends(); // Load default daily trends on page load
});
