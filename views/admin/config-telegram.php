<?php
session_start();
require '../../config/db.php';
require_once '../../controllers/telegram-alertas-funcoes.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login-admin.php');
    exit;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function detectarBaseSistemaTelegram() {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = dirname(dirname(dirname($script)));
    $basePath = $basePath === DIRECTORY_SEPARATOR ? '' : $basePath;
    return $host ? $scheme . '://' . $host . rtrim(str_replace('\\', '/', $basePath), '/') : '';
}

telegramGarantirTabelas($pdo);
$config = telegramBuscarConfig($pdo);
if (empty($config['url_sistema'])) {
    $config['url_sistema'] = detectarBaseSistemaTelegram();
}

$success = $_SESSION['success_msg'] ?? '';
$error = $_SESSION['error_msg'] ?? '';
$chatsEncontrados = [];
$chatMsg = '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $notificarNovoChamado = isset($_POST['notificar_novo_chamado']) ? 1 : 0;
    $botToken = trim($_POST['bot_token'] ?? '');
    $chatIds = trim($_POST['chat_ids'] ?? '');
    $urlSistema = trim($_POST['url_sistema'] ?? '');

    try {
        if (isset($_POST['buscar_chats'])) {
            telegramSalvarConfig($pdo, $ativo, $botToken, $chatIds, $urlSistema, $notificarNovoChamado);
            $busca = telegramBuscarChatsPorToken($botToken);
            $chatMsg = $busca['message'];
            $chatsEncontrados = $busca['chats'];
            if (!$busca['ok']) {
                $error = 'Não foi possível buscar chats: ' . $busca['message'];
            }
            $config = telegramBuscarConfig($pdo);
        } elseif (isset($_POST['testar_alerta'])) {
            telegramSalvarConfig($pdo, $ativo, $botToken, $chatIds, $urlSistema, $notificarNovoChamado);
            $testeUrl = $urlSistema ? rtrim($urlSistema, '/') . '/views/admin/dashboard.php' : '';
            $resultado = telegramEnviarMensagem(
                $pdo,
                "✅ <b>Teste de alerta Infoservice</b>\n\nSe você recebeu esta mensagem, o técnico já pode receber aviso de novo chamado no celular.",
                $testeUrl,
                'Abrir painel',
                null
            );
            if ($resultado['ok']) {
                $_SESSION['success_msg'] = 'Configuração salva e alerta teste enviado com sucesso.';
            } else {
                $_SESSION['error_msg'] = 'Configuração salva, mas o teste falhou: ' . $resultado['message'];
            }
            header('Location: config-telegram.php');
            exit;
        } else {
            telegramSalvarConfig($pdo, $ativo, $botToken, $chatIds, $urlSistema, $notificarNovoChamado);
            $_SESSION['success_msg'] = 'Configuração do Telegram salva com sucesso.';
            header('Location: config-telegram.php');
            exit;
        }
    } catch (Throwable $e) {
        $error = 'Erro ao salvar configuração: ' . $e->getMessage();
    }
}

$config = telegramBuscarConfig($pdo);
if (empty($config['url_sistema'])) {
    $config['url_sistema'] = detectarBaseSistemaTelegram();
}

