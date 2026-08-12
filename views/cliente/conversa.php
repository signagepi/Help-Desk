<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require '../../config/db.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: ../../auth/login-cliente.php');
    exit;
}

$cliente_id = $_SESSION['cliente_id'];
$admin_id = 1; // ID fixo do administrador principal

// Buscar nome do admin
$stmt = $pdo->prepare("SELECT nome FROM administradores WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

// Buscar histórico de mensagens entre cliente e admin
$stmt = $pdo->prepare("
    SELECT m.*, 
           CASE 
               WHEN m.remetente_id = ? THEN 'cliente' 
               ELSE 'admin' 
           END AS origem
    FROM mensagens m
    WHERE (remetente_id = ? AND destinatario_id = ?) 
       OR (remetente_id = ? AND destinatario_id = ?)
    ORDER BY m.data ASC
");
$stmt->execute([$cliente_id, $cliente_id, $admin_id, $admin_id, $cliente_id]);
$mensagens = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Conversa com <?= htmlspecialchars($admin['nome']) ?></title>
    <link rel="stylesheet" href="../../assets/style.css">
    <style>
        body { background-color: #f4f6f8; font-family: sans-serif; }
        .header, .footer {
            background-color: #0078d4;
            color: white;
            padding: 15px;
            text-align: center;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
        }
        .chat-box {
            background: white;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .mensagem {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
            max-width: 80%;
        }
        .cliente {
            background-color: #e0e0e0;
            text-align: left;
        }
        .admin {
            background-color: #0078d4;
            color: white;
            text-align: right;
            margin-left: auto;
        }
        .mensagem small {
            display: block;
            font-size: 12px;
            margin-top: 5px;
            color: #555;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            resize: vertical;
        }
        button {
            padding: 10px 15px;
            background-color: #0078d4;
            color: white;
            border: none;
            border-radius: 4px;
            margin-top: 10px;
            cursor: pointer;
        }
        button:hover {
            background-color: #005fa3;
        }
        .cookie-banner {
            background-color: #f0f0f0;
            padding: 10px;
            text-align: center;
            font-size: 14px;
            border-top: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Conversa com <?= htmlspecialchars($admin['nome']) ?></h1>
    </div>

    <div class="container">
        <div class="chat-box">
            <?php foreach ($mensagens as $m): ?>
                <div class="mensagem <?= $m['origem'] ?>">
                    <?= nl2br(htmlspecialchars($m['mensagem'])) ?>
                    <small><?= date('d/m/Y H:i', strtotime($m['data'])) ?></small>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" action="../../controllers/responder-mensagem.php">
            <input type="hidden" name="destinatario_id" value="<?= $admin_id ?>">
            <textarea name="mensagem" rows="4" placeholder="Digite sua mensagem..." required></textarea>
            <button type="submit">Enviar</button>
        </form>
    </div>

    <div class="cookie-banner">
        Este site utiliza cookies para melhorar sua experiência. Ao continuar, você concorda com nossa política.
    </div>

    <div class="footer">
        &copy; <?= date('Y') ?> Infoservice. Todos os direitos reservados.
    </div>
        <!-- No final do arquivo, antes do </body> -->
<?php include '../../components/cookie-banner.php'; ?>
</body>
</html>
