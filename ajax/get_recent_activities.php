<?php
require_once '../config/db.php';

// Fetch recent activity
$recent_activity = [];
$sql_recent_activity = "SELECT il.id, i.name as item_name, il.type, il.quantity_change, il.reason, DATE_FORMAT(il.log_date, '%Y-%m-%d %H:%i') as log_date 
                        FROM inventory_log il
                        JOIN items i ON il.item_id = i.id
                        ORDER BY il.log_date DESC LIMIT 5";

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
                    <th class="table__cell">Item</th>
                    <th class="table__cell">Type</th>
                    <th class="table__cell">Quantity</th>
                    <th class="table__cell">Reason</th>
                    <th class="table__cell">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recent_activity as $activity): ?>
                <tr class="table__row">
                    <td class="table__cell"><?php echo htmlspecialchars($activity['item_name']); ?></td>
                    <td class="table__cell">
                        <span class="btn btn--<?php echo $activity['type'] == 'in' ? 'success' : ($activity['type'] == 'out' ? 'danger' : 'primary'); ?>">
                            <?php echo htmlspecialchars(ucfirst($activity['type'])); ?>
                        </span>
                    </td>
                    <td class="table__cell"><?php echo htmlspecialchars($activity['quantity_change']); ?></td>
                    <td class="table__cell"><?php echo htmlspecialchars($activity['reason'] ?? 'N/A'); ?></td>
                    <td class="table__cell"><?php echo htmlspecialchars($activity['log_date']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-center text-muted">No recent stock movements.</p>
<?php endif; ?>
