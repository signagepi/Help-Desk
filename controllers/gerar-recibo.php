<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cliente_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('ID do serviço não especificado.');
}

require '../config/db.php';

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function qtd($v) { return rtrim(rtrim(number_format((float)$v, 2, ',', '.'), '0'), ','); }

$servicoId = (int)$_GET['id'];
$isAdmin = isset($_SESSION['admin_id']);
$params = [$servicoId];
$whereCliente = '';

if (!$isAdmin) {
    $whereCliente = ' AND s.cliente_id = ? ';
    $params[] = (int)$_SESSION['cliente_id'];
}

$stmt = $pdo->prepare("SELECT s.*, c.nome cliente_nome, c.email cliente_email, c.telefone cliente_telefone, c.endereco cliente_endereco FROM solicitacoes s LEFT JOIN clientes c ON s.cliente_id = c.id WHERE s.id = ? $whereCliente LIMIT 1");
$stmt->execute($params);
$servico = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$servico) {
    die('Serviço não encontrado.');
}

$stmt = $pdo->prepare("SELECT si.*, COALESCE(NULLIF(si.foto, ''), ci.foto) AS foto_exibir FROM solicitacao_itens si LEFT JOIN catalogo_itens ci ON ci.id = si.catalogo_item_id WHERE si.solicitacao_id=? ORDER BY si.id ASC");
$stmt->execute([$servicoId]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalItens = 0;
foreach ($itens as $item) {
    $totalItens += (float)$item['subtotal'];
}

$total = (float)($servico['preco'] ?? 0);
if ($total <= 0 && $totalItens > 0) {
    $total = $totalItens;
}

if ($total <= 0) {
    die('Serviço não possui valor definido.');
}

$empresa = [];
try {
    $empresa = $pdo->query('SELECT * FROM config_empresa ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $empresa = [];
}

$empresa = array_merge([
    'nome_empresa' => 'INFOSERVICE',
    'endereco' => '',
    'telefone' => '',
    'email' => '',
    'chave_pix' => ''
], $empresa);

$protocolo = $servico['protocolo'] ?: ('#' . $servico['id']);
$voltarUrl = $isAdmin
    ? '../views/admin/itens-chamado.php?id=' . (int)$servico['id']
    : '../views/cliente/detalhes-servico.php?id=' . (int)$servico['id'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recibo <?= h($protocolo) ?></title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Courier New',Courier,monospace}body{background:#f5f5f5;color:#000}.container{background:#fff;border:1px solid #000}.text-center{text-align:center}.text-left{text-align:left}.text-right{text-align:right}.bold{font-weight:bold}.uppercase{text-transform:uppercase}.divider{border-top:1px solid #000;margin:6px 0;width:100%}.double-divider{border-top:2px solid #000;margin:8px 0;width:100%}.linha{display:flex;justify-content:space-between;gap:8px;margin:4px 0}.header{text-align:center;margin-bottom:6px;padding-bottom:5px;border-bottom:1px solid #000}.empresa-nome{font-weight:bold;margin-bottom:2px}.empresa-info{margin-bottom:1px}.titulo{font-weight:bold;text-align:center;margin:8px 0}.servico-desc{margin:7px 0;padding:6px;border:1px solid #000;line-height:1.25;min-height:36px}.itens-table{width:100%;border-collapse:collapse;margin:8px 0}.itens-table th,.itens-table td{border-bottom:1px dotted #000;padding:4px 2px;vertical-align:top}.itens-table th{text-align:left;font-weight:bold}.itens-table .num{text-align:right;white-space:nowrap}.item-tipo{opacity:.8}.valor-container{text-align:center;margin:9px 0;padding:8px;border-top:2px solid #000;border-bottom:2px solid #000}.valor-total{font-weight:bold}.assinaturas{margin-top:18px;padding-top:10px;border-top:1px solid #000}.assinatura-linha{display:flex;justify-content:space-between;gap:12px}.assinatura{width:48%;text-align:center}.rodape{margin-top:10px;padding-top:6px;border-top:1px dashed #000;text-align:center}.botoes-mobile{text-align:center;margin:15px auto;padding:12px;max-width:520px;font-family:Arial,sans-serif}.btn-mobile{display:block;width:100%;padding:12px 10px;margin:8px 0;border:0;border-radius:8px;cursor:pointer;font-size:14px;font-family:Arial,sans-serif}.btn-print{background:#007bff;color:#fff}.btn-print-80{background:#111827;color:#fff}.btn-save{background:#28a745;color:#fff}.btn-close{background:#dc3545;color:#fff}.no-print{display:block}
body.recibo-80{width:80mm;max-width:80mm;margin:0 auto;font-size:10px}.recibo-80 .container{width:76mm;max-width:76mm;margin:0 auto;padding:4mm}.recibo-80 .empresa-nome{font-size:13px}.recibo-80 .empresa-info{font-size:9px}.recibo-80 .titulo{font-size:12px}.recibo-80 .servico-desc{font-size:10px}.recibo-80 .itens-table{font-size:9px}.recibo-80 .item-tipo{font-size:8px}.recibo-80 .valor-total{font-size:15px}.recibo-80 .assinatura,.recibo-80 .rodape{font-size:8px}
body.recibo-a4{width:210mm;max-width:210mm;margin:0 auto;font-size:12px}.recibo-a4 .container{width:180mm;max-width:180mm;margin:12mm auto;padding:12mm}.recibo-a4 .empresa-nome{font-size:20px}.recibo-a4 .empresa-info{font-size:12px}.recibo-a4 .titulo{font-size:18px}.recibo-a4 .servico-desc{font-size:12px}.recibo-a4 .itens-table{font-size:12px}.recibo-a4 .item-tipo{font-size:10px}.recibo-a4 .valor-total{font-size:22px}.recibo-a4 .assinatura,.recibo-a4 .rodape{font-size:11px}
@media print{.no-print{display:none!important}body{background:#fff!important}.container{border:0!important}body.recibo-80{width:80mm!important;max-width:80mm!important;margin:0!important}.recibo-80 .container{width:76mm!important;max-width:76mm!important;margin:0!important;padding:2mm!important}body.recibo-a4{width:210mm!important;max-width:210mm!important;margin:0!important}.recibo-a4 .container{width:180mm!important;max-width:180mm!important;margin:0 auto!important;padding:0!important}}
</style>
</head>
<body class="recibo-80">
<div class="container" id="recibo-container">
    <div class="header">
        <div class="empresa-nome uppercase"><?= h($empresa['nome_empresa'] ?: 'INFOSERVICE') ?></div>
        <?php if (!empty($empresa['endereco'])): ?><div class="empresa-info"><?= h($empresa['endereco']) ?></div><?php endif; ?>
        <?php if (!empty($empresa['telefone'])): ?><div class="empresa-info">Tel: <?= h($empresa['telefone']) ?></div><?php endif; ?>
        <?php if (!empty($empresa['email'])): ?><div class="empresa-info"><?= h($empresa['email']) ?></div><?php endif; ?>
    </div>

    <div class="double-divider"></div>
    <div class="titulo uppercase">Recibo de Serviço / Venda</div>
    <div class="divider"></div>

    <div class="linha">
        <div><span class="bold">DOC:</span> <?= h($protocolo) ?></div>
        <div><span class="bold">DATA:</span> <?= date('d/m/Y', strtotime($servico['data'])) ?></div>
    </div>
    <div class="linha"><div><span class="bold">CLIENTE:</span> <?= h(mb_strtoupper($servico['cliente_nome'] ?? '')) ?></div></div>
    <?php if (!empty($servico['cliente_telefone'])): ?><div class="linha"><div><span class="bold">TEL:</span> <?= h($servico['cliente_telefone']) ?></div></div><?php endif; ?>

    <div class="divider"></div>
    <div class="bold">SERVIÇO EXECUTADO:</div>
    <div class="servico-desc">
        <?php if (!empty($servico['titulo'])): ?><span class="bold"><?= h($servico['titulo']) ?></span><br><?php endif; ?>
        <?= nl2br(h($servico['observacao'] ?: $servico['descricao'])) ?>
    </div>

    <?php if ($itens): ?>
        <div class="bold">PRODUTOS/SERVIÇOS LANÇADOS:</div>
        <table class="itens-table">
            <thead><tr><th>Item</th><th class="num">Qtd</th><th class="num">Unit</th><th class="num">Total</th></tr></thead>
            <tbody>
            <?php foreach ($itens as $item): ?>
                <tr>
                    <td><span class="bold"><?= h($item['nome']) ?></span><br><span class="item-tipo"><?= h(mb_strtoupper($item['tipo'])) ?></span></td>
                    <td class="num"><?= h(qtd($item['quantidade'])) ?></td>
                    <td class="num"><?= h(number_format((float)$item['valor_unitario'], 2, ',', '.')) ?></td>
                    <td class="num"><?= h(number_format((float)$item['subtotal'], 2, ',', '.')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="valor-container"><div class="valor-total">TOTAL: <?= money($total) ?></div></div>
    <div class="divider"></div>

    <div class="linha">
        <div><span class="bold">STATUS:</span> <?= h(mb_strtoupper($servico['status'])) ?></div>
        <div><span class="bold">EMISSÃO:</span> <?= date('H:i') ?></div>
    </div>

    <?php if (!empty($empresa['chave_pix'])): ?>
        <div class="divider"></div>
        <div><span class="bold">PIX:</span> <?= h($empresa['chave_pix']) ?></div>
    <?php endif; ?>

    <div class="assinaturas">
        <div class="assinatura-linha">
            <div class="assinatura"><div>___________________</div><div>Cliente</div></div>
            <div class="assinatura"><div>___________________</div><div>Técnico</div></div>
        </div>
    </div>

    <div class="rodape">
        <div>*** COMPROVANTE VÁLIDO ***</div>
        <div>Gerado em <?= date('d/m/Y H:i') ?></div>
    </div>
</div>

<div class="botoes-mobile no-print" id="botoesMobile">
    <button class="btn-mobile btn-print" onclick="imprimirRecibo('a4')">🖨️ Imprimir em A4</button>
    <button class="btn-mobile btn-print-80" onclick="imprimirRecibo('80')">🧾 Imprimir em impressora 80mm</button>
    <button class="btn-mobile btn-save" onclick="salvarComoImagem()">💾 Salvar como Imagem</button>
    <button class="btn-mobile btn-close" onclick="voltarPagina()">↩️ Voltar</button>
</div>

<script>
function definirModoRecibo(modo) {
    document.body.classList.remove('recibo-a4', 'recibo-80');
    document.body.classList.add(modo === 'a4' ? 'recibo-a4' : 'recibo-80');
}

function aplicarEstiloImpressao(modo) {
    let style = document.getElementById('printModeStyle');
    if (!style) {
        style = document.createElement('style');
        style.id = 'printModeStyle';
        document.head.appendChild(style);
    }

    if (modo === 'a4') {
        style.textContent = '@page{size:A4;margin:12mm;}';
    } else {
        style.textContent = '@page{size:80mm 297mm;margin:2mm;}';
    }
}

function imprimirRecibo(modo) {
    definirModoRecibo(modo);
    aplicarEstiloImpressao(modo);
    setTimeout(() => window.print(), 120);
}

function voltarPagina() {
    window.location.href = '<?= h($voltarUrl) ?>';
}

function salvarComoImagem() {
    definirModoRecibo('80');
    const elemento = document.getElementById('recibo-container');
    html2canvas(elemento, { scale: 3, backgroundColor: '#ffffff' }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'recibo-<?= (int)$servico['id'] ?>-<?= date('Y-m-d') ?>.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    }).catch(() => alert('Não foi possível gerar a imagem do recibo.'));
}
</script>
</body>
</html>
