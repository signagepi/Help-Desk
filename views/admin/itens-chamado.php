<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login-admin.php');
    exit;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function fotoItem($foto) {
    $foto = basename((string)$foto);
    return $foto !== '' ? '../../uploads/catalogo/' . rawurlencode($foto) : '';
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error_msg'] = 'Chamado não informado.';
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare('SELECT s.*, c.nome cliente_nome, c.email cliente_email, c.telefone cliente_telefone FROM solicitacoes s LEFT JOIN clientes c ON c.id=s.cliente_id WHERE s.id=?');
$stmt->execute([$id]);
$chamado = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$chamado) {
    $_SESSION['error_msg'] = 'Chamado não encontrado.';
    header('Location: dashboard.php');
    exit;
}

$catalogo = $pdo->query("SELECT * FROM catalogo_itens WHERE ativo=1 ORDER BY tipo ASC, nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("SELECT si.*, COALESCE(NULLIF(si.foto, ''), ci.foto) AS foto_exibir FROM solicitacao_itens si LEFT JOIN catalogo_itens ci ON ci.id = si.catalogo_item_id WHERE si.solicitacao_id=? ORDER BY si.id ASC");
$stmt->execute([$id]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalItens = 0;
foreach ($itens as $item) { $totalItens += (float)$item['subtotal']; }

$success = $_SESSION['success_msg'] ?? '';
$error = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Itens do chamado - Infoservice</title>
<link rel="stylesheet" href="../../assets/css/helpdesk-pro.css">
<style>
.item-foto{width:62px;height:62px;border-radius:14px;overflow:hidden;background:#eef2f7;border:1px solid var(--hd-border,#d8dee9);display:flex;align-items:center;justify-content:center;color:#64748b;font-weight:900;flex:0 0 62px}.item-foto img{width:100%;height:100%;object-fit:contain;background:#fff;padding:4px}.item-com-foto{display:flex;gap:12px;align-items:center}.catalogo-preview-card{display:flex;gap:12px;align-items:center;border:1px solid var(--hd-border,#d8dee9);border-radius:16px;padding:10px;background:#f8fafc;margin-top:10px}.catalogo-preview-card small{display:block;color:#64748b;margin-top:2px}.catalogo-preview-card strong{display:block}.catalogo-mini-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin:12px 0}.catalogo-mini{border:1px solid var(--hd-border,#d8dee9);border-radius:16px;padding:10px;background:#fff}.catalogo-mini .item-foto{width:100%;height:96px;margin-bottom:8px}.catalogo-mini span{font-size:11px;color:#64748b;font-weight:900;text-transform:uppercase}.catalogo-mini strong{display:block;margin-top:3px}.catalogo-mini small{display:block;color:#64748b;margin-top:2px}
</style>
</head>
<body>
<div class="hd-page">
<header class="hd-topbar">
    <div class="hd-container hd-topbar-inner">
        <div class="hd-brand">
            <div class="hd-brand-icon">🧰</div>
            <div>
                <h1>Produtos e serviços do chamado</h1>
                <p>Lance mão de obra, peças, materiais e serviços cadastrados para gerar recibo completo.</p>
            </div>
        </div>
        <div class="hd-user">
            <a class="hd-chip" href="dashboard.php">Dashboard</a>
            <a class="hd-chip" href="catalogo.php">Catálogo</a>
            <a class="hd-chip" href="financeiro.php">Financeiro</a>
            <a class="hd-chip" href="../../auth/logout.php">Sair</a>
        </div>
    </div>
</header>
<main class="hd-main">
<div class="hd-container">
<?php if ($success): ?><div class="hd-alert hd-alert-success"><?= h($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="hd-alert hd-alert-error"><?= h($error) ?></div><?php endif; ?>

<section class="hd-card hd-card-pad" style="margin-bottom:16px">
    <div class="hd-section-head">
        <div>
            <h2><?= h($chamado['protocolo'] ?: '#' . $chamado['id']) ?> — <?= h($chamado['titulo'] ?: mb_substr((string)$chamado['descricao'], 0, 90)) ?></h2>
            <p>Cliente: <strong><?= h($chamado['cliente_nome']) ?></strong> • Status: <?= h($chamado['status']) ?></p>
        </div>
        <div class="hd-actions">
            <a class="hd-btn hd-btn-outline" href="dashboard.php">Voltar</a>
            <a class="hd-btn hd-btn-light" href="../../controllers/gerar-recibo.php?id=<?= (int)$chamado['id'] ?>" target="_blank">Prévia do recibo</a>
        </div>
    </div>
    <div class="hd-bank-summary">
        <div><span>Total do chamado</span><strong><?= money($chamado['preco'] ?? 0) ?></strong><small>Gravado em solicitacoes.preco</small></div>
        <div><span>Total em itens</span><strong><?= money($totalItens) ?></strong><small><?= count($itens) ?> item(ns)</small></div>
        <div><span>Cliente</span><strong><?= h($chamado['cliente_nome']) ?></strong><small><?= h($chamado['cliente_telefone'] ?: $chamado['cliente_email']) ?></small></div>
    </div>
</section>

<div class="hd-grid">
<section class="hd-card hd-card-pad">
    <div class="hd-section-head"><div><h2>Lançar item cadastrado</h2><p>Selecione um produto ou serviço do catálogo.</p></div></div>
    <?php if ($catalogo): ?>
        <div class="catalogo-mini-grid">
            <?php foreach (array_slice($catalogo, 0, 8) as $item): ?>
                <?php $foto = fotoItem($item['foto_exibir'] ?? $item['foto'] ?? ''); ?>
                <div class="catalogo-mini">
                    <div class="item-foto"><?php if ($foto): ?><img src="<?= h($foto) ?>" alt="Foto de <?= h($item['nome']) ?>"><?php else: ?>📷<?php endif; ?></div>
                    <span><?= h($item['tipo']) ?></span>
                    <strong><?= h($item['nome']) ?></strong>
                    <small><?= money($item['preco']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="POST" action="../../controllers/salvar-itens-chamado.php" class="hd-form-grid">
        <input type="hidden" name="solicitacao_id" value="<?= (int)$chamado['id'] ?>">
        <input type="hidden" name="acao" value="adicionar_catalogo">
        <div class="hd-field hd-field-full">
            <label class="hd-label">Produto/serviço</label>
            <select class="hd-select" name="catalogo_item_id" id="catalogoItemSelect" required>
                <option value="">Selecione</option>
                <?php foreach ($catalogo as $item): ?>
                    <?php $foto = fotoItem($item['foto'] ?? ''); ?>
                    <option value="<?= (int)$item['id'] ?>" data-preco="<?= h(number_format((float)$item['preco'], 2, ',', '.')) ?>" data-foto="<?= h($foto) ?>" data-nome="<?= h($item['nome']) ?>" data-tipo="<?= h(ucfirst($item['tipo'])) ?>" data-descricao="<?= h($item['descricao'] ?? '') ?>">
                        <?= h(strtoupper($item['tipo']) . ' - ' . $item['nome'] . ' - ' . money($item['preco'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="catalogo-preview-card" id="catalogoPreview" style="display:none">
                <div class="item-foto" id="catalogoPreviewFoto">📷</div>
                <div><strong id="catalogoPreviewNome"></strong><small id="catalogoPreviewMeta"></small><small id="catalogoPreviewDesc"></small></div>
            </div>
        </div>
        <div class="hd-field">
            <label class="hd-label">Quantidade</label>
            <input class="hd-input" name="quantidade" value="1" placeholder="1">
        </div>
        <div class="hd-field">
            <label class="hd-label">Valor unitário opcional</label>
            <input class="hd-input" name="valor_unitario" placeholder="Deixe vazio para usar o preço padrão">
        </div>
        <div class="hd-field hd-field-full"><button class="hd-btn hd-btn-primary" type="submit">Adicionar ao chamado</button></div>
    </form>

    <div class="double-divider" style="margin:18px 0;border-top:1px solid var(--hd-border)"></div>
    <div class="hd-section-head"><div><h2>Item manual</h2><p>Use quando o item ainda não estiver cadastrado. Itens manuais não têm foto.</p></div></div>
    <form method="POST" action="../../controllers/salvar-itens-chamado.php" class="hd-form-grid">
        <input type="hidden" name="solicitacao_id" value="<?= (int)$chamado['id'] ?>">
        <input type="hidden" name="acao" value="adicionar_manual">
        <div class="hd-field">
            <label class="hd-label">Tipo</label>
            <select class="hd-select" name="tipo_manual"><option value="servico">Serviço</option><option value="produto">Produto</option></select>
        </div>
        <div class="hd-field">
            <label class="hd-label">Nome</label>
            <input class="hd-input" name="nome_manual" placeholder="Ex.: Cabo de rede 5m">
        </div>
        <div class="hd-field">
            <label class="hd-label">Quantidade</label>
            <input class="hd-input" name="quantidade" value="1">
        </div>
        <div class="hd-field">
            <label class="hd-label">Valor unitário</label>
            <input class="hd-input" name="valor_unitario" placeholder="0,00">
        </div>
        <div class="hd-field hd-field-full">
            <label class="hd-label">Descrição opcional</label>
            <textarea class="hd-textarea" name="descricao_manual" placeholder="Detalhes do item."></textarea>
        </div>
        <div class="hd-field hd-field-full"><button class="hd-btn hd-btn-light" type="submit">Adicionar item manual</button></div>
    </form>
</section>

<section class="hd-card hd-card-pad">
    <div class="hd-section-head"><div><h2>Itens lançados</h2><p>Estes itens compõem o total do recibo.</p></div></div>
    <?php if (!$itens): ?>
        <div class="hd-empty">🧾<strong>Nenhum item lançado</strong>Adicione serviços ou produtos para montar o recibo.</div>
    <?php else: ?>
        <div class="hd-table-wrap">
            <table class="hd-table">
                <thead><tr><th>Item</th><th>Qtd.</th><th>Unit.</th><th>Subtotal</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($itens as $item): ?>
                    <?php $foto = fotoItem($item['foto_exibir'] ?? $item['foto'] ?? ''); ?>
                    <tr>
                        <td>
                            <div class="item-com-foto">
                                <div class="item-foto"><?php if ($foto): ?><img src="<?= h($foto) ?>" alt="Foto de <?= h($item['nome']) ?>"><?php else: ?>📷<?php endif; ?></div>
                                <div><strong><?= h($item['nome']) ?></strong><br><small><?= h(ucfirst($item['tipo'])) ?> <?= $item['descricao'] ? '• ' . h($item['descricao']) : '' ?></small></div>
                            </div>
                        </td>
                        <td><?= h(number_format((float)$item['quantidade'], 2, ',', '.')) ?></td>
                        <td><?= money($item['valor_unitario']) ?></td>
                        <td><strong><?= money($item['subtotal']) ?></strong></td>
                        <td>
                            <form method="POST" action="../../controllers/salvar-itens-chamado.php" onsubmit="return confirm('Remover este item?')">
                                <input type="hidden" name="solicitacao_id" value="<?= (int)$chamado['id'] ?>">
                                <input type="hidden" name="acao" value="remover">
                                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                <button class="hd-btn hd-btn-danger" type="submit">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td colspan="3">Total</td><td colspan="2"><strong><?= money($totalItens) ?></strong></td></tr></tfoot>
            </table>
        </div>
    <?php endif; ?>
</section>
</div>
</div>
</main>
</div>
<script>
(function(){
    const select = document.getElementById('catalogoItemSelect');
    const preview = document.getElementById('catalogoPreview');
    const foto = document.getElementById('catalogoPreviewFoto');
    const nome = document.getElementById('catalogoPreviewNome');
    const meta = document.getElementById('catalogoPreviewMeta');
    const desc = document.getElementById('catalogoPreviewDesc');
    if (!select || !preview) return;
    select.addEventListener('change', function(){
        const opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) { preview.style.display = 'none'; return; }
        const fotoUrl = opt.dataset.foto || '';
        foto.innerHTML = fotoUrl ? '<img src="' + fotoUrl.replace(/"/g, '&quot;') + '" alt="Foto">' : '📷';
        nome.textContent = opt.dataset.nome || '';
        meta.textContent = (opt.dataset.tipo || '') + ' • Preço padrão: ' + (opt.dataset.preco || '0,00');
        desc.textContent = opt.dataset.descricao || '';
        preview.style.display = 'flex';
    });
})();
window.HD_CHAT_CONFIG={endpointBase:'../../controllers/',chatUrl:'mensagens.php'};
</script>
<script src="../../assets/js/live-chat-alerts.js"></script>
<script src="../../assets/js/helpdesk-pro.js"></script>
</body>
</html>
