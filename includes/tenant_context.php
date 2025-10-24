<?php
/**
 * Tenant Context System for AINI.com Multi-Tenant Platform
 * 
 * Handles hotel_id isolation for multi-tenant queries
 * Ensures data security by filtering all queries by current hotel context
 * 
 * @package AINI.com
 * @version 1.0
 */

class TenantContext {
    private static $hotel_id = null;
    private static $hotel_data = null;
    private static $bypass_filter = false;
    
    /**
     * Set the current hotel context
     * Call this after user login or hotel selection
     * 
     * @param int $hotel_id The hotel ID to set as current context
     * @return bool Success
     */
    public static function setHotel($hotel_id) {
        if (!is_numeric($hotel_id) || $hotel_id <= 0) {
            error_log("TenantContext: Invalid hotel_id attempted: " . var_export($hotel_id, true));
            return false;
        }
        
        self::$hotel_id = (int)$hotel_id;
        $_SESSION['current_hotel_id'] = self::$hotel_id;
        
        // Load hotel data for quick access
        self::loadHotelData();
        
        error_log("TenantContext: Set hotel_id to {$hotel_id}");
        return true;
    }
    
    /**
     * Get the current hotel ID from context
     * 
     * @return int|null Current hotel ID or null if not set
     */
    public static function getHotelId() {
        // Return cached value if set
        if (self::$hotel_id !== null) {
            return self::$hotel_id;
        }
        
        // Try to restore from session
        if (isset($_SESSION['current_hotel_id'])) {
            self::$hotel_id = (int)$_SESSION['current_hotel_id'];
            self::loadHotelData();
            return self::$hotel_id;
        }
        
        // Try to determine from logged-in user
        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
            // If user has a hotel_id in their profile, use it
            if (isset($_SESSION['hotel_id']) && $_SESSION['hotel_id'] > 0) {
                self::setHotel($_SESSION['hotel_id']);
                return self::$hotel_id;
            }
        }
        
        return null;
    }
    
    /**
     * Require a hotel context to be set
     * Redirects to hotel selection page if not set
     * 
     * @param string $redirect_url URL to redirect if no hotel context
     */
    public static function requireHotel($redirect_url = '/select-hotel.php') {
        if (self::getHotelId() === null) {
            error_log("TenantContext: Hotel context required but not set, redirecting to {$redirect_url}");
            header("Location: {$redirect_url}");
            exit;
        }
    }
    
    /**
     * Check if user has access to a specific hotel
     * 
     * @param int $hotel_id Hotel ID to check
     * @return bool True if user has access
     */
    public static function hasAccessToHotel($hotel_id) {
        // Platform admins have access to all hotels
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'owner' && !isset($_SESSION['hotel_id'])) {
            return true; // Platform owner (no hotel_id) can access all
        }
        
        // Check if hotel_id matches current user's hotel
        if (isset($_SESSION['hotel_id'])) {
            return $_SESSION['hotel_id'] == $hotel_id;
        }
        
        return false;
    }
    
    /**
     * Get current hotel data (cached)
     * 
     * @return array|null Hotel data or null
     */
    public static function getHotelData() {
        if (self::$hotel_data === null) {
            self::loadHotelData();
        }
        return self::$hotel_data;
    }
    
    /**
     * Load hotel data from database (private)
     */
    private static function loadHotelData() {
        if (self::$hotel_id === null) {
            return;
        }
        
        try {
            require_once __DIR__ . '/classes.php';
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                SELECT 
                    id, hotel_name, slug, city, region, star_rating,
                    amenities, currency, timezone, commission_rate, status
                FROM hotels 
                WHERE id = ?
            ");
            $stmt->bind_param("i", self::$hotel_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                self::$hotel_data = $result->fetch_assoc();
                
                // Decode JSON fields
                if (self::$hotel_data['amenities']) {
                    self::$hotel_data['amenities'] = json_decode(self::$hotel_data['amenities'], true);
                }
            }
        } catch (Exception $e) {
            error_log("TenantContext: Error loading hotel data: " . $e->getMessage());
        }
    }
    
    /**
     * Add hotel_id filter to WHERE clause
     * Auto-injects hotel_id condition to queries
     * 
     * @param string $where_clause Existing WHERE clause (without WHERE keyword)
     * @param string $table_alias Optional table alias (e.g., 'b' for bookings b)
     * @return string Modified WHERE clause
     */
    public static function addHotelFilter($where_clause = '1=1', $table_alias = '') {
        $hotel_id = self::getHotelId();
        
        // If no hotel context or bypass is enabled, return original
        if ($hotel_id === null || self::$bypass_filter) {
            return $where_clause;
        }
        
        $prefix = $table_alias ? $table_alias . '.' : '';
        $filter = "{$prefix}hotel_id = {$hotel_id}";
        
        // Add to existing WHERE clause
        if (empty($where_clause) || $where_clause === '1=1') {
            return $filter;
        } else {
            return "({$where_clause}) AND {$filter}";
        }
    }
    
    /**
     * Wrap a query to automatically add hotel_id filter
     * 
     * @param string $query SQL query
     * @param string $table_name Main table name to filter
     * @return string Modified query
     */
    public static function filterQuery($query, $table_name = null) {
        $hotel_id = self::getHotelId();
        
        // If no hotel context or bypass enabled, return original
        if ($hotel_id === null || self::$bypass_filter) {
            return $query;
        }
        
        // Simple injection of hotel_id filter
        // This is a basic implementation - for production, use prepared statements
        
        // If WHERE clause exists, add AND condition
        if (stripos($query, 'WHERE') !== false) {
            $query = str_ireplace('WHERE', "WHERE hotel_id = {$hotel_id} AND", $query);
        } else {
            // Add WHERE clause before ORDER BY, GROUP BY, or LIMIT
            $keywords = ['ORDER BY', 'GROUP BY', 'LIMIT', 'HAVING'];
            $inserted = false;
            
            foreach ($keywords as $keyword) {
                if (stripos($query, $keyword) !== false) {
                    $query = str_ireplace($keyword, "WHERE hotel_id = {$hotel_id} {$keyword}", $query);
                    $inserted = true;
                    break;
                }
            }
            
            // If no keywords found, append at end
            if (!$inserted) {
                $query = rtrim($query, ';') . " WHERE hotel_id = {$hotel_id}";
            }
        }
        
        return $query;
    }
    
    /**
     * Temporarily bypass tenant filtering
     * USE WITH EXTREME CAUTION - Only for platform admin functions
     * 
     * @param callable $callback Function to execute without filtering
     * @return mixed Result of callback
     */
    public static function withoutTenantFilter($callback) {
        $original_state = self::$bypass_filter;
        self::$bypass_filter = true;
        
        try {
            $result = $callback();
        } finally {
            self::$bypass_filter = $original_state;
        }
        
        return $result;
    }
    
    /**
     * Clear hotel context (logout)
     */
    public static function clear() {
        self::$hotel_id = null;
        self::$hotel_data = null;
        unset($_SESSION['current_hotel_id']);
        error_log("TenantContext: Cleared hotel context");
    }
    
    /**
     * Get hotel name for display
     * 
     * @return string Hotel name or empty string
     */
    public static function getHotelName() {
        $data = self::getHotelData();
        return $data ? $data['hotel_name'] : '';
    }
    
    /**
     * Get hotel slug for URLs
     * 
     * @return string Hotel slug or empty string
     */
    public static function getHotelSlug() {
        $data = self::getHotelData();
        return $data ? $data['slug'] : '';
    }
    
    /**
     * Check if current hotel is active
     * 
     * @return bool True if active
     */
    public static function isHotelActive() {
        $data = self::getHotelData();
        return $data && $data['status'] === 'active';
    }
    
    /**
     * Get commission rate for current hotel
     * 
     * @return float Commission rate (e.g., 15.00 for 15%)
     */
    public static function getCommissionRate() {
        $data = self::getHotelData();
        return $data ? (float)$data['commission_rate'] : 15.00;
    }
    
    /**
     * Debug: Get current state
     * 
     * @return array Debug information
     */
    public static function getDebugInfo() {
        return [
            'hotel_id' => self::$hotel_id,
            'bypass_filter' => self::$bypass_filter,
            'session_hotel_id' => $_SESSION['current_hotel_id'] ?? null,
            'hotel_loaded' => self::$hotel_data !== null,
            'hotel_name' => self::getHotelName()
        ];
    }
}

