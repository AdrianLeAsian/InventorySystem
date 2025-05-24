<?php
require_once '../config/db.php';

// Fetch recent activity from activity_log
$recent_activity = [];
$sql_recent_activity = "SELECT id, activity_type, entity_type, entity_id, entity_name, quantity_change, reason, DATE_FORMAT(log_date, '%Y-%m-%d %H:%i') as log_date 
                        FROM activity_log
                        ORDER BY log_date DESC LIMIT 5";

if($result_activity = mysqli_query($link, $sql_recent_activity)){
    while($row_activity = mysqli_fetch_assoc($result_activity)){
        $recent_activity[] = $row_activity;
    }
    mysqli_free_result($result_activity);
}

// Output the HTML for recent activities
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
                <?php foreach($recent_activity as $activity): ?>
                <tr class="table__row">
                    <td class="table__cell">
                        <span class="btn btn--primary">
                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $activity['activity_type']))); ?>
                        </span>
                    </td>
                    <td class="table__cell">
                        <?php echo htmlspecialchars($activity['entity_name']); ?> (<?php echo htmlspecialchars(ucfirst($activity['entity_type'])); ?>)
                    </td>
                    <td class="table__cell">
                        <?php 
                            $details = [];
                            if (!empty($activity['quantity_change'])) {
                                $details[] = "Qty: " . htmlspecialchars($activity['quantity_change']);
                            }
                            if (!empty($activity['reason'])) {
                                $details[] = "Reason: " . htmlspecialchars($activity['reason']);
                            }
                            echo empty($details) ? 'N/A' : implode(', ', $details);
                        ?>
                    </td>
                    <td class="table__cell"><?php echo htmlspecialchars($activity['log_date']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-center text-muted">No recent activities logged yet.</p>
<?php endif; ?>
