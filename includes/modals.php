<?php
$modalUser = function_exists('get_current_user_data') ? get_current_user_data() : null;
$modalCountryId = $modalUser['country_id'] ?? null;
?>
<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <?php if (!is_logged_in()): ?>
                <div class="modal-body">
                    <p class="mb-3">You need to sign in before creating events.</p>
                    <a class="btn btn-primary" href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            <?php else: ?>
                <form id="addEventForm" action="api/events.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="event_id" id="event_id_field" value="">
                        <?php if ($modalCountryId): ?>
                            <input type="hidden" name="country_id" value="<?php echo (int) $modalCountryId; ?>">
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Assign a country to your profile before adding events.
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="eventTitle" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="eventTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="eventDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="eventDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="eventStartDate" class="form-label">Start Date *</label>
                                <input type="date" class="form-control" id="eventStartDate" name="start_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="eventEndDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="eventEndDate" name="end_date">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="eventStartTime" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="eventStartTime" name="start_time">
                            </div>
                            <div class="col-md-6">
                                <label for="eventEndTime" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="eventEndTime" name="end_time">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label for="eventColor" class="form-label">Color</label>
                                <input type="color" class="form-control form-control-color" id="eventColor" name="color" value="#007bff">
                            </div>
                            <div class="col-md-4">
                                <label for="eventCategory" class="form-label">Category</label>
                                <select class="form-select" id="eventCategory" name="category">
                                    <option value="general">General</option>
                                    <option value="work">Work</option>
                                    <option value="personal">Personal</option>
                                    <option value="meeting">Meeting</option>
                                    <option value="important">Important</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="eventPriority" class="form-label">Priority</label>
                                <select class="form-select" id="eventPriority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="eventAllDay" name="all_day">
                                <label class="form-check-label" for="eventAllDay">
                                    All Day Event
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Event</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Event Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventDetailsContent">
                <!-- Event details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning d-none" id="editEventBtn">Edit</button>
                <button type="button" class="btn btn-danger d-none" id="deleteEventBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
