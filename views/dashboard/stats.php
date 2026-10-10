<?php
declare(strict_types=1);

/**
 * Tamoza UI Standard - Verified: 8pt Logic, Premium Glassmorphism, Centered Layout, Indigo Brand DNA, Cairo Typography.
 */

use AtharLink\Helpers;
use AtharLink\I18n;
use AtharLink\Icon;

$link = $stats['link'];
$period = $stats['period'] ?? '30d';
$trackUrl = Helpers::trackingUrl($link['slug']);
?>

<!-- Header & Quick Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="<?= Helpers::baseUrl('index.php?page=links') ?>" class="btn btn-tamoza-subtle btn-sm"><?= I18n::t('back_to_links') ?></a>
            <h2 class="fw-bold mb-0"><?= I18n::t('stats_title_prefix') ?>/<?= Helpers::e($link['slug']) ?></h2>
        </div>
        <p class="text-secondary small mb-0">
            <?= !empty($link['title']) ? Helpers::e($link['title']) . ' — ' : '' ?>
            <?= I18n::t('destination_prefix') ?><a href="<?= Helpers::e($link['target_url']) ?>" target="_blank" class="text-secondary d-inline-flex align-items-center gap-1"><span><?= Helpers::e($link['target_url']) ?></span><?= Icon::get('external-link', '', 12) ?></a>
        </p>
    </div>

    <!-- Quick Tools -->
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <button type="button" class="btn btn-tamoza-primary d-inline-flex align-items-center gap-2" data-copy-url="<?= Helpers::e($trackUrl) ?>">
            <?= Icon::get('copy', '', 15) ?>
            <span><?= I18n::t('copy_track_url') ?></span>
        </button>

        <button type="button" class="btn btn-tamoza-secondary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#qrCodeModal" data-qr-url="<?= Helpers::e($trackUrl) ?>" data-qr-title="<?= Helpers::e($link['slug']) ?>">
            <?= Icon::get('qr-code', '', 15) ?>
            <span><?= I18n::t('qr_code_btn') ?></span>
        </button>

        <button type="button" class="btn btn-tamoza-secondary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#embedCounterModal"
            data-slug="<?= Helpers::e($link['slug']) ?>"
            data-total-clicks="<?= (int)$stats['total_clicks'] ?>"
            data-unique-clicks="<?= (int)$stats['unique_clicks'] ?>">
            <?= Icon::get('code', '', 15) ?>
            <span><?= I18n::t('embed_counter_btn') ?></span>
        </button>

        <button type="button" class="btn btn-tamoza-secondary d-inline-flex align-items-center gap-2" id="exportSingleLinkImageBtn">
            <?= Icon::get('image', '', 15) ?>
            <span><?= I18n::t('export_image_btn') ?></span>
        </button>

        <div class="tamoza-segmented-group">
            <a href="<?= Helpers::baseUrl('index.php?page=stats&id=' . $link['id'] . '&period=24h') ?>" class="tamoza-segmented-btn <?= $period === '24h' ? 'active' : '' ?>"><?= I18n::t('period_24h') ?></a>
            <a href="<?= Helpers::baseUrl('index.php?page=stats&id=' . $link['id'] . '&period=7d') ?>" class="tamoza-segmented-btn <?= $period === '7d' ? 'active' : '' ?>"><?= I18n::t('period_7d') ?></a>
            <a href="<?= Helpers::baseUrl('index.php?page=stats&id=' . $link['id'] . '&period=30d') ?>" class="tamoza-segmented-btn <?= $period === '30d' ? 'active' : '' ?>"><?= I18n::t('period_30d') ?></a>
            <a href="<?= Helpers::baseUrl('index.php?page=stats&id=' . $link['id'] . '&period=all') ?>" class="tamoza-segmented-btn <?= $period === 'all' ? 'active' : '' ?>"><?= I18n::t('period_all') ?></a>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="tamoza-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-semibold"><?= I18n::t('total_clicks_label') ?></span>
                <div class="tamoza-stat-icon" style="background: rgba(99, 102, 241, 0.12); color: #6366F1;"><?= Icon::get('mouse-pointer') ?></div>
            </div>
            <h2 class="fw-bold my-2" style="color: #6366F1;"><?= number_format($stats['total_clicks']) ?></h2>
            <span class="text-secondary small"><?= I18n::t('during_period') ?></span>
            <?php if (!empty($link['initial_clicks'])): ?>
                <div class="text-secondary small mt-1"><?= I18n::t('includes_initial_clicks', ['count' => number_format($link['initial_clicks'])]) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="tamoza-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-semibold"><?= I18n::t('unique_clicks_label') ?></span>
                <div class="tamoza-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10B981;"><?= Icon::get('target') ?></div>
            </div>
            <h2 class="fw-bold my-2" style="color: #10B981;"><?= number_format($stats['unique_clicks']) ?></h2>
            <span class="text-success small fw-medium"><?= I18n::t('conversion_rate_label') ?><?= $stats['conversion_rate'] ?>%</span>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="tamoza-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-semibold"><?= I18n::t('link_status_label') ?></span>
                <div class="tamoza-stat-icon" style="background: rgba(34, 211, 238, 0.12); color: #22D3EE;"><?= Icon::get('link-2') ?></div>
            </div>
            <div class="my-2">
                <?php if ((int)$link['is_active'] === 1): ?>
                    <span class="tamoza-badge badge-success fs-6"><?= I18n::t('status_active_ready') ?></span>
                <?php else: ?>
                    <span class="tamoza-badge badge-danger fs-6"><?= I18n::t('status_disabled') ?></span>
                <?php endif; ?>
            </div>
            <span class="text-secondary small"><?= I18n::t('created_date_label') ?><?= Helpers::formatDate($link['created_at'], 'Y-m-d H:i') ?></span>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Timeline Chart -->
    <div class="col-12 col-lg-8">
        <div class="tamoza-card h-100">
            <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <?= Icon::get('trending-up', 'text-indigo') ?>
                <span><?= I18n::t('chart_growth_title') ?></span>
            </h4>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="linkTimelineChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Referrers Doughnut -->
    <div class="col-12 col-lg-4">
        <div class="tamoza-card h-100">
            <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <?= Icon::get('share-2', 'text-indigo') ?>
                <span><?= I18n::t('chart_ref_title') ?></span>
            </h4>
            <div style="position: relative; height: 240px; width: 100%;">
                <canvas id="linkReferrerChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Devices & Top Countries -->
