/**
 * Database Scanner
 * Detects database performance issues and anti-patterns
 */

class DatabaseScanner {
    constructor() {
        this.patterns = [
            {
                id: 'N_PLUS_ONE_QUERY',
                name: 'N+1 Query Problem',
                severity: 'CRITICAL',
                detect: (code) => {
                    // Look for loops with queries inside
                    const loopWithQuery = /(?:for|while|forEach)\s*\([^)]*\)\s*{[^}]*(?:SELECT|query|find|get)[^}]*}/gis;
                    return loopWithQuery.test(code);
                },
                message: 'Query inside loop - classic N+1 problem',
                recommendation: 'Fetch all data in single query with JOIN or WHERE IN clause',
                example: `
// Bad - N+1 queries (1 + N database calls)
const users = await db.query('SELECT * FROM users');
for (const user of users) {
    const orders = await db.query('SELECT * FROM orders WHERE user_id = ?', [user.id]);
    user.orders = orders;
}

// Good - Single query with JOIN
const users = await db.query(\`
    SELECT users.*, orders.*
    FROM users
    LEFT JOIN orders ON users.id = orders.user_id
\`);

// Good - WHERE IN clause
const users = await db.query('SELECT * FROM users');
const userIds = users.map(u => u.id);
const orders = await db.query('SELECT * FROM orders WHERE user_id IN (?)', [userIds]);`
            },
            {
                id: 'MISSING_INDEX',
                name: 'Potentially Missing Index',
                severity: 'HIGH',
                regex: /WHERE\s+(?!id\s*=)(\w+)\s*=/gi,
                message: 'Query on non-id column - might need index',
                recommendation: 'Add database index for frequently queried columns',
                example: `
-- Bad - No index on email
SELECT * FROM users WHERE email = 'user@example.com';

-- Good - Add index
CREATE INDEX idx_users_email ON users(email);

-- Also consider composite indexes
CREATE INDEX idx_orders_user_date ON orders(user_id, created_at);`
            },
            {
                id: 'SELECT_STAR',
                name: 'SELECT * Anti-Pattern',
                severity: 'MEDIUM',
                regex: /SELECT\s+\*\s+FROM/gi,
                message: 'SELECT * fetches unnecessary columns',
                recommendation: 'Specify only needed columns to reduce data transfer',
                autoFix: false,
                example: `
// Bad - Fetching all columns
SELECT * FROM users;

// Good - Only needed columns
SELECT id, name, email FROM users;`
            },
            {
                id: 'MISSING_LIMIT',
                name: 'Query Without LIMIT',
                severity: 'HIGH',
                regex: /SELECT\s+.*FROM\s+\w+(?!.*LIMIT)/gis,
                message: 'Unbounded query - could return millions of rows',
                recommendation: 'Always use LIMIT for list queries. Add pagination.',
                autoFix: true,
                example: `
// Bad - Could return 1 million rows
SELECT * FROM orders;

// Good - Paginated
SELECT * FROM orders ORDER BY created_at DESC LIMIT 50 OFFSET 0;`
            },
            {
                id: 'INEFFICIENT_COUNT',
                name: 'Inefficient COUNT Query',
                severity: 'MEDIUM',
                regex: /SELECT\s+COUNT\s*\(\s*\*\s*\)\s+FROM\s+\w+(?!.*WHERE)/gi,
                message: 'COUNT(*) without WHERE on large table',
                recommendation: 'Cache counts or use approximate count for large tables',
                autoFix: false
            },
            {
                id: 'MISSING_TRANSACTION',
                name: 'Multiple Writes Without Transaction',
                severity: 'CRITICAL',
                detect: (code) => {
                    const updates = (code.match(/(?:INSERT|UPDATE|DELETE)/gi) || []).length;
                    const hasTransaction = /BEGIN|START TRANSACTION|beginTransaction/i.test(code);
                    return updates >= 2 && !hasTransaction;
                },
                message: 'Multiple write operations without transaction',
                recommendation: 'Wrap related operations in transaction for atomicity',
                example: `
// Bad - No transaction
await db.query('INSERT INTO orders VALUES (...)');
await db.query('UPDATE inventory SET stock = stock - 1');
await db.query('INSERT INTO audit_log VALUES (...)');

// Good - Atomic transaction
await db.beginTransaction();
try {
    await db.query('INSERT INTO orders VALUES (...)');
    await db.query('UPDATE inventory SET stock = stock - 1');
    await db.query('INSERT INTO audit_log VALUES (...)');
    await db.commit();
} catch (error) {
    await db.rollback();
    throw error;
}`
            },
            {
                id: 'INEFFICIENT_LIKE',
                name: 'Inefficient LIKE Pattern',
                severity: 'MEDIUM',
                regex: /LIKE\s+['"]%[^%]*['"]/gi,
                message: 'LIKE with leading wildcard cannot use index',
                recommendation: 'Avoid leading wildcards. Use full-text search for complex patterns.',
                example: `
// Bad - Leading wildcard (index not used)
SELECT * FROM products WHERE name LIKE '%phone';

// Better - Trailing wildcard (index can be used)
SELECT * FROM products WHERE name LIKE 'phone%';

// Best - Full-text search for complex patterns
SELECT * FROM products WHERE MATCH(name) AGAINST('phone' IN BOOLEAN MODE);`
            },
            {
                id: 'MISSING_FOREIGN_KEY',
                name: 'Missing Foreign Key Constraint',
                severity: 'MEDIUM',
                regex: /CREATE\s+TABLE\s+\w+\s*\([^)]*(\w+_id\s+INT)[^)]*\)(?!.*FOREIGN\s+KEY)/gis,
                message: 'Column named *_id without foreign key constraint',
                recommendation: 'Add foreign key constraints for referential integrity',
                example: `
-- Bad - No constraint
CREATE TABLE orders (
    id INT PRIMARY KEY,
    user_id INT  -- Missing FK!
);

-- Good - With foreign key
CREATE TABLE orders (
    id INT PRIMARY KEY,
    user_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);`
            },
            {
                id: 'IMPLICIT_CONVERSION',
                name: 'Implicit Type Conversion',
                severity: 'MEDIUM',
                regex: /WHERE\s+id\s*=\s*['"][^'"]+['"]/gi,
                message: 'Comparing INT column with string - implicit conversion',
                recommendation: 'Use correct types to avoid index bypass',
                example: `
// Bad - String comparison on INT column
WHERE id = '123'  // Forces conversion, index not used

// Good - Correct type
WHERE id = 123  // Index used efficiently`
            },
            {
                id: 'CARTESIAN_PRODUCT',
                name: 'Potential Cartesian Product',
                severity: 'CRITICAL',
                regex: /FROM\s+\w+\s*,\s*\w+(?!.*WHERE.*=)/gis,
                message: 'Multiple tables without JOIN condition - Cartesian product',
                recommendation: 'Use explicit JOINs with ON conditions',
                example: `
// Bad - Cartesian product (table1.rows × table2.rows)
SELECT * FROM users, orders;

// Good - Explicit JOIN
SELECT * FROM users
JOIN orders ON users.id = orders.user_id;`
            },
            {
                id: 'NO_PREPARED_STATEMENT',
                name: 'String Concatenation in Query',
                severity: 'CRITICAL',
                regex: /(?:query|execute)\s*\(\s*["'`].*\$\{|(?:query|execute)\s*\(\s*["'`].*\+/g,
                message: 'SQL injection risk + no query plan caching',
                recommendation: 'Use prepared statements with parameter binding',
                example: `
// Bad - String concatenation
db.query(\`SELECT * FROM users WHERE email = '\${email}'\`);

// Good - Prepared statement
db.query('SELECT * FROM users WHERE email = ?', [email]);`
            },
            {
                id: 'MISSING_BULK_INSERT',
                name: 'Loop with Individual Inserts',
                severity: 'HIGH',
                detect: (code) => {
                    return /(?:for|forEach).*INSERT\s+INTO/gis.test(code);
                },
                message: 'Individual inserts in loop - inefficient',
                recommendation: 'Use bulk insert for multiple rows',
                example: `
// Bad - N separate INSERT statements
for (const user of users) {
    await db.query('INSERT INTO users VALUES (?)', [user]);
}

// Good - Bulk insert
const values = users.map(u => [u.name, u.email]);
await db.query('INSERT INTO users (name, email) VALUES ?', [values]);`
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
                    type: 'DATABASE',
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

    /**
     * Generate fixes for database issues
     */
    generateFix(issue, code) {
        switch (issue.id) {
            case 'MISSING_LIMIT':
                return this.addLimit(code);
            
            case 'NO_PREPARED_STATEMENT':
                return this.convertToPreparedStatement(code, issue);
            
            default:
                return null;
        }
    }

    addLimit(code) {
        return code.replace(
            /(SELECT\s+.*FROM\s+\w+)/gi,
            '$1 ORDER BY id DESC LIMIT 100'
        );
    }

    convertToPreparedStatement(code, issue) {
        // Convert string concatenation to prepared statement
        return code.replace(
            /query\s*\(\s*["'`]([^"'`]*)\$\{([^}]+)\}([^"'`]*)["'`]\s*\)/g,
            'query(\'$1?$3\', [$2])'
        );
    }
}

module.exports = DatabaseScanner;
