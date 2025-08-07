# Active Context

This document outlines the current work focus, recent changes, next steps, and active decisions/considerations for the InventorySystem project.

## Current Work Focus:
- Remaking the reports page to focus on usage trends.
- Implementing import and export data functionalities on the reports page.
- Debugging and resolving issues with the "Export All Reports (CSV)" feature.
- Maintaining and updating memory bank documentation.

## Recent Changes:
- Removed Low Stock Threshold, Min Stock, Max Stock, and Last Activity columns from the All Inventory Items tab in `pages/inventory.php` for visual declutter. These fields remain editable in item modals and are still present in backend logic for feature dependencies.
- **Reports Page (`pages/reports.php`)**:
    - Streamlined to focus solely on "Usage Trends Report".
    - Period selection (Daily, Weekly, Monthly, Yearly) is now a dropdown integrated into the card header for a cleaner UI.
    - An "Import and Export Data" section has been added below the trends chart.
    - Includes a "Import Items" button which triggers a modal for CSV import.
    - Includes a "Export All Reports (CSV)" button.
- **Import Functionality (`ajax/import_items.php`)**:
    - Updated to correctly handle CSV file uploads, replacing previous Excel-focused logic.
    - Processes CSV data to add/update items and categories in the database.
- **Export Functionality (`export.php`)**:
    - Modified to include a new `all_reports_csv` type, intended to consolidate all relevant inventory data (logs, items, categories) into a single CSV file.
    - The `output_csv` function was adjusted to correctly map associative array data to CSV columns.
    - Currently contains debugging `var_dump` statements to diagnose the export issue.
- **Mock Data (`mock_data.sql`)**: A new SQL file was created to provide sample data for testing all system features, especially the reports and import/export.

## Active Decisions and Considerations:
- Ensure consistency in marking required fields across all forms in the application.
- The previous task regarding Apache logs for `RentManangementSystem` was a misunderstanding; the current focus is on `InventorySystem` UI improvements.
- **Critical Issue**: The "Export All Reports (CSV)" functionality is currently producing blank files, despite confirmation that data exists in the database. This is the primary blocking issue.
