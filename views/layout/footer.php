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

<!-- Structured Footer (Clean Layout: Brand & Copyright) -->
<footer class="tamoza-footer">
    <div class="tamoza-footer-inner">
        <!-- Brand / Identity -->
        <div class="tamoza-footer-brand">
            <?= Icon::get('link', 'text-primary', 18) ?>
            <span><?= APP_NAME ?></span>
        </div>

        <!-- Center / Right Copyright -->
        <div class="tamoza-footer-copy">
            <?= str_replace('Tamoza.net', '<a href="https://tamoza.net" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-primary fw-medium">Tamoza.net</a>', Helpers::e(I18n::t('copyright', ['year' => date('Y')]))) ?>
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
});
</script>

</body>
</html>
