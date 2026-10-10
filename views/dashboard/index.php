<?php
declare(strict_types=1);

/**
 * Tamoza UI Standard - Verified: 8pt Logic, Premium Glassmorphism, Centered Layout, Indigo Brand DNA, Dual Language.
 */

use AtharLink\Helpers;
use AtharLink\I18n;
use AtharLink\Icon;

$period = $stats['period'] ?? '30d';
?>

<!-- Page Header & Period Filters -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1"><?= Helpers::e(I18n::t('analytics_title')) ?></h2>
        <p class="text-secondary small mb-0"><?= Helpers::e(I18n::t('analytics_desc')) ?></p>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <!-- Export Summary as Image Button -->
        <button type="button" class="btn btn-tamoza-secondary d-inline-flex align-items-center gap-2" id="exportOverviewImageBtn">
            <?= Icon::get('image', '', 16) ?>
            <span><?= Helpers::e(I18n::t('export_summary_image_btn')) ?></span>
        </button>

        <!-- Time Range Filter Pills -->
        <div class="tamoza-segmented-group">
            <a href="<?= Helpers::baseUrl('index.php?page=overview&period=24h') ?>" class="tamoza-segmented-btn <?= $period === '24h' ? 'active' : '' ?>"><?= Helpers::e(I18n::t('filter_24h')) ?></a>
            <a href="<?= Helpers::baseUrl('index.php?page=overview&period=7d') ?>" class="tamoza-segmented-btn <?= $period === '7d' ? 'active' : '' ?>"><?= Helpers::e(I18n::t('filter_7d')) ?></a>
            <a href="<?= Helpers::baseUrl('index.php?page=overview&period=30d') ?>" class="tamoza-segmented-btn <?= $period === '30d' ? 'active' : '' ?>"><?= Helpers::e(I18n::t('filter_30d')) ?></a>
            <a href="<?= Helpers::baseUrl('index.php?page=overview&period=all') ?>" class="tamoza-segmented-btn <?= $period === 'all' ? 'active' : '' ?>"><?= Helpers::e(I18n::t('filter_all')) ?></a>
        </div>
    </div>
</div>

<!-- Overview KPI Cards (8pt Spacing, 16px Radius, Soft Shadow) -->
<div class="row g-3 mb-4">
    <!-- Total Clicks -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="tamoza-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-semibold"><?= Helpers::e(I18n::t('kpi_total_clicks')) ?></span>
                <div class="tamoza-stat-icon" style="background: rgba(99, 102, 241, 0.12); color: #6366F1;"><?= Icon::get('mouse-pointer') ?></div>
            </div>
            <h2 class="fw-bold mb-1"><?= number_format($stats['total_clicks']) ?></h2>
            <span class="text-secondary small"><?= Helpers::e(I18n::t('kpi_all_time')) ?>: <?= number_format($stats['all_time_clicks']) ?></span>
        </div>
    </div>

    <!-- Unique Clicks -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="tamoza-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-semibold"><?= Helpers::e(I18n::t('kpi_unique_clicks')) ?></span>
                <div class="tamoza-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10B981;"><?= Icon::get('target') ?></div>
            </div>
            <h2 class="fw-bold mb-1"><?= number_format($stats['unique_clicks']) ?></h2>
            <span class="text-success small fw-medium"><?= Helpers::e(I18n::t('kpi_conversion_rate')) ?>: <?= $stats['avg_unique_rate'] ?>%</span>
        </div>
    </div>

    <!-- Active Links -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="tamoza-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-semibold"><?= Helpers::e(I18n::t('kpi_active_links')) ?></span>
                <div class="tamoza-stat-icon" style="background: rgba(34, 211, 238, 0.12); color: #22D3EE;"><?= Icon::get('link-2') ?></div>
            </div>
            <h2 class="fw-bold mb-1"><?= number_format($stats['active_links']) ?></h2>
            <span class="text-secondary small"><?= Helpers::e(I18n::t('kpi_out_of_links', ['total' => number_format($stats['total_links'])])) ?></span>
        </div>
    </div>

    <!-- Peak Hours -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="tamoza-stat-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-semibold"><?= Helpers::e(I18n::t('kpi_peak_hours')) ?></span>
                <div class="tamoza-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #F59E0B;"><?= Icon::get('clock') ?></div>
            </div>
            <h3 class="fw-bold mb-1 mt-1"><?= Helpers::e($stats['peak_hour']) ?></h3>
            <span class="text-secondary small"><?= Helpers::e(I18n::t('kpi_peak_desc')) ?></span>
        </div>
    </div>
