<?php
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<div class="sidebar">
    <div class="sidebar-header">
        <img src="assets/images/logo.png" alt="AI Korean Buffet Restaurant Logo" style="width: 90px; height: 90px;"> <!-- Increased size -->
        <h2>AI Korean Buffet Restaurant</h2>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <a href="index.php?page=dashboard">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="<?php echo $current_page === 'inventory' ? 'active' : ''; ?>">
                <a href="index.php?page=inventory">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Inventory</span>
                </a>
            </li>
             <li class="<?php echo $current_page === 'items' ? 'active' : ''; ?>">
                <a href="index.php?page=items">
                    <i class="fas fa-box-open"></i>
                    <span>Items</span>
                </a>
            </li>
            <li class="<?php echo $current_page === 'categories' ? 'active' : ''; ?>">
                <a href="index.php?page=categories">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li class="<?php echo $current_page === 'tracking' ? 'active' : ''; ?>">
                <a href="index.php?page=tracking">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Daily Tracking</span>
                </a>
            </li>
            <li class="<?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                <a href="index.php?page=reports">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
        </ul>
    </nav>
</div>

<style>
/* Specific styles for this sidebar */
.sidebar {
    background-color: #2b3b61; /* A softer blue color */
    color: white;
    padding-top: 30px; /* Adjust padding for the new header */
}

.sidebar-header {
    text-align: center;
    margin-bottom: 20px;
    padding: 0 10px;
    flex-direction: column; /* Stack logo and text */
    gap: 10px;
    border-bottom: none; /* Remove border below header */
}

.sidebar-header img {
    display: block;
    margin: 0 auto 10px auto; /* Center the logo */
}

.sidebar-header h2 {
    font-size: 1.2rem; /* Adjust font size */
    line-height: 1.4; /* Improve readability */
    color: white; /* Ensure text is white */
}

.sidebar-nav ul {
    border-top: none; /* Remove border above navigation */
    padding-top: 20px; /* Keep padding */
    border-bottom: none; /* Ensure no border below navigation */
}

.sidebar-nav li {
    margin-bottom: 0;
}

.sidebar-nav a {
    padding: 12px 20px; /* Adjust padding */
    color: #cfd8dc; /* Lighter text color */
    transition: background-color 0.3s ease, color 0.3s ease;
}

.sidebar-nav a:hover {
    background-color: rgba(255,255,255,0.1);
    color: white; /* White text on hover */
}

.sidebar-nav li.active a {
    background-color: #3f51b5; /* A slightly lighter blue for active */
    color: white; /* White text for active */
}

.sidebar-nav i {
    margin-right: 15px; /* Adjust spacing for icons */
    width: 20px;
    text-align: center;
}

/* Remove collapse styles */
.sidebar-footer,
.sidebar-collapsed .sidebar-header h2,
.sidebar-collapsed .sidebar-nav span,
.sidebar-collapsed .sidebar-footer,
.sidebar-collapsed .sidebar-footer span {
    display: none !important;
}

/* Adjustments for collapsed state (if needed, though collapse is removed) */
.sidebar-collapsed .sidebar {
    width: 60px;
}

.sidebar-collapsed .sidebar-nav i {
    margin-right: 0;
}

.sidebar-collapsed main.container {
    margin-left: 60px;
    width: calc(100% - 60px);
}

/* Smooth transition */
.sidebar,
main.container {
    transition: all 0.3s ease;
}

</style> 