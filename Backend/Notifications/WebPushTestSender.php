<?php
declare(strict_types=1);

require_once __DIR__ . '/WebPushConfig.php';
require_once __DIR__ . '/PushTransport.php';

/**
 * Envio manual de uma notificação de teste a cada dispositivo inscrito.
 *
 * Não grava em webpush_deliveries e não altera inscrições: serve apenas para
 * separar "o servidor envia" de "o dispositivo exibe".
 */
final class WebPushTestSender
{
    public static function run(\WebPushRepository $repository, PushTransport $transport, ?DateTimeImmutable $now = null): int
    {
        $now ??= new DateTimeImmutable('now', new DateTimeZone(WebPushConfig::TIMEZONE));
        $subscriptions = $repository->activeSubscriptions($now);
        if (!$subscriptions) {
            echo "Nenhum dispositivo inscrito e ativo. Ative os lembretes na tela Notificações.\n";
            return 1;
        }

        $counts = ['dispositivos' => count($subscriptions), 'sent' => 0, 'temporary' => 0, 'expired' => 0, 'permanent' => 0];
        $payload = [
            'title' => 'Teste de notificação',
            'body' => 'Se você está vendo esta mensagem, o Web Push está funcionando neste dispositivo.',
            'tag' => 'teste-' . $now->getTimestamp(),
            'url' => '/consultas',
            'expiresAt' => ($now->getTimestamp() + 300) * 1000,
        ];
        foreach ($subscriptions as $subscription) {
            $result = $transport->send($subscription, $payload, 300, 'teste-' . $now->getTimestamp());
            $counts[$result['status']] = ($counts[$result['status']] ?? 0) + 1;
            printf("dispositivo=%d status=%s http=%s\n", (int) $subscription['id'], $result['status'], $result['http_status'] ?? '-');
        }
        echo json_encode($counts, JSON_THROW_ON_ERROR) . PHP_EOL;

        if ($counts['sent'] === 0) {
            echo "Nenhum provedor aceitou a mensagem. Veja o código HTTP acima e o modo --diagnostico.\n";
            return 1;
        }
        echo "Aceito pelo provedor. Se a notificação não aparecer, o problema está no dispositivo:\n";
        echo "  - permissão de notificações do site no navegador;\n";
        echo "  - notificações do navegador desativadas no sistema operacional (Windows: Configurações > Notificações, e Assistente de Foco);\n";
        echo "  - modo não perturbar, silencioso ou economia de bateria no celular.\n";
        return 0;
    }
}
