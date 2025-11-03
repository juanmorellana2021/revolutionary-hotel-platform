<?php
/**
 * Intelligent Cache Manager for Multi-Hotel Platform
 * Optimizes performance across multiple properties and user sessions
 */

class CacheManager {
    private static $instance = null;
    private $cachePrefix = 'pms_cache_';
    private $defaultTTL = 300; // 5 minutes
    
    private function __construct() {
        // Singleton pattern
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get cached data for specific hotel
     */
    public function get($key, $hotelId = null) {
        $cacheKey = $this->buildKey($key, $hotelId);
        
        if (!isset($_SESSION[$cacheKey])) {
            return null;
        }
        
        $cached = $_SESSION[$cacheKey];
        
        // Check if expired
        if (isset($cached['expires']) && time() > $cached['expires']) {
            unset($_SESSION[$cacheKey]);
            return null;
        }
        
        return $cached['data'] ?? null;
    }
    
    /**
     * Set cached data with TTL
     */
    public function set($key, $data, $hotelId = null, $ttl = null) {
        $cacheKey = $this->buildKey($key, $hotelId);
        $ttl = $ttl ?? $this->defaultTTL;
        
        $_SESSION[$cacheKey] = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return true;
    }
    
    /**
     * Invalidate cache for specific hotel
     */
    public function invalidate($key, $hotelId = null) {
        $cacheKey = $this->buildKey($key, $hotelId);
        unset($_SESSION[$cacheKey]);
    }
    
    /**
     * Invalidate ALL caches for a hotel (when booking changes)
     */
    public function invalidateHotel($hotelId) {
        $pattern = $this->cachePrefix . 'hotel_' . $hotelId;
        
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, $pattern) === 0) {
                unset($_SESSION[$key]);
            }
        }
    }
    
    /**
     * Clear all expired caches
     */
    public function clearExpired() {
        $now = time();
        $cleared = 0;
        
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, $this->cachePrefix) === 0) {
                if (isset($value['expires']) && $now > $value['expires']) {
                    unset($_SESSION[$key]);
                    $cleared++;
                }
            }
        }
        
        return $cleared;
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $total = 0;
        $expired = 0;
        $valid = 0;
        $totalSize = 0;
        $now = time();
        
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, $this->cachePrefix) === 0) {
                $total++;
                $totalSize += strlen(serialize($value));
                
                if (isset($value['expires'])) {
                    if ($now > $value['expires']) {
                        $expired++;
                    } else {
                        $valid++;
                    }
                }
            }
        }
        
        return [
            'total_caches' => $total,
            'valid' => $valid,
            'expired' => $expired,
            'total_size_bytes' => $totalSize,
            'total_size_kb' => round($totalSize / 1024, 2)
        ];
    }
    
    /**
     * Build cache key
     */
    private function buildKey($key, $hotelId = null) {
        if ($hotelId !== null) {
            return $this->cachePrefix . 'hotel_' . $hotelId . '_' . $key;
        }
        return $this->cachePrefix . $key;
    }
}

/**
 * Hook into booking events to auto-invalidate cache
 */
class CacheEventHooks {
    
    /**
     * Call this after creating/updating/canceling a booking
     */
    public static function onBookingChange($hotelId) {
        $cache = CacheManager::getInstance();
        $cache->invalidateHotel($hotelId);
    }
    
    /**
     * Call this after adding/editing/deleting a room
     */
    public static function onRoomChange($hotelId) {
        $cache = CacheManager::getInstance();
        $cache->invalidateHotel($hotelId);
    }
    
    /**
     * Call this on user login to clear old caches
     */
    public static function onUserLogin() {
        $cache = CacheManager::getInstance();
        return $cache->clearExpired();
    }
}
?>
