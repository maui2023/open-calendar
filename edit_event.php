<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/Database.php';
require_once 'includes/Event.php';

require_login();

$database = new Database();
$db = $database->getConnection();
$event = new Event();
$currentUser = get_current_user_data();

$eventId = $_GET['id'] ?? null;
$eventData = null;
$error = null;

if (!$eventId) {
    header('Location: events.php');
    exit;
}

// Get event data
$eventData = $event->getById($eventId);
if (!$eventData) {
    $error = "Event not found.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $eventData) {
    $data = [
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? null,
        'start_time' => $_POST['start_time'] ?? null,
        'end_time' => $_POST['end_time'] ?? null,
        'color' => $_POST['color'] ?? '#007bff',
        'category' => $_POST['category'] ?? 'general',
        'priority' => $_POST['priority'] ?? 'medium',
        'all_day' => isset($_POST['all_day']) ? 1 : 0,
        'country_id' => $_POST['country_id'] ?? null
    ];
    
    try {
        if ($event->updateEvent($eventId, $data)) {
            $success = "Event updated successfully!";
            // Refresh event data
            $eventData = $event->getById($eventId);
        } else {
            $error = "Failed to update event. Please try again.";
        }
    } catch (Exception $e) {
        $error = "Error updating event: " . $e->getMessage();
    }
}

$categories = $event->getEventCategories();
$countries = $event->getCountryManager()->getAllCountries();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - <?php echo APP_NAME; ?></title>
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
                <a class="nav-link" href="add_event.php">Add Event</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="bi bi-pencil-square"></i> Edit Event
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="events.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Back to Events
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="POST" id="editEventForm">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="title" class="form-label">Event Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" required 
                                               value="<?php echo htmlspecialchars($eventData['title']); ?>"
                                               placeholder="Enter event title">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"
                                                  placeholder="Enter event description (optional)"><?php echo htmlspecialchars($eventData['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label">Start Date *</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required
                                               value="<?php echo $eventData['start_date']; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date"
                                               value="<?php echo $eventData['end_date'] ?? ''; ?>">
                                    </div>
                                </div>

                                <div class="row" id="timeFields" <?php echo $eventData['all_day'] ? 'style="display: none;"' : ''; ?>>
                                    <div class="col-md-6 mb-3">
                                        <label for="start_time" class="form-label">Start Time</label>
                                        <input type="time" class="form-control" id="start_time" name="start_time"
                                               value="<?php echo $eventData['start_time'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="end_time" class="form-label">End Time</label>
                                        <input type="time" class="form-control" id="end_time" name="end_time"
                                               value="<?php echo $eventData['end_time'] ?? ''; ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="country_id" class="form-label">Country</label>
                                        <select class="form-select" id="country_id" name="country_id" onchange="updateEventColor()">
                                            <option value="">Select a country (optional)</option>
                                            <?php foreach ($countries as $country): ?>
                                                <option value="<?php echo $country['id']; ?>" 
                                                        data-color="<?php echo htmlspecialchars($country['color']); ?>"
                                                        <?php echo ($eventData['country_id'] == $country['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($country['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="color" class="form-label">Event Color</label>
                                        <input type="color" class="form-control form-control-color" id="color" name="color" 
                                               value="<?php echo $eventData['color'] ?? '#007bff'; ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="category" class="form-label">Category</label>
                                        <select class="form-select" id="category" name="category">
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo htmlspecialchars($cat['name']); ?>"
                                                        <?php echo ($eventData['category'] === $cat['name']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars(ucfirst($cat['name'])); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="priority" class="form-label">Priority</label>
                                        <select class="form-select" id="priority" name="priority">
                                            <option value="low" <?php echo ($eventData['priority'] === 'low') ? 'selected' : ''; ?>>Low</option>
                                            <option value="medium" <?php echo ($eventData['priority'] === 'medium') ? 'selected' : ''; ?>>Medium</option>
                                            <option value="high" <?php echo ($eventData['priority'] === 'high') ? 'selected' : ''; ?>>High</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="all_day" name="all_day"
                                                   <?php echo $eventData['all_day'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="all_day">
                                                All Day Event
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                                    <div>
                                        <a href="events.php" class="btn btn-secondary">
                                            <i class="bi bi-arrow-left"></i> Back to Events
                                        </a>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-danger me-2" onclick="deleteEvent()">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle"></i> Update Event
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this event? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete Event</button>
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

        // Form validation
        document.getElementById('editEventForm').addEventListener('submit', function(e) {
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

        function deleteEvent() {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        function confirmDelete() {
            fetch(`api/events.php?id=<?php echo $eventId; ?>`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'events.php?deleted=1';
                } else {
                    alert('Failed to delete event: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the event.');
            });
        }

        // Function to update event color based on country selection
        function updateEventColor() {
            const countrySelect = document.getElementById('country_id');
            const colorInput = document.getElementById('color');
            const selectedOption = countrySelect.options[countrySelect.selectedIndex];
            
            if (selectedOption.value && selectedOption.dataset.color) {
                colorInput.value = selectedOption.dataset.color;
                colorInput.disabled = true;
            } else {
                colorInput.disabled = false;
                // Keep current color if no country selected
            }
        }
    </script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
