/**
 * AiNi Travel - Mobile Translation Engine
 * Two modes:
 *   1. DOM walker  — translates all visible text on the static page
 *   2. data-i18n   — translateMarked(container) for dynamically injected HTML
 *      Usage: add data-i18n to any element whose textContent should be translated.
 *      e.g.  <span data-i18n>Ver Cuartos</span>
 *            <button><svg/> <span data-i18n>Reservar</span></button>
 */
(function () {
    'use strict';

    const lang = document.body.dataset.lang || 'es';
    if (lang === 'es') {
        // Still expose API so hotels.js can call it safely
        window.AiniTranslate = { run: function(){}, translateMarked: function(){} };
        return;
    }

    const CACHE_PREFIX = 'aini_tr_' + lang + '_';
    const CACHE_VERSION = 'v2';
    const API_URL = '/mobile_translate_api.php';

    // ── localStorage helpers ──────────────────────────────────────────────────
    function cacheGet(key) {
        try {
            const raw = localStorage.getItem(CACHE_PREFIX + key);
            if (!raw) return null;
            const obj = JSON.parse(raw);
            if (obj.v !== CACHE_VERSION) return null;
            if (Date.now() - obj.ts > 7 * 86400 * 1000) { localStorage.removeItem(CACHE_PREFIX + key); return null; }
            return obj.t;
        } catch(e) { return null; }
    }
    function cacheSet(key, value) {
        try {
            localStorage.setItem(CACHE_PREFIX + key, JSON.stringify({v: CACHE_VERSION, t: value, ts: Date.now()}));
        } catch(e) {}
    }

    // ── Core: translate a list of strings, apply via callback ────────────────
    async function translateStrings(strings, applyFn) {
        if (!strings.length) return;
        const cached    = {};
        const needsAPI  = [];

        for (const s of strings) {
            const hit = cacheGet(s);
            if (hit !== null) cached[s] = hit;
            else needsAPI.push(s);
        }

        // Apply cache hits immediately (instant)
        if (Object.keys(cached).length) applyFn(cached);
        if (!needsAPI.length) return;

        // Show loading indicator
        let indicator = document.getElementById('aini-tr-loading');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'aini-tr-loading';
            indicator.style.cssText = 'position:fixed;bottom:70px;right:12px;background:rgba(0,0,0,.55);color:#fff;border-radius:20px;padding:4px 12px;font-size:.72rem;z-index:9998;pointer-events:none';
            indicator.textContent = '🌐 Translating…';
            document.body.appendChild(indicator);
        }

        // Split into batches of 10 — prevents timeout and token overrun
        const BATCH = 10;
        try {
            for (let i = 0; i < needsAPI.length; i += BATCH) {
                const batch = needsAPI.slice(i, i + BATCH);
                const res   = await fetch(API_URL, {
                    method:  'POST',
                    headers: {'Content-Type': 'application/json'},
                    body:    JSON.stringify({strings: batch, lang: lang})
                });
                const data = await res.json();
                if (data.success && data.translations) {
                    for (const [k, v] of Object.entries(data.translations)) cacheSet(k, v);
                    applyFn(data.translations);
                }
            }
        } catch(e) {
            console.warn('AiNi Translate: API unavailable', e);
        } finally {
            indicator.remove();
        }
    }

    // ── Mode 1: DOM walker for static page text nodes ─────────────────────────
    const SKIP_TAGS  = new Set(['SCRIPT','STYLE','NOSCRIPT','SVG','TEXTAREA','INPUT','SELECT','OPTION','CODE','PRE','CANVAS']);
    const SKIP_CLASS = 'notranslate';

    function collectNodes(root) {
        root = root || document.body;
        const nodeMap = new Map();
        const walker  = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                let el = node.parentElement;
                while (el) {
                    if (SKIP_TAGS.has(el.tagName)) return NodeFilter.FILTER_REJECT;
                    if (el.classList && el.classList.contains(SKIP_CLASS)) return NodeFilter.FILTER_REJECT;
                    el = el.parentElement;
                }
                const t = node.textContent.trim();
                if (t.length < 2) return NodeFilter.FILTER_SKIP;
                if (!/[a-zA-ZÀ-ÿ\u00C0-\u024F]/.test(t)) return NodeFilter.FILTER_SKIP;
                if (t.startsWith('http')) return NodeFilter.FILTER_SKIP;
                return NodeFilter.FILTER_ACCEPT;
            }
        });
        let node;
        while ((node = walker.nextNode())) {
            const key = node.textContent.trim();
            if (!nodeMap.has(key)) nodeMap.set(key, []);
            nodeMap.get(key).push(node);
        }
        return nodeMap;
    }

    async function translatePage() {
        const nodeMap = collectNodes();
        const strings = [...nodeMap.keys()];
        await translateStrings(strings, translations => {
            for (const [original, nodes] of nodeMap) {
                const tr = translations[original];
                if (tr && tr !== original) {
                    nodes.forEach(n => {
                        const ws = n.textContent.match(/^(\s*)/)[1];
                        const we = n.textContent.match(/(\s*)$/)[1];
                        n.textContent = ws + tr + we;
                    });
                }
            }
        });
    }

    // ── Mode 2: data-i18n elements (dynamic HTML like hotel cards) ────────────
    // Usage: <span data-i18n>Texto español</span>
    // The element's trimmed textContent is the translation key.
    async function translateMarked(container) {
        container = container || document.body;
        const els = Array.from(container.querySelectorAll('[data-i18n]'));
        if (!els.length) return;

        // Group elements by their original text
        const elMap = new Map(); // text => [element, ...]
        els.forEach(el => {
            const key = el.textContent.trim();
            if (key.length < 2) return;
            if (!elMap.has(key)) elMap.set(key, []);
            elMap.get(key).push(el);
        });

        const strings = [...elMap.keys()];
        await translateStrings(strings, translations => {
            for (const [original, elements] of elMap) {
                const tr = translations[original];
                if (tr && tr !== original) {
                    elements.forEach(el => { el.textContent = tr; });
                }
            }
        });
    }

    // Run DOM walker on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', translatePage);
    } else {
        translatePage();
    }

    // Expose globally
    window.AiniTranslate = { run: translatePage, translateMarked: translateMarked };

})();
