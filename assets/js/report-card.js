/**
 * AtharLink Infographic Report Card Canvas Engine
 * Renders high-resolution (Retina 2x) branded report cards client-side for single links and system overview.
 */
(function () {
    'use strict';

    function t(key, fallback) {
        if (window.__i18n && typeof window.__i18n[key] !== 'undefined') {
            return window.__i18n[key];
        }
        return fallback;
    }

    function isRtl() {
        return (window.__i18n && window.__i18n.dir === 'rtl') || document.documentElement.getAttribute('dir') === 'rtl';
    }

    function getFontFamily() {
        return isRtl()
            ? 'Cairo, "Segoe UI", Tahoma, sans-serif'
            : '-apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, sans-serif';
    }

    function formatNumber(num) {
        if (num === null || num === undefined) return '0';
        return Number(num).toLocaleString();
    }

    function drawRoundRect(ctx, x, y, w, h, r, fillColor, strokeColor, lineWidth) {
        ctx.beginPath();
        if (typeof ctx.roundRect === 'function') {
            ctx.roundRect(x, y, w, h, r);
        } else {
            ctx.moveTo(x + r, y);
            ctx.lineTo(x + w - r, y);
            ctx.quadraticCurveTo(x + w, y, x + w, y + r);
            ctx.lineTo(x + w, y + h - r);
            ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
            ctx.lineTo(x + r, y + h);
            ctx.quadraticCurveTo(x, y + h, x, y + h - r);
            ctx.lineTo(x, y + r);
            ctx.quadraticCurveTo(x, y, x + r, y);
            ctx.closePath();
        }

        if (fillColor) {
            ctx.fillStyle = fillColor;
            ctx.fill();
        }
        if (strokeColor) {
            ctx.strokeStyle = strokeColor;
            ctx.lineWidth = lineWidth || 1;
            ctx.stroke();
        }
    }

    function truncate(ctx, text, maxWidth) {
        if (!text) return '';
        if (ctx.measureText(text).width <= maxWidth) return text;
        let str = text;
        while (str.length > 0 && ctx.measureText(str + '...').width > maxWidth) {
            str = str.slice(0, -1);
        }
        return str + '...';
    }

    function drawBackground(ctx, w, h) {
        // Deep obsidian gradient
        const bgGrad = ctx.createLinearGradient(0, 0, w, h);
        bgGrad.addColorStop(0, '#0b0f19');
        bgGrad.addColorStop(0.5, '#10172a');
        bgGrad.addColorStop(1, '#1e1b4b');
        ctx.fillStyle = bgGrad;
        ctx.fillRect(0, 0, w, h);

        // Ambient radial orbs
        const orb1 = ctx.createRadialGradient(w - 120, 100, 10, w - 120, 100, 320);
        orb1.addColorStop(0, 'rgba(99, 102, 241, 0.25)');
        orb1.addColorStop(1, 'rgba(99, 102, 241, 0)');
        ctx.fillStyle = orb1;
        ctx.fillRect(0, 0, w, h);

        const orb2 = ctx.createRadialGradient(140, h - 80, 10, 140, h - 80, 280);
        orb2.addColorStop(0, 'rgba(34, 211, 238, 0.18)');
        orb2.addColorStop(1, 'rgba(34, 211, 238, 0)');
        ctx.fillStyle = orb2;
        ctx.fillRect(0, 0, w, h);

        // Subtle outer border
        drawRoundRect(ctx, 14, 14, w - 28, h - 28, 24, null, 'rgba(255, 255, 255, 0.08)', 1.5);
    }

    // Single Link Report Card Generator
    function renderSingleLinkCard(canvas, data) {
        const rtl = isRtl();
        const font = getFontFamily();
        const baseW = 1100;
        const baseH = 680;
        const scale = 2; // Retina 2x

        canvas.width = baseW * scale;
        canvas.height = baseH * scale;
        canvas.style.width = '100%';
        canvas.style.maxWidth = baseW + 'px';

        const ctx = canvas.getContext('2d');
        ctx.scale(scale, scale);

        drawBackground(ctx, baseW, baseH);

        // Header: Brand & Meta
        ctx.direction = rtl ? 'rtl' : 'ltr';
        ctx.textAlign = rtl ? 'right' : 'left';

        const leftX = rtl ? baseW - 50 : 50;
        const rightX = rtl ? 50 : baseW - 50;

        // Logo & Title
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 24px ' + font;
        ctx.fillText('AtharLink', leftX, 60);

        // Brand Badge
        const badgeX = rtl ? baseW - 220 : 210;
        drawRoundRect(ctx, badgeX, 40, 145, 26, 13, 'rgba(99, 102, 241, 0.25)', 'rgba(99, 102, 241, 0.4)', 1);
        ctx.fillStyle = '#A5B4FC';
        ctx.font = 'bold 11px ' + font;
        ctx.textAlign = 'center';
        ctx.fillText(t('reportBadgeAnalytics', 'ANALYTICS CARD'), badgeX + 72, 57);

        // Date & Status (Right side in LTR, Left in RTL)
        ctx.textAlign = rtl ? 'left' : 'right';
        ctx.font = '500 13px ' + font;
        ctx.fillStyle = '#94A3B8';
        const dateStr = (new Date()).toISOString().slice(0, 10);
        ctx.fillText(dateStr, rightX, 50);

        const isActive = data.is_active === undefined || data.is_active == 1;
        const statusText = isActive ? t('statusActive', 'Active') : t('statusPaused', 'Paused');
        const statusColor = isActive ? '#10B981' : '#F59E0B';
        const statusBg = isActive ? 'rgba(16, 185, 129, 0.15)' : 'rgba(245, 158, 11, 0.15)';
        const stW = 85;
        const stX = rtl ? rightX : rightX - stW;
        drawRoundRect(ctx, stX, 58, stW, 22, 11, statusBg, statusColor, 1);
        ctx.fillStyle = statusColor;
        ctx.font = 'bold 11px ' + font;
        ctx.textAlign = 'center';
        ctx.fillText('● ' + statusText, stX + (stW / 2), 73);

        // Link Headline Card
        drawRoundRect(ctx, 45, 100, baseW - 90, 78, 16, 'rgba(255, 255, 255, 0.035)', 'rgba(255, 255, 255, 0.08)', 1);
        ctx.textAlign = rtl ? 'right' : 'left';
        
        // Short URL / Slug
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 24px ' + font;
        const slugTitle = '/' + (data.slug || 'link');
        ctx.fillText(slugTitle, leftX, 134);

        if (data.title) {
            ctx.fillStyle = '#A5B4FC';
            ctx.font = '500 15px ' + font;
            const titleDisplay = ' — ' + truncate(ctx, data.title, 400);
            const slugWidth = ctx.measureText(slugTitle).width;
            const titleX = rtl ? leftX - slugWidth - 10 : leftX + slugWidth + 10;
            ctx.fillText(titleDisplay, titleX, 134);
        }

        // Target URL
        ctx.fillStyle = '#94A3B8';
        ctx.font = '400 13px ' + font;
        const targetLabel = t('reportTargetLabel', 'Target URL') + ': ';
        const targetText = truncate(ctx, data.target_url || '', 750);
        ctx.fillText(targetLabel + targetText, leftX, 160);

        // KPI Stat Cards Row (3 Cards)
        const kpiY = 195;
        const kpiH = 100;
        const kpiW = (baseW - 90 - 32) / 3;

        const kpis = [
            { label: t('kpiTotalClicks', 'Total Clicks'), val: formatNumber(data.total_clicks || 0), color: '#818CF8' },
            { label: t('kpiUniqueClicks', 'Unique Clicks'), val: formatNumber(data.unique_clicks || 0), color: '#34D399' },
            { label: t('kpiConversionRate', 'Unique Rate'), val: (data.conversion_rate || 0) + '%', color: '#38BDF8' }
        ];

        kpis.forEach((kpi, idx) => {
            const cardX = 45 + (idx * (kpiW + 16));
            drawRoundRect(ctx, cardX, kpiY, kpiW, kpiH, 16, 'rgba(255, 255, 255, 0.035)', 'rgba(255, 255, 255, 0.08)', 1);

            ctx.textAlign = rtl ? 'right' : 'left';
            const kpiContentX = rtl ? cardX + kpiW - 22 : cardX + 22;
            const kpiIconX = rtl ? cardX + 22 : cardX + kpiW - 22;

            // Stylish Status Indicator Dot
            ctx.fillStyle = kpi.color;
            ctx.beginPath();
            ctx.arc(kpiIconX, kpiY + 36, 5, 0, Math.PI * 2);
            ctx.fill();

            // Label
            ctx.fillStyle = '#94A3B8';
            ctx.font = '500 13px ' + font;
            ctx.fillText(kpi.label, kpiContentX, kpiY + 36);

            // Value
            ctx.fillStyle = kpi.color;
            ctx.font = 'bold 32px ' + font;
            ctx.fillText(kpi.val, kpiContentX, kpiY + 76);
        });

        // Detailed Breakdown Panels (3 Columns)
        const rowY = 312;
        const rowH = 300;
        const colW = (baseW - 90 - 32) / 3;

        // 1. Countries Card
        const col1X = 45;
        drawRoundRect(ctx, col1X, rowY, colW, rowH, 16, 'rgba(255, 255, 255, 0.03)', 'rgba(255, 255, 255, 0.06)', 1);
        ctx.textAlign = rtl ? 'right' : 'left';
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 16px ' + font;
        ctx.fillText(t('reportTopCountries', 'Top Countries'), rtl ? col1X + colW - 20 : col1X + 20, rowY + 34);

        const countries = Array.isArray(data.countries) ? data.countries.slice(0, 5) : [];
        if (countries.length === 0) {
            ctx.fillStyle = '#64748B';
            ctx.font = '400 13px ' + font;
            ctx.textAlign = 'center';
            ctx.fillText('— ' + t('no_country_data', 'No country data available') + ' —', col1X + (colW / 2), rowY + 150);
        } else {
            const maxCount = Math.max(...countries.map(c => Number(c.count) || 1), 1);
            countries.forEach((c, i) => {
                const itemY = rowY + 68 + (i * 44);
                const count = Number(c.count) || 0;
                const ratio = Math.min(1, count / maxCount);

                // Code badge
                const badgeBoxX = rtl ? col1X + colW - 55 : col1X + 20;
                drawRoundRect(ctx, badgeBoxX, itemY - 14, 35, 20, 6, 'rgba(99, 102, 241, 0.2)', '#6366F1', 1);
                ctx.fillStyle = '#C7D2FE';
                ctx.font = 'bold 11px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(c.country_code || 'XX', badgeBoxX + 17, itemY);

                // Count text
                ctx.fillStyle = '#E2E8F0';
                ctx.font = 'bold 13px ' + font;
                ctx.textAlign = rtl ? 'left' : 'right';
                const countX = rtl ? col1X + 20 : col1X + colW - 20;
                ctx.fillText(formatNumber(count), countX, itemY);

                // Mini progress bar
                const barX = rtl ? col1X + 60 : col1X + 65;
                const barW = colW - 130;
                drawRoundRect(ctx, barX, itemY - 6, barW, 6, 3, 'rgba(255, 255, 255, 0.08)');
                drawRoundRect(ctx, barX, itemY - 6, Math.max(8, barW * ratio), 6, 3, '#6366F1');
            });
        }

        // 2. Devices Card
        const col2X = col1X + colW + 16;
        drawRoundRect(ctx, col2X, rowY, colW, rowH, 16, 'rgba(255, 255, 255, 0.03)', 'rgba(255, 255, 255, 0.06)', 1);
        ctx.textAlign = rtl ? 'right' : 'left';
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 16px ' + font;
        ctx.fillText(t('reportTopDevices', 'Device Breakdown'), rtl ? col2X + colW - 20 : col2X + 20, rowY + 34);

        const devices = Array.isArray(data.devices) ? data.devices.slice(0, 4) : [];
        if (devices.length === 0) {
            ctx.fillStyle = '#64748B';
            ctx.font = '400 13px ' + font;
            ctx.textAlign = 'center';
            ctx.fillText('— ' + t('no_device_data', 'No device data') + ' —', col2X + (colW / 2), rowY + 150);
        } else {
            const devTotal = devices.reduce((sum, d) => sum + (Number(d.count) || 0), 0) || 1;
            devices.forEach((d, i) => {
                const itemY = rowY + 70 + (i * 48);
                const count = Number(d.count) || 0;
                const pct = Math.round((count / devTotal) * 100);

                ctx.textAlign = rtl ? 'right' : 'left';
                ctx.fillStyle = '#E2E8F0';
                ctx.font = '600 14px ' + font;
                const nameX = rtl ? col2X + colW - 20 : col2X + 20;
                ctx.fillText((d.device_type || 'other').toUpperCase(), nameX, itemY);

                ctx.textAlign = rtl ? 'left' : 'right';
                ctx.fillStyle = '#34D399';
                ctx.font = 'bold 14px ' + font;
                const valX = rtl ? col2X + 20 : col2X + colW - 20;
                ctx.fillText(pct + '% (' + formatNumber(count) + ')', valX, itemY);

                const barX = col2X + 20;
                const barW = colW - 40;
                drawRoundRect(ctx, barX, itemY + 8, barW, 6, 3, 'rgba(255, 255, 255, 0.08)');
                drawRoundRect(ctx, barX, itemY + 8, Math.max(8, barW * (pct / 100)), 6, 3, '#34D399');
            });
        }

        // 3. Referrers Card
        const col3X = col2X + colW + 16;
        drawRoundRect(ctx, col3X, rowY, colW, rowH, 16, 'rgba(255, 255, 255, 0.03)', 'rgba(255, 255, 255, 0.06)', 1);
        ctx.textAlign = rtl ? 'right' : 'left';
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 16px ' + font;
        ctx.fillText(t('reportTopReferrers', 'Top Referrers'), rtl ? col3X + colW - 20 : col3X + 20, rowY + 34);

        const refs = Array.isArray(data.referrers) ? data.referrers.slice(0, 5) : [];
        if (refs.length === 0) {
            ctx.fillStyle = '#64748B';
            ctx.font = '400 13px ' + font;
            ctx.textAlign = 'center';
            ctx.fillText('— ' + t('no_referrer_data', 'Direct visits only') + ' —', col3X + (colW / 2), rowY + 150);
        } else {
            refs.forEach((r, i) => {
                const itemY = rowY + 68 + (i * 44);
                const domain = r.domain === 'Direct' ? t('direct_referrer', 'Direct Traffic') : (r.domain || 'Direct');

                ctx.textAlign = rtl ? 'right' : 'left';
                ctx.fillStyle = '#CBD5E1';
                ctx.font = '500 13px ' + font;
                const domX = rtl ? col3X + colW - 20 : col3X + 20;
                ctx.fillText(truncate(ctx, domain, colW - 100), domX, itemY);

                ctx.textAlign = rtl ? 'left' : 'right';
                ctx.fillStyle = '#38BDF8';
                ctx.font = 'bold 13px ' + font;
                const countX = rtl ? col3X + 20 : col3X + colW - 20;
                ctx.fillText(formatNumber(r.count || 0), countX, itemY);
            });
        }

        // Footer Watermark
        ctx.textAlign = 'center';
        ctx.fillStyle = '#64748B';
        ctx.font = '400 12px ' + font;
        ctx.fillText(t('reportWatermark', 'Generated by AtharLink Engine • Privacy-Friendly Click Analytics'), baseW / 2, baseH - 26);
    }

    // System Overview Report Card Generator (All Links in Simplified Infographic)
    function renderOverviewCard(canvas, data) {
        const rtl = isRtl();
        const font = getFontFamily();
        const baseW = 1160;
        const baseH = 820;
        const scale = 2; // Retina 2x

        canvas.width = baseW * scale;
        canvas.height = baseH * scale;
        canvas.style.width = '100%';
        canvas.style.maxWidth = baseW + 'px';

        const ctx = canvas.getContext('2d');
        ctx.scale(scale, scale);

        drawBackground(ctx, baseW, baseH);

        // Header: Brand & Meta
        ctx.direction = rtl ? 'rtl' : 'ltr';
        ctx.textAlign = rtl ? 'right' : 'left';

        const leftX = rtl ? baseW - 50 : 50;
        const rightX = rtl ? 50 : baseW - 50;

        // Logo
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 24px ' + font;
        ctx.fillText('AtharLink', leftX, 58);

        // System Overview Badge
        const badgeW = rtl ? 115 : 155;
        const badgeX = rtl ? baseW - 225 : 190;
        drawRoundRect(ctx, badgeX, 38, badgeW, 26, 13, 'rgba(99, 102, 241, 0.25)', 'rgba(99, 102, 241, 0.4)', 1);
        ctx.fillStyle = '#A5B4FC';
        ctx.font = 'bold 11px ' + font;
        ctx.textAlign = 'center';
        ctx.fillText(t('reportBadgeOverview', 'SYSTEM OVERVIEW'), badgeX + (badgeW / 2), 55);

        // Date & Period
        ctx.textAlign = rtl ? 'left' : 'right';
        ctx.font = '500 13px ' + font;
        ctx.fillStyle = '#94A3B8';
        const dateStr = (new Date()).toISOString().slice(0, 10);
        ctx.fillText(dateStr, rightX, 48);

        const periodLabel = data.period ? ('Period: ' + data.period.toUpperCase()) : '30 DAYS';
        ctx.fillStyle = '#6366F1';
        ctx.font = 'bold 12px ' + font;
        ctx.fillText(periodLabel, rightX, 68);

        // Headline Banner
        drawRoundRect(ctx, 45, 90, baseW - 90, 72, 16, 'rgba(255, 255, 255, 0.035)', 'rgba(255, 255, 255, 0.08)', 1);
        ctx.textAlign = rtl ? 'right' : 'left';
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 22px ' + font;
        ctx.fillText(t('reportOverviewTitle', 'All Links Performance Overview'), leftX, 122);

        ctx.fillStyle = '#94A3B8';
        ctx.font = '400 13px ' + font;
        ctx.fillText(t('reportOverviewSubtitle', 'System Engagement & Analytics Summary'), leftX, 145);

        // 4 KPI Cards Row
        const kpiY = 178;
        const kpiH = 92;
        const kpiW = (baseW - 90 - 36) / 4;

        const kpis = [
            { label: t('kpiActiveLinks', 'Active Links'), val: formatNumber(data.active_links || 0), sub: 'Total: ' + formatNumber(data.total_links || 0), color: '#38BDF8' },
            { label: t('kpiTotalClicks', 'Total Clicks'), val: formatNumber(data.total_clicks || 0), sub: 'All-Time: ' + formatNumber(data.all_time_clicks || 0), color: '#818CF8' },
            { label: t('kpiUniqueClicks', 'Unique Clicks'), val: formatNumber(data.unique_clicks || 0), sub: 'Rate: ' + (data.avg_unique_rate || 0) + '%', color: '#34D399' },
            { label: t('kpiPeakHours', 'Peak Hours'), val: data.peak_hour || '18:00 - 19:00', sub: 'High Engagement', color: '#F59E0B' }
        ];

        kpis.forEach((kpi, idx) => {
            const cardX = 45 + (idx * (kpiW + 12));
            drawRoundRect(ctx, cardX, kpiY, kpiW, kpiH, 14, 'rgba(255, 255, 255, 0.035)', 'rgba(255, 255, 255, 0.08)', 1);

            ctx.textAlign = rtl ? 'right' : 'left';
            const kpiContentX = rtl ? cardX + kpiW - 16 : cardX + 16;

            ctx.fillStyle = '#94A3B8';
            ctx.font = '500 12px ' + font;
            ctx.fillText(kpi.label, kpiContentX, kpiY + 26);

            ctx.fillStyle = kpi.color;
            ctx.font = 'bold 22px ' + font;
            ctx.fillText(kpi.val, kpiContentX, kpiY + 54);

            ctx.fillStyle = '#64748B';
            ctx.font = '400 11px ' + font;
            ctx.fillText(kpi.sub, kpiContentX, kpiY + 74);
        });

        // Row 2: Top Performing Links & Smart Traffic Channels
        const row2Y = 286;
        const row2H = 310;
        const cardGap = 16;
        const leftCardW = 570;
        const rightCardW = (baseW - 90 - cardGap) - leftCardW; // 484

        const topLinksCardX = rtl ? 45 + rightCardW + cardGap : 45;
        const channelsCardX = rtl ? 45 : 45 + leftCardW + cardGap;

        // --- Card A: Top Performing Links ---
        drawRoundRect(ctx, topLinksCardX, row2Y, leftCardW, row2H, 16, 'rgba(255, 255, 255, 0.03)', 'rgba(255, 255, 255, 0.06)', 1);
        ctx.textAlign = rtl ? 'right' : 'left';
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 16px ' + font;
        ctx.fillText(t('reportTopLinks', 'Top Performing Links'), rtl ? topLinksCardX + leftCardW - 20 : topLinksCardX + 20, row2Y + 32);

        const topLinks = Array.isArray(data.top_links) ? data.top_links.slice(0, 5) : [];
        const totalPeriodClicks = Math.max(1, Number(data.total_clicks) || 1);

        if (topLinks.length === 0) {
            ctx.fillStyle = '#64748B';
            ctx.font = '400 13px ' + font;
            ctx.textAlign = 'center';
            ctx.fillText('— ' + t('no_links_found', 'No links recorded yet') + ' —', topLinksCardX + (leftCardW / 2), row2Y + 160);
        } else {
            topLinks.forEach((l, i) => {
                const itemY = row2Y + 68 + (i * 48);
                const clicks = Number(l.total_clicks) || 0;
                const sharePct = Math.round((clicks / totalPeriodClicks) * 100);

                // Rank badge
                const rankX = rtl ? topLinksCardX + leftCardW - 42 : topLinksCardX + 20;
                const rankColors = ['#F59E0B', '#94A3B8', '#D97706', '#6366F1', '#6366F1'];
                drawRoundRect(ctx, rankX, itemY - 14, 22, 22, 11, 'rgba(255, 255, 255, 0.05)', rankColors[i] || '#6366F1', 1.5);
                ctx.fillStyle = rankColors[i] || '#FFFFFF';
                ctx.font = 'bold 11px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('#' + (i + 1), rankX + 11, itemY + 1);

                // Slug & Title
                ctx.textAlign = rtl ? 'right' : 'left';
                ctx.fillStyle = '#FFFFFF';
                ctx.font = 'bold 13.5px ' + font;
                const slugX = rtl ? topLinksCardX + leftCardW - 74 : topLinksCardX + 52;
                ctx.fillText('/' + truncate(ctx, l.slug, 220), slugX, itemY - 2);

                ctx.fillStyle = '#94A3B8';
                ctx.font = '400 11.5px ' + font;
                const titleStr = l.title || l.target_url || '';
                ctx.fillText(truncate(ctx, titleStr, 220), slugX, itemY + 14);

                // Total Clicks & Share Bar
                ctx.textAlign = rtl ? 'left' : 'right';
                ctx.fillStyle = '#818CF8';
                ctx.font = 'bold 13.5px ' + font;
                const clicksX = rtl ? topLinksCardX + 20 : topLinksCardX + leftCardW - 20;
                ctx.fillText(formatNumber(clicks) + ' clicks (' + sharePct + '%)', clicksX, itemY - 2);

                // Traffic share bar
                const barW = 140;
                const barX = rtl ? topLinksCardX + 20 : topLinksCardX + leftCardW - 20 - barW;
                drawRoundRect(ctx, barX, itemY + 7, barW, 5, 2.5, 'rgba(255, 255, 255, 0.08)');
                drawRoundRect(ctx, barX, itemY + 7, Math.max(6, barW * (sharePct / 100)), 5, 2.5, '#6366F1');
            });
        }

        // --- Card B: Smart Traffic Channels ---
        drawRoundRect(ctx, channelsCardX, row2Y, rightCardW, row2H, 16, 'rgba(255, 255, 255, 0.03)', 'rgba(255, 255, 255, 0.06)', 1);
        ctx.textAlign = rtl ? 'right' : 'left';
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 16px ' + font;
        ctx.fillText(t('channelsTitle', 'Smart Traffic Channels'), rtl ? channelsCardX + rightCardW - 20 : channelsCardX + 20, row2Y + 32);

        const channelsData = data.channels || {};
        const chList = [
            { key: 'social', label: t('channelSocial', 'Social Media'), color: '#6366F1', count: channelsData.social?.count || 0, rate: channelsData.social?.rate || 0 },
            { key: 'search', label: t('channelSearch', 'Search Engines'), color: '#10B981', count: channelsData.search?.count || 0, rate: channelsData.search?.rate || 0 },
            { key: 'direct', label: t('channelDirect', 'Direct Traffic'), color: '#22D3EE', count: channelsData.direct?.count || 0, rate: channelsData.direct?.rate || 0 },
            { key: 'referral', label: t('channelReferral', 'External Referrals'), color: '#F59E0B', count: channelsData.referral?.count || 0, rate: channelsData.referral?.rate || 0 }
        ];

        chList.forEach((ch, idx) => {
            const chY = row2Y + 68 + (idx * 56);

            // Channel Indicator & Name
            ctx.textAlign = rtl ? 'right' : 'left';
            ctx.fillStyle = '#FFFFFF';
            ctx.font = '600 13.5px ' + font;
            const nameX = rtl ? channelsCardX + rightCardW - 20 : channelsCardX + 20;
            ctx.fillText('● ' + ch.label, nameX, chY);

            // Channel count & rate
            ctx.textAlign = rtl ? 'left' : 'right';
            ctx.fillStyle = ch.color;
            ctx.font = 'bold 13.5px ' + font;
            const valX = rtl ? channelsCardX + 20 : channelsCardX + rightCardW - 20;
            ctx.fillText(formatNumber(ch.count) + ' (' + ch.rate + '%)', valX, chY);

            // Progress bar
            const barX = channelsCardX + 20;
            const barW = rightCardW - 40;
            drawRoundRect(ctx, barX, chY + 9, barW, 6, 3, 'rgba(255, 255, 255, 0.08)');
            drawRoundRect(ctx, barX, chY + 9, Math.max(6, barW * (ch.rate / 100)), 6, 3, ch.color);
        });

        // Row 3: 3 Metric Cards (Countries, Devices, Browsers)
        const row3Y = 612;
        const row3H = 160;
        const col3W = (baseW - 90 - 32) / 3;

        // 1. Countries
        const col1X = rtl ? 45 + (col3W * 2) + 32 : 45;
        drawRoundRect(ctx, col1X, row3Y, col3W, row3H, 16, 'rgba(255, 255, 255, 0.03)', 'rgba(255, 255, 255, 0.06)', 1);
        ctx.textAlign = rtl ? 'right' : 'left';
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 15px ' + font;
        ctx.fillText(t('reportTopCountries', 'Top Countries'), rtl ? col1X + col3W - 18 : col1X + 18, row3Y + 28);

        const countries = Array.isArray(data.countries) ? data.countries.slice(0, 3) : [];
        if (countries.length === 0) {
            ctx.fillStyle = '#64748B';
            ctx.font = '400 12px ' + font;
            ctx.textAlign = 'center';
            ctx.fillText('— ' + t('no_country_data', 'No country data') + ' —', col1X + (col3W / 2), row3Y + 85);
        } else {
            countries.forEach((c, i) => {
                const cY = row3Y + 56 + (i * 32);
                ctx.textAlign = rtl ? 'right' : 'left';
                ctx.fillStyle = '#CBD5E1';
                ctx.font = '600 12.5px ' + font;
                const cX = rtl ? col1X + col3W - 18 : col1X + 18;
                ctx.fillText('● ' + (c.country_code || 'XX'), cX, cY);

                ctx.textAlign = rtl ? 'left' : 'right';
                ctx.fillStyle = '#38BDF8';
                ctx.font = 'bold 12.5px ' + font;
                const valX = rtl ? col1X + 18 : col1X + col3W - 18;
                ctx.fillText(formatNumber(c.count) + ' clicks', valX, cY);
            });
        }

        // 2. Devices
        const col2X = 45 + col3W + 16;
        drawRoundRect(ctx, col2X, row3Y, col3W, row3H, 16, 'rgba(255, 255, 255, 0.03)', 'rgba(255, 255, 255, 0.06)', 1);
        ctx.textAlign = rtl ? 'right' : 'left';
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 15px ' + font;
        ctx.fillText(t('reportTopDevices', 'Device Breakdown'), rtl ? col2X + col3W - 18 : col2X + 18, row3Y + 28);

        const devices = Array.isArray(data.devices) ? data.devices.slice(0, 3) : [];
        const devTotal = devices.reduce((sum, d) => sum + (Number(d.count) || 0), 0) || 1;
        if (devices.length === 0) {
            ctx.fillStyle = '#64748B';
            ctx.font = '400 12px ' + font;
            ctx.textAlign = 'center';
            ctx.fillText('— ' + t('no_device_data', 'No device data') + ' —', col2X + (col3W / 2), row3Y + 85);
        } else {
            devices.forEach((d, i) => {
                const dY = row3Y + 56 + (i * 32);
                const count = Number(d.count) || 0;
                const pct = Math.round((count / devTotal) * 100);

                ctx.textAlign = rtl ? 'right' : 'left';
                ctx.fillStyle = '#CBD5E1';
                ctx.font = '600 12.5px ' + font;
                const dX = rtl ? col2X + col3W - 18 : col2X + 18;
                ctx.fillText((d.device_type || 'other').toUpperCase(), dX, dY);

                ctx.textAlign = rtl ? 'left' : 'right';
                ctx.fillStyle = '#34D399';
                ctx.font = 'bold 12.5px ' + font;
                const valX = rtl ? col2X + 18 : col2X + col3W - 18;
                ctx.fillText(pct + '%', valX, dY);
            });
        }

        // 3. Browsers
        const col3X = rtl ? 45 : 45 + (col3W * 2) + 32;
        drawRoundRect(ctx, col3X, row3Y, col3W, row3H, 16, 'rgba(255, 255, 255, 0.03)', 'rgba(255, 255, 255, 0.06)', 1);
        ctx.textAlign = rtl ? 'right' : 'left';
        ctx.fillStyle = '#FFFFFF';
        ctx.font = 'bold 15px ' + font;
        ctx.fillText(t('browsersTitle', 'Most Used Browsers'), rtl ? col3X + col3W - 18 : col3X + 18, row3Y + 28);

        const browsers = Array.isArray(data.browsers) ? data.browsers.slice(0, 3) : [];
        if (browsers.length === 0) {
            ctx.fillStyle = '#64748B';
            ctx.font = '400 12px ' + font;
            ctx.textAlign = 'center';
            ctx.fillText('— ' + t('no_browsers_data', 'No browser data') + ' —', col3X + (col3W / 2), row3Y + 85);
        } else {
            browsers.forEach((b, i) => {
                const bY = row3Y + 56 + (i * 32);
                ctx.textAlign = rtl ? 'right' : 'left';
                ctx.fillStyle = '#CBD5E1';
                ctx.font = '600 12.5px ' + font;
                const bX = rtl ? col3X + col3W - 18 : col3X + 18;
                ctx.fillText(b.browser || 'Other', bX, bY);

                ctx.textAlign = rtl ? 'left' : 'right';
                ctx.fillStyle = '#22D3EE';
                ctx.font = 'bold 12.5px ' + font;
                const valX = rtl ? col3X + 18 : col3X + col3W - 18;
                ctx.fillText(formatNumber(b.count) + ' clicks', valX, bY);
            });
        }

        // Footer Watermark
        ctx.textAlign = 'center';
        ctx.fillStyle = '#64748B';
        ctx.font = '400 12px ' + font;
        ctx.fillText(t('reportWatermark', 'Generated by AtharLink Click Analytics Engine • Tamoza.net'), baseW / 2, baseH - 20);
    }

    // Modal Controller & Trigger Bindings
    let currentDownloadFilename = 'atharlink_report.png';

    function openReportModal(renderFn, data, filename) {
        const modalEl = document.getElementById('reportImageModal');
        const canvas = document.getElementById('reportCardCanvas');
        if (!modalEl || !canvas) return;

        currentDownloadFilename = filename || 'atharlink_report.png';
        renderFn(canvas, data);

        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();
    }

    // Attach actions to window
    window.AtharReportCard = {
        renderSingleLinkCard: renderSingleLinkCard,
        renderOverviewCard: renderOverviewCard,
        openSingleLink: function (data) {
            const slug = data.slug ? data.slug.replace(/[^a-zA-Z0-9_-]/g, '') : 'link';
            openReportModal(renderSingleLinkCard, data, 'atharlink_report_' + slug + '.png');
        },
        openOverview: function (data) {
            openReportModal(renderOverviewCard, data, 'atharlink_overview_report.png');
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        // Download Button inside Modal
        const downloadBtn = document.getElementById('downloadReportImageBtn');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', function () {
                const canvas = document.getElementById('reportCardCanvas');
                if (!canvas) return;

                const a = document.createElement('a');
                a.href = canvas.toDataURL('image/png');
                a.download = currentDownloadFilename;
                a.click();
            });
        }

        // Copy Image Button inside Modal (if present)
        const copyBtn = document.getElementById('copyReportImageBtn');
        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                const canvas = document.getElementById('reportCardCanvas');
                if (!canvas || !canvas.toBlob || !navigator.clipboard) return;

                canvas.toBlob(function (blob) {
                    if (!blob) return;
                    navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })])
                        .then(() => {
                            const original = copyBtn.innerHTML;
                            copyBtn.innerHTML = '<svg class="tamoza-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> ' + t('copyImageSuccess', 'Copied!');
                            copyBtn.classList.add('btn-success');
                            setTimeout(() => {
                                copyBtn.innerHTML = original;
                                copyBtn.classList.remove('btn-success');
                            }, 2000);
                        })
                        .catch(() => {
                            alert(t('copyManually', 'Could not copy automatically. Please click download.'));
                        });
                }, 'image/png');
            });
        }
    });

})();
