<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login-admin.php');
    exit;
}

// Buscar estatísticas por cliente
$stmt = $pdo->query("
    SELECT c.id, c.nome,
        COUNT(m.id) AS total_mensagens,
        SUM(CASE WHEN m.resposta_de IS NULL THEN 1 ELSE 0 END) AS enviadas,
        SUM(CASE WHEN m.resposta_de IS NOT NULL THEN 1 ELSE 0 END) AS respondidas
    FROM clientes c
    LEFT JOIN mensagens m ON m.remetente_id = c.id
    GROUP BY c.id, c.nome
    ORDER BY total_mensagens DESC
");
$estatisticas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Estatísticas de Mensagens</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { background-color: #f4f6f8; font-family: sans-serif; margin: 0; }
        .header, .footer {
            background-color: #0078d4;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #0078d4;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #0078d4;
            color: white;
        }
        .nav-buttons {
            text-align: center;
            margin-bottom: 20px;
        }
        .nav-buttons a {
            background-color: #0078d4;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px;
            display: inline-block;
        }
        @media (max-width: 768px) {
            th, td { font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Estatísticas de Mensagens por Cliente</h1>
    </div>

    <div class="container">
        <div class="nav-buttons">
            <a href="dashboard.php">🔙 Voltar</a>
            <a href="mensagens.php">💬 Ver Mensagens</a>
        </div>

        <h2>Resumo por Cliente</h2>
        <table>
            <tr>
                <th>Cliente</th>
                <th>Total de Mensagens</th>
                <th>Enviadas</th>
                <th>Respondidas</th>
            </tr>
            <?php foreach ($estatisticas as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['nome']) ?></td>
                    <td><?= $e['total_mensagens'] ?></td>
                    <td><?= $e['enviadas'] ?></td>
                    <td><?= $e['respondidas'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="footer">
        &copy; <?= date('Y') ?> Infoservice. Todos os direitos reservados.
    </div>
</body>
</html>
