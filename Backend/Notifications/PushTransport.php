<?php
declare(strict_types=1);

interface PushTransport
{
    /** @return array{status: string, http_status: ?int, retry_after: ?int} */
    public function send(array $subscription, array $payload, int $ttl, string $topic): array;
}