$logs = [];
try {
    $logs = $pdo->query("SELECT * FROM telegram_alertas_log ORDER BY id DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $logs = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertas Telegram - Infoservice</title>
    <link rel="stylesheet" href="../../assets/css/helpdesk-pro.css">
    <style>
        .tg-grid{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(320px,.75fr);gap:18px;align-items:start}.tg-help{display:grid;gap:12px}.tg-step{padding:14px;border:1px solid #dbe4f0;border-radius:16px;background:#f8fbff}.tg-step strong{display:block;margin-bottom:6px}.tg-found{display:grid;gap:8px;margin-top:12px}.tg-found code,.tg-code{display:inline-block;padding:6px 8px;border-radius:10px;background:#eef4ff;color:#0f172a}.tg-log{display:grid;gap:10px}.tg-log-item{border:1px solid #dbe4f0;border-radius:14px;padding:10px;background:#fff}.tg-status-ok{color:#047857;font-weight:800}.tg-status-erro{color:#b91c1c;font-weight:800}@media(max-width:900px){.tg-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="hd-page">
    <header class="hd-topbar">
        <div class="hd-container hd-topbar-inner">
            <div class="hd-brand">
                <div class="hd-brand-icon">📲</div>
                <div>
                    <h1>Alertas Telegram</h1>
                    <p>Receba aviso no celular do técnico quando um cliente abrir chamado.</p>
                </div>
            </div>
            <div class="hd-user">
                <a class="hd-chip" href="dashboard.php">Painel técnico</a>
                <a class="hd-chip" href="catalogo.php">Catálogo</a>
                <a class="hd-chip" href="../../auth/logout.php">Sair</a>
            </div>
        </div>
    </header>

    <main class="hd-main">
        <div class="hd-container">
            <?php if($success): ?><div class="hd-alert hd-alert-success"><?= h($success) ?></div><?php endif; ?>
            <?php if($error): ?><div class="hd-alert hd-alert-error"><?= h($error) ?></div><?php endif; ?>

            <div class="tg-grid">
                <section class="hd-card hd-card-pad">
                    <div class="hd-section-head">
                        <div>
                            <h2>Configuração do bot</h2>
                            <p class="hd-ticket-meta">Preencha o token, informe o chat_id do técnico e teste antes de ativar em produção.</p>
                        </div>
                    </div>

                    <form method="post" class="hd-form-grid">
                        <div class="hd-field hd-field-full">
                            <label class="hd-label">Ativar alertas Telegram</label>
                            <label class="hd-check"><input type="checkbox" name="ativo" value="1" <?= !empty($config['ativo']) ? 'checked' : '' ?>> Enviar alertas automaticamente</label>
                        </div>

                        <div class="hd-field hd-field-full">
                            <label class="hd-label">Token do bot</label>
                            <input class="hd-input" name="bot_token" value="<?= h($config['bot_token'] ?? '') ?>" placeholder="Ex.: 123456789:ABCDEF...">
                        </div>

                        <div class="hd-field hd-field-full">
                            <label class="hd-label">Chat ID do técnico</label>
                            <input class="hd-input" name="chat_ids" value="<?= h($config['chat_ids'] ?? '') ?>" placeholder="Ex.: 123456789">
                            <span class="hd-ticket-meta">Para mais de um técnico, separe os chat_ids por vírgula.</span>
                        </div>

                        <div class="hd-field hd-field-full">
                            <label class="hd-label">URL base do sistema</label>
                            <input class="hd-input" name="url_sistema" value="<?= h($config['url_sistema'] ?? '') ?>" placeholder="https://seudominio.com">
                            <span class="hd-ticket-meta">Usada no botão “Abrir chamado” dentro do Telegram.</span>
                        </div>

                        <div class="hd-field hd-field-full">
                            <label class="hd-label">Eventos</label>
                            <label class="hd-check"><input type="checkbox" name="notificar_novo_chamado" value="1" <?= !empty($config['notificar_novo_chamado']) ? 'checked' : '' ?>> Novo chamado aberto pelo cliente</label>
                        </div>

                        <div class="hd-field hd-field-full" style="display:flex;gap:10px;flex-wrap:wrap">
                            <button class="hd-btn hd-btn-primary" type="submit" name="salvar_config" value="1">Salvar configuração</button>
                            <button class="hd-btn hd-btn-success" type="submit" name="testar_alerta" value="1">Enviar alerta teste</button>
                            <button class="hd-btn hd-btn-outline" type="submit" name="buscar_chats" value="1">Buscar chat_id</button>
                        </div>
                    </form>

                    <?php if($chatMsg): ?>
                        <div class="hd-alert <?= $chatsEncontrados ? 'hd-alert-success' : 'hd-alert-error' ?>" style="margin-top:14px"><?= h($chatMsg) ?></div>
                    <?php endif; ?>

                    <?php if($chatsEncontrados): ?>
                        <div class="tg-found">
                            <?php foreach($chatsEncontrados as $idChat => $nomeChat): ?>
                                <div class="tg-step"><strong><?= h($nomeChat) ?></strong><span>Chat ID: </span><code><?= h($idChat) ?></code></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <aside class="tg-help">
                    <section class="hd-card hd-card-pad">
                        <div class="hd-section-head"><h3>Como configurar</h3></div>
                        <div class="tg-help">
                            <div class="tg-step"><strong>1. Crie o bot</strong>Abra o Telegram, procure <b>@BotFather</b>, envie <span class="tg-code">/newbot</span> e copie o token.</div>
                            <div class="tg-step"><strong>2. Inicie o bot</strong>No celular do técnico, abra o bot criado e envie <span class="tg-code">/start</span>.</div>
                            <div class="tg-step"><strong>3. Busque o chat_id</strong>Cole o token nesta tela e clique em <b>Buscar chat_id</b>. Copie o número encontrado para o campo Chat ID.</div>
                            <div class="tg-step"><strong>4. Teste</strong>Clique em <b>Enviar alerta teste</b>. Se chegar no celular, deixe os alertas ativados.</div>
                        </div>
                    </section>

                    <section class="hd-card hd-card-pad">
                        <div class="hd-section-head"><h3>Últimos envios</h3></div>
                        <div class="tg-log">
                            <?php if(!$logs): ?><div class="hd-empty">Sem logs de Telegram ainda.</div><?php endif; ?>
                            <?php foreach($logs as $log): ?>
                                <div class="tg-log-item">
                                    <strong class="<?= $log['status'] === 'enviado' ? 'tg-status-ok' : 'tg-status-erro' ?>"><?= h(ucfirst($log['status'])) ?></strong>
                                    <div class="hd-ticket-meta">Destino: <?= h($log['destino'] ?? '') ?> • <?= !empty($log['criado_em']) ? date('d/m/Y H:i', strtotime($log['criado_em'])) : '' ?></div>
                                    <?php if(!empty($log['solicitacao_id'])): ?><div class="hd-ticket-meta">Chamado #<?= (int)$log['solicitacao_id'] ?></div><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </main>
</div>
</body>
</html>
