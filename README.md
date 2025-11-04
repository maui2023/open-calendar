# Open Calendar System

A modern, responsive calendar system built with PHP 8.4, MariaDB 11.4, and Bootstrap 5.3. This system provides a clean interface for managing events with full CRUD operations, similar to FullCalendar but with a custom PHP backend.

## Features

- 📅 **Monthly Calendar View** - Clean, responsive calendar grid
- ➕ **Event Management** - Create, read, update, and delete events
- 🎨 **Color-coded Events** - Customizable event colors and categories
- 📱 **Responsive Design** - Works on desktop, tablet, and mobile
- ⏰ **Time Management** - Support for all-day and timed events
- 🔍 **Search & Filter** - Find events quickly
- 📊 **Event Statistics** - View event counts and summaries
- 🏷️ **Categories & Priorities** - Organize events with categories and priority levels

## Requirements

- PHP 8.4 or higher
- MariaDB 11.4 or higher
- Web server (Apache/Nginx)
- Bootstrap 5.3 (included in assets folder)

## Installation

1. **Clone or download** this project to your web server directory

2. **Configure Database** - Edit `includes/config.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'calendar_system');
   ```

3. **Run Setup** - Navigate to `setup.php` in your browser to initialize the database:
   ```
   http://your-domain/setup.php
   ```

4. **Access Calendar** - Visit `index.php` to start using the calendar:
   ```
   http://your-domain/index.php
   ```

## File Structure

```
calendar-system/
├── assets/
│   ├── css/
│   │   ├── bootstrap.min.css    # Bootstrap 5.3 CSS
│   │   └── calendar.css         # Custom calendar styles
│   └── js/
│       ├── bootstrap.bundle.min.js  # Bootstrap 5.3 JS
│       └── calendar.js          # Calendar functionality
├── database/
│   └── schema.sql              # Database schema
├── includes/
│   ├── config.php              # Configuration settings
│   ├── Database.php            # Database connection class
│   ├── Event.php               # Event model class
│   └── modals.php              # Reusable modal components
├── api/
│   └── events.php              # REST API for events
├── index.php                   # Main calendar view
├── events.php                  # Events list page
├── add_event.php              # Add new event page
├── edit_event.php             # Edit event page
├── setup.php                  # Database setup script
└── README.md                  # This file
```

## Usage

### Main Calendar View (`index.php`)
- View events in a monthly calendar grid
- Navigate between months using arrow buttons
- Click on dates to add new events
- Click on events to view details
- Use the floating action button to quickly add events

### Events Management (`events.php`)
- View all events in a list format
- Search events by title or description
- Filter events by category or priority
- View event statistics
- Quick access to edit or delete events

### Adding Events (`add_event.php`)
- Create new events with detailed information
- Set event dates, times, and duration
- Choose colors and categories
- Set priority levels
- Support for all-day events

### Editing Events (`edit_event.php`)
- Modify existing event details
- Update event properties
- Delete events with confirmation

## API Endpoints

The system includes a REST API at `api/events.php`:

- `GET /api/events.php` - Get all events
- `GET /api/events.php?id=1` - Get specific event
- `GET /api/events.php?date=2024-01-15` - Get events for specific date
- `GET /api/events.php?month=2024-01` - Get events for specific month
- `POST /api/events.php` - Create new event
- `PUT /api/events.php?id=1` - Update event
- `DELETE /api/events.php?id=1` - Delete event

## Database Schema

The system uses two main tables:

### Events Table
- `id` - Primary key
- `title` - Event title
- `description` - Event description
- `start_date` - Event start date
- `end_date` - Event end date (optional)
- `start_time` - Event start time (optional)
- `end_time` - Event end time (optional)
- `color` - Event color
- `category` - Event category
- `priority` - Event priority (low/medium/high)
- `all_day` - All-day event flag
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp
- `deleted_at` - Soft delete timestamp

### Event Categories Table
- `id` - Primary key
- `name` - Category name
- `color` - Default category color
- `description` - Category description

## Customization

### Adding New Categories
Edit the `database/schema.sql` file to add new categories:
```sql
INSERT INTO event_categories (name, color, description) VALUES 
('custom', '#ff6b6b', 'Custom category');
```

### Styling
Modify `assets/css/calendar.css` to customize the appearance:
- Calendar grid colors
- Event styling
- Button appearances
- Responsive breakpoints

### Functionality
Extend the system by:
- Adding new event fields in `includes/Event.php`
- Creating additional API endpoints in `api/events.php`
- Enhancing the UI with new JavaScript features in `assets/js/calendar.js`

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## License

This project is open source and available under the GPLv3 License.

## Support

For issues or questions, please check the code comments or create an issue in the project repository.
