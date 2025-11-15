/**
 * Gumroad Webhook Handler
 * 
 * This receives notifications when someone buys your Pro license.
 * It generates a license key and emails it to the customer.
 * 
 * What you're learning:
 * - Webhook handling
 * - Payment integration
 * - License key generation
 * - Email automation (future)
 */

const crypto = require('crypto');

// Generate a random license key
function generateLicenseKey() {
  const segments = [];
  for (let i = 0; i < 4; i++) {
    segments.push(crypto.randomBytes(4).toString('hex').toUpperCase());
  }
  return segments.join('-'); // Example: A1B2-C3D4-E5F6-G7H8
}

// In-memory storage (will upgrade to database later)
const activeLicenses = new Map();

module.exports = async (req, res) => {
  // Enable CORS
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  try {
    // Gumroad sends this data when someone purchases
    const {
      sale_id,
      email,
      product_name,
      price,
      currency,
      recurrence,
      refunded
    } = req.body;

    // Don't process refunds
    if (refunded === 'true') {
      console.log(`Sale ${sale_id} was refunded, deactivating license`);
      // TODO: Deactivate license
      return res.status(200).json({ message: 'Refund processed' });
    }

    // Generate new license key
    const licenseKey = generateLicenseKey();

    // Determine tier based on product
    let tier = 'pro';
    if (product_name && product_name.includes('Enterprise')) {
      tier = 'enterprise';
    }

    // Store license
    const licenseData = {
      key: licenseKey,
      tier,
      email,
      sale_id,
      price,
      currency,
      recurrence,
      active: true,
      created: new Date().toISOString()
    };

    activeLicenses.set(licenseKey, licenseData);

    // Log for debugging (in production, save to database)
    console.log('New license created:', licenseData);

    // TODO: Send email to customer with license key
    // TODO: Save to database (PostgreSQL/MongoDB)

    // Return success to Gumroad
    return res.status(200).json({ 
      success: true,
      license_key: licenseKey,
      message: 'License created successfully'
    });

  } catch (error) {
    console.error('Gumroad webhook error:', error);
    return res.status(500).json({ error: 'Internal server error' });
  }
};
