<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login-admin.php');
    exit;
}

$stmt = $pdo->query("
    SELECT l.*, a.nome AS admin_nome
    FROM log_acoes l
    JOIN administradores a ON l.usuario_id = a.id
    ORDER BY l.data DESC
    LIMIT 100
");
$logs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Ações</title>
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
        <h1>🧾 Histórico de Ações Realizadas</h1>
    </div>

    <div class="container">
        <div class="nav-buttons">
            <a href="dashboard.php">🔙 Voltar</a>
        </div>

        <h2>Últimas Ações</h2>
        <table>
            <tr>
                <th>Admin</th>
                <th>Tipo</th>
                <th>Descrição</th>
                <th>Data</th>
            </tr>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?= htmlspecialchars($l['admin_nome']) ?></td>
                    <td><?= htmlspecialchars($l['tipo']) ?></td>
                    <td><?= htmlspecialchars($l['descricao']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($l['data'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="footer">
        &copy; <?= date('Y') ?> Infoservice. Todos os direitos reservados.
    </div>
</body>
</html>
