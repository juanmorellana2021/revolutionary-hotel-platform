/**
 * API Design Scanner
 * Detects API design anti-patterns and REST violations
 */

class APIScanner {
    constructor() {
        this.patterns = [
            {
                id: 'NON_RESTFUL_ENDPOINT',
                name: 'Non-RESTful Endpoint',
                severity: 'MEDIUM',
                regex: /(?:app|router)\.(get|post|put|delete)\s*\(['"]\/api\/(?:get|create|update|delete|add|remove)/gi,
                message: 'API endpoint uses verbs instead of nouns (REST violation)',
                recommendation: 'Use HTTP methods with resource nouns: GET /users not GET /getUsers',
                example: `
// Bad - Verbs in URLs
app.get('/api/getUsers', ...);
app.post('/api/createUser', ...);
app.put('/api/updateUser/:id', ...);

// Good - RESTful
app.get('/api/users', ...);        // List
app.get('/api/users/:id', ...);    // Get one
app.post('/api/users', ...);       // Create
app.put('/api/users/:id', ...);    // Update
app.delete('/api/users/:id', ...); // Delete`
            },
            {
                id: 'MISSING_VALIDATION',
                name: 'Missing Input Validation',
                severity: 'CRITICAL',
                detect: (code) => {
                    const hasPost = /app\.post|router\.post/.test(code);
                    const hasValidation = /validate|joi\.|yup\.|check\(|sanitize/.test(code);
                    return hasPost && !hasValidation;
                },
                message: 'POST/PUT endpoint without input validation',
                recommendation: 'Validate all inputs using Joi, Yup, or express-validator',
                example: `
// Bad - No validation
app.post('/api/users', (req, res) => {
    const user = req.body; // Trust user input?!
    db.save(user);
});

// Good - With validation
const userSchema = Joi.object({
    name: Joi.string().min(3).max(50).required(),
    email: Joi.string().email().required(),
    age: Joi.number().min(18).max(120)
});

app.post('/api/users', (req, res) => {
    const { error, value } = userSchema.validate(req.body);
    if (error) return res.status(400).json({ error: error.details });
    
    db.save(value);
});`
            },
            {
                id: 'INCONSISTENT_RESPONSE_FORMAT',
                name: 'Inconsistent Response Format',
                severity: 'MEDIUM',
                detect: (code) => {
                    const hasSuccess = /res\.json\s*\(\s*{[^}]*success\s*:/.test(code);
                    const hasData = /res\.json\s*\(\s*{[^}]*data\s*:/.test(code);
                    const hasBare = /res\.json\s*\(\s*(?!{.*(?:success|data|error)\s*:)/.test(code);
                    return [hasSuccess, hasData, hasBare].filter(Boolean).length > 1;
                },
                message: 'Inconsistent API response formats across endpoints',
                recommendation: 'Standardize response format: { success, data, error }',
                example: `
// Bad - Inconsistent formats
app.get('/users', (req, res) => {
    res.json(users); // Bare array
});

app.get('/posts', (req, res) => {
    res.json({ data: posts }); // Wrapped
});

app.get('/comments', (req, res) => {
    res.json({ success: true, result: comments }); // Different keys
});

// Good - Consistent format
const sendSuccess = (res, data) => {
    res.json({ success: true, data, error: null });
};

const sendError = (res, error, status = 400) => {
    res.status(status).json({ success: false, data: null, error });
};

app.get('/users', (req, res) => {
    sendSuccess(res, users);
});`
            },
            {
                id: 'MISSING_STATUS_CODES',
                name: 'Improper HTTP Status Codes',
                severity: 'MEDIUM',
                regex: /res\.json\([^)]*\)(?![\s\S]*res\.status)/g,
                message: 'Response without explicit status code',
                recommendation: 'Always set proper HTTP status codes',
                example: `
// Bad - Defaults to 200
res.json({ error: 'Not found' });

// Good - Explicit status
res.status(404).json({ error: 'Not found' });
res.status(201).json({ data: newUser });
res.status(400).json({ error: 'Invalid input' });`
            },
            {
                id: 'NO_RATE_LIMITING',
                name: 'Missing Rate Limiting',
                severity: 'HIGH',
                detect: (code) => {
                    const hasEndpoints = /app\.(get|post|put|delete)/.test(code);
                    const hasRateLimit = /rateLimit|expressRateLimit|limiter/.test(code);
                    return hasEndpoints && !hasRateLimit;
                },
                message: 'API endpoints without rate limiting',
                recommendation: 'Add rate limiting to prevent abuse and DoS',
                example: `
// Add rate limiting
const rateLimit = require('express-rate-limit');

const apiLimiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100, // limit each IP to 100 requests per windowMs
    message: 'Too many requests, please try again later'
});

app.use('/api/', apiLimiter);`
            },
            {
                id: 'MISSING_VERSIONING',
                name: 'No API Versioning',
                severity: 'MEDIUM',
                regex: /app\.(get|post|put|delete)\s*\(['"]\/api\/(?!v\d)/gi,
                message: 'API endpoint without version number',
                recommendation: 'Version your API: /api/v1/users for easier updates',
                example: `
// Bad - No version
app.get('/api/users', ...);

// Good - Versioned
app.get('/api/v1/users', ...);
app.get('/api/v2/users', ...); // New version alongside old`
            },
            {
                id: 'MISSING_PAGINATION',
                name: 'List Endpoint Without Pagination',
                severity: 'HIGH',
                detect: (code) => {
                    const hasListEndpoint = /app\.get\s*\(['"]\/api\/\w+['"]\s*,/.test(code);
                    const hasPagination = /limit|offset|page|perPage/.test(code);
                    return hasListEndpoint && !hasPagination;
                },
                message: 'List endpoint without pagination - could return huge dataset',
                recommendation: 'Add pagination: ?page=1&limit=50',
                example: `
// Bad - Returns all records
app.get('/api/users', async (req, res) => {
    const users = await db.query('SELECT * FROM users');
    res.json(users); // Could be 1 million records!
});

// Good - Paginated
app.get('/api/users', async (req, res) => {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 50;
    const offset = (page - 1) * limit;
    
    const users = await db.query(
        'SELECT * FROM users LIMIT ? OFFSET ?',
        [limit, offset]
    );
    
    const total = await db.query('SELECT COUNT(*) as count FROM users');
    
    res.json({
        data: users,
        pagination: {
            page,
            limit,
            total: total[0].count,
            totalPages: Math.ceil(total[0].count / limit)
        }
    });
});`
            },
            {
                id: 'MISSING_CORS',
                name: 'Missing CORS Configuration',
                severity: 'MEDIUM',
                detect: (code) => {
                    const hasAPI = /app\.(get|post|put|delete).*\/api\//.test(code);
                    const hasCORS = /cors\(\)|Access-Control-Allow/.test(code);
                    return hasAPI && !hasCORS;
                },
                message: 'API without CORS configuration',
                recommendation: 'Configure CORS for cross-origin requests',
                example: `
const cors = require('cors');

// Development - Allow all
app.use(cors());

// Production - Specific origins
app.use(cors({
    origin: ['https://yourdomain.com'],
    methods: ['GET', 'POST', 'PUT', 'DELETE'],
    credentials: true
}));`
            },
            {
                id: 'NO_ERROR_MIDDLEWARE',
                name: 'Missing Error Handling Middleware',
                severity: 'HIGH',
                detect: (code) => {
                    const hasEndpoints = /app\.(get|post|put|delete)/.test(code);
                    const hasErrorHandler = /app\.use\s*\(\s*\([^,]*,\s*[^,]*,\s*[^,]*,\s*next\s*\)/.test(code);
                    return hasEndpoints && !hasErrorHandler;
                },
                message: 'No global error handling middleware',
                recommendation: 'Add error handler middleware at the end of middleware chain',
                example: `
// Add at the END of your middleware
app.use((err, req, res, next) => {
    console.error('Error:', err);
    
    res.status(err.status || 500).json({
        success: false,
        error: {
            message: err.message,
            ...(process.env.NODE_ENV === 'development' && { stack: err.stack })
        }
    });
});`
            },
            {
                id: 'SENSITIVE_DATA_IN_RESPONSE',
                name: 'Sensitive Data Leak',
                severity: 'CRITICAL',
                regex: /res\.json\([^)]*(?:password|secret|token|apiKey|privateKey)/gi,
                message: 'Potentially sensitive data in API response',
                recommendation: 'Never return passwords, tokens, or secrets in responses',
                example: `
// Bad - Leaking sensitive data
res.json({ user: userFromDB }); // Includes password hash!

// Good - Strip sensitive fields
const sanitizeUser = (user) => {
    const { password, resetToken, ...safe } = user;
    return safe;
};

res.json({ user: sanitizeUser(userFromDB) });`
            },
            {
                id: 'NO_COMPRESSION',
                name: 'Missing Response Compression',
                severity: 'LOW',
                detect: (code) => {
                    const hasAPI = /app\.(get|post)/.test(code);
                    const hasCompression = /compression\(\)/.test(code);
                    return hasAPI && !hasCompression;
                },
                message: 'API responses not compressed',
                recommendation: 'Use compression middleware to reduce bandwidth',
                example: `
const compression = require('compression');

app.use(compression());`
            }
        ];
    }

    scan(code, filePath = '') {
        const issues = [];

        for (const pattern of this.patterns) {
            let matches = [];
            
            if (pattern.detect) {
                if (pattern.detect(code)) {
                    matches = [{ index: 0, 0: code.substring(0, 100) }];
                }
            } else if (pattern.regex) {
                matches = Array.from(code.matchAll(pattern.regex));
            }

            for (const match of matches) {
                const lineNumber = this.getLineNumber(code, match.index);
                
                issues.push({
                    id: pattern.id,
                    type: 'API_DESIGN',
                    name: pattern.name,
                    severity: pattern.severity,
                    message: pattern.message,
                    recommendation: pattern.recommendation,
                    line: lineNumber,
                    column: this.getColumnNumber(code, match.index),
                    code: match[0] ? match[0].substring(0, 100) + '...' : '',
                    autoFix: pattern.autoFix || false,
                    example: pattern.example,
                    file: filePath
                });
            }
        }

        return issues;
    }

    getLineNumber(code, index) {
        return code.substring(0, index).split('\n').length;
    }

    getColumnNumber(code, index) {
        const lines = code.substring(0, index).split('\n');
        return lines[lines.length - 1].length + 1;
    }
}

module.exports = APIScanner;
