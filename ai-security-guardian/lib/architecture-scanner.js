/**
 * Architecture Scanner
 * Detects architecture and design pattern violations
 * Based on SOLID principles and memory-bank best practices
 */

class ArchitectureScanner {
    constructor() {
        this.patterns = [
            {
                id: 'GOD_OBJECT',
                name: 'God Object Anti-Pattern',
                severity: 'HIGH',
                detect: (code) => {
                    const classMatch = code.match(/class\s+\w+\s*{([^}]*)}/s);
                    if (!classMatch) return false;
                    
                    const methods = (classMatch[1].match(/\w+\s*\([^)]*\)\s*{/g) || []).length;
                    const properties = (classMatch[1].match(/this\.\w+\s*=/g) || []).length;
                    
                    return methods > 15 || properties > 10;
                },
                message: 'Class has too many responsibilities (God Object)',
                recommendation: 'Split into smaller, focused classes following Single Responsibility Principle',
                example: `
// Bad - God Object
class UserManager {
    login() {}
    logout() {}
    updateProfile() {}
    sendEmail() {}
    processPayment() {}
    generateReport() {}
    // ... 20 more methods
}

// Good - Separated Concerns
class AuthService { login() {} logout() {} }
class ProfileService { update() {} get() {} }
class EmailService { send() {} }
class PaymentService { process() {} }
class ReportService { generate() {} }`
            },
            {
                id: 'MISSING_REPOSITORY_PATTERN',
                name: 'Direct Database Access',
                severity: 'MEDIUM',
                regex: /(?:SELECT|INSERT|UPDATE|DELETE)\s+.*FROM/gi,
                message: 'SQL query in business logic - missing Repository pattern',
                recommendation: 'Use Repository pattern to separate data access from business logic',
                example: `
// Bad - SQL in controller
app.post('/users', async (req, res) => {
    const result = await db.query('INSERT INTO users VALUES (?, ?)', [name, email]);
});

// Good - Repository pattern
class UserRepository {
    async create(user) {
        return db.query('INSERT INTO users VALUES (?, ?)', [user.name, user.email]);
    }
}

app.post('/users', async (req, res) => {
    const user = await userRepository.create(req.body);
});`
            },
            {
                id: 'LONG_METHOD',
                name: 'Long Method',
                severity: 'MEDIUM',
                detect: (code) => {
                    const methods = code.match(/function\s+\w+\s*\([^)]*\)\s*{[^}]*}/gs) || [];
                    return methods.some(method => {
                        const lines = method.split('\n').length;
                        return lines > 50;
                    });
                },
                message: 'Method exceeds 50 lines - too complex',
                recommendation: 'Extract smaller methods. Each method should do one thing well.',
                autoFix: false
            },
            {
                id: 'MISSING_DEPENDENCY_INJECTION',
                name: 'Hard-Coded Dependencies',
                severity: 'MEDIUM',
                regex: /new\s+\w+\s*\([^)]*\)\s*;/g,
                message: 'Hard-coded dependency - use Dependency Injection',
                recommendation: 'Inject dependencies through constructor for better testability',
                example: `
// Bad - Hard-coded
class UserService {
    constructor() {
        this.emailService = new EmailService(); // Hard-coded!
    }
}

// Good - Dependency Injection
class UserService {
    constructor(emailService) {
        this.emailService = emailService; // Injected!
    }
}`
            },
            {
                id: 'NO_INTERFACE_SEGREGATION',
                name: 'Fat Interface',
                severity: 'LOW',
                detect: (code) => {
                    const interfaceMatch = code.match(/interface\s+\w+\s*{([^}]*)}/s);
                    if (!interfaceMatch) return false;
                    
                    const methods = (interfaceMatch[1].match(/\w+\s*\([^)]*\)\s*:/g) || []).length;
                    return methods > 8;
                },
                message: 'Interface has too many methods - violates Interface Segregation Principle',
                recommendation: 'Split into smaller, focused interfaces',
                autoFix: false
            },
            {
                id: 'TIGHT_COUPLING',
                name: 'Tight Coupling',
                severity: 'MEDIUM',
                regex: /require\s*\(\s*['"][./]*(?!node_modules)/g,
                message: 'Tight coupling to specific implementation',
                recommendation: 'Depend on abstractions, not concrete implementations',
                autoFix: false
            },
            {
                id: 'NO_SEPARATION_OF_CONCERNS',
                name: 'Mixed Concerns',
                severity: 'HIGH',
                detect: (code) => {
                    const hasUI = /innerHTML|querySelector|addEventListener/.test(code);
                    const hasDB = /SELECT|INSERT|UPDATE|DELETE/.test(code);
                    const hasBusiness = /calculate|process|validate/.test(code);
                    
                    const concerns = [hasUI, hasDB, hasBusiness].filter(Boolean).length;
                    return concerns >= 2;
                },
                message: 'Multiple concerns in single file (UI + DB + Business Logic)',
                recommendation: 'Separate into layers: Presentation, Business Logic, Data Access',
                example: `
// Bad - Everything mixed
function handleUserRegistration() {
    const name = document.getElementById('name').value; // UI
    const isValid = name.length > 3; // Business
    db.query('INSERT INTO users...'); // Data
}

// Good - Separated
class UserView { getName() {} }
class UserValidator { validate() {} }
class UserRepository { save() {} }
class UserService { 
    register(user) {
        const validated = this.validator.validate(user);
        return this.repository.save(validated);
    }
}`
            },
            {
                id: 'PRIMITIVE_OBSESSION',
                name: 'Primitive Obsession',
                severity: 'LOW',
                regex: /function\s+\w+\s*\(\s*(\w+\s*,\s*){4,}/g,
                message: 'Too many primitive parameters - use value objects',
                recommendation: 'Group related parameters into objects',
                example: `
// Bad - Primitives everywhere
function createUser(name, email, age, address, city, country, zip) {}

// Good - Value Object
class UserData {
    constructor(name, email, age, address) {
        this.name = name;
        this.email = email;
        // ...
    }
}
function createUser(userData) {}`
            },
            {
                id: 'MISSING_FACTORY_PATTERN',
                name: 'Complex Object Creation',
                severity: 'LOW',
                regex: /new\s+\w+\s*\([^)]*,\s*[^)]*,\s*[^)]*,/g,
                message: 'Complex object creation - consider Factory pattern',
                recommendation: 'Use Factory pattern for complex object creation',
                example: `
// Bad - Complex construction
const user = new User(name, email, role, permissions, settings, preferences);

// Good - Factory pattern
class UserFactory {
    static createAdmin(name, email) {
        return new User(name, email, 'admin', ADMIN_PERMISSIONS, DEFAULT_SETTINGS);
    }
    
    static createGuest(name, email) {
        return new User(name, email, 'guest', GUEST_PERMISSIONS, DEFAULT_SETTINGS);
    }
}`
            },
            {
                id: 'NO_STRATEGY_PATTERN',
                name: 'Complex Conditionals',
                severity: 'MEDIUM',
                regex: /if\s*\([^)]*\)\s*{[^}]*}\s*else\s+if\s*\([^)]*\)\s*{[^}]*}\s*else\s+if/gs,
                message: 'Multiple if/else chains - consider Strategy pattern',
                recommendation: 'Replace conditionals with Strategy pattern for better extensibility',
                example: `
// Bad - Long if/else chain
if (paymentType === 'credit') {
    processCreditCard();
} else if (paymentType === 'paypal') {
    processPaypal();
} else if (paymentType === 'crypto') {
    processCrypto();
}

// Good - Strategy pattern
const strategies = {
    credit: new CreditCardStrategy(),
    paypal: new PaypalStrategy(),
    crypto: new CryptoStrategy()
};
strategies[paymentType].process();`
            }
        ];
    }

    scan(code, filePath = '') {
        const issues = [];

        for (const pattern of this.patterns) {
            let matches = [];
            
            if (pattern.detect) {
                // Custom detection function
                if (pattern.detect(code)) {
                    matches = [{ index: 0 }];
                }
            } else if (pattern.regex) {
                // Regex pattern
                matches = Array.from(code.matchAll(pattern.regex));
            }

            for (const match of matches) {
                const lineNumber = this.getLineNumber(code, match.index);
                
                issues.push({
                    id: pattern.id,
                    type: 'ARCHITECTURE',
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

module.exports = ArchitectureScanner;
