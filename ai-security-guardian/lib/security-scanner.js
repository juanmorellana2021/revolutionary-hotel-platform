/**
 * Security Scanner - Detects vulnerabilities in code
 * Based on patterns from your security audit
 */

class SecurityScanner {
    constructor() {
        this.patterns = {
            // JavaScript/TypeScript patterns
            javascript: [
                {
                    name: 'NO_RATE_LIMIT',
                    pattern: /app\.(post|put|delete|patch)\s*\(['"](.*?)['"],\s*async/g,
                    checkContext: (code, match) => {
                        // Check if rate limiter is applied
                        const contextStart = Math.max(0, match.index - 500);
                        const context = code.substring(contextStart, match.index + 200);
                        return !context.includes('rateLimit') && !context.includes('limiter');
                    },
                    severity: 'HIGH',
                    message: (match) => `No rate limiting on ${match[1].toUpperCase()} ${match[2]}. Vulnerable to spam/DoS attacks.`,
                    fix: 'Add rate limiting middleware: app.post(\'/route\', rateLimiter, async (req, res) => ...)'
                },
                {
                    name: 'UNBOUNDED_LIMIT',
                    pattern: /(?:const|let|var)\s+limit\s*=\s*parseInt\([^)]+\)\s*\|\|\s*\d+/g,
                    checkContext: (code, match) => {
                        // Check if Math.min is used
                        const lineEnd = code.indexOf('\n', match.index);
                        const line = code.substring(match.index, lineEnd);
                        return !line.includes('Math.min') && !line.includes('Math.max');
                    },
                    severity: 'MEDIUM',
                    message: () => 'Unbounded limit parameter. Could fetch millions of records (DoS risk).',
                    fix: 'Add bounds: const limit = Math.min(parseInt(req.query.limit) || 20, 100);'
                },
                {
                    name: 'NO_INPUT_SANITIZATION',
                    pattern: /(?:const|let|var)\s+\{[^}]*message[^}]*\}\s*=\s*req\.body/g,
                    checkContext: (code, match) => {
                        const contextEnd = Math.min(code.length, match.index + 300);
                        const context = code.substring(match.index, contextEnd);
                        return !context.includes('sanitize') && 
                               !context.includes('escape') &&
                               !context.includes('validator');
                    },
                    severity: 'HIGH',
                    message: () => 'Unsanitized user input from request body. XSS vulnerability.',
                    fix: 'Sanitize input: const message = validator.escape(req.body.message || \'\');'
                },
                {
                    name: 'NO_CSRF_PROTECTION',
                    pattern: /app\.(post|put|delete|patch)\s*\(['"](.*?)['"],\s*async/g,
                    checkContext: (code, match) => {
                        // Check if CSRF middleware is applied
                        return !code.includes('csrfProtection') && 
                               !code.includes('csrf') &&
                               !code.includes('_csrf');
                    },
                    severity: 'MEDIUM',
                    message: (match) => `No CSRF protection on ${match[1].toUpperCase()} ${match[2]}. Vulnerable to cross-site attacks.`,
                    fix: 'Add CSRF middleware: app.post(\'/route\', csrfProtection, async (req, res) => ...)'
                },
                {
                    name: 'SQL_INJECTION_RISK',
                    pattern: /query\s*\(\s*[`'"].*?\$\{|query\s*\(\s*["'].*?\+/g,
                    checkContext: (code, match) => true,
                    severity: 'CRITICAL',
                    message: () => 'SQL injection vulnerability! Using string interpolation in query.',
                    fix: 'Use parameterized queries: pool.query(\'SELECT * FROM users WHERE id = $1\', [userId])'
                }
            ],

            // PHP patterns
            php: [
                {
                    name: 'XSS_VULNERABILITY',
                    pattern: /\$_(POST|GET|REQUEST)\[['"]\w+['"]\](?!.*htmlspecialchars)/g,
                    checkContext: (code, match) => {
                        const lineEnd = code.indexOf(';', match.index);
                        const statement = code.substring(match.index, lineEnd);
                        return !statement.includes('htmlspecialchars') &&
                               !statement.includes('htmlentities') &&
                               !statement.includes('filter_var');
                    },
                    severity: 'HIGH',
                    message: (match) => `Unsanitized ${match[1]} variable. XSS vulnerability.`,
                    fix: 'Sanitize output: htmlspecialchars($_POST[\'field\'], ENT_QUOTES, \'UTF-8\')'
                },
                {
                    name: 'SQL_INJECTION_PHP',
                    pattern: /query\s*\(\s*["'].*?\.?\s*\$(?!stmt)/g,
                    checkContext: (code, match) => {
                        const contextStart = Math.max(0, match.index - 100);
                        const context = code.substring(contextStart, match.index + 100);
                        return !context.includes('prepare') && 
                               !context.includes('mysqli_real_escape_string');
                    },
                    severity: 'CRITICAL',
                    message: () => 'SQL injection vulnerability! Using variable concatenation in query.',
                    fix: 'Use prepared statements: $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?"); $stmt->execute([$id]);'
                },
                {
                    name: 'NO_CSRF_TOKEN_PHP',
                    pattern: /<form[^>]*method\s*=\s*["']post["'][^>]*>/gi,
                    checkContext: (code, match) => {
                        const formEnd = code.indexOf('</form>', match.index);
                        const formContent = code.substring(match.index, formEnd);
                        return !formContent.includes('csrf_token') &&
                               !formContent.includes('token') &&
                               !formContent.includes('_token');
                    },
                    severity: 'HIGH',
                    message: () => 'POST form without CSRF token. Vulnerable to cross-site request forgery.',
                    fix: 'Add CSRF token: <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[\'csrf_token\']; ?>">'
                },
                {
                    name: 'INSECURE_SESSION',
                    pattern: /session_start\s*\(\s*\)/g,
                    checkContext: (code, match) => {
                        const contextStart = Math.max(0, match.index - 200);
                        const context = code.substring(contextStart, match.index);
                        return !context.includes('session_start([') &&
                               !context.includes('cookie_httponly');
                    },
                    severity: 'MEDIUM',
                    message: () => 'Insecure session configuration. Missing security flags.',
                    fix: 'Use secure config: session_start([\'cookie_httponly\' => true, \'cookie_secure\' => true, \'cookie_samesite\' => \'Strict\']);'
                },
                {
                    name: 'WEAK_PASSWORD_HASHING',
                    pattern: /(?:md5|sha1)\s*\(\s*\$.*?password/gi,
                    checkContext: (code, match) => true,
                    severity: 'CRITICAL',
                    message: () => 'Weak password hashing algorithm (MD5/SHA1). Use bcrypt/Argon2.',
                    fix: 'Use password_hash: $hash = password_hash($password, PASSWORD_BCRYPT);'
                }
            ]
        };
    }

    /**
     * Scan code for vulnerabilities
     */
    scan(fileContent, filePath) {
        const vulnerabilities = [];
        const fileType = this.getFileType(filePath);
        
        if (!this.patterns[fileType]) {
            return vulnerabilities; // Unsupported file type
        }

        const patterns = this.patterns[fileType];

        patterns.forEach(pattern => {
            let match;
            const regex = new RegExp(pattern.pattern);
            
            // Reset regex state
            regex.lastIndex = 0;
            
            while ((match = regex.exec(fileContent)) !== null) {
                // Check context if needed
                if (pattern.checkContext && !pattern.checkContext(fileContent, match)) {
                    continue;
                }

                const lineNumber = this.getLineNumber(fileContent, match.index);
                
                vulnerabilities.push({
                    type: pattern.name,
                    severity: pattern.severity,
                    line: lineNumber,
                    message: typeof pattern.message === 'function' 
                        ? pattern.message(match) 
                        : pattern.message,
                    fix: pattern.fix,
                    code: this.getCodeSnippet(fileContent, lineNumber)
                });
            }
        });

        return vulnerabilities;
    }

    /**
     * Determine file type from path
     */
    getFileType(filePath) {
        if (filePath.endsWith('.js') || filePath.endsWith('.ts')) {
            return 'javascript';
        } else if (filePath.endsWith('.php')) {
            return 'php';
        }
        return 'unknown';
    }

    /**
     * Get line number from character index
     */
    getLineNumber(content, index) {
        return content.substring(0, index).split('\n').length;
    }

    /**
     * Get code snippet around line number
     */
    getCodeSnippet(content, lineNumber, contextLines = 2) {
        const lines = content.split('\n');
        const start = Math.max(0, lineNumber - contextLines - 1);
        const end = Math.min(lines.length, lineNumber + contextLines);
        
        return lines.slice(start, end).join('\n');
    }

    /**
     * Calculate security score (0-100)
     */
    calculateSecurityScore(vulnerabilities) {
        if (vulnerabilities.length === 0) return 100;

        const weights = {
            'CRITICAL': 20,
            'HIGH': 10,
            'MEDIUM': 5,
            'LOW': 2
        };

        const totalPenalty = vulnerabilities.reduce((sum, vuln) => {
            return sum + (weights[vuln.severity] || 0);
        }, 0);

        return Math.max(0, 100 - totalPenalty);
    }
}

module.exports = SecurityScanner;
