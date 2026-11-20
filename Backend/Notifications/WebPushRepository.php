<?php
declare(strict_types=1);

final class WebPushRepository
{
    private string $lockName;

    public function __construct(private PDO $db)
    {
        $this->lockName = 'clinica_webpush_' . substr(hash('sha256', (string) $db->query('SELECT DATABASE()')->fetchColumn()), 0, 32);
    }

    public function save(int $psychologist, array $subscription): void
    {
        $hash = hash('sha256', $subscription['endpoint']);
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id_psicologo FROM webpush_subscriptions WHERE endpoint_hash = ? FOR UPDATE');
            $stmt->execute([$hash]);
            $owner = $stmt->fetchColumn();
            if ($owner !== false && (int) $owner !== $psychologist) {
                throw new DomainException('Inscrição indisponível.');
            }
            $stmt = $this->db->prepare('INSERT INTO webpush_subscriptions
                (id_psicologo, endpoint_hash, endpoint, p256dh, auth, expires_at, active, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())
                ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth),
                    expires_at = VALUES(expires_at), active = 1, updated_at = UTC_TIMESTAMP()');
            $stmt->execute([$psychologist, $hash, $subscription['endpoint'], $subscription['p256dh'], $subscription['auth'], $subscription['expires_at']]);
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function remove(int $psychologist, string $endpoint): void
    {
        $stmt = $this->db->prepare('UPDATE webpush_subscriptions SET active = 0, updated_at = UTC_TIMESTAMP()
            WHERE id_psicologo = ? AND endpoint_hash = ?');
        $stmt->execute([$psychologist, hash('sha256', $endpoint)]);
    }

    public function acquireLock(): bool
    {
        $stmt = $this->db->prepare('SELECT GET_LOCK(?, 0)');
        $stmt->execute([$this->lockName]);
        return (int) $stmt->fetchColumn() === 1;
    }

    public function releaseLock(): void
    {
        $stmt = $this->db->prepare('SELECT RELEASE_LOCK(?)');
        $stmt->execute([$this->lockName]);
    }

    public function candidates(DateTimeImmutable $now): array
    {
        if ((int) $this->db->query('SELECT COUNT(*) FROM psicologo')->fetchColumn() > 1) {
            throw new RuntimeException('Webpush de consultas requer a regra atual de conta única.');
        }
        $stmt = $this->db->prepare('SELECT c.id_consulta, TIMESTAMP(c.data, c.horario_inicio) AS starts_at,
                s.id AS subscription_id
            FROM consulta c
            JOIN agendamento a ON a.id_agendamento = c.id_agendamento
            JOIN paciente p ON p.id_paciente = a.id_paciente AND p.status = \'Ativo\'
            CROSS JOIN webpush_subscriptions s
            JOIN psicologo psi ON psi.id_psicologo = s.id_psicologo
            WHERE c.status = \'Pendente\' AND s.active = 1
                AND (s.expires_at IS NULL OR s.expires_at > ?)
                AND TIMESTAMP(c.data, c.horario_inicio) > ?
                AND TIMESTAMP(c.data, c.horario_inicio) <= ?
            ORDER BY c.data, c.horario_inicio, c.id_consulta, s.id');
        $stmt->execute([
            self::utc($now), $now->format('Y-m-d H:i:s'), $now->modify('+10 minutes')->format('Y-m-d H:i:s'),
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function current(array $candidate, DateTimeImmutable $now): ?array
    {
        $stmt = $this->db->prepare('SELECT c.id_consulta, p.nome, TIMESTAMP(c.data, c.horario_inicio) AS starts_at,
                s.id AS subscription_id, s.endpoint, s.p256dh, s.auth
            FROM consulta c
            JOIN agendamento a ON a.id_agendamento = c.id_agendamento
            JOIN paciente p ON p.id_paciente = a.id_paciente AND p.status = \'Ativo\'
            CROSS JOIN webpush_subscriptions s
            JOIN psicologo psi ON psi.id_psicologo = s.id_psicologo
            WHERE c.id_consulta = ? AND s.id = ? AND c.status = \'Pendente\' AND s.active = 1
                AND TIMESTAMP(c.data, c.horario_inicio) = ?
                AND (s.expires_at IS NULL OR s.expires_at > ?)');
        $stmt->execute([$candidate['id_consulta'], $candidate['subscription_id'], $candidate['starts_at'], self::utc($now)]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function attempt(array $candidate, DateTimeImmutable $now): bool
    {
        $stmt = $this->db->prepare('INSERT IGNORE INTO webpush_deliveries
            (id_consulta, subscription_id, starts_at, status, attempts, updated_at)
            VALUES (?, ?, ?, \'pending\', 0, ?)');
        $stmt->execute([$candidate['id_consulta'], $candidate['subscription_id'], $candidate['starts_at'], self::utc($now)]);
        $stmt = $this->db->prepare('UPDATE webpush_deliveries SET attempts = attempts + 1, updated_at = ?
            WHERE id_consulta = ? AND subscription_id = ? AND starts_at = ? AND status = \'pending\'
                AND (next_attempt_at IS NULL OR next_attempt_at <= ?)');
        $stmt->execute([self::utc($now), $candidate['id_consulta'], $candidate['subscription_id'], $candidate['starts_at'], self::utc($now)]);
        return $stmt->rowCount() === 1;
    }

    public function finish(array $candidate, array $result, DateTimeImmutable $now): void
    {
        $status = match ($result['status']) {
            'sent' => 'sent', 'expired' => 'expired', 'permanent' => 'failed', default => 'pending',
        };
        $retry = $status === 'pending' ? $now->modify('+' . max(60, $result['retry_after'] ?? 60) . ' seconds') : null;
        $stmt = $this->db->prepare('UPDATE webpush_deliveries SET status = ?, http_status = ?, next_attempt_at = ?, updated_at = ?
            WHERE id_consulta = ? AND subscription_id = ? AND starts_at = ?');
        $stmt->execute([$status, $result['http_status'], $retry ? self::utc($retry) : null, self::utc($now),
            $candidate['id_consulta'], $candidate['subscription_id'], $candidate['starts_at']]);
        if ($status === 'expired') {
            $stmt = $this->db->prepare('UPDATE webpush_subscriptions SET active = 0, updated_at = UTC_TIMESTAMP() WHERE id = ?');
            $stmt->execute([$candidate['subscription_id']]);
        }
    }

    public static function utc(DateTimeImmutable $time): string
    {
        return $time->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
