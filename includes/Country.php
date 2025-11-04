<?php
class Country {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all countries
     */
    public function getAllCountries() {
        $sql = "SELECT * FROM countries ORDER BY name";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get country by ID
     */
    public function getCountryById($id) {
        $sql = "SELECT * FROM countries WHERE id = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    /**
     * Get country by name
     */
    public function getCountryByName($name) {
        $sql = "SELECT * FROM countries WHERE name = :name";
        return $this->db->fetch($sql, ['name' => $name]);
    }
    
    /**
     * Get country color by ID
     */
    public function getCountryColor($countryId) {
        $sql = "SELECT color FROM countries WHERE id = :id";
        $result = $this->db->fetch($sql, ['id' => $countryId]);
        return $result ? $result['color'] : '#007bff'; // Default color if not found
    }
    
    /**
     * Get countries as options for dropdown
     */
    public function getCountriesForDropdown() {
        $countries = $this->getAllCountries();
        $options = [];
        foreach ($countries as $country) {
            $options[$country['id']] = $country['name'];
        }
        return $options;
    }
    
    /**
     * Get country statistics (event counts per country)
     */
    public function getCountryStatistics() {
        $sql = "SELECT c.id, c.name, c.color, COUNT(e.id) as event_count 
                FROM countries c 
                LEFT JOIN events e ON c.id = e.country_id AND e.status = 'active'
                GROUP BY c.id, c.name, c.color 
                ORDER BY c.name";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get events by country
     */
    public function getEventsByCountry($countryId, $startDate = null, $endDate = null) {
        $sql = "SELECT e.*, c.name as country_name, c.color as country_color 
                FROM events e 
                JOIN countries c ON e.country_id = c.id 
                WHERE e.country_id = :country_id AND e.status = 'active'";
        
        $params = ['country_id' => $countryId];
        
        if ($startDate && $endDate) {
            $sql .= " AND ((e.start_date BETWEEN :start_date AND :end_date) 
                         OR (e.end_date BETWEEN :start_date AND :end_date)
                         OR (e.start_date <= :start_date AND e.end_date >= :end_date))";
            $params['start_date'] = $startDate;
            $params['end_date'] = $endDate;
        }
        
        $sql .= " ORDER BY e.start_date, e.start_time";
        
        return $this->db->fetchAll($sql, $params);
    }
}
?>