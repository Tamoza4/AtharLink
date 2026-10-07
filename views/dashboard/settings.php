<?php
declare(strict_types=1);

/**
 * Tamoza UI Standard - Verified: 8pt Logic, Premium Glassmorphism, Centered Layout, Indigo Brand DNA, Cairo Typography.
 */

use AtharLink\Helpers;
use AtharLink\Database;
use AtharLink\Auth;
use AtharLink\I18n;
use AtharLink\Icon;
use AtharLink\Updater;

$user = Auth::user();
$siteTitle = Database::getSetting('site_title', 'AtharLink');
$uniquenessWindow = (int)Database::getSetting('uniqueness_window', (string)DEFAULT_UNIQUENESS_WINDOW);
$hoursWindow = round($uniquenessWindow / 3600, 1);
$updateInfo = Updater::check();
?>

<div class="mb-4">
    <h2 class="fw-bold mb-1"><?= I18n::t('settings_title') ?></h2>
    <p class="text-secondary small mb-0"><?= I18n::t('settings_desc') ?></p>
</div>

<div class="row g-4">
    <!-- Left Column: API & Integration -->
    <div class="col-12 col-lg-6">
        <!-- API Token Card -->
        <div class="tamoza-card mb-4">
            <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <?= Icon::get('key', 'text-indigo') ?>
                <span><?= I18n::t('api_card_title') ?></span>
            </h4>
            <p class="text-secondary small mb-3"><?= I18n::t('api_card_desc') ?></p>
            
            <div class="input-group mb-3">
                <input type="password" id="apiTokenField" class="form-control tamoza-input font-monospace" value="<?= Helpers::e($user['api_token'] ?? '') ?>" readonly>
                <button type="button" class="btn btn-tamoza-secondary d-flex align-items-center justify-content-center" id="toggleApiTokenBtn" title="<?= Helpers::e(I18n::t('toggle_password_tooltip')) ?>">
                    <?= Icon::get('eye', '', 16) ?>
                </button>
                <button type="button" class="btn btn-tamoza-secondary d-inline-flex align-items-center gap-1" data-copy-url="<?= Helpers::e($user['api_token'] ?? '') ?>" title="<?= I18n::t('copy_token_title') ?>">
                    <?= Icon::get('copy', '', 14) ?>
                    <span><?= I18n::t('copy_btn') ?></span>
                </button>
            </div>

            <form method="POST" action="<?= Helpers::baseUrl('index.php?action=regen_token') ?>" onsubmit="return confirm('<?= Helpers::e(I18n::t('regen_token_confirm')) ?>');">
                <?= Helpers::csrfInput() ?>
                <button type="submit" class="btn btn-tamoza-subtle d-inline-flex align-items-center gap-2">
                    <?= Icon::get('refresh', '', 14) ?>
                    <span><?= I18n::t('regen_token_btn') ?></span>
                </button>
            </form>
        </div>

        <!-- Emergency Recovery Key Card -->
        <div class="tamoza-card mb-4">
            <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <?= Icon::get('shield', 'text-warning') ?>
                <span><?= I18n::t('emergency_card_title') ?></span>
            </h4>
            <p class="text-secondary small mb-3"><?= I18n::t('emergency_card_desc') ?></p>

            <div class="input-group mb-2">
                <input type="password" id="recoveryKeyField" class="form-control tamoza-input font-monospace" value="<?= Helpers::e(Auth::getRecoveryKey()) ?>" readonly>
                <button type="button" class="btn btn-tamoza-secondary d-flex align-items-center justify-content-center" id="toggleRecoveryKeyBtn" title="<?= Helpers::e(I18n::t('toggle_password_tooltip')) ?>">
                    <?= Icon::get('eye', '', 16) ?>
                </button>
                <button type="button" class="btn btn-tamoza-secondary d-inline-flex align-items-center gap-1" data-copy-url="<?= Helpers::e(Auth::getRecoveryKey()) ?>" title="<?= I18n::t('copy_btn') ?>">
                    <?= Icon::get('copy', '', 14) ?>
                    <span><?= I18n::t('copy_btn') ?></span>
                </button>
            </div>
        </div>

        <!-- Public Counter Embed Guide -->
        <div class="tamoza-card">
            <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <?= Icon::get('link-2', 'text-indigo') ?>
                <span><?= I18n::t('embed_card_title') ?></span>
            </h4>
            <p class="text-secondary small mb-3"><?= I18n::t('embed_card_desc') ?></p>

            <div class="p-3 rounded-4 mb-3 small font-monospace" style="background: rgba(0, 0, 0, 0.2); border: 1px solid var(--tamoza-border);" dir="ltr">
                &lt;span data-athar-count="<span class="text-primary">YOUR_SLUG</span>" data-type="unique"&gt;&lt;/span&gt;<br>
                &lt;script src="<?= Helpers::baseUrl('embed.js') ?>" async&gt;&lt;/script&gt;
            </div>

            <span class="tamoza-badge badge-indigo small">
                <?= I18n::t('embed_hint') ?>
            </span>
        </div>
    </div>

    <!-- Right Column: System Settings & Backup -->
    <div class="col-12 col-lg-6">
        <!-- General System Settings Form -->
        <div class="tamoza-card mb-4">
            <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <?= Icon::get('settings', 'text-indigo') ?>
                <span><?= I18n::t('engine_settings_title') ?></span>
            </h4>
            
            <form method="POST" action="<?= Helpers::baseUrl('index.php?action=save_settings') ?>">
                <?= Helpers::csrfInput() ?>

                <div class="mb-3">
                    <label class="form-label small fw-semibold"><?= I18n::t('site_title_label') ?></label>
                    <input type="text" name="site_title" class="form-control tamoza-input" value="<?= Helpers::e($siteTitle) ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold"><?= I18n::t('uniqueness_window_label') ?></label>
                    <input type="number" step="0.5" min="1" max="168" name="uniqueness_hours" class="form-control tamoza-input" value="<?= $hoursWindow ?>" required>
                    <small class="text-secondary d-block mt-1"><?= I18n::t('uniqueness_window_hint') ?></small>
                </div>

                <button type="submit" class="btn btn-tamoza-primary px-4"><?= I18n::t('save_settings_btn') ?></button>
            </form>
        </div>

        <!-- Secure Backup & Restore -->
        <div class="tamoza-card">
            <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <?= Icon::get('database', 'text-indigo') ?>
                <span><?= I18n::t('backup_card_title') ?></span>
            </h4>
            <p class="text-secondary small mb-3"><?= I18n::t('backup_card_desc') ?></p>

            <!-- Download Backup Button -->
            <div class="mb-4">
                <a href="<?= Helpers::baseUrl('index.php?action=download_backup') ?>" class="btn btn-tamoza-secondary d-inline-flex align-items-center gap-2">
                    <?= Icon::get('download', '', 16) ?>
                    <span><?= I18n::t('download_backup_btn') ?></span>
                </a>
            </div>

            <!-- Restore Backup Form -->
            <form method="POST" action="<?= Helpers::baseUrl('index.php?action=restore_backup') ?>" enctype="multipart/form-data" onsubmit="return confirm('<?= Helpers::e(I18n::t('restore_warning')) ?>');">
                <?= Helpers::csrfInput() ?>
                
                <label class="form-label small fw-semibold"><?= I18n::t('restore_backup_label') ?></label>
                <div class="input-group mb-2">
                    <input type="file" name="backup_file" class="form-control tamoza-input" accept=".sqlite,.db" required>
                    <button type="submit" class="btn btn-danger rounded-3 fw-bold d-inline-flex align-items-center gap-2">
                        <?= Icon::get('upload', '', 16) ?>
                        <span><?= I18n::t('restore_btn') ?></span>
                    </button>
                </div>
                <small class="text-secondary"><?= I18n::t('restore_hint') ?></small>
            </form>
        </div>

        <!-- System Updates & Version Card -->
        <div class="tamoza-card mt-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                        <?= Icon::get('cloud-download', 'text-indigo') ?>
                        <span><?= I18n::t('updates_card_title') ?></span>
                    </h4>
                    <p class="text-secondary small mb-0"><?= I18n::t('updates_card_desc') ?></p>
                </div>
                <?php if (!empty($updateInfo['has_update'])): ?>
                    <span class="tamoza-badge badge-warning d-inline-flex align-items-center gap-1">
                        <span class="pulse-dot"></span>
                        <span><?= I18n::t('update_available_badge', ['version' => 'v' . $updateInfo['latest']]) ?></span>
                    </span>
                <?php else: ?>
                    <span class="tamoza-badge badge-success d-inline-flex align-items-center gap-1">
                        <?= Icon::get('check-circle', '', 14) ?>
                        <span><?= I18n::t('system_up_to_date') ?></span>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Version Comparison Box -->
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <div class="p-3 rounded-4" style="background: rgba(99, 102, 241, 0.06); border: 1px solid var(--tamoza-border);">
                        <span class="text-secondary small d-block mb-1"><?= I18n::t('current_version_label') ?></span>
                        <span class="fs-5 fw-bold font-monospace text-primary">v<?= Helpers::e($updateInfo['current']) ?></span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 rounded-4" style="background: rgba(16, 185, 129, 0.06); border: 1px solid var(--tamoza-border);">
                        <span class="text-secondary small d-block mb-1"><?= I18n::t('latest_version_label') ?></span>
                        <span class="fs-5 fw-bold font-monospace <?= !empty($updateInfo['has_update']) ? 'text-warning' : 'text-success' ?>">
                            v<?= Helpers::e($updateInfo['latest']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if (!empty($updateInfo['has_update']) && !empty($updateInfo['release_notes'])): ?>
                <div class="p-3 rounded-4 mb-3 small" style="background: rgba(0, 0, 0, 0.15); border: 1px solid var(--tamoza-border);">
                    <div class="fw-semibold text-warning mb-1 d-flex align-items-center gap-1">
                        <?= Icon::get('zap', '', 14) ?>
                        <span><?= I18n::t('release_notes_label') ?> <?= Helpers::e($updateInfo['release_name']) ?></span>
                    </div>
                    <div class="text-secondary" style="max-height: 120px; overflow-y: auto; white-space: pre-wrap; font-size: 13px;"><?= Helpers::e($updateInfo['release_notes']) ?></div>
                </div>
            <?php endif; ?>

            <!-- Action Controls -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top" style="border-color: var(--tamoza-border) !important;">
                <div class="d-flex align-items-center gap-2">
                    <!-- Check for Updates Now Button -->
                    <a href="<?= Helpers::baseUrl('index.php?action=check_updates') ?>" class="btn btn-tamoza-secondary d-inline-flex align-items-center gap-2">
                        <?= Icon::get('refresh', '', 14) ?>
                        <span><?= I18n::t('check_updates_btn') ?></span>
                    </a>

                    <!-- Apply Update Now Button -->
                    <form method="POST" action="<?= Helpers::baseUrl('index.php?action=apply_update') ?>" onsubmit="return confirm('<?= Helpers::e(I18n::t('update_confirm_prompt')) ?>');" class="m-0">
                        <?= Helpers::csrfInput() ?>
                        <button type="submit" class="btn <?= !empty($updateInfo['has_update']) ? 'btn-tamoza-primary' : 'btn-tamoza-subtle' ?> d-inline-flex align-items-center gap-2">
                            <?= Icon::get('rocket', '', 15) ?>
                            <span><?= I18n::t('update_now_btn') ?></span>
                        </button>
                    </form>
                </div>

                <span class="text-secondary small">
                    <?= I18n::t('update_checking_hint', ['date' => !empty($updateInfo['cached_at']) ? date('Y-m-d H:i', (int)$updateInfo['cached_at']) : date('Y-m-d H:i')]) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const eyeIconSvg = <?= json_encode(Icon::get('eye', '', 16)) ?>;
    const eyeOffIconSvg = <?= json_encode(Icon::get('eye-off', '', 16)) ?>;

    const bindSecretToggle = (btnId, inputId) => {
        const btn = document.getElementById(btnId);
        const inp = document.getElementById(inputId);
        if (!btn || !inp) return;
        btn.addEventListener('click', function () {
            if (inp.type === 'password') {
                inp.type = 'text';
                btn.innerHTML = eyeOffIconSvg;
            } else {
                inp.type = 'password';
                btn.innerHTML = eyeIconSvg;
            }
        });
    };
    bindSecretToggle('toggleApiTokenBtn', 'apiTokenField');
    bindSecretToggle('toggleRecoveryKeyBtn', 'recoveryKeyField');
});
</script>
