# System Patterns

## System Architecture
The Inventory System follows a traditional client-server architecture, typical for a PHP web application.
- **Frontend:** HTML, CSS, JavaScript for user interface and interaction.
- **Backend:** PHP scripts handle business logic, data processing, and interaction with the database.
- **Database:** MySQL stores all inventory-related data.

## Key Technical Decisions
- **PHP for Backend:** Chosen for its widespread support in web hosting environments and ease of development for dynamic web content.
- **MySQL for Database:** A robust and widely used relational database, suitable for structured inventory data.
- **AJAX for Dynamic Updates:** Used to fetch and update data without full page reloads, improving user experience (e.g., `ajax/add_item.php`, `ajax/get_item.php`).
- **Modular Design:** Code is organized into `pages/`, `includes/`, `ajax/`, `css/`, `js/` directories for better maintainability and separation of concerns.

## Design Patterns in Use
- **MVC-like Structure (Implicit):** While not a strict MVC framework, there's a separation where:
    - `pages/` act as views (presenting data).
    - `ajax/` scripts and some `index.php` logic act as controllers (handling requests).
    - `config/db.php` and database interactions represent the model (data layer).
- **Inclusion Pattern:** PHP `include` statements are heavily used to compose pages from smaller components (e.g., `includes/header.php`, `includes/sidebar.php`).

## Component Relationships
- **`index.php`:** Main entry point, likely handles routing to different `pages/`.
- **`config/db.php`:** Centralized database connection configuration, included by all scripts needing database access.
- **`pages/*.php`:** Render specific views (e.g., dashboard, inventory).
- **`ajax/*.php`:** Handle asynchronous requests for CRUD operations and data retrieval.
- **`includes/*.php`:** Reusable UI components (modals, header, sidebar).
- **`js/*.js`:** Client-side logic for interactivity and AJAX calls.
- **`css/*.css`:** Styling for the application.

```mermaid
graph TD
    User --> Frontend(HTML/CSS/JS)
    Frontend --> Backend(PHP Scripts)
    Backend --> Database(MySQL)

    subgraph Frontend Components
        P[pages/]
        I[includes/]
        J[js/]
        C[css/]
    end

    subgraph Backend Components
        A[ajax/]
        CFG[config/db.php]
        DB[db.sql]
    end

    Frontend --> A
    A --> CFG
    CFG --> DB
    Backend --> CFG
