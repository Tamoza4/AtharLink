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
?>
<!DOCTYPE html>
<html lang="<?= Helpers::e($lang) ?>" dir="<?= Helpers::e($dir) ?>" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e(I18n::t('login_title')) ?> | <?= APP_NAME ?></title>
    
    <!-- Cairo Typography (Arabic) -->
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
            copied: <?= json_encode(I18n::t('copied_success')) ?>,
            copyManually: <?= json_encode(I18n::t('copy_manually')) ?>,
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
        .tamoza-auth-box {
            width: 100%;
            max-width: 440px;
            background: var(--tamoza-surface-card);
            backdrop-filter: blur(16px) saturate(140%);
            -webkit-backdrop-filter: blur(16px) saturate(140%);
            border: 1px solid var(--tamoza-border);
            border-radius: var(--tamoza-radius-lg); /* 24px */
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            padding: var(--tamoza-spacing-48) var(--tamoza-spacing-32);
            text-align: center;
        }
    </style>
</head>
<body class="<?= $lang === 'en' ? 'lang-en' : 'lang-ar' ?>">

<div class="tamoza-auth-box">
    <!-- On-Card Language Toggle Header -->
    <div class="d-flex justify-content-end mb-3">
        <a href="<?= Helpers::baseUrl('index.php?action=toggle_lang') ?>" class="btn btn-tamoza-subtle btn-sm px-3 py-1 rounded-3 d-inline-flex align-items-center gap-1" title="<?= Helpers::e(I18n::t('switch_lang_title')) ?>">
            <?= Icon::get('globe', '', 14) ?>
            <span class="fw-semibold small"><?= Helpers::e(I18n::t('lang_switch_label')) ?></span>
        </a>
    </div>

    <!-- Brand Icon & Title -->
    <div class="mb-4">
        <div class="display-5 mb-2 text-indigo"><?= Icon::get('link-2', '', 48) ?></div>
        <h2 class="fw-bold mb-1"><?= APP_NAME ?></h2>
        <p class="text-secondary small mb-0"><?= Helpers::e(I18n::t('app_subtitle')) ?></p>
    </div>

    <!-- Feedback Alerts -->
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger py-2 small mb-4 rounded-3 text-start d-flex align-items-center gap-2" role="alert">
            <?= Icon::get('alert-triangle', 'text-danger', 16) ?>
            <div><?= Helpers::e($errorMessage) ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success py-2 small mb-4 rounded-3 text-start d-flex align-items-center gap-2" role="alert">
            <?= Icon::get('check-circle', 'text-success', 16) ?>
            <div><?= Helpers::e($_SESSION['flash_success']) ?></div>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" action="<?= Helpers::baseUrl('index.php?action=login') ?>" class="text-start">
        <?= Helpers::csrfInput() ?>

        <div class="mb-3">
            <label class="form-label small fw-semibold"><?= Helpers::e(I18n::t('username_label')) ?></label>
            <input type="text" name="username" class="form-control tamoza-input" required autofocus autocomplete="username">
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label small fw-semibold mb-0"><?= Helpers::e(I18n::t('password_input_title')) ?></label>
                <a href="#" class="text-decoration-none small text-secondary" data-bs-toggle="modal" data-bs-target="#recoveryModal">
                    <?= Helpers::e(I18n::t('forgot_password')) ?>
                </a>
            </div>
            <input type="password" name="password" class="form-control tamoza-input" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-tamoza-primary w-100 py-3 mb-3 fw-bold fs-6">
            <?= Helpers::e(I18n::t('sign_in_btn')) ?>
        </button>
    </form>

    <!-- Bottom Controls -->
    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-secondary-subtle">
        <button type="button" class="btn btn-tamoza-subtle d-inline-flex align-items-center gap-2" id="themeToggleBtn" title="<?= Helpers::e(I18n::t('theme_toggle')) ?>">
            <span class="theme-toggle-icon d-inline-flex align-items-center"></span>
            <span class="small theme-toggle-text"><?= Helpers::e(I18n::t('theme_toggle')) ?></span>
        </button>
        <span class="text-secondary small"><?= Helpers::e(I18n::t('version_label')) ?> <?= APP_VERSION ?></span>
    </div>
</div>

<!-- Emergency Password Recovery Modal -->
<div class="modal fade" id="recoveryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="background: #161A23 !important; border: 1px solid var(--tamoza-border); border-radius: var(--tamoza-radius-lg);">
            <form method="POST" action="<?= Helpers::baseUrl('index.php?action=recover_password') ?>" class="text-start">
                <?= Helpers::csrfInput() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                        <?= Icon::get('key', 'text-warning') ?>
                        <span><?= Helpers::e(I18n::t('emergency_recovery_title')) ?></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="text-secondary small mb-3"><?= Helpers::e(I18n::t('recovery_key_hint')) ?></p>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><?= Helpers::e(I18n::t('recovery_key_label')) ?></label>
                        <input type="text" name="recovery_key" class="form-control tamoza-input font-monospace text-uppercase" placeholder="ATHAR-XXXX-XXXX-XXXX" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><?= Helpers::e(I18n::t('new_password_label')) ?></label>
                        <input type="password" name="new_password" class="form-control tamoza-input" minlength="8" required autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-tamoza-secondary" data-bs-dismiss="modal"><?= Helpers::e(I18n::t('cancel_btn')) ?></button>
                    <button type="submit" class="btn btn-tamoza-primary px-4 fw-bold"><?= Helpers::e(I18n::t('reset_my_password_btn')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= Helpers::baseUrl('assets/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= Helpers::baseUrl('assets/js/app.js') ?>"></script>
</body>
</html>
