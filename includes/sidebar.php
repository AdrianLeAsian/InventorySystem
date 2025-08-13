<?php
// session_start() is already called in auth.php, which is included before sidebar.php in main pages.
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<div class="sidebar">
    <img src="assets/images/logo.png" alt="Logo" class="logo">
    <div style="text-align:center; margin: 12px 0 24px 0; font-size:1.1em; font-weight:700; color:#fff; letter-spacing:1px;">AI Korean Buffet Restaurant</div>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><span class="icon"><span class="material-icons">dashboard</span></span>Dashboard</a>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user'): // Only show Inventory link if not 'user' ?>
    <a href="inventory.php" class="nav-link <?php echo ($current_page == 'inventory.php') ? 'active' : ''; ?>"><span class="icon"><span class="material-icons">inventory_2</span></span>Inventory</a>
    <?php endif; ?>
    <a href="reports.php" class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>"><span class="icon"><span class="material-icons">bar_chart</span></span>Reports</a>
    <?php if ($is_admin): // Only show Users link if admin ?>
    <a href="users.php" class="nav-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>"><span class="icon"><span class="material-icons">people</span></span>Users</a>
    <?php endif; ?>
    <a href="logout.php" class="nav-link" style="margin-top:32px;color:#D33F49;"><span class="icon"><span class="material-icons">logout</span></span>Logout</a>
</div>
