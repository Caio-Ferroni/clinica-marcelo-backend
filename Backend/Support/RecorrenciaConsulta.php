<?php
final class RecorrenciaConsulta {
    public static function datas(string $dataInicio, int $diaSemana, string $repeticao, DateTimeImmutable $de, DateTimeImmutable $ate): array {
        $fuso = new DateTimeZone('America/Sao_Paulo');
        $inicio = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $dataInicio)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $dataInicio, $fuso) : false;
        if (!$inicio || $inicio->format('Y-m-d') !== $dataInicio || (int) $inicio->format('Y') < 1
            || $diaSemana < 0 || $diaSemana > 6 || !in_array($repeticao, ['Semanal', 'Quinzenal', 'Mensal'], true)) {
            throw new InvalidArgumentException('Dados de recorrência inválidos.');
        }
        $de = $de->setTimezone($fuso)->setTime(0, 0);
        $ate = $ate->setTimezone($fuso)->setTime(0, 0);
        if ($de > $ate) return [];
        $primeira = $inicio->modify('+' . (($diaSemana - (int) $inicio->format('w') + 7) % 7) . ' days');
        $datas = [];
        if ($repeticao !== 'Mensal') {
            $intervalo = $repeticao === 'Semanal' ? 7 : 14;
            $cursor = $primeira;
            if ($cursor < $de) {
                $dias = (int) $cursor->diff($de)->format('%a');
                $cursor = $cursor->modify('+' . (intdiv($dias, $intervalo) * $intervalo) . ' days');
                if ($cursor < $de) $cursor = $cursor->modify('+' . $intervalo . ' days');
            }
            while ($cursor <= $ate) {
                $datas[] = $cursor->format('Y-m-d');
                $cursor = $cursor->modify('+' . $intervalo . ' days');
            }
            return $datas;
        }
        $posicao = intdiv((int) $primeira->format('j') - 1, 7) + 1;
        $mes = ($de > $primeira ? $de : $primeira)->modify('first day of this month');
        while ($mes <= $ate) {
            $deslocamento = ($diaSemana - (int) $mes->format('w') + 7) % 7;
            $cursor = $mes->modify('+' . ($deslocamento + ($posicao - 1) * 7) . ' days');
            if ($cursor->format('Y-m') !== $mes->format('Y-m')) $cursor = $cursor->modify('-7 days');
            if ($cursor >= $primeira && $cursor >= $de && $cursor <= $ate) $datas[] = $cursor->format('Y-m-d');
            $mes = $mes->modify('+1 month');
        }
        return $datas;
    }
}
