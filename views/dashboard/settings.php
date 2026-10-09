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
                <div id="updateStatusBadgeWrapper">
                    <?php if (!empty($updateInfo['has_update'])): ?>
                        <span id="updateStatusBadge" class="tamoza-badge badge-warning d-inline-flex align-items-center gap-1">
                            <span class="pulse-dot"></span>
                            <span><?= I18n::t('update_available_badge', ['version' => 'v' . $updateInfo['latest']]) ?></span>
                        </span>
                    <?php else: ?>
                        <span id="updateStatusBadge" class="tamoza-badge badge-success d-inline-flex align-items-center gap-1">
                            <?= Icon::get('check-circle', '', 14) ?>
                            <span><?= I18n::t('system_up_to_date') ?></span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Version Comparison Box -->
            <div class="row g-3 mb-2">
                <div class="col-6">
                    <div class="p-3 rounded-4" style="background: rgba(99, 102, 241, 0.06); border: 1px solid var(--tamoza-border);">
                        <span class="text-secondary small d-block mb-1"><?= I18n::t('current_version_label') ?></span>
                        <span class="fs-5 fw-bold font-monospace text-primary">v<?= Helpers::e($updateInfo['current']) ?></span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 rounded-4" style="background: rgba(16, 185, 129, 0.06); border: 1px solid var(--tamoza-border);">
                        <span class="text-secondary small d-block mb-1"><?= I18n::t('latest_version_label') ?></span>
                        <span id="updateLatestVersionDisplay" class="fs-5 fw-bold font-monospace <?= !empty($updateInfo['has_update']) ? 'text-warning' : 'text-success' ?>">
                            v<?= Helpers::e(!empty($updateInfo['latest']) ? $updateInfo['latest'] : ($updateInfo['current'] ?? APP_VERSION)) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- GitHub Source Link Hint -->
            <div class="mb-3">
                <a href="<?= Helpers::e($updateInfo['release_url']) ?>" target="_blank" class="text-secondary small text-decoration-none d-inline-flex align-items-center gap-1">
                    <span><?= I18n::t('repo_url_label') ?>: Tamoza4/AtharLink</span>
                    <?= Icon::get('external-link', '', 12) ?>
                </a>
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
                    <button type="button" id="checkUpdatesBtn" class="btn btn-tamoza-secondary d-inline-flex align-items-center gap-2">
                        <span id="checkUpdatesIcon"><?= Icon::get('refresh', '', 14) ?></span>
                        <span id="checkUpdatesText"><?= I18n::t('check_updates_btn') ?></span>
                    </button>

                    <!-- Trigger Interactive Update Modal (Always Active, Checks First) -->
                    <button type="button" id="triggerUpdateBtn" class="btn <?= !empty($updateInfo['has_update']) ? 'btn-tamoza-primary' : 'btn-tamoza-secondary' ?> d-inline-flex align-items-center gap-2">
                        <span id="triggerUpdateIcon"><?= Icon::get('rocket', '', 15) ?></span>
                        <span id="triggerUpdateText"><?= I18n::t('update_now_btn') ?></span>
                    </button>
                </div>

                <span id="updateCheckedDateDisplay" class="text-secondary small">
                    <?= I18n::t('update_checking_hint', ['date' => !empty($updateInfo['cached_at']) ? date('Y-m-d H:i', (int)$updateInfo['cached_at']) : date('Y-m-d H:i')]) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Check Updates Feedback Modal -->
<div class="modal fade" id="checkUpdateStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content shadow-lg border-0 text-center p-4" style="background: #161A23 !important; border: 1px solid var(--tamoza-border); border-radius: var(--tamoza-radius-lg);">
            <div id="checkModalIconWrapper" class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 58px; height: 58px; border-radius: 50%;">
                <span id="checkModalIcon"></span>
            </div>
            <h5 class="fw-bold mb-2 text-white" id="checkModalTitle"></h5>
            <p class="text-secondary small mb-4" id="checkModalMessage"></p>
            <div class="d-flex justify-content-center gap-2" id="checkModalActions">
                <button type="button" class="btn btn-tamoza-primary px-4" data-bs-dismiss="modal"><?= I18n::t('ok_btn') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: System Update with Confirmation Step & Progress Bar -->
