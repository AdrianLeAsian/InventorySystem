document.addEventListener('DOMContentLoaded', function() {
    function showInventoryTab(tabContentId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
        });

        // Deactivate all tab buttons
        document.querySelectorAll('.tabs .tab-button').forEach(button => {
            button.classList.remove('active');
        });

        // Show the selected tab content
        const selectedTabContent = document.getElementById(tabContentId);
        if (selectedTabContent) {
            selectedTabContent.style.display = 'block';
        }

        // Activate the clicked tab button
        const clickedTabButton = document.querySelector(`.tabs .tab-button[data-tab-content="${tabContentId}"]`);
        if (clickedTabButton) {
            clickedTabButton.classList.add('active');
        }

        // Show/hide specific buttons based on the active tab
        document.getElementById('items-buttons').style.display = 'none';
        document.getElementById('categories-buttons').style.display = 'none';
        document.getElementById('tracking-buttons').style.display = 'none';

        if (tabContentId === 'items-content') {
            document.getElementById('items-buttons').style.display = 'block';
        } else if (tabContentId === 'categories-content') {
            document.getElementById('categories-buttons').style.display = 'block';
        } else if (tabContentId === 'tracking-content') {
            document.getElementById('tracking-buttons').style.display = 'block';
        }
    }

    // Initial setup on page load for inventory
    // Set the default active tab to "Inventory Items"
    showInventoryTab('items-content');

    // Attach event listeners for inventory tab buttons
    document.querySelectorAll('.tabs .tab-button').forEach(button => {
        button.addEventListener('click', function() {
            showInventoryTab(this.dataset.tabContent);
        });
    });
});
