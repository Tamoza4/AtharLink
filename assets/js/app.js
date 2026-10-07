/**
 * Tamoza UI Standard - Verified: 8pt Logic, Premium Glassmorphism, Theme Toggle Active, Strict Dual-Language.
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // Helper to get localized string from window.__i18n
    function t(key, fallback) {
        if (window.__i18n && typeof window.__i18n[key] !== 'undefined') {
            return window.__i18n[key];
        }
        return fallback;
    }

    // 1. Theme Management (Dark / Light Mode)
    const storedTheme = localStorage.getItem('athar_theme') || 'dark';

    const sunSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tamoza-theme-icon"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>';
    const moonSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tamoza-theme-icon"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>';

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('athar_theme', theme);
        const isDark = theme === 'dark';
        const iconSvg = isDark ? sunSvg : moonSvg;

        document.querySelectorAll('#themeToggleBtn, .tamoza-theme-toggle').forEach(function (btn) {
            const iconSpan = btn.querySelector('.theme-toggle-icon');
            if (iconSpan) {
                iconSpan.innerHTML = iconSvg;
            } else if (!btn.querySelector('.theme-toggle-text')) {
                btn.innerHTML = iconSvg;
            }
            btn.title = isDark 
                ? t('themeLight', 'Switch to light theme') 
                : t('themeDark', 'Switch to dark theme');
        });
    }

    applyTheme(storedTheme);

    document.querySelectorAll('#themeToggleBtn, .tamoza-theme-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'dark';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
        });
    });

    // 2. Copy to Clipboard Utility
    document.querySelectorAll('[data-copy-url]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const url = this.getAttribute('data-copy-url');
            if (!url) return;

            navigator.clipboard.writeText(url).then(() => {
                const originalHtml = this.innerHTML;
                this.innerHTML = '<svg class="tamoza-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> ' + t('copied', 'Copied');
                this.classList.add('badge-success');

                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.classList.remove('badge-success');
                }, 2000);
            }).catch(() => {
                prompt(t('copyManually', 'Copy link manually:'), url);
            });
        });
    });

    // 3. Live Slug Availability Checker
    const slugInput = document.getElementById('linkSlugInput');
    const slugFeedback = document.getElementById('slugFeedback');
    let slugTimer = null;

    if (slugInput && slugFeedback) {
        slugInput.addEventListener('input', function () {
            clearTimeout(slugTimer);
            const slug = this.value.trim();

            if (slug.length === 0) {
                slugFeedback.innerHTML = '<small class="text-secondary">' + t('slugHint', 'Leave empty for auto-generation (6 characters)') + '</small>';
                return;
            }

            if (!/^[a-zA-Z0-9_-]+$/.test(slug)) {
                slugFeedback.innerHTML = '<span class="tamoza-badge badge-danger">' + t('slugInvalid', 'Slug contains invalid characters') + '</span>';
                return;
            }

            slugFeedback.innerHTML = '<span class="tamoza-badge badge-indigo">' + t('slugChecking', 'Checking availability...') + '</span>';

            slugTimer = setTimeout(() => {
                fetch('index.php?action=check_slug&slug=' + encodeURIComponent(slug))
                    .then(res => res.json())
                    .then(data => {
                        if (data.available) {
                            slugFeedback.innerHTML = '<span class="tamoza-badge badge-success d-inline-flex align-items-center gap-1"><svg class="tamoza-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> ' + t('slugAvailable', 'Available') + '</span>';
                        } else {
                            slugFeedback.innerHTML = '<span class="tamoza-badge badge-danger d-inline-flex align-items-center gap-1"><svg class="tamoza-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> ' + t('slugTaken', 'Already taken') + '</span>';
                        }
                    })
                    .catch(() => {
                        slugFeedback.innerHTML = '';
                    });
            }, 300);
        });
    }

    // 4. Modal QR Code Generator
    const qrModal = document.getElementById('qrCodeModal');
    let qrInstance = null;

    if (qrModal) {
        qrModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const url = button.getAttribute('data-qr-url');
            const title = button.getAttribute('data-qr-title') || 'QR Code';

            const modalTitleEl = document.getElementById('qrModalTitle');
            if (modalTitleEl) {
                modalTitleEl.textContent = t('qrPrefix', 'QR Code: /') + title;
            }
            const container = document.getElementById('qrModalCanvas');
            if (container) {
                container.innerHTML = '';

                if (typeof QRCode !== 'undefined') {
                    qrInstance = new QRCode(container, {
                        text: url,
                        width: 200,
                        height: 200,
                        colorDark: '#0f172a',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });

                    const centerEl = () => {
                        const img = container.querySelector('img');
                        if (img) {
                            img.style.margin = '0 auto';
                            img.style.display = 'block';
                        }
                        const cvs = container.querySelector('canvas');
                        if (cvs) {
                            cvs.style.display = 'none';
                        }
                    };
                    centerEl();
                    setTimeout(centerEl, 50);
                    setTimeout(centerEl, 200);
                }
            }

            // Setup Download Button
            const downloadPngBtn = document.getElementById('downloadQrPngBtn');
            if (downloadPngBtn && container) {
                downloadPngBtn.onclick = function () {
                    const img = container.querySelector('img');
                    if (img && img.src) {
                        const a = document.createElement('a');
                        a.href = img.src;
                        a.download = 'qr_' + (title.replace(/\s+/g, '_')) + '.png';
                        a.click();
                    } else {
                        const canvas = container.querySelector('canvas');
                        if (canvas) {
                            const a = document.createElement('a');
                            a.href = canvas.toDataURL('image/png');
                            a.download = 'qr_' + (title.replace(/\s+/g, '_')) + '.png';
                            a.click();
                        }
                    }
                };
            }
        });
    }
});
