<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/Database.php';
require_once '../includes/User.php';
require_once '../includes/Country.php';

require_admin();

$currentUser = get_current_user_data();
$currentAdminId = $currentUser['id'];

$database = new Database();
$countryModel = new Country($database);
$countries = $countryModel->getAllCountries();
$countryLookup = [];
foreach ($countries as $country) {
    $countryLookup[(int) $country['id']] = $country;
}

$userModel = new User();
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'approve_user') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            if (!$userId) {
                throw new Exception('Invalid user id.');
            }

            $user = $userModel->getUserById($userId);
            if (!$user) {
                throw new Exception('User not found.');
            }

            if ($user['status'] !== 'pending') {
                throw new Exception('Only pending users can be approved.');
            }

            $userModel->approveUser($userId, $currentAdminId);
            $flash = ['type' => 'success', 'message' => 'User approved successfully.'];
        } elseif ($action === 'disable_user') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            if (!$userId) {
                throw new Exception('Invalid user id.');
            }

            $user = $userModel->getUserById($userId);
            if (!$user) {
                throw new Exception('User not found.');
            }

            $userModel->disableUser($userId, $currentAdminId);
            $flash = ['type' => 'warning', 'message' => 'User disabled.'];
        } elseif ($action === 'create_user') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            $status = $_POST['status'] ?? 'pending';
            $countryId = !empty($_POST['country_id']) ? (int) $_POST['country_id'] : null;

            if ($name === '' || $email === '' || $password === '') {
                throw new Exception('Name, email, and password are required.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address.');
            }

            if ($userModel->emailExists($email)) {
                throw new Exception('Email address is already registered.');
            }

            if ($countryId !== null && !isset($countryLookup[$countryId])) {
                throw new Exception('Selected country is invalid.');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $userData = [
                'name' => $name,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $role,
                'status' => $status,
                'country_id' => $countryId,
                'approved_by' => $status === 'active' ? $currentAdminId : null,
                'approved_at' => $status === 'active' ? date('Y-m-d H:i:s') : null,
            ];

            $userModel->createUser($userData);
            $flash = ['type' => 'success', 'message' => 'User created successfully.'];
        } elseif ($action === 'update_user') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'user';
            $countryId = ($_POST['country_id'] ?? '') !== '' ? (int) $_POST['country_id'] : null;

            if (!$userId) {
                throw new Exception('Invalid user id.');
            }

            $user = $userModel->getUserById($userId);
            if (!$user) {
                throw new Exception('User not found.');
            }

            if ($name === '' || $email === '') {
                throw new Exception('Name and email are required.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address.');
            }

            if (!in_array($role, ['user', 'admin'], true)) {
                throw new Exception('Invalid role selection.');
            }

            if ($countryId !== null && !isset($countryLookup[$countryId])) {
                throw new Exception('Selected country is invalid.');
            }

            if ($userModel->emailExists($email, $userId)) {
                throw new Exception('Email address is already registered to another user.');
            }

            $updateData = [
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'country_id' => $countryId
            ];

            $userModel->updateUser($userId, $updateData);

            $flash = ['type' => 'success', 'message' => 'User updated successfully.'];
        }
    } catch (Exception $e) {
        $flash = ['type' => 'danger', 'message' => $e->getMessage()];
    }
}

