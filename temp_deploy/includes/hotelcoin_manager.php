<?php
require_once 'classes.php';

class HotelCoinManager {
    private $db;
    private $connection;
    
    public function __construct() {
        $this->db = new Database();
        $this->connection = $this->db->getConnection();
    }
    
    // Create wallet for new user
    public function createWallet($userId) {
        try {
            $walletAddress = $this->generateWalletAddress();
            
            $stmt = $this->connection->prepare("
                INSERT INTO hotelcoin_wallets (user_id, wallet_address) 
                VALUES (?, ?)
            ");
            
            return $stmt->execute([$userId, $walletAddress]);
        } catch (Exception $e) {
            error_log("Error creating HotelCoin wallet: " . $e->getMessage());
            return false;
        }
    }
    
    // Get user's HotelCoin balance
    public function getBalance($userId) {
        try {
            $stmt = $this->connection->prepare("
                SELECT balance FROM hotelcoin_wallets WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return $result ? (float)$result['balance'] : 0.0000;
        } catch (Exception $e) {
            error_log("Error getting HotelCoin balance: " . $e->getMessage());
            return 0.0000;
        }
    }
    
    // Get current exchange rate
    public function getCurrentRate($currency = 'USD') {
        try {
            $stmt = $this->connection->prepare("
                SELECT rate_to_usd, rate_to_pen 
                FROM hotelcoin_exchange_rates 
                WHERE is_active = 1 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            
            if (!$result) {
                // Insert default rate if none exists
                $this->updateExchangeRate(1.0000, 3.7500);
                return $currency === 'USD' ? 1.0000 : 3.7500;
            }
            
            return $currency === 'USD' ? (float)$result['rate_to_usd'] : (float)$result['rate_to_pen'];
        } catch (Exception $e) {
            error_log("Error getting exchange rate: " . $e->getMessage());
            return $currency === 'USD' ? 1.0000 : 3.7500;
        }
    }
    
    // Award HotelCoins for booking
    public function awardCoinsForBooking($userId, $bookingAmount, $currency = 'USD', $bookingId = null) {
        try {
            // Calculate coins: 1% of booking value in coins
            $coinRate = $this->getCurrentRate($currency);
            $coinsToAward = ($bookingAmount * 0.01) / $coinRate;
            
            return $this->addCoins($userId, $coinsToAward, 'booking', $bookingId, 
                "Earned from " . $currency . " " . number_format($bookingAmount, 2) . " booking");
        } catch (Exception $e) {
            error_log("Error awarding coins for booking: " . $e->getMessage());
            return false;
        }
    }
    
    // Add coins to user wallet
    public function addCoins($userId, $amount, $source = 'admin', $sourceId = null, $description = '') {
        try {
            $this->connection->beginTransaction();
            
            // Get current balance
            $currentBalance = $this->getBalance($userId);
            $newBalance = $currentBalance + $amount;
            
            // Update wallet
            $stmt = $this->connection->prepare("
                UPDATE hotelcoin_wallets 
                SET balance = ?, total_earned = total_earned + ?, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ");
            $stmt->execute([$newBalance, $amount, $userId]);
            
            // Create transaction record
            $transactionHash = $this->generateTransactionHash();
            $usdEquivalent = $amount * $this->getCurrentRate('USD');
            $penEquivalent = $amount * $this->getCurrentRate('PEN');
            
            $stmt = $this->connection->prepare("
                INSERT INTO hotelcoin_transactions 
                (to_user_id, transaction_type, amount, usd_equivalent, pen_equivalent, 
                 exchange_rate, source, source_id, transaction_hash, description, status, confirmed_at)
                VALUES (?, 'earn', ?, ?, ?, ?, ?, ?, ?, ?, 'completed', CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $userId, $amount, $usdEquivalent, $penEquivalent,
                $this->getCurrentRate('USD'), $source, $sourceId, 
                $transactionHash, $description
            ]);
            
            $this->connection->commit();
            return true;
            
        } catch (Exception $e) {
            $this->connection->rollBack();
            error_log("Error adding coins: " . $e->getMessage());
            return false;
        }
    }
    
    // Transfer coins between users
    public function transferCoins($fromUserId, $toUserId, $amount, $description = '') {
        try {
            $this->connection->beginTransaction();
            
            // Check sender balance
            $senderBalance = $this->getBalance($fromUserId);
            if ($senderBalance < $amount) {
                throw new Exception("Insufficient HotelCoin balance");
            }
            
            // Update sender
            $newSenderBalance = $senderBalance - $amount;
            $stmt = $this->connection->prepare("
                UPDATE hotelcoin_wallets 
                SET balance = ?, total_spent = total_spent + ?, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ");
            $stmt->execute([$newSenderBalance, $amount, $fromUserId]);
            
            // Update receiver
            $receiverBalance = $this->getBalance($toUserId);
            $newReceiverBalance = $receiverBalance + $amount;
            $stmt = $this->connection->prepare("
                UPDATE hotelcoin_wallets 
                SET balance = ?, total_earned = total_earned + ?, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ");
            $stmt->execute([$newReceiverBalance, $amount, $toUserId]);
            
            // Create transaction record
            $transactionHash = $this->generateTransactionHash();
            $usdEquivalent = $amount * $this->getCurrentRate('USD');
            $penEquivalent = $amount * $this->getCurrentRate('PEN');
            
            $stmt = $this->connection->prepare("
                INSERT INTO hotelcoin_transactions 
                (from_user_id, to_user_id, transaction_type, amount, usd_equivalent, pen_equivalent,
                 exchange_rate, source, transaction_hash, description, status, confirmed_at)
                VALUES (?, ?, 'transfer', ?, ?, ?, ?, 'peer_transfer', ?, ?, 'completed', CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $fromUserId, $toUserId, $amount, $usdEquivalent, $penEquivalent,
                $this->getCurrentRate('USD'), $transactionHash, $description
            ]);
            
            $this->connection->commit();
            return ['success' => true, 'transaction_hash' => $transactionHash];
            
        } catch (Exception $e) {
            $this->connection->rollBack();
            error_log("Error transferring coins: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Get user transaction history
    public function getTransactionHistory($userId, $limit = 50) {
        try {
            $stmt = $this->connection->prepare("
                SELECT ht.*, 
                       CONCAT(u1.first_name, ' ', u1.last_name) as from_username,
                       u1.email as from_email,
                       CONCAT(u2.first_name, ' ', u2.last_name) as to_username,
                       u2.email as to_email
                FROM hotelcoin_transactions ht
                LEFT JOIN users u1 ON ht.from_user_id = u1.id
                LEFT JOIN users u2 ON ht.to_user_id = u2.id
                WHERE ht.from_user_id = ? OR ht.to_user_id = ?
                ORDER BY ht.created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $userId, $limit]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting transaction history: " . $e->getMessage());
            return [];
        }
    }
    
    // Update exchange rate (admin function)
    public function updateExchangeRate($usdRate, $penRate) {
        try {
            // Deactivate old rates
            $stmt = $this->connection->prepare("
                UPDATE hotelcoin_exchange_rates SET is_active = 0
            ");
            $stmt->execute();
            
            // Insert new rate
            $stmt = $this->connection->prepare("
                INSERT INTO hotelcoin_exchange_rates (rate_to_usd, rate_to_pen, is_active)
                VALUES (?, ?, 1)
            ");
            
            return $stmt->execute([$usdRate, $penRate]);
        } catch (Exception $e) {
            error_log("Error updating exchange rate: " . $e->getMessage());
            return false;
        }
    }
    
    // Generate unique wallet address
    private function generateWalletAddress() {
        return 'HC' . strtoupper(bin2hex(random_bytes(20)));
    }
    
    // Generate unique transaction hash
    private function generateTransactionHash() {
        return hash('sha256', uniqid() . microtime() . random_bytes(16));
    }
    
    // Get wallet info
    public function getWalletInfo($userId) {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM hotelcoin_wallets WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();
            
            if (!$wallet) {
                // Create wallet if doesn't exist
                $this->createWallet($userId);
                return $this->getWalletInfo($userId);
            }
            
            return $wallet;
        } catch (Exception $e) {
            error_log("Error getting wallet info: " . $e->getMessage());
            return null;
        }
    }
}
?>