/**
 * Quick test of the security scanner
 */

const SecurityScanner = require('./lib/security-scanner');
const fs = require('fs');
const path = require('path');

// Test on your actual vulnerable social_routes.js
const testFile = path.join(__dirname, '../social_routes.js');

if (fs.existsSync(testFile)) {
    const code = fs.readFileSync(testFile, 'utf8');
    const scanner = new SecurityScanner();
    
    console.log('🔍 Scanning social_routes.js for vulnerabilities...\n');
    
    const vulnerabilities = scanner.scan(code, testFile);
    
    if (vulnerabilities.length === 0) {
        console.log('✅ No vulnerabilities found!');
    } else {
        console.log(`⚠️  Found ${vulnerabilities.length} vulnerabilities:\n`);
        
        vulnerabilities.forEach((vuln, index) => {
            console.log(`${index + 1}. [${vuln.severity}] Line ${vuln.line}`);
            console.log(`   Type: ${vuln.type}`);
            console.log(`   Message: ${vuln.message}`);
            console.log(`   Fix: ${vuln.fix}\n`);
        });
        
        const score = scanner.calculateSecurityScore(vulnerabilities);
        console.log(`📊 Security Score: ${score}/100\n`);
    }
} else {
    console.log('❌ social_routes.js not found');
    console.log('Testing with sample code instead...\n');
    
    const sampleCode = `
app.post('/api/test', async (req, res) => {
    const { message } = req.body;
    const limit = parseInt(req.query.limit) || 20;
    await pool.query('SELECT * FROM users WHERE id = ' + userId);
});
`;
    
    const scanner = new SecurityScanner();
    const vulnerabilities = scanner.scan(sampleCode, 'test.js');
    
    console.log(`Found ${vulnerabilities.length} issues in sample code:`);
    vulnerabilities.forEach(v => {
        console.log(`- ${v.type}: ${v.message}`);
    });
}
