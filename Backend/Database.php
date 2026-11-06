<?php
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "mydb";
$port = "3307";

$conexao = new mysqli(
    $servidor,
    $usuario,
    $senha,
    $banco,
    $port
);

if ($conexao->connect_error) {

    die(
        "Erro na conexão: " .
        $conexao->connect_error
    );

}
?>