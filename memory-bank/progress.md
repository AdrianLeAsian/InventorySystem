### What Works
- Implemented logic to add perishable items to the `item_batches` table upon initial item creation. This ensures that perishable items are tracked with their expiry dates and quantities, facilitating FIFO management.

### What's Left to Build
- Further integration of the `item_batches` table for stock management (e.g., when items are stocked out, ensuring FIFO logic is applied).
- Reporting and display of perishable item batches.

### Current Status
- The core functionality for initially adding perishable items to the FIFO system is complete.

### Known Issues
- None identified at this stage related to this specific change.
