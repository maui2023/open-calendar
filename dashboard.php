<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/Event.php';

require_login();

$user = get_current_user_data();
$eventModel = new Event();
// keep statuses fresh if user visits dashboard
$eventModel->completePastEvents();
$myEventCount = $eventModel->getUserEventCount($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - User Dashboard</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Top Header (match index/day design) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h2 mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?php echo APP_NAME; ?>
                    </h1>
                    <div class="d-flex gap-2">
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-calendar me-1"></i>Back to Calendar
                        </a>
                        <?php if (is_admin()): ?>
                            <a class="btn btn-warning text-white" href="admin/dashboard.php">
                                <i class="fas fa-tools me-1"></i>Admin Dashboard
                            </a>
                        <?php endif; ?>
                        <a class="btn btn-primary" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                <p class="text-muted mb-0">Here's what you can do today.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-calendar-day me-2 text-primary"></i>View Calendar
                        </h5>
                        <p class="card-text text-muted">Check upcoming events in the monthly calendar view.</p>
                        <a href="index.php" class="btn btn-primary btn-sm">Open Calendar</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-tasks me-2 text-primary"></i>Manage Events
                        </h5>
                        <p class="card-text text-muted">Add, edit, or review the list of your events. You have created <strong><?php echo (int)$myEventCount; ?></strong> events.</p>
                        <a href="events.php" class="btn btn-outline-primary btn-sm">Go to Events</a>
                    </div>
                </div>
            </div>

            <?php if (is_admin()): ?>
            <div class="col-md-4">
                <div class="card shadow-sm h-100 border-warning">
                    <div class="card-body">
                        <h5 class="card-title text-warning">
                            <i class="fas fa-tools me-2"></i>Admin Console
                        </h5>
                        <p class="card-text text-muted">Review pending users and manage the system.</p>
                        <a href="admin/dashboard.php" class="btn btn-warning btn-sm text-white">Open Admin Dashboard</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="row g-4 mt-2">
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-key me-2 text-primary"></i>Change Password
                        </h5>
                        <p class="card-text text-muted">Update your account password for better security.</p>
                        <a href="change_password.php" class="btn btn-primary btn-sm">Change Password</a>
                    </div>
                </div>
            </div>
        </div>
        </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
