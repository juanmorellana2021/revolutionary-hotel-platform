// Winston Structured Logging for AiniFlow
// npm install winston

const winston = require('winston');
const path = require('path');

// Define log format
const logFormat = winston.format.combine(
  winston.format.timestamp({ format: 'YYYY-MM-DD HH:mm:ss' }),
  winston.format.errors({ stack: true }),
  winston.format.json()
);

// Create logger instance
const logger = winston.createLogger({
  level: process.env.LOG_LEVEL || 'info',
  format: logFormat,
  defaultMeta: { service: 'ainiflow-api' },
  transports: [
    // Error logs
    new winston.transports.File({ 
      filename: 'logs/error.log', 
      level: 'error',
      maxsize: 5242880, // 5MB
      maxFiles: 5,
    }),
    // Combined logs
    new winston.transports.File({ 
      filename: 'logs/combined.log',
      maxsize: 5242880, // 5MB
      maxFiles: 5,
    }),
    // API request logs
    new winston.transports.File({ 
      filename: 'logs/api.log',
      level: 'http',
      maxsize: 5242880, // 5MB
      maxFiles: 5,
    }),
  ],
});

// Console logging in development
if (process.env.NODE_ENV !== 'production') {
  logger.add(new winston.transports.Console({
    format: winston.format.combine(
      winston.format.colorize(),
      winston.format.simple()
    )
  }));
}

// Request logging middleware
function requestLogger(req, res, next) {
  const start = Date.now();
  
  // Log request
  logger.http({
    type: 'request',
    method: req.method,
    url: req.url,
    ip: req.ip,
    userAgent: req.get('user-agent'),
    user: req.session?.user?.phone || 'anonymous',
  });
  
  // Log response when finished
  res.on('finish', () => {
    const duration = Date.now() - start;
    
    logger.http({
      type: 'response',
      method: req.method,
      url: req.url,
      status: res.statusCode,
      duration: `${duration}ms`,
      user: req.session?.user?.phone || 'anonymous',
    });
    
    // Update global metrics
    global.requestCount = (global.requestCount || 0) + 1;
    global.averageResponseTime = global.averageResponseTime 
      ? (global.averageResponseTime + duration) / 2 
      : duration;
    
    if (res.statusCode >= 400) {
      global.errorRate = ((global.errorRate || 0) + 1) / global.requestCount;
    }
  });
  
  next();
}

// Error logging middleware
function errorLogger(err, req, res, next) {
  logger.error({
    type: 'error',
    message: err.message,
    stack: err.stack,
    method: req.method,
    url: req.url,
    user: req.session?.user?.phone || 'anonymous',
    body: req.body,
  });
  
  // Don't expose internal errors to users
  res.status(err.status || 500).json({
    error: process.env.NODE_ENV === 'production' 
      ? 'Internal server error' 
      : err.message
  });
}

// Helper logging functions
const log = {
  info: (message, meta = {}) => logger.info({ message, ...meta }),
  warn: (message, meta = {}) => logger.warn({ message, ...meta }),
  error: (message, error, meta = {}) => logger.error({ 
    message, 
    error: error?.message, 
    stack: error?.stack,
    ...meta 
  }),
  debug: (message, meta = {}) => logger.debug({ message, ...meta }),
};

module.exports = {
  logger,
  requestLogger,
  errorLogger,
  log,
};

// Usage in index.js:
// const { requestLogger, errorLogger, log } = require('./logging');
// app.use(requestLogger);
// app.use(errorLogger); // Add after all routes
// log.info('Server started', { port: 3000 });
