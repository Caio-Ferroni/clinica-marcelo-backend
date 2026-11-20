<?php
declare(strict_types=1);

require_once __DIR__ . '/../Auth/Auth.php';
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Notifications/bootstrap.php';

final class NotificacaoController
{
    public function __construct(private ?WebPushConfig $config = null, private ?WebPushRepository $repository = null)
    {
        $this->config ??= WebPushConfig::fromEnvironment();
    }

    public function handle(string $action): void
    {
        $response = $this->dispatch($action);
        http_response_code($response['status']);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($response['body'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public function dispatch(string $action, ?string $rawBody = null): array
    {
        if (!Auth::estaAutenticado() || filter_var($_SESSION['psicologo_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            return self::response(401, ['error' => 'Autenticação necessária.']);
        }
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (($action === 'config' && $method !== 'GET') || ($action !== 'config' && $method !== 'POST')) {
            return self::response(405, ['error' => 'Método não permitido.']);
        }
        if ($action === 'config') {
            try {
                if ($this->config->enabled) {
                    $this->config->assertReady();
                }
                $_SESSION['webpush_csrf'] ??= bin2hex(random_bytes(32));
                return self::response(200, [
                    'enabled' => $this->config->enabled,
                    'publicKey' => $this->config->enabled ? $this->config->publicKey : null,
                    'csrfToken' => $_SESSION['webpush_csrf'],
                ]);
            } catch (Throwable $error) {
                return self::response(503, ['error' => 'Webpush indisponível. Verifique a configuração no servidor.']);
            }
        }
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($token) || empty($_SESSION['webpush_csrf']) || !hash_equals($_SESSION['webpush_csrf'], $token)) {
            return self::response(403, ['error' => 'Token de segurança inválido.']);
        }
        if (!in_array($action, ['save', 'remove'], true)) {
            return self::response(404, ['error' => 'Operação não encontrada.']);
        }
        if ($action === 'save' && !$this->config->enabled) {
            return self::response(503, ['error' => 'Webpush desabilitado.']);
        }
        if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
            return self::response(415, ['error' => 'Envie application/json.']);
        }
        $rawBody ??= file_get_contents('php://input', false, null, 0, 16385) ?: '';
        if (strlen($rawBody) > 16384) {
            return self::response(413, ['error' => 'Inscrição excede o tamanho permitido.']);
        }
        try {
            $input = json_decode($rawBody, true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($input) || array_is_list($input)) {
                throw new InvalidArgumentException('Objeto JSON esperado.');
            }
            if ($action === 'save') {
                $this->config->assertReady();
                $subscription = PushSubscriptionValidator::validate($input);
                $this->repository ??= new WebPushRepository(Database::getConnection(true));
                $this->repository->save((int) $_SESSION['psicologo_id'], $subscription);
            } else {
                $endpoint = PushSubscriptionValidator::endpoint($input['endpoint'] ?? null);
                $this->repository ??= new WebPushRepository(Database::getConnection(true));
                $this->repository->remove((int) $_SESSION['psicologo_id'], $endpoint);
            }
            return self::response(200, ['ok' => true]);
        } catch (JsonException | InvalidArgumentException $error) {
            return self::response(422, ['error' => 'Inscrição ou JSON inválido.']);
        } catch (DomainException $error) {
            return self::response(409, ['error' => 'Inscrição indisponível.']);
        } catch (Throwable $error) {
            error_log('[WebPush] Falha na operação de inscrição.');
            return self::response(503, ['error' => 'Não foi possível concluir a operação. Tente novamente.']);
        }
    }

    private static function response(int $status, array $body): array
    {
        return ['status' => $status, 'body' => $body];
    }
}
