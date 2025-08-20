### Current Work Focus
- Implemented edit, delete, and stock out actions for perishable item batches.
- Updated the "Perishable Items (FIFO)" table title to "Perishable/Batched Items" and added action buttons.
- Fixed an issue where non-perishable items were appearing in the "Perishable/Batched Items" list.
- Modified the system to include non-perishable items in the "Perishable/Batched Items" table, treating them as batches without expiry dates.
- Changed the "Update Stock" button to "Stock In" on the item list.
- Removed the "Remove Stock" button from the "Update Stock" modal.

### Recent Changes
- Modified `inventory.php` to include "Edit", "Delete", and "Stock Out" buttons for each row in the perishable items table.
- Added JavaScript functions `showEditBatchModal`, `showDeleteBatchModal`, and `showStockOutBatchModal` to `inventory.php` to handle the display of modals for these new actions.
- Implemented new actions (`edit_batch`, `delete_batch`, `stock_out_batch`) in `includes/stock_actions.php` to manage perishable item batches:
    - `edit_batch`: Updates the expiry date and quantity of a specific batch, and adjusts the item's total stock accordingly.
    - `delete_batch`: Deletes a specific batch and reduces the item's total stock by the batch quantity.
    - `stock_out_batch`: Reduces the quantity of a specific batch and the item's total stock. If the batch quantity becomes zero or less, the batch is deleted.
- All batch actions are logged in the `logs` table.
- Updated the SQL query in `inventory.php` for the "Perishable/Batched Items" table to explicitly filter for `item_batches.quantity > 0` and include `items.is_perishable` to correctly display status for both perishable and non-perishable items. The ordering now prioritizes perishable items by expiry date, then non-perishable.
- Modified `db_inventory.sql` to allow `expiry_date` to be `NULL` in the `item_batches` table.
- Modified `includes/item_actions.php` to always add new items (both perishable and non-perishable) to the `item_batches` table. For non-perishable items, `expiry_date` will be `NULL`.
- Modified `includes/stock_actions.php` to handle stock updates for non-perishable items by adding them to `item_batches` with a `NULL` expiry date, and to implement FIFO logic for reducing perishable stock from batches.
- Changed the text of the "Update Stock" button to "Stock In" in `inventory.php`.
- Removed the "Remove Stock" button from the `showUpdateStockModal` function in `inventory.php`.

### Next Steps
- Verify the functionality of edit, delete, and stock out actions for all batched items (perishable and non-perishable).
- Ensure stock totals are correctly updated after batch modifications.
- Confirm that all batched items are displayed correctly in the "Perishable/Batched Items" table with appropriate status and expiry date (or N/A).

### Active Decisions and Considerations
- The `item_batches` table is now the central point for all item stock, whether perishable or not.
- The UI for batched items now provides direct management capabilities for individual batches, including non-perishable items.
- The display of batched items is now accurate and comprehensive.
- The "Update Stock" button has been renamed to "Stock In" to reflect its primary function.
- The "Remove Stock" button has been removed to simplify the stock management process, focusing solely on adding stock through this interface.
