<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/Event.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$eventModel = new Event();
// Auto-mark past events as completed before serving API responses
$eventModel->completePastEvents();
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => '', 'data' => null];

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($eventModel, $response);
            break;
            
        case 'POST':
            handlePostRequest($eventModel, $response);
            break;
            
        case 'PUT':
            handlePutRequest($eventModel, $response);
            break;
            
        case 'DELETE':
            handleDeleteRequest($eventModel, $response);
            break;
            
        default:
            $response['message'] = 'Method not allowed';
            http_response_code(405);
            break;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);

function handleGetRequest($eventModel, &$response) {
    if (isset($_GET['id'])) {
        // Get single event
        $event = $eventModel->getEventById($_GET['id']);
        if ($event) {
            $response['success'] = true;
            $response['data'] = $event;
        } else {
            $response['message'] = 'Event not found';
            http_response_code(404);
        }
    } elseif (isset($_GET['date'])) {
        // Get events by date
        $events = $eventModel->getEventsByDate($_GET['date']);
        $response['success'] = true;
        $response['data'] = $events;
    } elseif (isset($_GET['month']) && isset($_GET['year'])) {
        // Get events by month with country information
        $events = $eventModel->getEventsByMonthWithCountry($_GET['year'], $_GET['month']);
        $response['success'] = true;
        $response['data'] = $events;
    } elseif (isset($_GET['search'])) {
        // Search events
        $events = $eventModel->searchEvents($_GET['search']);
        $response['success'] = true;
        $response['data'] = $events;
    } elseif (isset($_GET['upcoming'])) {
        // Get upcoming events
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $events = $eventModel->getUpcomingEvents($limit);
        $response['success'] = true;
        $response['data'] = $events;
    } elseif (isset($_GET['stats'])) {
        // Get event statistics
        $stats = $eventModel->getEventStats();
        $response['success'] = true;
        $response['data'] = $stats;
    } elseif (isset($_GET['categories'])) {
        // Get event categories
        $categories = $eventModel->getEventCategories();
        $response['success'] = true;
        $response['data'] = $categories;
    } else {
        // Get all events
        $events = $eventModel->getAllEventsWithCountry();
        $response['success'] = true;
        $response['data'] = $events;
    }
}

function handlePostRequest($eventModel, &$response) {
    if (!is_logged_in()) {
        $response['message'] = 'Authentication required';
        http_response_code(401);
        return;
    }
    // Check if it's a form submission (redirect) or AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    $data = [];
    
    if ($isAjax) {
        // Handle JSON data
        $input = json_decode(file_get_contents('php://input'), true);
        $data = $input ?: $_POST;
    } else {
        // Handle form data
        $data = $_POST;
    }

    $currentUser = get_current_user_data();
    if ($currentUser) {
        if (!empty($currentUser['country_id'])) {
            $data['country_id'] = $currentUser['country_id'];
        }
        $data['created_by'] = $currentUser['id'];
    }

    // Validate required fields
    if (empty($data['title']) || empty($data['start_date'])) {
        $response['message'] = 'Title and start date are required';
        http_response_code(400);
        
        if (!$isAjax) {
            // Redirect back with error
            header('Location: ../index.php?error=' . urlencode($response['message']));
            exit();
        }
        return;
    }
    
    if (empty($data['country_id'])) {
        $response['message'] = 'Country is required to create an event.';
        http_response_code(400);

        if (!$isAjax) {
            header('Location: ../index.php?error=' . urlencode($response['message']));
            exit();
        }
        return;
    }
    
    // Create event
    $eventId = $eventModel->createEvent($data);
    
    if ($eventId) {
        $response['success'] = true;
        $response['message'] = 'Event created successfully';
        $response['data'] = ['id' => $eventId];
        
        if (!$isAjax) {
            // Redirect back to calendar
            $month = isset($data['start_date']) ? date('n', strtotime($data['start_date'])) : date('n');
            $year = isset($data['start_date']) ? date('Y', strtotime($data['start_date'])) : date('Y');
            header("Location: ../index.php?month=$month&year=$year&success=" . urlencode($response['message']));
            exit();
        }
    } else {
        $response['message'] = 'Failed to create event';
        http_response_code(500);
        
        if (!$isAjax) {
            header('Location: ../index.php?error=' . urlencode($response['message']));
            exit();
        }
    }
}

function handlePutRequest($eventModel, &$response) {
    if (!is_logged_in()) {
        $response['message'] = 'Authentication required';
        http_response_code(401);
        return;
    }
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $eventId = isset($input['event_id']) ? (int)$input['event_id'] : (isset($input['id']) ? (int)$input['id'] : 0);
    if ($eventId <= 0) {
        $response['message'] = 'Event ID is required';
        http_response_code(400);
        return;
    }

    $event = $eventModel->getEventById($eventId);
    if (!$event) {
        $response['message'] = 'Event not found';
        http_response_code(404);
        return;
    }

    $currentUser = get_current_user_data();
    if (!$currentUser) {
        $response['message'] = 'Authentication required';
        http_response_code(401);
        return;
    }

    $isOwner = isset($event['created_by']) && (int)$event['created_by'] === (int)$currentUser['id'];
    $isAdmin = isset($currentUser['role']) && $currentUser['role'] === 'admin';
    if (!$isOwner && !$isAdmin) {
        $response['message'] = 'You do not have permission to update this event.';
        http_response_code(403);
        return;
    }

    $updatableKeys = [
        'title',
        'description',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'color',
        'category',
        'priority',
        'status',
        'country_id',
        'all_day'
    ];

    $updateData = [];
    foreach ($updatableKeys as $key) {
        if (array_key_exists($key, $input)) {
            $value = $input[$key];
            if ($value === '' || $value === 'null') {
                $value = null;
            }
            $updateData[$key] = $value;
        }
    }

    if (!$updateData) {
        $response['message'] = 'No changes detected.';
        http_response_code(400);
        return;
    }

    if (isset($updateData['title']) && trim((string)$updateData['title']) === '') {
        $response['message'] = 'Title cannot be empty.';
        http_response_code(400);
        return;
    }

    if (isset($updateData['start_date']) && empty($updateData['start_date'])) {
        $response['message'] = 'Start date is required.';
        http_response_code(400);
        return;
    }

    if (!empty($currentUser['country_id'])) {
        $updateData['country_id'] = $currentUser['country_id'];
    }

    if (isset($updateData['all_day'])) {
        $updateData['all_day'] = (bool)$updateData['all_day'];
    }

    $result = $eventModel->updateEvent($eventId, $updateData);

    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Event updated successfully';
    } else {
        $response['message'] = 'Failed to update event';
        http_response_code(500);
    }
}

function handleDeleteRequest($eventModel, &$response) {
    if (!is_logged_in()) {
        $response['message'] = 'Authentication required';
        http_response_code(401);
        return;
    }
    if (isset($_GET['id'])) {
        $eventId = $_GET['id'];
        $hard = isset($_GET['hard']) && $_GET['hard'] === 'true';
        
        if ($hard) {
            $result = $eventModel->hardDeleteEvent($eventId);
        } else {
            $result = $eventModel->deleteEvent($eventId);
        }
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = 'Event deleted successfully';
        } else {
            $response['message'] = 'Failed to delete event';
            http_response_code(500);
        }
    } else {
        $response['message'] = 'Event ID is required';
        http_response_code(400);
    }
}
?>
