<?php
declare(strict_types=1);

/**
 * Tamoza UI Standard - Verified: 8pt Logic, Premium Glassmorphism, Centered Layout, Indigo Brand DNA, SF Pro & Cairo Typography, Theme Toggle Active, Full Component Set.
 */

use AtharLink\Helpers;
use AtharLink\Auth;
use AtharLink\I18n;
use AtharLink\Icon;

$user = Auth::user();
$activePage = $activePage ?? 'overview';
$lang = I18n::getLang();
$dir = I18n::getDir();

// Content Security Policy
if (!headers_sent()) {
    header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.googleapis.com https://fonts.gstatic.com data:;");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
}
?>
<!DOCTYPE html>
<html lang="<?= Helpers::e($lang) ?>" dir="<?= Helpers::e($dir) ?>" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? Helpers::e($pageTitle) . ' | ' : '' ?><?= APP_NAME ?> — <?= Helpers::e(I18n::t('app_subtitle')) ?></title>
    
    <!-- Cairo Typography (For Arabic) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= Helpers::baseUrl('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= Helpers::baseUrl('assets/css/app.css') ?>">

    <!-- Global Client-Side Localization Context -->
    <script>
        window.__i18n = {
            lang: <?= json_encode($lang) ?>,
            dir: <?= json_encode($dir) ?>,
            copied: <?= json_encode(I18n::t('copied_success')) ?>,
            copyManually: <?= json_encode(I18n::t('copy_manually')) ?>,
            slugHint: <?= json_encode(I18n::t('slug_feedback_hint')) ?>,
            slugInvalid: <?= json_encode(I18n::t('slug_feedback_invalid')) ?>,
            slugChecking: <?= json_encode(I18n::t('slug_feedback_checking')) ?>,
            slugAvailable: <?= json_encode(I18n::t('slug_feedback_available')) ?>,
            slugTaken: <?= json_encode(I18n::t('slug_feedback_taken')) ?>,
            qrPrefix: <?= json_encode(I18n::t('qr_modal_prefix')) ?>,
            themeLight: <?= json_encode(I18n::t('theme_toggle_light')) ?>,
            themeDark: <?= json_encode(I18n::t('theme_toggle_dark')) ?>,
            reportSingleTitle: <?= json_encode(I18n::t('report_single_title')) ?>,
            reportOverviewTitle: <?= json_encode(I18n::t('report_overview_title')) ?>,
            reportOverviewSubtitle: <?= json_encode(I18n::t('report_overview_subtitle')) ?>,
            reportTargetLabel: <?= json_encode(I18n::t('report_target_label')) ?>,
            reportTopLinks: <?= json_encode(I18n::t('report_top_links_title')) ?>,
            reportTopCountries: <?= json_encode(I18n::t('report_top_countries_title')) ?>,
            reportTopDevices: <?= json_encode(I18n::t('report_top_devices_title')) ?>,
            reportTopReferrers: <?= json_encode(I18n::t('report_top_referrers_title')) ?>,
            reportBadgeAnalytics: <?= json_encode(I18n::t('report_badge_analytics')) ?>,
            reportBadgeOverview: <?= json_encode(I18n::t('report_badge_overview')) ?>,
            reportWatermark: <?= json_encode(I18n::t('report_watermark')) ?>,
            kpiTotalClicks: <?= json_encode(I18n::t('kpi_total_clicks')) ?>,
            kpiUniqueClicks: <?= json_encode(I18n::t('kpi_unique_clicks')) ?>,
            kpiConversionRate: <?= json_encode(I18n::t('kpi_conversion_rate')) ?>,
            kpiActiveLinks: <?= json_encode(I18n::t('kpi_active_links')) ?>,
            kpiPeakHours: <?= json_encode(I18n::t('kpi_peak_hours')) ?>,
            statusActive: <?= json_encode(I18n::t('status_active')) ?>,
            statusPaused: <?= json_encode(I18n::t('status_paused')) ?>,
            copyImageSuccess: <?= json_encode(I18n::t('copied_image_success')) ?>,
            channelsTitle: <?= json_encode(I18n::t('channels_title')) ?>,
            channelSocial: <?= json_encode(I18n::t('channel_social')) ?>,
            channelSearch: <?= json_encode(I18n::t('channel_search')) ?>,
            channelDirect: <?= json_encode(I18n::t('channel_direct')) ?>,
            channelReferral: <?= json_encode(I18n::t('channel_referral')) ?>,
            browsersTitle: <?= json_encode(I18n::t('browsers_title')) ?>,
            platformsTitle: <?= json_encode(I18n::t('platforms_title')) ?>,
            trafficShare: <?= json_encode(I18n::t('leaderboard_share')) ?>
        };
    </script>
</head>
<body class="<?= $lang === 'en' ? 'lang-en' : 'lang-ar' ?>">

<?php if (Auth::check()): ?>
<!-- Tamoza Floating Header (Safe Float Rule: 24px top & side margins) -->
<header class="tamoza-floating-header">
    <!-- Brand / Logo -->
    <a class="tamoza-brand" href="<?= Helpers::baseUrl() ?>">
        <?= Icon::get('link', 'text-primary', 22) ?>
        <span><?= APP_NAME ?></span>
        <span class="tamoza-brand-badge">v<?= APP_VERSION ?></span>
    </a>

    <!-- Navigation Links (Center) -->
    <nav class="tamoza-header-nav d-none d-md-flex">
        <a class="tamoza-nav-link <?= $activePage === 'overview' ? 'active' : '' ?>" href="<?= Helpers::baseUrl() ?>">
            <?= Icon::get('bar-chart', 'me-1', 16) ?> <?= Helpers::e(I18n::t('nav_overview')) ?>
        </a>
        <a class="tamoza-nav-link <?= $activePage === 'links' ? 'active' : '' ?>" href="<?= Helpers::baseUrl('index.php?page=links') ?>">
            <?= Icon::get('link-2', 'me-1', 16) ?> <?= Helpers::e(I18n::t('nav_links')) ?>
        </a>
        <a class="tamoza-nav-link <?= $activePage === 'settings' ? 'active' : '' ?>" href="<?= Helpers::baseUrl('index.php?page=settings') ?>">
            <?= Icon::get('settings', 'me-1', 16) ?> <?= Helpers::e(I18n::t('nav_settings')) ?>
        </a>
    </nav>

    <!-- Controls (Theme Toggle, Language Switch, User Profile) -->
    <div class="tamoza-header-controls">
        <!-- Language Switcher Button (AR / EN) -->
        <a href="<?= Helpers::baseUrl('index.php?action=toggle_lang') ?>" class="btn btn-tamoza-subtle d-inline-flex align-items-center gap-2" title="<?= Helpers::e(I18n::t('switch_lang_title')) ?>">
            <?= Icon::get('globe', '', 15) ?>
            <span class="fw-semibold small"><?= Helpers::e(I18n::t('lang_switch_label')) ?></span>
        </a>

        <!-- Theme Toggle (Moon / Sun) -->
        <button type="button" class="btn btn-tamoza-subtle tamoza-theme-toggle" id="themeToggleBtn" title="<?= Helpers::e(I18n::t('theme_toggle')) ?>" aria-label="<?= Helpers::e(I18n::t('theme_toggle')) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tamoza-theme-icon"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
        </button>

        <!-- User Dropdown -->
        <div class="dropdown">
            <button class="btn btn-tamoza-subtle dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                <?= Icon::get('user', '', 15) ?>
                <span class="d-none d-sm-inline fw-semibold"><?= Helpers::e($user['username'] ?? I18n::t('nav_profile')) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2">
                <li><span class="dropdown-item-text text-secondary small px-3"><?= Helpers::e(I18n::t('nav_profile')) ?>: <?= Helpers::e($user['username'] ?? '') ?></span></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li class="d-md-none"><a class="dropdown-item" href="<?= Helpers::baseUrl() ?>"><?= Icon::get('bar-chart', 'me-2', 15) ?> <?= Helpers::e(I18n::t('nav_overview')) ?></a></li>
                <li class="d-md-none"><a class="dropdown-item" href="<?= Helpers::baseUrl('index.php?page=links') ?>"><?= Icon::get('link-2', 'me-2', 15) ?> <?= Helpers::e(I18n::t('nav_links')) ?></a></li>
                <li><a class="dropdown-item" href="<?= Helpers::baseUrl('index.php?page=settings') ?>"><?= Icon::get('settings', 'me-2', 15) ?> <?= Helpers::e(I18n::t('nav_settings')) ?></a></li>
                <li>
                    <form method="POST" action="<?= Helpers::baseUrl('index.php?action=logout') ?>" class="m-0 p-0">
                        <?= Helpers::csrfInput() ?>
                        <button type="submit" class="dropdown-item text-danger border-0 bg-transparent w-100 text-start py-2">
                            <?= Icon::get('log-out', 'me-2 text-danger', 15) ?> <?= Helpers::e(I18n::t('nav_logout')) ?>
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
<?php endif; ?>

<!-- Main Centered Content Container (1200px Max-Width) -->
<main class="tamoza-container py-3">
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4 d-flex align-items-center gap-2" role="alert">
            <?= Icon::get('check-circle', 'text-success', 18) ?>
            <div><?= Helpers::e($_SESSION['flash_success']) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4 d-flex align-items-center gap-2" role="alert">
            <?= Icon::get('alert-triangle', 'text-danger', 18) ?>
            <div><?= Helpers::e($_SESSION['flash_error']) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
