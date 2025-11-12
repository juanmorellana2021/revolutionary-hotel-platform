/**
 * Auto-Fixer - Generates secure versions of vulnerable code
 * Based on your security hardening patterns
 */

class AutoFixer {
    constructor() {
        this.templates = {
            javascript: {
                rate_limiter_import: `const rateLimit = require('express-rate-limit');\n`,
                
                rate_limiter_definition: `
// Rate limiter: {MAX} requests per {WINDOW} minutes
const {NAME}Limiter = rateLimit({
    windowMs: {WINDOW} * 60 * 1000,
    max: {MAX},
    message: { error: 'Too many requests, please try again later' },
    standardHeaders: true,
    legacyHeaders: false
});\n`,
                
                input_sanitization: `const validator = require('validator');\nconst sanitizeInput = (input, maxLength = 500) => validator.escape(String(input || '').substring(0, maxLength));\n`,
                
                csrf_middleware: `const csurf = require('csurf');\nconst csrfProtection = csurf({ cookie: { httpOnly: true, secure: process.env.NODE_ENV === 'production', sameSite: 'strict' } });\n`
            },
            
            php: {
                secure_session: `session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true
]);\n`,
                
                csrf_token_generation: `if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}\n`,
                
                csrf_token_validation: `if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid security token');
}\n`
            }
        };
    }

    /**
     * Generate fixed version of code
     */
    async generateFixedVersion(fileContent, filePath, vulnerabilities) {
        const fileType = this.getFileType(filePath);
        let fixedCode = fileContent;

        if (fileType === 'javascript') {
            fixedCode = this.fixJavaScript(fixedCode, vulnerabilities);
        } else if (fileType === 'php') {
            fixedCode = this.fixPHP(fixedCode, vulnerabilities);
        }

        return fixedCode;
    }

    /**
     * Fix JavaScript vulnerabilities
     */
    fixJavaScript(code, vulnerabilities) {
        let fixed = code;
        const hasRateLimitIssues = vulnerabilities.some(v => v.type === 'NO_RATE_LIMIT');
        const hasSanitizationIssues = vulnerabilities.some(v => v.type === 'NO_INPUT_SANITIZATION');
        const hasCsrfIssues = vulnerabilities.some(v => v.type === 'NO_CSRF_PROTECTION');
        const hasUnboundedLimit = vulnerabilities.some(v => v.type === 'UNBOUNDED_LIMIT');

        // Add imports at top if needed
        let imports = '';
        if (hasRateLimitIssues && !fixed.includes('express-rate-limit')) {
            imports += this.templates.javascript.rate_limiter_import;
        }
        if (hasSanitizationIssues && !fixed.includes('validator')) {
            imports += `const validator = require('validator');\n`;
        }
        if (hasCsrfIssues && !fixed.includes('csurf')) {
            imports += `const csurf = require('csurf');\nconst cookieParser = require('cookie-parser');\n`;
        }

        if (imports) {
            // Add after other requires
            const lastRequire = fixed.lastIndexOf('require(');
            if (lastRequire !== -1) {
                const lineEnd = fixed.indexOf('\n', lastRequire);
                fixed = fixed.substring(0, lineEnd + 1) + '\n' + imports + fixed.substring(lineEnd + 1);
            } else {
                fixed = imports + '\n' + fixed;
            }
        }

        // Fix unbounded limits
        if (hasUnboundedLimit) {
            fixed = fixed.replace(
                /(const|let|var)\s+(limit)\s*=\s*parseInt\(([^)]+)\)\s*\|\|\s*(\d+)/g,
                '$1 $2 = Math.min(parseInt($3) || $4, 100)'
            );
        }

