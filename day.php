<?php
require_once 'includes/config.php';
require_once 'includes/Event.php';
require_once 'includes/auth.php';

$eventModel = new Event();
// Auto-mark past events as completed
$eventModel->completePastEvents();

// Resolve date param
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
// Basic validation for date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

// Get events for the day
$events = $eventModel->getEventsByDateWithCountry($date);

// Prepare buckets
$allDayEvents = [];
$eventsByHour = [];
for ($h = 0; $h < 24; $h++) {
    $hourKey = str_pad((string)$h, 2, '0', STR_PAD_LEFT);
    $eventsByHour[$hourKey] = [];
}

foreach ($events as $event) {
    $isAllDay = !empty($event['all_day']) || (empty($event['start_time']) && empty($event['end_time']));

    if ($isAllDay) {
        $allDayEvents[] = $event;
        continue;
    }

    $startHour = '00';
    if (!empty($event['start_time'])) {
        $startHour = date('H', strtotime($event['start_time']));
    }
    $eventsByHour[$startHour][] = $event;
}

// Navigation dates
$current = new DateTime($date);
$prev = (clone $current)->modify('-1 day')->format('Y-m-d');
$next = (clone $current)->modify('+1 day')->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Day View (<?php echo $date; ?>)</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/calendar.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flag-icons@6.6.6/css/flag-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Top Header (match index.php) -->
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
                        
                        <?php if (is_logged_in()): ?>
                            <a class="btn btn-outline-secondary" href="<?php echo is_admin() ? 'admin/dashboard.php' : 'dashboard.php'; ?>">
                                <i class="fas fa-gauge-high me-1"></i>Dashboard
                            </a>
                            <a class="btn btn-primary" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a>
                        <?php else: ?>
                            <a class="btn btn-primary" href="login.php">
                                <i class="fas fa-user me-1"></i>Login
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Themed calendar container with sticky day navigation -->
        <div class="calendar-container">
            <div class="calendar-header sticky-top">
                <button class="calendar-nav-btn" onclick="location.href='day.php?date=<?php echo $prev; ?>'">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h3 class="mb-0"><?php echo date('l, F j, Y', strtotime($date)); ?></h3>
                <button class="calendar-nav-btn" onclick="location.href='day.php?date=<?php echo $next; ?>'">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

        <!-- All Day Events -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <strong>All-Day Events</strong>
                    </div>
                    <div class="card-body">
                        <?php if (empty($allDayEvents)): ?>
                            <p class="text-muted mb-0">No all-day events.</p>
                        <?php else: ?>
                            <?php foreach ($allDayEvents as $event): ?>
                                <div class="d-flex align-items-center mb-2" style="border-left: 4px solid <?php echo htmlspecialchars($event['color'] ?? '#0d6efd'); ?>; padding-left: 10px;">
                                    <div class="flex-grow-1">
                                        <strong>
                                            <?php if (!empty($event['country_code'])): ?>
                                                <?php 
                                                    $code3 = strtoupper($event['country_code']);
                                                    $map2 = [
                                                        'CYB' => 'my',
                                                        'BWA' => 'bw',
                                                        'SWZ' => 'sz',
                                                        'KHM' => 'kh',
                                                        'SLE' => 'sl',
                                                        'NAM' => 'na',
                                                        'UGA' => 'ug',
                                                        'LSO' => 'ls',
                                                    ];
                                                    $code2 = isset($map2[$code3]) ? $map2[$code3] : '';
                                                ?>
                                                <?php if ($code2): ?>
                                                    <span class="fi fi-<?php echo $code2; ?> me-1" title="<?php echo htmlspecialchars($event['country_name']); ?>" style="vertical-align: middle;"></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($event['title']); ?>
                                        </strong>
                                        <?php if (!empty($event['description'])): ?>
                                            <div class="text-muted small"><?php echo htmlspecialchars($event['description']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-<?php echo $event['priority'] === 'high' ? 'danger' : ($event['priority'] === 'medium' ? 'warning' : 'success'); ?>"><?php echo ucfirst($event['priority']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hourly Timeline -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Timeline (00:00–23:59)</strong>
                        <a class="btn btn-sm btn-outline-secondary" href="events.php">
                            <i class="fas fa-list me-1"></i>All Events
                        </a>
                    </div>
                    <div class="card-body">
                        <?php
                        for ($h = 0; $h < 24; $h++):
                            $hourKey = str_pad((string)$h, 2, '0', STR_PAD_LEFT);
                            $items = $eventsByHour[$hourKey];
                        ?>
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="me-3 text-muted" style="width: 70px;">
                                    <?php echo $hourKey; ?>:00
                                </div>
                                <div class="flex-grow-1 border-top"></div>
                            </div>
                            <?php if (empty($items)): ?>
                                <div class="text-muted small">No events</div>
                            <?php else: ?>
                                <?php foreach ($items as $event): ?>
                                    <?php
                                        $startTime = !empty($event['start_time']) ? date('H:i', strtotime($event['start_time'])) : '—';
                                        $endTime = !empty($event['end_time']) ? date('H:i', strtotime($event['end_time'])) : '';
                                    ?>
                                    <div class="d-flex align-items-center mb-2" style="border-left: 4px solid <?php echo htmlspecialchars($event['color'] ?? '#0d6efd'); ?>; padding-left: 10px;">
                                        <div class="me-3 text-nowrap" style="width: 70px;">
                                            <?php echo $startTime; ?><?php echo $endTime ? '–' . $endTime : ''; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <strong>
                                                <?php if (!empty($event['country_code'])): ?>
                                                    <?php 
                                                        $code3 = strtoupper($event['country_code']);
                                                        $map2 = [
                                                            'CYB' => 'my',
                                                            'BWA' => 'bw',
                                                            'SWZ' => 'sz',
                                                            'KHM' => 'kh',
                                                            'SLE' => 'sl',
                                                            'NAM' => 'na',
                                                            'UGA' => 'ug',
                                                            'LSO' => 'ls',
                                                        ];
                                                        $code2 = isset($map2[$code3]) ? $map2[$code3] : '';
                                                    ?>
                                                    <?php if ($code2): ?>
                                                        <span class="fi fi-<?php echo $code2; ?> me-1" title="<?php echo htmlspecialchars($event['country_name']); ?>" style="vertical-align: middle;"></span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($event['title']); ?>
                                            </strong>
                                            <?php if (!empty($event['description'])): ?>
                                                <div class="text-muted small"><?php echo htmlspecialchars($event['description']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="badge bg-<?php echo $event['priority'] === 'high' ? 'danger' : ($event['priority'] === 'medium' ? 'warning' : 'success'); ?>"><?php echo ucfirst($event['priority']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
        </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>