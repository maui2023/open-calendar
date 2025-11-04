<?php
require_once 'Database.php';
require_once 'Country.php';

class Event {
    private $db;
    private $country;
    
    public function __construct() {
        $this->db = new Database();
        $this->country = new Country($this->db);
    }

    /**
     * Mark all past events as completed.
     *
     * Rules:
     * - If an event has an end_date, use end_date + end_time (or 23:59:59) to determine past
     * - If no end_date, use start_date + end_time (or start_time or 23:59:59)
     * - Only transition events currently marked as 'active'
     */
    public function completePastEvents() {
        $sql = "UPDATE events
                SET status = 'completed', updated_at = NOW()
                WHERE status = 'active' AND (
                    (
                        end_date IS NOT NULL AND
                        CONCAT(end_date, ' ', COALESCE(end_time, '23:59:59')) < NOW()
                    )
                    OR (
                        end_date IS NULL AND (
                            (
                                start_time IS NULL AND
                                CONCAT(start_date, ' ', '23:59:59') < NOW()
                            )
                            OR (
                                start_time IS NOT NULL AND
                                CONCAT(start_date, ' ', COALESCE(end_time, start_time)) < NOW()
                            )
                        )
                    )
                )";
        $this->db->query($sql);
    }
    
    public function getAllEvents() {
        $sql = "SELECT * FROM events WHERE status = 'active' ORDER BY start_date, start_time";
        return $this->db->fetchAll($sql);
    }
    
    public function getEventsByMonth($year, $month) {
        $sql = "SELECT * FROM events 
                WHERE status = 'active' 
                AND (
                    (YEAR(start_date) = :year1 AND MONTH(start_date) = :month1)
                    OR (YEAR(end_date) = :year2 AND MONTH(end_date) = :month2)
                    OR (start_date <= :month_end AND end_date >= :month_start)
                )
                ORDER BY start_date, start_time";
        
        $monthStart = sprintf('%04d-%02d-01', $year, $month);
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        
        return $this->db->fetchAll($sql, [
            'year1' => $year,
            'month1' => $month,
            'year2' => $year,
            'month2' => $month,
            'month_start' => $monthStart,
            'month_end' => $monthEnd
        ]);
    }
    
    public function getEventsByDate($date) {
        $sql = "SELECT * FROM events 
                WHERE status = 'active' 
                AND (
                    (start_date <= :date1 AND end_date >= :date2)
                    OR (start_date = :date3)
                )
                ORDER BY start_time";

        return $this->db->fetchAll($sql, [
            'date1' => $date,
            'date2' => $date,
            'date3' => $date
        ]);
    }

    /**
     * Get events for a specific date with country information
     */
    public function getEventsByDateWithCountry($date) {
        $sql = "SELECT e.*, c.name as country_name, c.color as country_color, c.code as country_code 
                FROM events e 
                LEFT JOIN countries c ON e.country_id = c.id 
                WHERE e.status = 'active' 
                AND (
                    (e.start_date <= :date1 AND e.end_date >= :date2)
                    OR (e.start_date = :date3)
                )
                ORDER BY e.start_time";

        return $this->db->fetchAll($sql, [
            'date1' => $date,
            'date2' => $date,
            'date3' => $date
        ]);
    }
    
    public function getEventById($id) {
        $sql = "SELECT * FROM events WHERE id = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    public function createEvent($data) {
        // Validate required fields
        if (empty($data['title']) || empty($data['start_date'])) {
            throw new Exception('Title and start date are required');
        }
        
        $countryId = isset($data['country_id']) && $data['country_id'] !== '' ? (int) $data['country_id'] : null;
        if (!$countryId) {
            throw new Exception('Country is required to create an event.');
        }
        
        // Get country color
        $color = '#007bff';
        if ($countryId) {
            $countryColor = $this->country->getCountryColor($countryId);
            if ($countryColor) {
                $color = $countryColor;
            }
        } elseif (!empty($data['color'])) {
            $color = $data['color'];
        }
        
        // Set default values
        $eventData = [
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? $data['start_date'],
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'all_day' => isset($data['all_day']) ? (bool)$data['all_day'] : false,
            'color' => $color,
            'category' => $data['category'] ?? 'general',
            'country_id' => $countryId,
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'active',
            'created_by' => isset($data['created_by']) ? (int)$data['created_by'] : null
        ];
        
        return $this->db->insert('events', $eventData);
    }
    
