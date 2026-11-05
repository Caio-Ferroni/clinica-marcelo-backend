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
            $consultasCriadas = 0;

            foreach($agendamentos as $agendamento){
                $diaDaSemana = $agendamento['dia_semana'];
                $repeticao = $agendamento['repeticao'];
                $datasConsultas = self::encontrarDatas($mes, $ano, $diaDaSemana, $repeticao);
                

                foreach($datasConsultas as $data_consultada){
                    $id_agendamento = $agendamento['id_agendamento'];
                    $horario_inicio = $agendamento["horario_inicio"];
                    $horario_fim = $agendamento["horario_termino"];

                    if(!self::consultaExiste($id_agendamento, $data_consultada, $horario_inicio)){
                        $sql = "INSERT INTO consulta(id_agendamento, horario_inicio, horario_fim, data) VALUES(:id_agendamento, :horario_inicio, :horario_fim, :data_consultada)";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([
                        ':id_agendamento' => $id_agendamento,
                        ':horario_inicio' => $horario_inicio,
                        ':horario_fim' => $horario_fim,
                        ':data_consultada' => $data_consultada  
                               
                    ]);
                    $consultasCriadas++;  
                    }
                   
                }    
            }
            return $consultasCriadas;

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

        private static function consultaExiste($id_agendamento, $data_consultada, $horario_inicio): bool {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM consulta 
                WHERE id_agendamento = :id_agendamento AND data = :data AND horario_inicio = :horario_inicio
            ");
            $stmt->execute([
                ':id_agendamento' => $id_agendamento,
                ':data'      => $data_consultada,
                ':horario_inicio'      => $horario_inicio

            ]);

            return $stmt->fetchColumn() > 0;
        }
    }
?>