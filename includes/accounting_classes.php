<?php
/**
 * Accounting Management Classes
 * Handles income, expenses, and financial reporting
 */

class IncomeManager {
    private $connection;
    
    public function __construct() {
        $db = new Database();
        $this->connection = $db->getConnection();
    }
    
    /**
     * Add income entry
     */
    public function addIncome($data) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO income (
                    booking_id, income_type, description, amount, payment_method,
                    payment_status, transaction_date, created_by, notes, receipt_number
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['booking_id'] ?? null,
                $data['income_type'],
                $data['description'],
                $data['amount'],
                $data['payment_method'] ?? 'cash',
                $data['payment_status'] ?? 'paid',
                $data['transaction_date'],
                $data['created_by'] ?? null,
                $data['notes'] ?? '',
                $data['receipt_number'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Income entry added successfully',
                'income_id' => $this->connection->lastInsertId()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to add income: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get income entries with filters
     */
    public function getIncome($filters = []) {
        $sql = "
            SELECT i.*, b.room_id, r.room_number, u.first_name, u.last_name
            FROM income i
            LEFT JOIN bookings b ON i.booking_id = b.id
            LEFT JOIN rooms r ON b.room_id = r.id
            LEFT JOIN users u ON i.created_by = u.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND i.transaction_date >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND i.transaction_date <= ?";
            $params[] = $filters['end_date'];
        }
        
        if (!empty($filters['income_type'])) {
            $sql .= " AND i.income_type = ?";
            $params[] = $filters['income_type'];
        }
        
        if (!empty($filters['payment_method'])) {
            $sql .= " AND i.payment_method = ?";
            $params[] = $filters['payment_method'];
        }
        
        if (!empty($filters['payment_status'])) {
            $sql .= " AND i.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        
        $sql .= " ORDER BY i.transaction_date DESC, i.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get income summary by type
     */
    public function getIncomeSummary($startDate, $endDate) {
        $stmt = $this->connection->prepare("
            SELECT 
                income_type,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount
            FROM income 
            WHERE transaction_date BETWEEN ? AND ?
            AND payment_status = 'paid'
            GROUP BY income_type
            ORDER BY total_amount DESC
        ");
        
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }
    
    /**
     * Update income entry
     */
    public function updateIncome($incomeId, $data) {
        try {
            $stmt = $this->connection->prepare("
                UPDATE income SET 
                    income_type = ?, description = ?, amount = ?, 
                    payment_method = ?, payment_status = ?, transaction_date = ?,
                    notes = ?, receipt_number = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['income_type'],
                $data['description'],
                $data['amount'],
                $data['payment_method'],
                $data['payment_status'],
                $data['transaction_date'],
                $data['notes'] ?? '',
                $data['receipt_number'] ?? null,
                $incomeId
            ]);
            
            return [
                'success' => true,
                'message' => 'Income entry updated successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update income: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete income entry
     */
    public function deleteIncome($incomeId) {
        try {
            $stmt = $this->connection->prepare("DELETE FROM income WHERE id = ?");
            $stmt->execute([$incomeId]);
            
            return [
                'success' => true,
                'message' => 'Income entry deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete income: ' . $e->getMessage()
            ];
        }
    }
}

class ExpenseManager {
    private $connection;
    
    public function __construct() {
        $db = new Database();
        $this->connection = $db->getConnection();
    }
    
    /**
     * Add expense entry
     */
    public function addExpense($data) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO expenses (
                    expense_category, description, amount, payment_method, vendor_name,
                    invoice_number, expense_date, is_recurring, recurring_frequency,
                    next_due_date, paid_by, status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['expense_category'],
                $data['description'],
                $data['amount'],
                $data['payment_method'] ?? 'cash',
                $data['vendor_name'] ?? '',
                $data['invoice_number'] ?? '',
                $data['expense_date'],
                $data['is_recurring'] ?? false,
                $data['recurring_frequency'] ?? null,
                $data['next_due_date'] ?? null,
                $data['paid_by'] ?? null,
                $data['status'] ?? 'paid',
                $data['notes'] ?? ''
            ]);
            
            return [
                'success' => true,
                'message' => 'Expense entry added successfully',
                'expense_id' => $this->connection->lastInsertId()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to add expense: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get expense entries with filters
     */
    public function getExpenses($filters = []) {
        $sql = "
            SELECT e.*, u1.first_name as paid_by_name, u1.last_name as paid_by_lastname,
                   u2.first_name as approved_by_name, u2.last_name as approved_by_lastname
            FROM expenses e
            LEFT JOIN users u1 ON e.paid_by = u1.id
            LEFT JOIN users u2 ON e.approved_by = u2.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND e.expense_date >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND e.expense_date <= ?";
            $params[] = $filters['end_date'];
        }
        
        if (!empty($filters['expense_category'])) {
            $sql .= " AND e.expense_category = ?";
            $params[] = $filters['expense_category'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY e.expense_date DESC, e.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get expense summary by category
     */
    public function getExpenseSummary($startDate, $endDate) {
        $stmt = $this->connection->prepare("
            SELECT 
                expense_category,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount
            FROM expenses 
            WHERE expense_date BETWEEN ? AND ?
            AND status = 'paid'
            GROUP BY expense_category
            ORDER BY total_amount DESC
        ");
        
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll();
    }
    
    /**
     * Update expense entry
     */
    public function updateExpense($expenseId, $data) {
        try {
            $stmt = $this->connection->prepare("
                UPDATE expenses SET 
                    expense_category = ?, description = ?, amount = ?, 
                    payment_method = ?, vendor_name = ?, invoice_number = ?,
                    expense_date = ?, status = ?, notes = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['expense_category'],
                $data['description'],
                $data['amount'],
                $data['payment_method'],
                $data['vendor_name'],
                $data['invoice_number'],
                $data['expense_date'],
                $data['status'],
                $data['notes'] ?? '',
                $expenseId
            ]);
            
            return [
                'success' => true,
                'message' => 'Expense entry updated successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update expense: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete expense entry
     */
    public function deleteExpense($expenseId) {
        try {
            $stmt = $this->connection->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$expenseId]);
            
            return [
                'success' => true,
                'message' => 'Expense entry deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete expense: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get expense summary by category
     */
    public function getCategorySummary($filters = []) {
        $sql = "
            SELECT 
                expense_category,
                COUNT(*) as count,
                SUM(amount) as total
            FROM expenses 
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND expense_date >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND expense_date <= ?";
            $params[] = $filters['end_date'];
        }
        
        $sql .= " GROUP BY expense_category ORDER BY total DESC";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
}

class FinancialReportManager {
    private $connection;
    private $incomeManager;
    private $expenseManager;
    
    public function __construct() {
        $db = new Database();
        $this->connection = $db->getConnection();
        $this->incomeManager = new IncomeManager();
        $this->expenseManager = new ExpenseManager();
    }
    
    /**
     * Generate financial report for date range
     */
    public function generateReport($startDate, $endDate, $reportType = 'custom') {
        // Get total income
        $stmt = $this->connection->prepare("
            SELECT SUM(amount) as total FROM income 
            WHERE transaction_date BETWEEN ? AND ? AND payment_status = 'paid'
        ");
        $stmt->execute([$startDate, $endDate]);
        $totalIncome = $stmt->fetch()['total'] ?? 0;
        
        // Get total expenses
        $stmt = $this->connection->prepare("
            SELECT SUM(amount) as total FROM expenses 
            WHERE expense_date BETWEEN ? AND ? AND status = 'paid'
        ");
        $stmt->execute([$startDate, $endDate]);
        $totalExpenses = $stmt->fetch()['total'] ?? 0;
        
        // Get room revenue specifically
        $stmt = $this->connection->prepare("
            SELECT SUM(amount) as total FROM income 
            WHERE transaction_date BETWEEN ? AND ? 
            AND payment_status = 'paid' 
            AND income_type IN ('room_booking', 'extra_bed')
        ");
        $stmt->execute([$startDate, $endDate]);
        $roomRevenue = $stmt->fetch()['total'] ?? 0;
        
        // Get extra services revenue
        $stmt = $this->connection->prepare("
            SELECT SUM(amount) as total FROM income 
            WHERE transaction_date BETWEEN ? AND ? 
            AND payment_status = 'paid' 
            AND income_type NOT IN ('room_booking', 'extra_bed')
        ");
        $stmt->execute([$startDate, $endDate]);
        $extraServicesRevenue = $stmt->fetch()['total'] ?? 0;
        
        // Calculate occupancy rate (simplified)
        $stmt = $this->connection->prepare("
            SELECT COUNT(DISTINCT DATE(check_in_date)) as occupied_days,
                   COUNT(DISTINCT room_id) as rooms_used
            FROM bookings 
            WHERE check_in_date BETWEEN ? AND ?
            AND status != 'cancelled'
        ");
        $stmt->execute([$startDate, $endDate]);
        $occupancyData = $stmt->fetch();
        
        $totalRooms = $this->getTotalRooms();
        $daysDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24) + 1;
        $occupancyRate = $totalRooms > 0 ? ($occupancyData['occupied_days'] / ($totalRooms * $daysDiff)) * 100 : 0;
        
        // Calculate average daily rate
        $avgDailyRate = $occupancyData['occupied_days'] > 0 ? $roomRevenue / $occupancyData['occupied_days'] : 0;
        
        $netProfit = $totalIncome - $totalExpenses;
        
        // Get detailed breakdowns
        $incomeBreakdown = $this->incomeManager->getIncomeSummary($startDate, $endDate);
        $expenseBreakdown = $this->expenseManager->getExpenseSummary($startDate, $endDate);
        
        $reportData = [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'income_breakdown' => $incomeBreakdown,
            'expense_breakdown' => $expenseBreakdown,
            'occupancy_details' => $occupancyData
        ];
        
        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'room_revenue' => $roomRevenue,
            'extra_services_revenue' => $extraServicesRevenue,
            'occupancy_rate' => round($occupancyRate, 2),
            'average_daily_rate' => round($avgDailyRate, 2),
            'profit_margin' => $totalIncome > 0 ? round(($netProfit / $totalIncome) * 100, 2) : 0,
            'report_data' => $reportData
        ];
    }
    
    /**
     * Get total number of rooms
     */
    private function getTotalRooms() {
        $stmt = $this->connection->prepare("SELECT COUNT(*) as total FROM rooms");
        $stmt->execute();
        return $stmt->fetch()['total'] ?? 0;
    }
    
    /**
     * Save report to database
     */
    public function saveReport($reportData, $reportType = 'custom', $generatedBy = null) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO financial_reports (
                    report_type, report_date, start_date, end_date,
                    total_income, total_expenses, net_profit, room_revenue,
                    extra_services_revenue, occupancy_rate, average_daily_rate,
                    report_data, generated_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    total_income = VALUES(total_income),
                    total_expenses = VALUES(total_expenses),
                    net_profit = VALUES(net_profit),
                    room_revenue = VALUES(room_revenue),
                    extra_services_revenue = VALUES(extra_services_revenue),
                    occupancy_rate = VALUES(occupancy_rate),
                    average_daily_rate = VALUES(average_daily_rate),
                    report_data = VALUES(report_data),
                    generated_by = VALUES(generated_by)
            ");
            
            $stmt->execute([
                $reportType,
                date('Y-m-d'),
                $reportData['start_date'],
                $reportData['end_date'],
                $reportData['total_income'],
                $reportData['total_expenses'],
                $reportData['net_profit'],
                $reportData['room_revenue'],
                $reportData['extra_services_revenue'],
                $reportData['occupancy_rate'],
                $reportData['average_daily_rate'],
                json_encode($reportData['report_data']),
                $generatedBy
            ]);
            
            return ['success' => true, 'message' => 'Report saved successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to save report: ' . $e->getMessage()];
        }
    }
}
?>