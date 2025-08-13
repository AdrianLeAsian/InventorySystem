# Progress

**What works:**
- User authentication (login, signup, logout).
- Displaying items, categories, and locations.
- Filtering items by search and category.
- CRUD operations for items (add, edit, delete).
- CRUD operations for categories (add, edit, delete).
- CRUD operations for locations (add, edit, delete).
- Updating stock for non-perishable items.
- Updating stock for perishable items (adding/reducing batches).
- Displaying perishable items in FIFO order with expiry status.
- **FIXED:** Initial stock for new perishable items now correctly appears in the "Perishable Items (FIFO)" table.
- **IMPROVED:** Dashboard summary cards have been re-implemented with updated styling, dynamic color indicators (green, orange, red), and housed within a `dashboard-container`.
- **FIXED:** Low stock alert calculation now correctly identifies items needing attention.
- **FIXED:** Near/Expired Items logic now prioritizes red for expired items.
- **FIXED:** Color indicators (circles) now render as perfect circles using SVG.
- **IMPROVED:** The color of the edit buttons has been updated to green.
- **NEW:** Comprehensive logging of all item and stock actions to a new `logs` table.
- **NEW:** "Recent Activities" container on the dashboard displaying the latest inventory changes.
- **IMPROVED:** Categories and Locations tables on `inventory.php` are now displayed side-by-side in a single row.
- **IMPROVED:** The "Inventory Overview" heading and summary cards on `dashboard.php` are now grouped within a single container.
- **SECURITY IMPROVEMENT:** All database interactions now use prepared statements to prevent SQL injection.
- **SECURITY IMPROVEMENT:** Session-based authentication checks have been added to `dashboard.php`, `inventory.php`, `reports.php`, and `users.php` to prevent unauthorized direct access.
- **NEW:** **Role System Implementation**:
    -   'user' and 'admin' roles are defined and stored in the `users` table.
    -   User roles are loaded into the session upon login.
    -   Access to `inventory.php` and `users.php` is restricted to 'admin' role users.
    -   User management features (adding users, updating roles) are available on the `users.php` page for admins.
    -   The 'Users' link in the sidebar is conditionally displayed only for 'admin' users.
    -   The GUI of the User Management page (`users.php`) has been updated for consistency with the system's overall look and feel.

**What's left to build:**
- Reporting functionality (as indicated by `reports.php`).
- More robust error handling and user feedback.
- Potentially more advanced stock management features (e.g., stock history beyond recent activities).
- Addressing the insecure database credentials for production environments.

**Current status:**
The core inventory management features are functional. The dashboard has been significantly improved with new summary card implementation, corrected alert logic, and a new "Recent Activities" log. All item and stock modifications are now tracked. The layout of the inventory and dashboard pages has been optimized for better visual organization. Significant security enhancements have been implemented for database interactions and access control. The role system is fully implemented, including user management and sidebar visibility for admins.

**Known issues:**
- The database connection in `includes/db.php` uses `root` with no password, which is insecure for production environments. This needs to be addressed before deployment.
