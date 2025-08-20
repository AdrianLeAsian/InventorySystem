### What Works
- Implemented logic to add perishable items to the `item_batches` table upon initial item creation. This ensures that perishable items are tracked with their expiry dates and quantities, facilitating FIFO management.
- The bug where "stock in" for non-perishable items did not close the modal or show the "Stock updated!" prompt has been fixed.
- Implemented edit, delete, and stock out actions for perishable item batches, including corresponding UI updates and backend logic.
- Corrected the display of the "Perishable/Batched Items" table to only show perishable items with active batches.
- Non-perishable items are now correctly added to the `item_batches` table and displayed in the "Perishable/Batched Items" table with "N/A" for expiry date and status.
- The "Update Stock" button has been changed to "Stock In".
- The "Remove Stock" button has been removed from the "Update Stock" modal.

### What's Left to Build
- Verify the functionality of edit, delete, and stock out actions for all batched items (perishable and non-perishable).
- Ensure stock totals are correctly updated after batch modifications.
- Confirm that all batched items are displayed correctly in the "Perishable/Batched Items" table with appropriate status and expiry date (or N/A).

### Current Status
- The core functionality for initially adding perishable items to the FIFO system is complete.
- The stock update functionality for non-perishable items is now working as expected.
- Perishable item batch management (edit, delete, stock out) is implemented.
- The display of perishable items is now accurate.
- Non-perishable items are now integrated into the batched items table.
- The UI for stock management has been updated as requested.

### Known Issues
- None identified at this stage.
