<?php
require_once __DIR__ . '/../config/Database.php';
    class Consulta{
        protected PDO $db;

        public function __construct() {
            $this->db = Database::getConnection();
        }

        public function listarTodos(){
            $sql = 'SELECT * FROM consulta;';
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        }

        public function gerarConsultas(){
            $mes = localtime(time()-9000, TRUE)['tm_mon'] + 1;
            $ano = localtime(time()-9000, TRUE)['tm_year'] + 1900;
            $stmt = $this->db->query("SELECT * FROM agendamento");
            $agendamentos = $stmt->fetchAll();

            foreach($agendamentos as $agendamento){
                $diaDaSemana = $agendamento['dia_semana'];
                $repeticao = $agendamento['repeticao'];
                $datasConsultas = self::encontrarDatas($mes, $ano, $diaDaSemana, $repeticao);
                foreach($datasConsultas as $dataConsultada){
                    $idAgendamento = $agendamento['id_agendamento'];
                    $horarioInicio = $agendamento["horario_inicio"];
                    $horarioFim = $agendamento["horario_termino"];
                    $sql = "INSERT INTO consulta(id_agendamento, horario_inicio, horario_fim, data) VALUES(:idAgendamento, :horarioInicio, :horarioFim, :dataConsultada)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        ':idAgendamento' => $idAgendamento,
                        ':horarioInicio' => $horarioInicio,
                        ':horarioFim' => $horarioFim,
                        ':dataConsultada' => $dataConsultada                      
                    ]);
                    

                    
                }    
            }

        }

        public function visualizarCalendario(){

        }

        public function visualizarConsulta($id){
            $sql = "SELECT * FROM consulta WHERE id_consulta = :id";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([
                ':id' => $id
            ]);
            return $stmt->fetchAll();
        }

        public function alterarStatus($id){

        }

        public function enviarNotificação(){

        }
        public function gerarRelatorio(){

        }

        public function encontrarDatas($ano, $mes, $diaAlvo, $repeticao){
            $datas = [];
            $primeiroDia = new Datetime("$mes-$ano-01"); 
            $ultimoDia = (clone $primeiroDia)->modify('last day of this month');
            $cursor = (clone $primeiroDia);
            while ($cursor->format('w') !== $diaAlvo){
                $cursor->modify('+1 day');
            }

            $diaSemanalCursor = $cursor->format('w');
            while($cursor <= $ultimoDia){
                

                if($repeticao === 'Semanal' && $diaSemanalCursor === $diaAlvo){
                    $diaMesAno = (array) $cursor;
                    array_push($datas, substr($diaMesAno['date'], 0, 10));
                    $cursor->modify('+1 week');
                }   
                
                if($repeticao === 'Quinzenal' && $diaSemanalCursor === $diaAlvo){
                    $diaMesAno = (array) $cursor;
                    array_push($datas, substr($diaMesAno['date'], 0, 10));
                    $cursor->modify('+2 weeks');
                }

                if($repeticao === 'Mensal' && $diaSemanalCursor === $diaAlvo){
                    $diaMesAno = (array) $cursor;
                    array_push($datas, substr($diaMesAno['date'], 0, 10));
                    $cursor->modify('+1 month');
                }
            }
            return $datas;
            
        }
    }
?>