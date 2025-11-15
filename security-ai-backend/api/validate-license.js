/**
 * License Validation API - Vercel Serverless Function
 * 
 * What you're learning:
 * - Serverless functions (runs without managing servers!)
 * - API endpoint design
 * - License key validation
 * - CORS handling
 * - Environment variables
 */

// In-memory license database (will move to real DB later)
// Format: { "license-key": { tier: "pro", email: "user@example.com", active: true } }
const licenses = {
  // Demo license for testing
  "DEMO-PRO-2024": {
    tier: "pro",
    email: "demo@example.com",
    active: true,
    created: "2024-11-11"
  },
  "TEST-KEY-123": {
    tier: "pro",
    email: "test@example.com",
    active: true,
    created: "2024-11-11"
  }
};

module.exports = async (req, res) => {
  // Enable CORS (allow extension to call this API)
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  // Handle preflight request
  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  // Only allow POST requests
  if (req.method !== 'POST') {
    return res.status(405).json({ 
      error: 'Method not allowed',
      message: 'Use POST to validate license keys'
    });
  }

  try {
    // Get license key from request
    const { key } = req.body;

    if (!key) {
      return res.status(400).json({ 
        valid: false, 
        error: 'License key is required' 
      });
    }

    // Validate license key
    const license = licenses[key];

    if (!license) {
      return res.status(200).json({ 
        valid: false, 
        tier: 'free',
        message: 'Invalid license key'
      });
    }

    if (!license.active) {
      return res.status(200).json({ 
        valid: false, 
        tier: 'free',
        message: 'License key has been deactivated'
      });
    }

    // Valid license!
    return res.status(200).json({ 
      valid: true, 
      tier: license.tier,
      email: license.email,
      message: 'License validated successfully'
    });

  } catch (error) {
    console.error('License validation error:', error);
    return res.status(500).json({ 
      valid: false,
      tier: 'free',
      error: 'Internal server error' 
    });
  }
};
