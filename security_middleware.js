// 🛡️ SECURITY MIDDLEWARE - Add to server4_app.js or main server file
// Install: npm install helmet express-rate-limit validator csurf cookie-parser

const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const cookieParser = require('cookie-parser');
const csurf = require('csurf');

// ====================  HELMET (SECURITY HEADERS) ====================

app.use(helmet({
    contentSecurityPolicy: {
        directives: {
            defaultSrc: ["'self'"],
            scriptSrc: ["'self'", "'unsafe-inline'", "cdn.jsdelivr.net", "cdn.tailwindcss.com", "unpkg.com"],
            styleSrc: ["'self'", "'unsafe-inline'", "fonts.googleapis.com"],
            imgSrc: ["'self'", "data:", "https:", "blob:"],
            connectSrc: ["'self'", "https://ainiflow.com"],
            fontSrc: ["'self'", "fonts.gstatic.com"],
            objectSrc: ["'none'"],
            mediaSrc: ["'self'", "blob:"],
            frameSrc: ["'none'"]
        }
    },
    hsts: {
        maxAge: 31536000,  // 1 year
        includeSubDomains: true,
        preload: true
    },
    frameguard: {
        action: 'deny'  // Prevent clickjacking
    },
    noSniff: true,  // Prevent MIME type sniffing
    xssFilter: true  // Enable XSS filter
}));

// ====================  REQUEST SIZE LIMITS ====================

app.use(express.json({ limit: '10kb' }));  // Max 10KB JSON
app.use(express.urlencoded({ extended: true, limit: '10kb' }));

// ====================  CSRF PROTECTION ====================

app.use(cookieParser());

// CSRF token middleware (apply to all POST/PUT/DELETE routes)
const csrfProtection = csurf({ 
    cookie: {
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',  // HTTPS only in production
        sameSite: 'strict'
    }
});

// CSRF token endpoint (call this from frontend to get token)
app.get('/api/csrf-token', csrfProtection, (req, res) => {
    res.json({ csrfToken: req.csrfToken() });
});

// Add CSRF to all state-changing routes
app.use('/api/social/swipe', csrfProtection);
app.use('/api/social/friend-request', csrfProtection);

// ====================  GLOBAL RATE LIMITER ====================

const globalLimiter = rateLimit({
    windowMs: 15 * 60 * 1000,  // 15 minutes
    max: 200,  // 200 requests per 15 min
    message: { error: 'Too many requests from this IP, please try again later' },
    standardHeaders: true,
    legacyHeaders: false,
    skip: (req) => {
        // Skip rate limiting for health checks
        return req.path === '/health' || req.path === '/metrics';
    }
});

app.use(globalLimiter);

// ====================  SESSION SECURITY ====================

// If using express-session:
const session = require('express-session');

app.use(session({
    secret: process.env.SESSION_SECRET || 'your-secret-key-change-this',  // Use env variable
    resave: false,
    saveUninitialized: false,
    name: 'sessionId',  // Don't use default 'connect.sid'
    cookie: {
        httpOnly: true,  // Prevent XSS access to cookie
        secure: process.env.NODE_ENV === 'production',  // HTTPS only in production
        sameSite: 'strict',  // Prevent CSRF
        maxAge: 24 * 60 * 60 * 1000  // 24 hours
    }
}));

// ====================  ERROR HANDLING (HIDE STACK TRACES) ====================

// Custom error handler (add at end of all routes)
app.use((err, req, res, next) => {
    console.error('Error:', err);
    
    // CSRF token errors
    if (err.code === 'EBADCSRFTOKEN') {
        return res.status(403).json({ error: 'Invalid security token' });
    }
    
    // Don't expose stack traces in production
    if (process.env.NODE_ENV === 'production') {
        res.status(err.status || 500).json({ 
            error: 'An error occurred' 
        });
    } else {
        // Show details in development
        res.status(err.status || 500).json({ 
            error: err.message,
            stack: err.stack
        });
    }
});

// ====================  REQUEST LOGGING (SECURITY AUDIT) ====================

app.use((req, res, next) => {
    const start = Date.now();
    
    res.on('finish', () => {
        const duration = Date.now() - start;
        
        // Log suspicious activity
        if (res.statusCode === 429) {
            console.warn(`[RATE LIMIT] ${req.ip} - ${req.method} ${req.path}`);
        }
        
        if (res.statusCode === 403) {
            console.warn(`[FORBIDDEN] ${req.ip} - ${req.method} ${req.path}`);
        }
        
        if (duration > 5000) {
            console.warn(`[SLOW REQUEST] ${duration}ms - ${req.method} ${req.path}`);
        }
    });
    
    next();
});

// ====================  IP BLOCKING (OPTIONAL) ====================

// Block known malicious IPs
const blockedIPs = new Set([
    // Add IPs to block here
]);

app.use((req, res, next) => {
    const clientIP = req.ip || req.connection.remoteAddress;
    
    if (blockedIPs.has(clientIP)) {
        console.warn(`[BLOCKED IP] ${clientIP} attempted access`);
        return res.status(403).json({ error: 'Access denied' });
    }
    
    next();
});

module.exports = { csrfProtection, globalLimiter };