</div>

<!-- Charts Section (24px Radius Glassmorphism Surfaces) -->
<div class="row g-4 mb-4">
    <!-- Timeline Line/Area Chart -->
    <div class="col-12 col-lg-8">
        <div class="tamoza-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
                    <?= Icon::get('trending-up', 'text-indigo') ?>
                    <span><?= Helpers::e(I18n::t('chart_timeline_title')) ?></span>
                </h4>
                <span class="tamoza-badge badge-indigo"><?= Helpers::e(I18n::t('chart_live_badge')) ?></span>
            </div>
            <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="timelineChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Referrer Breakdown Doughnut Chart -->
    <div class="col-12 col-lg-4">
        <div class="tamoza-card h-100">
            <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <?= Icon::get('share-2', 'text-indigo') ?>
                <span><?= Helpers::e(I18n::t('chart_referrers_title')) ?></span>
            </h4>
            <div style="position: relative; height: 260px; width: 100%;">
                <canvas id="referrerChart"></canvas>
            </div>
            <div class="mt-3 small text-secondary">
                <?php if (empty($stats['referrers'])): ?>
                    <p class="text-center my-3"><?= Helpers::e(I18n::t('chart_no_referrers')) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Device Distribution Bar Chart -->
    <div class="col-12 col-lg-6">
        <div class="tamoza-card h-100">
            <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <?= Icon::get('smartphone', 'text-indigo') ?>
                <span><?= Helpers::e(I18n::t('chart_devices_title')) ?></span>
            </h4>
            <div style="position: relative; height: 260px; width: 100%;">
                <canvas id="deviceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Countries Geographic Table -->
    <div class="col-12 col-lg-6">
        <div class="tamoza-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
                    <?= Icon::get('globe', 'text-indigo') ?>
                    <span><?= Helpers::e(I18n::t('chart_geo_title')) ?></span>
                </h4>
                <span class="tamoza-badge badge-muted"><?= Helpers::e(I18n::t('iso_code')) ?></span>
            </div>
            
            <div class="tamoza-table-container">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?= Helpers::e(I18n::t('country')) ?></th>
                            <th class="text-end"><?= Helpers::e(I18n::t('click_count')) ?></th>
                            <th class="text-end"><?= Helpers::e(I18n::t('percentage')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats['countries'])): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-secondary"><?= Helpers::e(I18n::t('no_clicks_period')) ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stats['countries'] as $c): ?>
                                <?php 
                                    $pct = $stats['total_clicks'] > 0 ? round(($c['count'] / $stats['total_clicks']) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="tamoza-badge badge-indigo me-1"><?= Helpers::e($c['country_code']) ?></span>
                                        <span class="fw-medium"><?= Helpers::e($c['country_name'] ?? Helpers::getCountryName($c['country_code'])) ?></span>
                                    </td>
                                    <td class="text-end fw-bold"><?= number_format($c['count']) ?></td>
                                    <td class="text-end text-secondary"><?= $pct ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: 24-Hour Activity Curve & Smart Traffic Channels -->
<div class="row g-4 mb-4">
    <!-- 24-Hour Activity Curve -->
    <div class="col-12 col-lg-8">
        <div class="tamoza-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                        <?= Icon::get('zap', 'text-warning') ?>
                        <span><?= Helpers::e(I18n::t('chart_hourly_title')) ?></span>
                    </h4>
                    <p class="text-secondary small mb-0"><?= Helpers::e(I18n::t('chart_hourly_desc')) ?></p>
                </div>
                <span class="tamoza-badge badge-warning"><?= Helpers::e(I18n::t('hourly_badge')) ?></span>
            </div>
            <div style="position: relative; height: 280px; width: 100%;">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Smart Traffic Channels -->
    <div class="col-12 col-lg-4">
        <div class="tamoza-card h-100">
            <div class="mb-3">
                <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                    <?= Icon::get('compass', 'text-indigo') ?>
                    <span><?= Helpers::e(I18n::t('channels_title')) ?></span>
                </h4>
                <p class="text-secondary small mb-0"><?= Helpers::e(I18n::t('channels_desc')) ?></p>
            </div>
            <div style="position: relative; height: 180px; width: 100%;">
                <canvas id="channelChart"></canvas>
            </div>
            <div class="mt-3 pt-2 border-top" style="border-color: var(--tamoza-border) !important;">
                <div class="d-flex justify-content-between align-items-center mb-2 small">
                    <span class="d-flex align-items-center gap-2">
                        <span style="width: 10px; height: 10px; border-radius: 50%; background: #6366F1; display: inline-block;"></span>
                        <?= Helpers::e(I18n::t('channel_social')) ?>
                    </span>
                    <span class="fw-bold"><?= number_format($stats['channels']['social']['count']) ?> <span class="text-secondary fw-normal">(<?= $stats['channels']['social']['rate'] ?>%)</span></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2 small">
                    <span class="d-flex align-items-center gap-2">
                        <span style="width: 10px; height: 10px; border-radius: 50%; background: #10B981; display: inline-block;"></span>
                        <?= Helpers::e(I18n::t('channel_search')) ?>
                    </span>
                    <span class="fw-bold"><?= number_format($stats['channels']['search']['count']) ?> <span class="text-secondary fw-normal">(<?= $stats['channels']['search']['rate'] ?>%)</span></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2 small">
                    <span class="d-flex align-items-center gap-2">
                        <span style="width: 10px; height: 10px; border-radius: 50%; background: #22D3EE; display: inline-block;"></span>
                        <?= Helpers::e(I18n::t('channel_direct')) ?>
                    </span>
                    <span class="fw-bold"><?= number_format($stats['channels']['direct']['count']) ?> <span class="text-secondary fw-normal">(<?= $stats['channels']['direct']['rate'] ?>%)</span></span>
                </div>
                <div class="d-flex justify-content-between align-items-center small">
                    <span class="d-flex align-items-center gap-2">
                        <span style="width: 10px; height: 10px; border-radius: 50%; background: #F59E0B; display: inline-block;"></span>
                        <?= Helpers::e(I18n::t('channel_referral')) ?>
                    </span>
                    <span class="fw-bold"><?= number_format($stats['channels']['referral']['count']) ?> <span class="text-secondary fw-normal">(<?= $stats['channels']['referral']['rate'] ?>%)</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 4: Top Performing Links Leaderboard -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="tamoza-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                        <?= Icon::get('trophy', 'text-warning') ?>
                        <span><?= Helpers::e(I18n::t('leaderboard_title')) ?></span>
                    </h4>
                    <p class="text-secondary small mb-0"><?= Helpers::e(I18n::t('leaderboard_desc')) ?></p>
                </div>
                <a href="<?= Helpers::baseUrl('index.php?page=links') ?>" class="btn btn-sm btn-tamoza-secondary d-inline-flex align-items-center gap-1">
                    <span><?= Helpers::e(I18n::t('nav_links')) ?></span>
                    <?= Icon::get('external-link', '', 14) ?>
                </a>
            </div>

            <div class="tamoza-table-container">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?= Helpers::e(I18n::t('leaderboard_rank')) ?></th>
                            <th><?= Helpers::e(I18n::t('leaderboard_link')) ?></th>
                            <th class="text-center"><?= Helpers::e(I18n::t('leaderboard_clicks')) ?></th>
                            <th class="text-center"><?= Helpers::e(I18n::t('leaderboard_unique')) ?></th>
                            <th style="min-width: 160px;"><?= Helpers::e(I18n::t('leaderboard_share')) ?></th>
                            <th class="text-end"><?= Helpers::e(I18n::t('leaderboard_action')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats['top_links'])): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-secondary"><?= Helpers::e(I18n::t('leaderboard_empty')) ?></td>
                            </tr>
                        <?php else: ?>
                            <?php $rank = 1; ?>
                            <?php foreach ($stats['top_links'] as $top): ?>
                                <?php 
                                    $totalPeriod = max(1, $stats['total_clicks']);
                                    $sharePct = round(($top['total_clicks'] / $totalPeriod) * 100, 1);
                                    $uniquePct = $top['total_clicks'] > 0 ? round(($top['unique_clicks'] / $top['total_clicks']) * 100, 1) : 0;
                                    $rankBadgeStyle = match($rank) {
                                        1 => 'background: rgba(245, 158, 11, 0.18); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.35);',
                                        2 => 'background: rgba(148, 163, 184, 0.18); color: #94A3B8; border: 1px solid rgba(148, 163, 184, 0.35);',
                                        3 => 'background: rgba(217, 119, 6, 0.18); color: #D97706; border: 1px solid rgba(217, 119, 6, 0.35);',
                                        default => 'background: rgba(99, 102, 241, 0.08); color: #6366F1;'
                                    };
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge rounded-circle p-2 fw-bold" style="<?= $rankBadgeStyle ?> width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                                            <?= $rank++ ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold mb-0 text-truncate" style="max-width: 280px;">
                                            <?= Helpers::e($top['title'] ?: $top['slug']) ?>
                                        </div>
                                        <a href="<?= Helpers::trackingUrl($top['slug']) ?>" target="_blank" class="small text-secondary text-decoration-none d-inline-flex align-items-center gap-1">
                                            <span>/<?= Helpers::e($top['slug']) ?></span>
                                            <?= Icon::get('external-link', '', 12) ?>
                                        </a>
                                    </td>
                                    <td class="text-center fw-bold">
                                        <?= number_format($top['total_clicks']) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold"><?= number_format($top['unique_clicks']) ?></span>
                                        <span class="small text-secondary ms-1">(<?= $uniquePct ?>%)</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px; background: rgba(140, 150, 170, 0.15); border-radius: 4px;">
                                                <div class="progress-bar" role="progressbar" style="width: <?= min(100, $sharePct) ?>%; background: #6366F1; border-radius: 4px;"></div>
                                            </div>
                                            <span class="small fw-semibold text-secondary" style="min-width: 42px;"><?= $sharePct ?>%</span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= Helpers::baseUrl('index.php?page=stats&id=' . (int)$top['id']) ?>" class="btn btn-sm btn-tamoza-secondary d-inline-flex align-items-center gap-1" title="<?= Helpers::e(I18n::t('stats_tooltip')) ?>">
                                            <?= Icon::get('bar-chart', '', 14) ?>
                                            <span><?= Helpers::e(I18n::t('leaderboard_action')) ?></span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Row 5: Platforms & Most Used Browsers Breakdown -->
