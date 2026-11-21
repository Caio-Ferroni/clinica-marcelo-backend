<?php
require_once __DIR__ . '/Toast.php';

final class AgendaOcupadaException extends RuntimeException {}

final class ConflitoAgendaException extends RuntimeException {
    public function __construct(public readonly array $conflito) {
        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $conflito['data']);
        $paciente = trim((string) ($conflito['paciente_nome'] ?? ''));
        $paciente = $paciente !== '' ? $paciente : 'consulta #' . $conflito['id_consulta'];
        parent::__construct(sprintf(
            'O horário de %s, das %s às %s, já está ocupado por %s. Escolha outro horário.',
            $data ? $data->format('d/m/Y') : $conflito['data'],
            substr($conflito['horario_inicio'], 0, 5),
            substr($conflito['horario_fim'], 0, 5),
            $paciente
        ));
    }
}

final class AgendaTransacao {
    private static array $ativas = [];

    public static function executar(PDO $db, callable $operacao): mixed {
        $id = spl_object_id($db);
        if (isset(self::$ativas[$id])) return $operacao();
        if ($db->inTransaction()) throw new LogicException('A operação de agenda deve controlar a transação.');
        $mysql = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
        $nome = null;
        if ($mysql) {
            $banco = (string) $db->query('SELECT DATABASE()')->fetchColumn();
            $nome = 'clinica_agenda_' . substr(hash('sha256', $banco), 0, 32);
            $stmt = $db->prepare('SELECT GET_LOCK(?, 5)');
            $stmt->execute([$nome]);
            if ((int) $stmt->fetchColumn() !== 1) throw new AgendaOcupadaException(Toast::message('schedule.busy'));
        }
        self::$ativas[$id] = true;
        try {
            $db->beginTransaction();
            $resultado = $operacao();
            $db->commit();
            return $resultado;
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        } finally {
            unset(self::$ativas[$id]);
            if ($nome !== null) {
                try {
                    $stmt = $db->prepare('SELECT RELEASE_LOCK(?)');
                    $stmt->execute([$nome]);
                } catch (PDOException $e) {
                    error_log('[Agenda] Falha ao liberar a trava da conexão.');
                }
            }
        }
    }
}
