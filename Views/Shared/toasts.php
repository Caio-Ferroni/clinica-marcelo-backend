<?php
require_once __DIR__ . '/../../Backend/Support/Toast.php';
$toastCatalog = Toast::catalog();
$toastMessages = Toast::consume();
?>
<link rel="stylesheet" href="/css/vendor/toastify.css?v=1.12.0">
<link rel="stylesheet" href="/css/toasts.css?v=<?= filemtime(__DIR__ . '/../../css/toasts.css') ?>">
<script id="clinic-toast-catalog" type="application/json"><?= json_encode($toastCatalog, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<div id="clinic-toast-fallback" class="clinic-toast-fallback">
    <?php foreach ($toastMessages as $toastCode): ?>
        <p data-toast-type="<?= $toastCatalog[$toastCode]['type'] ?>" data-toast-code="<?= $toastCode ?>" role="<?= $toastCatalog[$toastCode]['type'] === 'error' ? 'alert' : 'status' ?>"><?= htmlspecialchars($toastCatalog[$toastCode]['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <?php endforeach; ?>
</div>
<script src="/js/vendor/toastify.js?v=1.12.0" defer></script>
<script src="/js/toasts.js?v=<?= filemtime(__DIR__ . '/../../js/toasts.js') ?>" defer></script>
