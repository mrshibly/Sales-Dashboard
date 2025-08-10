<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Sales Dashboard</h2>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <?php
            // Get current URL parameter
            $current_page = isset($_GET['url']) ? $_GET['url'] : 'dashboard';

            // Define sidebar links and their corresponding URL parameters
            $sidebar_links = [
                'dashboard' => ['icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard'],
                'customers' => ['icon' => 'fas fa-users', 'text' => 'Customers'],
                'orders' => ['icon' => 'fas fa-shopping-cart', 'text' => 'Orders'],
                'products' => ['icon' => 'fas fa-box', 'text' => 'Products'],
                'targets' => ['icon' => 'fas fa-bullseye', 'text' => 'Targets'],
                'analytics' => ['icon' => 'fas fa-chart-line', 'text' => 'Analytics'],
                'leaderboard' => ['icon' => 'fas fa-trophy', 'text' => 'Leaderboard'],
            ];

            // Include Auth class and get current user role
            require_once 'classes/Database.php';
            require_once 'classes/Auth.php';
            $database = new Database();
            $db = $database->getConnection();
            $auth = new Auth($db);
            $current_user_role = $auth->get_current_user_role();

            foreach ($sidebar_links as $url_param => $link_info) {
                $active_class = ($current_page === $url_param) ? 'active' : '';
                echo '<li><a href="index.php?url=' . $url_param . '" class="' . $active_class . '"><i class="' . $link_info['icon'] . '"></i> ' . $link_info['text'] . '</a></li>';
            }

            // Conditionally display User Management link
            if ($current_user_role === 'HOM') {
                $active_class = ($current_page === 'user_management') ? 'active' : '';
                echo '<li><a href="index.php?url=user_management" class="' . $active_class . '"><i class="fas fa-user-cog"></i> User Management</a></li>';
            }
            ?>
            <li><a href="index.php?url=settings" class="<?php echo ($current_page === 'settings') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
</aside>