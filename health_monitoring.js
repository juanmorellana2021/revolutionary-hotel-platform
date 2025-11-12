// Health Check & Monitoring Endpoints for AiniFlow
// Add to index.js

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
    console.error('Database health check failed:', error.message);
  }
  
  // Check Redis
  try {
    await redisClient.ping();
    health.services.redis = 'healthy';
  } catch (error) {
    health.services.redis = 'unhealthy';
    health.status = 'degraded';
    console.error('Redis health check failed:', error.message);
  }
  
  // Return appropriate status code
  const statusCode = health.status === 'healthy' ? 200 : 503;
  res.status(statusCode).json(health);
});

// Metrics endpoint for monitoring
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

// Ready check (for Kubernetes/Docker)
app.get('/ready', async (req, res) => {
  try {
    await pool.query('SELECT 1');
    res.status(200).send('OK');
  } catch (error) {
    res.status(503).send('Not Ready');
  }
});

// Liveness check
app.get('/live', (req, res) => {
  res.status(200).send('OK');
});