/**
 * TenantDatabase Class - Extends Database with tenant-aware methods
 */
class TenantDatabase {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Execute a tenant-filtered SELECT query
     * 
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return mysqli_result|false
     */
    public function query($query, $params = []) {
        // Add hotel_id filter automatically
        $query = TenantContext::filterQuery($query);
        
        if (empty($params)) {
            return $this->db->query($query);
        }
        
        // Prepared statement
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("TenantDatabase: Prepare failed: " . $this->db->error);
            return false;
        }
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params)); // Default to strings
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }
    
    /**
     * Insert with automatic hotel_id
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int|false Insert ID or false
     */
    public function insert($table, $data) {
        // Add hotel_id automatically
        $hotel_id = TenantContext::getHotelId();
        if ($hotel_id !== null) {
            $data['hotel_id'] = $hotel_id;
        }
        
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        
        $query = "INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES ({$placeholders})";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("TenantDatabase: Insert prepare failed: " . $this->db->error);
            return false;
        }
        
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        
        error_log("TenantDatabase: Insert failed: " . $stmt->error);
        return false;
    }
    
    /**
     * Update with automatic hotel_id filter
     * 
     * @param string $table Table name
     * @param array $data Data to update
     * @param string $where WHERE clause (without hotel_id, it's added automatically)
     * @param array $where_params Parameters for WHERE clause
     * @return bool Success
     */
    public function update($table, $data, $where, $where_params = []) {
        // Add hotel_id to WHERE
        $where = TenantContext::addHotelFilter($where);
        
        $set_clause = [];
        foreach (array_keys($data) as $column) {
            $set_clause[] = "{$column} = ?";
        }
        
        $query = "UPDATE {$table} SET " . implode(', ', $set_clause) . " WHERE {$where}";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("TenantDatabase: Update prepare failed: " . $this->db->error);
            return false;
        }
        
        $params = array_merge(array_values($data), $where_params);
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
        
        return $stmt->execute();
    }
    
    /**
     * Delete with automatic hotel_id filter
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $where_params Parameters
     * @return bool Success
     */
    public function delete($table, $where, $where_params = []) {
        // Add hotel_id to WHERE for safety
        $where = TenantContext::addHotelFilter($where);
        
        $query = "DELETE FROM {$table} WHERE {$where}";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("TenantDatabase: Delete prepare failed: " . $this->db->error);
            return false;
        }
        
        if (!empty($where_params)) {
            $types = str_repeat('s', count($where_params));
            $stmt->bind_param($types, ...$where_params);
        }
        
        return $stmt->execute();
    }
}
