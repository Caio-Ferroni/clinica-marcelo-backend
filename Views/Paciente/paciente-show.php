<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form method="POST">
        <input type="text" name="nome">
        <input type="text" name="cpf">
        <input type="text" name="telefone">
        <input type="date" name="data_nascimento">
        <input type="text" name="status">
        <button type="submit">enviar a bomba</button>
    </form>
</body>
</html>
<?php 
    echo $mensagem;
?>