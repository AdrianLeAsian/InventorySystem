document.addEventListener('DOMContentLoaded', function() {
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
    }

    // Attach event listeners for modal cancel buttons
    document.querySelectorAll('.cancel-modal-btn').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.dataset.modalId;
            // Assuming toggleModal is now in a common utility or inventory.js
            // If toggleModal is truly global, it will still work.
            // If not, this call will fail.
            // For now, I'll leave it as is, as the user's request is to remove dashboard code from inventory page.
            // The dashboard page itself might still use these modals.
            // If this causes issues on the dashboard, I'll need to re-evaluate.
            toggleModal(modalId, false); 
        });
    });

    // When the user clicks anywhere outside of the modal, close it
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.add('is-hidden');
        }
    });
});