<div class="modal fade" id="systemUpdateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="background: #161A23 !important; border: 1px solid var(--tamoza-border); border-radius: var(--tamoza-radius-lg);">
            
            <!-- Step 1: Confirmation View -->
            <div id="updateConfirmView">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2 text-white">
                        <?= Icon::get('rocket', 'text-indigo') ?>
                        <span><?= I18n::t('update_confirm_title') ?></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="text-white fw-semibold mb-3"><?= I18n::t('update_confirm_text') ?></p>
                    <div class="p-3 rounded-4 mb-2 small" style="background: rgba(99, 102, 241, 0.05); border: 1px solid var(--tamoza-border);">
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-2 text-secondary">
                            <li class="d-flex align-items-center gap-2">
                                <span class="text-success"><?= Icon::get('check-circle', '', 14) ?></span>
                                <span><?= I18n::t('update_confirm_point_backup') ?></span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="text-success"><?= Icon::get('check-circle', '', 14) ?></span>
                                <span><?= I18n::t('update_confirm_point_git') ?></span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="text-success"><?= Icon::get('check-circle', '', 14) ?></span>
                                <span><?= I18n::t('update_confirm_point_db') ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-tamoza-subtle btn-sm px-3" data-bs-dismiss="modal"><?= I18n::t('update_dismiss_btn') ?></button>
                    <button type="button" class="btn btn-tamoza-primary btn-sm px-4 d-inline-flex align-items-center gap-2" id="startUpdateExecutionBtn">
                        <?= Icon::get('rocket', '', 14) ?>
                        <span><?= I18n::t('update_confirm_btn') ?></span>
                    </button>
                </div>
            </div>

            <!-- Step 2: Execution & Progress Bar View -->
            <div id="updateProgressView" class="d-none">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2 text-white">
                        <?= Icon::get('cloud-download', 'text-indigo') ?>
                        <span><?= I18n::t('update_modal_title') ?></span>
                    </h5>
                    <button type="button" class="btn-close" id="updateModalCloseBtn" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <!-- Progress Bar Container -->
                    <div class="progress mb-3" style="height: 10px; background: rgba(140, 150, 170, 0.15); border-radius: 999px;">
                        <div id="updateProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%; background: #6366F1; transition: width 0.4s ease;"></div>
                    </div>

                    <!-- Live Status Line -->
                    <div id="updateStepStatus" class="fw-semibold small text-primary mb-2 d-flex align-items-center gap-2">
                        <span id="updateSpinner" class="spinner-border spinner-border-sm text-primary"></span>
                        <span id="updateStepText"><?= I18n::t('update_step_backup') ?></span>
                    </div>

                    <!-- Collapsible Log Details -->
                    <div id="updateLogWrapper" class="d-none mt-3">
                        <div class="text-secondary small fw-semibold mb-1"><?= I18n::t('update_log_title') ?></div>
                        <div id="updateLogBox" class="p-3 rounded-3 font-monospace text-start" dir="ltr" style="background: rgba(0, 0, 0, 0.45); border: 1px solid var(--tamoza-border); max-height: 140px; overflow-y: auto; font-size: 11.5px; color: #94A3B8; white-space: pre-wrap;"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-tamoza-subtle btn-sm px-3" id="updateCancelBtn" data-bs-dismiss="modal"><?= I18n::t('cancel_btn') ?></button>
                    <button type="button" class="btn btn-tamoza-primary btn-sm px-3 d-none" id="updateReloadBtn" onclick="window.location.reload();">
                        <?= Icon::get('refresh', '', 14) ?>
                        <span><?= I18n::t('reload_platform_btn') ?></span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Password/Key Visibility Toggles
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

    // 2. Localization context for updates
    const updateI18n = {
        stepBackup: <?= json_encode(I18n::t('update_step_backup')) ?>,
        stepGit: <?= json_encode(I18n::t('update_step_git')) ?>,
        stepMigration: <?= json_encode(I18n::t('update_step_migration')) ?>,
        stepIntegrity: <?= json_encode(I18n::t('update_step_integrity')) ?>,
        stepComplete: <?= json_encode(I18n::t('update_step_complete')) ?>,
        stepFailed: <?= json_encode(I18n::t('update_step_failed')) ?>,
        upToDate: <?= json_encode(I18n::t('system_up_to_date')) ?>,
        checkingLoading: <?= json_encode(I18n::t('checking_updates_loading')) ?>,
        checkBtnText: <?= json_encode(I18n::t('check_updates_btn')) ?>,
        checkUpToDateTitle: <?= json_encode(I18n::t('update_check_up_to_date_title')) ?>,
        checkUpToDateMsg: <?= json_encode(I18n::t('update_check_up_to_date_msg')) ?>,
        checkAvailableTitle: <?= json_encode(I18n::t('update_check_available_title')) ?>,
        checkAvailableMsg: <?= json_encode(I18n::t('update_check_available_msg')) ?>,
        updateNowBtnText: <?= json_encode(I18n::t('update_now_btn')) ?>,
        dismissBtnText: <?= json_encode(I18n::t('update_dismiss_btn')) ?>,
        okBtnText: <?= json_encode(I18n::t('ok_btn')) ?>,
        disabledHint: <?= json_encode(I18n::t('update_disabled_hint')) ?>,
        hasUpdate: <?= json_encode(!empty($updateInfo['has_update'])) ?>,
        checkCircleIcon: <?= json_encode(Icon::get('check-circle', '', 28)) ?>,
        rocketIcon: <?= json_encode(Icon::get('rocket', '', 28)) ?>,
        rocketIconSmall: <?= json_encode(Icon::get('rocket', '', 15)) ?>,
        csrfToken: <?= json_encode(Helpers::csrfToken()) ?>,
        checkUrl: <?= json_encode(Helpers::baseUrl('index.php?action=check_updates')) ?>,
        applyUrl: <?= json_encode(Helpers::baseUrl('index.php?action=apply_update')) ?>
    };

    const updateModalEl = document.getElementById('systemUpdateModal');
    let systemModalInstance = null;
    if (updateModalEl) {
        systemModalInstance = new bootstrap.Modal(updateModalEl);
    }

    const openUpdateModalWithConfirm = () => {
        if (!systemModalInstance) return;
        const confirmView = document.getElementById('updateConfirmView');
        const progressView = document.getElementById('updateProgressView');
        if (confirmView && progressView) {
            confirmView.classList.remove('d-none');
            progressView.classList.add('d-none');
        }
        systemModalInstance.show();
    };

    const renderCheckResultModal = (data) => {
        const checkStatusModalEl = document.getElementById('checkUpdateStatusModal');
        if (!checkStatusModalEl) return;
        const iconWrapper = document.getElementById('checkModalIconWrapper');
        const titleEl = document.getElementById('checkModalTitle');
        const msgEl = document.getElementById('checkModalMessage');
        const actionsEl = document.getElementById('checkModalActions');
        const checkModal = new bootstrap.Modal(checkStatusModalEl);

        if (data.has_update) {
            iconWrapper.style.background = 'rgba(99, 102, 241, 0.15)';
            iconWrapper.style.color = '#818CF8';
            iconWrapper.innerHTML = updateI18n.rocketIcon;
            titleEl.textContent = updateI18n.checkAvailableTitle;
            msgEl.textContent = updateI18n.checkAvailableMsg.replace(':version', 'v' + data.latest);
            actionsEl.innerHTML = `
                <button type="button" class="btn btn-tamoza-subtle px-3" data-bs-dismiss="modal">${updateI18n.dismissBtnText}</button>
                <button type="button" class="btn btn-tamoza-primary px-3 d-inline-flex align-items-center gap-1" id="openUpdateFromCheckBtn">
                    <span>${updateI18n.updateNowBtnText}</span>
                </button>
            `;
            const openUpdateBtn = document.getElementById('openUpdateFromCheckBtn');
            if (openUpdateBtn) {
                openUpdateBtn.addEventListener('click', function () {
                    checkModal.hide();
                    openUpdateModalWithConfirm();
                });
            }
        } else {
            iconWrapper.style.background = 'rgba(16, 185, 129, 0.15)';
            iconWrapper.style.color = '#10B981';
            iconWrapper.innerHTML = updateI18n.checkCircleIcon;
            titleEl.textContent = updateI18n.checkUpToDateTitle;
            msgEl.textContent = updateI18n.checkUpToDateMsg.replace(':version', 'v' + data.latest);
            actionsEl.innerHTML = `
                <button type="button" class="btn btn-tamoza-primary px-4" data-bs-dismiss="modal">${updateI18n.okBtnText}</button>
            `;
        }
        checkModal.show();
    };

    const syncVersionDisplays = (data) => {
        updateI18n.hasUpdate = Boolean(data.has_update);

        const latestEl = document.getElementById('updateLatestVersionDisplay');
        if (latestEl) {
            latestEl.textContent = 'v' + data.latest;
            latestEl.className = data.has_update ? 'fs-5 fw-bold font-monospace text-warning' : 'fs-5 fw-bold font-monospace text-success';
        }

        const badgeWrapper = document.getElementById('updateStatusBadgeWrapper');
        if (badgeWrapper) {
            if (data.has_update) {
                badgeWrapper.innerHTML = '<span id="updateStatusBadge" class="tamoza-badge badge-warning d-inline-flex align-items-center gap-1"><span class="pulse-dot"></span><span>' + <?= json_encode(I18n::t('update_available_badge', ['version' => ':v'])) ?>.replace(':v', 'v' + data.latest) + '</span></span>';
            } else {
                badgeWrapper.innerHTML = '<span id="updateStatusBadge" class="tamoza-badge badge-success d-inline-flex align-items-center gap-1">' + <?= json_encode(Icon::get('check-circle', '', 14)) ?> + ' <span>' + updateI18n.upToDate + '</span></span>';
            }
        }

        const dateEl = document.getElementById('updateCheckedDateDisplay');
        if (dateEl) {
            const now = new Date();
            const formatted = now.toISOString().slice(0, 16).replace('T', ' ');
            dateEl.textContent = <?= json_encode(I18n::t('update_checking_hint', ['date' => ':d'])) ?>.replace(':d', formatted);
        }

        const triggerBtn = document.getElementById('triggerUpdateBtn');
        if (triggerBtn) {
            if (data.has_update) {
                triggerBtn.classList.remove('btn-tamoza-secondary');
                triggerBtn.classList.add('btn-tamoza-primary');
            } else {
                triggerBtn.classList.remove('btn-tamoza-primary');
                triggerBtn.classList.add('btn-tamoza-secondary');
            }
        }
    };

    // 3. AJAX Check for Updates Button
    const checkBtn = document.getElementById('checkUpdatesBtn');
    if (checkBtn) {
        checkBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const iconEl = document.getElementById('checkUpdatesIcon');
            const textEl = document.getElementById('checkUpdatesText');
            checkBtn.disabled = true;
            if (iconEl) iconEl.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            if (textEl) textEl.textContent = updateI18n.checkingLoading;

            fetch(updateI18n.checkUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                checkBtn.disabled = false;
                if (iconEl) iconEl.innerHTML = <?= json_encode(Icon::get('refresh', '', 14)) ?>;
                if (textEl) textEl.textContent = updateI18n.checkBtnText;

                syncVersionDisplays(data);
                renderCheckResultModal(data);
            })
            .catch(() => {
                checkBtn.disabled = false;
                if (iconEl) iconEl.innerHTML = <?= json_encode(Icon::get('refresh', '', 14)) ?>;
                if (textEl) textEl.textContent = updateI18n.checkBtnText;
            });
        });
    }

    // 4. Trigger Update Flow: Always active, checks first then proceeds or informs
    const triggerUpdateBtn = document.getElementById('triggerUpdateBtn');
    if (triggerUpdateBtn) {
        triggerUpdateBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const iconEl = document.getElementById('triggerUpdateIcon');
            const textEl = document.getElementById('triggerUpdateText');

            triggerUpdateBtn.disabled = true;
            if (iconEl) iconEl.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            if (textEl) textEl.textContent = updateI18n.checkingLoading;

            fetch(updateI18n.checkUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                triggerUpdateBtn.disabled = false;
                if (iconEl) iconEl.innerHTML = updateI18n.rocketIconSmall;
                if (textEl) textEl.textContent = updateI18n.updateNowBtnText;

                syncVersionDisplays(data);

                if (data.has_update) {
                    // Update exists -> Show confirmation modal
                    openUpdateModalWithConfirm();
                } else {
                    // No update -> Show up-to-date modal immediately
                    renderCheckResultModal(data);
                }
            })
            .catch(() => {
                triggerUpdateBtn.disabled = false;
                if (iconEl) iconEl.innerHTML = updateI18n.rocketIconSmall;
                if (textEl) textEl.textContent = updateI18n.updateNowBtnText;
            });
        });
    }

    // 5. Start Update Execution after User Confirmation
    const startUpdateExecutionBtn = document.getElementById('startUpdateExecutionBtn');
    if (startUpdateExecutionBtn) {
        startUpdateExecutionBtn.addEventListener('click', function () {
            const confirmView = document.getElementById('updateConfirmView');
            const progressView = document.getElementById('updateProgressView');
            if (confirmView) confirmView.classList.add('d-none');
            if (progressView) progressView.classList.remove('d-none');

            const pBar = document.getElementById('updateProgressBar');
            const stepText = document.getElementById('updateStepText');
            const spinner = document.getElementById('updateSpinner');
            const logWrapper = document.getElementById('updateLogWrapper');
            const logBox = document.getElementById('updateLogBox');
            const cancelBtn = document.getElementById('updateCancelBtn');
            const reloadBtn = document.getElementById('updateReloadBtn');
            const closeBtn = document.getElementById('updateModalCloseBtn');

            // Reset initial state
            pBar.className = 'progress-bar progress-bar-striped progress-bar-animated';
            pBar.style.background = '#6366F1';
            pBar.style.width = '15%';
            stepText.textContent = updateI18n.stepBackup;
            stepText.className = 'fw-semibold small text-primary';
            spinner.className = 'spinner-border spinner-border-sm text-primary';
            spinner.classList.remove('d-none');
            logWrapper.classList.add('d-none');
            cancelBtn.classList.remove('d-none');
            cancelBtn.disabled = true;
            if (closeBtn) closeBtn.disabled = true;
            reloadBtn.classList.add('d-none');

            // Smooth progress step transitions while backend performs work
            setTimeout(() => {
                if (pBar.style.width === '15%') {
                    pBar.style.width = '45%';
                    stepText.textContent = updateI18n.stepGit;
                }
            }, 600);

            setTimeout(() => {
                if (pBar.style.width === '45%') {
                    pBar.style.width = '70%';
                    stepText.textContent = updateI18n.stepMigration;
                }
            }, 1200);

            setTimeout(() => {
                if (pBar.style.width === '70%') {
                    pBar.style.width = '88%';
                    stepText.textContent = updateI18n.stepIntegrity;
                }
            }, 1800);

            const formData = new FormData();
            formData.append('csrf_token', updateI18n.csrfToken);

            fetch(updateI18n.applyUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                cancelBtn.disabled = false;
                if (closeBtn) closeBtn.disabled = false;
                if (data.success) {
                    pBar.style.width = '100%';
                    pBar.style.background = '#10B981';
                    pBar.classList.remove('progress-bar-animated');
                    spinner.classList.add('d-none');
                    stepText.textContent = updateI18n.stepComplete;
                    stepText.className = 'fw-bold small text-success';

                    if (data.log && data.log.length > 0) {
                        logBox.textContent = data.log.join('\n');
                        logWrapper.classList.remove('d-none');
                    }

                    cancelBtn.classList.add('d-none');
                    reloadBtn.classList.remove('d-none');
                } else if (data.already_latest) {
                    // System is already up to date: no update was needed
                    pBar.style.width = '100%';
                    pBar.style.background = '#3B82F6';
                    pBar.classList.remove('progress-bar-animated');
                    spinner.classList.add('d-none');
                    stepText.textContent = data.message;
                    stepText.className = 'fw-bold small text-info';

                    if (data.log && data.log.length > 0) {
                        logBox.textContent = data.log.join('\n');
                        logWrapper.classList.remove('d-none');
                    }
                    cancelBtn.classList.remove('d-none');
                    cancelBtn.disabled = false;
                } else {
                    pBar.style.width = '100%';
                    pBar.style.background = '#EF4444';
                    pBar.classList.remove('progress-bar-animated');
                    spinner.classList.add('d-none');
                    stepText.textContent = data.message || updateI18n.stepFailed;
                    stepText.className = 'fw-bold small text-danger';

                    if (data.log && data.log.length > 0) {
                        logBox.textContent = data.log.join('\n');
                        logWrapper.classList.remove('d-none');
                    }
                }
            })
            .catch(err => {
                cancelBtn.disabled = false;
                if (closeBtn) closeBtn.disabled = false;
                pBar.style.width = '100%';
                pBar.style.background = '#EF4444';
                pBar.classList.remove('progress-bar-animated');
                spinner.classList.add('d-none');
                stepText.textContent = updateI18n.stepFailed + ' ' + (err.message || '');
                stepText.className = 'fw-bold small text-danger';
            });
        });
    }

    // 6. Seamless Automatic Check on Page Load
    const runAutoCheckOnPageLoad = () => {
        fetch(updateI18n.checkUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            syncVersionDisplays(data);
        })
        .catch(() => {
            // Silently fallback without disrupting the user
        });
    };

    // Trigger auto-check smoothly 250ms after page loads
    setTimeout(runAutoCheckOnPageLoad, 250);
});
</script>
