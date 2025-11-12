# Error Handling Best Practices

## 🎯 Guiding Principles

1. **Fail Gracefully**: Never leave user with broken interface
2. **Inform Users**: Show clear, actionable error messages
3. **Log Everything**: Errors should be logged for debugging
4. **Preserve State**: Don't lose user data on errors
5. **Provide Recovery**: Give users a way to retry or go back

## 📱 Frontend Error Handling (Alpine.js)

### Basic Try/Catch Pattern
```javascript
async handleAction() {
    this.loading = true;
    this.error = null;
    
    try {
        const response = await fetch('/api/endpoint', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ data: this.data })
        });
        
        // Check HTTP status
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        // Check application-level errors
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Success!
        this.result = data;
        this.loading = false;
        
    } catch (error) {
        console.error('Error in handleAction:', error);
        
        // User-friendly error message
        this.error = this.getUserFriendlyError(error);
        this.loading = false;
    }
},

getUserFriendlyError(error) {
    const message = error.message.toLowerCase();
    
    // Network errors
    if (message.includes('failed to fetch') || message.includes('network')) {
        return 'Connection problem. Please check your internet and try again.';
    }
    
    // Authentication errors
    if (message.includes('401') || message.includes('unauthorized')) {
        setTimeout(() => window.location.href = 'login.html', 2000);
        return 'Session expired. Redirecting to login...';
    }
    
    // Server errors
    if (message.includes('500') || message.includes('internal server')) {
        return 'Server error. Our team has been notified. Please try again later.';
    }
    
    // Default: show the actual error message
    return error.message || 'Something went wrong. Please try again.';
}
```

### Error Display Component
```html
<!-- In your Alpine.js component -->
<div x-show="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i>
            <span x-text="error"></span>
        </div>
        <button @click="error = null" class="text-red-700">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <!-- Optional: Retry button -->
    <button @click="retryLastAction()" 
            class="mt-2 bg-red-600 text-white px-3 py-1 rounded text-sm">
        Try Again
    </button>
</div>
```

### Loading States
```html
<!-- Prevent double-clicks during loading -->
<button @click="handleAction()" 
        :disabled="loading"
        :class="loading ? 'opacity-50 cursor-not-allowed' : ''"
        class="bg-orange-500 text-white px-4 py-2 rounded">
    <span x-show="!loading">Submit</span>
    <span x-show="loading">
        <i class="fas fa-spinner fa-spin"></i> Processing...
    </span>
</button>
```

### Retry Logic
```javascript
maxRetries: 3,
retryCount: 0,

async loadDataWithRetry() {
    try {
        await this.loadData();
        this.retryCount = 0; // Reset on success
    } catch (error) {
        if (this.retryCount < this.maxRetries) {
            this.retryCount++;
            console.log(`Retry attempt ${this.retryCount}/${this.maxRetries}`);
            
            // Exponential backoff: 1s, 2s, 4s
            const delay = Math.pow(2, this.retryCount - 1) * 1000;
            setTimeout(() => this.loadDataWithRetry(), delay);
        } else {
            this.error = 'Failed after multiple attempts. Please refresh the page.';
        }
    }
}
```

## 🔧 Backend Error Handling (Node.js)

### Standard API Error Response
```javascript
// Create error response helper
function errorResponse(res, statusCode, message, details = null) {
    const response = {
        error: message,
        timestamp: new Date().toISOString()
    };
    
    if (details && process.env.NODE_ENV !== 'production') {
        response.details = details; // Only in development
    }
    
    return res.status(statusCode).json(response);
}

// Use in routes
app.post('/api/action', async (req, res) => {
    try {
        // Validate input
        if (!req.body.required_field) {
            return errorResponse(res, 400, 'Missing required field: required_field');
        }
        
        // Business logic
        const result = await performAction(req.body);
        
        res.json({ success: true, data: result });
        
    } catch (error) {
        console.error('Error in /api/action:', error);
        
        // Database errors
        if (error.code === '23505') { // PostgreSQL unique violation
            return errorResponse(res, 409, 'Record already exists');
        }
        
        if (error.code === '23503') { // Foreign key violation
            return errorResponse(res, 400, 'Referenced record not found');
        }
        
        // Default server error
        return errorResponse(res, 500, 'Internal server error', error.message);
    }
});
```

