<?php
require_once 'classes.php';

class LoyaltyManager {
    private $db;
    private $connection;
    
    // Tier thresholds and multipliers
    private $tiers = [
        'Bronze' => ['threshold' => 0, 'multiplier' => 1.0],
        'Silver' => ['threshold' => 1000, 'multiplier' => 1.25],
        'Gold' => ['threshold' => 5000, 'multiplier' => 1.5],
        'Platinum' => ['threshold' => 15000, 'multiplier' => 2.0]
    ];
    
    public function __construct() {
        $this->db = new Database();
        $this->connection = $this->db->getConnection();
    }
    
    // Create loyalty wallet for new user
    public function createLoyaltyWallet($userId) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO loyalty_wallets (user_id) VALUES (?)
            ");
            
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Error creating loyalty wallet: " . $e->getMessage());
            return false;
        }
    }
    
    // Get user's points balance
    public function getPointsBalance($userId) {
        try {
            $stmt = $this->connection->prepare("
                SELECT balance FROM loyalty_wallets WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return $result ? (float)$result['balance'] : 0.00;
        } catch (Exception $e) {
            error_log("Error getting points balance: " . $e->getMessage());
            return 0.00;
        }
    }
    
    // Get user's membership tier
    public function getMembershipTier($userId) {
        try {
            $stmt = $this->connection->prepare("
                SELECT total_earned, membership_tier FROM loyalty_wallets WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            if (!$result) {
                $this->createLoyaltyWallet($userId);
                return 'Bronze';
            }
            
            // Check if tier needs updating
            $totalEarned = (float)$result['total_earned'];
            $currentTier = $result['membership_tier'];
            $newTier = $this->calculateTier($totalEarned);
            
            if ($newTier !== $currentTier) {
                $this->updateMembershipTier($userId, $newTier);
                return $newTier;
            }
            
            return $currentTier;
        } catch (Exception $e) {
            error_log("Error getting membership tier: " . $e->getMessage());
            return 'Bronze';
        }
    }
    
    // Award points for booking
    public function awardPointsForBooking($userId, $bookingAmount, $currency = 'USD', $bookingId = null) {
        try {
            // Base points: 10 points per dollar spent
            $basePoints = $bookingAmount * 10;
            
            // Apply tier multiplier
            $tier = $this->getMembershipTier($userId);
            $multiplier = $this->tiers[$tier]['multiplier'];
            $pointsToAward = $basePoints * $multiplier;
            
            return $this->addPoints($userId, $pointsToAward, 'booking', $bookingId, 
                "Earned from " . $currency . " " . number_format($bookingAmount, 2) . " booking (Tier: {$tier})");
        } catch (Exception $e) {
            error_log("Error awarding points for booking: " . $e->getMessage());
            return false;
        }
    }
    
    // Add points to user wallet
    public function addPoints($userId, $points, $source = 'admin', $sourceId = null, $description = '') {
        try {
            $this->connection->beginTransaction();
            
            // Get current balance
            $currentBalance = $this->getPointsBalance($userId);
            $newBalance = $currentBalance + $points;
            
            // Update wallet
            $stmt = $this->connection->prepare("
                UPDATE loyalty_wallets 
                SET balance = ?, total_earned = total_earned + ?, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ");
            $stmt->execute([$newBalance, $points, $userId]);
            
            // Create transaction record (we'll need to create this table)
            $this->logTransaction($userId, 'earn', $points, $newBalance, $source, $sourceId, $description);
            
            // Check and update tier
            $this->updateMembershipTier($userId);
            
            $this->connection->commit();
            return true;
            
        } catch (Exception $e) {
            $this->connection->rollBack();
            error_log("Error adding points: " . $e->getMessage());
            return false;
        }
    }
    
    // Spend points for redemption
    public function spendPoints($userId, $points, $redemptionType, $description = '') {
        try {
            $this->connection->beginTransaction();
            
            // Check balance
            $currentBalance = $this->getPointsBalance($userId);
            if ($currentBalance < $points) {
                throw new Exception("Insufficient points balance");
            }
            
            $newBalance = $currentBalance - $points;
            
            // Update wallet
            $stmt = $this->connection->prepare("
                UPDATE loyalty_wallets 
                SET balance = ?, total_spent = total_spent + ?, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ");
            $stmt->execute([$newBalance, $points, $userId]);
            
            // Log transaction
            $this->logTransaction($userId, 'spend', -$points, $newBalance, $redemptionType, null, $description);
            
            $this->connection->commit();
            return true;
            
        } catch (Exception $e) {
            $this->connection->rollBack();
            error_log("Error spending points: " . $e->getMessage());
            return false;
        }
    }
    
    // Award points for reviews
    public function awardPointsForReview($userId, $rating, $hasText = false) {
        $basePoints = 50; // Base points for review
        $ratingBonus = $rating >= 4 ? 25 : 0; // Bonus for good ratings
        $textBonus = $hasText ? 25 : 0; // Bonus for detailed review
        
        $totalPoints = $basePoints + $ratingBonus + $textBonus;
        
        return $this->addPoints($userId, $totalPoints, 'review', null, 
            "Review reward (Rating: {$rating}/5, Text: " . ($hasText ? 'Yes' : 'No') . ")");
    }
    
    // Award points for referrals
    public function awardPointsForReferral($referrerId, $referredUserId) {
        $referralPoints = 500; // Points for successful referral
        
        return $this->addPoints($referrerId, $referralPoints, 'referral', $referredUserId, 
            "Referral bonus for new user signup");
    }
    
    // Get available redemption options
    public function getRedemptionOptions($userId) {
        $userBalance = $this->getPointsBalance($userId);
        $tier = $this->getMembershipTier($userId);
        
        $options = [
            [
                'type' => 'room_upgrade',
                'name' => 'Room Upgrade',
                'points_required' => 2000,
                'value' => '$50 room upgrade',
                'available' => $userBalance >= 2000
            ],
            [
                'type' => 'free_night',
                'name' => 'Free Night',
                'points_required' => 5000,
                'value' => 'One free night stay',
                'available' => $userBalance >= 5000
            ],
            [
                'type' => 'restaurant_discount',
                'name' => 'Restaurant Discount',
                'points_required' => 1000,
                'value' => '20% off restaurant bill',
                'available' => $userBalance >= 1000
            ],
            [
                'type' => 'spa_service',
                'name' => 'Spa Service',
                'points_required' => 1500,
                'value' => 'Complimentary spa treatment',
                'available' => $userBalance >= 1500
            ]
        ];
        
        // Add tier-specific bonuses
        if ($tier === 'Gold' || $tier === 'Platinum') {
            $options[] = [
                'type' => 'exclusive_experience',
                'name' => 'Exclusive Experience',
                'points_required' => 3000,
                'value' => 'VIP tour or activity',
                'available' => $userBalance >= 3000
            ];
        }
        
        return $options;
    }
    
    // Get user's complete loyalty profile
    public function getLoyaltyProfile($userId) {
        try {
            $stmt = $this->connection->prepare("
                SELECT * FROM loyalty_wallets WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();
            
            if (!$wallet) {
                $this->createLoyaltyWallet($userId);
                return $this->getLoyaltyProfile($userId);
            }
            
            $tier = $this->getMembershipTier($userId);
            $nextTier = $this->getNextTier($tier);
            $pointsToNextTier = $nextTier ? $this->tiers[$nextTier]['threshold'] - $wallet['total_earned'] : 0;
            
            return [
                'balance' => (float)$wallet['balance'],
                'total_earned' => (float)$wallet['total_earned'],
                'total_spent' => (float)$wallet['total_spent'],
                'tier' => $tier,
                'tier_multiplier' => $this->tiers[$tier]['multiplier'],
                'next_tier' => $nextTier,
                'points_to_next_tier' => max(0, $pointsToNextTier),
                'redemption_options' => $this->getRedemptionOptions($userId)
            ];
        } catch (Exception $e) {
            error_log("Error getting loyalty profile: " . $e->getMessage());
            return null;
        }
    }
    
    // Calculate tier based on total earned points
    private function calculateTier($totalEarned) {
        foreach (array_reverse($this->tiers, true) as $tier => $data) {
            if ($totalEarned >= $data['threshold']) {
                return $tier;
            }
        }
        return 'Bronze';
    }
    
    // Update membership tier
    private function updateMembershipTier($userId, $newTier = null) {
        try {
            if (!$newTier) {
                $stmt = $this->connection->prepare("SELECT total_earned FROM loyalty_wallets WHERE user_id = ?");
                $stmt->execute([$userId]);
                $result = $stmt->fetch();
                $newTier = $this->calculateTier($result['total_earned']);
            }
            
            $stmt = $this->connection->prepare("
                UPDATE loyalty_wallets SET membership_tier = ? WHERE user_id = ?
            ");
            $stmt->execute([$newTier, $userId]);
            
        } catch (Exception $e) {
            error_log("Error updating membership tier: " . $e->getMessage());
        }
    }
    
    // Get next tier
    private function getNextTier($currentTier) {
        $tierOrder = ['Bronze', 'Silver', 'Gold', 'Platinum'];
        $currentIndex = array_search($currentTier, $tierOrder);
        
        return isset($tierOrder[$currentIndex + 1]) ? $tierOrder[$currentIndex + 1] : null;
    }
    
    // Log transaction (simplified version)
    private function logTransaction($userId, $type, $amount, $balanceAfter, $source, $sourceId, $description) {
        // For now, we'll store this in hotelcoin_transactions table or create a separate one later
        // This is a placeholder for transaction logging
    }
}
?>