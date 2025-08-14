### Current Work Focus
- Ensuring perishable items are correctly integrated into the FIFO system upon initial addition.

### Recent Changes
- Modified `includes/item_actions.php` to insert new perishable items into the `item_batches` table when they are added. This includes the `item_id`, `expiry_date`, and `quantity` (initial stock).

### Next Steps
- Monitor the functionality to ensure new perishable items are correctly recorded.
- Consider future tasks for integrating `item_batches` into stock management and reporting.

### Active Decisions and Considerations
- The `item_batches` table is now the primary source for FIFO management of perishable goods.
- The initial stock of a perishable item is now recorded as a batch with its expiry date.
