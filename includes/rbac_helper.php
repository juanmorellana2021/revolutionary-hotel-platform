<?php
/**
 * Role-Based Access Control (RBAC) Helper Class
 * Manages user permissions and access control
 */

class RBACHelper {
    private $db;
    private $connection;
    private static $roleHierarchy = [
        'owner' => 100,
        'manager' => 90,
        'investor' => 50,  // High level but read-only
        'receptionist' => 40,
        'employee' => 20,
        'guest' => 10
    ];
    
    public function __construct() {
        $this->db = new Database();
        $this->connection = $this->db->getConnection();
    }
    
    /**
     * Check if current user has a specific permission
     */
    public function hasPermission($permissionName) {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            return false;
        }
        
        $role = $_SESSION['user_role'];
        $userId = $_SESSION['user_id'];
        
        // Owner and Manager have all permissions
        if ($role === 'owner' || $role === 'manager') {
            return true;
        }
        
        // Check if role has the specific permission
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as has_permission
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role = ? AND p.permission_name = ?
        ");
        $stmt->execute([$role, $permissionName]);
        $result = $stmt->fetch();
        
        return $result['has_permission'] > 0;
    }
    
    /**
     * Check if current user has ANY of the specified permissions
     */
    public function hasAnyPermission($permissions) {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if current user has ALL of the specified permissions
     */
    public function hasAllPermissions($permissions) {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Check if user can access a specific page
     */
    public function canAccessPage($pageName) {
        $pagePermissions = [
            'manager_dashboard.php' => ['view_dashboard'],
            'dashboard.php' => ['view_dashboard'],
            'calendar_view.php' => ['view_bookings'],
            'accounting_dashboard.php' => ['view_financials'],
            'expense_management.php' => ['manage_financials'],
            'income_management.php' => ['manage_financials'],
            'employee_management.php' => ['view_employees'],
            'payroll_management.php' => ['view_payroll'],
            'hotel_setup.php' => ['manage_settings'],
            'room_management.php' => ['manage_rooms'],
            'time_clock.php' => ['clock_in_out'],
            'my_hours.php' => ['view_own_hours'],
            'my_payroll.php' => ['view_own_payroll']
        ];
        
        if (!isset($pagePermissions[$pageName])) {
            return true; // Unknown pages are accessible by default
        }
        
        return $this->hasAnyPermission($pagePermissions[$pageName]);
    }
    
    /**
     * Redirect if user doesn't have permission
     */
    public function requirePermission($permission, $redirectUrl = 'index.php') {
        if (!$this->hasPermission($permission)) {
            header('Location: ' . $redirectUrl . '?error=access_denied');
            exit;
        }
    }
    
    /**
     * Redirect if user cannot access page
     */
    public function requirePageAccess($pageName, $redirectUrl = 'index.php') {
        if (!$this->canAccessPage($pageName)) {
            header('Location: ' . $redirectUrl . '?error=access_denied');
            exit;
        }
    }
    
    /**
     * Check if user has a role with equal or higher authority
     */
    public function hasRoleLevel($minimumRole) {
        if (!isset($_SESSION['user_role'])) {
            return false;
        }
        
        $userLevel = self::$roleHierarchy[$_SESSION['user_role']] ?? 0;
        $requiredLevel = self::$roleHierarchy[$minimumRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Get all permissions for a role
     */
    public function getRolePermissions($role) {
        $stmt = $this->connection->prepare("
            SELECT p.permission_name, p.permission_description
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role = ?
            ORDER BY p.permission_name
        ");
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get dashboard URL based on user role
     */
    public static function getDashboardForRole($role) {
        $dashboards = [
            'owner' => 'manager_dashboard.php',
            'manager' => 'manager_dashboard.php',
            'investor' => 'investor_dashboard.php',
            'receptionist' => 'receptionist_dashboard.php',
            'employee' => 'employee_dashboard.php',
            'guest' => 'guest_dashboard.php'
        ];
        
        return $dashboards[$role] ?? 'index.php';
    }
    
    /**
     * Check if current user can view another user's data
     */
    public function canViewUser($targetUserId) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Own data is always viewable
        if ($_SESSION['user_id'] == $targetUserId) {
            return true;
        }
        
        // Managers and owners can view all users
        if ($this->hasPermission('manage_users') || $this->hasPermission('view_employees')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if user can edit financial data (for investor read-only restriction)
     */
    public function canEditFinancials() {
        return $this->hasPermission('manage_financials');
    }
    
    /**
     * Get user-friendly role name
     */
    public static function getRoleName($role) {
        $names = [
            'owner' => 'Hotel Owner',
            'manager' => 'Manager',
            'investor' => 'Investor',
            'receptionist' => 'Receptionist',
            'employee' => 'Employee',
            'guest' => 'Guest'
        ];
        
        return $names[$role] ?? ucfirst($role);
    }
    
    /**
     * Get role badge color for UI
     */
    public static function getRoleBadgeColor($role) {
        $colors = [
            'owner' => 'danger',
            'manager' => 'primary',
            'investor' => 'info',
            'receptionist' => 'success',
            'employee' => 'warning',
            'guest' => 'secondary'
        ];
        
        return $colors[$role] ?? 'secondary';
    }
}
?>
