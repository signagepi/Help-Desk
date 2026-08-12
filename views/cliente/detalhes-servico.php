<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function qtd($v) { return rtrim(rtrim(number_format((float)$v, 2, ',', '.'), '0'), ','); }
function status_class($status) { return 'st-' . str_replace(['_', ' '], '-', strtolower((string)$status)); }
function fotoItem($foto) {
    $foto = basename((string)$foto);
    return $foto !== '' ? '../../uploads/catalogo/' . rawurlencode($foto) : '';
}

$clienteId = (int)$_SESSION['cliente_id'];
$servicoId = (int)($_GET['id'] ?? 0);

if ($servicoId <= 0) {
    header('Location: financeiro.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM clientes WHERE id=?');
$stmt->execute([$clienteId]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$stmt = $pdo->prepare('SELECT * FROM solicitacoes WHERE id=? AND cliente_id=? AND preco IS NOT NULL');
$stmt->execute([$servicoId, $clienteId]);
$servico = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$servico) {
    header('Location: financeiro.php');
    exit;
}

$stmt = $pdo->prepare("\n    SELECT \n        si.*,\n        COALESCE(NULLIF(si.foto, ''), ci.foto) AS foto_exibir\n    FROM solicitacao_itens si\n    LEFT JOIN catalogo_itens ci ON ci.id = si.catalogo_item_id\n    WHERE si.solicitacao_id=?\n    ORDER BY si.id ASC\n");
$stmt->execute([$servicoId]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalItens = 0;
foreach ($itens as $item) { $totalItens += (float)$item['subtotal']; }
$total = (float)($servico['preco'] ?? 0);
if ($total <= 0 && $totalItens > 0) $total = $totalItens;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detalhes do Serviço - Infoservice</title>
<link rel="stylesheet" href="../../assets/css/helpdesk-pro.css">
<style>
.detalhes-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(420px,.72fr);gap:18px;align-items:start}.detalhe-lancamentos-card{position:sticky;top:18px}.item-foto{width:72px;height:72px;border-radius:16px;overflow:hidden;background:#eef2f7;border:1px solid var(--hd-border,#d8dee9);display:flex;align-items:center;justify-content:center;color:#64748b;font-weight:900;flex:0 0 72px}.item-foto img{width:100%;height:100%;object-fit:contain;background:#fff;padding:4px}.lancamentos-list{display:grid;gap:12px}.lancamento-card{display:grid;grid-template-columns:72px minmax(0,1fr) auto;gap:14px;align-items:center;border:1px solid var(--hd-border,#d8dee9);border-radius:18px;background:#fff;padding:12px}.lancamento-info{min-width:0}.lancamento-tipo{display:inline-flex;margin-bottom:5px;border-radius:999px;background:#eef4ff;color:#1e40af;font-size:11px;font-weight:900;text-transform:uppercase;padding:4px 8px}.lancamento-info strong{display:block;font-size:15px;line-height:1.25;color:#0f172a;overflow-wrap:anywhere}.lancamento-info p{margin:5px 0 0;color:#475569;font-size:12px;line-height:1.35;overflow-wrap:anywhere}.lancamento-info small{display:block;margin-top:6px;color:#64748b;font-size:12px}.lancamento-total{text-align:right;white-space:nowrap;border-left:1px solid var(--hd-border,#d8dee9);padding-left:12px}.lancamento-total span{display:block;color:#64748b;font-size:11px;font-weight:900;text-transform:uppercase}.lancamento-total strong{display:block;color:#1d4ed8;font-size:15px;margin-top:3px}.lancamentos-total{display:flex;justify-content:space-between;gap:12px;align-items:center;border-top:2px solid #bfdbfe;margin-top:14px;padding-top:14px;font-weight:1000}.lancamentos-total strong{font-size:22px;color:#1d4ed8}@media(max-width:1080px){.detalhes-layout{grid-template-columns:1fr}.detalhe-lancamentos-card{position:static}}@media(max-width:620px){.lancamento-card{grid-template-columns:58px minmax(0,1fr)}.item-foto{width:58px;height:58px;flex-basis:58px}.lancamento-total{grid-column:1/-1;text-align:left;border-left:0;border-top:1px solid var(--hd-border,#d8dee9);padding-left:0;padding-top:10px}.lancamentos-total{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body>
<div class="hd-page">
<header class="hd-topbar">
    <div class="hd-container hd-topbar-inner">
        <div class="hd-brand">
            <div class="hd-brand-icon">🔍</div>
            <div>
                <h1>Detalhes do serviço</h1>
                <p>Resumo do atendimento, itens lançados e recibo do chamado.</p>
            </div>
        </div>
        <div class="hd-user">
            <a class="hd-chip" href="financeiro.php">Financeiro</a>
            <a class="hd-chip" href="dashboard.php">Dashboard</a>
            <a class="hd-chip" href="mensagens.php">Mensagens</a>
            <a class="hd-chip" href="../../auth/logout.php">Sair</a>
        </div>
    </div>
</header>
<main class="hd-main">
<div class="hd-container">
<section class="hd-card hd-card-pad" style="margin-bottom:16px">
    <div class="hd-section-head">
        <div>
            <h2><?= h($servico['protocolo'] ?: '#' . $servico['id']) ?> — <?= h($servico['titulo'] ?: 'Serviço técnico') ?></h2>
            <p>Cliente: <strong><?= h($cliente['nome'] ?? '') ?></strong> • Data: <?= date('d/m/Y H:i', strtotime($servico['data'])) ?></p>
        </div>
        <div class="hd-actions">
            <a class="hd-btn hd-btn-outline" href="financeiro.php">Voltar</a>
            <a class="hd-btn hd-btn-primary" href="../../controllers/gerar-recibo.php?id=<?= (int)$servico['id'] ?>" target="_blank">Gerar recibo</a>
        </div>
    </div>
    <div class="hd-bank-summary">
        <div><span>Status</span><strong><?= h(ucfirst((string)$servico['status'])) ?></strong><small>Chamado <?= h($servico['protocolo'] ?: '#' . $servico['id']) ?></small></div>
        <div><span>Total</span><strong><?= money($total) ?></strong><small>Serviços e produtos lançados</small></div>
        <div><span>Itens</span><strong><?= count($itens) ?></strong><small>Produto(s)/serviço(s)</small></div>
    </div>
</section>

<div class="detalhes-layout">
<section class="hd-card hd-card-pad">
    <div class="hd-section-head"><div><h2>Dados do atendimento</h2><p>Descrição do chamado e execução técnica.</p></div></div>
    <div class="hd-ticket-card">
        <strong>Solicitação do cliente</strong>
        <p><?= nl2br(h($servico['descricao'])) ?></p>
    </div>
    <div class="hd-ticket-card">
        <strong>Serviço executado / observação técnica</strong>
        <p><?= $servico['observacao'] ? nl2br(h($servico['observacao'])) : 'Nenhuma observação técnica registrada.' ?></p>
    </div>
    <div class="hd-ticket-card">
        <strong>Status</strong><br>
        <span class="hd-badge <?= h(status_class($servico['status'])) ?>"><?= h(ucfirst((string)$servico['status'])) ?></span>
    </div>
</section>

<aside class="hd-card hd-card-pad detalhe-lancamentos-card">
    <div class="hd-section-head"><div><h2>Produtos e serviços lançados</h2><p>Itens que compõem o valor do recibo.</p></div></div>
    <?php if (!$itens): ?>
        <div class="hd-empty">🧾<strong>Sem itens detalhados</strong>O recibo usará o valor total lançado no chamado.</div>
    <?php else: ?>
        <div class="lancamentos-list">
            <?php foreach ($itens as $item): ?>
                <?php $foto = fotoItem($item['foto_exibir'] ?? $item['foto'] ?? ''); ?>
                <article class="lancamento-card">
                    <div class="item-foto"><?php if ($foto): ?><img src="<?= h($foto) ?>" alt="Foto de <?= h($item['nome']) ?>"><?php else: ?>📷<?php endif; ?></div>
                    <div class="lancamento-info">
                        <span class="lancamento-tipo"><?= h(ucfirst($item['tipo'])) ?></span>
                        <strong><?= h($item['nome']) ?></strong>
                        <?php if (!empty($item['descricao'])): ?><p><?= h($item['descricao']) ?></p><?php endif; ?>
                        <small><?= h(qtd($item['quantidade'])) ?> × <?= money($item['valor_unitario']) ?></small>
                    </div>
                    <div class="lancamento-total"><span>Total</span><strong><?= money($item['subtotal']) ?></strong></div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="lancamentos-total"><span>Total do chamado</span><strong><?= money($total) ?></strong></div>
    <?php endif; ?>
</aside>
</div>
</div>
</main>
</div>
<script>window.HD_CHAT_CONFIG={endpointBase:'../../controllers/',chatUrl:'mensagens.php'};</script>
<script src="../../assets/js/live-chat-alerts.js"></script>
<script src="../../assets/js/helpdesk-pro.js"></script>
</body>
</html>
