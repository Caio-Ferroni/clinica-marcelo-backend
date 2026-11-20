<?php
class Toast {
    private const MESSAGES = [
        'validation.required' => ['type' => 'warning', 'message' => 'Preencha os campos obrigatórios.'],
        'validation.email' => ['type' => 'warning', 'message' => 'Informe um e-mail válido.'],
        'validation.password_length' => ['type' => 'warning', 'message' => 'A senha deve ter pelo menos 8 caracteres.'],
        'validation.password_match' => ['type' => 'warning', 'message' => 'As senhas não coincidem.'],
        'validation.code' => ['type' => 'warning', 'message' => 'Informe um código de 6 dígitos.'],
        'validation.fields' => ['type' => 'warning', 'message' => 'Confira os campos destacados e tente novamente.'],
        'auth.credentials' => ['type' => 'error', 'message' => 'E-mail ou senha incorretos.'],
        'auth.code_sent' => ['type' => 'info', 'message' => 'Código enviado. Confira seu e-mail para continuar.'],
        'auth.code_invalid' => ['type' => 'error', 'message' => 'Código inválido ou expirado. Confira o código ou entre novamente.'],
        'auth.code_failed' => ['type' => 'error', 'message' => 'Não foi possível enviar o código de verificação. Tente entrar novamente em instantes.'],
        'auth.login' => ['type' => 'success', 'message' => 'Você entrou no sistema.'],
        'auth.required' => ['type' => 'warning', 'message' => 'Entre na sua conta para continuar.'],
        'auth.logout' => ['type' => 'info', 'message' => 'Você saiu do sistema.'],
        'account.created' => ['type' => 'success', 'message' => 'Conta criada. Entre para continuar.'],
        'account.exists' => ['type' => 'info', 'message' => 'Já existe uma conta cadastrada. Entre para continuar.'],
        'account.failed' => ['type' => 'error', 'message' => 'Não foi possível realizar o cadastro.'],
        'task.created' => ['type' => 'success', 'message' => 'Tarefa criada com sucesso.'],
        'task.required' => ['type' => 'warning', 'message' => 'Preencha o título, o conteúdo e a data de conclusão da tarefa.'],
        'task.date' => ['type' => 'warning', 'message' => 'Informe uma data de conclusão válida.'],
        'consultation.status_saved' => ['type' => 'success', 'message' => 'Status da consulta atualizado.'],
        'consultation.status_invalid' => ['type' => 'warning', 'message' => 'Selecione um status válido para a consulta.'],
        'consultation.form_expired' => ['type' => 'warning', 'message' => 'O formulário expirou. Tente salvar o status novamente.'],
        'consultation.missing' => ['type' => 'error', 'message' => 'Consulta não encontrada.'],
        'consultation.load_failed' => ['type' => 'error', 'message' => 'Não foi possível carregar as consultas. Tente novamente.'],
        'consultation.save_failed' => ['type' => 'error', 'message' => 'Não foi possível salvar o status. Tente novamente.'],
        'consultation.generated' => ['type' => 'success', 'message' => 'As consultas do mês foram geradas.'],
        'consultation.none_generated' => ['type' => 'info', 'message' => 'Nenhuma nova consulta foi gerada.'],
        'report.load_failed' => ['type' => 'error', 'message' => 'Não foi possível carregar o relatório. Tente novamente.'],
        'patient.create_unavailable' => ['type' => 'warning', 'message' => 'O cadastro de pacientes ainda está indisponível. Nenhum dado foi salvo.'],
        'patient.edit_unavailable' => ['type' => 'warning', 'message' => 'A edição de pacientes ainda está indisponível. Nenhuma alteração foi salva.'],
        'patient.load_failed' => ['type' => 'error', 'message' => 'Não foi possível carregar os pacientes. Tente novamente.'],
        'patient.required' => ['type' => 'warning', 'message' => 'Preencha o nome, CPF, telefone e a data de nascimento do paciente.'],
        'operation.failed' => ['type' => 'error', 'message' => 'Não foi possível concluir a ação. Tente novamente.']
    ];

    public static function catalog(): array {
        return self::MESSAGES;
    }

    public static function message(string $code): string {
        return self::MESSAGES[$code]['message'] ?? self::MESSAGES['operation.failed']['message'];
    }

    public static function discardSuccess(): void {
        $_SESSION['clinic_toasts'] = array_values(array_filter(
            $_SESSION['clinic_toasts'] ?? [],
            static fn($code) => is_string($code) && isset(self::MESSAGES[$code]) && self::MESSAGES[$code]['type'] !== 'success'
        ));
    }

    public static function add(string $code): void {
        if (!isset(self::MESSAGES[$code])) {
            throw new InvalidArgumentException('Mensagem de notificação desconhecida.');
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (in_array(self::MESSAGES[$code]['type'], ['warning', 'error'], true)) {
            self::discardSuccess();
        }
        if (!in_array($code, $_SESSION['clinic_toasts'] ?? [], true)) {
            $_SESSION['clinic_toasts'][] = $code;
        }
    }

    public static function consume(): array {
        $codes = $_SESSION['clinic_toasts'] ?? [];
        unset($_SESSION['clinic_toasts']);
        return array_values(array_filter($codes, static fn($code) => is_string($code) && isset(self::MESSAGES[$code])));
    }
}
