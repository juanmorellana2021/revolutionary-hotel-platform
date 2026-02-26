/**
 * AiNi Travel - Mobile Translation Engine
 * Reads data-lang from <body>, walks DOM text nodes,
 * checks localStorage cache, calls batch API for misses,
 * applies translations to DOM, saves to localStorage.
 */
(function () {
    'use strict';

    const lang = document.body.dataset.lang || 'es';
    if (lang === 'es') return; // Spanish is the base language — nothing to do

    const CACHE_PREFIX = 'aini_tr_' + lang + '_';
    const CACHE_VERSION = 'v2';
    const API_URL = '/mobile_translate_api.php';

    // Elements whose text content should never be translated
    const SKIP_TAGS = new Set(['SCRIPT','STYLE','NOSCRIPT','SVG','TEXTAREA','INPUT','SELECT','OPTION','CODE','PRE','CANVAS']);
    const SKIP_CLASS = 'notranslate';

    // ── Collect all unique translatable text nodes ──────────────────────────
    function collectNodes() {
        const nodeMap = new Map(); // text => [TextNode, ...]
        const walker  = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode(node) {
                    let el = node.parentElement;
                    while (el) {
                        if (SKIP_TAGS.has(el.tagName)) return NodeFilter.FILTER_REJECT;
                        if (el.classList && el.classList.contains(SKIP_CLASS)) return NodeFilter.FILTER_REJECT;
                        el = el.parentElement;
                    }
                    const t = node.textContent.trim();
                    // Must be meaningful: length ≥ 2, has at least one letter, not a number/URL/emoji-only
                    if (t.length < 2) return NodeFilter.FILTER_SKIP;
                    if (!/[a-zA-ZÀ-ÿ\u00C0-\u024F]/.test(t)) return NodeFilter.FILTER_SKIP;
                    if (t.startsWith('http') || t.startsWith('/')) return NodeFilter.FILTER_SKIP;
                    return NodeFilter.FILTER_ACCEPT;
                }
            }
        );
        let node;
        while ((node = walker.nextNode())) {
            const key = node.textContent.trim();
            if (!nodeMap.has(key)) nodeMap.set(key, []);
            nodeMap.get(key).push(node);
        }
        return nodeMap; // Map<original, [TextNode]>
    }

    // ── Apply a translations map to the DOM ───────────────────────────────────
    function applyTranslations(nodeMap, translations) {
        for (const [original, nodes] of nodeMap) {
            const translated = translations[original];
            if (translated && translated !== original) {
                nodes.forEach(node => {
                    // Preserve surrounding whitespace
                    const ws = node.textContent.match(/^(\s*)/)[1];
                    const we = node.textContent.match(/(\s*)$/)[1];
                    node.textContent = ws + translated + we;
                });
            }
        }
    }

    // ── localStorage helpers ──────────────────────────────────────────────────
    function cacheGet(key) {
        try {
            const raw = localStorage.getItem(CACHE_PREFIX + key);
            if (!raw) return null;
            const obj = JSON.parse(raw);
            if (obj.v !== CACHE_VERSION) return null;
            // Expire after 7 days
            if (Date.now() - obj.ts > 7 * 86400 * 1000) { localStorage.removeItem(CACHE_PREFIX + key); return null; }
            return obj.t;
        } catch(e) { return null; }
    }

    function cacheSet(key, value) {
        try {
            localStorage.setItem(CACHE_PREFIX + key, JSON.stringify({v: CACHE_VERSION, t: value, ts: Date.now()}));
        } catch(e) { /* storage full — ignore */ }
    }

    // ── Main ──────────────────────────────────────────────────────────────────
    async function translatePage() {
        const nodeMap    = collectNodes();
        if (nodeMap.size === 0) return;

        const allStrings  = [...nodeMap.keys()];
        const cached      = {};
        const needsAPI    = [];

        // Check localStorage first
        for (const str of allStrings) {
            const hit = cacheGet(str);
            if (hit !== null) {
                cached[str] = hit;
            } else {
                needsAPI.push(str);
            }
        }

        // Apply cached translations immediately (instant, no flicker)
        if (Object.keys(cached).length > 0) {
            applyTranslations(nodeMap, cached);
        }

        if (needsAPI.length === 0) return;

        // Show subtle loading indicator
        const indicator = document.createElement('div');
        indicator.id    = 'aini-tr-loading';
        indicator.style.cssText = 'position:fixed;bottom:70px;right:12px;background:rgba(0,0,0,.55);color:#fff;border-radius:20px;padding:4px 12px;font-size:.72rem;z-index:9998;pointer-events:none';
        indicator.textContent = '🌐 Translating…';
        document.body.appendChild(indicator);

        try {
            const res  = await fetch(API_URL, {
                method:  'POST',
                headers: {'Content-Type':'application/json'},
                body:    JSON.stringify({strings: needsAPI, lang: lang})
            });
            const data = await res.json();

            if (data.success && data.translations) {
                // Save to localStorage + apply
                for (const [original, translated] of Object.entries(data.translations)) {
                    cacheSet(original, translated);
                }
                applyTranslations(nodeMap, data.translations);
            }
        } catch(e) {
            // API unavailable — page stays in Spanish, no error shown
            console.warn('AiNi Translate: API unavailable', e);
        } finally {
            indicator.remove();
        }
    }

    // Run after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', translatePage);
    } else {
        translatePage();
    }

})();
