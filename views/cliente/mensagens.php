<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$cliente_id = (int)$_SESSION['cliente_id'];

$stmt = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$logo = !empty($cliente['logo'])
    ? '../../uploads/clientes/logos/' . basename($cliente['logo'])
    : '';

$empresa = [];

try {
    $empresa = $pdo->query('SELECT * FROM config_empresa ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $empresa = [];
}

$nomeEmpresa = $empresa['nome_empresa'] ?? 'Infoservice';
$nomeCliente = $cliente['nome'] ?? 'Cliente';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Live Chat - Infoservice</title>

<link rel="stylesheet" href="../../assets/css/helpdesk-pro.css">

<style>
:root {
    --chat-blue: #2563eb;
    --chat-blue-dark: #1d4ed8;
    --chat-green: #22c55e;
    --chat-bg: #eef3fb;
    --chat-panel: #ffffff;
    --chat-border: #dbe3f0;
    --chat-text: #0f172a;
    --chat-muted: #64748b;
    --chat-received: #ffffff;
    --chat-sent: #dbeafe;
}

body {
    background:
        radial-gradient(circle at top right, rgba(37, 99, 235, .16), transparent 380px),
        linear-gradient(180deg, #f4f7fb 0%, #eef3fb 100%);
}

.hd-wa-shell {
    margin-top: -24px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 22px;
    align-items: stretch;
}

.hd-wa-card {
    background: var(--chat-panel);
    border: 1px solid rgba(219, 227, 240, .95);
    border-radius: 28px;
    box-shadow: 0 24px 70px rgba(15, 23, 42, .12);
    overflow: hidden;
    min-height: 660px;
    display: flex;
    flex-direction: column;
    position: relative;
}

.hd-wa-chat-head {
    min-height: 88px;
    padding: 18px 22px;
    color: #fff;
    background:
        radial-gradient(circle at top right, rgba(255,255,255,.22), transparent 240px),
        linear-gradient(135deg, #0f172a 0%, #1d4ed8 70%, #2563eb 100%);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.hd-wa-agent {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}

.hd-wa-avatar {
    width: 54px;
    height: 54px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.25);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.08);
    font-size: 25px;
    overflow: hidden;
    flex: 0 0 auto;
}

.hd-wa-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hd-wa-agent h2 {
    margin: 0;
    line-height: 1.1;
    font-size: 22px;
    font-weight: 900;
}

.hd-wa-agent p {
    margin: 5px 0 0;
    color: rgba(255,255,255,.82);
    font-size: 14px;
}

.hd-wa-online {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 13px;
    border-radius: 999px;
    background: rgba(34, 197, 94, .16);
    border: 1px solid rgba(134, 239, 172, .32);
    color: #dcfce7;
    font-size: 13px;
    white-space: nowrap;
}

.hd-wa-dot {
    width: 9px;
    height: 9px;
    border-radius: 99px;
    background: #22c55e;
    box-shadow: 0 0 0 6px rgba(34,197,94,.16);
}

.hd-wa-messages {
    flex: 1;
    padding: 24px;
    overflow-y: auto;
    background:
        linear-gradient(rgba(255,255,255,.78), rgba(255,255,255,.78)),
        radial-gradient(circle at 20px 20px, rgba(37, 99, 235, .07) 2px, transparent 2px);
    background-size: auto, 28px 28px;
    scroll-behavior: smooth;
}

.hd-wa-day {
    text-align: center;
    margin: 4px 0 22px;
}

.hd-wa-day span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 7px 13px;
    background: rgba(15, 23, 42, .07);
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 999px;
    color: #475569;
    font-size: 12px;
    font-weight: 800;
}

.hd-wa-empty {
    height: 100%;
    min-height: 370px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: var(--chat-muted);
    padding: 30px;
}

.hd-wa-empty div {
    max-width: 360px;
}

.hd-wa-empty strong {
    display: block;
    margin: 14px 0 7px;
    color: var(--chat-text);
    font-size: 20px;
}

.hd-wa-empty-icon {
    width: 82px;
    height: 82px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 26px;
    background: linear-gradient(135deg, #dbeafe, #eff6ff);
    color: #1d4ed8;
    font-size: 38px;
    box-shadow: 0 12px 30px rgba(37,99,235,.15);
}

.hd-wa-row {
    display: flex;
    margin-bottom: 14px;
    animation: hdWaPop .18s ease-out;
}

.hd-wa-row.sent {
    justify-content: flex-end;
}

.hd-wa-row.received {
    justify-content: flex-start;
}

.hd-wa-bubble {
    max-width: min(72%, 620px);
    padding: 12px 14px 9px;
    border-radius: 18px;
    position: relative;
    box-shadow: 0 8px 22px rgba(15,23,42,.07);
    border: 1px solid rgba(15,23,42,.06);
    word-break: break-word;
}

.hd-wa-row.sent .hd-wa-bubble {
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    border-bottom-right-radius: 5px;
}

.hd-wa-row.received .hd-wa-bubble {
    background: #ffffff;
    border-bottom-left-radius: 5px;
}

.hd-wa-name {
    display: block;
    font-size: 12px;
    font-weight: 900;
    margin-bottom: 5px;
    color: #1d4ed8;
}

.hd-wa-row.received .hd-wa-name {
    color: #0f766e;
}

.hd-wa-text {
    font-size: 15px;
    line-height: 1.45;
    color: #0f172a;
    white-space: pre-wrap;
}

.hd-wa-meta {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
    margin-top: 6px;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
}

.hd-wa-check {
    color: #2563eb;
    font-size: 13px;
}

.hd-wa-composer {
    padding: 14px 16px;
    border-top: 1px solid var(--chat-border);
    background: rgba(255,255,255,.96);
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

.hd-wa-tool {
    width: 44px;
    height: 44px;
    border: 0;
    border-radius: 16px;
    background: #f1f5f9;
    color: #334155;
    cursor: pointer;
    font-size: 20px;
    transition: .18s;
}

.hd-wa-tool:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}

.hd-wa-input-wrap {
    flex: 1;
    position: relative;
}

.hd-wa-input {
    width: 100%;
    min-height: 46px;
    max-height: 126px;
    resize: none;
    border: 1px solid #dbe3f0;
    background: #f8fafc;
    border-radius: 18px;
    padding: 13px 16px;
    font-size: 15px;
    outline: none;
    transition: .18s;
}

.hd-wa-input:focus {
    border-color: rgba(37,99,235,.55);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(37,99,235,.10);
}

.hd-wa-send {
    width: 50px;
    height: 50px;
    border: 0;
    border-radius: 18px;
    cursor: pointer;
    color: #fff;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    box-shadow: 0 10px 22px rgba(37,99,235,.28);
    font-size: 20px;
    transition: .18s;
}

.hd-wa-send:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 28px rgba(37,99,235,.34);
}

.hd-wa-send:disabled {
    opacity: .55;
    cursor: not-allowed;
    transform: none;
}

.hd-wa-side {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.hd-wa-side-card {
    background: rgba(255,255,255,.94);
    border: 1px solid rgba(219,227,240,.95);
    border-radius: 26px;
    box-shadow: 0 18px 48px rgba(15,23,42,.10);
    padding: 20px;
}

.hd-wa-side-card h3 {
    margin: 0 0 12px;
    font-size: 19px;
    color: #0f172a;
}

.hd-wa-support-status {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-radius: 20px;
    background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
    border: 1px solid #bbf7d0;
    margin-bottom: 14px;
}

.hd-wa-support-status strong {
    display: block;
    color: #14532d;
}

.hd-wa-support-status small {
    color: #166534;
}

.hd-wa-feature {
    display: flex;
    gap: 12px;
    padding: 13px 0;
    border-top: 1px solid #e2e8f0;
}

.hd-wa-feature:first-of-type {
    border-top: 0;
}

.hd-wa-feature-icon {
    width: 38px;
    height: 38px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eff6ff;
    color: #1d4ed8;
    flex: 0 0 auto;
}

.hd-wa-feature strong {
    display: block;
    color: #0f172a;
    font-size: 14px;
    margin-bottom: 3px;
}

.hd-wa-feature p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
    line-height: 1.35;
}

.hd-wa-shortcuts {
    display: grid;
    gap: 10px;
}

.hd-wa-shortcuts a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 13px 14px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #0f172a;
    text-decoration: none;
    font-weight: 900;
    transition: .18s;
}

