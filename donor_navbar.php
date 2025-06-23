<?php
// Get current page name for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
    <div class="container">
        <a class="navbar-brand" href="donordashboard.php">
            <i class="fas fa-heartbeat me-2"></i>E Care Hub
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'donordashboard.php' ? 'active' : '' ?>" href="donordashboard.php">
                        <i class="fas fa-home me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'donation_drives.php' ? 'active' : '' ?>" href="donation_drives.php">
                        <i class="fas fa-calendar-alt me-1"></i> Donation Drives
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'donation_history.php' ? 'active' : '' ?>" href="donation_history.php">
                        <i class="fas fa-history me-1"></i> Donation History
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'resources.php' ? 'active' : '' ?>" href="resources.php">
                        <i class="fas fa-book-medical me-1"></i> Resources
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i> 
                        <?php 
                        if (isset($_SESSION['donor_id'])) {
                            $stmt = $pdo->prepare("SELECT full_name FROM donors WHERE id = ?");
                            $stmt->execute([$_SESSION['donor_id']]);
                            $name = $stmt->fetchColumn();
                            echo htmlspecialchars($name);
                        } else {
                            echo "Account";
                        }
                        ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item <?= $current_page == 'edit_profile.php' ? 'active' : '' ?>" href="edit_profile.php">
                                <i class="fas fa-user-edit me-2"></i>Edit Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?= $current_page == 'change_password.php' ? 'active' : '' ?>" href="change_password.php">
                                <i class="fas fa-key me-2"></i>Change Password
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>