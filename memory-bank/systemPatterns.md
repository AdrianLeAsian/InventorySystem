# System Patterns

**System architecture:**
The system follows a traditional LAMP (Linux, Apache, MySQL, PHP) stack architecture.
- **Frontend:** HTML, CSS, JavaScript (for modals and AJAX calls).
- **Backend:** PHP for server-side logic and database interaction.
- **Database:** MySQL for data storage.

**Key technical decisions:**
- **Prepared Statements:** All database interactions now use prepared statements (`mysqli_prepare()`, `mysqli_bind_param()`, `mysqli_execute()`, `mysqli_get_result()`) to prevent SQL injection vulnerabilities.
- **Password Hashing:** Passwords are securely stored using `password_hash()` and verified with `password_verify()`.
- **Session-based Authentication:** User sessions are managed to control access to authenticated pages (`dashboard.php`, `inventory.php`, `reports.php`, `users.php`) via `includes/auth.php`.
- **AJAX for Modals:** Modals use `fetch` API to send data to PHP action scripts (`item_actions.php`, `category_location_actions.php`, `stock_actions.php`) without full page reloads.
- **Separation of Concerns (Basic):** Logic is somewhat separated into `includes/` directory for database connection, authentication, and specific actions.
- **Centralized Logging:** A dedicated `logs` table is used to record all significant inventory actions, providing an audit trail.
- **CSS Flexbox for Layout:** Used for arranging elements like dashboard summary cards and category/location tables for responsive and flexible layouts.

**Design patterns in use:**
- **Model-View-Controller (Implicit/Partial):** While not a strict MVC framework, `inventory.php` acts as a view/controller, and PHP action files (`item_actions.php`, etc.) handle model-like operations (database interactions).
- **Procedural PHP:** The codebase primarily uses procedural PHP.

**Component relationships:**
- `inventory.php`: Main page displaying inventory, categories, and locations. Contains JavaScript for modal interactions.
- `dashboard.php`: Displays an overview of inventory and recent activities.
- `includes/db.php`: Handles database connection.
- `includes/auth.php`: Manages user authentication and redirects unauthenticated users.
- `includes/sidebar.php`, `includes/header.php`, `includes/modals.php`: UI components.
- `includes/item_actions.php`: Handles CRUD operations for items, including logging.
- `includes/category_location_actions.php`: Handles CRUD for categories and locations.
- `includes/stock_actions.php`: Handles adding and reducing stock, including perishable item batches and logging.
- `items` table: Stores item details, including a new `expiry_date` column.
- `item_batches` table: Stores information about batches of perishable items, including expiry dates and quantities.
- `logs` table: Stores a history of all inventory actions, linked to `items` and `categories`.
