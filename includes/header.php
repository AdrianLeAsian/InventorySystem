<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="header">
        <div class="container">
            <h1 class="header__title">
                <?php 
                    $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
                    echo ucfirst($page);
                ?>
            </h1>
        </div>
    </div>
    <main class="container" id="main-content">
