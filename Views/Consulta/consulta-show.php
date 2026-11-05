<?php
// teste.php

// 1. Liga a exibição de erros do PHP para facilitar o diagnóstico
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Inclui o Controller
require_once __DIR__ . '/../../Backend/Models/Consulta.php';

echo "<h2>Testando o ConsultaController...</h2>";

try {
    // 3. Instancia o Controller (isso acionará o __construct do Controller e do Model)
    $controller = new Consulta();

    // 4. Executa a ação index() que busca os dados e inclui a View
    $controller->gerarConsultas();

} catch (Exception $e) {
    echo "<p style='color: red;'>Erro durante o teste: " . $e->getMessage() . "</p>";
}