<div class="row g-4 mb-4">
    <!-- Devices -->
    <div class="col-12 col-lg-6">
        <div class="tamoza-card h-100">
            <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <?= Icon::get('smartphone', 'text-indigo') ?>
                <span><?= I18n::t('chart_dev_title') ?></span>
            </h4>
            <div style="position: relative; height: 240px; width: 100%;">
                <canvas id="linkDeviceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Countries Table -->
    <div class="col-12 col-lg-6">
        <div class="tamoza-card h-100">
            <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <?= Icon::get('globe', 'text-indigo') ?>
                <span><?= I18n::t('chart_geo_title_stats') ?></span>
            </h4>
            <div class="tamoza-table-container">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?= I18n::t('country') ?></th>
                            <th class="text-end"><?= I18n::t('click_count') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats['countries'])): ?>
                            <tr><td colspan="2" class="text-center text-secondary py-3"><?= I18n::t('no_country_data') ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($stats['countries'] as $c): ?>
                                <tr>
                                    <td>
                                        <span class="tamoza-badge badge-indigo me-1"><?= Helpers::e($c['country_code']) ?></span>
                                        <span class="fw-medium"><?= Helpers::e($c['country_name'] ?? Helpers::getCountryName($c['country_code'])) ?></span>
                                    </td>
                                    <td class="text-end fw-bold"><?= number_format($c['count']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Granular Click Logs Table -->
<div class="tamoza-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <?= Icon::get('activity', 'text-success') ?>
            <span><?= I18n::t('granular_logs_title') ?></span>
        </h4>
        <div class="dropdown">
            <button class="btn btn-tamoza-secondary btn-sm dropdown-toggle d-inline-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-popper-config='{"strategy":"fixed"}'>
                <?= Icon::get('download', '', 14) ?>
                <span><?= I18n::t('export_data') ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2">
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= Helpers::baseUrl('index.php?action=export_clicks&link_id=' . $link['id'] . '&format=csv') ?>"><?= Icon::get('download', '', 14) ?> <?= I18n::t('export_clicks_csv_btn') ?></a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= Helpers::baseUrl('index.php?action=export_clicks&link_id=' . $link['id'] . '&format=json') ?>"><?= Icon::get('download', '', 14) ?> <?= I18n::t('export_clicks_json_btn') ?></a></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item text-primary d-flex align-items-center gap-2" href="javascript:void(0)" onclick="document.getElementById('exportSingleLinkImageBtn')?.click();"><?= Icon::get('image', '', 14) ?> <?= I18n::t('export_image_btn') ?></a></li>
            </ul>
        </div>
    </div>
    <div class="tamoza-table-container">
        <table class="table table-hover align-middle mb-0 small">
            <thead>
                <tr>
                    <th><?= I18n::t('col_ip_address') ?></th>
                    <th><?= I18n::t('col_country') ?></th>
                    <th><?= I18n::t('col_click_type') ?></th>
                    <th><?= I18n::t('col_browser_os') ?></th>
                    <th><?= I18n::t('col_referrer') ?></th>
                    <th><?= I18n::t('col_click_time') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stats['recent_logs'])): ?>
                    <tr><td colspan="6" class="text-center py-4 text-secondary"><?= I18n::t('no_logs_yet') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($stats['recent_logs'] as $log): ?>
                        <tr>
                            <td><code class="text-primary fw-medium"><?= Helpers::e(!empty($log['ip_address']) ? $log['ip_address'] : ($log['ip_hash'] ? substr($log['ip_hash'], 0, 14) . '...' : '—')) ?></code></td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="tamoza-badge badge-indigo"><?= Helpers::e($log['country_code']) ?></span>
                                    <span class="small fw-medium text-secondary text-truncate" style="max-width: 140px;" title="<?= Helpers::e($log['country_name'] ?? Helpers::getCountryName($log['country_code'])) ?>"><?= Helpers::e($log['country_name'] ?? Helpers::getCountryName($log['country_code'])) ?></span>
                                </div>
                            </td>
                            <td>
                                <?= (int)$log['is_unique'] === 1 ? '<span class="tamoza-badge badge-success">' . I18n::t('type_unique_badge') . '</span>' : '<span class="tamoza-badge badge-muted">' . I18n::t('type_repeat_badge') . '</span>' ?>
                            </td>
                            <td>
                                <?= Helpers::e($log['browser']) ?> / <?= Helpers::e($log['platform']) ?>
                                <span class="tamoza-badge badge-muted ms-1"><?= Helpers::e($log['device_type']) ?></span>
                            </td>
                            <td><?= Helpers::e($log['referrer_domain'] ?? I18n::t('direct_referrer')) ?></td>
                            <td class="text-secondary"><?= Helpers::e(Helpers::formatDate($log['clicked_at'], 'Y-m-d H:i:s')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const isEn = <?= json_encode(I18n::getLang() === 'en') ?>;
    const fontFamily = isEn ? '-apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", sans-serif' : 'Cairo';

    // 1. Timeline
    const timelineData = <?= json_encode($stats['timeline']) ?>;
    const ctxTimeline = document.getElementById('linkTimelineChart').getContext('2d');
    new Chart(ctxTimeline, {
        type: 'line',
        data: {
            labels: timelineData.labels,
            datasets: [
                {
                    label: <?= json_encode(I18n::t('kpi_total_clicks')) ?>,
                    data: timelineData.totals,
                    borderColor: '#6366F1',
                    backgroundColor: 'rgba(99, 102, 241, 0.15)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5
                },
                {
                    label: <?= json_encode(I18n::t('kpi_unique_clicks')) ?>,
                    data: timelineData.uniques,
                    borderColor: '#22D3EE',
                    backgroundColor: 'rgba(34, 211, 238, 0.1)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { font: { family: fontFamily } } }
            },
            scales: { y: { beginAtZero: true, grid: { color: 'rgba(140, 150, 170, 0.08)' } } }
        }
    });

    // 2. Referrers
    const refData = <?= json_encode($stats['referrers']) ?>;
    const ctxRef = document.getElementById('linkReferrerChart').getContext('2d');
    const directLabel = <?= json_encode(I18n::t('direct_referrer')) ?>;
    new Chart(ctxRef, {
        type: 'doughnut',
        data: {
            labels: refData.map(r => r.domain === 'Direct' ? directLabel : r.domain),
            datasets: [{
                data: refData.map(r => r.count),
                backgroundColor: ['#6366F1', '#22D3EE', '#10B981', '#F59E0B', '#EC4899', '#64748B']
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { font: { family: fontFamily } } }
            }
        }
    });

    // 3. Devices
    const devData = <?= json_encode($stats['devices']) ?>;
    const ctxDev = document.getElementById('linkDeviceChart').getContext('2d');
    const devLabelsMap = {
        desktop: <?= json_encode(I18n::t('device_desktop')) ?>,
        mobile:  <?= json_encode(I18n::t('device_mobile')) ?>,
        tablet:  <?= json_encode(I18n::t('device_tablet')) ?>,
        bot:     <?= json_encode(I18n::t('device_bot')) ?>,
        other:   <?= json_encode(I18n::t('device_other')) ?>
    };
    new Chart(ctxDev, {
        type: 'bar',
        data: {
            labels: devData.map(d => devLabelsMap[d.device_type] || devLabelsMap.other),
            datasets: [{
                label: <?= json_encode(I18n::t('clicks_label')) ?>,
                data: devData.map(d => d.count),
                backgroundColor: '#10B981',
                borderRadius: 8
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { font: { family: fontFamily } } }
            }
        }
    });

    // 4. Export as Infographic Image Handler
    const singleLinkData = {
        slug: <?= json_encode($link['slug']) ?>,
        title: <?= json_encode($link['title'] ?? '') ?>,
        target_url: <?= json_encode($link['target_url']) ?>,
        is_active: <?= json_encode((int)$link['is_active']) ?>,
        total_clicks: <?= json_encode($stats['total_clicks']) ?>,
        unique_clicks: <?= json_encode($stats['unique_clicks']) ?>,
        conversion_rate: <?= json_encode($stats['conversion_rate']) ?>,
        countries: <?= json_encode($stats['countries'] ?? []) ?>,
        devices: <?= json_encode($stats['devices'] ?? []) ?>,
        referrers: <?= json_encode($stats['referrers'] ?? []) ?>
    };

    document.getElementById('exportSingleLinkImageBtn')?.addEventListener('click', function () {
        if (window.AtharReportCard) {
            window.AtharReportCard.openSingleLink(singleLinkData);
        }
    });
});
</script>
