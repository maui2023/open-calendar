<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/Database.php';
require_once 'includes/Event.php';

require_login();

$event = new Event();
$currentUser = get_current_user_data();
$userCountryId = $currentUser['country_id'] ?? null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $countryId = $userCountryId ?: (!empty($_POST['country_id']) ? (int) $_POST['country_id'] : null);

    $data = [
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? null,
        'start_time' => $_POST['start_time'] ?? null,
        'end_time' => $_POST['end_time'] ?? null,
        'color' => $_POST['color'] ?? '#007bff',
        'category' => $_POST['category'] ?? 'general',
        'country_id' => $countryId,
        'priority' => $_POST['priority'] ?? 'medium',
        'all_day' => isset($_POST['all_day']) ? 1 : 0,
        'created_by' => $currentUser['id'] ?? null
    ];
    
    try {
        if ($event->createEvent($data)) {
            $success = "Event created successfully!";
        } else {
            $error = "Failed to create event. Please try again.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$categories = $event->getEventCategories();
$countries = $event->getCountryManager()->getAllCountries();
$countryLookup = [];
foreach ($countries as $country) {
    $countryLookup[$country['id']] = $country;
}
$userCountryName = '';
$userCountryColor = '#007bff';
if ($userCountryId && isset($countryLookup[$userCountryId])) {
    $userCountryName = $countryLookup[$userCountryId]['name'];
    $userCountryColor = $countryLookup[$userCountryId]['color'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - <?php echo APP_NAME; ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/calendar.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-calendar3"></i> <?php echo APP_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Calendar</a>
                <a class="nav-link" href="events.php">Events</a>
                <a class="nav-link active" href="add_event.php">Add Event</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="bi bi-plus-circle"></i> Add New Event
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="addEventForm">
                            <input type="hidden" name="event_id" id="event_id_page_field" value="">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="title" class="form-label">Event Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           placeholder="Enter event title">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"
                                              placeholder="Enter event description (optional)"></textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date *</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date">
                                </div>
                            </div>

                            <div class="row" id="timeFields">
                                <div class="col-md-6 mb-3">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Country</label>
                                    <?php if ($userCountryId): ?>
                                        <input type="hidden" name="country_id" value="<?php echo (int) $userCountryId; ?>">
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($userCountryName); ?>" disabled>
                                        <div class="form-text">Country is set from your profile.</div>
                                    <?php else: ?>
                                        <select class="form-select" id="country_id" name="country_id" required onchange="updateEventColor()">
                                            <option value="">Select Country</option>
                                            <?php foreach ($countries as $country): ?>
                                                <option value="<?php echo $country['id']; ?>" data-color="<?php echo $country['color']; ?>">
                                                    <?php echo htmlspecialchars($country['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Selecting a country will automatically set the event color</div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category">
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat['name']); ?>">
                                                <?php echo htmlspecialchars(ucfirst($cat['name'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="color" class="form-label">Event Color</label>
                                    <input type="color" class="form-control form-control-color" id="color" name="color" value="<?php echo htmlspecialchars($userCountryColor); ?>" <?php echo $userCountryId ? 'disabled' : ''; ?>>
                                    <div class="form-text">Color will be automatically set when a country is selected</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="priority" class="form-label">Priority</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="all_day" name="all_day">
                                        <label class="form-check-label" for="all_day">
                                            All Day Event
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-secondary me-md-2">
                                    <i class="bi bi-arrow-left"></i> Back to Calendar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Create Event
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle time fields based on all day checkbox
        document.getElementById('all_day').addEventListener('change', function() {
            const timeFields = document.getElementById('timeFields');
            const startTime = document.getElementById('start_time');
            const endTime = document.getElementById('end_time');
            
            if (this.checked) {
                timeFields.style.display = 'none';
                startTime.value = '';
                endTime.value = '';
            } else {
                timeFields.style.display = 'flex';
            }
        });

        // Set default start date to today
        document.getElementById('start_date').value = new Date().toISOString().split('T')[0];

        // Auto-set end date when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            const endDate = document.getElementById('end_date');
            if (!endDate.value) {
                endDate.value = this.value;
            }
        });

        // Form validation
        document.getElementById('addEventForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const startDate = document.getElementById('start_date').value;
            
            if (!title) {
                e.preventDefault();
                alert('Please enter an event title.');
                return;
            }
            
            if (!startDate) {
                e.preventDefault();
                alert('Please select a start date.');
                return;
            }
            
            const endDate = document.getElementById('end_date').value;
            if (endDate && endDate < startDate) {
                e.preventDefault();
                alert('End date cannot be before start date.');
                return;
            }
        });

        // Function to update event color based on country selection
        function updateEventColor() {
            const countrySelect = document.getElementById('country_id');
            const colorInput = document.getElementById('color');

            if (!countrySelect || !colorInput) {
                return;
            }

            const selectedOption = countrySelect.options[countrySelect.selectedIndex];
            
            if (selectedOption.value && selectedOption.dataset.color) {
                colorInput.value = selectedOption.dataset.color;
                colorInput.disabled = true;
            } else {
                colorInput.disabled = false;
                colorInput.value = '#007bff'; // Reset to default
            }
        }

        updateEventColor();
    </script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
