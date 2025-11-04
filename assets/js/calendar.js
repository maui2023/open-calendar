// Calendar JavaScript Functionality
class CalendarApp {
    constructor() {
        this.currentEventId = null;
        const rawUserId = typeof window !== 'undefined' ? window.currentUserId : null;
        if (rawUserId === null || rawUserId === undefined || rawUserId === 'null' || rawUserId === '') {
            this.currentUserId = null;
        } else {
            const parsed = parseInt(rawUserId, 10);
            this.currentUserId = Number.isNaN(parsed) ? null : parsed;
        }
        this.currentUserRole = typeof window !== 'undefined' ? window.currentUserRole || null : null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupFormValidation();
        this.loadEventCategories();
    }

    bindEvents() {
        // Form submission
        const addEventForm = document.getElementById('addEventForm');
        if (addEventForm) {
            addEventForm.addEventListener('submit', (e) => this.handleEventSubmit(e));
        }

        // All day checkbox toggle
        const allDayCheckbox = document.getElementById('eventAllDay');
        if (allDayCheckbox) {
            allDayCheckbox.addEventListener('change', (e) => this.toggleTimeFields(e.target.checked));
        }

        // Date field synchronization
        const startDateField = document.getElementById('eventStartDate');
        const endDateField = document.getElementById('eventEndDate');
        if (startDateField && endDateField) {
            startDateField.addEventListener('change', (e) => {
                if (!endDateField.value || endDateField.value < e.target.value) {
                    endDateField.value = e.target.value;
                }
            });
        }

        // Time field synchronization
        const startTimeField = document.getElementById('eventStartTime');
        const endTimeField = document.getElementById('eventEndTime');
        if (startTimeField && endTimeField) {
            startTimeField.addEventListener('change', (e) => {
                if (!endTimeField.value && e.target.value) {
                    const startTime = new Date(`2000-01-01 ${e.target.value}`);
                    startTime.setHours(startTime.getHours() + 1);
                    endTimeField.value = startTime.toTimeString().slice(0, 5);
                }
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));

        // Click outside to close modals
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                const modal = bootstrap.Modal.getInstance(e.target);
                if (modal) modal.hide();
            }
        });
    }

    setupFormValidation() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    }

    async loadEventCategories() {
        try {
            const response = await fetch('api/events.php?categories=true');
            const result = await response.json();
            
            if (result.success && result.data) {
                this.populateCategorySelect(result.data);
            }
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }

    populateCategorySelect(categories) {
        const categorySelect = document.getElementById('eventCategory');
        if (!categorySelect) return;

        // Clear existing options except the first one
        while (categorySelect.children.length > 1) {
            categorySelect.removeChild(categorySelect.lastChild);
        }

        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.name.toLowerCase();
            option.textContent = category.name;
            categorySelect.appendChild(option);
        });
    }

    toggleTimeFields(allDay) {
        const timeFields = ['eventStartTime', 'eventEndTime'];
        timeFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.disabled = allDay;
                if (allDay) {
                    field.value = '';
                }
            }
        });
    }

    async handleEventSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const eventIdField = form.querySelector('input[name="event_id"]');
        const eventIdValue = eventIdField && eventIdField.value ? parseInt(eventIdField.value, 10) : null;
        const isEditMode = eventIdValue !== null && !Number.isNaN(eventIdValue);
        
        // Show loading state
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

        try {
            let response;

            if (isEditMode) {
                const payload = {};
                formData.forEach((value, key) => {
                    payload[key] = value;
                });

                payload.event_id = eventIdValue;
                payload.all_day = form.querySelector('#eventAllDay')?.checked ? 1 : 0;

                ['end_date', 'start_time', 'end_time', 'country_id', 'color', 'description'].forEach((field) => {
                    if (field in payload && payload[field] === '') {
                        payload[field] = null;
                    }
                });

                response = await fetch(form.action, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });
            } else {
                if (eventIdField) {
                    eventIdField.value = '';
                }
                formData.delete('event_id');
                response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            }
            const result = await response.json();

            if (result.success) {
                this.showNotification(isEditMode ? 'Event updated successfully!' : 'Event created successfully!', 'success');
                form.reset();
                if (eventIdField) {
                    eventIdField.value = '';
                }
                if (isEditMode) {
                    this.currentEventId = null;
                }
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addEventModal'));
                if (modal) modal.hide();
                
                // Reload page to show updated events
                setTimeout(() => {
                    window.location.reload();
                }, 800);
            } else {
                const errorMessage = result.message || (isEditMode ? 'Error updating event' : 'Error creating event');
                this.showNotification(errorMessage, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Network error occurred', 'error');
        } finally {
            // Restore button state
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    }

    async loadEventDetails(eventId) {
        try {
            const response = await fetch(`api/events.php?id=${eventId}`);
            const result = await response.json();

            if (result.success && result.data) {
                this.displayEventDetails(result.data);
                this.currentEventId = eventId;
            } else {
                this.showNotification('Event not found', 'error');
            }
        } catch (error) {
            console.error('Error loading event:', error);
            this.showNotification('Error loading event details', 'error');
        }
    }

    displayEventDetails(event) {
        const content = document.getElementById('eventDetailsContent');
        if (!content) return;

        const startDate = new Date(event.start_date);
        const endDate = event.end_date ? new Date(event.end_date) : startDate;
        const startTime = event.start_time ? this.formatTime(event.start_time) : null;
        const endTime = event.end_time ? this.formatTime(event.end_time) : null;

        content.innerHTML = `
            <div class="event-details">
                <h5 class="mb-3" style="color: ${event.color}">${this.escapeHtml(event.title)}</h5>
                
                ${event.description ? `<p class="mb-3">${this.escapeHtml(event.description)}</p>` : ''}
                
                <div class="row">
                    <div class="col-md-6">
                        <strong>Date:</strong><br>
                        ${this.formatDate(startDate)}
                        ${endDate.getTime() !== startDate.getTime() ? ` - ${this.formatDate(endDate)}` : ''}
                    </div>
                    <div class="col-md-6">
                        ${startTime || endTime ? `
                            <strong>Time:</strong><br>
                            ${startTime || 'All day'}${endTime && endTime !== startTime ? ` - ${endTime}` : ''}
                        ` : '<strong>All Day Event</strong>'}
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-4">
                        <strong>Category:</strong><br>
                        <span class="badge bg-secondary">${this.escapeHtml(event.category)}</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Priority:</strong><br>
                        <span class="badge bg-${this.getPriorityColor(event.priority)}">${this.escapeHtml(event.priority)}</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Status:</strong><br>
                        <span class="badge bg-${this.getStatusColor(event.status)}">${this.escapeHtml(event.status)}</span>
                    </div>
                </div>
                
                ${event.created_at ? `
                    <div class="mt-3">
                        <small class="text-muted">
                            Created: ${this.formatDateTime(event.created_at)}
                            ${event.updated_at && event.updated_at !== event.created_at ? 
                                `<br>Updated: ${this.formatDateTime(event.updated_at)}` : ''}
                        </small>
                    </div>
                ` : ''}
            </div>
        `;

        // Setup action buttons
        this.setupEventActionButtons(event);
    }

    setupEventActionButtons(event) {
        const modalEl = document.getElementById('eventDetailsModal');
        const editBtn = modalEl ? modalEl.querySelector('#editEventBtn') : document.getElementById('editEventBtn');
        const deleteBtn = modalEl ? modalEl.querySelector('#deleteEventBtn') : document.getElementById('deleteEventBtn');
        const isPast = this.isEventPast(event);
        const canEdit = this.canEditEvent(event);

        // Debug info to help diagnose visibility issues
        try {
            console.debug('[CalendarApp] setupEventActionButtons', {
                currentUserId: this.currentUserId,
                eventCreatedBy: event && event.created_by,
                isPast,
                canEdit,
                editBtnFound: !!editBtn,
                deleteBtnFound: !!deleteBtn
            });
        } catch (_) {}

        // Edit button visibility
        if (editBtn) {
            if (!isPast && canEdit) {
                editBtn.classList.remove('d-none');
                editBtn.onclick = () => this.editEvent(event);
            } else {
                editBtn.classList.add('d-none');
                editBtn.onclick = null;
            }
        }

        // Delete button visibility
        if (deleteBtn) {
            if (!isPast && canEdit) {
                deleteBtn.classList.remove('d-none');
                deleteBtn.onclick = () => this.deleteEvent(event.id);
            } else {
                deleteBtn.classList.add('d-none');
                deleteBtn.onclick = null;
            }
        }
    }

    canEditEvent(event) {
        if (!event) return false;
        if (!this.currentUserId) return false;
        // Only owner (created_by) may edit/delete
        const creatorId = event.created_by !== undefined && event.created_by !== null
            ? parseInt(event.created_by, 10)
            : null;
        return creatorId !== null && creatorId === this.currentUserId;
    }

    isEventPast(event) {
        try {
            const now = new Date();
            const endDateStr = event.end_date || event.start_date;
            let endTimeStr = event.end_time || null;

            // Normalize time string
            if (!endTimeStr || endTimeStr === '00:00:00') {
                if (event.all_day) {
                    endTimeStr = '23:59:59';
                } else if (event.start_time) {
                    endTimeStr = event.start_time;
                } else {
                    endTimeStr = '23:59:59';
                }
            }

            // Parse date parts (YYYY-MM-DD)
            const parts = (endDateStr || '').split('-').map(p => parseInt(p, 10));
            if (parts.length !== 3 || parts.some(Number.isNaN)) {
                // If parsing fails, treat as not past to avoid false negatives
                return false;
            }
            const [year, month, day] = parts;

            // Parse time parts (HH:MM[:SS])
            const timeParts = (endTimeStr || '').split(':').map(p => parseInt(p, 10));
            const hours = !Number.isNaN(timeParts[0]) ? timeParts[0] : 23;
            const minutes = !Number.isNaN(timeParts[1]) ? timeParts[1] : 59;
            const seconds = !Number.isNaN(timeParts[2]) ? timeParts[2] : 59;

            // Create local Date object
            const endDateTime = new Date(year, month - 1, day, hours, minutes, seconds);
            return now > endDateTime;
        } catch (e) {
            // On error, default to not past so UI remains permissive
            return false;
        }
    }

    prepareCreateForm(defaultDate = null) {
        const form = document.getElementById('addEventForm');
        const modalElement = document.getElementById('addEventModal');
        if (!form || !modalElement) return;

        this.currentEventId = null;

        const eventIdField = form.querySelector('input[name="event_id"]');
        if (eventIdField) eventIdField.value = '';

        const modalTitle = modalElement.querySelector('.modal-title');
        if (modalTitle) modalTitle.textContent = 'Add New Event';

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.textContent = 'Add Event';

        const allDayCheckbox = form.querySelector('#eventAllDay, #all_day');
        if (allDayCheckbox) {
            allDayCheckbox.checked = false;
            this.toggleTimeFields(false);
        }

        if (defaultDate) {
            const startDateField = form.querySelector('#eventStartDate, #start_date');
            if (startDateField) startDateField.value = defaultDate;
            const endDateField = form.querySelector('#eventEndDate, #end_date');
            if (endDateField) endDateField.value = defaultDate;
        }

        const startTimeField = form.querySelector('#eventStartTime, #start_time');
        if (startTimeField) startTimeField.value = '';
        const endTimeField = form.querySelector('#eventEndTime, #end_time');
        if (endTimeField) endTimeField.value = '';

        const colorField = form.querySelector('#eventColor, #color');
        if (colorField && !colorField.disabled) {
            colorField.value = '#007bff';
        }
    }

    async editEventById(eventId) {
        if (!eventId) return;
        try {
            const response = await fetch(`api/events.php?id=${eventId}`);
            const result = await response.json();

            if (result.success && result.data) {
                this.editEvent(result.data);
            } else {
                this.showNotification(result.message || 'Event not found', 'error');
            }
        } catch (error) {
            console.error('Error loading event for edit:', error);
            this.showNotification('Unable to load event for editing', 'error');
        }
    }

    editEvent(event) {
        if (!this.canEditEvent(event)) {
            this.showNotification('You do not have permission to edit this event.', 'error');
            return;
        }

        const form = document.getElementById('addEventForm');
        const modalElement = document.getElementById('addEventModal');
        if (!form || !modalElement) {
            return;
        }

        this.currentEventId = event.id;

        const eventIdField = form.querySelector('input[name="event_id"]');
        if (eventIdField) {
            eventIdField.value = event.id;
        }

        const titleField = form.querySelector('#eventTitle, #title');
        if (titleField) titleField.value = event.title || '';

        const descriptionField = form.querySelector('#eventDescription, #description');
        if (descriptionField) descriptionField.value = event.description || '';

        const startDateField = form.querySelector('#eventStartDate, #start_date');
        if (startDateField) startDateField.value = event.start_date || '';

        const endDateField = form.querySelector('#eventEndDate, #end_date');
        if (endDateField) endDateField.value = event.end_date || event.start_date || '';

        const startTimeField = form.querySelector('#eventStartTime, #start_time');
        if (startTimeField) startTimeField.value = event.start_time || '';

        const endTimeField = form.querySelector('#eventEndTime, #end_time');
        if (endTimeField) endTimeField.value = event.end_time || '';

        const allDayCheckbox = form.querySelector('#eventAllDay, #all_day');
        if (allDayCheckbox) {
            allDayCheckbox.checked = Boolean(event.all_day);
            this.toggleTimeFields(allDayCheckbox.checked);
        }

        const categoryField = form.querySelector('#eventCategory, #category');
        if (categoryField) categoryField.value = event.category || categoryField.value;

        const priorityField = form.querySelector('#eventPriority, #priority');
        if (priorityField) priorityField.value = event.priority || priorityField.value;

        const colorField = form.querySelector('#eventColor, #color');
        if (colorField) {
            colorField.value = event.color || '#007bff';
        }

        const countryField = document.getElementById('country_id');
        if (countryField && countryField.tagName === 'SELECT') {
            countryField.value = event.country_id || '';
            if (typeof updateEventColor === 'function') {
                updateEventColor();
            }
        }

        const modalTitle = modalElement.querySelector('.modal-title');
        if (modalTitle) modalTitle.textContent = 'Edit Event';

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.textContent = 'Update Event';

        const detailsModal = bootstrap.Modal.getInstance(document.getElementById('eventDetailsModal'));
        if (detailsModal) detailsModal.hide();

        const editModal = new bootstrap.Modal(modalElement);
        editModal.show();
    }

    async deleteEvent(eventId) {
        if (!confirm('Are you sure you want to delete this event?')) {
            return;
        }

        try {
            const response = await fetch(`api/events.php?id=${eventId}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Event deleted successfully!', 'success');
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('eventDetailsModal'));
                if (modal) modal.hide();
                
                // Reload page
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                this.showNotification(result.message || 'Error deleting event', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Network error occurred', 'error');
        }
    }

    handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + N: New event
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            this.prepareCreateForm(new Date().toISOString().split('T')[0]);
            const modal = new bootstrap.Modal(document.getElementById('addEventModal'));
            modal.show();
        }

        // Escape: Close modals
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                const instance = bootstrap.Modal.getInstance(modal);
                if (instance) instance.hide();
            });
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Utility functions
    formatDate(date) {
        return date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    formatTime(timeString) {
        const time = new Date(`2000-01-01 ${timeString}`);
        return time.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    formatDateTime(dateTimeString) {
        const date = new Date(dateTimeString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    getPriorityColor(priority) {
        const colors = {
            'low': 'success',
            'medium': 'warning',
            'high': 'danger'
        };
        return colors[priority] || 'secondary';
    }

    getStatusColor(status) {
        const colors = {
            'active': 'success',
            'cancelled': 'danger',
            'completed': 'info'
        };
        return colors[status] || 'secondary';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global functions for calendar interactions
function openEventModal(eventId) {
    const app = window.calendarApp;
    if (app) {
        app.loadEventDetails(eventId);
        const modal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
        modal.show();
    }
}

function openDayModal(date) {
    if (window.calendarApp) {
        window.calendarApp.prepareCreateForm(date);
    } else {
        const startDateField = document.getElementById('eventStartDate');
        const endDateField = document.getElementById('eventEndDate');
        if (startDateField) startDateField.value = date;
        if (endDateField) endDateField.value = date;
    }
    
    // Open add event modal
    const modal = new bootstrap.Modal(document.getElementById('addEventModal'));
    modal.show();
}

// Initialize calendar app when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.calendarApp = new CalendarApp();
    
    // Set default date to today for new events
    const today = new Date().toISOString().split('T')[0];
    const startDateField = document.getElementById('eventStartDate');
    const endDateField = document.getElementById('eventEndDate');
    
    if (startDateField && !startDateField.value) {
        startDateField.value = today;
    }
    if (endDateField && !endDateField.value) {
        endDateField.value = today;
    }
});

// Handle modal cleanup
document.addEventListener('hidden.bs.modal', function(e) {
    if (e.target.id === 'addEventModal') {
        // Reset form to add mode
        const form = document.getElementById('addEventForm');
        const modalTitle = e.target.querySelector('.modal-title');
        const submitBtn = form.querySelector('button[type="submit"]');
        const eventIdField = form.querySelector('input[name="event_id"]');
        
        modalTitle.textContent = 'Add New Event';
        submitBtn.textContent = 'Add Event';
        if (eventIdField) {
            eventIdField.value = '';
        }
        
        // Reset form validation
        form.classList.remove('was-validated');
    }

    // Reset event details modal buttons when it closes to avoid stale state
    if (e.target.id === 'eventDetailsModal') {
        const editBtn = e.target.querySelector('#editEventBtn') || document.getElementById('editEventBtn');
        const deleteBtn = e.target.querySelector('#deleteEventBtn') || document.getElementById('deleteEventBtn');
        if (editBtn) {
            editBtn.classList.add('d-none');
            editBtn.onclick = null;
        }
        if (deleteBtn) {
            deleteBtn.classList.add('d-none');
            deleteBtn.onclick = null;
        }
    }
});