.hd-wa-shortcuts a:hover {
    transform: translateY(-1px);
    background: #eff6ff;
    border-color: #bfdbfe;
}

.hd-wa-shortcuts a.primary {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    border-color: transparent;
}

.hd-wa-toast {
    position: fixed;
    right: 22px;
    bottom: 22px;
    z-index: 9999;
    min-width: 280px;
    max-width: 360px;
    padding: 15px 16px;
    border-radius: 20px;
    color: #fff;
    background: linear-gradient(135deg, #0f172a, #1d4ed8);
    box-shadow: 0 22px 52px rgba(15,23,42,.28);
    display: none;
    animation: hdWaSlide .2s ease-out;
}

.hd-wa-toast strong {
    display: block;
    margin-bottom: 3px;
}

.hd-wa-toast p {
    margin: 0;
    color: rgba(255,255,255,.82);
    font-size: 13px;
}

.hd-wa-typing {
    display: none;
    align-items: center;
    gap: 6px;
    color: #64748b;
    font-size: 13px;
    margin: -4px 0 12px;
}

.hd-wa-typing span {
    width: 6px;
    height: 6px;
    background: #94a3b8;
    border-radius: 50%;
    display: inline-block;
    animation: hdTyping 1s infinite ease-in-out;
}

.hd-wa-typing span:nth-child(2) { animation-delay: .15s; }
.hd-wa-typing span:nth-child(3) { animation-delay: .3s; }

@keyframes hdTyping {
    0%, 80%, 100% { opacity: .35; transform: translateY(0); }
    40% { opacity: 1; transform: translateY(-3px); }
}

@keyframes hdWaPop {
    from { opacity: 0; transform: translateY(7px) scale(.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes hdWaSlide {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 980px) {
    .hd-wa-shell {
        grid-template-columns: 1fr;
    }

    .hd-wa-card {
        min-height: 620px;
    }

    .hd-wa-side {
        order: 2;
    }
}

@media (max-width: 640px) {
    .hd-wa-shell {
        margin-top: 0;
    }

    .hd-wa-card,
    .hd-wa-side-card {
        border-radius: 20px;
    }

    .hd-wa-chat-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .hd-wa-messages {
        padding: 16px;
    }

    .hd-wa-bubble {
        max-width: 88%;
    }

    .hd-wa-composer {
        padding: 12px;
    }

    .hd-wa-tool {
        display: none;
    }
}
</style>
</head>

<body>

<div class="hd-page">

<header class="hd-topbar hd-no-print">
    <div class="hd-container hd-topbar-inner">

        <div class="hd-brand">
            <div class="hd-client-logo-box">
                <?php if ($logo): ?>
                    <img src="<?= h($logo) ?>" alt="Logo do cliente">
                <?php else: ?>
                    💬
                <?php endif; ?>
            </div>

            <div>
                <h1>Live Chat</h1>
                <p>Atendimento rápido, organizado e com notificações em tempo real.</p>
            </div>
        </div>

        <div class="hd-user">
            <span class="hd-chip"><?= h($nomeCliente) ?></span>
            <a class="hd-chip" href="dashboard.php">Dashboard</a>
            <a class="hd-chip" href="financeiro.php">Financeiro</a>
            <a class="hd-chip" href="../../auth/logout.php">Sair</a>
        </div>

    </div>
</header>

<main class="hd-main">
<div class="hd-container">

<div class="hd-wa-shell">

    <section class="hd-wa-card">

        <div class="hd-wa-chat-head">

            <div class="hd-wa-agent">
                <div class="hd-wa-avatar">
                    <?php if (!empty($empresa['logo'])): ?>
                        <img src="../../uploads/empresa/<?= h(basename($empresa['logo'])) ?>" alt="Logo empresa">
                    <?php else: ?>
                        🛠️
                    <?php endif; ?>
                </div>

                <div>
                    <h2>Atendimento <?= h($nomeEmpresa) ?></h2>
                    <p>Suporte técnico, financeiro e acompanhamento de chamados</p>
                </div>
            </div>

            <div class="hd-wa-online">
                <span class="hd-wa-dot"></span>
                Online agora
            </div>

        </div>

        <div class="hd-wa-messages" id="hd-chat-messages">

            <div class="hd-wa-day">
                <span>Hoje</span>
            </div>

            <div id="hd-chat-list">
                <div class="hd-wa-empty" id="hd-chat-empty">
                    <div>
                        <div class="hd-wa-empty-icon">💬</div>
                        <strong>Nenhuma mensagem ainda</strong>
                        <p>Envie sua dúvida para iniciar o atendimento. Nossa equipe será avisada automaticamente.</p>
                    </div>
                </div>
            </div>

        </div>

        <div class="hd-wa-typing" id="hd-chat-loading">
            <span></span><span></span><span></span>
            Atualizando mensagens...
        </div>

        <form class="hd-wa-composer" id="hd-chat-form" autocomplete="off">

            <button class="hd-wa-tool" type="button" title="Atendimento">💬</button>

            <div class="hd-wa-input-wrap">
                <textarea
                    class="hd-wa-input"
                    id="hd-chat-text"
                    rows="1"
                    placeholder="Digite sua mensagem..."
                ></textarea>
            </div>

            <button class="hd-wa-send" id="hd-chat-send" type="submit" title="Enviar mensagem">
                ➤
            </button>

        </form>

    </section>

    <aside class="hd-wa-side">

        <section class="hd-wa-side-card">

            <h3>Central de atendimento</h3>

            <div class="hd-wa-support-status">
                <div class="hd-wa-dot"></div>
                <div>
                    <strong>Equipe disponível</strong>
                    <small>Você será notificado quando responderem.</small>
                </div>
            </div>

            <div class="hd-wa-feature">
                <div class="hd-wa-feature-icon">🔔</div>
                <div>
                    <strong>Alerta visual</strong>
                    <p>Ao receber resposta, um aviso aparece na tela automaticamente.</p>
                </div>
            </div>

            <div class="hd-wa-feature">
                <div class="hd-wa-feature-icon">🔊</div>
                <div>
                    <strong>Alerta sonoro</strong>
                    <p>Depois do primeiro clique na página, o navegador libera o som.</p>
                </div>
            </div>

            <div class="hd-wa-feature">
                <div class="hd-wa-feature-icon">⚡</div>
                <div>
                    <strong>Atualização automática</strong>
                    <p>O chat busca novas mensagens sem precisar atualizar a página.</p>
                </div>
            </div>

        </section>

        <section class="hd-wa-side-card">

            <h3>Acessos rápidos</h3>

            <div class="hd-wa-shortcuts">
                <a href="financeiro.php">
                    <span>💰 Financeiro</span>
                    <b>›</b>
                </a>

                <a href="calendario.php">
                    <span>📅 Calendário</span>
                    <b>›</b>
                </a>

                <a href="solicitar.php" class="primary">
                    <span>➕ Abrir chamado</span>
                    <b>›</b>
                </a>
            </div>

        </section>

    </aside>

</div>

</div>
</main>

</div>

<div class="hd-wa-toast" id="hd-chat-toast">
    <strong>Nova mensagem recebida</strong>
    <p>O atendimento acabou de responder no chat.</p>
</div>

<script>
window.HD_CHAT_CONFIG = {
    endpointBase: '../../controllers/',
    chatUrl: 'mensagens.php'
};
</script>

<script>
const chatFetchEndpoint = '../../controllers/chat-fetch.php';
const chatSendEndpoint = '../../controllers/chat-send.php';

const chatMessages = document.getElementById('hd-chat-messages');
const chatList = document.getElementById('hd-chat-list');
const chatForm = document.getElementById('hd-chat-form');
const chatText = document.getElementById('hd-chat-text');
const chatSend = document.getElementById('hd-chat-send');
const chatToast = document.getElementById('hd-chat-toast');
const chatLoading = document.getElementById('hd-chat-loading');

let lastMessageSignature = '';
let firstLoad = true;
let audioUnlocked = false;

function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(char) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[char];
    });
}

function normalizeSender(msg) {
    const tipo = String(msg.tipo || msg.remetente_tipo || msg.origem || '').toLowerCase();

    if (tipo === 'cliente' || tipo === 'client') {
        return 'sent';
    }

    return 'received';
}

function getMessageText(msg) {
    return msg.mensagem || msg.message || msg.texto || msg.conteudo || '';
}

function getMessageTime(msg) {
    const raw = msg.criado_em || msg.created_at || msg.data || msg.hora || '';

    if (!raw) {
        return '';
    }

    const date = new Date(String(raw).replace(' ', 'T'));

    if (isNaN(date.getTime())) {
        return String(raw).slice(11, 16) || '';
    }

    return date.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getMessageSignature(data) {
    if (!Array.isArray(data) || data.length === 0) {
        return '';
    }

    const last = data[data.length - 1];

    return [
        last.id || '',
        last.criado_em || last.created_at || last.data || '',
        last.tipo || last.remetente_tipo || last.origem || '',
        getMessageText(last)
    ].join('|');
}

function playNotifySound() {
    if (!audioUnlocked) {
        return;
    }

    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioContext();
        const oscillator = ctx.createOscillator();
        const gain = ctx.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(720, ctx.currentTime);
        oscillator.frequency.setValueAtTime(920, ctx.currentTime + 0.08);

        gain.gain.setValueAtTime(0.0001, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.22);

        oscillator.connect(gain);
        gain.connect(ctx.destination);

        oscillator.start(ctx.currentTime);
        oscillator.stop(ctx.currentTime + 0.24);
    } catch (e) {}
}

function showToast() {
    chatToast.style.display = 'block';

    setTimeout(function() {
        chatToast.style.display = 'none';
    }, 4200);
}

function renderMessages(data) {
    chatList.innerHTML = '';

    if (!Array.isArray(data) || data.length === 0) {
        chatList.innerHTML = `
            <div class="hd-wa-empty" id="hd-chat-empty">
                <div>
                    <div class="hd-wa-empty-icon">💬</div>
                    <strong>Nenhuma mensagem ainda</strong>
                    <p>Envie sua dúvida para iniciar o atendimento. Nossa equipe será avisada automaticamente.</p>
                </div>
            </div>
        `;
        return;
    }

    data.forEach(function(msg) {
        const side = normalizeSender(msg);
        const name = side === 'sent' ? 'Você' : 'Atendimento';
        const text = escapeHTML(getMessageText(msg));
        const time = escapeHTML(getMessageTime(msg));

        const row = document.createElement('div');
        row.className = 'hd-wa-row ' + side;

        row.innerHTML = `
            <div class="hd-wa-bubble">
                <span class="hd-wa-name">${name}</span>
                <div class="hd-wa-text">${text}</div>
                <div class="hd-wa-meta">
                    <span>${time}</span>
                    ${side === 'sent' ? '<span class="hd-wa-check">✓✓</span>' : ''}
                </div>
            </div>
        `;

        chatList.appendChild(row);
    });
}

async function fetchMessages() {
    try {
        const response = await fetch(chatFetchEndpoint, {
            cache: 'no-store'
        });

        const data = await response.json();
        const signature = getMessageSignature(data);

        if (signature !== lastMessageSignature) {
            const oldSignature = lastMessageSignature;
            lastMessageSignature = signature;

            renderMessages(data);

            chatMessages.scrollTop = chatMessages.scrollHeight;

            if (!firstLoad && oldSignature && Array.isArray(data) && data.length > 0) {
                const last = data[data.length - 1];

                if (normalizeSender(last) === 'received') {
                    showToast();
                    playNotifySound();
                }
            }
        }

        firstLoad = false;
    } catch (e) {
        console.warn('Erro ao atualizar chat:', e);
    }
}

async function sendMessage() {
    const text = chatText.value.trim();

    if (!text) {
        return;
    }

    chatSend.disabled = true;

    try {
        await fetch(chatSendEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                mensagem: text
            })
        });

        chatText.value = '';
        chatText.style.height = 'auto';

        await fetchMessages();
    } catch (e) {
        alert('Não foi possível enviar a mensagem. Tente novamente.');
    } finally {
        chatSend.disabled = false;
        chatText.focus();
    }
}

document.addEventListener('click', function() {
    audioUnlocked = true;
}, { once: true });

chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    audioUnlocked = true;
    sendMessage();
});

chatText.addEventListener('input', function() {
    chatText.style.height = 'auto';
    chatText.style.height = Math.min(chatText.scrollHeight, 126) + 'px';
});

chatText.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

fetchMessages();
setInterval(fetchMessages, 4000);
</script>

<script src="../../assets/js/helpdesk-pro.js"></script>

</body>
</html>
