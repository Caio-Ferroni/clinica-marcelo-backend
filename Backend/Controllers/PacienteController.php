<?php
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Paciente.php';
require_once __DIR__ . '/../Models/Perfil.php';
require_once __DIR__ . '/../Models/Encaminhamento.php';
require_once __DIR__ . '/../Models/Agendamento.php';
require_once __DIR__ . '/../Models/Consulta.php';
require_once __DIR__ . '/../Auth/Auth.php';

class PacienteController {
    public Paciente $paciente;
    public Perfil $perfil;
    public Encaminhamento $encaminhamento;
    public Agendamento $agendamento;
    public Consulta $consulta;
    protected PDO $db;

    private function prepararModelos(): void {
        if (isset($this->db)) return;
        $this->db = Database::getConnection(true);
        $this->paciente = new Paciente;
        $this->perfil = new Perfil;
        $this->encaminhamento = new Encaminhamento;
        $this->agendamento = new Agendamento;
        $this->consulta = new Consulta;
    }

    private function falhar(string $code, int $status): void {
        http_response_code($status);
        Toast::discardSuccess();
        $erro = Toast::message($code);
        $secao = 'patients';
        $voltar = '/pacientes';
        require __DIR__ . '/../../Views/Shared/error.php';
    }

    public function index(): void {
        try {
            $this->prepararModelos();
            $pacientes = $this->paciente->listarTodos();
        } catch (PDOException $e) {
            $this->falhar('patient.load_failed', 503);
            return;
        }
        require __DIR__ . '/../../Views/Paciente/paciente.php';
    }

    private function carregarPaciente($id): ?array {
        $this->prepararModelos();
        $paciente = $this->paciente->visualizarPaciente($id)[0] ?? null;
        if ($paciente === null) return null;
        return [
            'paciente' => $paciente,
            'perfil' => $this->perfil->buscarPorPaciente($id),
            'agendamento' => $this->agendamento->buscarPorPaciente($id),
            'encaminhamento' => $this->encaminhamento->buscarPorPaciente($id)
        ];
    }

    public function visualizar($id): void {
        try {
            $dados = $this->carregarPaciente($id);
        } catch (PDOException $e) {
            $this->falhar('patient.load_failed', 503);
            return;
        }
        if ($dados === null) {
            $this->falhar('patient.missing', 404);
            return;
        }
        extract($dados);
        require __DIR__ . '/../../Views/Paciente/paciente-show.php';
    }

    private function formularioEdicao(array $dados, ?string $erro = null): void {
        extract($dados);
        $toastType = 'warning';
        $pacientesSelecao = array_map(static fn($p) => [
            'id_paciente' => $p['id_paciente'], 'nome' => $p['nome'], 'cpf' => $p['cpf'] ?? ''
        ], $this->paciente->listarTodos());
        require __DIR__ . '/../../Views/Paciente/paciente-update.php';
    }

    public function editar($id): void {
        try {
            $dados = $this->carregarPaciente($id);
            if ($dados === null) {
                $this->falhar('patient.missing', 404);
                return;
            }
            $this->formularioEdicao($dados);
        } catch (PDOException $e) {
            $this->falhar('patient.load_failed', 503);
        }
    }

    private function valores(): array {
        $campos = ['nome', 'cpf', 'telefone', 'data_nascimento', 'status', 'quadro', 'descricao',
            'historico_paciente', 'tipo_encaminhamento', 'dia_semana', 'repeticao',
            'horario_inicio', 'horario_termino', 'data_inicio'];
        $valores = [];
        foreach ($campos as $campo) {
            $valores[$campo] = is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
        }
        return $valores;
    }

