<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste</title>
</head>
<body>
    <?php
    include __DIR__ .  "/Backend/Database.php";
    $sql = 'SELECT * FROM paciente;';
    $resultado = $conexao->query($sql)->fetch_assoc();
    if(!isset($resultado)) {
        echo "Ocorreu um Erro";
    }
    var_dump($resultado);
    ?>
    
</body>
</html>