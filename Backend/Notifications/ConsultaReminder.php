<?php
declare(strict_types=1);

require_once __DIR__ . '/WebPushConfig.php';
require_once __DIR__ . '/WebPushRepository.php';
require_once __DIR__ . '/PushTransport.php';

final class ConsultaReminder
{
    /** Ainda avisa no horário mesmo se o agendador atrasar; depois disso a ocorrência é ignorada. */
    public const AT_TIME_GRACE = 900;

    private Closure $clock;

    public function __construct(
        private WebPushRepository $repository,
        private PushTransport $transport,
        ?Closure $clock = null,
        private int $reminderSeconds = 600,
        private string $timezone = WebPushConfig::TIMEZONE
    ) {
        $this->clock = $clock ?? static fn () => new DateTimeImmutable('now', new DateTimeZone($timezone));
        $this->reminderSeconds = max(60, $reminderSeconds);
    }

    public function run(bool $dryRun = false): array
    {
        $counts = ['eligible' => 0, 'sent' => 0, 'temporary' => 0, 'expired' => 0, 'permanent' => 0, 'skipped' => 0, 'locked' => false];
        if (!$dryRun && !$this->repository->acquireLock()) {
            $counts['locked'] = true;
            return $counts;
        }
        try {
            foreach ($this->repository->candidates($this->now(), $this->reminderSeconds, self::AT_TIME_GRACE) as $candidate) {
                $now = $this->now();
                $current = $this->repository->current($candidate, $now);
                $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $candidate['starts_at'], new DateTimeZone($this->timezone));
                if ($start === false) {
                    $counts['skipped']++;
                    continue;
                }
                $remaining = $start->getTimestamp() - $now->getTimestamp();
                if ($current === null || $remaining > $this->reminderSeconds || $remaining < -self::AT_TIME_GRACE) {
                    $counts['skipped']++;
                    continue;
                }
                $kind = $remaining > 0 ? 'antes' : 'no_horario';
                $counts['eligible']++;
                if ($dryRun) {
                    continue;
                }
                if (!$this->repository->attempt($candidate, $kind, $now)) {
                    $counts['skipped']++;
                    continue;
                }

                $now = $this->now();
                $current = $this->repository->current($candidate, $now);
                $remaining = $start->getTimestamp() - $now->getTimestamp();
                if ($current === null || $remaining < -self::AT_TIME_GRACE) {
                    $counts['skipped']++;
                    continue;
                }
                [$payload, $ttl, $tag] = $this->message($candidate, $current, $start, $now, $remaining, $kind);
                try {
                    $result = $this->transport->send($current, $payload, $ttl, substr(hash('sha256', $tag), 0, 32));
                } catch (Throwable $error) {
                    $result = ['status' => 'temporary', 'http_status' => null, 'retry_after' => null];
                }
                $this->repository->finish($candidate, $kind, $result, $this->now());
                $counts[$result['status']]++;
            }
        } finally {
            if (!$dryRun) {
                $this->repository->releaseLock();
            }
        }
        return $counts;
    }

    /**
     * @return array{0: array, 1: int, 2: string} payload, TTL e tag.
     */
    private function message(array $candidate, array $current, DateTimeImmutable $start, DateTimeImmutable $now, int $remaining, string $kind): array
    {
        $relativo = $start->format('Y-m-d') === $now->format('Y-m-d') ? 'hoje às ' . $start->format('H:i')
            : ($start->format('Y-m-d') === $now->modify('+1 day')->format('Y-m-d') ? 'amanhã às ' . $start->format('H:i')
            : 'em ' . $start->format('d/m') . ' às ' . $start->format('H:i'));
        $nome = mb_substr($current['nome'], 0, 120);
        $base = 'consulta-' . $candidate['id_consulta'] . '-' . $start->getTimestamp();
        if ($kind === 'antes') {
            $minutes = (int) ceil($remaining / 60);
            return [[
                'title' => 'Lembrete de consulta',
                'body' => 'Consulta com ' . $nome . ' ' . $relativo
                    . ' — começa em ' . $minutes . ($minutes === 1 ? ' minuto.' : ' minutos.'),
                'tag' => $base,
                'url' => '/consultas',
                'expiresAt' => $start->getTimestamp() * 1000,
            ], min($remaining, 2419200), $base];
        }
        if ($remaining >= -60) {
            $sufixo = ' — é agora.';
        } else {
            $minutosAtras = (int) ceil(-$remaining / 60);
            $sufixo = ' — começou há ' . $minutosAtras . ($minutosAtras === 1 ? ' minuto.' : ' minutos.');
        }
        $expira = $start->getTimestamp() + self::AT_TIME_GRACE;
        return [[
            'title' => 'Consulta agora',
            'body' => 'Consulta com ' . $nome . ' ' . $relativo . $sufixo,
            'tag' => $base . '-agora',
            'url' => '/consultas',
            'expiresAt' => $expira * 1000,
        ], min(2419200, max(60, $expira - $now->getTimestamp())), $base . '-agora'];
    }

    private function now(): DateTimeImmutable
    {
        return ($this->clock)()->setTimezone(new DateTimeZone($this->timezone));
    }
}
