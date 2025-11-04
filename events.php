<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/Event.php';

$eventModel = new Event();
// Auto-mark past events as completed
$eventModel->completePastEvents();
$currentUser = get_current_user_data();

// Handle search
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$events = $searchQuery ? $eventModel->searchEvents($searchQuery) : $eventModel->getAllEventsWithCountry();

// Get statistics
$stats = $eventModel->getEventStats();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Event Management</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/calendar.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body id="pageBody" class="bg-light text-dark" data-theme="light">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 mb-0">
                            <i class="fas fa-list me-2"></i>
                            Event Management
                        </h1>

                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                            <i class="fas fa-plus me-1"></i>Add Event
                        </button>
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-calendar me-1"></i>Back to Calendar
                        </a>
                        <?php if (is_logged_in()): ?>
                            <a class="btn btn-outline-secondary" href="<?php echo is_admin() ? 'admin/dashboard.php' : 'dashboard.php'; ?>">
                                <i class="fas fa-gauge-high me-1"></i>Dashboard
                            </a>
                            <a class="btn btn-secondary" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a>
                        <?php else: ?>
                            <a class="btn btn-secondary" href="login.php">
                                <i class="fas fa-user me-1"></i>Login
                            </a>
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-primary"><?php echo $stats['total']; ?></h5>
                        <p class="card-text">Total Events</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-info"><?php echo $stats['this_month']; ?></h5>
                        <p class="card-text">This Month</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-danger"><?php echo $stats['priority']['high'] ?? 0; ?></h5>
                        <p class="card-text">High Priority</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-warning"><?php echo $stats['priority']['medium'] ?? 0; ?></h5>
                        <p class="card-text">Medium Priority</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="Search events..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </div>
                            <div class="col-md-2">
                                <a href="events.php" class="btn btn-outline-secondary w-100">Clear</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events List -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <?php if ($searchQuery): ?>
                                Search Results for "<?php echo htmlspecialchars($searchQuery); ?>" (<?php echo count($events); ?> found)
                            <?php else: ?>
                                All Events (<?php echo count($events); ?> total)
                            <?php endif; ?>
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($events)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No events found</h5>
                                <p class="text-muted">
                                    <?php if ($searchQuery): ?>
                                        Try adjusting your search criteria.
                                    <?php else: ?>
                                        Start by creating your first event.
                                    <?php endif; ?>
                                </p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                                    <i class="fas fa-plus me-1"></i>Add Event
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Title</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Category</th>
                                            <th>Country</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($events as $event): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-2" style="width: 4px; height: 30px; background-color: <?php echo htmlspecialchars($event['color']); ?>; border-radius: 2px;"></div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                                            <?php if ($event['description']): ?>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($event['description'], 0, 50)); ?><?php echo strlen($event['description']) > 50 ? '...' : ''; ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($event['start_date'])); ?>
                                                    <?php if ($event['end_date'] && $event['end_date'] !== $event['start_date']): ?>
                                                        <br><small class="text-muted">to <?php echo date('M j, Y', strtotime($event['end_date'])); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($event['all_day']): ?>
                                                        <span class="badge bg-info">All Day</span>
                                                    <?php elseif ($event['start_time']): ?>
                                                        <?php echo date('g:i A', strtotime($event['start_time'])); ?>
                                                        <?php if ($event['end_time']): ?>
                                                            <br><small class="text-muted">to <?php echo date('g:i A', strtotime($event['end_time'])); ?></small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($event['category'])); ?></span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($event['country_name'])): ?>
                                                        <span class="badge" style="background-color: <?php echo htmlspecialchars($event['country_color'] ?? '#6c757d'); ?>; color: white;">
                                                            <?php echo htmlspecialchars($event['country_name']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $event['priority'] === 'high' ? 'danger' : ($event['priority'] === 'medium' ? 'warning' : 'success'); ?>">
                                                        <?php echo htmlspecialchars(ucfirst($event['priority'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $event['status'] === 'active' ? 'success' : ($event['status'] === 'cancelled' ? 'danger' : 'info'); ?>">
                                                        <?php echo htmlspecialchars(ucfirst($event['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-success" onclick="shareEventFromList(<?php echo (int)$event['id']; ?>)" title="Share">
                                                            <i class="fas fa-share-alt"></i>
                                                        </button>
                                                        <?php
                                                            $canEdit = $currentUser && (
                                                                $currentUser['role'] === 'admin' ||
                                                                (isset($event['created_by']) && (int)$event['created_by'] === (int)$currentUser['id'])
                                                            );
                                                        ?>
                                                        <?php if ($canEdit): ?>
                                                            <button class="btn btn-outline-warning" onclick="editEventFromList(<?php echo (int) $event['id']; ?>)" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
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
        </div>
    </div>

    <!-- Include modals from index.php -->
    <?php include 'includes/modals.php'; ?>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        window.currentUserId = <?php echo $currentUser ? (int) $currentUser['id'] : 'null'; ?>;
        window.currentUserRole = <?php echo $currentUser ? json_encode($currentUser['role']) : 'null'; ?>;
    </script>
    <script src="assets/js/calendar.js"></script>
    <script>
        function editEventFromList(eventId) {
            if (window.calendarApp) {
                window.calendarApp.editEventById(eventId);
            }
        }

        async function shareEventFromList(eventId) {
            try {
                const res = await fetch(`api/events.php?id=${encodeURIComponent(eventId)}`);
                if (!res.ok) throw new Error('Failed to load event');
                const event = await res.json();

                const date = event && event.start_date ? event.start_date : null;
                const title = event && event.title ? event.title : 'Calendar Event';
                const shareUrl = new URL(date ? `day.php?date=${encodeURIComponent(date)}` : 'index.php', window.location.href).toString();

                const shareData = {
                    title: `Share: ${title}`,
                    text: date ? `${title} on ${date}` : title,
                    url: shareUrl
                };

                if (navigator.share) {
                    await navigator.share(shareData);
                } else if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(shareUrl);
                    alert(`Link copied to clipboard:\n${shareUrl}`);
                } else {
                    prompt('Copy this link:', shareUrl);
                }
            } catch (err) {
                console.error('Share failed', err);
                alert('Unable to share this event right now.');
            }
        }
    </script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
