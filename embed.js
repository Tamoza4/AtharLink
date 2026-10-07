/**
 * AtharLink Lightweight Public Embed Counter Widget
 * Usage:
 *   <span data-athar-count="myslug" data-type="unique">0</span>
 *   <script src="https://yourdomain.com/embed.js" async></script>
 */
(function() {
    'use strict';

    // Auto-detect AtharLink API Base URL from the script tag itself
    function getApiBase() {
        const scripts = document.getElementsByTagName('script');
        for (let i = scripts.length - 1; i >= 0; i--) {
            const src = scripts[i].src;
            if (src && src.indexOf('embed.js') !== -1) {
                const url = new URL(src);
                return url.origin + url.pathname.replace(/\/embed\.js$/, '');
            }
        }
        return window.location.origin;
    }

    const apiBase = getApiBase();

    function initAtharCounters() {
        const elements = document.querySelectorAll('[data-athar-count]');
        if (!elements.length) return;

        elements.forEach(function(el) {
            const slug = el.getAttribute('data-athar-count');
            const type = el.getAttribute('data-type') || 'all';
            if (!slug) return;

            const endpoint = apiBase + '/api/v1/counter?slug=' + encodeURIComponent(slug) + '&type=' + encodeURIComponent(type);

            fetch(endpoint)
                .then(function(res) {
                    if (!res.ok) throw new Error('Network error');
                    return res.json();
                })
                .then(function(data) {
                    if (data && data.success) {
                        const count = (type === 'unique') ? data.unique_clicks : (data.clicks !== undefined ? data.clicks : data.total_clicks);
                        el.textContent = Number(count).toLocaleString();
                    }
                })
                .catch(function(err) {
                    console.warn('[AtharLink] Counter fetch error for slug:', slug, err);
                });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAtharCounters);
    } else {
        initAtharCounters();
    }
})();