### Input Validation
```javascript
function validateSwipeRequest(body) {
    const errors = [];
    
    if (!body.user_phone || typeof body.user_phone !== 'string') {
        errors.push('Invalid user_phone');
    }
    
    if (!body.swiped_id || typeof body.swiped_id !== 'string') {
        errors.push('Invalid swiped_id');
    }
    
    if (!['left', 'right'].includes(body.direction)) {
        errors.push('Direction must be "left" or "right"');
    }
    
    if (errors.length > 0) {
        return { valid: false, errors };
    }
    
    return { valid: true };
}

// Use in route
app.post('/api/social/swipe', async (req, res) => {
    const validation = validateSwipeRequest(req.body);
    
    if (!validation.valid) {
        return errorResponse(res, 400, validation.errors.join(', '));
    }
    
    // Continue with request...
});
```

### Database Error Handling
```javascript
async function safeQuery(pool, query, params) {
    try {
        const result = await pool.query(query, params);
        return { success: true, data: result.rows };
        
    } catch (error) {
        console.error('Database query error:', error);
        console.error('Query:', query);
        console.error('Params:', params);
        
        return { 
            success: false, 
            error: error.message,
            code: error.code 
        };
    }
}

// Use in routes
const result = await safeQuery(pool, 'SELECT * FROM users WHERE phone = $1', [userPhone]);

if (!result.success) {
    return errorResponse(res, 500, 'Database error', result.error);
}

if (result.data.length === 0) {
    return errorResponse(res, 404, 'User not found');
}
```

### Async Error Wrapper
```javascript
// Wrap async route handlers
function asyncHandler(fn) {
    return (req, res, next) => {
        Promise.resolve(fn(req, res, next)).catch(next);
    };
}

// Use it
app.post('/api/action', asyncHandler(async (req, res) => {
    // No try/catch needed, errors automatically caught
    const result = await performAction(req.body);
    res.json({ success: true, data: result });
}));

// Global error handler
app.use((error, req, res, next) => {
    console.error('Unhandled error:', error);
    errorResponse(res, 500, 'Internal server error');
});
```

## 🗄️ Database Error Codes

### Common PostgreSQL Errors
```javascript
const PG_ERRORS = {
    '23505': 'Duplicate record',           // UNIQUE violation
    '23503': 'Referenced record not found', // FOREIGN KEY violation
    '23502': 'Required field missing',      // NOT NULL violation
    '42P01': 'Table does not exist',        // Undefined table
    '42703': 'Column does not exist',       // Undefined column
    '22P02': 'Invalid data type',           // Invalid text representation
};

function getDatabaseErrorMessage(error) {
    return PG_ERRORS[error.code] || 'Database error';
}
```

## 📊 Logging Best Practices

### Structured Logging
```javascript
function logError(context, error, additionalInfo = {}) {
    const logEntry = {
        timestamp: new Date().toISOString(),
        context,
        error: {
            message: error.message,
            stack: error.stack,
            code: error.code
        },
        ...additionalInfo
    };
    
    console.error(JSON.stringify(logEntry, null, 2));
    
    // In production, send to logging service
    // await sendToLoggingService(logEntry);
}

// Use it
try {
    await performAction();
} catch (error) {
    logError('api/social/swipe', error, {
        userId: req.body.user_phone,
        swipedId: req.body.swiped_id
    });
    
    return errorResponse(res, 500, 'Failed to process swipe');
}
```

## 🧪 Testing Error Scenarios

### Manual Test Cases
```bash
# Test 401 Unauthorized
curl -X POST http://localhost:3000/api/protected-endpoint \
  -H "Content-Type: application/json" \
  -d '{}'

# Test 400 Bad Request (missing field)
curl -X POST http://localhost:3000/api/action \
  -H "Content-Type: application/json" \
  -d '{"user_phone": "+123"}'

# Test 404 Not Found
curl http://localhost:3000/api/nonexistent

# Test 500 Server Error (simulate)
# Add temporary throw new Error() in route
```

## ✅ Error Handling Checklist

- [ ] All async functions wrapped in try/catch
- [ ] User-friendly error messages displayed
- [ ] Errors logged with context
- [ ] Loading states prevent double-clicks
- [ ] Network errors handled gracefully
- [ ] Authentication errors redirect to login
- [ ] Database errors don't expose schema
- [ ] Validation happens before database calls
- [ ] HTTP status codes are appropriate
- [ ] Retry logic for transient failures
- [ ] Fallback data for non-critical features
- [ ] Error boundaries prevent full page crashes

---

**Remember**: Good error handling is invisible when things work, but saves the day when they don't!
