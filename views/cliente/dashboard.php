<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function statusBadge($status) {
    $classe = 'st-' . str_replace(' ', '-', strtolower((string)$status));
    return '<span class="hd-badge ' . h($classe) . '">' . h(ucfirst((string)$status)) . '</span>';
}
function prioridadeBadge($prioridade) {
    $p = $prioridade ?: 'media';
    return '<span class="hd-badge pr-' . h($p) . '">' . h(ucfirst($p)) . '</span>';
}
function progressoStatus($status) {
    return match ((string)$status) {
        'pendente' => 25,
        'em andamento' => 65,
        'finalizado' => 100,
        'nao resolvido', 'cancelado' => 100,
        default => 25,
    };
}

$id = (int)$_SESSION['cliente_id'];
$stmt = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
$stmt->execute([$id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);
$logo = !empty($cliente['logo']) ? '../../uploads/clientes/logos/' . basename($cliente['logo']) : '';
if (!$cliente) {
    session_destroy();
    header('Location: ../../auth/login.php');
    exit;
}

$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$limite = 6;
$offset = ($pagina - 1) * $limite;
$stmt = $pdo->prepare('SELECT * FROM solicitacoes WHERE cliente_id = ? ORDER BY data DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->bindValue(2, $limite, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtTotal = $pdo->prepare('SELECT COUNT(*) FROM solicitacoes WHERE cliente_id = ?');
$stmtTotal->execute([$id]);
$total = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($total / $limite));

$stmtStats = $pdo->prepare("SELECT
    COUNT(*) total,
    SUM(status = 'pendente') pendente,
    SUM(status = 'em andamento') andamento,
    SUM(status = 'finalizado') finalizado
    FROM solicitacoes WHERE cliente_id = ?");
$stmtStats->execute([$id]);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC) ?: [];

$stmtMsg = $pdo->prepare('SELECT COUNT(*) FROM mensagens WHERE destinatario_id = ? AND lida = 0');
$stmtMsg->execute([$id]);
$nao_lidas = (int)$stmtMsg->fetchColumn();

$success = $_SESSION['success_msg'] ?? '';
$error = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Central de Atendimento - Infoservice</title>
    <link rel="stylesheet" href="../../assets/css/helpdesk-pro.css">
</head>
<body>
<div class="hd-page">
    <header class="hd-topbar">
        <div class="hd-container hd-topbar-inner">
            <div class="hd-brand">
                <div class="hd-client-logo-box"><?php if ($logo): ?><img src="<?= h($logo) ?>" alt="Logo do cliente"><?php else: ?>🎧<?php endif; ?></div>
                <div>
                    <h1>Minha Central Infoservice</h1>
                    <p>Acompanhe seus chamados, prazos, valores e mensagens em um só lugar.</p>
                </div>
            </div>
            <div class="hd-user">
                <span class="hd-chip">Olá, <?= h($cliente['nome']) ?></span>
                <a class="hd-chip" href="mensagens.php">💬 <?= $nao_lidas ?> mensagens</a>
                <a class="hd-chip" href="../../auth/logout.php">Sair</a>
            </div>
        </div>
    </header>

    <main class="hd-main">
        <div class="hd-container">
            <?php if ($success): ?><div class="hd-alert hd-alert-success"><?= h($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="hd-alert hd-alert-error"><?= h($error) ?></div><?php endif; ?>

            <section class="hd-kpis">
                <div class="hd-kpi"><span>Total de chamados</span><strong><?= (int)($stats['total'] ?? 0) ?></strong><small>Solicitações abertas</small></div>
                <div class="hd-kpi"><span>Pendentes</span><strong><?= (int)($stats['pendente'] ?? 0) ?></strong><small>Aguardando análise</small></div>
                <div class="hd-kpi"><span>Em andamento</span><strong><?= (int)($stats['andamento'] ?? 0) ?></strong><small>Equipe atuando</small></div>
                <div class="hd-kpi"><span>Finalizados</span><strong><?= (int)($stats['finalizado'] ?? 0) ?></strong><small>Concluídos</small></div>
            </section>

            <div class="hd-grid">
                <section class="hd-card hd-card-pad">
                    <div class="hd-section-head">
                        <div>
                            <h2>Meus chamados</h2>
                            <p class="hd-ticket-meta">Veja o andamento das solicitações abertas com a Infoservice.</p>
                        </div>
                        <div class="hd-actions">
                            <a class="hd-btn hd-btn-primary" href="solicitar.php">➕ Abrir chamado</a>
                            <button class="hd-btn hd-btn-outline" onclick="imprimirRelatorio()">🖨️ Imprimir</button>
                        </div>
                    </div>

                    <?php if (!$solicitacoes): ?>
                        <div class="hd-empty">📋<strong>Nenhum chamado aberto</strong>Abra sua primeira solicitação e acompanhe tudo por aqui.<br><br><a class="hd-btn hd-btn-primary" href="solicitar.php">Abrir chamado</a></div>
                    <?php endif; ?>

                    <?php foreach ($solicitacoes as $s): ?>
                        <article class="hd-ticket-card">
                            <div class="hd-ticket-card-head">
                                <div>
                                    <div class="hd-ticket-title"><?= h($s['titulo'] ?: mb_substr($s['descricao'], 0, 90)) ?></div>
                                    <div class="hd-ticket-meta"><?= h($s['protocolo'] ?: '#'.$s['id']) ?> • Aberto em <?= date('d/m/Y H:i', strtotime($s['data'])) ?></div>
                                </div>
                                <div class="hd-actions"><?= statusBadge($s['status']) ?> <?= prioridadeBadge($s['prioridade'] ?? 'media') ?></div>
                            </div>
                            <p><?= nl2br(h(mb_strimwidth($s['descricao'], 0, 260, '...'))) ?></p>
                            <div class="hd-progress" title="Progresso do chamado"><span style="width:<?= progressoStatus($s['status']) ?>%"></span></div>
                            <div class="hd-ticket-meta" style="margin-top:10px">
                                Categoria: <?= h($s['categoria'] ?: 'Suporte técnico') ?>
                                <?= !empty($s['prazo']) ? ' • Prazo estimado: ' . date('d/m/Y H:i', strtotime($s['prazo'])) : '' ?>
                                <?= $s['preco'] !== null ? ' • Valor: R$ ' . number_format((float)$s['preco'], 2, ',', '.') : '' ?>
                            </div>
                            <?php if (!empty($s['observacao'])): ?>
                                <div class="hd-mini" style="margin-top:12px"><strong>Observação técnica</strong><p><?= nl2br(h($s['observacao'])) ?></p></div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>

                    <?php if ($totalPaginas > 1): ?>
                        <div class="hd-pagination">
                            <?php for ($i=1; $i <= $totalPaginas; $i++): ?>
                                <a class="<?= $i === $pagina ? 'active' : '' ?>" href="?pagina=<?= $i ?>"><?= $i ?></a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <aside class="hd-sidebar">
                    <section class="hd-card hd-card-pad">
                        <div class="hd-section-head"><h3>Menu rápido</h3></div>
                        <div class="hd-sidebar-list">
                            <a class="hd-btn hd-btn-primary" href="solicitar.php">➕ Nova solicitação</a>
                            <a class="hd-btn hd-btn-light" href="mensagens.php">💬 Mensagens</a>
                            <a class="hd-btn hd-btn-light" href="financeiro.php">💰 Financeiro</a>
                            <a class="hd-btn hd-btn-light" href="calendario.php">🗓️ Calendário</a>
                        </div>
                    </section>
                    <section class="hd-card hd-card-pad" style="margin-top:16px">
                        <div class="hd-section-head"><h3>Como agilizar</h3></div>
                        <div class="hd-sidebar-list">
                            <div class="hd-mini"><strong>Envie detalhes</strong><p>Informe equipamento, local, erro exibido e quando o problema começou.</p></div>
                            <div class="hd-mini"><strong>Prioridade correta</strong><p>Use urgente somente quando o serviço estiver parado ou gerando prejuízo imediato.</p></div>
                            <div class="hd-mini"><strong>Acompanhe o prazo</strong><p>Cada chamado recebe um prazo estimado conforme a prioridade.</p></div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </main>
</div>
<script src="../../assets/js/helpdesk-pro.js"></script>
</body>
</html>
