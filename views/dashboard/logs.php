<?php
declare(strict_types=1);

/**
 * Tamoza UI Standard - Verified: 8pt Logic, Premium Glassmorphism, Centered Layout, Indigo Brand DNA, Dual Language.
 */

use AtharLink\Helpers;
use AtharLink\I18n;
use AtharLink\Icon;

$search = $_GET['q'] ?? '';
$activeFilter = $_GET['filter_action'] ?? '';
$totalLogs = $totalLogs ?? 0;
$pageNum = $pageNum ?? 1;
$totalPages = $totalPages ?? 1;
$logs = $logs ?? [];
$distinctActions = $distinctActions ?? [];

// Helper function for action styling
$getActionBadge = function (string $action): array {
    $actionMap = [
        'login'           => ['class' => 'bg-success-subtle text-success border border-success-subtle', 'icon' => 'log-in'],
        'login_failed'    => ['class' => 'bg-danger-subtle text-danger border border-danger-subtle', 'icon' => 'alert-triangle'],
        'login_locked'    => ['class' => 'bg-warning-subtle text-warning border border-warning-subtle', 'icon' => 'lock'],
        'account_locked'  => ['class' => 'bg-danger-subtle text-danger border border-danger-subtle', 'icon' => 'shield'],
        'logout'          => ['class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'icon' => 'log-out'],
        'recovery_reset'  => ['class' => 'bg-warning-subtle text-warning border border-warning-subtle', 'icon' => 'key'],
        'create_link'     => ['class' => 'bg-success-subtle text-success border border-success-subtle', 'icon' => 'plus'],
        'update_link'     => ['class' => 'bg-info-subtle text-info border border-info-subtle', 'icon' => 'edit'],
        'delete_link'     => ['class' => 'bg-danger-subtle text-danger border border-danger-subtle', 'icon' => 'trash'],
        'toggle_link'     => ['class' => 'bg-warning-subtle text-warning border border-warning-subtle', 'icon' => 'pause'],
        'reset_clicks'    => ['class' => 'bg-warning-subtle text-warning border border-warning-subtle', 'icon' => 'refresh'],
        'save_settings'   => ['class' => 'bg-primary-subtle text-primary border border-primary-subtle', 'icon' => 'settings'],
        'regen_token'     => ['class' => 'bg-info-subtle text-info border border-info-subtle', 'icon' => 'key'],
        'download_backup' => ['class' => 'bg-info-subtle text-info border border-info-subtle', 'icon' => 'download'],
        'restore_backup'  => ['class' => 'bg-purple-subtle text-purple border border-purple-subtle', 'icon' => 'database'],
        'apply_update'    => ['class' => 'bg-success-subtle text-success border border-success-subtle', 'icon' => 'rocket'],
        'rebuild_repair'  => ['class' => 'bg-primary-subtle text-primary border border-primary-subtle', 'icon' => 'refresh'],
        'prune_logs'      => ['class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'icon' => 'filter'],
        'clear_logs'      => ['class' => 'bg-danger-subtle text-danger border border-danger-subtle', 'icon' => 'trash'],
    ];

    return $actionMap[$action] ?? ['class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'icon' => 'activity'];
};
?>

<!-- Page Header & Action Controls -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="fw-bold mb-0"><?= Helpers::e(I18n::t('logs_title')) ?></h2>
            <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2 fw-semibold">
                <?= Helpers::e(I18n::t('logs_count_badge', ['count' => number_format($totalLogs)])) ?>
            </span>
        </div>
        <p class="text-secondary small mb-0"><?= Helpers::e(I18n::t('logs_desc')) ?></p>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <!-- Export Dropdown -->
        <div class="dropdown">
            <button class="btn btn-tamoza-secondary dropdown-toggle d-inline-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-popper-config='{"strategy":"fixed"}'>
                <?= Icon::get('download', '', 16) ?>
                <span><?= Helpers::e(I18n::t('export_data')) ?></span>
            </button>
            <ul class="dropdown-menu shadow border-0 py-2">
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= Helpers::baseUrl('index.php?action=export_logs&format=csv') ?>"><?= Icon::get('download', '', 14) ?> <?= Helpers::e(I18n::t('export_logs_csv')) ?></a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= Helpers::baseUrl('index.php?action=export_logs&format=json') ?>"><?= Icon::get('download', '', 14) ?> <?= Helpers::e(I18n::t('export_logs_json')) ?></a></li>
            </ul>
        </div>

        <!-- Prune Logs Button -->
        <button type="button" class="btn btn-tamoza-secondary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#pruneLogsModal">
            <?= Icon::get('filter', '', 16) ?>
            <span><?= Helpers::e(I18n::t('prune_logs_btn')) ?></span>
        </button>

        <!-- Clear All Logs Button -->
        <button type="button" class="btn btn-tamoza-subtle text-danger d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
            <?= Icon::get('trash', '', 16) ?>
            <span class="d-none d-sm-inline"><?= Helpers::e(I18n::t('clear_all_logs_btn')) ?></span>
        </button>
    </div>
</div>

<!-- Filters and Search Toolbar (Tamoza Glassmorphism Card) -->
<div class="tamoza-card p-3 mb-4">
    <form method="GET" action="<?= Helpers::baseUrl('index.php') ?>" class="row g-3 align-items-center">
        <input type="hidden" name="page" value="logs">
        
        <!-- Search Input -->
        <div class="col-12 col-md-5 col-lg-5">
            <div class="tamoza-search-wrapper">
                <span class="tamoza-search-icon"><?= Icon::get('search', '', 16) ?></span>
                <input type="text" name="q" class="tamoza-search-input" placeholder="<?= Helpers::e(I18n::t('search_placeholder')) ?>" value="<?= Helpers::e($search) ?>">
            </div>
        </div>

        <!-- Action Filter Dropdown -->
        <div class="col-12 col-md-4 col-lg-4">
            <select name="filter_action" class="form-select tamoza-input">
                <option value=""><?= Helpers::e(I18n::t('action_all')) ?></option>
                <?php
                $knownActions = [
                    'login', 'login_failed', 'login_locked', 'account_locked', 'logout', 'recovery_reset',
                    'create_link', 'update_link', 'delete_link', 'toggle_link', 'reset_clicks',
                    'save_settings', 'regen_token', 'download_backup', 'restore_backup',
                    'apply_update', 'rebuild_repair', 'prune_logs', 'clear_logs'
                ];
                $allActions = array_unique(array_merge($knownActions, $distinctActions));
                sort($allActions);
                foreach ($allActions as $act):
                    $actLabel = I18n::t('action_' . $act);
                    if ($actLabel === 'action_' . $act) {
                        $actLabel = ucwords(str_replace('_', ' ', $act));
                    }
                ?>
                    <option value="<?= Helpers::e($act) ?>" <?= $activeFilter === $act ? 'selected' : '' ?>>
                        <?= Helpers::e($actLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filter & Reset Actions -->
        <div class="col-12 col-md-3 col-lg-3 d-flex gap-2">
            <button type="submit" class="btn btn-tamoza-secondary flex-grow-1"><?= Helpers::e(I18n::t('filter_btn')) ?></button>
            <?php if ($search !== '' || $activeFilter !== ''): ?>
                <a href="<?= Helpers::baseUrl('index.php?page=logs') ?>" class="btn btn-tamoza-subtle px-3"><?= Helpers::e(I18n::t('cancel_btn')) ?></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Audit Logs Table (Tamoza Glass Container) -->
<div class="tamoza-table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="min-width: 140px;"><?= Helpers::e(I18n::t('col_log_time')) ?></th>
                    <th style="min-width: 120px;"><?= Helpers::e(I18n::t('col_log_admin')) ?></th>
                    <th style="min-width: 140px;"><?= Helpers::e(I18n::t('col_log_action')) ?></th>
                    <th><?= Helpers::e(I18n::t('col_log_description')) ?></th>
                    <th style="min-width: 150px;"><?= Helpers::e(I18n::t('col_log_ip')) ?></th>
                    <th class="text-end" style="min-width: 100px;"><?= Helpers::e(I18n::t('col_log_details_btn')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-secondary">
                            <div class="display-6 mb-2 text-indigo"><?= Icon::get('activity', '', 40) ?></div>
                            <p class="mb-0"><?= Helpers::e(I18n::t('no_logs_found')) ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): 
                        $badgeInfo = $getActionBadge($log['action']);
                        $actLabel = I18n::t('action_' . $log['action']);
                        if ($actLabel === 'action_' . $log['action']) {
                            $actLabel = ucwords(str_replace('_', ' ', $log['action']));
                        }
                        $hasDetails = !empty($log['details']) && $log['details'] !== 'null';
                    ?>
                        <tr>
                            <!-- Timestamp -->
                            <td>
                                <div class="fw-semibold small"><?= Helpers::e(date('Y-m-d', strtotime($log['created_at']))) ?></div>
                                <div class="text-secondary smaller font-monospace"><?= Helpers::e(date('H:i:s', strtotime($log['created_at']))) ?></div>
                            </td>

                            <!-- Admin Username -->
                            <td>
                                <span class="d-inline-flex align-items-center gap-1 small fw-semibold text-secondary">
                                    <?= Icon::get('user', '', 14) ?>
                                    <span><?= Helpers::e($log['username'] ?: I18n::t('unknown_user')) ?></span>
                                </span>
                            </td>

                            <!-- Action Badge -->
                            <td>
                                <span class="badge rounded-pill d-inline-flex align-items-center gap-1 px-2.5 py-1.5 small <?= Helpers::e($badgeInfo['class']) ?>">
                                    <?= Icon::get($badgeInfo['icon'], '', 13) ?>
                                    <span><?= Helpers::e($actLabel) ?></span>
                                </span>
                            </td>

                            <!-- Description & In-line Change Badges -->
                            <td>
                                <?php
                                $detailsArr = null;
                                if (!empty($log['details'])) {
                                    $detailsArr = json_decode($log['details'], true);
                                }
                                ?>
                                <div class="small fw-semibold text-body mb-1">
                                    <?= Helpers::e($log['description']) ?>
                                </div>
                                <?php if (!empty($detailsArr['changes']) && is_array($detailsArr['changes'])): ?>
                                    <div class="d-flex flex-wrap gap-1.5 mt-1.5">
                                        <?php foreach ($detailsArr['changes'] as $fKey => $chg): ?>
                                            <span class="badge bg-body-tertiary border border-secondary-subtle text-secondary py-1 px-2 d-inline-flex align-items-center gap-1.5 fw-normal">
                                                <strong class="text-body"><?= Helpers::e(I18n::t('field_' . $fKey) !== 'field_' . $fKey ? I18n::t('field_' . $fKey) : ($chg['field'] ?? $fKey)) ?>:</strong>
                                                <span class="text-danger text-decoration-line-through font-monospace smaller text-truncate" style="max-width: 140px;" title="<?= Helpers::e((string)($chg['old'] ?? '')) ?>"><?= Helpers::e((string)($chg['old'] ?? '—')) ?></span>
                                                <span class="text-muted">➔</span>
                                                <span class="text-success fw-semibold font-monospace smaller text-truncate" style="max-width: 140px;" title="<?= Helpers::e((string)($chg['new'] ?? '')) ?>"><?= Helpers::e((string)($chg['new'] ?? '—')) ?></span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif (!empty($detailsArr['target_url'])): ?>
                                    <div class="small text-secondary mt-1 text-truncate" style="max-width: 380px;">
                                        <span class="badge bg-secondary-subtle text-secondary me-1"><?= Helpers::e(I18n::t('field_target_url')) ?></span>
                                        <span class="font-monospace smaller text-body"><?= Helpers::e($detailsArr['target_url']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- IP Address & User Agent -->
                            <td>
                                <div class="small font-monospace text-secondary mb-0.5">
                                    <?= Helpers::e($log['ip_address'] ?: '127.0.0.1') ?>
                                </div>
                                <?php if (!empty($log['user_agent'])): ?>
                                    <div class="smaller text-muted text-truncate" style="max-width: 160px;" title="<?= Helpers::e($log['user_agent']) ?>">
                                        <?= Helpers::e($log['user_agent']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Details Modal Trigger -->
                            <td class="text-end">
                                <?php if ($hasDetails): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-tamoza-subtle py-1 px-2.5 view-log-details-btn"
                                            data-id="<?= Helpers::e((string)$log['id']) ?>"
                                            data-action="<?= Helpers::e($actLabel) ?>"
                                            data-time="<?= Helpers::e($log['created_at']) ?>"
                                            data-user="<?= Helpers::e($log['username'] ?? '') ?>"
                                            data-desc="<?= Helpers::e($log['description']) ?>"
                                            data-ip="<?= Helpers::e($log['ip_address'] ?? '') ?>"
                                            data-details='<?= htmlspecialchars((string)$log['details'], ENT_QUOTES, 'UTF-8') ?>'>
                                        <?= Icon::get('eye', 'me-1', 13) ?>
                                        <span class="smaller"><?= Helpers::e(I18n::t('col_log_details_btn')) ?></span>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted smaller">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center p-3 border-top border-secondary-subtle">
            <span class="text-secondary small">
                <?= Helpers::e(I18n::t('pagination_showing', ['from' => (($pageNum - 1) * 50) + 1, 'to' => min($totalLogs, $pageNum * 50), 'total' => $totalLogs])) ?>
            </span>
            <ul class="pagination pagination-sm mb-0">
                <?php if ($pageNum > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= Helpers::baseUrl('index.php?page=logs&p=' . ($pageNum - 1) . ($search !== '' ? '&q=' . urlencode($search) : '') . ($activeFilter !== '' ? '&filter_action=' . urlencode($activeFilter) : '')) ?>">
                            <?= Icon::get('arrow-right', '', 14) ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php 
                $startPage = max(1, $pageNum - 2);
                $endPage = min($totalPages, $pageNum + 2);
                for ($p = $startPage; $p <= $endPage; $p++): 
                ?>
                    <li class="page-item <?= $p === $pageNum ? 'active' : '' ?>">
                        <a class="page-link" href="<?= Helpers::baseUrl('index.php?page=logs&p=' . $p . ($search !== '' ? '&q=' . urlencode($search) : '') . ($activeFilter !== '' ? '&filter_action=' . urlencode($activeFilter) : '')) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($pageNum < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= Helpers::baseUrl('index.php?page=logs&p=' . ($pageNum + 1) . ($search !== '' ? '&q=' . urlencode($search) : '') . ($activeFilter !== '' ? '&filter_action=' . urlencode($activeFilter) : '')) ?>">
                            <?= Icon::get('arrow-left', '', 14) ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- Modal 1: Event Details & Visual Diff Comparison Viewer -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content tamoza-card border-0 shadow-lg">
            <div class="modal-header border-bottom border-secondary-subtle">
                <div class="d-flex align-items-center gap-2">
                    <div class="text-primary"><?= Icon::get('activity', '', 20) ?></div>
                    <h5 class="modal-title fw-bold mb-0" id="logDetailsModalTitle"><?= Helpers::e(I18n::t('log_details_title')) ?></h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Event Header Info Card -->
                <div class="p-3 rounded-3 bg-body-tertiary border border-secondary-subtle mb-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-md-6">
                            <div class="text-secondary smaller mb-1"><?= Helpers::e(I18n::t('col_log_description')) ?></div>
                            <div class="fw-semibold text-body" id="modalLogDesc"></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-secondary smaller mb-1"><?= Helpers::e(I18n::t('col_log_admin')) ?></div>
                            <div class="small fw-semibold" id="modalLogUser"></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-secondary smaller mb-1"><?= Helpers::e(I18n::t('col_log_time')) ?></div>
                            <div class="small font-monospace text-body" id="modalLogTime"></div>
                        </div>
                    </div>
                </div>

                <!-- Visual Diff Section: Changes (Before vs After) -->
                <div id="modalChangesSection" class="mb-3 d-none">
                    <div class="d-flex align-items-center gap-2 text-primary smaller fw-bold mb-2">
                        <?= Icon::get('refresh', 'text-primary', 15) ?>
                        <span><?= Helpers::e(I18n::t('diff_changes_title')) ?></span>
                    </div>
                    <div id="modalChangesContainer" class="d-flex flex-column gap-2"></div>
                </div>

                <!-- Info Cards for Non-diff fields (Target URL, Slug, etc.) -->
                <div id="modalSummarySection" class="mb-3 d-none">
                    <div class="d-flex align-items-center gap-2 text-secondary smaller fw-bold mb-2">
                        <?= Icon::get('link-2', 'text-primary', 15) ?>
                        <span><?= Helpers::e(I18n::t('col_log_description')) ?></span>
                    </div>
                    <div id="modalSummaryContainer" class="p-3 rounded-3 bg-body-tertiary border border-secondary-subtle"></div>
                </div>

                <!-- Collapsible Raw JSON / Technical Metadata -->
                <div class="border-top border-secondary-subtle pt-3">
                    <a class="d-flex justify-content-between align-items-center text-decoration-none small text-secondary fw-semibold py-1" data-bs-toggle="collapse" href="#modalJsonCollapse" role="button">
                        <span class="d-flex align-items-center gap-1.5">
                            <?= Icon::get('layers', '', 14) ?>
                            <span><?= Helpers::e(I18n::t('raw_json_toggle')) ?></span>
                        </span>
                        <span class="smaller font-monospace text-muted">▼</span>
                    </a>
                    <div class="collapse mt-2" id="modalJsonCollapse">
                        <pre class="p-3 rounded-3 bg-body-tertiary border border-secondary-subtle smaller mb-0 font-monospace text-wrap" id="modalLogJson" style="max-height: 200px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary-subtle">
                <button type="button" class="btn btn-tamoza-secondary px-4" data-bs-dismiss="modal"><?= Helpers::e(I18n::t('close_btn')) ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Prune Logs Modal -->
<div class="modal fade" id="pruneLogsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content tamoza-card border-0 shadow-lg">
            <form method="POST" action="<?= Helpers::baseUrl('index.php?action=prune_logs') ?>">
                <?= Helpers::csrfInput() ?>
                <div class="modal-header border-bottom border-secondary-subtle">
                    <div class="d-flex align-items-center gap-2">
                        <div class="text-warning"><?= Icon::get('filter', '', 20) ?></div>
                        <h5 class="modal-title fw-bold mb-0"><?= Helpers::e(I18n::t('prune_modal_title')) ?></h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-3"><?= Helpers::e(I18n::t('prune_modal_desc')) ?></p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><?= Helpers::e(I18n::t('prune_days_label')) ?></label>
                        <select name="days" class="form-select tamoza-input">
                            <option value="30"><?= Helpers::e(I18n::t('prune_days_30')) ?></option>
                            <option value="60" selected><?= Helpers::e(I18n::t('prune_days_60')) ?></option>
                            <option value="90"><?= Helpers::e(I18n::t('prune_days_90')) ?></option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary-subtle">
                    <button type="button" class="btn btn-tamoza-secondary" data-bs-dismiss="modal"><?= Helpers::e(I18n::t('cancel_btn')) ?></button>
                    <button type="submit" class="btn btn-tamoza-primary px-4"><?= Helpers::e(I18n::t('prune_confirm_btn')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Clear All Logs Confirmation Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content tamoza-card border-0 shadow-lg">
            <form method="POST" action="<?= Helpers::baseUrl('index.php?action=clear_logs') ?>">
                <?= Helpers::csrfInput() ?>
                <div class="modal-header border-bottom border-secondary-subtle">
                    <div class="d-flex align-items-center gap-2">
                        <div class="text-danger"><?= Icon::get('alert-triangle', '', 20) ?></div>
                        <h5 class="modal-title fw-bold mb-0 text-danger"><?= Helpers::e(I18n::t('clear_all_logs_btn')) ?></h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-0"><?= Helpers::e(I18n::t('clear_logs_confirm')) ?></p>
                </div>
                <div class="modal-footer border-top border-secondary-subtle">
                    <button type="button" class="btn btn-tamoza-secondary" data-bs-dismiss="modal"><?= Helpers::e(I18n::t('cancel_btn')) ?></button>
                    <button type="submit" class="btn btn-danger px-4"><?= Helpers::e(I18n::t('delete_confirm_btn')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const detailsModalElement = document.getElementById('logDetailsModal');
    if (!detailsModalElement) return;

    const detailsModal = new bootstrap.Modal(detailsModalElement);
    const modalDesc = document.getElementById('modalLogDesc');
    const modalUser = document.getElementById('modalLogUser');
    const modalTime = document.getElementById('modalLogTime');
    const modalJson = document.getElementById('modalLogJson');
    const changesSection = document.getElementById('modalChangesSection');
    const changesContainer = document.getElementById('modalChangesContainer');
    const summarySection = document.getElementById('modalSummarySection');
    const summaryContainer = document.getElementById('modalSummaryContainer');

    const i18n = {
        before: <?= json_encode(I18n::t('diff_before')) ?>,
        after: <?= json_encode(I18n::t('diff_after')) ?>,
        field: <?= json_encode(I18n::t('diff_field')) ?>,
        targetUrl: <?= json_encode(I18n::t('field_target_url')) ?>,
        slug: <?= json_encode(I18n::t('field_slug')) ?>,
        title: <?= json_encode(I18n::t('field_title')) ?>,
        redirectType: <?= json_encode(I18n::t('field_redirect_type')) ?>,
        status: <?= json_encode(I18n::t('field_status')) ?>,
        password: <?= json_encode(I18n::t('field_password')) ?>,
        clickLimit: <?= json_encode(I18n::t('field_click_limit')) ?>,
        expiresAt: <?= json_encode(I18n::t('field_expires_at')) ?>
    };

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function getFieldTitle(key, defaultTitle) {
        const keyMap = {
            'target_url': i18n.targetUrl,
            'slug': i18n.slug,
            'title': i18n.title,
            'redirect_type': i18n.redirectType,
            'status': i18n.status,
            'is_active': i18n.status,
            'password': i18n.password,
            'click_limit': i18n.clickLimit,
            'expires_at': i18n.expiresAt
        };
        return keyMap[key] || defaultTitle || key;
    }

    document.querySelectorAll('.view-log-details-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const desc = this.getAttribute('data-desc') || '';
            const user = this.getAttribute('data-user') || '';
            const time = this.getAttribute('data-time') || '';
            const rawDetails = this.getAttribute('data-details') || '';

            modalDesc.textContent = desc;
            modalUser.textContent = user;
            modalTime.textContent = time;

            changesContainer.innerHTML = '';
            summaryContainer.innerHTML = '';
            changesSection.classList.add('d-none');
            summarySection.classList.add('d-none');

            let parsed = null;
            try {
                parsed = JSON.parse(rawDetails);
                modalJson.textContent = JSON.stringify(parsed, null, 2);
            } catch (e) {
                modalJson.textContent = rawDetails;
            }

            if (parsed && typeof parsed === 'object') {
                let changesList = [];

                // 1. Check if structured 'changes' array/object exists
                if (parsed.changes && typeof parsed.changes === 'object') {
                    for (const [k, v] of Object.entries(parsed.changes)) {
                        changesList.push({
                            fieldKey: k,
                            label: getFieldTitle(k, v.field),
                            oldVal: v.old,
                            newVal: v.new
                        });
                    }
                } else if (parsed.old && parsed.new) {
                    // Compare old and new objects dynamically
                    const allKeys = new Set([...Object.keys(parsed.old), ...Object.keys(parsed.new)]);
                    allKeys.forEach(k => {
                        if (String(parsed.old[k] ?? '') !== String(parsed.new[k] ?? '')) {
                            changesList.push({
                                fieldKey: k,
                                label: getFieldTitle(k),
                                oldVal: parsed.old[k],
                                newVal: parsed.new[k]
                            });
                        }
                    });
                }

                // Render visual diff cards
                if (changesList.length > 0) {
                    changesSection.classList.remove('d-none');
                    changesList.forEach(item => {
                        const card = document.createElement('div');
                        card.className = 'p-3 rounded-3 bg-body-tertiary border border-secondary-subtle';
                        card.innerHTML = `
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-secondary-subtle">
                                <span class="fw-bold small text-body">${escapeHtml(item.label)}</span>
                            </div>
                            <div class="row g-2 align-items-stretch">
                                <div class="col-12 col-md-5">
                                    <div class="smaller text-secondary fw-semibold mb-1">${escapeHtml(i18n.before)}:</div>
                                    <div class="p-2.5 rounded-2 bg-danger-subtle text-danger font-monospace smaller text-break border border-danger-subtle h-100 d-flex align-items-center">
                                        <span class="text-decoration-line-through">${escapeHtml(item.oldVal !== null && item.oldVal !== undefined && item.oldVal !== '' ? String(item.oldVal) : '—')}</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-2 d-flex align-items-center justify-content-center text-secondary py-1">
                                    <span class="fs-4">➔</span>
                                </div>
                                <div class="col-12 col-md-5">
                                    <div class="smaller text-success fw-semibold mb-1">${escapeHtml(i18n.after)}:</div>
                                    <div class="p-2.5 rounded-2 bg-success-subtle text-success font-monospace smaller text-break border border-success-subtle h-100 d-flex align-items-center fw-bold">
                                        <span>${escapeHtml(item.newVal !== null && item.newVal !== undefined && item.newVal !== '' ? String(item.newVal) : '—')}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                        changesContainer.appendChild(card);
                    });
                } else if (parsed.target_url) {
                    // For single target_url records (e.g. create_link, delete_link, or legacy logs)
                    summarySection.classList.remove('d-none');
                    summaryContainer.innerHTML = `
                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-sm-4">
                                <span class="text-secondary smaller fw-semibold d-block">${escapeHtml(i18n.slug)}</span>
                                <span class="badge bg-primary-subtle text-primary font-monospace mt-1 px-2.5 py-1.5">${escapeHtml(parsed.slug || '—')}</span>
                            </div>
                            <div class="col-12 col-sm-8">
                                <span class="text-secondary smaller fw-semibold d-block">${escapeHtml(i18n.targetUrl)}</span>
                                <a href="${escapeHtml(parsed.target_url)}" target="_blank" class="font-monospace small text-primary text-break text-decoration-none d-inline-block mt-1">
                                    ${escapeHtml(parsed.target_url)}
                                </a>
                            </div>
                        </div>
                    `;
                }
            }

            detailsModal.show();
        });
    });
});
</script>