$pendingUsers = $userModel->getUsers(['status' => 'pending']);
$activeUsers = $userModel->getUsers(['status' => 'active']);
$disabledUsers = $userModel->getUsers(['status' => 'disabled']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - User Administration</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .admin-header { margin-bottom: 2rem; }
        .table-actions { white-space: nowrap; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center admin-header">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-users-cog me-2"></i>User Administration
                </h1>
                <p class="text-muted mb-0">Approve pending registrations or add new users manually.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="../index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-calendar me-1"></i>Calendar
                </a>
                <a href="../events.php" class="btn btn-outline-secondary">
                    <i class="fas fa-list me-1"></i>Events
                </a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">
                            <i class="fas fa-user-clock me-2"></i>Pending Approvals
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($pendingUsers) === 0): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <p class="mb-0">No pending registrations.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Country</th>
                                            <th>Role</th>
                                            <th>Requested</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingUsers as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <?php if (!empty($user['country_name'])): ?>
                                                        <?php echo htmlspecialchars($user['country_name']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($user['role']); ?></span></td>
                                                <td><?php echo date('M j, Y H:i', strtotime($user['created_at'])); ?></td>
                                                <td class="table-actions">
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="approve_user">
                                                        <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check me-1"></i>Approve
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline ms-1">
                                                        <input type="hidden" name="action" value="disable_user">
                                                        <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-ban me-1"></i>Reject
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">
                            <i class="fas fa-users me-2"></i>Active Users
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($activeUsers) === 0): ?>
                            <p class="text-muted mb-0">No active users yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Country</th>
                                            <th>Role</th>
                                            <th>Approved</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeUsers as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <?php if (!empty($user['country_name'])): ?>
                                                        <?php echo htmlspecialchars($user['country_name']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($user['role']); ?></span></td>
                                                <td>
                                                    <?php if (!empty($user['approved_at'])): ?>
                                                        <?php echo date('M j, Y H:i', strtotime($user['approved_at'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="table-actions">
                                                    <button 
                                                        type="button" 
                                                        class="btn btn-sm btn-outline-primary edit-user-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editUserModal"
                                                        data-user-id="<?php echo (int) $user['id']; ?>"
                                                        data-user-name="<?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>"
                                                        data-user-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?>"
                                                        data-user-role="<?php echo htmlspecialchars($user['role'], ENT_QUOTES); ?>"
                                                        data-user-country="<?php echo $user['country_id'] ? (int) $user['country_id'] : ''; ?>"
                                                    >
                                                        <i class="fas fa-edit me-1"></i>Edit
                                                    </button>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="disable_user">
                                                        <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-user-slash me-1"></i>Disable
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">
                            <i class="fas fa-user-slash me-2"></i>Disabled Users
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($disabledUsers) === 0): ?>
                            <p class="text-muted mb-0">No disabled users.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Country</th>
                                            <th>Role</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($disabledUsers as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <?php if (!empty($user['country_name'])): ?>
                                                        <?php echo htmlspecialchars($user['country_name']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                                <td class="table-actions">
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="approve_user">
                                                        <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-redo me-1"></i>Re-activate
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">
                            <i class="fas fa-user-plus me-2"></i>Add User Manually
                        </h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create_user">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="country_id" class="form-label">Country</label>
                                <select class="form-select" id="country_id" name="country_id">
                                    <option value="">Select a country</option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?php echo (int) $country['id']; ?>">
                                            <?php echo htmlspecialchars($country['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Used as the default country tag when the user creates events.</div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                <div class="form-text">Minimum 8 characters; encourage admins to send reset link after creation.</div>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="user">Standard User</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="pending">Pending Approval</option>
                                    <option value="active">Active Immediately</option>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Create User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user-pen me-2"></i>Edit User
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_country_id" class="form-label">Country</label>
                            <select class="form-select" id="edit_country_id" name="country_id">
                                <option value="">Select a country</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo (int) $country['id']; ?>">
                                        <?php echo htmlspecialchars($country['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select class="form-select" id="edit_role" name="role">
                                <option value="user">Standard User</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var editUserModal = document.getElementById('editUserModal');
            if (!editUserModal) {
                return;
            }

            editUserModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button) {
                    return;
                }

                var userId = button.getAttribute('data-user-id');
                var name = button.getAttribute('data-user-name') || '';
                var email = button.getAttribute('data-user-email') || '';
                var role = button.getAttribute('data-user-role') || 'user';
                var country = button.getAttribute('data-user-country') || '';

                editUserModal.querySelector('#edit_user_id').value = userId;
                editUserModal.querySelector('#edit_name').value = name;
                editUserModal.querySelector('#edit_email').value = email;
                editUserModal.querySelector('#edit_role').value = role;
                editUserModal.querySelector('#edit_country_id').value = country;
            });
        });
    </script>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
