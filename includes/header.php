<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --danger-color: #d9534f;
            --success-color: #5cb85c;
            --warning-color: #f0ad4e;
            --text-color: #333;
            --light-gray: #f8f9fa;
            --border-color: #dee2e6;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--text-color);
            background-color: #f5f6fa;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            padding: 20px 0;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 0 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin-bottom: 5px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-nav a:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .sidebar-nav li.active a {
            background-color: var(--secondary-color);
        }

        .sidebar-nav i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .page-header {
            background: white;
            height: 60px; /* Fixed height for the header */
            padding: 0 20px; /* Adjusted padding */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed; /* Fixed position */
            top: 0; /* Align to the top */
            left: 250px; /* Align next to the sidebar */
            width: calc(100% - 250px); /* Calculate width */
            z-index: 999; /* Below sidebar, above content */
        }

        .page-header h1 {
            margin: 0;
            font-size: 20px; /* Slightly smaller font size */
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        main.container {
            margin-left: 250px; /* Keep margin for sidebar */
            padding: 80px 20px 20px 20px; /* Add padding-top for the fixed header */
            flex: 1;
            width: calc(100% - 250px);
        }

        .content-wrapper {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .card-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h2 {
            margin: 0;
            font-size: 18px;
            color: var(--primary-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        table th {
            background-color: var(--light-gray);
            font-weight: 600;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .text-danger { color: var(--danger-color); }
        .text-success { color: var(--success-color); }
        .text-warning { color: var(--warning-color); }

        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
            }

            .sidebar-header h2,
            .sidebar-nav span,
            .sidebar-footer span {
                display: none;
            }

            .sidebar-nav i,
            .sidebar-footer i {
                margin-right: 0;
            }

            main.container {
                margin-left: 60px;
                width: calc(100% - 60px);
                padding-top: 80px; /* Adjust padding-top for collapsed sidebar */
            }

            .page-header {
                left: 60px; /* Adjust left position for collapsed sidebar */
                width: calc(100% - 60px); /* Adjust width for collapsed sidebar */
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                height: auto; /* Allow height to adjust on mobile */
                padding: 10px 20px; /* Adjust padding on mobile */
            }

             .page-header h1 {
                 font-size: 20px;
             }
        }

        /* Style for sidebar collapsed state applied to body */
        body.sidebar-collapsed .sidebar {
            width: 60px;
        }

        body.sidebar-collapsed .sidebar-header h2,
        body.sidebar-collapsed .sidebar-nav span,
        body.sidebar-collapsed .sidebar-footer span {
            display: none;
        }

        body.sidebar-collapsed .sidebar-nav i,
        body.sidebar-collapsed .sidebar-footer i {
            margin-right: 0;
        }

        body.sidebar-collapsed main.container {
            margin-left: 60px;
            width: calc(100% - 60px);
        }

         body.sidebar-collapsed .page-header {
            left: 60px;
            width: calc(100% - 60px);
        }

    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="page-header">
        <h1>
            <?php 
                $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
                echo ucfirst($page);
            ?>
        </h1>
    </div>
    <main class="container">