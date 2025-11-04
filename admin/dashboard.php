<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/User.php';
require_once '../includes/Event.php';

require_admin();

$user = get_current_user_data();

$userModel = new User();
$eventModel = new Event();

$pendingUsers = $userModel->getUsers(['status' => 'pending']);
$activeUsers = $userModel->getUsers(['status' => 'active']);
$events = $eventModel->getAllEvents();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Admin Dashboard</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-tools me-2"></i>Admin Panel
            </a>
            <div class="d-flex align-items-center gap-3">
                <a class="btn btn-outline-light btn-sm" href="../dashboard.php">
                    <i class="fas fa-arrow-left me-1"></i>User Dashboard
                </a>
                <a class="btn btn-outline-light btn-sm" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 text-dark">Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
                <p class="text-muted mb-0">Monitor system activity and manage user registrations.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-primary">
                    <div class="card-body">
                        <h5 class="card-title text-primary">
                            <i class="fas fa-user-clock me-2"></i>Pending Users
                        </h5>
                        <p class="display-6 mb-0"><?php echo count($pendingUsers); ?></p>
                        <p class="text-muted">Awaiting approval</p>
                        <a href="users.php" class="btn btn-primary btn-sm">Review Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-success">
                    <div class="card-body">
                        <h5 class="card-title text-success">
                            <i class="fas fa-users me-2"></i>Active Users
                        </h5>
                        <p class="display-6 mb-0"><?php echo count($activeUsers); ?></p>
                        <p class="text-muted">Approved accounts</p>
                        <a href="users.php" class="btn btn-success btn-sm">Manage Users</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-warning">
                    <div class="card-body">
                        <h5 class="card-title text-warning">
                            <i class="fas fa-calendar-check me-2"></i>Total Events
                        </h5>
                        <p class="display-6 mb-0"><?php echo count($events); ?></p>
                        <p class="text-muted">Active events in the system</p>
                        <a href="../events.php" class="btn btn-warning btn-sm text-white">View Events</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>Recent Pending Registrations
                </h2>
            </div>
            <div class="card-body">
                <?php if (empty($pendingUsers)): ?>
                    <p class="text-muted mb-0">No pending registrations right now.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Requested</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($pendingUsers, 0, 5) as $pending): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($pending['name']); ?></td>
                                        <td><?php echo htmlspecialchars($pending['email']); ?></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($pending['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="users.php" class="btn btn-outline-secondary btn-sm">View all pending users</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
