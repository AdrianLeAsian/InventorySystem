# System Patterns

## Architecture

The system follows a traditional web application architecture, likely a Model-View-Controller (MVC) or a similar pattern, given the PHP files present.

- **Frontend:** Primarily handled by PHP files (`dashboard.php`, `inventory.php`, `reports.php`) and static assets in the `assets/` directory (CSS, images).
- **Backend Logic:** PHP scripts within the `includes/` directory handle database interactions, business logic, and data processing (e.g., `category_location_actions.php`, `db.php`, `item_actions.php`, `stock_actions.php`).
- **Database:** A SQL database is used, as indicated by `db_inventory.sql` and the presence of `includes/db.php`.

## Key Technical Decisions

- **Technology Stack:** PHP, MySQL, HTML, CSS, JavaScript (implied by web context).
- **Database Interaction:** A dedicated `db.php` file suggests a centralized approach to database connections and queries.
- **Modular Design:** The use of `includes/` directory indicates a modular approach to code organization, separating concerns like headers, sidebars, and action handlers.

## Design Patterns

- **Include/Require:** PHP's `include` and `require` statements are used for code modularity and reusability.
- **Database Abstraction (Implied):** The `includes/db.php` file likely abstracts database connection and query logic.
- **Action Controllers (Implied):** Files like `item_actions.php` and `stock_actions.php` suggest a pattern where specific actions are handled by dedicated files.
