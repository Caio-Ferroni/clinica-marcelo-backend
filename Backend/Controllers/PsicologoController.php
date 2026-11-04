<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../Models/Psicologo.php';

    class PsicologoController{
        public Psicologo $psicologo;
        public function __construct()
        {
            $this->psicologo = new Psicologo;
        }
    }
?>