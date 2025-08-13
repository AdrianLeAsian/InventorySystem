# Project Brief

This project is an Inventory Management System. Its primary goal is to provide a web-based application for managing items, categories, locations, and stock levels within an inventory.

**Core Requirements:**
- User authentication (login, signup, logout).
- CRUD operations for items, categories, and locations.
- Stock management (add/remove stock).
- Tracking of perishable items with expiry dates (FIFO).
- Reporting on inventory status (e.g., low stock, full stock).
- Comprehensive logging of all inventory actions.
- Dashboard display of recent inventory activities.
- Improved layout for categories and locations tables on the inventory page.
- Grouping of inventory overview text and summary cards on the dashboard.
- Enhanced security measures, including prepared statements for all database interactions and session-based access control for authenticated pages.
- **NEW: Role System Implementation**: Define 'user' and 'admin' roles, implement role-based access control for pages, and enable user management (adding users, updating roles) for administrators.

**Current Task:**
Implemented a new `logs` table in the database, added `expiry_date` column to the `items` table, updated backend logic in `includes/item_actions.php` and `includes/stock_actions.php` to handle expiry dates and log all item and stock related actions, and created a "Recent Activities" container on `dashboard.php` with corresponding CSS styling. Also, refactored the layout of categories and locations tables on `inventory.php` and grouped the inventory overview text and summary cards on `dashboard.php`. Implemented prepared statements for all database queries and added session-based authentication checks to `dashboard.php`, `inventory.php`, `reports.php`, and `users.php` to prevent unauthorized access. Implemented a role system with user management and conditional sidebar visibility for admins, ensuring GUI consistency across the application. The 'Inventory' link in the sidebar is now conditionally displayed only for 'admin' users.
