/**
 * Error Handling Scanner
 * Detects missing error handling patterns from memory-bank/ERROR_HANDLING_BEST_PRACTICES.md
 */

class ErrorHandlingScanner {
    constructor() {
        this.patterns = [
            {
                id: 'MISSING_TRY_CATCH',
                name: 'Missing Try/Catch',
                severity: 'HIGH',
                regex: /async\s+function\s+\w+\s*\([^)]*\)\s*{(?:(?!try\s*{).)*}/gs,
                message: 'Async function missing try/catch block',
                recommendation: 'Wrap async operations in try/catch to handle errors gracefully',
                autoFix: true,
                example: `
async handleAction() {
    this.loading = true;
    this.error = null;
    
    try {
        const response = await fetch('/api/endpoint', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ data: this.data })
        });
        
        if (!response.ok) {
            throw new Error(\`HTTP \${response.status}: \${response.statusText}\`);
        }
        
        const data = await response.json();
        this.result = data;
        
    } catch (error) {
        console.error('Error:', error);
        this.error = this.getUserFriendlyError(error);
    } finally {
        this.loading = false;
    }
}`
            },
            {
                id: 'NO_ERROR_LOGGING',
                name: 'Missing Error Logging',
                severity: 'MEDIUM',
                regex: /catch\s*\([^)]*\)\s*{(?:(?!console\.error|logger\.).)*}/gs,
                message: 'Catch block without error logging',
                recommendation: 'Always log errors for debugging: console.error() or logger.error()',
                autoFix: true
            },
            {
                id: 'GENERIC_ERROR_MESSAGE',
                name: 'Generic Error Message',
                severity: 'MEDIUM',
                regex: /throw\s+new\s+Error\s*\(\s*['"]error['"]\s*\)/gi,
                message: 'Generic error message - not user-friendly',
                recommendation: 'Provide specific, actionable error messages',
                autoFix: false
            },
            {
                id: 'UNHANDLED_PROMISE',
                name: 'Unhandled Promise Rejection',
                severity: 'HIGH',
                regex: /\.then\([^)]*\)(?!\s*\.catch)/g,
                message: 'Promise without .catch() handler',
                recommendation: 'Add .catch() to handle promise rejections',
                autoFix: true,
                example: `
// Bad
fetch('/api/endpoint').then(data => console.log(data));

// Good
fetch('/api/endpoint')
    .then(data => console.log(data))
    .catch(error => {
        console.error('Fetch failed:', error);
        showErrorMessage(error);
    });`
            },
            {
                id: 'NO_USER_FEEDBACK',
                name: 'No User Error Feedback',
                severity: 'MEDIUM',
                regex: /catch\s*\([^)]*\)\s*{(?:(?!alert|showError|this\.error|setError).)*console\.error/gs,
                message: 'Error logged but not shown to user',
                recommendation: 'Display user-friendly error message in UI',
                autoFix: false
            },
            {
                id: 'MISSING_FINALLY',
                name: 'Missing Finally Block',
                severity: 'LOW',
                regex: /try\s*{[^}]*}\s*catch\s*\([^)]*\)\s*{[^}]*}(?!\s*finally)/gs,
                message: 'Try/catch without finally block for cleanup',
                recommendation: 'Use finally block to reset loading states and cleanup resources',
                autoFix: true,
                example: `
try {
    this.loading = true;
    await doSomething();
} catch (error) {
    this.error = error.message;
} finally {
    this.loading = false; // Always cleanup
}`
            },
            {
                id: 'SWALLOWED_ERROR',
                name: 'Swallowed Error',
                severity: 'CRITICAL',
                regex: /catch\s*\([^)]*\)\s*{\s*}/g,
                message: 'Empty catch block - error silently swallowed',
                recommendation: 'Never use empty catch blocks. At minimum, log the error.',
                autoFix: true
            },
            {
                id: 'NO_HTTP_STATUS_CHECK',
                name: 'Missing HTTP Status Check',
                severity: 'HIGH',
                regex: /await\s+fetch\([^)]*\)(?![\s\S]*?if\s*\(\s*!?\s*response\.ok)/g,
                message: 'Fetch without checking response.ok',
                recommendation: 'Always check response.ok before processing data',
                autoFix: true,
                example: `
const response = await fetch('/api/endpoint');

if (!response.ok) {
    throw new Error(\`HTTP \${response.status}: \${response.statusText}\`);
}

const data = await response.json();`
            },
            {
                id: 'NO_NETWORK_ERROR_HANDLING',
                name: 'Missing Network Error Handling',
                severity: 'MEDIUM',
                regex: /catch\s*\(\s*(\w+)\s*\)\s*{(?:(?!network|fetch|connection).)*}/gis,
                message: 'No specific handling for network errors',
                recommendation: 'Detect and handle network errors separately with user-friendly messages',
                autoFix: false,
                example: `
getUserFriendlyError(error) {
    const message = error.message.toLowerCase();
    
    if (message.includes('failed to fetch') || message.includes('network')) {
        return 'Connection problem. Check your internet and try again.';
    }
    
    if (message.includes('401') || message.includes('unauthorized')) {
        return 'Session expired. Redirecting to login...';
    }
    
    if (message.includes('500')) {
        return 'Server error. Our team has been notified.';
    }
    
    return error.message || 'Something went wrong';
}`
            },
            {
                id: 'MISSING_RETRY_MECHANISM',
                name: 'No Retry Logic',
                severity: 'LOW',
                regex: /catch\s*\([^)]*\)\s*{(?:(?!retry|again).)*}/gis,
                message: 'Error handling without retry option',
                recommendation: 'Provide retry mechanism for transient failures',
                autoFix: false
            }
        ];
    }

    /**
     * Scan code for error handling issues
     */
    scan(code, filePath = '') {
        const issues = [];

        for (const pattern of this.patterns) {
            const matches = code.matchAll(pattern.regex);
            
            for (const match of matches) {
                const lineNumber = this.getLineNumber(code, match.index);
                
                issues.push({
                    id: pattern.id,
                    type: 'ERROR_HANDLING',
                    name: pattern.name,
                    severity: pattern.severity,
                    message: pattern.message,
                    recommendation: pattern.recommendation,
                    line: lineNumber,
                    column: this.getColumnNumber(code, match.index),
                    code: match[0].substring(0, 100) + '...',
                    autoFix: pattern.autoFix,
                    example: pattern.example,
                    file: filePath
                });
            }
        }

        return issues;
    }

    /**
     * Get line number from character index
     */
    getLineNumber(code, index) {
        return code.substring(0, index).split('\n').length;
    }

    /**
     * Get column number from character index
     */
    getColumnNumber(code, index) {
        const lines = code.substring(0, index).split('\n');
        return lines[lines.length - 1].length + 1;
    }

    /**
     * Generate auto-fix for error handling issues
     */
    generateFix(issue, code) {
        switch (issue.id) {
            case 'MISSING_TRY_CATCH':
                return this.addTryCatch(code, issue);
            
            case 'NO_ERROR_LOGGING':
                return this.addErrorLogging(code, issue);
            
            case 'UNHANDLED_PROMISE':
                return this.addCatchHandler(code, issue);
            
            case 'MISSING_FINALLY':
                return this.addFinallyBlock(code, issue);
            
            case 'SWALLOWED_ERROR':
                return this.addErrorLogging(code, issue);
            
            case 'NO_HTTP_STATUS_CHECK':
                return this.addStatusCheck(code, issue);
            
            default:
                return null;
        }
    }

    addTryCatch(code, issue) {
        const functionMatch = code.match(/async\s+function\s+(\w+)\s*\([^)]*\)\s*{/);
        if (!functionMatch) return null;

        const functionName = functionMatch[1];
        
        return `
async ${functionName}() {
    this.loading = true;
    this.error = null;
    
    try {
        // Your code here
        
    } catch (error) {
        console.error('Error in ${functionName}:', error);
        this.error = this.getUserFriendlyError(error);
    } finally {
        this.loading = false;
    }
}`;
    }

    addErrorLogging(code, issue) {
        return code.replace(
            /catch\s*\(([^)]*)\)\s*{/,
            'catch ($1) {\n        console.error(\'Error:\', $1);'
        );
    }

    addCatchHandler(code, issue) {
        return code.replace(
            /\.then\(([^)]*)\)/,
            '.then($1)\n    .catch(error => {\n        console.error(\'Promise rejected:\', error);\n        // Handle error\n    })'
        );
    }

    addFinallyBlock(code, issue) {
        return code.replace(
            /(catch\s*\([^)]*\)\s*{[^}]*})/,
            '$1\n    finally {\n        this.loading = false;\n        // Cleanup resources\n    }'
        );
    }

    addStatusCheck(code, issue) {
        return code.replace(
            /(const\s+response\s*=\s*await\s+fetch\([^)]*\);)/,
            `$1\n\nif (!response.ok) {\n    throw new Error(\`HTTP \${response.status}: \${response.statusText}\`);\n}`
        );
    }
}

module.exports = ErrorHandlingScanner;
