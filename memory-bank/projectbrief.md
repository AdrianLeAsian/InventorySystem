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

**Current Task:**
Implemented a new `logs` table in the database, added `expiry_date` column to the `items` table, updated backend logic in `includes/item_actions.php` and `includes/stock_actions.php` to handle expiry dates and log all item and stock related actions, and created a "Recent Activities" container on `dashboard.php` with corresponding CSS styling.
