<?php
/**
 * get_recent_activities.php
 *
 * This script fetches the most recent activities from the 'activity_log' table
 * and generates HTML to display them in a table format. It is typically used
 * for displaying a dashboard or a recent activity feed.
 */

// Include the database configuration file
require_once '../config/db.php';

// Initialize an empty array to store recent activity data
$recent_activity = [];

// SQL query to select the latest 5 activities from the activity_log table
// It formats the log_date for better readability.
$sql_recent_activity = "SELECT id, activity_type, entity_type, entity_id, entity_name, quantity_change, reason, DATE_FORMAT(log_date, '%Y-%m-%d %H:%i') as log_date 
                        FROM activity_log
                        ORDER BY log_date DESC LIMIT 5";

// Execute the SQL query
if($result_activity = mysqli_query($conn, $sql_recent_activity)){
    // Loop through the fetched rows and add them to the $recent_activity array
    while($row_activity = mysqli_fetch_assoc($result_activity)){
        $recent_activity[] = $row_activity;
    }
    // Free the result set
    mysqli_free_result($result_activity);
}

// Output the HTML for recent activities based on whether any activities were found
if (!empty($recent_activity)): ?>
    <div class="table">
        <table class="w-100">
            <thead>
                <tr class="table__header">
                    <th class="table__cell">Activity Type</th>
                    <th class="table__cell">Entity</th>
                    <th class="table__cell">Details</th>
                    <th class="table__cell">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recent_activity as $activity): // Loop through each activity to display it ?>
                <tr class="table__row">
                    <td class="table__cell">
                        <span class="btn btn--primary">
                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $activity['activity_type']))); // Display activity type, formatted ?>
                        </span>
                    </td>
                    <td class="table__cell">
                        <?php echo htmlspecialchars($activity['entity_name']); ?> (<?php echo htmlspecialchars(ucfirst($activity['entity_type'])); ?>)
                    </td>
                    <td class="table__cell">
                        <?php 
                            $details = [];
                            // Add quantity change to details if available
                            if (!empty($activity['quantity_change'])) {
                                $details[] = "Qty: " . htmlspecialchars($activity['quantity_change']);
                            }
                            // Add reason to details if available
                            if (!empty($activity['reason'])) {
                                $details[] = "Reason: " . htmlspecialchars($activity['reason']);
                            }
                            // Output details, or 'N/A' if no details
                            echo empty($details) ? 'N/A' : implode(', ', $details);
                        ?>
                    </td>
                    <td class="table__cell"><?php echo htmlspecialchars($activity['log_date']); // Display formatted log date ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: // If no recent activities are found ?>
    <p class="text-center text-muted">No recent activities logged yet.</p>
<?php endif; ?>
