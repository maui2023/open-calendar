<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/User.php';

require_login();

$user = get_current_user_data();
$userModel = new User();
$message = null;
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $record = $userModel->getUserById($user['id']);

    if (!$record) {
        $message = 'User not found.';
        $messageType = 'danger';
    } elseif (!password_verify($current, $record['password_hash'])) {
        $message = 'Current password is incorrect.';
        $messageType = 'danger';
    } elseif (strlen($new) < 8) {
        $message = 'New password must be at least 8 characters.';
        $messageType = 'warning';
    } elseif ($new !== $confirm) {
        $message = 'New password and confirmation do not match.';
        $messageType = 'warning';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $userModel->updateUser($user['id'], ['password_hash' => $hash]);
        $message = 'Your password has been updated successfully.';
        $messageType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Change Password</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        .form-control::placeholder { color: #bbb; }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2 mb-0">
                    <i class="fas fa-user-shield me-2"></i>Change Password
                </h1>
                <div class="d-flex gap-2">
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-gauge me-1"></i>Dashboard
                    </a>
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-calendar me-1"></i>Back to Calendar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Update your password</h5>
                    <p class="text-muted">Use a strong password with a mix of letters, numbers, and symbols.</p>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Enter current password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Minimum 8 characters" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key me-1"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>