<?php
session_start();

$user_type = 'visitante';
$user_name = '';
$login_url = 'login.php';

if (isset($_SESSION['admin_id'])) {
    $user_type = 'admin';
    $user_name = $_SESSION['admin_nome'] ?? 'Administrador';
    $login_url = 'login-admin.php';
} elseif (isset($_SESSION['cliente_id'])) {
    $user_type = 'cliente';
    $user_name = $_SESSION['cliente_nome'] ?? 'Cliente';
    $login_url = 'login.php';
}

session_unset();
session_destroy();

if (isset($_GET['ajax']) || $user_type === 'visitante') {
    header('Location: ' . $login_url);
    exit;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessão encerrada - Infoservice Helpdesk</title>
    <link rel="stylesheet" href="../assets/css/helpdesk-pro.css">
</head>
<body>
    <main class="hd-logout-shell">
        <section class="hd-logout-card">
            <div class="hd-logout-brand">
                <div class="hd-brand-icon">✓</div>
                <div>
                    <strong>Infoservice Helpdesk</strong>
                    <span>Atendimento técnico profissional</span>
                </div>
            </div>

            <div class="hd-logout-status">Sessão encerrada com segurança</div>
            <h1>Até logo<?= $user_name ? ', ' . h($user_name) : '' ?>!</h1>
            <p>
                Seu acesso foi finalizado corretamente. Para continuar acompanhando chamados,
                financeiro, mensagens ou agenda, faça login novamente.
            </p>

            <div class="hd-logout-actions">
                <a class="hd-btn hd-btn-primary" href="<?= h($login_url) ?>">🔐 Entrar novamente</a>
                <a class="hd-btn hd-btn-outline" href="../index.php">🏠 Ir para o site</a>
            </div>

            <div class="hd-logout-countdown">
                Redirecionamento automático em <strong id="countdown">8</strong>s
            </div>
        </section>
    </main>

    <script>
        let seconds = 8;
        const target = <?= json_encode($login_url) ?>;
        const el = document.getElementById('countdown');
        const timer = setInterval(function () {
            seconds--;
            el.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = target;
            }
        }, 1000);
    </script>
</body>
</html>
