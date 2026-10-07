<?php
declare(strict_types=1);

/**
 * Tamoza UI Standard - Verified: 8pt Logic, Premium Glassmorphism, Centered Layout, Indigo Brand DNA, Dual Language.
 */

use AtharLink\Helpers;
use AtharLink\I18n;
use AtharLink\Icon;

$lang = I18n::getLang();
$dir = I18n::getDir();

// Perform Environmental Checks
$phpVersionOk = version_compare(PHP_VERSION, '8.2.0', '>=');
$sqliteOk = extension_loaded('pdo_sqlite');
$storageWritable = is_dir(STORAGE_DIR) ? is_writable(STORAGE_DIR) : @mkdir(STORAGE_DIR, 0755, true);

$allChecksPassed = $phpVersionOk && $sqliteOk && $storageWritable;
?>
<!DOCTYPE html>
<html lang="<?= Helpers::e($lang) ?>" dir="<?= Helpers::e($dir) ?>" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e(I18n::t('install_title')) ?> | <?= APP_NAME ?></title>
    
    <!-- Cairo Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= Helpers::baseUrl('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= Helpers::baseUrl('assets/css/app.css') ?>">

    <!-- Global Client Localization Context -->
    <script>
        window.__i18n = {
            lang: <?= json_encode($lang) ?>,
            dir: <?= json_encode($dir) ?>,
            themeLight: <?= json_encode(I18n::t('theme_toggle_light')) ?>,
            themeDark: <?= json_encode(I18n::t('theme_toggle_dark')) ?>
        };
    </script>

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--tamoza-spacing-24);
            margin: 0;
        }
        .tamoza-install-box {
            width: 100%;
            max-width: 520px;
            background: var(--tamoza-surface-card);
            backdrop-filter: blur(16px) saturate(140%);
            -webkit-backdrop-filter: blur(16px) saturate(140%);
            border: 1px solid var(--tamoza-border);
            border-radius: var(--tamoza-radius-lg); /* 24px */
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            padding: var(--tamoza-spacing-48) var(--tamoza-spacing-32);
        }
    </style>
</head>
<body class="<?= $lang === 'en' ? 'lang-en' : 'lang-ar' ?>">

<div class="tamoza-install-box">
    <!-- Language Toggle -->
    <div class="d-flex justify-content-end mb-3">
        <a href="<?= Helpers::baseUrl('index.php?action=toggle_lang') ?>" class="btn btn-tamoza-subtle btn-sm px-3 py-1 rounded-3 d-inline-flex align-items-center gap-1" title="<?= Helpers::e(I18n::t('switch_lang_title')) ?>">
            <?= Icon::get('globe', '', 14) ?>
            <span class="fw-semibold small"><?= Helpers::e(I18n::t('lang_switch_label')) ?></span>
        </a>
    </div>

    <div class="text-center mb-4">
        <div class="display-5 mb-2 text-indigo"><?= Icon::get('rocket', '', 48) ?></div>
        <h2 class="fw-bold mb-1"><?= Helpers::e(I18n::t('install_title')) ?></h2>
        <p class="text-secondary small mb-0"><?= Helpers::e(I18n::t('install_subtitle')) ?></p>
    </div>

    <!-- Requirements Check Box -->
    <div class="p-3 mb-4 rounded-4" style="background: rgba(99, 102, 241, 0.05); border: 1px solid var(--tamoza-border);">
        <h6 class="fw-bold mb-3 small text-secondary"><?= Helpers::e(I18n::t('install_req_title')) ?></h6>
        
        <div class="d-flex justify-content-between align-items-center mb-2 small">
            <span><?= Helpers::e(I18n::t('install_php_req', ['version' => PHP_VERSION])) ?></span>
            <?= $phpVersionOk ? '<span class="badge badge-success">' . Helpers::e(I18n::t('badge_compatible')) . '</span>' : '<span class="badge badge-danger">' . Helpers::e(I18n::t('badge_incompatible')) . '</span>' ?>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2 small">
            <span><?= Helpers::e(I18n::t('install_sqlite_req')) ?></span>
            <?= $sqliteOk ? '<span class="badge badge-success">' . Helpers::e(I18n::t('badge_enabled')) . '</span>' : '<span class="badge badge-danger">' . Helpers::e(I18n::t('badge_disabled')) . '</span>' ?>
        </div>

        <div class="d-flex justify-content-between align-items-center small">
            <span><?= Helpers::e(I18n::t('install_storage_req')) ?></span>
            <?= $storageWritable ? '<span class="badge badge-success">' . Helpers::e(I18n::t('badge_writable')) . '</span>' : '<span class="badge badge-danger">' . Helpers::e(I18n::t('badge_not_writable')) . '</span>' ?>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger py-2 small mb-4 rounded-3 text-start d-flex align-items-center gap-2">
            <?= Icon::get('alert-triangle', 'text-danger', 16) ?>
            <div><?= Helpers::e($errorMessage) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($allChecksPassed): ?>
        <form method="POST" action="<?= Helpers::baseUrl('index.php?action=do_install') ?>" class="text-start">
            <h5 class="fw-bold mb-3 fs-6"><?= Helpers::e(I18n::t('install_admin_title')) ?></h5>

            <div class="mb-3">
                <label class="form-label small fw-semibold"><?= Helpers::e(I18n::t('install_admin_user')) ?></label>
                <input type="text" name="admin_username" class="form-control tamoza-input" required autocomplete="username">
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold"><?= Helpers::e(I18n::t('install_admin_pass')) ?></label>
                <input type="password" name="admin_password" class="form-control tamoza-input" required autocomplete="new-password">
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold"><?= Helpers::e(I18n::t('install_admin_pass_confirm')) ?></label>
                <input type="password" name="admin_password_confirm" class="form-control tamoza-input" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-tamoza-primary w-100 py-3 fw-bold fs-6 d-inline-flex align-items-center justify-content-center gap-2">
                <?= Icon::get('rocket', '', 18) ?>
                <span><?= Helpers::e(I18n::t('install_start_btn')) ?></span>
            </button>
        </form>
    <?php else: ?>
        <div class="alert alert-warning mb-0 small rounded-3 text-start">
            <?= Helpers::e(I18n::t('install_req_warning')) ?>
        </div>
    <?php endif; ?>
</div>

<script src="<?= Helpers::baseUrl('assets/js/app.js') ?>"></script>
</body>
</html>
