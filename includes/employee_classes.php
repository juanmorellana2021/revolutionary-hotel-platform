<?php

class EmployeeManager {
    private $connection;
    
    public function __construct() {
        $db = new Database();
        $this->connection = $db->getConnection();
    }
    
    /**
     * Add new employee
     */
    public function addEmployee($data) {
        try {
            // Convert empty email to NULL to avoid duplicate empty string conflicts
            $email = !empty($data['email']) ? $data['email'] : null;
            
            $stmt = $this->connection->prepare("
                INSERT INTO employees (
                    employee_id, first_name, last_name, email, phone, position, department, 
                    hire_date, hourly_rate, hourly_rate_currency, overtime_rate, overtime_rate_currency, 
                    weekly_hours, salary_type, monthly_salary, emergency_contact_name, emergency_contact_phone, 
                    address, tax_id, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['employee_id'],
                $data['first_name'],
                $data['last_name'],
                $email,
                $data['phone'],
                $data['position'],
                $data['department'],
                $data['hire_date'],
                $data['hourly_rate'],
                $data['hourly_rate_currency'] ?? 'USD',
                $data['overtime_rate'] ?? ($data['hourly_rate'] * 1.5),
                $data['overtime_rate_currency'] ?? 'USD',
                $data['weekly_hours'] ?? 40,
                $data['salary_type'] ?? 'hourly',
                $data['monthly_salary'] ?? null,
                $data['emergency_contact_name'] ?? '',
                $data['emergency_contact_phone'] ?? '',
                $data['address'] ?? '',
                $data['tax_id'] ?? '',
                $data['notes'] ?? ''
            ]);
            
            return [
                'success' => true,
                'message' => 'Employee added successfully',
                'employee_id' => $this->connection->lastInsertId()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to add employee: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get employees with filters
     */
    public function getEmployees($filters = []) {
        $sql = "
            SELECT e.*, u.first_name as user_first_name, u.last_name as user_last_name, u.email as user_email,
                   COALESCE(e.hourly_rate_currency, 'USD') as hourly_rate_currency,
                   COALESCE(e.overtime_rate_currency, 'USD') as overtime_rate_currency
            FROM employees e
            LEFT JOIN users u ON e.user_id = u.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['department'])) {
            $sql .= " AND e.department = ?";
            $params[] = $filters['department'];
        }
        
        if (!empty($filters['position'])) {
            $sql .= " AND e.position = ?";
            $params[] = $filters['position'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY e.last_name, e.first_name";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . intval($filters['offset']);
            }
        }
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get employee by ID
     */
    public function getEmployeeById($employeeId) {
        $stmt = $this->connection->prepare("
            SELECT *, 
                   COALESCE(hourly_rate_currency, 'USD') as hourly_rate_currency,
                   COALESCE(overtime_rate_currency, 'USD') as overtime_rate_currency
            FROM employees 
            WHERE id = ?
        ");
        $stmt->execute([$employeeId]);
        return $stmt->fetch();
    }
    
    /**
     * Update employee
     */
    public function updateEmployee($employeeId, $data) {
        try {
            // Convert empty email to NULL to avoid duplicate empty string conflicts
            $email = !empty($data['email']) ? $data['email'] : null;
            
            $stmt = $this->connection->prepare("
                UPDATE employees SET 
                    first_name = ?, last_name = ?, email = ?, phone = ?, position = ?, 
                    department = ?, hourly_rate = ?, hourly_rate_currency = ?, overtime_rate = ?, 
                    overtime_rate_currency = ?, weekly_hours = ?, salary_type = ?, monthly_salary = ?, 
                    status = ?, emergency_contact_name = ?, emergency_contact_phone = ?, 
                    address = ?, tax_id = ?, notes = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $email,
                $data['phone'],
                $data['position'],
                $data['department'],
                $data['hourly_rate'],
                $data['hourly_rate_currency'] ?? 'USD',
                $data['overtime_rate'],
                $data['overtime_rate_currency'] ?? 'USD',
                $data['weekly_hours'],
                $data['salary_type'],
                $data['monthly_salary'],
                $data['status'] ?? 'active',
                $data['emergency_contact_name'],
                $data['emergency_contact_phone'],
                $data['address'],
                $data['tax_id'],
                $data['notes'],
                $employeeId
            ]);
            
            return [
                'success' => true,
                'message' => 'Employee updated successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update employee: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete employee
     */
    public function deleteEmployee($employeeId) {
        try {
            $stmt = $this->connection->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$employeeId]);
            
            return [
                'success' => true,
                'message' => 'Employee deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete employee: ' . $e->getMessage()
            ];
        }
    }
}

class TimeClockManager {
    private $connection;
    
    public function __construct() {
        $db = new Database();
        $this->connection = $db->getConnection();
    }
    
    /**
     * Clock in employee
     */
    public function clockIn($employeeId, $location = 'hotel', $notes = '') {
        try {
            // Check if employee is already clocked in
            $stmt = $this->connection->prepare("
                SELECT id FROM time_clock 
                WHERE employee_id = ? AND clock_out IS NULL
            ");
            $stmt->execute([$employeeId]);
            
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Employee is already clocked in'
                ];
            }
            
            $stmt = $this->connection->prepare("
                INSERT INTO time_clock (employee_id, clock_in, location, notes, status) 
                VALUES (?, NOW(), ?, ?, 'clocked_in')
            ");
            
            $stmt->execute([$employeeId, $location, $notes]);
            
            return [
                'success' => true,
                'message' => 'Successfully clocked in',
                'clock_id' => $this->connection->lastInsertId()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to clock in: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Clock out employee
     */
    public function clockOut($employeeId, $notes = '') {
        try {
            // Get active clock entry
            $stmt = $this->connection->prepare("
                SELECT id, clock_in, total_break_minutes 
                FROM time_clock 
                WHERE employee_id = ? AND clock_out IS NULL
                ORDER BY clock_in DESC LIMIT 1
            ");
            $stmt->execute([$employeeId]);
            $clockEntry = $stmt->fetch();
            
            if (!$clockEntry) {
                return [
                    'success' => false,
                    'message' => 'No active clock in found for this employee'
                ];
            }
            
            // Calculate total hours
            $clockIn = new DateTime($clockEntry['clock_in']);
            $clockOut = new DateTime();
            $interval = $clockIn->diff($clockOut);
            $totalMinutes = ($interval->h * 60) + $interval->i;
            $breakMinutes = $clockEntry['total_break_minutes'] ?? 0;
            $workMinutes = $totalMinutes - $breakMinutes;
            $totalHours = round($workMinutes / 60, 2);
            
            // Calculate overtime (over 8 hours per day)
            $overtimeHours = max(0, $totalHours - 8);
            $regularHours = $totalHours - $overtimeHours;
            
            $stmt = $this->connection->prepare("
                UPDATE time_clock SET 
                    clock_out = NOW(), 
                    total_hours = ?, 
                    overtime_hours = ?,
                    notes = CONCAT(COALESCE(notes, ''), ?),
                    status = 'completed'
                WHERE id = ?
            ");
            
            $additionalNotes = $notes ? "\nClock out: " . $notes : '';
            $stmt->execute([$totalHours, $overtimeHours, $additionalNotes, $clockEntry['id']]);
            
            return [
                'success' => true,
                'message' => 'Successfully clocked out',
                'total_hours' => $totalHours,
                'overtime_hours' => $overtimeHours
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to clock out: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Start break
     */
    public function startBreak($employeeId) {
        try {
            $stmt = $this->connection->prepare("
                UPDATE time_clock SET break_start = NOW() 
                WHERE employee_id = ? AND status = 'active' AND clock_out IS NULL
                AND break_start IS NULL
            ");
            
            $stmt->execute([$employeeId]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Break started'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Unable to start break. Employee may not be clocked in or already on break.'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to start break: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * End break
     */
    public function endBreak($employeeId) {
        try {
            // Get the break start time
            $stmt = $this->connection->prepare("
                SELECT break_start, total_break_minutes 
                FROM time_clock 
                WHERE employee_id = ? AND status = 'active' AND clock_out IS NULL
                AND break_start IS NOT NULL AND break_end IS NULL
            ");
            $stmt->execute([$employeeId]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'No active break found for this employee'
                ];
            }
            
            // Calculate break duration
            $breakStart = new DateTime($result['break_start']);
            $breakEnd = new DateTime();
            $breakInterval = $breakStart->diff($breakEnd);
            $breakMinutes = ($breakInterval->h * 60) + $breakInterval->i;
            $totalBreakMinutes = ($result['total_break_minutes'] ?? 0) + $breakMinutes;
            
            $stmt = $this->connection->prepare("
                UPDATE time_clock SET 
                    break_end = NOW(), 
                    total_break_minutes = ?
                WHERE employee_id = ? AND status = 'active' AND clock_out IS NULL
            ");
            
            $stmt->execute([$totalBreakMinutes, $employeeId]);
            
            return [
                'success' => true,
                'message' => 'Break ended',
                'break_duration' => $breakMinutes
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to end break: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Add manual time entry (Manager only)
     */
    public function addManualEntry($employeeId, $clockIn, $clockOut = null, $breakMinutes = 0, $notes = '', $createdBy = null) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO time_clock (
                    employee_id, clock_in, clock_out, total_break_minutes, notes, 
                    status, created_by, created_at, is_manual_entry
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 1)
            ");
            
            $status = $clockOut ? 'completed' : 'active';
            
            $stmt->execute([
                $employeeId,
                $clockIn,
                $clockOut,
                $breakMinutes > 0 ? $breakMinutes : null,
                $notes,
                $status,
                $createdBy
            ]);
            
            return [
                'success' => true,
                'message' => 'Manual time entry added successfully',
                'entry_id' => $this->connection->lastInsertId()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to add manual entry: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get time clock entries with filters
     */
    public function getTimeEntries($filters = []) {
        $sql = "
            SELECT tc.*, e.first_name, e.last_name, e.employee_id, e.hourly_rate, e.overtime_rate
            FROM time_clock tc
            JOIN employees e ON tc.employee_id = e.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['employee_id'])) {
            $sql .= " AND tc.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(tc.clock_in) >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(tc.clock_in) <= ?";
            $params[] = $filters['end_date'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND tc.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY tc.clock_in DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . intval($filters['offset']);
            }
        }
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        
        $entries = $stmt->fetchAll();
        
        // Calculate total hours for each entry
        foreach ($entries as &$entry) {
            if ($entry['clock_in'] && $entry['clock_out']) {
                try {
                    $clockIn = new DateTime($entry['clock_in']);
                    $clockOut = new DateTime($entry['clock_out']);
                    
                    // Calculate total minutes worked
                    $totalMinutes = ($clockOut->getTimestamp() - $clockIn->getTimestamp()) / 60;
                    
                    // Handle negative values (clock_out before clock_in - likely date parsing issue)
                    if ($totalMinutes < 0) {
                        // If same day and clock_out time is earlier, it's likely 0 minutes worked
                        if (date('Y-m-d', strtotime($entry['clock_in'])) === date('Y-m-d', strtotime($entry['clock_out']))) {
                            $totalMinutes = 0;
                        } else {
                            // Clock out is next day
                            $totalMinutes = abs($totalMinutes);
                        }
                    }
                    
                    // Subtract break minutes
                    $breakMinutes = $entry['total_break_minutes'] ?? 0;
                    $totalMinutes = max(0, $totalMinutes - $breakMinutes);
                    
                    // Convert to hours
                    $totalHours = $totalMinutes / 60;
                    
                    // Ensure reasonable maximum (24 hours per entry)
                    if ($totalHours > 24) {
                        $totalHours = 0;
                    }
                    
                    // Calculate overtime (over 8 hours)
                    $regularHours = min($totalHours, 8);
                    $overtimeHours = max(0, $totalHours - 8);
                    
                    $entry['total_hours'] = $totalHours;
                    $entry['regular_hours'] = $regularHours;
                    $entry['overtime_hours'] = $overtimeHours;
                    
                } catch (Exception $e) {
                    // If date parsing fails, set to 0
                    $entry['total_hours'] = 0;
                    $entry['regular_hours'] = 0;
                    $entry['overtime_hours'] = 0;
                }
            } else {
                $entry['total_hours'] = null;
                $entry['regular_hours'] = 0;
                $entry['overtime_hours'] = 0;
            }
        }
        
        return $entries;
    }
    
    /**
     * Get current status of employee
     */
    public function getEmployeeStatus($employeeId) {
        $stmt = $this->connection->prepare("
            SELECT *, 
                CASE 
                    WHEN break_start IS NOT NULL AND break_end IS NULL THEN 'on_break'
                    WHEN clock_out IS NULL THEN 'clocked_in'
                    ELSE 'clocked_out'
                END as current_status
            FROM time_clock 
            WHERE employee_id = ? AND status = 'active' AND clock_out IS NULL
            ORDER BY clock_in DESC LIMIT 1
        ");
        $stmt->execute([$employeeId]);
        return $stmt->fetch();
    }

    /**
     * Get currently working employees
     */
    public function getCurrentlyWorking() {
        $sql = "
            SELECT 
                e.id,
                e.first_name,
                e.last_name,
                e.position,
                tc.clock_in,
                CASE 
                    WHEN tc.break_start IS NOT NULL AND tc.break_end IS NULL THEN 1
                    ELSE 0
                END as on_break,
                TIMESTAMPDIFF(MINUTE, tc.clock_in, NOW()) / 60 as hours_today
            FROM employees e
            JOIN time_clock tc ON e.id = tc.employee_id
            WHERE tc.clock_out IS NULL
            ORDER BY tc.clock_in ASC
        ";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update existing time entry (Manager only)
     */
    public function updateTimeEntry($entryId, $employeeId, $date, $clockIn, $clockOut = null, $notes = '') {
        try {
            // Combine date with times
            $clockInDateTime = $date . ' ' . $clockIn;
            $clockOutDateTime = $clockOut ? $date . ' ' . $clockOut : null;
            
            // Calculate total hours if both times are provided
            $totalHours = null;
            $overtimeHours = 0;
            
            if ($clockOutDateTime) {
                $clockInTime = new DateTime($clockInDateTime);
                $clockOutTime = new DateTime($clockOutDateTime);
                
                if ($clockOutTime > $clockInTime) {
                    $interval = $clockInTime->diff($clockOutTime);
                    $totalMinutes = ($interval->h * 60) + $interval->i;
                    $totalHours = round($totalMinutes / 60, 2);
                    
                    // Calculate overtime (over 8 hours per day)
                    $overtimeHours = max(0, $totalHours - 8);
                } else {
                    return [
                        'success' => false,
                        'message' => 'Clock out time must be after clock in time'
                    ];
                }
            }
            
            $status = $clockOutDateTime ? 'completed' : 'active';
            
            $stmt = $this->connection->prepare("
                UPDATE time_clock SET 
                    employee_id = ?,
                    clock_in = ?,
                    clock_out = ?,
                    total_hours = ?,
                    overtime_hours = ?,
                    notes = ?,
                    status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $employeeId,
                $clockInDateTime,
                $clockOutDateTime,
                $totalHours,
                $overtimeHours,
                $notes,
                $status,
                $entryId
            ]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Time entry updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No changes made or entry not found'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update entry: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete time entry (Manager only)
     */
    public function deleteTimeEntry($entryId) {
        try {
            // First check if the entry exists
            $stmt = $this->connection->prepare("
                SELECT id, employee_id, clock_in, clock_out 
                FROM time_clock 
                WHERE id = ?
            ");
            $stmt->execute([$entryId]);
            $entry = $stmt->fetch();
            
            if (!$entry) {
                return [
                    'success' => false,
                    'message' => 'Time entry not found'
                ];
            }
            
            // Delete the entry
            $stmt = $this->connection->prepare("DELETE FROM time_clock WHERE id = ?");
            $stmt->execute([$entryId]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Time entry deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete entry'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete entry: ' . $e->getMessage()
            ];
        }
    }
}

class PayrollManager {
    private $connection;
    
    public function __construct() {
        $db = new Database();
        $this->connection = $db->getConnection();
    }
    
    /**
     * Calculate payroll for employee
     */
    public function calculatePayroll($employeeId, $startDate, $endDate, $createdBy) {
        try {
            // Get employee details
            $stmt = $this->connection->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch();
            
            if (!$employee) {
                return [
                    'success' => false,
                    'message' => 'Employee not found'
                ];
            }
            
            // Get time entries for the period
            $stmt = $this->connection->prepare("
                SELECT SUM(total_hours) as total_hours, SUM(overtime_hours) as overtime_hours
                FROM time_clock 
                WHERE employee_id = ? AND DATE(clock_in) BETWEEN ? AND ? 
                AND status IN ('completed', 'approved')
            ");
            $stmt->execute([$employeeId, $startDate, $endDate]);
            $timeData = $stmt->fetch();
            
            $regularHours = ($timeData['total_hours'] ?? 0) - ($timeData['overtime_hours'] ?? 0);
            $overtimeHours = $timeData['overtime_hours'] ?? 0;
            
            // Calculate pay
            $regularPay = $regularHours * $employee['hourly_rate'];
            $overtimePay = $overtimeHours * ($employee['overtime_rate'] ?? ($employee['hourly_rate'] * 1.5));
            $grossPay = $regularPay + $overtimePay;
            
            // Basic tax calculation (simplified - you should implement proper tax calculation)
            $taxRate = 0.20; // 20% tax rate
            $taxDeductions = $grossPay * $taxRate;
            $netPay = $grossPay - $taxDeductions;
            
            // Insert payroll record
            $stmt = $this->connection->prepare("
                INSERT INTO payroll (
                    employee_id, pay_period_start, pay_period_end, regular_hours, overtime_hours, 
                    total_hours, regular_pay, overtime_pay, gross_pay, tax_deductions, net_pay, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $employeeId, $startDate, $endDate, $regularHours, $overtimeHours,
                $timeData['total_hours'] ?? 0, $regularPay, $overtimePay, $grossPay, $taxDeductions, $netPay, $createdBy
            ]);
            
            return [
                'success' => true,
                'message' => 'Payroll calculated successfully',
                'payroll_id' => $this->connection->lastInsertId(),
                'data' => [
                    'regular_hours' => $regularHours,
                    'overtime_hours' => $overtimeHours,
                    'regular_pay' => $regularPay,
                    'overtime_pay' => $overtimePay,
                    'gross_pay' => $grossPay,
                    'tax_deductions' => $taxDeductions,
                    'net_pay' => $netPay
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to calculate payroll: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get payroll records
     */
    public function getPayrollRecords($filters = []) {
        $sql = "
            SELECT p.*, e.first_name, e.last_name, e.employee_id
            FROM payroll p
            JOIN employees e ON p.employee_id = e.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['employee_id'])) {
            $sql .= " AND p.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND p.pay_period_start >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND p.pay_period_end <= ?";
            $params[] = $filters['end_date'];
        }
        
        if (!empty($filters['payment_status'])) {
            $sql .= " AND p.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        
        $sql .= " ORDER BY p.pay_period_end DESC, e.last_name";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET " . intval($filters['offset']);
            }
        }
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Update payroll payment status
     */
    public function updatePaymentStatus($payrollId, $status, $payDate = null, $notes = '') {
        try {
            $stmt = $this->connection->prepare("
                UPDATE payroll SET 
                    payment_status = ?, 
                    pay_date = ?, 
                    notes = CONCAT(COALESCE(notes, ''), ?)
                WHERE id = ?
            ");
            
            $additionalNotes = $notes ? "\nPayment update: " . $notes : '';
            $stmt->execute([$status, $payDate, $additionalNotes, $payrollId]);
            
            return [
                'success' => true,
                'message' => 'Payment status updated successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update payment status: ' . $e->getMessage()
            ];
        }
    }
}

?>