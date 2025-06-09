# System Patterns

This document outlines the system architecture, key technical decisions, design patterns, and component relationships for the InventorySystem project.

## System Architecture:
- **Client-Server Model:** A web-based application where the client (browser) interacts with a server (Apache/PHP) to manage inventory data.
- **Database-driven:** MySQL database serves as the primary data store for all inventory-related information.

## Key Technical Decisions:
- **PHP for Backend Logic:** Chosen for its widespread support in web hosting environments and ease of development for dynamic web content.
- **MySQL for Database:** A robust and widely used relational database management system suitable for structured inventory data.
- **Procedural/Hybrid PHP:** The existing codebase appears to use a mix of procedural PHP with some modularization (e.g., `includes`, `ajax` folders).

## Design Patterns in Use:
- **Modular Design:** Code is organized into logical directories (e.g., `pages`, `ajax`, `includes`, `css`, `js`) to separate concerns.
- **Database Abstraction (Basic):** Direct SQL queries are likely used, possibly with some helper functions for common database operations (to be confirmed by code review).

## Component Relationships:
- **`index.php`:** Likely the entry point, possibly redirecting to `pages/dashboard.php` or `pages/inventory.php`.
- **`config/db.php`:** Handles database connection.
- **`includes/`:** Contains reusable UI components (headers, sidebars, modals) and potentially common functions.
- **`pages/`:** Contains the main views/pages of the application (dashboard, inventory, reports).
- **`ajax/`:** Handles asynchronous requests for data manipulation (add, update, delete, log stock).
- **`js/`:** Contains client-side JavaScript for interactivity.
- **`css/`:** Contains styling for the application.
- **`db.sql`:** Database schema and initial data.
