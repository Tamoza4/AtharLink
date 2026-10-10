<?php
declare(strict_types=1);

/**
 * Tamoza UI Standard - Verified: 8pt Logic, Premium Glassmorphism, Centered Layout, Indigo Brand DNA, Dual Language.
 */

use AtharLink\Helpers;
use AtharLink\I18n;
use AtharLink\Icon;

$search = $_GET['search'] ?? '';
$activeFilter = $_GET['is_active'] ?? '';
?>

<!-- Page Header & Action Controls -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1"><?= Helpers::e(I18n::t('links_title')) ?></h2>
        <p class="text-secondary small mb-0"><?= Helpers::e(I18n::t('links_desc')) ?></p>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-3">
        <!-- Export Dropdown -->
        <div class="dropdown">
            <button class="btn btn-tamoza-secondary dropdown-toggle d-inline-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-popper-config='{"strategy":"fixed"}'>
                <?= Icon::get('download', '', 16) ?>
                <span><?= Helpers::e(I18n::t('export_data')) ?></span>
            </button>
            <ul class="dropdown-menu shadow border-0 py-2">
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= Helpers::baseUrl('index.php?action=export_links&format=csv') ?>"><?= Icon::get('download', '', 14) ?> <?= Helpers::e(I18n::t('export_links_csv')) ?></a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= Helpers::baseUrl('index.php?action=export_links&format=json') ?>"><?= Icon::get('download', '', 14) ?> <?= Helpers::e(I18n::t('export_links_json')) ?></a></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= Helpers::baseUrl('index.php?action=export_clicks&format=csv') ?>"><?= Icon::get('download', '', 14) ?> <?= Helpers::e(I18n::t('export_clicks_csv')) ?></a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= Helpers::baseUrl('index.php?action=export_clicks&format=json') ?>"><?= Icon::get('download', '', 14) ?> <?= Helpers::e(I18n::t('export_clicks_json')) ?></a></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item text-primary d-flex align-items-center gap-2" href="javascript:void(0)" id="exportLinksOverviewImageBtn"><?= Icon::get('image', '', 14) ?> <?= Helpers::e(I18n::t('export_summary_image_btn')) ?></a></li>
            </ul>
        </div>

        <!-- Create Link Button (Gradient CTA) -->
        <button type="button" class="btn btn-tamoza-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createLinkModal">
            <?= Icon::get('plus', '', 16) ?>
            <span><?= Helpers::e(I18n::t('new_link_btn')) ?></span>
        </button>
    </div>
</div>

<!-- Filters and Search Toolbar (Tamoza Glassmorphism Card) -->
<div class="tamoza-card p-3 mb-4">
    <form method="GET" action="<?= Helpers::baseUrl('index.php') ?>" class="row g-3 align-items-center">
        <input type="hidden" name="page" value="links">
        
        <!-- Seamless Search Input with Icon Inside -->
        <div class="col-12 col-md-5 col-lg-5">
            <div class="tamoza-search-wrapper">
                <span class="tamoza-search-icon"><?= Icon::get('search', '', 16) ?></span>
                <input type="text" name="search" class="tamoza-search-input" placeholder="<?= Helpers::e(I18n::t('search_placeholder')) ?>" value="<?= Helpers::e($search) ?>">
            </div>
        </div>

        <!-- Status Filter Dropdown -->
        <div class="col-12 col-md-4 col-lg-4">
            <select name="is_active" class="form-select tamoza-input">
                <option value=""><?= Helpers::e(I18n::t('all_statuses')) ?></option>
                <option value="1" <?= $activeFilter === '1' ? 'selected' : '' ?>><?= Helpers::e(I18n::t('active_only')) ?></option>
                <option value="0" <?= $activeFilter === '0' ? 'selected' : '' ?>><?= Helpers::e(I18n::t('paused_only')) ?></option>
            </select>
        </div>

        <!-- Filter & Reset Actions -->
        <div class="col-12 col-md-3 col-lg-3 d-flex gap-2">
            <button type="submit" class="btn btn-tamoza-secondary flex-grow-1"><?= Helpers::e(I18n::t('filter_btn')) ?></button>
            <?php if ($search !== '' || $activeFilter !== ''): ?>
                <a href="<?= Helpers::baseUrl('index.php?page=links') ?>" class="btn btn-tamoza-subtle px-3"><?= Helpers::e(I18n::t('cancel_btn')) ?></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Links Table (Tamoza Glass Container with Visible Dropdown Support) -->
