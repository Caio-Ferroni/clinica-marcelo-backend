<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste</title>
</head>
<body>
    <?php
    include __DIR__ . "/Backend/Database.php";

    $sql = 'SELECT * FROM Paciente';
    $result = pg_query($connection, $sql);
    if ($result === false) {
    die('Erro na consulta: ' . pg_last_error($connection));
}

echo 'Registros encontrados: ' . pg_num_rows($result) . '<br>';

while ($paciente = pg_fetch_assoc($result)) {
    echo '<pre>';
    print_r($paciente);
    echo '</pre>';
}
    ?>
    
</body>
</html>