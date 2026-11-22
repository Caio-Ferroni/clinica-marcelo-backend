<?php
declare(strict_types=1);

require_once __DIR__ . '/WebPushConfig.php';
require_once __DIR__ . '/WebPushRepository.php';
require_once __DIR__ . '/ConsultaReminder.php';

/**
 * Relatório de diagnóstico do Webpush executado por CLI.
 *
 * Somente leitura: não envia notificações, não grava tentativas e não imprime
 * endpoints, chaves, nomes de pacientes ou qualquer outro dado sensível.
 */
final class WebPushDiagnostics
{
    private const PUSH_HOSTS_TIMEOUT = 3;

    public function __construct(private WebPushConfig $config, private ?PDO $db = null) {}

    public function report(): string
    {
        $lines = ['Webpush — diagnóstico', ''];
        $this->appendEnvironment($lines);
        $lines[] = '';
        $this->appendDatabase($lines);
        $lines[] = '';
        $this->appendWindow($lines);
        $lines[] = '';
        $this->appendDeliveries($lines);
        $lines[] = '';
        $this->appendConnectivity($lines);
        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function appendEnvironment(array &$lines): void
    {
        $lines[] = 'Ambiente';
        $lines[] = '  PHP: ' . PHP_VERSION . ' (' . PHP_SAPI . ') — ' . PHP_BINARY;
        $extensions = [];
        foreach (['curl', 'mbstring', 'openssl', 'pdo_mysql'] as $extension) {
            $extensions[] = $extension . '=' . (extension_loaded($extension) ? 'ok' : 'AUSENTE');
        }
        $lines[] = '  Extensões: ' . implode(' ', $extensions);
        $lines[] = '  Fuso usado na comparação: ' . $this->config->timezone . ' (WEBPUSH_TIMEZONE)'
            . ($this->config->timezone === WebPushConfig::TIMEZONE ? '' : ' — diferente do padrão ' . WebPushConfig::TIMEZONE);
        $lines[] = '  Fuso padrão do PHP: ' . date_default_timezone_get()
            . (date_default_timezone_get() === $this->config->timezone ? '' : ' (não afeta o módulo)');
        $lines[] = '  WEBPUSH_ENABLED: ' . ($this->config->enabled ? 'true' : 'false (o agendador encerra sem enviar)');
        $subject = $this->config->subject;
        $kind = $subject === '' ? 'ausente'
            : (str_starts_with($subject, 'mailto:') ? 'mailto' : (str_starts_with($subject, 'https://') ? 'https' : 'inválido'));
        $lines[] = '  Contato VAPID: ' . $kind;
        $lines[] = '  Chaves VAPID: ' . $this->keyState();
    }

    private function keyState(): string
    {
        if ($this->config->publicKey === '' || $this->config->privateKey === '' || $this->config->subject === '') {
            return 'ausentes (configure WEBPUSH_VAPID_PUBLIC_KEY, WEBPUSH_VAPID_PRIVATE_KEY e WEBPUSH_VAPID_SUBJECT)';
        }
        try {
            $this->config->assertReady();
            return 'válidas (par P-256 conferido)';
        } catch (Throwable $error) {
            return 'inválidas: ' . $error->getMessage();
        }
    }

    private function appendDatabase(array &$lines): void
    {
        $lines[] = 'Banco de dados';
        if ($this->db === null) {
            $lines[] = '  Conexão: FALHOU (verifique Backend/Config/Database.php)';
            return;
        }
        try {
            $lines[] = '  Conexão: ok (' . $this->db->query('SELECT DATABASE()')->fetchColumn() . ')';
        } catch (Throwable $error) {
            $lines[] = '  Conexão: FALHOU';
            return;
        }

        $tables = [];
        try {
            $tables = array_map('strval', $this->db->query("SHOW TABLES LIKE 'webpush\\_%'")->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $error) {
            $lines[] = '  Tabelas: erro ao consultar';
        }
        foreach (['webpush_subscriptions', 'webpush_deliveries'] as $table) {
            if (!in_array($table, $tables, true)) {
                $lines[] = '  ' . $table . ': AUSENTE — aplique Backend/Migrations/001_webpush_up.sql';
            }
        }
        if (!in_array('webpush_subscriptions', $tables, true)) {
            return;
        }

        try {
            $total = (int) $this->db->query('SELECT COUNT(*) FROM webpush_subscriptions')->fetchColumn();
            $active = (int) $this->db->query('SELECT COUNT(*) FROM webpush_subscriptions WHERE active = 1')->fetchColumn();
            $expired = (int) $this->db->query('SELECT COUNT(*) FROM webpush_subscriptions WHERE active = 1 AND expires_at IS NOT NULL AND expires_at <= UTC_TIMESTAMP()')->fetchColumn();
            $lines[] = "  webpush_subscriptions: {$total} inscrição(ões), {$active} ativa(s), {$expired} ativa(s) vencida(s)";
            $owners = $this->db->query('SELECT id_psicologo, COUNT(*) AS total FROM webpush_subscriptions WHERE active = 1 GROUP BY id_psicologo')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($owners as $owner) {
                $lines[] = '    conta id_psicologo=' . (int) $owner['id_psicologo'] . ' com ' . (int) $owner['total'] . ' dispositivo(s) ativo(s)';
            }
        } catch (Throwable $error) {
            $lines[] = '  webpush_subscriptions: erro ao consultar';
        }

        try {
            $accounts = (int) $this->db->query('SELECT COUNT(*) FROM psicologo')->fetchColumn();
            $lines[] = '  Contas de psicólogo: ' . $accounts . ($accounts === 1 ? '' : ' — BLOQUEIO');
            if ($accounts !== 1) {
                $lines[] = '    PROBLEMA: com mais de uma conta o agendador encerra sem enviar nada (o --teste continua funcionando).';
                $lines[] = '    Corrija removendo a conta excedente ou ajustando a regra de conta única em WebPushRepository::candidates().';
            }
        } catch (Throwable $error) {
            $lines[] = '  Contas de psicólogo: erro ao consultar (tabela psicologo ausente?)';
        }
    }

    private function appendWindow(array &$lines): void
    {
        $lines[] = 'Janela atual';
        if ($this->db === null) {
            $lines[] = '  não avaliada (sem conexão com o banco)';
            return;
        }
        $now = new DateTimeImmutable('now', new DateTimeZone($this->config->timezone));
        $minutes = intdiv($this->config->reminderSeconds, 60);
        $lines[] = '  Agora (' . $this->config->timezone . '): ' . $now->format('Y-m-d H:i:s') . ' (offset ' . $now->format('P') . ')';
        $this->appendClocks($lines, $now);
        $lines[] = '  Regra: um aviso quando faltarem até ' . $minutes . ' min (WEBPUSH_REMINDER_MINUTES) e outro no horário marcado, com até '
            . intdiv(ConsultaReminder::AT_TIME_GRACE, 60) . ' min de tolerância. Nada é enviado no cadastro.';
        try {
            $lines[] = '  Consultas Pendente (paciente Ativo) dentro do aviso prévio: ' . $this->scalar('SELECT COUNT(DISTINCT c.id_consulta) FROM consulta c
                JOIN agendamento a ON a.id_agendamento = c.id_agendamento
                JOIN paciente p ON p.id_paciente = a.id_paciente AND p.status = \'Ativo\'
                WHERE c.status = \'Pendente\'
                    AND TIMESTAMP(c.data, c.horario_inicio) > ?
                    AND TIMESTAMP(c.data, c.horario_inicio) <= ?',
                [$now->format('Y-m-d H:i:s'), $now->modify('+' . $this->config->reminderSeconds . ' seconds')->format('Y-m-d H:i:s')]);
            $lines[] = '  Consultas Pendente (paciente Ativo) já iniciadas dentro da tolerância: ' . $this->scalar('SELECT COUNT(DISTINCT c.id_consulta) FROM consulta c
                JOIN agendamento a ON a.id_agendamento = c.id_agendamento
                JOIN paciente p ON p.id_paciente = a.id_paciente AND p.status = \'Ativo\'
                WHERE c.status = \'Pendente\'
                    AND TIMESTAMP(c.data, c.horario_inicio) <= ?
                    AND TIMESTAMP(c.data, c.horario_inicio) > ?',
                [$now->format('Y-m-d H:i:s'), $now->modify('-' . ConsultaReminder::AT_TIME_GRACE . ' seconds')->format('Y-m-d H:i:s')]);
        } catch (Throwable $error) {
            $lines[] = '  Consultas Pendente: não avaliadas (tabelas consulta/agendamento/paciente)';
            $lines[] = '  Motivo: configuração ou tabelas ausentes.';
        }
        try {
            $lines[] = '  Pares consulta/dispositivo elegíveis neste minuto: ' . $this->scalar('SELECT COUNT(*) FROM consulta c
                JOIN agendamento a ON a.id_agendamento = c.id_agendamento
                JOIN paciente p ON p.id_paciente = a.id_paciente AND p.status = \'Ativo\'
                CROSS JOIN webpush_subscriptions s
                WHERE c.status = \'Pendente\' AND s.active = 1
                    AND (s.expires_at IS NULL OR s.expires_at > ?)
                    AND TIMESTAMP(c.data, c.horario_inicio) > ?
                    AND TIMESTAMP(c.data, c.horario_inicio) <= ?',
                [WebPushRepository::utc($now), $now->modify('-' . ConsultaReminder::AT_TIME_GRACE . ' seconds')->format('Y-m-d H:i:s'),
                    $now->modify('+' . $this->config->reminderSeconds . ' seconds')->format('Y-m-d H:i:s')]);
        } catch (Throwable $error) {
            $lines[] = '  Pares elegíveis: não avaliados (webpush_subscriptions ausente)';
        }
        try {
            $stmt = $this->db->prepare('SELECT MIN(TIMESTAMP(c.data, c.horario_inicio)) FROM consulta c
                JOIN agendamento a ON a.id_agendamento = c.id_agendamento
                JOIN paciente p ON p.id_paciente = a.id_paciente AND p.status = \'Ativo\'
                WHERE c.status = \'Pendente\' AND TIMESTAMP(c.data, c.horario_inicio) > ?');
            $stmt->execute([$now->format('Y-m-d H:i:s')]);
            $next = $stmt->fetchColumn();
            if ($next) {
                $minutes = (int) ceil(($this->timestamp((string) $next) - $now->getTimestamp()) / 60);
                $lines[] = '  Próxima consulta Pendente: ' . $next . ' (em ' . $minutes . ' min)';
            } else {
                $lines[] = '  Próxima consulta Pendente: nenhuma (gere as consultas na tela de Consultas)';
            }
            $lines[] = '  Consultas Pendente de hoje: ' . $this->countToday($now);
        } catch (Throwable $error) {
            $lines[] = '  Próxima consulta Pendente: não avaliada';
        }
        $this->appendVerdicts($lines, $now);
    }

    /** Explica, consulta por consulta, se ela entraria na fila e por quê. */
    private function appendVerdicts(array &$lines, DateTimeImmutable $now): void
    {
        $lines[] = '';
        $lines[] = 'Próximas consultas Pendente';
        try {
            $stmt = $this->db->query('SELECT c.id_consulta, TIMESTAMP(c.data, c.horario_inicio) AS starts_at,
                    c.status AS consulta_status, p.status AS paciente_status
                FROM consulta c
                JOIN agendamento a ON a.id_agendamento = c.id_agendamento
                LEFT JOIN paciente p ON p.id_paciente = a.id_paciente
                WHERE c.status = \'Pendente\'
                ORDER BY starts_at LIMIT 5');
            $consultas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $error) {
            $lines[] = '  não avaliadas (tabelas consulta/agendamento/paciente)';
            return;
        }
        if (!$consultas) {
            $lines[] = '  nenhuma — as ocorrências precisam ser geradas na tela de Consultas';
            return;
        }
        $ativos = (int) $this->db->query('SELECT COUNT(*) FROM webpush_subscriptions WHERE active = 1 AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP())')->fetchColumn();
        foreach ($consultas as $consulta) {
            $remaining = $this->timestamp((string) $consulta['starts_at']) - $now->getTimestamp();
            $motivo = 'elegível agora';
            if (($consulta['paciente_status'] ?? '') !== 'Ativo') {
                $motivo = 'paciente não está Ativo';
            } elseif ($ativos === 0) {
                $motivo = 'nenhum dispositivo inscrito e ativo';
            } elseif ($remaining < -ConsultaReminder::AT_TIME_GRACE) {
                $motivo = 'já passou da tolerância de ' . intdiv(ConsultaReminder::AT_TIME_GRACE, 60) . ' min';
            } elseif ($remaining > $this->config->reminderSeconds) {
                $motivo = 'fora da antecedência (faltam ' . (int) ceil($remaining / 60) . ' min; aviso a partir de '
                    . intdiv($this->config->reminderSeconds, 60) . ' min)';
            }
            $entregas = [];
            try {
                $stmt = $this->db->prepare('SELECT kind, status FROM webpush_deliveries WHERE id_consulta = ? ORDER BY id');
                $stmt->execute([(int) $consulta['id_consulta']]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $entrega) {
                    $entregas[] = $entrega['kind'] . ':' . $entrega['status'];
                }
            } catch (Throwable $error) {
                $entregas[] = 'histórico indisponível';
            }
            $lines[] = sprintf('  consulta=%d %s (%s) — %s | entregas: %s',
                (int) $consulta['id_consulta'], $consulta['starts_at'],
                $remaining >= 0 ? 'em ' . (int) ceil($remaining / 60) . ' min' : 'iniciada há ' . (int) ceil(-$remaining / 60) . ' min',
                $motivo, $entregas ? implode(', ', $entregas) : 'nenhuma');
        }
    }

    private function scalar(string $sql, array $parameters): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parameters);
        return (int) $stmt->fetchColumn();
    }

    /**
     * A data do banco é local de São Paulo. Interpretar com strtotime usaria o fuso padrão do PHP,
     * que pode ser UTC, produzindo diferenças de horas no relatório.
     */
    private function timestamp(string $value): int
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone($this->config->timezone));
        return $parsed === false ? 0 : $parsed->getTimestamp();
    }

    /** O módulo compara a data/hora local da consulta com o relógio do PHP, nunca com o do MySQL. */
    private function appendClocks(array &$lines, DateTimeImmutable $now): void
    {
        try {
            $clock = $this->db->query('SELECT NOW()')->fetchColumn();
            $lines[] = '  Relógio do banco: ' . $clock . ' (informativo; a comparação usa o fuso acima)';
        } catch (Throwable $error) {
            $lines[] = '  Relógio do banco: indisponível';
        }
    }

    private function countToday(DateTimeImmutable $now): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM consulta WHERE status = \'Pendente\' AND data = ?');
        $stmt->execute([$now->format('Y-m-d')]);
        return (int) $stmt->fetchColumn();
    }

    private function appendDeliveries(array &$lines): void
    {
        $lines[] = 'Histórico recente de entregas';
        if ($this->db === null) {
            $lines[] = '  não avaliado (sem conexão com o banco)';
            return;
        }
        try {
            $stmt = $this->db->query('SELECT id, id_consulta, subscription_id, starts_at, kind, status, attempts, http_status, next_attempt_at
                FROM webpush_deliveries ORDER BY id DESC LIMIT 10');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $error) {
            $lines[] = '  não avaliado (tabela webpush_deliveries ausente)';
            return;
        }
        if (!$rows) {
            $lines[] = '  vazio — nenhuma tentativa registrada. O agendador não está executando ou não encontrou pares elegíveis.';
            return;
        }
        foreach ($rows as $row) {
            $lines[] = sprintf('  consulta=%d dispositivo=%d tipo=%s início=%s status=%s tentativas=%d http=%s próxima=%s',
                (int) $row['id_consulta'], (int) $row['subscription_id'], $row['kind'] ?? '-', $row['starts_at'], $row['status'],
                (int) $row['attempts'], $row['http_status'] ?? '-', $row['next_attempt_at'] ?? '-');
        }
        $lines[] = '  Legenda: sent=aceito pelo provedor; failed=recusa permanente (ver http); pending=erro temporário, haverá nova tentativa.';
    }

    private function appendConnectivity(array &$lines): void
    {
        $lines[] = 'Conectividade de saída';
        if ($this->db === null || !function_exists('curl_init')) {
            $lines[] = '  não avaliada';
            return;
        }
        try {
            $endpoints = $this->db->query('SELECT endpoint FROM webpush_subscriptions WHERE active = 1')->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $error) {
            $lines[] = '  não avaliada';
            return;
        }
        $hosts = [];
        foreach ($endpoints as $endpoint) {
            $host = parse_url((string) $endpoint, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                $hosts[$host] = true;
            }
        }
        if (!$hosts) {
            $lines[] = '  sem dispositivos ativos para testar';
            return;
        }
        foreach (array_keys($hosts) as $host) {
            $lines[] = '  ' . $host . ': ' . $this->probe((string) $host);
        }
    }

    private function probe(string $host): string
    {
        $curl = curl_init('https://' . $host . '/');
        curl_setopt_array($curl, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::PUSH_HOSTS_TIMEOUT,
            CURLOPT_TIMEOUT => self::PUSH_HOSTS_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        curl_exec($curl);
        $code = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        unset($curl);
        if ($code > 0) {
            return 'alcançável (HTTP ' . $code . ')';
        }
        return 'SEM CONEXÃO (' . ($error !== '' ? $error : 'sem resposta') . ')';
    }
}
