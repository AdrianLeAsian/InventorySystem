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

    // Initial fetch when the page loads
    fetchFilteredItems();
});
