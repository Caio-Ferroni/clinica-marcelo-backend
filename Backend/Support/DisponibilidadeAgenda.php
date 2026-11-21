<?php
require_once __DIR__ . '/Agenda.php';
require_once __DIR__ . '/RecorrenciaConsulta.php';

final class DisponibilidadeAgenda {
    public function __construct(private PDO $db) {}

    private function normalizarHora(string $hora): string {
        if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9](?::[0-5][0-9])?$/D', $hora)) throw new InvalidArgumentException('Horário inválido.');
        return strlen($hora) === 5 ? $hora . ':00' : $hora;
    }

    private function minutos(string $hora): int {
        $hora = $this->normalizarHora($hora);
        return (int) substr($hora, 0, 2) * 60 + (int) substr($hora, 3, 2);
    }

    private function hora(int $minutos): string {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }

    private function datas(array $agenda, DateTimeImmutable $de, DateTimeImmutable $ate): array {
        if (!in_array((string) ($agenda['dia_semana'] ?? ''), ['0', '1', '2', '3', '4', '5', '6'], true)) throw new InvalidArgumentException('Dia inválido.');
        return RecorrenciaConsulta::datas((string) ($agenda['data_inicio'] ?? ''), (int) $agenda['dia_semana'], (string) ($agenda['repeticao'] ?? ''), $de, $ate);
    }

    private function ocupacoes(DateTimeImmutable $de, DateTimeImmutable $ate, int $idAgendamento): array {
        $stmt = $this->db->prepare('SELECT c.id_consulta, c.id_agendamento, c.data, c.horario_inicio, c.horario_fim, c.status, a.id_paciente, p.nome AS paciente_nome
            FROM consulta c LEFT JOIN agendamento a ON a.id_agendamento = c.id_agendamento
            LEFT JOIN paciente p ON p.id_paciente = a.id_paciente WHERE c.data BETWEEN ? AND ?');
        $stmt->execute([$de->format('Y-m-d'), $ate->format('Y-m-d')]);
        $ocupacoes = [];
        $existentes = [];
        foreach ($stmt->fetchAll() as $consulta) {
            $existentes[$consulta['id_agendamento'] . '|' . $consulta['data'] . '|' . $this->normalizarHora($consulta['horario_inicio'])] = true;
            if ($consulta['status'] !== 'Cancelado') $ocupacoes[$consulta['data']][] = $consulta;
        }
        $stmt = $this->db->query("SELECT a.*, p.nome AS paciente_nome FROM agendamento a INNER JOIN paciente p ON p.id_paciente = a.id_paciente WHERE p.status = 'Ativo' ORDER BY a.id_agendamento");
        foreach ($stmt->fetchAll() as $agenda) {
            if ((int) $agenda['id_agendamento'] === $idAgendamento) continue;
            try {
                $inicio = $this->normalizarHora((string) $agenda['horario_inicio']);
                $fim = $this->normalizarHora((string) $agenda['horario_termino']);
                if ($fim <= $inicio) continue;
                $datas = $this->datas($agenda, $de, $ate);
            } catch (InvalidArgumentException $e) {
                continue;
            }
            foreach ($datas as $data) {
                if (isset($existentes[$agenda['id_agendamento'] . '|' . $data . '|' . $inicio])) continue;
                $ocupacoes[$data][] = [
                    'id_consulta' => null, 'id_agendamento' => (int) $agenda['id_agendamento'],
                    'id_paciente' => (int) $agenda['id_paciente'], 'paciente_nome' => $agenda['paciente_nome'],
                    'data' => $data, 'horario_inicio' => $inicio, 'horario_fim' => $fim
                ];
            }
        }
        return $ocupacoes;
    }

    private function avaliar(array $datas, string $inicio, string $fim, array $ocupacoes, int $idAgendamento, DateTimeImmutable $agora): array {
        $inicio = $this->normalizarHora($inicio);
        $fim = $this->normalizarHora($fim);
        $futuras = [];
        foreach ($datas as $data) {
            if (new DateTimeImmutable($data . ' ' . $inicio, $agora->getTimezone()) <= $agora) continue;
            $futuras[] = $data;
            foreach ($ocupacoes[$data] ?? [] as $ocupacao) {
                if ($idAgendamento > 0 && (int) $ocupacao['id_agendamento'] === $idAgendamento && $this->normalizarHora($ocupacao['horario_inicio']) === $inicio) continue;
                if ($inicio < $this->normalizarHora($ocupacao['horario_fim']) && $fim > $this->normalizarHora($ocupacao['horario_inicio'])) return ['datas' => $futuras, 'conflito' => $ocupacao];
            }
        }
        return ['datas' => $futuras, 'conflito' => null];
    }

    public function validar(array $agenda, int $idAgendamento = 0, ?DateTimeImmutable $agora = null): void {
        $agora = ($agora ?? new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        $fim = $agora->modify('last day of this month');
        $inicio = $this->normalizarHora((string) $agenda['horario_inicio']);
        $termino = $this->normalizarHora((string) $agenda['horario_termino']);
        if ($termino <= $inicio) throw new InvalidArgumentException('Intervalo inválido.');
        $datas = $this->datas($agenda, $agora, $fim);
        $avaliacao = $this->avaliar($datas, $inicio, $termino, $this->ocupacoes($agora, $fim, $idAgendamento), $idAgendamento, $agora);
        if ($avaliacao['conflito'] !== null) throw new ConflitoAgendaException($avaliacao['conflito']);
    }

    public function sugerir(array $agenda, string $de, string $ate, int $duracao, int $idAgendamento = 0, ?DateTimeImmutable $agora = null): array {
        $inicioBusca = $this->minutos($de);
        $fimBusca = $this->minutos($ate);
        if ($duracao < 1 || $duracao > 720 || $fimBusca <= $inicioBusca || $fimBusca - $inicioBusca < $duracao) throw new InvalidArgumentException('Faixa inválida.');
        $agora = ($agora ?? new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        $fim = $agora->modify('last day of this month');
        $datas = $this->datas($agenda, $agora, $fim);
        if ((string) $agenda['dia_semana'] === '0') return ['sugestoes' => [], 'motivo' => 'domingo', 'atual' => null];
        $limite = $agora;
        if (!$datas || (count($datas) === 1 && $datas[0] === $agora->format('Y-m-d') && $this->hora($fimBusca - $duracao) <= $agora->format('H:i'))) {
            $limite = max($agora->modify('first day of next month')->setTime(0, 0), new DateTimeImmutable($agenda['data_inicio'], $agora->getTimezone()));
            $fim = $limite->modify('last day of this month');
            $datas = $this->datas($agenda, $limite, $fim);
            if (!$datas) {
                $fim = $fim->modify('last day of next month');
                $datas = $this->datas($agenda, $limite, $fim);
            }
        }
        $ocupacoes = $this->ocupacoes($limite, $fim, $idAgendamento);
        $atual = null;
        $preferido = $inicioBusca;
        if (!empty($agenda['horario_inicio']) && !empty($agenda['horario_termino'])) {
            $preferido = $this->minutos($agenda['horario_inicio']);
            $termino = $this->minutos($agenda['horario_termino']);
            if ($termino <= $preferido) throw new InvalidArgumentException('Intervalo inválido.');
            $avaliacao = $this->avaliar($datas, $agenda['horario_inicio'], $agenda['horario_termino'], $ocupacoes, $idAgendamento, $agora);
            $atual = ['livre' => $avaliacao['conflito'] === null && count($avaliacao['datas']) > 0, 'mensagem' => $avaliacao['conflito'] === null ? null : (new ConflitoAgendaException($avaliacao['conflito']))->getMessage()];
        }
        $candidatos = range($inicioBusca, $fimBusca - $duracao, 15);
        foreach ($ocupacoes as $dia) {
            foreach ($dia as $ocupacao) {
                $finalOcupado = $this->normalizarHora($ocupacao['horario_fim']);
                $apos = $this->minutos($finalOcupado) + (substr($finalOcupado, 6, 2) !== '00' ? 1 : 0);
                if ($apos >= $inicioBusca && $apos + $duracao <= $fimBusca) $candidatos[] = $apos;
            }
        }
        if ($preferido >= $inicioBusca && $preferido + $duracao <= $fimBusca) $candidatos[] = $preferido;
        $candidatos = array_values(array_unique($candidatos));
        usort($candidatos, static fn($a, $b) => [abs($a - $preferido), $a] <=> [abs($b - $preferido), $b]);
        $sugestoes = [];
        foreach ($candidatos as $minutos) {
            $inicio = $this->hora($minutos);
            $termino = $this->hora($minutos + $duracao);
            $avaliacao = $this->avaliar($datas, $inicio, $termino, $ocupacoes, $idAgendamento, $agora);
            if ($avaliacao['conflito'] !== null || !$avaliacao['datas']) continue;
            $sugestoes[] = ['inicio' => $inicio, 'fim' => $termino, 'datas' => $avaliacao['datas']];
            if (count($sugestoes) >= 6) break;
        }
        return ['sugestoes' => $sugestoes, 'atual' => $atual, 'de' => $limite->format('Y-m-d'), 'ate' => $fim->format('Y-m-d'), 'motivo' => $sugestoes ? null : 'sem_horarios'];
    }
}