    private function validar(array $v): ?string {
        foreach (['nome', 'cpf', 'telefone', 'data_nascimento'] as $campo) {
            if ($v[$campo] === '') return 'patient.required';
        }
        $data = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $v['data_nascimento']) ? DateTimeImmutable::createFromFormat('!Y-m-d', $v['data_nascimento']) : false;
        if (!$data || $data->format('Y-m-d') !== $v['data_nascimento']) return 'patient.date_invalid';
        if ($v['status'] !== '' && !in_array($v['status'], ['Ativo', 'Inativo'], true)) return 'validation.fields';
        $grupos = [
            ['quadro', 'descricao'], ['historico_paciente', 'tipo_encaminhamento'],
            ['dia_semana', 'repeticao', 'horario_inicio', 'horario_termino', 'data_inicio']
        ];
        foreach ($grupos as $grupo) {
            $preenchidos = count(array_filter($grupo, static fn($campo) => $v[$campo] !== ''));
            if ($preenchidos > 0 && $preenchidos < count($grupo)) return 'patient.optional_required';
        }
        if ($v['tipo_encaminhamento'] !== '' && !in_array($v['tipo_encaminhamento'], ['Recebimento', 'Envio'], true)) return 'validation.fields';
        if ($v['dia_semana'] !== '') {
            $inicio = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $v['data_inicio']) ? DateTimeImmutable::createFromFormat('!Y-m-d', $v['data_inicio']) : false;
            $horaValida = static fn($hora) => preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9](?::[0-5][0-9])?$/D', $hora);
            if (!in_array($v['dia_semana'], ['0','1','2','3','4','5','6'], true)
                || !in_array($v['repeticao'], ['Semanal', 'Quinzenal', 'Mensal'], true)
                || !$inicio || $inicio->format('Y-m-d') !== $v['data_inicio']
                || !$horaValida($v['horario_inicio']) || !$horaValida($v['horario_termino'])
                || substr($v['horario_termino'], 0, 5) <= substr($v['horario_inicio'], 0, 5)) return 'patient.schedule_invalid';
        }
        return null;
    }

    private function salvarAssociacoes($id, array $v, bool $editar): array {
        if ($v['quadro'] !== '') {
            $metodo = $editar ? 'atualizarPerfil' : 'criarPerfil';
            $this->perfil->$metodo($id, $v['quadro'], $v['descricao']);
        }
        if ($v['tipo_encaminhamento'] !== '') {
            $metodo = $editar ? 'atualizarEncaminhamento' : 'criarEncaminhamento';
            $this->encaminhamento->$metodo($id, $v['historico_paciente'], $v['tipo_encaminhamento']);
        }
        if ($v['dia_semana'] !== '') {
            $metodo = $editar ? 'atualizarAgendamento' : 'criarAgendamento';
            $this->agendamento->$metodo($id, $v['dia_semana'], $v['repeticao'], $v['horario_inicio'], $v['horario_termino'], $v['data_inicio']);
            return $this->consulta->gerarConsultas((int) $id);
        }
        return ['criadas' => 0, 'existentes' => 0, 'conflitos' => [], 'agendamentos_invalidos' => []];
    }

    private function dadosEnviados(array $dados, array $valores): array {
        $dados['paciente'] = array_replace($dados['paciente'], array_intersect_key($valores, array_flip(['nome', 'cpf', 'telefone', 'data_nascimento', 'status'])));
        foreach (['perfil' => ['quadro', 'descricao'], 'encaminhamento' => ['tipo_encaminhamento', 'historico_paciente'], 'agendamento' => ['dia_semana', 'repeticao', 'horario_inicio', 'horario_termino', 'data_inicio']] as $grupo => $campos) {
            $dados[$grupo] = array_replace($dados[$grupo], array_intersect_key($valores, array_flip($campos), $_POST));
        }
        return $dados;
    }

    public function criar(): void {
        $valores = $this->valores();
        $code = Auth::validarCsrf($_POST['csrf_token'] ?? null) ? $this->validar($valores) : 'validation.form_expired';
        if ($code !== null) {
            http_response_code($code === 'validation.form_expired' ? 403 : 422);
            Toast::discardSuccess();
            $erro = Toast::message($code);
            require __DIR__ . '/../../Views/Paciente/paciente-create.php';
            return;
        }
        try {
            $this->prepararModelos();
            [$id, $geracao] = AgendaTransacao::executar($this->db, function () use ($valores) {
                $id = $this->paciente->criarPaciente($valores['nome'], $valores['cpf'], $valores['telefone'], $valores['data_nascimento']);
                return [$id, $this->salvarAssociacoes($id, $valores, false)];
            });
        } catch (ConflitoAgendaException | AgendaOcupadaException | InvalidArgumentException $e) {
            http_response_code($e instanceof ConflitoAgendaException ? 409 : ($e instanceof AgendaOcupadaException ? 503 : 422));
            Toast::discardSuccess();
            $toastType = 'warning';
            $erro = $e instanceof InvalidArgumentException ? Toast::message('patient.schedule_invalid') : $e->getMessage();
            require __DIR__ . '/../../Views/Paciente/paciente-create.php';
            return;
        } catch (PDOException $e) {
            if (isset($this->db) && $this->db->inTransaction()) $this->db->rollBack();
            http_response_code(500);
            Toast::discardSuccess();
            $toastType = 'error';
            $erro = Toast::message('patient.save_failed');
            require __DIR__ . '/../../Views/Paciente/paciente-create.php';
            return;
        }
        Toast::add('patient.created');
        if ($geracao['criadas'] > 0) Toast::add('patient-consultation.generated');
        header('Location: /pacientes/' . $id, true, 303);
        exit;
    }

    public function atualizar($id): void {
        try {
            $dados = $this->carregarPaciente($id);
            if ($dados === null) {
                $this->falhar('patient.missing', 404);
                return;
            }
            $v = $this->valores();
            $code = Auth::validarCsrf($_POST['csrf_token'] ?? null) ? $this->validar($v) : 'validation.form_expired';
            if ($code !== null) {
                if ($code !== 'validation.form_expired') {
                    $dados = $this->dadosEnviados($dados, $v);
                }
                Toast::discardSuccess();
                http_response_code($code === 'validation.form_expired' ? 403 : 422);
                $this->formularioEdicao($dados, Toast::message($code));
                return;
            }
            $geracao = AgendaTransacao::executar($this->db, function () use ($id, $v) {
                $this->paciente->atualizarPaciente($id, $v['nome'], $v['cpf'], $v['telefone'], $v['data_nascimento']);
                if ($v['status'] !== '') $this->paciente->atualizarStatus($id, $v['status']);
                return $this->salvarAssociacoes($id, $v, true);
            });
        } catch (ConflitoAgendaException | AgendaOcupadaException | InvalidArgumentException $e) {
            http_response_code($e instanceof ConflitoAgendaException ? 409 : ($e instanceof AgendaOcupadaException ? 503 : 422));
            Toast::discardSuccess();
            $erro = $e instanceof InvalidArgumentException ? Toast::message('patient.schedule_invalid') : $e->getMessage();
            $this->formularioEdicao($this->dadosEnviados($dados, $v), $erro);
            return;
        } catch (PDOException $e) {
            if (isset($this->db) && $this->db->inTransaction()) $this->db->rollBack();
            $this->falhar('patient.save_failed', 500);
            return;
        }
        Toast::add('patient.updated');
        if ($geracao['criadas'] > 0) Toast::add('patient-consultation.generated');
        header('Location: /pacientes/' . $id, true, 303);
        exit;
    }
}
