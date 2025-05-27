document.addEventListener('DOMContentLoaded', function() {
    // Function to update recent activities (existing functionality)
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
});
