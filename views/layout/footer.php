<?php
declare(strict_types=1);

/**
 * Tamoza UI Standard - Verified: Structured Footer (Split Layout), Centered Container, Dual Language Support.
 */

use AtharLink\Helpers;
use AtharLink\I18n;
use AtharLink\Icon;
?>
</main>

<!-- Structured Footer (Clean Layout: Brand, Center Copyright & Social Links) -->
<footer class="tamoza-footer">
    <div class="tamoza-footer-inner">
        <!-- Brand / Identity -->
        <div class="tamoza-footer-brand">
            <?= Icon::get('link', 'text-primary', 18) ?>
            <span><?= APP_NAME ?></span>
        </div>

        <!-- Center Copyright -->
        <div class="tamoza-footer-copy">
            <?= str_replace('Tamoza.net', '<a href="https://tamoza.net" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-primary fw-medium">Tamoza.net</a>', Helpers::e(I18n::t('copyright', ['year' => date('Y')]))) ?>
        </div>

        <!-- Social Media Links (Bottom Right) -->
        <div class="tamoza-footer-social">
            <a href="https://github.com/Tamoza4/AtharLink" target="_blank" rel="noopener noreferrer" class="tamoza-footer-social-link" title="GitHub" aria-label="GitHub">
                <?= Icon::get('github', '', 18) ?>
            </a>
            <a href="https://x.com/Tamoza04" target="_blank" rel="noopener noreferrer" class="tamoza-footer-social-link" title="X" aria-label="X">
                <?= Icon::get('x', '', 16) ?>
            </a>
        </div>
    </div>
</footer>

<!-- QR Code Modal (Tamoza Glassmorphism 24px Radius) -->
<div class="modal fade" id="qrCodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="qrModalTitle"><?= Helpers::e(I18n::t('qr_code_title')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="d-flex justify-content-center align-items-center mb-3 w-100">
                    <div id="qrModalCanvas" class="text-center d-flex justify-content-center align-items-center mx-auto"></div>
                </div>
                <p class="text-secondary small mb-0"><?= Helpers::e(I18n::t('qr_scan_hint')) ?></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" id="downloadQrPngBtn" class="btn btn-tamoza-primary px-4 d-inline-flex align-items-center gap-2">
                    <?= Icon::get('download', '', 16) ?> <?= Helpers::e(I18n::t('download_png')) ?>
                </button>
                <button type="button" class="btn btn-tamoza-secondary px-3" data-bs-dismiss="modal"><?= Helpers::e(I18n::t('cancel_btn')) ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Embed Live Click Counter (Tamoza Glassmorphism 24px Radius) -->
