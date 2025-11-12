// Integration Guide - Add to index.js (or server4_app.js)

// 1. Import monitoring modules at the top
const { requestLogger, errorLogger, log } = require('./logging');

// 2. Add global metrics initialization (after imports)
global.requestCount = 0;
global.activeConnections = 0;
global.averageResponseTime = 0;
global.errorRate = 0;
global.cacheHits = 0;
global.cacheMisses = 0;

// 3. Add request logger middleware (BEFORE routes)
app.use(requestLogger);

// 4. Add health & metrics endpoints (BEFORE other routes)
// Health check endpoint
app.get('/health', async (req, res) => {
  const health = {
    status: 'healthy',
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    environment: process.env.NODE_ENV || 'development',
    version: '1.0.0',
    services: {
      database: 'unknown',
      redis: 'unknown',
    },
    memory: {
      used: Math.round(process.memoryUsage().heapUsed / 1024 / 1024),
      total: Math.round(process.memoryUsage().heapTotal / 1024 / 1024),
      unit: 'MB'
    }
  };
  
  // Check PostgreSQL
  try {
    await pool.query('SELECT 1');
    health.services.database = 'healthy';
  } catch (error) {
    health.services.database = 'unhealthy';
    health.status = 'degraded';
    log.error('Database health check failed', error);
  }
  
  // Check Redis
  try {
    await redisClient.ping();
    health.services.redis = 'healthy';
  } catch (error) {
    health.services.redis = 'unhealthy';
    health.status = 'degraded';
    log.error('Redis health check failed', error);
  }
  
  const statusCode = health.status === 'healthy' ? 200 : 503;
  res.status(statusCode).json(health);
});

// Metrics endpoint
app.get('/metrics', async (req, res) => {
  const metrics = {
    timestamp: new Date().toISOString(),
    server: {
      uptime: process.uptime(),
      memory: process.memoryUsage(),
      cpu: process.cpuUsage(),
    },
    api: {
      totalRequests: global.requestCount || 0,
      activeConnections: global.activeConnections || 0,
      averageResponseTime: global.averageResponseTime || 0,
      errorRate: global.errorRate || 0,
    },
    cache: {
      hits: global.cacheHits || 0,
      misses: global.cacheMisses || 0,
      hitRate: global.cacheHits ? 
        ((global.cacheHits / (global.cacheHits + global.cacheMisses)) * 100).toFixed(2) + '%' 
        : '0%'
    }
  };
  
  res.json(metrics);
});

// Monitoring dashboard
app.get('/monitoring', (req, res) => {
  res.sendFile(__dirname + '/monitoring-dashboard.html');
});

// 5. Add error logger middleware (AFTER all routes)
app.use(errorLogger);

// 6. Update cache operations to track hits/misses
// In getCached function:
async function getCached(key) {
  try {
    const data = await redisClient.get(key);
    if (data) {
      global.cacheHits = (global.cacheHits || 0) + 1;
      return JSON.parse(data);
    }
    global.cacheMisses = (global.cacheMisses || 0) + 1;
    return null;
  } catch (error) {
    global.cacheMisses = (global.cacheMisses || 0) + 1;
    log.error('Cache get failed', error, { key });
    return null;
  }
}

// 7. Add startup log
app.listen(PORT, () => {
  log.info('Server started successfully', { 
    port: PORT, 
    environment: process.env.NODE_ENV || 'development',
    nodeVersion: process.version
  });
});

// 8. Add graceful shutdown
process.on('SIGTERM', () => {
  log.info('SIGTERM signal received: closing HTTP server');
  server.close(() => {
    log.info('HTTP server closed');
    process.exit(0);
  });
});

// 9. Set up cron job for uptime monitoring (on VPS)
// Run: crontab -e
// Add: */5 * * * * /var/www/aini-platform/uptime-monitor.sh

// QUICK START:
// 1. npm install winston
// 2. Copy health & metrics endpoints to index.js
// 3. Add app.use(requestLogger) before routes
// 4. Add app.use(errorLogger) after routes
// 5. Test: curl http://localhost:3000/health
// 6. View dashboard: http://localhost:3000/monitoring