<div class="tamoza-table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th><?= Helpers::e(I18n::t('col_link_details')) ?></th>
                    <th class="text-center"><?= Helpers::e(I18n::t('col_clicks')) ?></th>
                    <th class="text-center"><?= Helpers::e(I18n::t('col_status')) ?></th>
                    <th><?= Helpers::e(I18n::t('col_created')) ?></th>
                    <th class="text-end"><?= Helpers::e(I18n::t('col_actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($links)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-secondary">
                            <div class="display-6 mb-2 text-indigo"><?= Icon::get('link-2', '', 40) ?></div>
                            <p class="mb-3"><?= Helpers::e(I18n::t('no_links_found')) ?></p>
                            <button type="button" class="btn btn-tamoza-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createLinkModal">
                                <?= Icon::get('plus', '', 16) ?>
                                <span><?= Helpers::e(I18n::t('create_first_link')) ?></span>
                            </button>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($links as $l): ?>
                        <?php 
                            $trackUrl = Helpers::trackingUrl($l['slug']);
                            $isExpired = !empty($l['expires_at']) && strtotime($l['expires_at']) <= time();
                            $isLimitReached = !empty($l['click_limit']) && (int)$l['total_clicks'] >= (int)$l['click_limit'];
                        ?>
                        <tr>
                            <!-- Slug, Title & Destination URL -->
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <a href="<?= Helpers::e($trackUrl) ?>" target="_blank" class="fw-bold fs-6 text-decoration-none text-primary">
                                            /<?= Helpers::e($l['slug']) ?>
                                        </a>
                                        <?php if (!empty($l['title'])): ?>
                                            <span class="text-secondary small fw-medium text-truncate" style="max-width: 280px;" title="<?= Helpers::e($l['title']) ?>">
                                                <?= Helpers::e($l['title']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($l['password_hash']) || !empty($l['password_plain'])): ?>
                                            <span class="tamoza-badge badge-warning" title="<?= !empty($l['password_plain']) ? Helpers::e(I18n::t('password_input_label')) . ': ' . Helpers::e($l['password_plain']) : Helpers::e(I18n::t('password_status_protected')) ?>">
                                                <?= Icon::get('key', '', 11) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 text-secondary small">
                                        <span class="opacity-50 select-none" style="font-size: 11px;">↳</span>
                                        <a href="<?= Helpers::e($l['target_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-secondary text-decoration-none d-inline-flex align-items-center gap-1 text-truncate" style="max-width: 480px;" title="<?= Helpers::e($l['target_url']) ?>">
                                            <span class="text-truncate"><?= Helpers::e($l['target_url']) ?></span>
                                            <span class="opacity-50"><?= Icon::get('external-link', '', 11) ?></span>
                                        </a>
                                    </div>
                                </div>
                            </td>

                            <!-- Clicks Counter -->
                            <td class="text-center text-nowrap">
                                <div class="d-inline-flex flex-column align-items-center gap-1">
                                    <span class="fw-bold fs-6"><?= number_format($l['total_clicks']) ?></span>
                                    <span class="text-success small fw-medium" style="font-size: 11px;" title="<?= Helpers::e(I18n::t('kpi_unique_clicks')) ?>">
                                        <?= number_format($l['unique_clicks']) ?> <?= Helpers::e(I18n::t('type_unique_badge')) ?>
                                    </span>
                                    <?php if (!empty($l['initial_clicks'])): ?>
                                        <span class="text-secondary opacity-75" style="font-size: 10px;">
                                            (<?= number_format((int)$l['initial_clicks']) ?> +)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Status & Redirect Type -->
                            <td class="text-center text-nowrap">
                                <div class="d-inline-flex flex-column align-items-center gap-1">
                                    <?php if ($isExpired): ?>
                                        <span class="tamoza-badge badge-danger"><?= Helpers::e(I18n::t('status_expired')) ?></span>
                                    <?php elseif ($isLimitReached): ?>
                                        <span class="tamoza-badge badge-warning"><?= Helpers::e(I18n::t('status_limit_reached')) ?></span>
                                    <?php elseif ((int)$l['is_active'] === 1): ?>
                                        <span class="tamoza-badge badge-success"><?= Helpers::e(I18n::t('status_active')) ?></span>
                                    <?php else: ?>
                                        <span class="tamoza-badge badge-muted"><?= Helpers::e(I18n::t('status_paused')) ?></span>
                                    <?php endif; ?>

                                    <?php if ((int)$l['redirect_type'] === 301): ?>
                                        <span class="tamoza-badge badge-indigo" style="font-size: 10px; padding: 1px 6px;" title="<?= Helpers::e(I18n::t('type_permanent')) ?>">301</span>
                                    <?php elseif ((int)$l['redirect_type'] === 2): ?>
                                        <span class="tamoza-badge badge-warning" style="font-size: 10px; padding: 1px 6px;" title="<?= Helpers::e(I18n::t('type_download')) ?>"><?= Helpers::e(I18n::t('type_download')) ?></span>
                                    <?php else: ?>
                                        <span class="tamoza-badge badge-muted" style="font-size: 10px; padding: 1px 6px;" title="<?= Helpers::e(I18n::t('type_temporary')) ?>">302</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Created Date -->
                            <td class="text-secondary small text-nowrap">
                                <?= Helpers::formatDate($l['created_at'], 'Y-m-d') ?>
                            </td>

                            <!-- Actions -->
                            <td class="text-end text-nowrap">
                                <div class="d-inline-flex gap-1 align-items-center">
                                    <!-- Copy Button -->
                                    <button type="button" class="btn btn-tamoza-subtle" data-copy-url="<?= Helpers::e($trackUrl) ?>" title="<?= Helpers::e(I18n::t('copy_url_tooltip')) ?>">
                                        <?= Icon::get('copy', '', 15) ?>
                                    </button>

                                     <!-- QR Code Button -->
                                    <button type="button" class="btn btn-tamoza-subtle" data-bs-toggle="modal" data-bs-target="#qrCodeModal" data-qr-url="<?= Helpers::e($trackUrl) ?>" data-qr-title="<?= Helpers::e($l['slug']) ?>" title="<?= Helpers::e(I18n::t('qr_code_title')) ?>">
                                        <?= Icon::get('qr-code', '', 15) ?>
                                    </button>

                                    <!-- Embed Counter Button -->
                                    <button type="button" class="btn btn-tamoza-subtle" data-bs-toggle="modal" data-bs-target="#embedCounterModal" 
                                        data-slug="<?= Helpers::e($l['slug']) ?>" 
                                        data-total-clicks="<?= (int)$l['total_clicks'] ?>" 
                                        data-unique-clicks="<?= (int)$l['unique_clicks'] ?>" 
                                        title="<?= Helpers::e(I18n::t('embed_counter_btn')) ?>">
                                        <?= Icon::get('code', '', 15) ?>
                                    </button>

                                    <!-- Detailed Stats -->
                                    <a href="<?= Helpers::baseUrl('index.php?page=stats&id=' . $l['id']) ?>" class="btn btn-tamoza-subtle" title="<?= Helpers::e(I18n::t('stats_tooltip')) ?>">
                                        <?= Icon::get('bar-chart', '', 15) ?>
                                    </a>

                                    <!-- Pause / Resume Toggle -->
                                    <form method="POST" action="<?= Helpers::baseUrl('index.php?action=toggle_link') ?>" class="d-inline m-0 p-0">
                                        <?= Helpers::csrfInput() ?>
                                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                        <button type="submit" class="btn btn-tamoza-subtle" title="<?= (int)$l['is_active'] === 1 ? Helpers::e(I18n::t('pause_action')) : Helpers::e(I18n::t('resume_action')) ?>">
                                            <?= (int)$l['is_active'] === 1 ? Icon::get('pause', '', 15) : Icon::get('play', '', 15) ?>
                                        </button>
                                    </form>

                                    <!-- More Actions Dropdown (Anchored to button) -->
                                    <div class="dropdown d-inline-block">
                                        <button type="button" class="btn btn-tamoza-subtle dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" title="<?= Helpers::e(I18n::t('col_actions')) ?>"></button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2">
                                            <li>
                                                <button class="dropdown-item py-2 text-indigo d-flex align-items-center gap-2" type="button" data-bs-toggle="modal" data-bs-target="#embedCounterModal" 
                                                    data-slug="<?= Helpers::e($l['slug']) ?>" 
                                                    data-total-clicks="<?= (int)$l['total_clicks'] ?>" 
                                                    data-unique-clicks="<?= (int)$l['unique_clicks'] ?>">
                                                    <?= Icon::get('code', '', 14) ?>
                                                    <span><?= Helpers::e(I18n::t('embed_counter_btn')) ?></span>
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item py-2 d-flex align-items-center gap-2" type="button" data-bs-toggle="modal" data-bs-target="#editLinkModal" 
                                                    data-link-id="<?= $l['id'] ?>"
                                                    data-slug="<?= Helpers::e($l['slug']) ?>"
                                                    data-target-url="<?= Helpers::e($l['target_url']) ?>"
                                                    data-title="<?= Helpers::e($l['title'] ?? '') ?>"
                                                    data-redirect-type="<?= $l['redirect_type'] ?>"
                                                    data-click-limit="<?= Helpers::e((string)($l['click_limit'] ?? '')) ?>"
                                                    data-expires-at="<?= Helpers::e((string)($l['expires_at'] ?? '')) ?>"
                                                    data-forward-utm="<?= $l['forward_utm'] ?>"
                                                    data-is-active="<?= $l['is_active'] ?>"
                                                    data-total-clicks="<?= (int)$l['total_clicks'] ?>"
                                                    data-unique-clicks="<?= (int)$l['unique_clicks'] ?>"
                                                    data-has-password="<?= (!empty($l['password_hash']) || !empty($l['password_plain'])) ? '1' : '0' ?>"
                                                    data-password="<?= Helpers::e(Helpers::decryptSensitive($l['password_plain'] ?? null) ?? '') ?>"
                                                    data-auth-lang="<?= Helpers::e($l['auth_lang'] ?? 'auto') ?>"
                                                >
                                                    <?= Icon::get('edit', '', 14) ?>
                                                    <span><?= Helpers::e(I18n::t('edit_link_action')) ?></span>
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item py-2 text-warning d-flex align-items-center gap-2" type="button" data-bs-toggle="modal" data-bs-target="#resetClicksModal" data-link-id="<?= $l['id'] ?>" data-link-slug="<?= Helpers::e($l['slug']) ?>">
                                                    <?= Icon::get('refresh', '', 14) ?>
                                                    <span><?= Helpers::e(I18n::t('reset_clicks_action')) ?></span>
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <button class="dropdown-item py-2 text-info d-flex align-items-center gap-2" type="button" data-export-link-image
                                                    data-link-id="<?= $l['id'] ?>"
                                                    data-slug="<?= Helpers::e($l['slug']) ?>"
                                                    data-title="<?= Helpers::e($l['title'] ?? '') ?>"
                                                    data-target-url="<?= Helpers::e($l['target_url']) ?>"
                                                    data-total-clicks="<?= (int)$l['total_clicks'] ?>"
                                                    data-unique-clicks="<?= (int)$l['unique_clicks'] ?>"
                                                    data-is-active="<?= $l['is_active'] ?>"
                                                >
                                                    <?= Icon::get('image', '', 14) ?>
                                                    <span><?= Helpers::e(I18n::t('export_image_btn')) ?></span>
                                                </button>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 text-secondary d-flex align-items-center gap-2" href="<?= Helpers::baseUrl('index.php?action=export_clicks&link_id=' . $l['id'] . '&format=csv') ?>">
                                                    <?= Icon::get('download', '', 14) ?>
                                                    <span><?= Helpers::e(I18n::t('export_clicks_csv_btn')) ?></span>
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <button class="dropdown-item py-2 text-danger d-flex align-items-center gap-2" type="button" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" data-link-id="<?= $l['id'] ?>" data-link-slug="<?= Helpers::e($l['slug']) ?>">
                                                    <?= Icon::get('trash', '', 14) ?>
                                                    <span><?= Helpers::e(I18n::t('delete_link_action')) ?></span>
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create New Link (24px Radius & Tamoza DNA) -->
<div class="modal fade" id="createLinkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <form method="POST" action="<?= Helpers::baseUrl('index.php?action=create_link') ?>">
                <?= Helpers::csrfInput() ?>
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                        <?= Icon::get('plus', 'text-indigo') ?>
                        <span><?= Helpers::e(I18n::t('create_modal_title')) ?></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body py-4">
                    <!-- Target URL -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('destination_url')) ?> <span class="text-danger">*</span></label>
                        <input type="url" name="target_url" class="form-control tamoza-input" placeholder="https://example.com/my-page" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Custom Slug -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('slug_label')) ?></label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-0 pe-2">/</span>
                                <input type="text" name="slug" id="linkSlugInput" class="form-control tamoza-input" placeholder="<?= Helpers::e(I18n::t('slug_hint')) ?>">
                            </div>
                            <div id="slugFeedback" class="mt-1"></div>
                        </div>

                        <!-- Title / Label -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('title_label')) ?></label>
                            <input type="text" name="title" class="form-control tamoza-input" placeholder="My Campaign / Link Title">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Redirection Type -->
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('redirect_type_label')) ?></label>
                            <select name="redirect_type" class="form-select tamoza-input">
                                <option value="302"><?= Helpers::e(I18n::t('type_temporary')) ?></option>
                                <option value="301"><?= Helpers::e(I18n::t('type_permanent')) ?></option>
                                <option value="2">2 (<?= Helpers::e(I18n::t('type_download')) ?>)</option>
                            </select>
                        </div>

                        <!-- Click Limit -->
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('click_limit_label')) ?></label>
                            <input type="number" name="click_limit" class="form-control tamoza-input" placeholder="500" min="1">
                        </div>

                        <!-- Expiration Date -->
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('expires_at_label')) ?></label>
                            <input type="datetime-local" name="expires_at" class="form-control tamoza-input">
                        </div>
                    </div>

                    <!-- Initial / Imported Clicks Fields (User Requested Feature) -->
                    <div class="p-3 mb-3 rounded-3" style="background: rgba(99, 102, 241, 0.05); border: 1px dashed var(--tamoza-border);">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small d-flex align-items-center gap-1">
                                    <?= Icon::get('bar-chart', 'text-indigo', 14) ?>
                                    <span><?= Helpers::e(I18n::t('initial_clicks_label')) ?></span>
                                </label>
                                <input type="number" name="initial_clicks" class="form-control tamoza-input" placeholder="0" min="0">
                                <small class="text-secondary"><?= Helpers::e(I18n::t('initial_clicks_hint')) ?></small>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small d-flex align-items-center gap-1">
                                    <?= Icon::get('target', 'text-indigo', 14) ?>
                                    <span><?= Helpers::e(I18n::t('initial_unique_label')) ?></span>
                                </label>
                                <input type="number" name="initial_unique_clicks" class="form-control tamoza-input" placeholder="0" min="0">
                            </div>
                        </div>
                    </div>

                    <!-- Password Protection & Authentication Language (User Requested Feature) -->
                    <div class="p-3 mb-3 rounded-3" style="background: rgba(99, 102, 241, 0.05); border: 1px dashed var(--tamoza-border);">
                        <div class="fw-semibold small mb-2 text-primary d-flex align-items-center gap-1">
                            <?= Icon::get('shield', 'text-indigo', 14) ?>
                            <span><?= Helpers::e(I18n::t('password_section_title')) ?></span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('password_input_label')) ?></label>
                                <div class="input-group">
                                    <input type="password" name="password" id="createPasswordInput" class="form-control tamoza-input" placeholder="<?= Helpers::e(I18n::t('password_input_placeholder')) ?>">
                                    <button type="button" class="btn btn-tamoza-secondary d-flex align-items-center justify-content-center" id="toggleCreatePassBtn" title="<?= Helpers::e(I18n::t('toggle_password_tooltip')) ?>">
                                        <?= Icon::get('eye', '', 16) ?>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('auth_lang_label')) ?></label>
                                <select name="auth_lang" class="form-select tamoza-input">
                                    <option value="auto"><?= Helpers::e(I18n::t('auth_lang_auto')) ?></option>
                                    <option value="ar"><?= Helpers::e(I18n::t('auth_lang_ar')) ?></option>
                                    <option value="en"><?= Helpers::e(I18n::t('auth_lang_en')) ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Switches: UTM & Active -->
                        <div class="col-12 d-flex gap-4 pt-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="forward_utm" value="1" id="forwardUtmCheck" checked>
                                <label class="form-check-label small" for="forwardUtmCheck">
                                    <?= Helpers::e(I18n::t('forward_utm_label')) ?>
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveCheck" checked>
                                <label class="form-check-label small" for="isActiveCheck">
                                    <?= Helpers::e(I18n::t('is_active_label')) ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-tamoza-secondary" data-bs-dismiss="modal"><?= Helpers::e(I18n::t('cancel_btn')) ?></button>
                    <button type="submit" class="btn btn-tamoza-primary px-4"><?= Helpers::e(I18n::t('save_create_btn')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Link -->
<div class="modal fade" id="editLinkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <form method="POST" action="<?= Helpers::baseUrl('index.php?action=update_link') ?>">
                <?= Helpers::csrfInput() ?>
                <input type="hidden" name="link_id" id="editLinkId" value="">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                        <?= Icon::get('edit', 'text-indigo') ?>
                        <span><?= Helpers::e(I18n::t('edit_modal_title')) ?></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('destination_url')) ?> <span class="text-danger">*</span></label>
                        <input type="url" name="target_url" id="editTargetUrl" class="form-control tamoza-input" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('slug_label')) ?> <span class="text-danger">*</span></label>
                            <input type="text" name="slug" id="editSlug" class="form-control tamoza-input" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('title_label')) ?></label>
                            <input type="text" name="title" id="editTitle" class="form-control tamoza-input">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('redirect_type_label')) ?></label>
                            <select name="redirect_type" id="editRedirectType" class="form-select tamoza-input">
                                <option value="302"><?= Helpers::e(I18n::t('type_temporary')) ?></option>
                                <option value="301"><?= Helpers::e(I18n::t('type_permanent')) ?></option>
                                <option value="2">2 (<?= Helpers::e(I18n::t('type_download')) ?>)</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('click_limit_label')) ?></label>
                            <input type="number" name="click_limit" id="editClickLimit" class="form-control tamoza-input" min="1">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('expires_at_label')) ?></label>
                            <input type="datetime-local" name="expires_at" id="editExpiresAt" class="form-control tamoza-input">
                        </div>
                    </div>

                    <!-- Edit Current Clicks Count (User Requested Feature) -->
                    <div class="p-3 mb-3 rounded-3" style="background: rgba(99, 102, 241, 0.05); border: 1px dashed var(--tamoza-border);">
                        <div class="fw-semibold small mb-2 text-primary d-flex align-items-center gap-1">
                            <?= Icon::get('bar-chart', 'text-indigo', 14) ?>
                            <span><?= Helpers::e(I18n::t('edit_clicks_section_title')) ?></span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('total_clicks_label')) ?></label>
                                <input type="number" name="total_clicks" id="editTotalClicks" class="form-control tamoza-input" min="0">
                                <small class="text-secondary"><?= Helpers::e(I18n::t('edit_clicks_hint')) ?></small>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('unique_clicks_label')) ?></label>
                                <input type="number" name="unique_clicks" id="editUniqueClicks" class="form-control tamoza-input" min="0">
                                <small class="text-secondary"><?= Helpers::e(I18n::t('edit_unique_hint')) ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Password Protection & Authentication Language (User Requested Feature) -->
                    <div class="p-3 mb-3 rounded-3" style="background: rgba(99, 102, 241, 0.05); border: 1px dashed var(--tamoza-border);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold small text-primary d-flex align-items-center gap-1">
                                <?= Icon::get('shield', 'text-indigo', 14) ?>
                                <span><?= Helpers::e(I18n::t('password_section_title')) ?></span>
                            </span>
                            <span id="editPasswordStatusBadge" class="tamoza-badge"></span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('password_input_label')) ?></label>
                                <div class="input-group">
                                    <input type="password" name="password" id="editPasswordInput" class="form-control tamoza-input" placeholder="<?= Helpers::e(I18n::t('password_input_placeholder')) ?>">
                                    <button type="button" class="btn btn-tamoza-secondary d-flex align-items-center justify-content-center" id="toggleEditPassBtn" title="<?= Helpers::e(I18n::t('toggle_password_tooltip')) ?>">
                                        <?= Icon::get('eye', '', 16) ?>
                                    </button>
                                </div>
                                <div class="form-check mt-2" id="editRemovePasswordWrap" style="display: none;">
                                    <input class="form-check-input" type="checkbox" name="remove_password" value="1" id="editRemovePasswordCheck">
                                    <label class="form-check-label small text-danger fw-semibold" for="editRemovePasswordCheck">
                                        <?= Helpers::e(I18n::t('remove_password_label')) ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small"><?= Helpers::e(I18n::t('auth_lang_label')) ?></label>
                                <select name="auth_lang" id="editAuthLang" class="form-select tamoza-input">
                                    <option value="auto"><?= Helpers::e(I18n::t('auth_lang_auto')) ?></option>
                                    <option value="ar"><?= Helpers::e(I18n::t('auth_lang_ar')) ?></option>
                                    <option value="en"><?= Helpers::e(I18n::t('auth_lang_en')) ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Switches: UTM & Active -->
                        <div class="col-12 d-flex gap-4 pt-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="forward_utm" value="1" id="editForwardUtm">
                                <label class="form-check-label small" for="editForwardUtm"><?= Helpers::e(I18n::t('forward_utm_label')) ?></label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editIsActive">
                                <label class="form-check-label small" for="editIsActive"><?= Helpers::e(I18n::t('is_active_label')) ?></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-tamoza-secondary" data-bs-dismiss="modal"><?= Helpers::e(I18n::t('cancel_btn')) ?></button>
                    <button type="submit" class="btn btn-tamoza-primary px-4"><?= Helpers::e(I18n::t('save_changes_btn')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Bind data to Edit Modal including Clicks, Passwords and Auth Language
document.addEventListener('DOMContentLoaded', function () {
    const eyeIconSvg = <?= json_encode(Icon::get('eye', '', 16)) ?>;
    const eyeOffIconSvg = <?= json_encode(Icon::get('eye-off', '', 16)) ?>;

    // 1. Toggle password visibility for Create Modal
    const toggleCreatePassBtn = document.getElementById('toggleCreatePassBtn');
    if (toggleCreatePassBtn) {
        toggleCreatePassBtn.addEventListener('click', function () {
            const inp = document.getElementById('createPasswordInput');
            if (inp.type === 'password') {
                inp.type = 'text';
                this.innerHTML = eyeOffIconSvg;
            } else {
                inp.type = 'password';
                this.innerHTML = eyeIconSvg;
            }
        });
    }

    // 2. Toggle password visibility for Edit Modal
    const toggleEditPassBtn = document.getElementById('toggleEditPassBtn');
    if (toggleEditPassBtn) {
        toggleEditPassBtn.addEventListener('click', function () {
            const inp = document.getElementById('editPasswordInput');
            if (inp.type === 'password') {
                inp.type = 'text';
                this.innerHTML = eyeOffIconSvg;
            } else {
                inp.type = 'password';
                this.innerHTML = eyeIconSvg;
            }
        });
    }

    // 3. Edit Modal Data Binding
    const editModal = document.getElementById('editLinkModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            document.getElementById('editLinkId').value = btn.getAttribute('data-link-id');
            document.getElementById('editSlug').value = btn.getAttribute('data-slug');
            document.getElementById('editTargetUrl').value = btn.getAttribute('data-target-url');
            document.getElementById('editTitle').value = btn.getAttribute('data-title');
            document.getElementById('editRedirectType').value = btn.getAttribute('data-redirect-type');
            document.getElementById('editClickLimit').value = btn.getAttribute('data-click-limit');
            document.getElementById('editTotalClicks').value = btn.getAttribute('data-total-clicks') || '0';
            document.getElementById('editUniqueClicks').value = btn.getAttribute('data-unique-clicks') || '0';
            
            const rawExpires = btn.getAttribute('data-expires-at');
            document.getElementById('editExpiresAt').value = rawExpires ? rawExpires.replace(' ', 'T').substring(0, 16) : '';

            document.getElementById('editForwardUtm').checked = btn.getAttribute('data-forward-utm') === '1';
            document.getElementById('editIsActive').checked = btn.getAttribute('data-is-active') === '1';

            // Password data & status
            const hasPass = btn.getAttribute('data-has-password') === '1';
            const plainPass = btn.getAttribute('data-password') || '';
            const authLang = btn.getAttribute('data-auth-lang') || 'auto';

            const passInp = document.getElementById('editPasswordInput');
            const passBadge = document.getElementById('editPasswordStatusBadge');
            const removeWrap = document.getElementById('editRemovePasswordWrap');
            const removeCheck = document.getElementById('editRemovePasswordCheck');
            const authLangSel = document.getElementById('editAuthLang');

            passInp.value = plainPass;
            passInp.type = 'password';
            if (toggleEditPassBtn) toggleEditPassBtn.innerHTML = eyeIconSvg;
            if (removeCheck) removeCheck.checked = false;

            if (authLangSel) {
                authLangSel.value = authLang;
            }

            if (hasPass) {
                passBadge.className = 'tamoza-badge badge-warning';
                passBadge.textContent = '<?= Helpers::e(I18n::t('password_status_protected')) ?>';
                removeWrap.style.display = 'block';
            } else {
                passBadge.className = 'tamoza-badge badge-muted';
                passBadge.textContent = '<?= Helpers::e(I18n::t('password_status_unprotected')) ?>';
                removeWrap.style.display = 'none';
            }
        });
    }

    // 3. Export Single Link as Image from Table Row
    document.querySelectorAll('[data-export-link-image]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const linkId = this.getAttribute('data-link-id');
            const fallbackData = {
                slug: this.getAttribute('data-slug') || 'link',
                title: this.getAttribute('data-title') || '',
                target_url: this.getAttribute('data-target-url') || '',
                total_clicks: parseInt(this.getAttribute('data-total-clicks') || '0', 10),
                unique_clicks: parseInt(this.getAttribute('data-unique-clicks') || '0', 10),
                conversion_rate: 0,
                is_active: this.getAttribute('data-is-active') === '1',
                countries: [],
                devices: [],
                referrers: []
            };
            if (fallbackData.total_clicks > 0) {
                fallbackData.conversion_rate = Math.round((fallbackData.unique_clicks / fallbackData.total_clicks) * 100);
            }

            // Fetch detailed stats via AJAX endpoint
            fetch('index.php?action=get_link_report_data&id=' + linkId)
                .then(res => res.json())
                .then(data => {
                    if (data && data.success) {
                        window.AtharReportCard?.openSingleLink(data);
                    } else {
                        window.AtharReportCard?.openSingleLink(fallbackData);
                    }
                })
                .catch(() => {
                    window.AtharReportCard?.openSingleLink(fallbackData);
                });
        });
    });

    // 4. Export All Links Overview as Image from Top Dropdown
    document.getElementById('exportLinksOverviewImageBtn')?.addEventListener('click', function () {
        fetch('index.php?action=get_overview_report_data&period=30d')
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    window.AtharReportCard?.openOverview(data);
                }
            })
            .catch(err => console.error(err));
    });
});
</script>
