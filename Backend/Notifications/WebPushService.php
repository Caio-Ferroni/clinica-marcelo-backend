<?php
declare(strict_types=1);

require_once __DIR__ . '/PushTransport.php';
require_once __DIR__ . '/WebPushConfig.php';
require_once __DIR__ . '/PushSubscriptionValidator.php';

final class WebPushService implements PushTransport
{
    private \Minishlink\WebPush\WebPush $sender;

    public function __construct(WebPushConfig $config, ?\Psr\Http\Client\ClientInterface $client = null)
    {
        if (!$config->enabled) {
            throw new RuntimeException('Webpush desabilitado.');
        }
        $config->assertReady();
        $client ??= new \GuzzleHttp\Client([
            'timeout' => 10, 'connect_timeout' => 5, 'allow_redirects' => false,
            'http_errors' => false, 'verify' => true, 'proxy' => '',
        ]);
        $factory = new \GuzzleHttp\Psr7\HttpFactory();
        $this->sender = new \Minishlink\WebPush\WebPush(['VAPID' => [
            'subject' => $config->subject, 'publicKey' => $config->publicKey, 'privateKey' => $config->privateKey,
        ]], ['urgency' => 'high', 'contentType' => 'application/json'], $client, $factory, $factory);
    }

    public function send(array $subscription, array $payload, int $ttl, string $topic): array
    {
        try {
            PushSubscriptionValidator::endpoint($subscription['endpoint']);
            $report = $this->sender->sendOneNotification(\Minishlink\WebPush\Subscription::create([
                'endpoint' => $subscription['endpoint'],
                'keys' => ['p256dh' => $subscription['p256dh'], 'auth' => $subscription['auth']],
                'contentEncoding' => 'aes128gcm',
            ]), json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), [
                'TTL' => max(0, $ttl), 'topic' => $topic,
            ]);
            $response = $report->getResponse();
            $http = $response?->getStatusCode();
            $status = match (true) {
                $http !== null && $http >= 200 && $http < 300 && $report->isSuccess() => 'sent',
                $report->isSubscriptionExpired() => 'expired',
                $http === null, in_array($http, [408, 425, 429], true), $http >= 500 => 'temporary',
                default => 'permanent',
            };
            $retry = trim($response?->getHeaderLine('Retry-After') ?? '');
            $seconds = ctype_digit($retry) ? min((float) $retry, 86400) : (($retry !== '' ? strtotime($retry) : false) ?: 0) - time();
            return ['status' => $status, 'http_status' => $http, 'retry_after' => $seconds > 0 ? (int) $seconds : null];
        } catch (InvalidArgumentException $error) {
            return ['status' => 'permanent', 'http_status' => null, 'retry_after' => null];
        } catch (Throwable $error) {
            // Não registrar a exceção: bibliotecas HTTP podem incluir endpoint, chaves ou payload.
            return ['status' => 'temporary', 'http_status' => null, 'retry_after' => null];
        }
    }
}
