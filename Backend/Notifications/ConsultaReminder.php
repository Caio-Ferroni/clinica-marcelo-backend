<?php
declare(strict_types=1);

require_once __DIR__ . '/WebPushConfig.php';
require_once __DIR__ . '/WebPushRepository.php';
require_once __DIR__ . '/PushTransport.php';

final class ConsultaReminder
{
    private Closure $clock;

    public function __construct(private WebPushRepository $repository, private PushTransport $transport, ?Closure $clock = null)
    {
        $this->clock = $clock ?? static fn () => new DateTimeImmutable('now', new DateTimeZone(WebPushConfig::TIMEZONE));
    }

    public function run(bool $dryRun = false): array
    {
        $counts = ['eligible' => 0, 'sent' => 0, 'temporary' => 0, 'expired' => 0, 'permanent' => 0, 'skipped' => 0, 'locked' => false];
        if (!$dryRun && !$this->repository->acquireLock()) {
            $counts['locked'] = true;
            return $counts;
        }
        try {
            foreach ($this->repository->candidates($this->now()) as $candidate) {
                $now = $this->now();
                $current = $this->repository->current($candidate, $now);
                $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $candidate['starts_at'], new DateTimeZone(WebPushConfig::TIMEZONE));
                $remaining = $start === false ? 0 : $start->getTimestamp() - $now->getTimestamp();
                if ($current === null || $remaining <= 0 || $remaining > 600) {
                    $counts['skipped']++;
                    continue;
                }
                $counts['eligible']++;
                if ($dryRun) {
                    continue;
                }
                if (!$this->repository->attempt($candidate, $now)) {
                    $counts['skipped']++;
                    continue;
                }

                $now = $this->now();
                $current = $this->repository->current($candidate, $now);
                $remaining = $start->getTimestamp() - $now->getTimestamp();
                if ($current === null || $remaining <= 0 || $remaining > 600) {
                    $counts['skipped']++;
                    continue;
                }
                $minutes = (int) ceil($remaining / 60);
                $tag = 'consulta-' . $candidate['id_consulta'] . '-' . $start->getTimestamp();
                $payload = [
                    'title' => 'Lembrete de consulta',
                    'body' => 'Consulta com ' . mb_substr($current['nome'], 0, 120) . ' às ' . $start->format('H:i')
                        . ' — começa em ' . $minutes . ($minutes === 1 ? ' minuto.' : ' minutos.'),
                    'tag' => $tag, 'url' => '/consultas', 'expiresAt' => $start->getTimestamp() * 1000,
                ];
                try {
                    $result = $this->transport->send($current, $payload, $remaining, substr(hash('sha256', $tag), 0, 32));
                } catch (Throwable $error) {
                    $result = ['status' => 'temporary', 'http_status' => null, 'retry_after' => null];
                }
                $this->repository->finish($candidate, $result, $this->now());
                $counts[$result['status']]++;
            }
        } finally {
            if (!$dryRun) {
                $this->repository->releaseLock();
            }
        }
        return $counts;
    }

    private function now(): DateTimeImmutable
    {
        return ($this->clock)()->setTimezone(new DateTimeZone(WebPushConfig::TIMEZONE));
    }
}
