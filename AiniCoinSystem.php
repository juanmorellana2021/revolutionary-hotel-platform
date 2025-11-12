<?php
/**
 * AiNi Coin Transaction Handler
 * Blockchain-style immutable ledger with hash verification
 * Future-ready for stablecoin backing and crypto integration
 */

class AiniCoinSystem {
    private $pdo;
    private $secretSalt = 'AiNi2025SecureHashSalt!@#'; // Change this to random string in production
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Generate transaction hash (blockchain-style)
     * This hash proves the transaction hasn't been tampered with
     */
    private function generateTransactionHash($userId, $amount, $balanceBefore, $balanceAfter, $prevTxId, $timestamp, $type) {
        $data = implode('|', [
            $userId,
            $amount,
            $balanceBefore,
            $balanceAfter,
            $prevTxId ?? '0',
            $timestamp,
            $type,
            $this->secretSalt
        ]);
        return hash('sha256', $data);
    }
    
    /**
     * Get user's current balance with row lock (prevents race conditions)
     */
    private function getCurrentBalance($userId) {
        $stmt = $this->pdo->prepare("SELECT aini_coins FROM ainitravel_users WHERE id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?? 0;
    }
    
    /**
     * Get user's last transaction ID for blockchain linking
     */
    private function getLastTransactionId($userId) {
        $stmt = $this->pdo->prepare("
            SELECT id FROM aini_coin_transactions 
            WHERE user_id = ? 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Main function: Record a coin transaction (IMMUTABLE)
     * This creates a permanent, tamper-proof record
     */
    public function recordTransaction(
        $userId,
        $amount,
        $transactionType,
        $referenceType = null,
        $referenceId = null,
        $description = '',
        $counterpartyUserId = null,
        $transferNote = null
    ) {
        try {
            $this->pdo->beginTransaction();
            
            // Get current balance with lock
            $balanceBefore = $this->getCurrentBalance($userId);
            $balanceAfter = $balanceBefore + $amount;
            
            // Prevent negative balance
            if ($balanceAfter < 0) {
                throw new Exception("Insufficient coins. Balance: $balanceBefore, Required: " . abs($amount));
            }
            
            // Get previous transaction for blockchain linking
            $prevTxId = $this->getLastTransactionId($userId);
            
            // Generate timestamp
            $timestamp = date('Y-m-d H:i:s');
            
            // Generate transaction hash
            $txHash = $this->generateTransactionHash(
                $userId,
                $amount,
                $balanceBefore,
                $balanceAfter,
                $prevTxId,
                $timestamp,
                $transactionType
            );
            
            // Insert transaction (IMMUTABLE - never delete!)
            $stmt = $this->pdo->prepare("
                INSERT INTO aini_coin_transactions (
                    user_id,
                    transaction_type,
                    amount,
                    balance_before,
                    balance_after,
                    previous_transaction_id,
                    transaction_hash,
                    counterparty_user_id,
                    transfer_note,
                    reference_type,
                    reference_id,
                    description,
                    ip_address,
                    user_agent,
                    created_at,
                    is_verified,
                    verified_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $stmt->execute([
                $userId,
                $transactionType,
                $amount,
                $balanceBefore,
                $balanceAfter,
                $prevTxId,
                $txHash,
                $counterpartyUserId,
                $transferNote,
                $referenceType,
                $referenceId,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $timestamp
            ]);
            
            $transactionId = $this->pdo->lastInsertId();
            
            // Update user balance (trigger will handle this, but doing manually for safety)
            $stmt = $this->pdo->prepare("UPDATE ainitravel_users SET aini_coins = ? WHERE id = ?");
            $stmt->execute([$balanceAfter, $userId]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'transaction_hash' => $txHash
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Coin transaction failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Transfer coins between users (peer-to-peer)
     */
    public function transferCoins($fromUserId, $toUserId, $amount, $note = '') {
        try {
            // Validation
            if ($fromUserId == $toUserId) {
                throw new Exception("Cannot transfer to yourself");
            }
            
            if ($amount < 1) {
                throw new Exception("Transfer amount must be at least 1 coin");
            }
            
            // Check transfer limits
            $limits = $this->checkTransferLimits($fromUserId, $amount);
            if (!$limits['allowed']) {
                throw new Exception($limits['reason']);
            }
            
            // Verify recipient exists
            $stmt = $this->pdo->prepare("SELECT id, name FROM ainitravel_users WHERE id = ?");
            $stmt->execute([$toUserId]);
            $recipient = $stmt->fetch();
            if (!$recipient) {
                throw new Exception("Recipient user not found");
            }
            
            $this->pdo->beginTransaction();
            
            // Record debit for sender
            $senderResult = $this->recordTransaction(
                $fromUserId,
                -$amount,
                'transfer_out',
                'transfer',
                $toUserId,
                "Transfer to user #{$toUserId}: " . $recipient['name'],
                $toUserId,
                $note
            );
            
            if (!$senderResult['success']) {
                throw new Exception($senderResult['error']);
            }
            
            // Record credit for recipient
            $stmt = $this->pdo->prepare("SELECT name FROM ainitravel_users WHERE id = ?");
            $stmt->execute([$fromUserId]);
            $senderName = $stmt->fetchColumn();
            
            $recipientResult = $this->recordTransaction(
                $toUserId,
                $amount,
                'transfer_in',
                'transfer',
                $fromUserId,
                "Transfer from user #{$fromUserId}: " . $senderName,
                $fromUserId,
                $note
            );
            
            if (!$recipientResult['success']) {
                throw new Exception($recipientResult['error']);
            }
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'amount' => $amount,
                'from_user' => $fromUserId,
                'to_user' => $toUserId,
                'sender_balance' => $senderResult['balance_after'],
                'recipient_balance' => $recipientResult['balance_after'],
                'sender_tx_id' => $senderResult['transaction_id'],
                'recipient_tx_id' => $recipientResult['transaction_id']
            ];
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if user can transfer (anti-fraud)
     */
    public function checkTransferLimits($userId, $amount = 0) {
        // Get or create transfer limits record
        $stmt = $this->pdo->prepare("
            SELECT * FROM aini_coin_transfer_limits WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $limits = $stmt->fetch();
        
        if (!$limits) {
            // Create limits record for new user
            $stmt = $this->pdo->prepare("SELECT DATEDIFF(NOW(), created_at) as age_days FROM ainitravel_users WHERE id = ?");
            $stmt->execute([$userId]);
            $ageDays = $stmt->fetchColumn();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO aini_coin_transfer_limits (user_id, account_age_days, is_transfer_enabled)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, $ageDays, $ageDays >= 30 ? 1 : 0]);
            
            $stmt = $this->pdo->prepare("SELECT * FROM aini_coin_transfer_limits WHERE user_id = ?");
            $stmt->execute([$userId]);
            $limits = $stmt->fetch();
        }
        
        // Check if flagged
        if ($limits['is_flagged']) {
            return ['allowed' => false, 'reason' => 'Account flagged for suspicious activity'];
        }
        
        // Check account age
        if ($limits['account_age_days'] < $limits['min_age_to_transfer']) {
            $daysRemaining = $limits['min_age_to_transfer'] - $limits['account_age_days'];
            return ['allowed' => false, 'reason' => "Account must be {$limits['min_age_to_transfer']} days old. Wait {$daysRemaining} more days."];
        }
        
        // Check if transfers enabled
        if (!$limits['is_transfer_enabled']) {
            return ['allowed' => false, 'reason' => 'Transfers not enabled for this account'];
        }
        
        // Check daily limit
        $todayTransferred = $limits['last_transfer_date'] == date('Y-m-d') ? $limits['daily_transferred_today'] : 0;
        if ($todayTransferred + $amount > $limits['daily_transfer_limit']) {
            $remaining = $limits['daily_transfer_limit'] - $todayTransferred;
            return ['allowed' => false, 'reason' => "Daily transfer limit reached. You can transfer {$remaining} more coins today."];
        }
        
        // Get system config
        $stmt = $this->pdo->prepare("SELECT config_value FROM aini_coin_system_config WHERE config_key = ?");
        $stmt->execute(['min_transfer_amount']);
        $minAmount = $stmt->fetchColumn() ?? 10;
        
        $stmt->execute(['max_transfer_amount']);
        $maxAmount = $stmt->fetchColumn() ?? 1000;
        
        if ($amount < $minAmount) {
            return ['allowed' => false, 'reason' => "Minimum transfer amount is {$minAmount} coins"];
        }
        
        if ($amount > $maxAmount) {
            return ['allowed' => false, 'reason' => "Maximum transfer amount is {$maxAmount} coins per transaction"];
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Award coins for booking
     */
    public function awardBookingCoins($userId, $bookingId, $bookingAmountUSD) {
        // Get coins per dollar from config
        $stmt = $this->pdo->prepare("SELECT config_value FROM aini_coin_system_config WHERE config_key = 'coins_per_dollar_spent'");
        $stmt->execute();
        $coinsPerDollar = $stmt->fetchColumn() ?? 10;
        
        $coinsEarned = floor($bookingAmountUSD * $coinsPerDollar);
        
        if ($coinsEarned < 1) {
            return ['success' => false, 'error' => 'Booking amount too low to earn coins'];
        }
        
        return $this->recordTransaction(
            $userId,
            $coinsEarned,
            'earned_booking',
            'booking',
            $bookingId,
            "Earned {$coinsEarned} coins from booking #{$bookingId} (\${$bookingAmountUSD})"
        );
    }
    
    /**
     * Verify transaction chain integrity (blockchain verification)
     */
    public function verifyUserTransactionChain($userId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM aini_coin_transactions 
            WHERE user_id = ? 
            ORDER BY id ASC
        ");
        $stmt->execute([$userId]);
        $transactions = $stmt->fetchAll();
        
        $errors = [];
        $prevTxId = null;
        
        foreach ($transactions as $tx) {
            // Recalculate hash
            $calculatedHash = $this->generateTransactionHash(
                $tx['user_id'],
                $tx['amount'],
                $tx['balance_before'],
                $tx['balance_after'],
                $tx['previous_transaction_id'],
                $tx['created_at'],
                $tx['transaction_type']
            );
            
            // Verify hash matches
            if ($calculatedHash !== $tx['transaction_hash']) {
                $errors[] = "Transaction #{$tx['id']}: Hash mismatch (tampering detected!)";
            }
            
            // Verify chain link
            if ($prevTxId !== null && $tx['previous_transaction_id'] != $prevTxId) {
                $errors[] = "Transaction #{$tx['id']}: Chain broken (previous_tx should be #{$prevTxId})";
            }
            
            // Verify balance calculation
            $expectedBalance = $tx['balance_before'] + $tx['amount'];
            if ($expectedBalance != $tx['balance_after']) {
                $errors[] = "Transaction #{$tx['id']}: Balance calculation error";
            }
            
            $prevTxId = $tx['id'];
        }
        
        return [
            'verified' => count($errors) === 0,
            'total_transactions' => count($transactions),
            'errors' => $errors
        ];
    }
    
    /**
     * Get user's transaction history
     */
    public function getTransactionHistory($userId, $limit = 50, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT 
                t.*,
                u.name as counterparty_name
            FROM aini_coin_transactions t
            LEFT JOIN ainitravel_users u ON u.id = t.counterparty_user_id
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get user's coin balance and stats
     */
    public function getUserCoinStats($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.aini_coins as current_balance,
                u.total_bookings,
                COALESCE(SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END), 0) as total_earned,
                COALESCE(SUM(CASE WHEN t.amount < 0 THEN ABS(t.amount) ELSE 0 END), 0) as total_spent,
                COUNT(t.id) as total_transactions
            FROM ainitravel_users u
            LEFT JOIN aini_coin_transactions t ON t.user_id = u.id
            WHERE u.id = ?
            GROUP BY u.id
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Get current crypto market price
     */
    public function getCryptoPrice() {
        $stmt = $this->pdo->query("SELECT price_usd FROM aini_crypto_market_prices ORDER BY recorded_at DESC LIMIT 1");
        return $stmt ? floatval($stmt->fetchColumn()) : 1.00;
    }
    
    /**
     * Convert AiNi Rewards to AiNi Crypto
     */
    public function convertRewardsToCrypto($userId, $rewardsAmount) {
        try {
            $this->pdo->beginTransaction();
            
            // Get user balances
            $stmt = $this->pdo->prepare("SELECT aini_rewards, aini_crypto FROM ainitravel_users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            if ($user['aini_rewards'] < $rewardsAmount) {
                throw new Exception("Insufficient Rewards balance");
            }
            
            // Get current crypto price (1 Rewards = X Crypto based on market price)
            $cryptoPrice = $this->getCryptoPrice();
            $conversionRate = 1.0 / $cryptoPrice; // How many crypto coins per 1 reward
            $cryptoAmount = round($rewardsAmount * $conversionRate);
            
            // Update balances
            $newRewards = $user['aini_rewards'] - $rewardsAmount;
            $newCrypto = $user['aini_crypto'] + $cryptoAmount;
            
            $stmt = $this->pdo->prepare("UPDATE ainitravel_users SET aini_rewards = ?, aini_crypto = ? WHERE id = ?");
            $stmt->execute([$newRewards, $newCrypto, $userId]);
            
            // Record conversion
            $stmt = $this->pdo->prepare("
                INSERT INTO aini_coin_conversions (
                    user_id, from_coin_type, to_coin_type, amount_converted, 
                    conversion_rate, from_balance_before, from_balance_after,
                    to_balance_before, to_balance_after, transaction_hash, market_price_usd
                ) VALUES (?, 'rewards', 'crypto', ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $hash = hash('sha256', implode('|', [$userId, $rewardsAmount, $cryptoAmount, time(), $this->secretSalt]));
            
            $stmt->execute([
                $userId,
                $rewardsAmount,
                $conversionRate,
                $user['aini_rewards'],
                $newRewards,
                $user['aini_crypto'],
                $newCrypto,
                $hash,
                $cryptoPrice
            ]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'rewards_spent' => $rewardsAmount,
                'crypto_received' => $cryptoAmount,
                'conversion_rate' => $conversionRate,
                'new_rewards_balance' => $newRewards,
                'new_crypto_balance' => $newCrypto
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Convert AiNi Crypto to AiNi Rewards
     */
    public function convertCryptoToRewards($userId, $cryptoAmount) {
        try {
            $this->pdo->beginTransaction();
            
            // Get user balances
            $stmt = $this->pdo->prepare("SELECT aini_rewards, aini_crypto FROM ainitravel_users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            if ($user['aini_crypto'] < $cryptoAmount) {
                throw new Exception("Insufficient Crypto balance");
            }
            
            // Get current crypto price (1 Crypto = X USD worth of Rewards)
            $cryptoPrice = $this->getCryptoPrice();
            $conversionRate = $cryptoPrice; // How many rewards per 1 crypto
            $rewardsAmount = round($cryptoAmount * $conversionRate);
            
            // Update balances
            $newCrypto = $user['aini_crypto'] - $cryptoAmount;
            $newRewards = $user['aini_rewards'] + $rewardsAmount;
            
            $stmt = $this->pdo->prepare("UPDATE ainitravel_users SET aini_rewards = ?, aini_crypto = ? WHERE id = ?");
            $stmt->execute([$newRewards, $newCrypto, $userId]);
            
            // Record conversion
            $stmt = $this->pdo->prepare("
                INSERT INTO aini_coin_conversions (
                    user_id, from_coin_type, to_coin_type, amount_converted,
                    conversion_rate, from_balance_before, from_balance_after,
                    to_balance_before, to_balance_after, transaction_hash, market_price_usd
                ) VALUES (?, 'crypto', 'rewards', ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $hash = hash('sha256', implode('|', [$userId, $cryptoAmount, $rewardsAmount, time(), $this->secretSalt]));
            
            $stmt->execute([
                $userId,
                $cryptoAmount,
                $conversionRate,
                $user['aini_crypto'],
                $newCrypto,
                $user['aini_rewards'],
                $newRewards,
                $hash,
                $cryptoPrice
            ]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'crypto_spent' => $cryptoAmount,
                'rewards_received' => $rewardsAmount,
                'conversion_rate' => $conversionRate,
                'new_crypto_balance' => $newCrypto,
                'new_rewards_balance' => $newRewards
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get conversion history for user
     */
    public function getConversionHistory($userId, $limit = 20) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM aini_coin_conversions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
}
?>
