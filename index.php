<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/Event.php';

$eventModel = new Event();
// Auto-mark past events as completed
$eventModel->completePastEvents();
$currentUser = get_current_user_data();

// Get current month and year from URL parameters or use current date
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validate month and year
if ($currentMonth < 1 || $currentMonth > 12) {
    $currentMonth = date('n');
}
if ($currentYear < 1900 || $currentYear > 2100) {
    $currentYear = date('Y');
}

// Get events for the current month with country information
$events = $eventModel->getEventsByMonthWithCountry($currentYear, $currentMonth);

// Group events by date
$eventsByDate = [];
foreach ($events as $event) {
    $startDate = $event['start_date'];
    $endDate = $event['end_date'] ?? $startDate;
    
    // Add event to all dates in its range
    $current = new DateTime($startDate);
    $end = new DateTime($endDate);
    
    while ($current <= $end) {
        $dateKey = $current->format('Y-m-d');
        if (!isset($eventsByDate[$dateKey])) {
            $eventsByDate[$dateKey] = [];
        }
        $eventsByDate[$dateKey][] = $event;
        $current->add(new DateInterval('P1D'));
    }
}

// Calculate calendar data
$firstDayOfMonth = new DateTime("$currentYear-$currentMonth-01");
$lastDayOfMonth = new DateTime($firstDayOfMonth->format('Y-m-t'));
$startOfCalendar = clone $firstDayOfMonth;
$startOfCalendar->modify('last sunday');
$endOfCalendar = clone $lastDayOfMonth;
$endOfCalendar->modify('next saturday');

// Navigation dates
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo $monthNames[$currentMonth] . ' ' . $currentYear; ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/calendar.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flag-icons@6.6.6/css/flag-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h2 mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?php echo APP_NAME; ?>
                    </h1>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" onclick="location.href='day.php?date=<?php echo date('Y-m-d'); ?>'">
                            <i class="fas fa-calendar-day me-1"></i>Today
                        </button>
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

        <!-- Calendar Container -->
        <div class="calendar-container">
            <!-- Calendar Header -->
            <div class="calendar-header">
                <button class="calendar-nav-btn" onclick="location.href='?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>'">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h3 class="mb-0"><?php echo $monthNames[$currentMonth] . ' ' . $currentYear; ?></h3>
                <button class="calendar-nav-btn" onclick="location.href='?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>'">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <!-- Calendar Grid -->
            <div class="calendar-grid">
                <!-- Day Headers -->
                <?php foreach ($dayNames as $dayName): ?>
                    <div class="calendar-day-header"><?php echo $dayName; ?></div>
                <?php endforeach; ?>

                <!-- Calendar Days -->
                <?php
                $current = clone $startOfCalendar;
                while ($current <= $endOfCalendar):
                    $dateKey = $current->format('Y-m-d');
                    $dayNumber = $current->format('j');
                    $isCurrentMonth = $current->format('n') == $currentMonth;
                    $isToday = $current->format('Y-m-d') === date('Y-m-d');
                    $hasEvents = isset($eventsByDate[$dateKey]);
                    
                    $dayClasses = ['calendar-day'];
                    if (!$isCurrentMonth) $dayClasses[] = 'other-month';
                    if ($isToday) $dayClasses[] = 'today';
                    if ($hasEvents) $dayClasses[] = 'has-events';
                ?>
                    <div class="<?php echo implode(' ', $dayClasses); ?>" 
                         data-date="<?php echo $dateKey; ?>"
                         onclick="openDayModal('<?php echo $dateKey; ?>')">
                        <div class="day-number"><?php echo $dayNumber; ?></div>
                        
                        <?php if ($hasEvents): ?>
                            <?php foreach (array_slice($eventsByDate[$dateKey], 0, 3) as $event): ?>
                                <div class="event-item event-<?php echo $event['priority']; ?>" 
                                     style="background-color: <?php echo htmlspecialchars($event['color']); ?>"
                                     onclick="event.stopPropagation(); openEventModal(<?php echo $event['id']; ?>)"
                                     title="<?php echo htmlspecialchars($event['title']); ?><?php echo !empty($event['country_name']) ? ' (' . htmlspecialchars($event['country_name']) . ')' : ''; ?>">
                                    <?php if (!empty($event['country_code'])): ?>
                                        <?php 
                                            // Map 3-letter codes to 2-letter ISO codes for flag-icons
                                            $code3 = strtoupper($event['country_code']);
                                            $map2 = [
                                                'CYB' => 'my', // Cyberjaya in Malaysia
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
                                    <?php echo htmlspecialchars(substr($event['title'], 0, 20)); ?>
                                    <?php if (strlen($event['title']) > 20): ?>...<?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($eventsByDate[$dateKey]) > 3): ?>
                                <div class="event-item" style="background-color: #6c757d; font-size: 0.7em;">
                                    +<?php echo count($eventsByDate[$dateKey]) - 3; ?> more
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php
                    $current->add(new DateInterval('P1D'));
                endwhile;
                ?>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-primary"><?php echo count($events); ?></h5>
                        <p class="card-text">Events This Month</p>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Upcoming Events</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $upcomingEvents = $eventModel->getUpcomingEvents(3);
                        if (empty($upcomingEvents)):
                        ?>
                            <p class="text-muted mb-0">No upcoming events</p>
                        <?php else: ?>
                            <?php foreach ($upcomingEvents as $event): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                        <small class="text-muted d-block">
                                            <?php echo date('M j, Y', strtotime($event['start_date'])); ?>
                                            <?php if ($event['start_time']): ?>
                                                at <?php echo date('g:i A', strtotime($event['start_time'])); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php echo $event['priority'] === 'high' ? 'danger' : ($event['priority'] === 'medium' ? 'warning' : 'success'); ?>">
                                        <?php echo ucfirst($event['priority']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/modals.php'; ?>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        window.currentUserId = <?php echo $currentUser ? (int) $currentUser['id'] : 'null'; ?>;
        window.currentUserRole = <?php echo $currentUser ? json_encode($currentUser['role']) : 'null'; ?>;
    </script>
    <script src="assets/js/calendar.js"></script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
