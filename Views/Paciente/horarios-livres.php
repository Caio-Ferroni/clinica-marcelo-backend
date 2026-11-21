<?php
$buscaInicio = is_string($_POST['busca_inicio'] ?? null) && preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $_POST['busca_inicio']) ? $_POST['busca_inicio'] : '08:00';
$buscaFim = is_string($_POST['busca_fim'] ?? null) && preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $_POST['busca_fim']) ? $_POST['busca_fim'] : '20:00';
$duracaoBusca = filter_var($_POST['duracao'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 720]]) ?: 50;
?>
<div class="schedule-suggestions border rounded-3 p-5 mt-5" data-schedule-suggestions>
    <h3 class="text-sm fw-semibold mb-3">Encontrar horário livre</h3>
    <?php if (http_response_code() === 409 && !empty($erro)): ?>
        <p class="text-sm border rounded-3 p-4 mb-4" data-schedule-conflict><?= htmlspecialchars($erro, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <?php endif; ?>
    <p class="text-sm mb-4">Escolha o dia, a repetição e a data inicial acima.</p>
    <div class="row g-4">
        <div class="col-12 md:col-4">
            <label class="form-label text-sm" for="search-duration">Duração (minutos)</label>
            <input class="form-control" type="number" id="search-duration" name="duracao" min="1" max="720" step="1" value="<?= $duracaoBusca ?>" data-search-duration>
        </div>
        <div class="col-6 md:col-4">
            <label class="form-label text-sm" for="search-from">Buscar a partir de</label>
            <input class="form-control" type="time" id="search-from" name="busca_inicio" value="<?= htmlspecialchars($buscaInicio, ENT_QUOTES, 'UTF-8') ?>" data-search-from>
        </div>
        <div class="col-6 md:col-4">
            <label class="form-label text-sm" for="search-until">Até</label>
            <input class="form-control" type="time" id="search-until" name="busca_fim" value="<?= htmlspecialchars($buscaFim, ENT_QUOTES, 'UTF-8') ?>" data-search-until>
        </div>
    </div>
    <button class="btn-outline px-5 py-3 mt-4" type="button" data-search-slots>
        <i class="bi bi-search" aria-hidden="true"></i> Sugerir horários livres
    </button>
    <p class="text-sm mt-4 mb-0" role="status" aria-live="polite" data-slots-feedback>As sugestões conferem todas as datas da repetição no período exibido.</p>
    <div class="d-flex flex-wrap gap-3 mt-4" aria-label="Sugestões de horários livres" data-slots-list></div>
</div>
