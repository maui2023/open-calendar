<?php
require_once 'includes/config.php';

// Database setup script
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar System Setup</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .setup-container { max-width: 800px; margin: 50px auto; }
        .log-output { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; font-family: monospace; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container setup-container">
        <h1 class="text-center mb-4">Calendar System Setup</h1>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Database Setup Progress</h5>
                <div class="log-output" id="setupLog">
<?php

function logMessage($message, $type = 'info') {
    $class = $type;
    echo "<div class='$class'>[" . date('Y-m-d H:i:s') . "] $message</div>";
    flush();
    ob_flush();
}

try {
    logMessage("Starting database setup...", 'info');
    
    // Create connection without database selection first
    $host = DB_HOST;
    $username = DB_USER;
    $password = DB_PASS;
    $database = DB_NAME;
    
    logMessage("Connecting to MariaDB server...", 'info');
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logMessage("Connected to MariaDB server successfully!", 'success');
    
    // Create database if it doesn't exist
    logMessage("Creating database '$database' if it doesn't exist...", 'info');
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    logMessage("Database '$database' is ready!", 'success');
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute schema
    logMessage("Reading database schema...", 'info');
    $schemaFile = 'database/schema.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $schema = file_get_contents($schemaFile);
    logMessage("Executing database schema...", 'info');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    logMessage("Database schema executed successfully!", 'success');
    
    // Test database connection using our classes
    logMessage("Testing database connection classes...", 'info');
    require_once 'includes/Database.php';
    require_once 'includes/Event.php';
    
    $database = new Database();
    $db = $database->getConnection();
    $event = new Event($db);
    
    logMessage("Database classes loaded successfully!", 'success');
    
    // Test basic operations
    logMessage("Testing event operations...", 'info');
    
    // Get categories
    $categories = $event->getEventCategories();
    logMessage("Found " . count($categories) . " event categories", 'success');
    
    // Get events
    $events = $event->getAllEvents();
    logMessage("Found " . count($events) . " events in database", 'success');
    
    // Test creating a sample event
    $testEvent = [
        'title' => 'Setup Test Event',
        'description' => 'This is a test event created during setup',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d'),
        'start_time' => '10:00',
        'end_time' => '11:00',
        'color' => '#28a745',
        'category' => 'general',
        'country_id' => 1,
        'priority' => 'medium',
        'all_day' => false,
        'created_by' => 1
    ];
    
    if ($event->createEvent($testEvent)) {
        logMessage("Test event created successfully!", 'success');
    } else {
        logMessage("Failed to create test event", 'error');
    }
    
    logMessage("Setup completed successfully!", 'success');
    logMessage("You can now access your calendar system:", 'info');
    logMessage("• Main Calendar: index.php", 'info');
    logMessage("• Events List: events.php", 'info');
    logMessage("• Add Event: add_event.php", 'info');
    
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage(), 'error');
    logMessage("Setup failed. Please check your database configuration.", 'error');
}

?>
                </div>
            </div>
        </div>
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-primary">Go to Calendar</a>
            <a href="events.php" class="btn btn-secondary">View Events</a>
        </div>
    </div>
</body>
</html>
