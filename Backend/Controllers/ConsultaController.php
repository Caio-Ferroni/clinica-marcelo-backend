<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../Models/Consulta.php';

    class ConsultaController{
        public Consulta $consulta;
        public function __construct()
        {
            $this->consulta = new Consulta;
        }

    }
?>