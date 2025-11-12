/**
 * License Validator - Checks if user has Pro license
 * Communicates with backend API to validate license keys
 */

const axios = require('axios');

class LicenseValidator {
    constructor(context) {
        this.context = context;
        this.apiEndpoint = 'https://api.securityai.dev/validate-license'; // Will create this
        this.cachedTier = null;
        this.cacheExpiry = null;
    }

    /**
     * Check user's license tier
     * Returns: 'free', 'pro', or 'enterprise'
     */
    async checkLicense() {
        // Check cache first (valid for 1 hour)
        if (this.cachedTier && this.cacheExpiry && Date.now() < this.cacheExpiry) {
            return this.cachedTier;
        }

        try {
            // Get license key from secure storage
            const licenseKey = await this.context.secrets.get('licenseKey');
            
            if (!licenseKey) {
                return 'free';
            }

            // Validate with backend (with timeout)
            const response = await axios.post(this.apiEndpoint, 
                { key: licenseKey },
                { timeout: 5000 }
            ).catch(err => {
                console.error('License validation failed:', err.message);
                // Fail open - allow usage if API is down
                return { data: { valid: true, tier: 'pro' } };
            });

            if (response.data.valid) {
                this.cachedTier = response.data.tier || 'pro';
                this.cacheExpiry = Date.now() + (60 * 60 * 1000); // Cache for 1 hour
                return this.cachedTier;
            } else {
                // Invalid key - clear from storage
                await this.context.secrets.delete('licenseKey');
                this.cachedTier = 'free';
                return 'free';
            }
        } catch (error) {
            console.error('License check error:', error);
            // Fail open - allow usage if something goes wrong
            return this.cachedTier || 'free';
        }
    }

    /**
     * Save license key
     */
    async saveLicenseKey(key) {
        await this.context.secrets.store('licenseKey', key);
        this.cachedTier = null; // Clear cache
        this.cacheExpiry = null;
    }

    /**
     * Clear license key
     */
    async clearLicenseKey() {
        await this.context.secrets.delete('licenseKey');
        this.cachedTier = 'free';
        this.cacheExpiry = null;
    }

    /**
     * Get license info for display
     */
    async getLicenseInfo() {
        const tier = await this.checkLicense();
        const licenseKey = await this.context.secrets.get('licenseKey');
        
        return {
            tier,
            hasKey: !!licenseKey,
            features: this.getFeaturesForTier(tier)
        };
    }

    /**
     * Get features available for tier
     */
    getFeaturesForTier(tier) {
        const features = {
            free: [
                'Scan 10 files per month',
                'View vulnerabilities',
                'Basic security tips',
                'Community support'
            ],
            pro: [
                'Unlimited scans',
                'One-click auto-fix',
                'Security test generation',
                'Custom security rules',
                'Priority support',
                'Workspace scanning'
            ],
            enterprise: [
                'Everything in Pro',
                'Team dashboard',
                'SSO integration',
                'Compliance reports',
                'Dedicated support',
                'SLA guarantee'
            ]
        };

        return features[tier] || features.free;
    }

    /**
     * Track usage for free tier
     */
    async trackUsage() {
        const tier = await this.checkLicense();
        
        if (tier !== 'free') {
            return true; // Unlimited for paid tiers
        }

        // Get usage from workspace state
        const usageKey = `usage_${this.getCurrentMonth()}`;
        const currentUsage = this.context.workspaceState.get(usageKey) || 0;
        
        if (currentUsage >= 10) {
            return false; // Limit exceeded
        }

        // Increment usage
        await this.context.workspaceState.update(usageKey, currentUsage + 1);
        return true;
    }

    /**
     * Get current month key for usage tracking
     */
    getCurrentMonth() {
        const now = new Date();
        return `${now.getFullYear()}-${now.getMonth() + 1}`;
    }

    /**
     * Get remaining scans for free tier
     */
    async getRemainingScans() {
        const tier = await this.checkLicense();
        
        if (tier !== 'free') {
            return Infinity;
        }

        const usageKey = `usage_${this.getCurrentMonth()}`;
        const currentUsage = this.context.workspaceState.get(usageKey) || 0;
        return Math.max(0, 10 - currentUsage);
    }
}

module.exports = LicenseValidator;
