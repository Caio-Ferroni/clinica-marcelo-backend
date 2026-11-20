<?php
$paciente = $paciente ?? [];
$perfil = $perfil ?? [];
$agendamento = $agendamento ?? [];
$encaminhamento = $encaminhamento ?? [];
$escape = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$formatarData = static function ($valor) {
    if (empty($valor)) return '';
    $data = DateTime::createFromFormat('!Y-m-d', substr((string) $valor, 0, 10));
    return $data ? $data->format('d/m/Y') : (string) $valor;
};
$dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
$secoes = [
    'Dados pessoais' => [
        'Nome completo' => $paciente['nome'] ?? '',
        'Data de nascimento' => $formatarData($paciente['data_nascimento'] ?? ''),
        'CPF' => $paciente['cpf'] ?? '',
        'Telefone' => $paciente['telefone'] ?? '',
        'Status' => $paciente['status'] ?? ''
    ],
    'Dados de agendamento' => [
        'Dia da semana' => $dias[(string) ($agendamento['dia_semana'] ?? '')] ?? '',
        'Repetição' => $agendamento['repeticao'] ?? '',
        'Horário de início' => $agendamento['horario_inicio'] ?? '',
        'Horário de término' => $agendamento['horario_termino'] ?? '',
        'Data de início' => $formatarData($agendamento['data_inicio'] ?? '')
    ],
    'Perfil do paciente' => [
        'Quadro' => $perfil['quadro'] ?? '',
        'Descrição' => $perfil['descricao'] ?? ''
    ],
    'Encaminhamento' => [
        'Tipo de encaminhamento' => $encaminhamento['tipo_encaminhamento'] ?? '',
        'Histórico do paciente' => $encaminhamento['historico_paciente'] ?? ''
    ]
];
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar paciente | Clínica</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <script src="../../js/layout.js" defer></script>
</head>
<body class="patient-create">
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="patients"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <div class="mx-auto" style="max-width: 1000px;">
                    <h1 class="text-2xl fw-semibold text-center mb-7">Visualizar paciente</h1>
                    <?php if (empty($paciente)): ?>
                        <p class="text-center">Nenhum paciente carregado.</p>
                    <?php else: ?>
                        <div class="border rounded-4 shadow-sm" style="background: var(--color-surface);">
                            <?php foreach ($secoes as $titulo => $campos): ?>
                                <section class="p-7 md:p-9 <?= $titulo !== 'Dados pessoais' ? 'border-top' : '' ?>">
                                    <h2 class="text-md fw-semibold mb-5"><?= $escape($titulo) ?></h2>
                                    <dl class="row g-5 mb-0">
                                        <?php foreach ($campos as $nome => $valor): ?>
                                            <div class="col-12 md:col-6">
                                                <dt class="text-sm fw-semibold mb-3"><?= $escape($nome) ?></dt>
                                                <dd class="border rounded-3 p-4 mb-0" style="white-space: pre-wrap; overflow-wrap: anywhere;"><?= $escape($valor !== '' && $valor !== null ? $valor : 'Não informado') ?></dd>
                                            </div>
                                        <?php endforeach; ?>
                                    </dl>
                                </section>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-end mt-7">
                            <a class="btn-outline" href="/pacientes/<?= (int) $paciente['id_paciente'] ?>/editar">
                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                Editar paciente
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
            <clinic-footer></clinic-footer>
        </div>
    </div>
    <?php require __DIR__ . '/../Shared/toasts.php'; ?>
</body>
</html>
