/**
 * Performance Scanner
 * Detects performance issues and optimization opportunities
 */

class PerformanceScanner {
    constructor() {
        this.patterns = [
            {
                id: 'INEFFICIENT_LOOP',
                name: 'Inefficient Loop Operation',
                severity: 'MEDIUM',
                regex: /for\s*\([^)]*\)\s*{[^}]*(?:querySelector|getElementById|getElementsBy)[^}]*}/gs,
                message: 'DOM query inside loop - causes reflow on each iteration',
                recommendation: 'Cache DOM queries outside loop',
                example: `
// Bad - Query on every iteration
for (let i = 0; i < 1000; i++) {
    document.getElementById('container').appendChild(item);
}

// Good - Cache reference
const container = document.getElementById('container');
for (let i = 0; i < 1000; i++) {
    container.appendChild(item);
}`
            },
            {
                id: 'NO_DEBOUNCE',
                name: 'Missing Debounce on Event',
                severity: 'MEDIUM',
                regex: /addEventListener\s*\(\s*['"](?:input|scroll|resize)['"][^)]*\)\s*{(?!.*debounce|.*throttle)/gs,
                message: 'High-frequency event without debounce/throttle',
                recommendation: 'Use debounce for input events, throttle for scroll/resize',
                autoFix: true,
                example: `
// Bad - Fires on every keystroke
input.addEventListener('input', (e) => {
    searchAPI(e.target.value); // Could fire 100x/second!
});

// Good - Debounced
const debouncedSearch = debounce((value) => {
    searchAPI(value);
}, 300);

input.addEventListener('input', (e) => {
    debouncedSearch(e.target.value);
});`
            },
            {
                id: 'LARGE_BUNDLE',
                name: 'Large Bundle Size',
                severity: 'MEDIUM',
                detect: (code) => {
                    const imports = code.match(/import .* from ['"](?!\.)/g) || [];
                    return imports.length > 20;
                },
                message: 'Too many imports - large bundle size',
                recommendation: 'Use code splitting and lazy loading',
                example: `
// Bad - Import everything upfront
import Chart from 'chart.js';
import moment from 'moment';
import lodash from 'lodash';

// Good - Lazy load
const Chart = () => import('chart.js');
const moment = () => import('moment');

// Better - Use lighter alternatives
import dayjs from 'dayjs'; // 2KB vs moment's 67KB`
            },
            {
                id: 'SYNCHRONOUS_AJAX',
                name: 'Synchronous AJAX',
                severity: 'CRITICAL',
                regex: /XMLHttpRequest\(\)[\s\S]*\.open\([^)]*,\s*false\s*\)/g,
                message: 'Synchronous AJAX blocks UI thread',
                recommendation: 'Use async: true or fetch() API',
                autoFix: true
            },
            {
                id: 'MISSING_LAZY_LOADING',
                name: 'No Lazy Loading for Images',
                severity: 'LOW',
                regex: /<img[^>]*src=["'][^"']*["'][^>]*(?!loading=["']lazy["'])/gi,
                message: 'Images without lazy loading attribute',
                recommendation: 'Add loading="lazy" for below-fold images',
                autoFix: true,
                example: `
<!-- Bad - All images load immediately -->
<img src="image.jpg" alt="Photo">

<!-- Good - Lazy load below-fold images -->
<img src="image.jpg" alt="Photo" loading="lazy">`
            },
            {
                id: 'NO_CACHING',
                name: 'Missing Cache Headers',
                severity: 'MEDIUM',
                detect: (code) => {
                    const hasAPI = /res\.json|res\.send/.test(code);
                    const hasCache = /Cache-Control|ETag|Expires/.test(code);
                    return hasAPI && !hasCache;
                },
                message: 'API response without cache headers',
                recommendation: 'Add Cache-Control headers for static data',
                example: `
// Bad - No caching
app.get('/api/products', (req, res) => {
    res.json(products);
});

// Good - With caching
app.get('/api/products', (req, res) => {
    res.set('Cache-Control', 'public, max-age=3600');
    res.set('ETag', generateETag(products));
    res.json(products);
});`
            },
            {
                id: 'BLOCKING_RENDER',
                name: 'Render-Blocking Scripts',
                severity: 'HIGH',
                regex: /<script\s+src=[^>]*(?!defer|async)/gi,
                message: 'Script tag without defer or async - blocks rendering',
                recommendation: 'Add defer attribute to non-critical scripts',
                autoFix: true,
                example: `
<!-- Bad - Blocks rendering -->
<script src="analytics.js"></script>

<!-- Good - Non-blocking -->
<script src="analytics.js" defer></script>
<script src="ads.js" async></script>`
            },
            {
                id: 'MEMORY_LEAK_LISTENER',
                name: 'Potential Memory Leak',
                severity: 'HIGH',
                detect: (code) => {
                    const hasListener = /addEventListener/.test(code);
                    const hasRemove = /removeEventListener/.test(code);
                    return hasListener && !hasRemove;
                },
                message: 'Event listener without cleanup - potential memory leak',
                recommendation: 'Remove event listeners in cleanup/destroy methods',
                example: `
// Bad - Listener never removed
componentDidMount() {
    window.addEventListener('resize', this.handleResize);
}

// Good - Cleanup on unmount
componentDidMount() {
    window.addEventListener('resize', this.handleResize);
}
componentWillUnmount() {
    window.removeEventListener('resize', this.handleResize);
}`
            },
            {
                id: 'INEFFICIENT_ARRAY_OPERATION',
                name: 'Inefficient Array Operation',
                severity: 'LOW',
                regex: /\.concat\s*\(|\.push\.apply/g,
                message: 'Inefficient array concatenation',
                recommendation: 'Use spread operator for better performance',
                example: `
// Bad - Less efficient
arr1 = arr1.concat(arr2);
Array.prototype.push.apply(arr1, arr2);

// Good - Spread operator
arr1 = [...arr1, ...arr2];
arr1.push(...arr2);`
            },
            {
                id: 'EXCESSIVE_REFLOWS',
                name: 'Excessive DOM Reflows',
                severity: 'MEDIUM',
                detect: (code) => {
                    const layoutReads = (code.match(/offsetTop|offsetLeft|offsetWidth|offsetHeight|clientTop/g) || []).length;
                    return layoutReads > 5;
                },
                message: 'Multiple layout property reads - causes forced reflows',
                recommendation: 'Batch DOM reads before writes to minimize reflows',
                example: `
// Bad - Forces reflow on each read
const h1 = element1.offsetHeight; // Read
element1.style.height = h1 + 'px'; // Write (reflow)
const h2 = element2.offsetHeight; // Read (forced reflow!)
element2.style.height = h2 + 'px'; // Write (reflow)

// Good - Batch reads, then batch writes
const h1 = element1.offsetHeight; // Read
const h2 = element2.offsetHeight; // Read
element1.style.height = h1 + 'px'; // Write
element2.style.height = h2 + 'px'; // Write`
            },
            {
                id: 'NO_REQUEST_ANIMATION_FRAME',
                name: 'Animation Without RAF',
                severity: 'MEDIUM',
                regex: /setInterval\s*\([^)]*(?:style|transform|animation)/gi,
                message: 'Animation using setInterval instead of requestAnimationFrame',
                recommendation: 'Use requestAnimationFrame for smoother animations',
                example: `
// Bad - Can cause jank
setInterval(() => {
    element.style.left = position + 'px';
    position += 1;
}, 16);

// Good - Synced with browser refresh
function animate() {
    element.style.left = position + 'px';
    position += 1;
    requestAnimationFrame(animate);
}
animate();`
            },
            {
                id: 'UNOPTIMIZED_REGEX',
                name: 'Inefficient Regular Expression',
                severity: 'LOW',
                regex: /new\s+RegExp\s*\(/g,
                message: 'RegExp created inside function - recompiled each call',
                recommendation: 'Define regex outside function for reuse',
                example: `
// Bad - Recompiled on every call
function validate(input) {
    const pattern = new RegExp('^[a-z]+$');
    return pattern.test(input);
}

// Good - Compiled once
const pattern = /^[a-z]+$/;
function validate(input) {
    return pattern.test(input);
}`
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
                    type: 'PERFORMANCE',
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

module.exports = PerformanceScanner;