<div class="modal fade" id="embedCounterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow border-0" style="background: #161A23 !important; border: 1px solid var(--tamoza-border); border-radius: var(--tamoza-radius-lg);">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded-3 text-indigo" style="background: rgba(99, 102, 241, 0.1);">
                        <?= Icon::get('code', 'text-indigo', 20) ?>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white"><?= Helpers::e(I18n::t('embed_counter_modal_title')) ?></h5>
                        <span class="text-secondary small font-monospace" id="embedModalSlugTitle">/slug</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body py-4">
                <!-- 1. Platform / Format Selector Pills -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary mb-2"><?= Helpers::e(I18n::t('embed_format_label')) ?></label>
                    <div class="d-flex flex-wrap gap-2" id="embedPlatformPills">
                        <button type="button" class="btn btn-sm btn-tamoza-primary px-3 rounded-pill active" data-format="html">
                            🌐 <?= Helpers::e(I18n::t('embed_format_html')) ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-tamoza-subtle px-3 rounded-pill" data-format="wp">
                            ⚡ <?= Helpers::e(I18n::t('embed_format_wp')) ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-tamoza-subtle px-3 rounded-pill" data-format="php">
                            🐘 <?= Helpers::e(I18n::t('embed_format_php')) ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-tamoza-subtle px-3 rounded-pill" data-format="badge">
                            🖼️ <?= Helpers::e(I18n::t('embed_format_badge')) ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-tamoza-subtle px-3 rounded-pill" data-format="api">
                            📡 <?= Helpers::e(I18n::t('embed_format_api')) ?>
                        </button>
                    </div>
                </div>

                <!-- 2. Metric Options (Unique vs Total) & Badge Controls -->
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6" id="embedMetricCol">
                        <label class="form-label small fw-semibold text-secondary mb-2"><?= Helpers::e(I18n::t('embed_type_label')) ?></label>
                        <div class="d-flex gap-2">
                            <label class="d-flex align-items-center gap-2 m-0 p-2 rounded-3 border w-100" for="embedMetricUnique" style="border-color: var(--tamoza-border) !important; background: rgba(0,0,0,0.2); cursor: pointer;">
                                <input class="form-check-input m-0 flex-shrink-0" type="radio" name="embed_metric_type" id="embedMetricUnique" value="unique" checked style="float: none; margin: 0 !important;">
                                <span class="small fw-semibold text-white">
                                    <?= Helpers::e(I18n::t('embed_type_unique')) ?>
                                </span>
                            </label>
                            <label class="d-flex align-items-center gap-2 m-0 p-2 rounded-3 border w-100" for="embedMetricAll" style="border-color: var(--tamoza-border) !important; background: rgba(0,0,0,0.2); cursor: pointer;">
                                <input class="form-check-input m-0 flex-shrink-0" type="radio" name="embed_metric_type" id="embedMetricAll" value="all" style="float: none; margin: 0 !important;">
                                <span class="small fw-semibold text-white">
                                    <?= Helpers::e(I18n::t('embed_type_all')) ?>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Badge Specific Controls (Hidden unless Badge format is chosen) -->
                    <div class="col-12 col-md-6 d-none" id="embedBadgeControls">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary mb-1"><?= Helpers::e(I18n::t('embed_badge_label_title')) ?></label>
                                <input type="text" id="embedBadgeLabelInput" class="form-control tamoza-input form-control-sm" value="clicks">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary mb-1"><?= Helpers::e(I18n::t('embed_badge_color_title')) ?></label>
                                <select id="embedBadgeColorSelect" class="form-select tamoza-input form-select-sm">
                                    <?php if (I18n::getLang() === 'ar'): ?>
                                        <option value="indigo" selected>نيلي (Indigo)</option>
                                        <option value="emerald">أخضر زمردي (Emerald)</option>
                                        <option value="blue">أزرق (Blue)</option>
                                        <option value="warning">برتقالي (Amber)</option>
                                        <option value="red">أحمر (Red)</option>
                                        <option value="purple">أرجواني (Purple)</option>
                                        <option value="dark">داكن (Dark)</option>
                                    <?php else: ?>
                                        <option value="indigo" selected>Indigo</option>
                                        <option value="emerald">Emerald</option>
                                        <option value="blue">Blue</option>
                                        <option value="warning">Amber</option>
                                        <option value="red">Red</option>
                                        <option value="purple">Purple</option>
                                        <option value="dark">Dark</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Live Preview Card -->
                <div class="p-3 rounded-4 mb-3" style="background: rgba(99, 102, 241, 0.04); border: 1px solid var(--tamoza-border);">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-semibold d-flex align-items-center gap-1">
                            <?= Icon::get('eye', 'text-indigo', 14) ?>
                            <span><?= Helpers::e(I18n::t('embed_preview_title')) ?></span>
                        </span>
                        <span id="embedPreviewBadgeTag" class="tamoza-badge badge-indigo small"><?= Helpers::e(I18n::t('embed_preview_tag_unique')) ?></span>
                    </div>

                    <!-- Dynamic Preview Render -->
                    <div id="embedPreviewDisplay" class="d-flex align-items-center gap-2 py-1">
                        <span class="fs-4 fw-bold font-monospace text-primary" id="embedPreviewNumber">0</span>
                        <span class="text-secondary small" id="embedPreviewNumberUnit"><?= I18n::t('col_clicks') ?></span>
                    </div>
                    <div id="embedPreviewBadgeWrapper" class="d-none py-1">
                        <img id="embedPreviewBadgeImg" src="" alt="Badge Preview" style="height: 22px; max-width: 100%;">
                    </div>
                </div>

                <!-- 4. Generated Snippet Box -->
                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-secondary small fw-semibold"><?= Helpers::e(I18n::t('embed_code_box_title')) ?></span>
                        <button type="button" class="btn btn-link btn-sm text-primary text-decoration-none p-0 d-inline-flex align-items-center gap-1" id="embedCopyBtnTop">
                            <?= Icon::get('copy', '', 14) ?>
                            <span id="embedCopyTextTop"><?= Helpers::e(I18n::t('embed_copy_code_btn')) ?></span>
                        </button>
                    </div>
                    <div class="position-relative">
                        <textarea id="embedCodeOutput" class="form-control font-monospace p-3 text-start small" dir="ltr" rows="4" readonly style="background: #0B0E14 !important; border: 1px solid var(--tamoza-border); color: #A5B4FC; resize: none; font-size: 12.5px; border-radius: 12px;"></textarea>
                    </div>
                </div>

                <!-- 5. Dynamic Helpful Instructions -->
                <div class="p-3 rounded-3 small text-secondary d-flex align-items-start gap-2" style="background: rgba(0, 0, 0, 0.25); border: 1px solid var(--tamoza-border);">
                    <span class="text-indigo mt-0"><?= Icon::get('info', 'text-indigo', 16) ?></span>
                    <span id="embedInstructionText"><?= Helpers::e(I18n::t('embed_instructions_html')) ?></span>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-tamoza-subtle btn-sm px-3" data-bs-dismiss="modal"><?= Helpers::e(I18n::t('close_btn')) ?></button>
                <button type="button" class="btn btn-tamoza-primary btn-sm px-4 d-inline-flex align-items-center gap-2" id="embedCopyBtnMain">
                    <span id="embedCopyIconMain"><?= Icon::get('copy', '', 14) ?></span>
                    <span id="embedCopyTextMain"><?= Helpers::e(I18n::t('embed_copy_code_btn')) ?></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <form method="POST" action="<?= Helpers::baseUrl('index.php?action=delete_link') ?>">
                <?= Helpers::csrfInput() ?>
                <input type="hidden" name="link_id" id="deleteLinkId" value="">
                <div class="modal-header">
                    <h5 class="modal-title text-danger fw-bold d-flex align-items-center gap-2">
                        <?= Icon::get('alert-triangle', 'text-danger', 20) ?> <?= Helpers::e(I18n::t('delete_confirm_title')) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="mb-2"><?= Helpers::e(I18n::t('delete_confirm_question_prefix')) ?> <strong id="deleteLinkSlug" class="text-primary"></strong><?= Helpers::e(I18n::t('question_mark')) ?></p>
                    <div class="alert alert-danger py-2 small mb-0 rounded-3">
                        <?= Helpers::e(I18n::t('delete_warning')) ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-tamoza-secondary" data-bs-dismiss="modal"><?= Helpers::e(I18n::t('cancel_btn')) ?></button>
                    <button type="submit" class="btn btn-danger px-4 rounded-4 fw-bold"><?= Helpers::e(I18n::t('confirm_delete_btn')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Clicks Confirmation Modal -->
<div class="modal fade" id="resetClicksModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <form method="POST" action="<?= Helpers::baseUrl('index.php?action=reset_clicks') ?>">
                <?= Helpers::csrfInput() ?>
                <input type="hidden" name="link_id" id="resetLinkId" value="">
                <div class="modal-header">
                    <h5 class="modal-title text-warning fw-bold d-flex align-items-center gap-2">
                        <?= Icon::get('refresh', 'text-warning', 20) ?> <?= Helpers::e(I18n::t('reset_confirm_title')) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <p class="mb-2"><?= Helpers::e(I18n::t('reset_confirm_question_prefix')) ?> <strong id="resetLinkSlug" class="text-primary"></strong><?= Helpers::e(I18n::t('question_mark')) ?></p>
                    <div class="alert alert-warning py-2 small mb-0 rounded-3">
                        <?= Helpers::e(I18n::t('reset_warning')) ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-tamoza-secondary" data-bs-dismiss="modal"><?= Helpers::e(I18n::t('cancel_btn')) ?></button>
                    <button type="submit" class="btn btn-warning px-4 rounded-4 fw-bold text-dark"><?= Helpers::e(I18n::t('confirm_reset_btn')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Report Image Preview & Download Modal -->
<div class="modal fade" id="reportImageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="reportImageModalTitle"><?= Helpers::e(I18n::t('report_image_modal_title')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="p-2 rounded-4 mb-3" style="background: rgba(0, 0, 0, 0.3); border: 1px solid var(--tamoza-border); overflow-x: auto;">
                    <canvas id="reportCardCanvas" style="max-width: 100%; height: auto; border-radius: 12px; display: block; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.4);"></canvas>
                </div>
                <p class="text-secondary small mb-0"><?= Helpers::e(I18n::t('report_image_hint')) ?></p>
            </div>
            <div class="modal-footer justify-content-center gap-2">
                <button type="button" id="downloadReportImageBtn" class="btn btn-tamoza-primary px-4 d-inline-flex align-items-center gap-2">
                    <?= Icon::get('download', '', 16) ?> <?= Helpers::e(I18n::t('download_image_png')) ?>
                </button>
                <button type="button" id="copyReportImageBtn" class="btn btn-tamoza-secondary px-3 d-inline-flex align-items-center gap-2">
                    <?= Icon::get('copy', '', 16) ?> <?= Helpers::e(I18n::t('copy_image_btn')) ?>
                </button>
                <button type="button" class="btn btn-tamoza-subtle px-3" data-bs-dismiss="modal">
                    <?= Helpers::e(I18n::t('close_btn')) ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="<?= Helpers::baseUrl('assets/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= Helpers::baseUrl('assets/js/chart.min.js') ?>"></script>
<script src="<?= Helpers::baseUrl('assets/js/qrcode.min.js') ?>"></script>
<script src="<?= Helpers::baseUrl('assets/js/app.js') ?>"></script>
<script src="<?= Helpers::baseUrl('assets/js/report-card.js') ?>"></script>

<script>
// Modal Data Attribute Binder
document.addEventListener('DOMContentLoaded', function () {
    const deleteModal = document.getElementById('deleteConfirmModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            document.getElementById('deleteLinkId').value = btn.getAttribute('data-link-id');
            document.getElementById('deleteLinkSlug').textContent = '/' + btn.getAttribute('data-link-slug');
        });
    }

    const resetModal = document.getElementById('resetClicksModal');
    if (resetModal) {
        resetModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            document.getElementById('resetLinkId').value = btn.getAttribute('data-link-id');
            document.getElementById('resetLinkSlug').textContent = '/' + btn.getAttribute('data-link-slug');
        });
    }

    // Embed Counter Modal Interactive Controller
    const embedModalEl = document.getElementById('embedCounterModal');
    if (embedModalEl) {
        let currentSlug = '';
        let totalClicks = 0;
        let uniqueClicks = 0;
        let currentFormat = 'html';
        let currentMetric = 'unique';
        let badgeLabel = 'clicks';
        let badgeColor = 'indigo';

        const apiBase = <?= json_encode(rtrim(Helpers::baseUrl(), '/')) ?>;
        const i18nEmbed = {
            instructionsHtml: <?= json_encode(I18n::t('embed_instructions_html')) ?>,
            instructionsWp: <?= json_encode(I18n::t('embed_instructions_wp')) ?>,
            instructionsPhp: <?= json_encode(I18n::t('embed_instructions_php')) ?>,
            instructionsBadge: <?= json_encode(I18n::t('embed_instructions_badge')) ?>,
            instructionsApi: <?= json_encode(I18n::t('embed_instructions_api')) ?>,
            copiedText: <?= json_encode(I18n::t('embed_copied_success')) ?>,
            copyText: <?= json_encode(I18n::t('embed_copy_code_btn')) ?>,
            previewUnique: <?= json_encode(I18n::t('embed_preview_tag_unique')) ?>,
            previewAll: <?= json_encode(I18n::t('embed_preview_tag_all')) ?>,
            clicksUnit: <?= json_encode(I18n::t('col_clicks')) ?>
        };

        const updateEmbedUI = () => {
            const countVal = (currentMetric === 'unique') ? uniqueClicks : totalClicks;
            const countFormatted = Number(countVal).toLocaleString();

            // Update title
            const slugTitle = document.getElementById('embedModalSlugTitle');
            if (slugTitle) slugTitle.textContent = '/' + currentSlug;

            // Update badge / number preview
            const previewDisplay = document.getElementById('embedPreviewDisplay');
            const previewBadgeWrap = document.getElementById('embedPreviewBadgeWrapper');
            const previewBadgeImg = document.getElementById('embedPreviewBadgeImg');
            const previewNum = document.getElementById('embedPreviewNumber');
            const previewTag = document.getElementById('embedPreviewBadgeTag');
            const badgeControls = document.getElementById('embedBadgeControls');
            const codeOutput = document.getElementById('embedCodeOutput');
            const instructionText = document.getElementById('embedInstructionText');

            if (previewTag) {
                previewTag.textContent = currentMetric === 'unique' ? i18nEmbed.previewUnique : i18nEmbed.previewAll;
            }

            if (currentFormat === 'badge') {
                if (previewDisplay) previewDisplay.classList.add('d-none');
                if (previewBadgeWrap) previewBadgeWrap.classList.remove('d-none');
                if (badgeControls) badgeControls.classList.remove('d-none');
                const badgeUrl = `${apiBase}/badge.php?slug=${encodeURIComponent(currentSlug)}&type=${currentMetric}&label=${encodeURIComponent(badgeLabel)}&color=${encodeURIComponent(badgeColor)}`;
                if (previewBadgeImg) previewBadgeImg.src = badgeUrl;
            } else {
                if (previewDisplay) previewDisplay.classList.remove('d-none');
                if (previewBadgeWrap) previewBadgeWrap.classList.add('d-none');
                if (badgeControls) badgeControls.classList.add('d-none');
                if (previewNum) previewNum.textContent = countFormatted;
            }

            // Generate Code Output (100% English comments & code, generic click counter)
            let snippet = '';
            if (currentFormat === 'html') {
                snippet = `<!-- AtharLink Live Counter -->\n<span data-athar-count="${currentSlug}" data-type="${currentMetric}">0</span>\n<script src="${apiBase}/embed.js" async><\/script>`;
                if (instructionText) instructionText.textContent = i18nEmbed.instructionsHtml;
            } else if (currentFormat === 'wp') {
                snippet = `[athar_counter slug="${currentSlug}" type="${currentMetric}"]`;
                if (instructionText) instructionText.textContent = i18nEmbed.instructionsWp;
            } else if (currentFormat === 'php') {
                snippet = `<\?php\n// Fetch live click counter directly from server with cache\n$clicks = (function($slug, $type = '${currentMetric}') {\n    $cache = sys_get_temp_dir() . '/athar_' . md5($slug . $type) . '.txt';\n    if (file_exists($cache) && (time() - filemtime($cache) < 600)) return (int)file_get_contents($cache);\n    $res = @file_get_contents("${apiBase}/index.php?action=get_count&slug={$slug}&type={$type}");\n    $val = $res ? (int)(json_decode($res, true)['clicks'] ?? 0) : 0;\n    @file_put_contents($cache, $val);\n    return $val;\n})('${currentSlug}', '${currentMetric}');\necho number_format($clicks);\n\?>`;
                if (instructionText) instructionText.textContent = i18nEmbed.instructionsPhp;
            } else if (currentFormat === 'badge') {
                const badgeUrl = `${apiBase}/badge.php?slug=${encodeURIComponent(currentSlug)}&type=${currentMetric}&label=${encodeURIComponent(badgeLabel)}&color=${encodeURIComponent(badgeColor)}`;
                snippet = `<img src="${badgeUrl}" alt="${badgeLabel}">\n\n<!-- Markdown: -->\n![${badgeLabel}](${badgeUrl})`;
                if (instructionText) instructionText.textContent = i18nEmbed.instructionsBadge;
            } else if (currentFormat === 'api') {
                snippet = `GET ${apiBase}/api/v1/counter?slug=${currentSlug}&type=${currentMetric}\n\n// Response Example:\n{\n  "success": true,\n  "slug": "${currentSlug}",\n  "clicks": ${countVal},\n  "total_clicks": ${totalClicks},\n  "unique_clicks": ${uniqueClicks}\n}`;
                if (instructionText) instructionText.textContent = i18nEmbed.instructionsApi;
            }

            if (codeOutput) codeOutput.value = snippet;
        };

        embedModalEl.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            if (btn) {
                currentSlug = btn.getAttribute('data-slug') || '';
                totalClicks = parseInt(btn.getAttribute('data-total-clicks') || '0', 10);
                uniqueClicks = parseInt(btn.getAttribute('data-unique-clicks') || '0', 10);
            }
            updateEmbedUI();
        });

        // Platform Pills switching
        const platformPills = document.querySelectorAll('#embedPlatformPills button');
        platformPills.forEach(btn => {
            btn.addEventListener('click', function () {
                platformPills.forEach(b => {
                    b.classList.remove('active', 'btn-tamoza-primary');
                    b.classList.add('btn-tamoza-subtle');
                });
                this.classList.add('active', 'btn-tamoza-primary');
                this.classList.remove('btn-tamoza-subtle');
                currentFormat = this.getAttribute('data-format');
                updateEmbedUI();
            });
        });

        // Metric radio switching
        const metricRadios = document.querySelectorAll('input[name="embed_metric_type"]');
        metricRadios.forEach(radio => {
            radio.addEventListener('change', function () {
                if (this.checked) {
                    currentMetric = this.value;
                    updateEmbedUI();
                }
            });
        });

        // Badge custom controls
        const labelInput = document.getElementById('embedBadgeLabelInput');
        if (labelInput) {
            labelInput.addEventListener('input', function () {
                badgeLabel = this.value.trim() || 'clicks';
                updateEmbedUI();
            });
        }

        const colorSelect = document.getElementById('embedBadgeColorSelect');
        if (colorSelect) {
            colorSelect.addEventListener('change', function () {
                badgeColor = this.value;
                updateEmbedUI();
            });
        }

        // Copy button handler (Top & Main)
        const triggerCopy = (btnEl, iconEl, textEl) => {
            const codeOutput = document.getElementById('embedCodeOutput');
            if (!codeOutput) return;
            navigator.clipboard.writeText(codeOutput.value).then(() => {
                if (textEl) textEl.textContent = i18nEmbed.copiedText;
                if (iconEl) iconEl.innerHTML = <?= json_encode(Icon::get('check', '', 14)) ?>;
                setTimeout(() => {
                    if (textEl) textEl.textContent = i18nEmbed.copyText;
                    if (iconEl) iconEl.innerHTML = <?= json_encode(Icon::get('copy', '', 14)) ?>;
                }, 2000);
            });
        };

        const copyMain = document.getElementById('embedCopyBtnMain');
        if (copyMain) {
            copyMain.addEventListener('click', () => {
                triggerCopy(copyMain, document.getElementById('embedCopyIconMain'), document.getElementById('embedCopyTextMain'));
            });
        }

        const copyTop = document.getElementById('embedCopyBtnTop');
        if (copyTop) {
            copyTop.addEventListener('click', () => {
                triggerCopy(copyTop, document.getElementById('embedCopyBtnTop').querySelector('svg'), document.getElementById('embedCopyTextTop'));
            });
        }
    }
});
</script>

</body>
</html>
