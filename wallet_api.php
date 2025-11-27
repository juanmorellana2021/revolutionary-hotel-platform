<?php
/**
 * Wallet API for AiNiFlow Social Platform
 * Connects to prod-vps hotel_booking_system database
 * Routes: /wallet_api.php?action=balance&phone=+1234567890
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
$db_host = 'localhost';
$db_name = 'hotel_booking_system';
$db_user = 'hoteluser';
$db_pass = 'hotelpass123';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Parse request - support both query string and path-based routing
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$phone = $_GET['phone'] ?? '';

// Parse from URL path if query string empty (wallet_api.php/balance/+1234567890)
if (empty($action)) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = array_filter(explode('/', $path));
    
    // Find wallet_api.php position
    foreach ($segments as $i => $seg) {
        if (strpos($seg, 'wallet_api') !== false) {
            $action = $segments[$i + 1] ?? '';
            $phone = $segments[$i + 2] ?? '';
            break;
        }
    }
}

// ROUTE: GET balance
if ($method === 'GET' && $action === 'balance' && $phone) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, phone, aini_coins as balance 
            FROM ainitravel_users 
            WHERE phone = ?
        ");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'balance' => floatval($user['balance'] ?? 0),
                'user_id' => $user['id']
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// ROUTE: GET transactions
elseif ($method === 'GET' && $action === 'transactions' && $phone) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM ainitravel_users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                id,
                user_id,
                transaction_type,
                amount,
                balance_after,
                description,
                created_at
            FROM aini_coin_transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 100
        ");
        $stmt->execute([$user['id']]);
        $transactions = $stmt->fetchAll();
        
        $formatted = array_map(function($tx) use ($phone) {
            return [
                'id' => $tx['id'],
                'transaction_type' => $tx['transaction_type'],
                'amount' => floatval($tx['amount']),
                'description' => $tx['description'],
                'created_at' => $tx['created_at'],
                'from_phone' => $phone,
                'to_phone' => $phone,
                'status' => 'completed'
            ];
        }, $transactions);
        
        echo json_encode($formatted);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// ROUTE: POST deposit
elseif ($method === 'POST' && $action === 'deposit') {
    $data = json_decode(file_get_contents('php://input'), true);
    $phone = $data['phone'] ?? '';
    $amount = floatval($data['amount'] ?? 0);
    
    if (!$phone || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid phone/amount']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT id, aini_coins FROM ainitravel_users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
        $oldBalance = floatval($user['aini_coins']);
        $newBalance = $oldBalance + $amount;
        $stmt = $pdo->prepare("UPDATE ainitravel_users SET aini_coins = ? WHERE id = ?");
        $stmt->execute([$newBalance, $user['id']]);
        
        // Generate transaction hash
        $txHash = hash('sha256', $user['id'] . $amount . time() . rand());
        
        $stmt = $pdo->prepare("
            INSERT INTO aini_coin_transactions 
            (user_id, transaction_type, amount, balance_before, balance_after, description, transaction_hash, created_at, coin_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([
            $user['id'],
            'rewards_purchase',
            $amount,
            $oldBalance,
            $newBalance,
            'Instant deposit (dev mode)',
            $txHash,
            'rewards'
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Deposit successful',
            'new_balance' => floatval($newBalance)
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// ROUTE: POST transfer
elseif ($method === 'POST' && $action === 'transfer') {
    $data = json_decode(file_get_contents('php://input'), true);
    $from_phone = $data['from_phone'] ?? '';
    $to_phone = $data['to_phone'] ?? '';
    $amount = floatval($data['amount'] ?? 0);
    $description = $data['description'] ?? 'AiNi transfer';
    
    if (!$from_phone || !$to_phone || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid fields']);
        exit;
    }
    
    if ($from_phone === $to_phone) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot transfer to yourself']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Sender
        $stmt = $pdo->prepare("SELECT id, aini_coins FROM ainitravel_users WHERE phone = ?");
        $stmt->execute([$from_phone]);
        $sender = $stmt->fetch();
        
        if (!$sender) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Sender not found']);
            exit;
        }
        
        if (floatval($sender['aini_coins']) < $amount) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Insufficient balance']);
            exit;
        }
        
        // Recipient
        $stmt = $pdo->prepare("SELECT id, aini_coins FROM ainitravel_users WHERE phone = ?");
        $stmt->execute([$to_phone]);
        $recipient = $stmt->fetch();
        
        if (!$recipient) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Recipient not found']);
            exit;
        }
        
        // Update balances
        $senderOldBalance = floatval($sender['aini_coins']);
        $senderNewBalance = $senderOldBalance - $amount;
        $recipientOldBalance = floatval($recipient['aini_coins']);
        $recipientNewBalance = $recipientOldBalance + $amount;
        
        $stmt = $pdo->prepare("UPDATE ainitravel_users SET aini_coins = ? WHERE id = ?");
        $stmt->execute([$senderNewBalance, $sender['id']]);
        $stmt->execute([$recipientNewBalance, $recipient['id']]);
        
        // Generate transaction hashes
        $txHashOut = hash('sha256', $sender['id'] . 'out' . $amount . time() . rand());
        $txHashIn = hash('sha256', $recipient['id'] . 'in' . $amount . time() . rand());
        
        // Record transactions
        $stmt = $pdo->prepare("
            INSERT INTO aini_coin_transactions 
            (user_id, transaction_type, amount, balance_before, balance_after, description, transaction_hash, created_at, counterparty_user_id, transfer_note, coin_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
        ");
        
        // Sender transaction (transfer_out)
        $stmt->execute([
            $sender['id'],
            'transfer_out',
            $amount,
            $senderOldBalance,
            $senderNewBalance,
            $description,
            $txHashOut,
            $recipient['id'],
            $description,
            'rewards'
        ]);
        
        // Recipient transaction (transfer_in)
        $stmt->execute([
            $recipient['id'],
            'transfer_in',
            $amount,
            $recipientOldBalance,
            $recipientNewBalance,
            $description,
            $txHashIn,
            $sender['id'],
            $description,
            'rewards'
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Transfer successful',
            'new_balance' => floatval($senderNewBalance)
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
}
?>