        // Fix SQL injection (string interpolation)
        fixed = fixed.replace(
            /query\s*\(\s*`([^`]*)\$\{([^}]+)\}([^`]*)`/g,
            (match, before, variable, after) => {
                // Convert to parameterized query
                const paramIndex = (before.match(/\$/g) || []).length + 1;
                return `query(\`${before}$${paramIndex}${after}\`, [${variable}])`;
            }
        );

        // Add rate limiters for endpoints
        if (hasRateLimitIssues) {
            // Add limiter definitions before first route
            const firstRoute = fixed.search(/app\.(get|post|put|delete|patch)/);
            if (firstRoute !== -1) {
                const limiterDef = this.templates.javascript.rate_limiter_definition
                    .replace(/{NAME}/g, 'api')
                    .replace(/{MAX}/g, '100')
                    .replace(/{WINDOW}/g, '15');
                
                fixed = fixed.substring(0, firstRoute) + limiterDef + fixed.substring(firstRoute);
            }

            // Apply limiters to POST/PUT/DELETE routes
            fixed = fixed.replace(
                /app\.(post|put|delete|patch)\s*\(\s*['"]([^'"]+)['"]\s*,\s*async/g,
                'app.$1(\'$2\', apiLimiter, async'
            );
        }

        // Add input sanitization
        if (hasSanitizationIssues) {
            // Add sanitization helper
            const sanitizationHelper = this.templates.javascript.input_sanitization;
            const firstFunction = fixed.search(/async\s+\(/);
            if (firstFunction !== -1) {
                fixed = fixed.substring(0, firstFunction) + sanitizationHelper + '\n' + fixed.substring(firstFunction);
            }

            // Sanitize message fields
            fixed = fixed.replace(
                /const\s+\{\s*([^}]*message[^}]*)\}\s*=\s*req\.body/g,
                (match, fields) => {
                    const fieldList = fields.split(',').map(f => f.trim());
                    const sanitized = fieldList.map(field => {
                        if (field.includes('message')) {
                            return `${field}: sanitizeInput(req.body.message)`;
                        }
                        return field;
                    }).join(', ');
                    return `const { ${sanitized} } = req.body`;
                }
            );
        }

        // Add CSRF protection
        if (hasCsrfIssues && !fixed.includes('csrfProtection')) {
            const csrfSetup = this.templates.javascript.csrf_middleware;
            const appUse = fixed.indexOf('app.use(');
            if (appUse !== -1) {
                const lineEnd = fixed.indexOf('\n', appUse);
                fixed = fixed.substring(0, lineEnd + 1) + csrfSetup + '\n' + fixed.substring(lineEnd + 1);
            }
        }

        return fixed;
    }

    /**
     * Fix PHP vulnerabilities
     */
    fixPHP(code, vulnerabilities) {
        let fixed = code;
        const hasXssIssues = vulnerabilities.some(v => v.type === 'XSS_VULNERABILITY');
        const hasSessionIssues = vulnerabilities.some(v => v.type === 'INSECURE_SESSION');
        const hasCsrfIssues = vulnerabilities.some(v => v.type === 'NO_CSRF_TOKEN_PHP');
        const hasSqlInjection = vulnerabilities.some(v => v.type === 'SQL_INJECTION_PHP');

        // Fix insecure sessions
        if (hasSessionIssues) {
            fixed = fixed.replace(
                /session_start\s*\(\s*\)/g,
                this.templates.php.secure_session.trim()
            );
        }

        // Add CSRF token generation after session_start
        if (hasCsrfIssues) {
            const sessionStart = fixed.indexOf('session_start');
            if (sessionStart !== -1) {
                const lineEnd = fixed.indexOf('\n', sessionStart);
                const nextLine = fixed.indexOf('\n', lineEnd + 1);
                fixed = fixed.substring(0, nextLine + 1) + 
                       this.templates.php.csrf_token_generation + 
                       fixed.substring(nextLine + 1);
            }

            // Add CSRF validation in POST handling
            const postCheck = fixed.indexOf('if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\')');
            if (postCheck !== -1) {
                const blockStart = fixed.indexOf('{', postCheck);
                fixed = fixed.substring(0, blockStart + 1) + 
                       '\n    ' + this.templates.php.csrf_token_validation.trim().replace(/\n/g, '\n    ') + 
                       '\n    ' + fixed.substring(blockStart + 1);
            }

            // Add CSRF token to forms
            fixed = fixed.replace(
                /(<form[^>]*>)/gi,
                '$1\n    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION[\'csrf_token\'], ENT_QUOTES, \'UTF-8\'); ?>">'
            );
        }

        // Fix XSS vulnerabilities
        if (hasXssIssues) {
            // Wrap $_POST/$_GET in htmlspecialchars for output
            fixed = fixed.replace(
                /(value|echo)\s*=?\s*["']?\s*<\?php\s+echo\s+\$_(POST|GET|REQUEST)\[([^\]]+)\];?\s*\?>/g,
                '$1="<?php echo htmlspecialchars($_$2[$3] ?? \'\', ENT_QUOTES, \'UTF-8\'); ?>"'
            );
        }

        // Fix SQL injection
        if (hasSqlInjection) {
            // This is complex - add comment suggesting prepared statements
            fixed = '<?php\n// TODO: Convert to prepared statements to fix SQL injection vulnerabilities\n' +
                   '// Example: $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");\n' +
                   '//          $stmt->execute([$id]);\n\n' + 
                   fixed.substring(fixed.indexOf('<?php') + 5);
        }

        // Fix weak password hashing
        fixed = fixed.replace(
            /(md5|sha1)\s*\(\s*\$.*?password.*?\)/gi,
            'password_hash($password, PASSWORD_BCRYPT)'
        );

        return fixed;
    }

    /**
     * Get file type from path
     */
    getFileType(filePath) {
        if (filePath.endsWith('.js') || filePath.endsWith('.ts')) {
            return 'javascript';
        } else if (filePath.endsWith('.php')) {
            return 'php';
        }
        return 'unknown';
    }
}

module.exports = AutoFixer;
