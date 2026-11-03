<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Clínica Psicológica</title>
</head>
<body>
    <h1>Dashboard - Lista de Pacientes</h1>

    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pacientes as $paciente): ?>
                <tr>
                    <td><?= htmlspecialchars($paciente['id_paciente']) ?></td>
                    <td><?= htmlspecialchars($paciente['nome']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>