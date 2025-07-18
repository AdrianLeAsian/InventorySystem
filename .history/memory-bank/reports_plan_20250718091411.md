# Reports System Enhancement Plan

## 1. Backend (PHP) Enhancements:

*   **Filtering:**
    *   Modify `pages/reports.php` to accept new filtering parameters: `category_id`, `start_date`, `end_date`, `min_stock`, `max_stock`, `item_status` (e.g., active, low stock, out of stock).
    *   Update existing SQL queries in `pages/reports.php` and create new AJAX scripts (e.g., `ajax/get_filtered_items.php`, `ajax/get_filtered_logs.php`) to incorporate these filters for various report sections.
*   **Search:**
    *   Implement server-side search logic in the new AJAX scripts to filter items by name or barcode.
*   **Pagination:**
    *   Add pagination parameters (`page`, `limit`) to the new AJAX scripts.
    *   Modify SQL queries to include `LIMIT` and `OFFSET` clauses and calculate total records for pagination display.
*   **Export Options (PDF/Excel):**
    *   Integrate PHP libraries for PDF generation (`Dompdf`) and Excel generation (`PhpSpreadsheet`).
    *   Modify `export.php` to handle new export types (e.g., `filtered_items_pdf`, `filtered_items_excel`, `filtered_logs_pdf`, `filtered_logs_excel`) based on the applied filters, search, and pagination.

## 2. Frontend (HTML/CSS/JS) Enhancements:

*   **Redesign `pages/reports.php`:**
    *   **Filtering Forms:** Add dropdowns for categories, date pickers for date ranges, input fields/sliders for stock level ranges, and a dropdown for item status.
    *   **Search Bar:** Add an input field for item search.
    *   **Pagination Controls:** Add "Previous", "Next", and page number buttons, and display current page/total pages.
    *   **Export Buttons:** Add "Export to PDF" and "Export to Excel" buttons that dynamically construct URLs for `export.php` based on current filters, search, and pagination.
*   **Improve Visual Presentation (`css/main.css`):** Add styles for the new filtering forms, search bar, and pagination controls to ensure a consistent and user-friendly layout.
*   **JavaScript (`js/reports.js` - new file):** Create a new JavaScript file to handle frontend logic, including AJAX calls for filtering, searching, and pagination, dynamic table updates, date picker initialization, and export URL construction.

## Detailed Steps:

1.  Create `memory-bank/reports_plan.md` to document this plan. (Completed)
2.  Install PHP libraries: `composer require dompdf/dompdf` and `composer require phpoffice/phpspreadsheet`. (I will first check if `composer.json` exists, if not, I will run `composer init`).
3.  Create `ajax/get_filtered_items.php` to handle fetching items with filters, search, and pagination.
4.  Modify `pages/reports.php` to add new filter forms, search bar, pagination controls, and export buttons.
5.  Modify `export.php` to implement PDF and Excel export functionalities using the installed libraries, ensuring it can receive and apply filtering/search parameters.
6.  Create `js/reports.js` to manage all frontend interactions for the reports page.
7.  Update `css/main.css` with necessary styling for the new UI elements.
