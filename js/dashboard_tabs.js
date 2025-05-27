document.addEventListener('DOMContentLoaded', function() {
    // Function to update recent activities
    function updateRecentActivities() {
        const activitiesContainer = document.querySelector('#recent-activity .card__body');
        if (activitiesContainer) {
            fetch('ajax/get_recent_activities.php')
                .then(response => response.text())
                .then(html => {
                    activitiesContainer.innerHTML = html;
                })
                .catch(error => console.error('Error updating recent activities:', error.message));
        }
    }

    // Only set interval and initial update if on dashboard page and elements exist
    if (document.getElementById('recent-activity')) {
        // Update activities every 30 seconds
        setInterval(updateRecentActivities, 30000);

        // Initial update when page loads
        updateRecentActivities();
    }

    // Main tab switching logic for dashboard
    function showDashboardTab(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
        });

        // Deactivate all tab buttons
        document.querySelectorAll('.tabs .tab-button').forEach(button => {
            button.classList.remove('active');
        });

        // Show the selected tab content
        const selectedTabContent = document.getElementById(tabId);
        if (selectedTabContent) {
            selectedTabContent.style.display = 'block';
        }

        // Activate the clicked tab button
        const clickedTabButton = document.querySelector(`.tabs .tab-button[data-tab="${tabId}"]`);
        if (clickedTabButton) {
            clickedTabButton.classList.add('active');
        }
    }

    // Initial setup on page load for dashboard
    // Set the default active tab to "Recent Activity"
    showDashboardTab('recent-activity');

    // Attach event listeners for main tab buttons on dashboard
    document.querySelectorAll('.tabs .tab-button').forEach(button => {
        button.addEventListener('click', function() {
            showDashboardTab(this.dataset.tab);
        });
    });
});
