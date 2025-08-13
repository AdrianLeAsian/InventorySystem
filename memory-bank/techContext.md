# Tech Context

**Technologies used:**
- **PHP:** Server-side scripting language (version not explicitly specified, assumed modern PHP 7+).
- **MySQL:** Relational database management system.
- **HTML5:** Markup language for web content.
- **CSS3:** Stylesheet language for styling web pages.
- **JavaScript (ES5/ES6+):** Client-side scripting for interactive elements and AJAX.
- **Apache HTTP Server:** Web server.

**Development setup:**
- **XAMPP:** Likely used for local development, providing Apache, MySQL, and PHP.
- **VS Code:** Integrated Development Environment.

**Technical constraints:**
- The current implementation relies on direct PHP-MySQL interactions without an ORM or a more structured framework, which might limit scalability or maintainability for very large applications.
- Client-side validation is minimal; server-side validation is crucial.
- Database connection uses `root` with no password in `includes/db.php`, which is insecure for production environments and requires a dedicated, privileged user.

**Dependencies:**
- PHP `mysqli` extension for database interaction.
- Standard web browser for client-side execution.

**Key UI Components:**
- **Dashboard Summary Cards:** The dashboard now features dynamic summary cards for "Total Items", "Near/Expired Items", and "Low Stock Alerts". These cards include counters and a colored indicator (green, orange, red) that changes based on predefined thresholds related to item details from the inventory. The styling of these cards has been updated to match the provided design, including rounded corners, shadows, and specific font sizes/colors for titles, counters, and descriptions. The color indicators are now implemented using SVG circles for consistent rendering. The summary cards are now housed within a `dashboard-container` for better structural organization and consistent padding/shadows. The entire "Inventory Overview" section (heading and summary cards) is now wrapped in a `dashboard-section` container for better visual grouping.
- **Recent Activities Container:** A new section on the dashboard displaying the 10 most recent inventory actions. It fetches data from the `logs` table, joining with `items` and `categories` to show item name, category, action performed, date/time, and expiry status. Styling is applied to ensure a clean, readable table format consistent with the overall dashboard design.
- **Categories and Locations Tables:** On the `inventory.php` page, the Categories and Locations tables are now displayed side-by-side using a flexbox layout, improving space utilization and visual organization.
- **User Management Page (`users.php`):**
    -   The page now includes a consistent GUI matching the system's overall look and feel.
    -   Features a "User Management" section with an "Add New User" form and a table displaying existing users.
    -   The "Add New User" form uses consistent input fields, select dropdowns, and primary buttons, styled to align with other forms in the application.
    -   The user table displays usernames, roles, and action buttons (Save Role), with styling consistent with other tables in the system (e.g., borders, header background, cell padding).
    -   Role selection is done via a dropdown, and changes are saved via a "Save Role" button styled as a primary button.
