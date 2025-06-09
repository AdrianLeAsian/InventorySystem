# Tech Context

## Technologies Used
- **Backend Language:** PHP (version likely 7.x or 8.x, given XAMPP environment)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript
- **Web Server:** Apache (part of XAMPP)
- **Database Management:** phpMyAdmin (typically bundled with XAMPP)

## Development Setup
The project is hosted within a XAMPP environment, which provides Apache, MySQL, and PHP.
- **Root Directory:** `c:/xampp/htdocs/InventorySystem`
- **Database Connection:** Configured in `config/db.php`.
- **Local Development:** Accessible via `http://localhost/InventorySystem/` (or similar, depending on Apache configuration).

## Technical Constraints
- **XAMPP Environment:** The project is designed to run within a XAMPP stack, implying a LAMP/WAMP-like environment.
- **PHP Version Compatibility:** Code should be compatible with common PHP versions found in XAMPP.
- **MySQL Specifics:** Database queries and schema should adhere to MySQL syntax and features.
- **No Frontend Frameworks:** The frontend is built with vanilla HTML, CSS, and JavaScript, without frameworks like React, Vue, or Angular.
- **No Backend Frameworks:** The PHP backend is custom-built, not using frameworks like Laravel, Symfony, or CodeIgniter.

## Dependencies
- **PHP Extensions:** Standard PHP extensions required for MySQL connectivity (e.g., `mysqli` or `pdo_mysql`).
- **MySQL Server:** A running MySQL instance is required for database operations.
- **Apache Web Server:** Necessary to serve the PHP files.
- **Browser:** A modern web browser is needed to access the frontend.
