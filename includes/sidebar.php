<?php
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<div class="sidebar">
    <div class="sidebar__header">
        <img src="assets/images/logo.png" alt="AI Korean Buffet Restaurant Logo" class="sidebar__logo" width="150" height="150">
        <h2 class="sidebar__title">AI Korean Buffet Restaurant</h2>
    </div>
    <nav class="sidebar__nav">
        <ul class="sidebar__list">
            <li class="sidebar__item <?php echo $current_page === 'dashboard' ? 'sidebar__item--active' : ''; ?>">
                <a href="index.php?page=dashboard" class="sidebar__link sidebar__button">
                    <i class="fas fa-tachometer-alt"></i> <!-- Changed icon for Dashboard -->
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar__item <?php echo ($current_page === 'inventory' || $current_page === 'tracking') ? 'sidebar__item--active' : ''; ?>">
                <a href="index.php?page=inventory" class="sidebar__link sidebar__button">
                    <i class="fas fa-boxes"></i> <!-- Changed icon for Inventory -->
                    <span>Inventory</span>
                </a>
            </li>
            <li class="sidebar__item <?php echo $current_page === 'reports' ? 'sidebar__item--active' : ''; ?>">
                <a href="index.php?page=reports" class="sidebar__link sidebar__button">
                    <i class="fas fa-chart-line"></i> <!-- Changed icon for Reports -->
                    <span>Reports</span>
                </a>
            </li>
        </ul>
    </nav>
</div>
