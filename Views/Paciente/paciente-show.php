<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form method="POST">
        <input type="text" name="nome">nome
        <input type="text" name="cpf">cpf
        <input type="text" name="telefone">telefone
        <input type="date" name="data_nascimento">nascimento

        <input type="text" name="quadro">quadro
        <input type="text" name="descricao">descricao
        

        <input type="text" name="dia_semana">dia da semana
        <input type="text" name="repeticao">repeticao
        <input type="time" name="horario_inicio">inicio
        <input type="time" name="horario_fim">fim
        <input type="date" name="data_inicio">data de inicio

        <input type="text" name="historico_paciente">historico
        <input type="text" name="tipo_encaminhamento">tipo

        <button type="submit">enviar a bomba</button>
    </form>
</body>
</html>
<?php 
    echo $e;
?>