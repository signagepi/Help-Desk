<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require '../config/db.php';
if (isset($_SESSION['cliente_id'])) { header('Location: ../views/cliente/dashboard.php'); exit; }
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    if ($email === '' || $senha === '') { $erro = 'Preencha email e senha.'; }
    else {
        try {
            $stmt = $pdo->prepare('SELECT id, senha FROM clientes WHERE email = ?');
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['cliente_id'] = (int)$usuario['id'];
                header('Location: ../views/cliente/dashboard.php'); exit;
            }
            $erro = 'Email ou senha inválidos.';
        } catch (Throwable $e) { $erro = 'Erro ao acessar o sistema.'; }
    }
}
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Portal do Cliente - Infoservice</title><link rel="stylesheet" href="../assets/css/helpdesk-pro.css"></head>
<body><div class="hd-login-shell">
<section class="hd-login-hero"><div class="hd-brand" style="margin-bottom:28px"><div class="hd-brand-icon">🛠️</div><div><h1 style="font-size:24px">Infoservice Helpdesk</h1><p>Portal de atendimento técnico</p></div></div><h1>Atendimento claro, rápido e profissional.</h1><p>Acompanhe seus chamados, mensagens, financeiro e agenda de visitas em um ambiente moderno e organizado.</p><div class="hd-login-benefits"><div>✅ Protocolo automático e histórico de atendimento</div><div>💬 Comunicação direta com o técnico</div><div>📅 Calendário de visitas e serviços</div></div></section>
<section class="hd-login-box"><div class="hd-login-card"><h2>Entrar como cliente</h2><p>Acesse sua central de serviços da Infoservice.</p><?php if ($erro): ?><div class="hd-alert hd-alert-error"><?= h($erro) ?></div><?php endif; ?><form method="POST" id="loginForm" class="hd-form-grid"><div class="hd-field hd-field-full"><label class="hd-label">Email</label><input class="hd-input" type="email" name="email" id="email" required autocomplete="email" placeholder="cliente@email.com"></div><div class="hd-field hd-field-full"><label class="hd-label">Senha</label><input class="hd-input" type="password" name="senha" id="senha" required autocomplete="current-password" placeholder="Sua senha"></div><div class="hd-field hd-field-full"><button class="hd-btn hd-btn-primary" type="submit" id="submitBtn">→ Acessar portal</button></div></form><div class="hd-actions" style="margin-top:16px;justify-content:space-between"><a class="hd-btn hd-btn-outline" href="../index.php">← Página inicial</a><a class="hd-btn hd-btn-light" href="login-admin.php">Sou técnico</a></div></div></section>
</div><script>document.getElementById('loginForm').addEventListener('submit',()=>{document.getElementById('submitBtn').textContent='Acessando...'});document.getElementById('email').focus();</script></body></html>