<div class="row g-4 mb-4">
    <!-- Operating Systems -->
    <div class="col-12 col-lg-6">
        <div class="tamoza-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
                    <?= Icon::get('monitor', 'text-indigo') ?>
                    <span><?= Helpers::e(I18n::t('platforms_title')) ?></span>
                </h4>
                <span class="tamoza-badge badge-indigo">OS</span>
            </div>
            <div class="tamoza-table-container" style="min-height: auto;">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?= Helpers::e(I18n::t('platforms_title')) ?></th>
                            <th class="text-end"><?= Helpers::e(I18n::t('click_count')) ?></th>
                            <th class="text-end"><?= Helpers::e(I18n::t('percentage')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats['platforms'])): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-secondary"><?= Helpers::e(I18n::t('no_clicks_period')) ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stats['platforms'] as $p): ?>
                                <?php 
                                    $pct = $stats['total_clicks'] > 0 ? round(($p['count'] / $stats['total_clicks']) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="tamoza-badge badge-indigo me-1"><?= Helpers::e($p['platform']) ?></span>
                                    </td>
                                    <td class="text-end fw-bold"><?= number_format($p['count']) ?></td>
                                    <td class="text-end text-secondary"><?= $pct ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Most Used Browsers -->
    <div class="col-12 col-lg-6">
        <div class="tamoza-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0 d-flex align-items-center gap-2">
                    <?= Icon::get('compass', 'text-cyan') ?>
                    <span><?= Helpers::e(I18n::t('browsers_title')) ?></span>
                </h4>
                <span class="tamoza-badge badge-cyan">Browser</span>
            </div>
            <div class="tamoza-table-container" style="min-height: auto;">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?= Helpers::e(I18n::t('browsers_title')) ?></th>
                            <th class="text-end"><?= Helpers::e(I18n::t('click_count')) ?></th>
                            <th class="text-end"><?= Helpers::e(I18n::t('percentage')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats['browsers'])): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-secondary"><?= Helpers::e(I18n::t('no_clicks_period')) ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stats['browsers'] as $b): ?>
                                <?php 
                                    $pct = $stats['total_clicks'] > 0 ? round(($b['count'] / $stats['total_clicks']) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="tamoza-badge badge-cyan me-1"><?= Helpers::e($b['browser']) ?></span>
                                    </td>
                                    <td class="text-end fw-bold"><?= number_format($b['count']) ?></td>
                                    <td class="text-end text-secondary"><?= $pct ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Row 6: Live Recent Activity Feed -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="tamoza-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                        <?= Icon::get('activity', 'text-success') ?>
                        <span><?= Helpers::e(I18n::t('recent_activity_title')) ?></span>
                    </h4>
                    <p class="text-secondary small mb-0"><?= Helpers::e(I18n::t('recent_activity_desc')) ?></p>
                </div>
                <span class="tamoza-badge badge-success d-inline-flex align-items-center gap-1">
                    <span class="pulse-dot"></span> <?= Helpers::e(I18n::t('recent_badge_live')) ?>
                </span>
            </div>

            <div class="tamoza-table-container">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th><?= Helpers::e(I18n::t('feed_time')) ?></th>
                            <th><?= Helpers::e(I18n::t('feed_link')) ?></th>
                            <th><?= Helpers::e(I18n::t('feed_country')) ?></th>
                            <th><?= Helpers::e(I18n::t('feed_device')) ?></th>
                            <th><?= Helpers::e(I18n::t('feed_browser')) ?></th>
                            <th><?= Helpers::e(I18n::t('feed_source')) ?></th>
                            <th class="text-end"><?= Helpers::e(I18n::t('feed_type')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats['recent_activity'])): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary"><?= Helpers::e(I18n::t('feed_empty')) ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stats['recent_activity'] as $act): ?>
                                <tr>
                                    <td class="text-nowrap small text-secondary" title="<?= Helpers::e($act['clicked_at']) ?>">
                                        <?= Helpers::e(Helpers::timeAgo($act['clicked_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 200px;">
                                            <?= Helpers::e($act['title'] ?: $act['slug']) ?>
                                        </div>
                                        <span class="text-secondary small">/<?= Helpers::e($act['slug']) ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="tamoza-badge badge-indigo"><?= Helpers::e($act['country_code']) ?></span>
                                            <span class="small fw-medium text-truncate" style="max-width: 140px;" title="<?= Helpers::e($act['country_name'] ?? Helpers::getCountryName($act['country_code'])) ?>"><?= Helpers::e($act['country_name'] ?? Helpers::getCountryName($act['country_code'])) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="small text-secondary"><?= Helpers::e($act['device_type']) ?></span>
                                        <span class="tamoza-badge badge-muted ms-1"><?= Helpers::e($act['platform']) ?></span>
                                    </td>
                                    <td>
                                        <span class="tamoza-badge badge-cyan"><?= Helpers::e($act['browser']) ?></span>
                                    </td>
                                    <td class="small text-secondary text-truncate" style="max-width: 150px;">
                                        <?= Helpers::e($act['referrer_domain'] ?: I18n::t('direct_referrer')) ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if (!empty($act['is_unique'])): ?>
                                            <span class="tamoza-badge badge-success"><?= Helpers::e(I18n::t('feed_unique')) ?></span>
                                        <?php else: ?>
                                            <span class="tamoza-badge badge-muted"><?= Helpers::e(I18n::t('feed_repeat')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js (Indigo & Cyan Brand DNA Theme) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isEn = <?= json_encode(I18n::getLang() === 'en') ?>;
    const fontFamily = isEn ? '-apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", sans-serif' : 'Cairo';

    // 1. Timeline Chart
    const timelineData = <?= json_encode($stats['timeline']) ?>;
    const ctxTimeline = document.getElementById('timelineChart').getContext('2d');
    
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
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointHoverRadius: 6
                },
                {
                    label: <?= json_encode(I18n::t('kpi_unique_clicks')) ?>,
                    data: timelineData.uniques,
                    borderColor: '#22D3EE',
                    backgroundColor: 'rgba(34, 211, 238, 0.1)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointHoverRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { 
                    position: 'top',
                    labels: { font: { family: fontFamily } }
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(140, 150, 170, 0.08)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Referrers Chart
    const refData = <?= json_encode($stats['referrers']) ?>;
    const ctxRef = document.getElementById('referrerChart').getContext('2d');
    const directLabel = <?= json_encode(I18n::t('direct_referrer')) ?>;
    const noDataLabel = <?= json_encode(I18n::t('no_data_label')) ?>;
    const refLabels = refData.map(r => r.domain === 'Direct' ? directLabel : r.domain);
    const refCounts = refData.map(r => r.count);

    new Chart(ctxRef, {
        type: 'doughnut',
        data: {
            labels: refLabels.length ? refLabels : [noDataLabel],
            datasets: [{
                data: refCounts.length ? refCounts : [1],
                backgroundColor: ['#6366F1', '#22D3EE', '#10B981', '#F59E0B', '#EC4899', '#64748B']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: { font: { family: fontFamily } }
                }
            }
        }
    });

    // 3. Devices Chart
    const devData = <?= json_encode($stats['devices']) ?>;
    const ctxDev = document.getElementById('deviceChart').getContext('2d');
    const devLabelsMap = {
        desktop: <?= json_encode(I18n::t('device_desktop')) ?>,
        mobile:  <?= json_encode(I18n::t('device_mobile')) ?>,
        tablet:  <?= json_encode(I18n::t('device_tablet')) ?>,
        bot:     <?= json_encode(I18n::t('device_bot')) ?>,
        other:   <?= json_encode(I18n::t('device_other')) ?>
    };
    const devLabels = devData.map(d => devLabelsMap[d.device_type] || devLabelsMap.other);
    const devCounts = devData.map(d => d.count);

    new Chart(ctxDev, {
        type: 'bar',
        data: {
            labels: devLabels.length ? devLabels : [noDataLabel],
            datasets: [{
                label: <?= json_encode(I18n::t('clicks_label')) ?>,
                data: devCounts.length ? devCounts : [0],
                backgroundColor: '#6366F1',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(140, 150, 170, 0.08)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // 4. Hourly Activity Curve Chart
    const hourlyData = <?= json_encode($stats['hourly']) ?>;
    const ctxHourly = document.getElementById('hourlyChart').getContext('2d');
    new Chart(ctxHourly, {
        type: 'line',
        data: {
            labels: hourlyData.labels,
            datasets: [{
                label: <?= json_encode(I18n::t('clicks_label')) ?>,
                data: hourlyData.counts,
                borderColor: '#F59E0B',
                backgroundColor: 'rgba(245, 158, 11, 0.15)',
                fill: true,
                tension: 0.35,
                borderWidth: 2.5,
                pointRadius: 2,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(140, 150, 170, 0.08)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // 5. Smart Channels Chart
    const channelData = <?= json_encode($stats['channels']) ?>;
    const ctxChannel = document.getElementById('channelChart').getContext('2d');
    const channelTotal = <?= (int)($stats['channels']['total'] ?? 0) ?>;

    new Chart(ctxChannel, {
        type: 'doughnut',
        data: {
            labels: [
                <?= json_encode(I18n::t('channel_social')) ?>,
                <?= json_encode(I18n::t('channel_search')) ?>,
                <?= json_encode(I18n::t('channel_direct')) ?>,
                <?= json_encode(I18n::t('channel_referral')) ?>
            ],
            datasets: [{
                data: channelTotal > 0 ? [
                    channelData.social.count,
                    channelData.search.count,
                    channelData.direct.count,
                    channelData.referral.count
                ] : [1],
                backgroundColor: channelTotal > 0 ? ['#6366F1', '#10B981', '#22D3EE', '#F59E0B'] : ['rgba(140, 150, 170, 0.2)']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false }
            }
        }
    });

    // Overview Report Card Generator Event
    const overviewReportData = {
        period: <?= json_encode($stats['period'] ?? '30d') ?>,
        total_links: <?= json_encode($stats['total_links'] ?? 0) ?>,
        active_links: <?= json_encode($stats['active_links'] ?? 0) ?>,
        total_clicks: <?= json_encode($stats['total_clicks'] ?? 0) ?>,
        all_time_clicks: <?= json_encode($stats['all_time_clicks'] ?? 0) ?>,
        unique_clicks: <?= json_encode($stats['unique_clicks'] ?? 0) ?>,
        avg_unique_rate: <?= json_encode($stats['avg_unique_rate'] ?? 0) ?>,
        peak_hour: <?= json_encode($stats['peak_hour'] ?? '') ?>,
        top_links: <?= json_encode($stats['top_links'] ?? []) ?>,
        countries: <?= json_encode($stats['countries'] ?? []) ?>,
        devices: <?= json_encode($stats['devices'] ?? []) ?>,
        channels: <?= json_encode($stats['channels'] ?? null) ?>,
        browsers: <?= json_encode($stats['browsers'] ?? []) ?>,
        platforms: <?= json_encode($stats['platforms'] ?? []) ?>,
        referrers: <?= json_encode($stats['referrers'] ?? []) ?>
    };

    document.getElementById('exportOverviewImageBtn')?.addEventListener('click', function () {
        if (window.AtharReportCard) {
            window.AtharReportCard.openOverview(overviewReportData);
        }
    });
});
</script>
