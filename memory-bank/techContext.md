# Tech Context

**Technologies Used:**
- **Backend:** PHP (inferred from file extensions like `.php`)
- **Database:** SQL (indicated by `db_inventory.sql`), likely MySQL given the context of PHP web development.
- **Frontend:** HTML, CSS (indicated by `assets/css/style.css`), and potentially JavaScript (though no `.js` files are immediately visible, it's common in web apps).

**Development Setup:**
- The project is located in `c:/xampp/htdocs/InventorySystem`. This suggests the use of XAMPP, a popular web development environment that includes Apache, MySQL, and PHP.
- The presence of a `.git` directory and a remote URL (`https://github.com/AdrianLeAsian/InventorySystem.git`) indicates version control is used, likely Git.

**Dependencies:**
- No explicit dependency management files (like `composer.json` for PHP or `package.json` for Node.js) are visible in the top-level directory. This might mean dependencies are either minimal, managed manually, or located in subdirectories not yet explored.
- The `includes/db.php` file will likely contain database connection details.

**Key UI Components:**
- **Dashboard Summary Cards:** The dashboard now features dynamic summary cards for "Total Items", "Near/Expired Items", and "Low Stock Alerts". These cards include counters and a colored indicator (green, orange, red) that changes based on predefined thresholds related to item details from the inventory. The styling of these cards has been updated to match the provided design, including rounded corners, shadows, and specific font sizes/colors for titles, counters, and descriptions. The summary cards are now housed within a `dashboard-container` for better structural organization and consistent padding/shadows.

**Database Schema Updates:**
- Added an `input_timestamp` column of type `TIMESTAMP` with a default value of `CURRENT_TIMESTAMP` to the `items` table in `db_inventory.sql` to record when items are inputted.