    public function updateEvent($id, $data) {
        // Remove id from data if present
        unset($data['id']);
        unset($data['created_by']);
        
        // Update color if country is changed
        if (!empty($data['country_id'])) {
            $countryColor = $this->country->getCountryColor($data['country_id']);
            if ($countryColor) {
                $data['color'] = $countryColor;
            }
        }
        
        // Add updated_at timestamp
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->update('events', $data, 'id = :id', ['id' => $id]);
    }
    
    public function deleteEvent($id) {
        // Soft delete by updating status
        return $this->db->update('events', ['status' => 'cancelled'], 'id = :id', ['id' => $id]);
    }
    
    public function hardDeleteEvent($id) {
        // Permanent delete
        return $this->db->delete('events', 'id = :id', ['id' => $id]);
    }
    
    public function getEventCategories() {
        $sql = "SELECT * FROM event_categories ORDER BY name";
        return $this->db->fetchAll($sql);
    }
    
    public function searchEvents($query) {
        $sql = "SELECT * FROM events 
                WHERE status = 'active' 
                AND (title LIKE :query_title OR description LIKE :query_desc)
                ORDER BY start_date, start_time";

        $searchQuery = '%' . $query . '%';
        return $this->db->fetchAll($sql, [
            'query_title' => $searchQuery,
            'query_desc' => $searchQuery
        ]);
    }
    
    public function getUpcomingEvents($limit = 5) {
        $limit = (int)$limit;
        $sql = "SELECT * FROM events 
                WHERE status = 'active' 
                AND start_date >= CURDATE()
                ORDER BY start_date, start_time
                LIMIT {$limit}";

        return $this->db->fetchAll($sql);
    }
    
    public function getEventStats() {
        $stats = [];
        
        // Total events
        $sql = "SELECT COUNT(*) as total FROM events WHERE status = 'active'";
        $stats['total'] = $this->db->fetch($sql)['total'];
        
        // Events this month
        $sql = "SELECT COUNT(*) as this_month FROM events 
                WHERE status = 'active' 
                AND YEAR(start_date) = YEAR(CURDATE()) 
                AND MONTH(start_date) = MONTH(CURDATE())";
        $stats['this_month'] = $this->db->fetch($sql)['this_month'];
        
        // Events by priority
        $sql = "SELECT priority, COUNT(*) as count FROM events 
                WHERE status = 'active' 
                GROUP BY priority";
        $priorityStats = $this->db->fetchAll($sql);
        foreach ($priorityStats as $stat) {
            $stats['priority'][$stat['priority']] = $stat['count'];
        }
        
        return $stats;
    }
    
    /**
     * Get all events with country information
     */
    public function getAllEventsWithCountry() {
        $sql = "SELECT e.*, c.name as country_name, c.color as country_color, c.code as country_code 
                FROM events e 
                LEFT JOIN countries c ON e.country_id = c.id 
                WHERE e.status = 'active' 
                ORDER BY e.start_date, e.start_time";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get events by month with country information
     */
    public function getEventsByMonthWithCountry($year, $month) {
        $sql = "SELECT e.*, c.name as country_name, c.color as country_color, c.code as country_code 
                FROM events e 
                LEFT JOIN countries c ON e.country_id = c.id 
                WHERE e.status = 'active' 
                AND (
                    (YEAR(e.start_date) = :year1 AND MONTH(e.start_date) = :month1)
                    OR (YEAR(e.end_date) = :year2 AND MONTH(e.end_date) = :month2)
                    OR (e.start_date <= :month_end AND e.end_date >= :month_start)
                )
                ORDER BY e.start_date, e.start_time";
        
        $monthStart = sprintf('%04d-%02d-01', $year, $month);
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        
        return $this->db->fetchAll($sql, [
            'year1' => $year,
            'month1' => $month,
            'year2' => $year,
            'month2' => $month,
            'month_start' => $monthStart,
            'month_end' => $monthEnd
        ]);
    }
    
    /**
     * Get event by ID with country information
     */
    public function getEventByIdWithCountry($id) {
        $sql = "SELECT e.*, c.name as country_name, c.color as country_color, c.code as country_code 
                FROM events e 
                LEFT JOIN countries c ON e.country_id = c.id 
                WHERE e.id = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    /**
     * Count events created by a specific user (all statuses)
     */
    public function getUserEventCount($userId) {
        $sql = "SELECT COUNT(*) AS total FROM events WHERE created_by = :uid";
        $row = $this->db->fetch($sql, ['uid' => $userId]);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Get Country instance for external use
     */
    public function getCountryManager() {
        return $this->country;
    }
}
?>
