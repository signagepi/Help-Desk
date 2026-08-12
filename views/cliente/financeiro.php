<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function money($v) {
    return 'R$ ' . number_format((float)$v, 2, ',', '.');
}

function primeiro_texto($a, $b = '', $c = '') {
    foreach ([$a, $b, $c] as $v) {
        if (trim((string)$v) !== '') {
            return $v;
        }
    }
    return '-';
}

function texto_status($status) {
    $status = strtolower((string)$status);

    $map = [
        'pendente'      => 'Pendente',
        'em_andamento'  => 'Em andamento',
        'em-andamento'  => 'Em andamento',
        'finalizado'    => 'Finalizado',
        'concluida'     => 'Concluído',
        'concluido'     => 'Concluído',
        'cancelado'     => 'Cancelado',
        'nao-resolvido' => 'Não resolvido'
    ];

    return $map[$status] ?? ucfirst(str_replace(['_', '-'], ' ', $status));
}

function status_class($status) {
    return 'st-' . str_replace('_', '-', strtolower((string)$status));
}

function fotoItem($foto) {
    $foto = basename((string)$foto);
    return $foto !== '' ? '../../uploads/catalogo/' . rawurlencode($foto) : '';
}

function mes_nome($ym) {
    $nomes = [
        '01' => 'Janeiro',
        '02' => 'Fevereiro',
        '03' => 'Março',
        '04' => 'Abril',
        '05' => 'Maio',
        '06' => 'Junho',
        '07' => 'Julho',
        '08' => 'Agosto',
        '09' => 'Setembro',
        '10' => 'Outubro',
        '11' => 'Novembro',
        '12' => 'Dezembro'
    ];

    if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
        return 'Todos os meses';
    }

    [$y, $m] = explode('-', $ym);

    return ($nomes[$m] ?? $m) . ' de ' . $y;
}

$cliente_id = (int)$_SESSION['cliente_id'];

$competencia = isset($_GET['competencia']) && preg_match('/^\d{4}-\d{2}$/', $_GET['competencia'])
    ? $_GET['competencia']
    : date('Y-m');

$verTodos = isset($_GET['todos']) && $_GET['todos'] === '1';

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

$mesesStmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(data, '%Y-%m') competencia,
        COUNT(*) total,
        SUM(COALESCE(preco, 0)) valor
    FROM solicitacoes
    WHERE cliente_id = ?
      AND preco IS NOT NULL
      AND preco > 0
    GROUP BY DATE_FORMAT(data, '%Y-%m')
    ORDER BY competencia DESC
");
$mesesStmt->execute([$cliente_id]);
$meses = $mesesStmt->fetchAll(PDO::FETCH_ASSOC);

$params = [$cliente_id];
$whereMes = '';

if (!$verTodos) {
    $whereMes = " AND DATE_FORMAT(data, '%Y-%m') = ? ";
    $params[] = $competencia;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM solicitacoes
    WHERE cliente_id = ?
      AND preco IS NOT NULL
      AND preco > 0
      $whereMes
    ORDER BY data DESC, id DESC
");
$stmt->execute($params);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$itensPorSolicitacao = [];
if ($itens) {
    $idsSolicitacoes = [];
    foreach ($itens as $itemSolicitacao) {
        $idsSolicitacoes[] = (int)$itemSolicitacao['id'];
    }
    $placeholders = implode(',', array_fill(0, count($idsSolicitacoes), '?'));
    $stmtItens = $pdo->prepare("SELECT si.*, COALESCE(NULLIF(si.foto, ''), ci.foto) AS foto_exibir FROM solicitacao_itens si LEFT JOIN catalogo_itens ci ON ci.id = si.catalogo_item_id WHERE si.solicitacao_id IN ($placeholders) ORDER BY si.id ASC");
    $stmtItens->execute($idsSolicitacoes);
    foreach ($stmtItens->fetchAll(PDO::FETCH_ASSOC) as $itemChamado) {
        $sid = (int)$itemChamado['solicitacao_id'];
        if (!isset($itensPorSolicitacao[$sid])) {
            $itensPorSolicitacao[$sid] = [];
        }
        $itensPorSolicitacao[$sid][] = $itemChamado;
    }
}

$total = 0;
$aberto = 0;
$finalizado = 0;
$qtdFinalizados = 0;
$qtdAbertos = 0;
$porMes = [];

foreach ($itens as $i) {
    $valor = (float)($i['preco'] ?? 0);
    $total += $valor;

    $mes = date('Y-m', strtotime($i['data'] ?? 'now'));

    if (!isset($porMes[$mes])) {
        $porMes[$mes] = [
            'qtd' => 0,
            'valor' => 0
        ];
    }

    $porMes[$mes]['qtd']++;
    $porMes[$mes]['valor'] += $valor;

    $status = strtolower((string)($i['status'] ?? ''));

    if (in_array($status, ['finalizado', 'concluida', 'concluido'], true)) {
        $finalizado += $valor;
        $qtdFinalizados++;
    } else {
        $aberto += $valor;
        $qtdAbertos++;
    }
}

$documento = 'EXT-' . ($verTodos ? 'GERAL' : str_replace('-', '', $competencia)) . '-' . str_pad((string)$cliente_id, 4, '0', STR_PAD_LEFT);
$geradoEm = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Financeiro - Infoservice</title>

<link rel="stylesheet" href="../../assets/css/helpdesk-pro.css">
<style>
.financeiro-itens-mini{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}.financeiro-item-mini{display:flex;gap:8px;align-items:center;border:1px solid var(--hd-border,#d8dee9);border-radius:12px;padding:6px 8px;background:#f8fafc;max-width:260px}.financeiro-item-foto{width:38px;height:38px;border-radius:10px;overflow:hidden;background:#eef2f7;display:flex;align-items:center;justify-content:center;flex:0 0 38px}.financeiro-item-foto img{width:100%;height:100%;object-fit:contain;background:#fff;padding:3px}.financeiro-item-mini strong{display:block;font-size:12px}.financeiro-item-mini small{display:block;color:#64748b;font-size:11px}
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
                    💳
                <?php endif; ?>
            </div>

            <div>
                <h1>Financeiro</h1>
                <p>Extrato bancário mensal de serviços, valores lançados e detalhamento dos chamados.</p>
            </div>
        </div>

        <div class="hd-user">
            <span class="hd-chip"><?= h($cliente['nome'] ?? 'Cliente') ?></span>
            <a class="hd-chip" href="dashboard.php">Dashboard</a>
            <a class="hd-chip" href="mensagens.php">Mensagens <b data-chat-badge></b></a>
            <a class="hd-chip" href="../../auth/logout.php">Sair</a>
        </div>

    </div>
</header>

<main class="hd-main hd-financial-main">
<div class="hd-container">

<section class="hd-bank-statement hd-card">

    <div class="hd-bank-head">

        <div class="hd-bank-company">
            <div class="hd-bank-logo">
                <?php if ($logo): ?>
                    <img src="<?= h($logo) ?>" alt="Logo do cliente">
                <?php else: ?>
                    IS
                <?php endif; ?>
            </div>

            <div>
                <span>Extrato financeiro de serviços</span>
                <h2><?= h($empresa['nome_empresa'] ?? 'Infoservice') ?></h2>
                <p>
                    <?= h($empresa['telefone'] ?? '') ?>
                    <?= !empty($empresa['email']) ? ' • ' . h($empresa['email']) : '' ?>
                </p>
            </div>
        </div>

        <div class="hd-bank-doc">
            <span>Documento</span>
            <strong><?= h($documento) ?></strong>
            <small>Gerado em <?= h($geradoEm) ?></small>
        </div>

    </div>

    <div class="hd-bank-account-grid">

        <div>
            <span>Cliente</span>
            <strong><?= h($cliente['nome'] ?? '-') ?></strong>
            <small>
                <?= h($cliente['email'] ?? '') ?>
                <?= !empty($cliente['telefone']) ? ' • ' . h($cliente['telefone']) : '' ?>
            </small>
        </div>

        <div>
            <span>Competência</span>
            <strong><?= h($verTodos ? 'Todos os meses' : mes_nome($competencia)) ?></strong>
            <small>Serviços filtrados por mês de lançamento</small>
        </div>

        <div>
            <span>Banco/Pix da empresa</span>
            <strong><?= h(primeiro_texto($empresa['banco_nome'] ?? '', 'PIX')) ?></strong>
            <small>
                <?php if (!empty($empresa['chave_pix'])): ?>
                    PIX: <?= h($empresa['chave_pix']) ?>
                <?php endif; ?>

                <?php if (!empty($empresa['banco_agencia']) || !empty($empresa['banco_conta'])): ?>
                    <?= !empty($empresa['chave_pix']) ? ' • ' : '' ?>
                    Ag. <?= h($empresa['banco_agencia'] ?? '-') ?> /
                    Conta <?= h($empresa['banco_conta'] ?? '-') ?>
                <?php endif; ?>
            </small>
        </div>

    </div>

    <div class="hd-bank-summary">

        <div>
            <span>Total da competência</span>
            <strong><?= money($total) ?></strong>
            <small><?= count($itens) ?> lançamento(s)</small>
        </div>

        <div>
            <span>Aguardando conclusão</span>
            <strong><?= money($aberto) ?></strong>
            <small><?= $qtdAbertos ?> chamado(s)</small>
        </div>

        <div>
            <span>Serviços finalizados</span>
            <strong><?= money($finalizado) ?></strong>
            <small><?= $qtdFinalizados ?> chamado(s)</small>
        </div>

    </div>

    <div class="hd-bank-actions hd-no-print">
        <button class="hd-btn hd-btn-primary" onclick="window.print()">🖨️ Imprimir extrato</button>
        <a class="hd-btn hd-btn-outline" href="mensagens.php">💬 Tirar dúvida sobre cobrança</a>
        <a class="hd-btn hd-btn-outline" href="solicitar.php">➕ Abrir chamado</a>
    </div>

</section>

<?php if ($verTodos && count($porMes) > 1): ?>
<section class="hd-card hd-card-pad hd-no-print">

    <div class="hd-section-head">
        <div>
            <h2>Resumo separado por mês</h2>
            <p>Visão rápida dos valores realizados em cada competência.</p>
        </div>
    </div>

    <div class="hd-month-grid">
        <?php foreach ($porMes as $ym => $r): ?>
            <a class="hd-month-card" href="?competencia=<?= h($ym) ?>">
                <span><?= h(mes_nome($ym)) ?></span>
                <strong><?= money($r['valor']) ?></strong>
                <small><?= (int)$r['qtd'] ?> serviço(s)</small>
            </a>
        <?php endforeach; ?>
    </div>

</section>
<?php endif; ?>

<section class="hd-card hd-card-pad hd-bank-table-card">

    <div class="hd-section-head">
        <div>
            <h2>Demonstrativo detalhado</h2>
            <p>Cada linha mostra o serviço cobrado, protocolo, descrição, execução técnica e valor.</p>
        </div>

        <span class="hd-badge pr-media hd-no-print">Extrato mensal</span>
    </div>

    <form method="GET" class="hd-form-grid hd-month-filter hd-no-print" style="margin-bottom: 22px;">

        <div class="hd-field">
            <label class="hd-label">Escolha o mês do extrato</label>

            <select class="hd-input" name="competencia">
                <?php if (!$meses): ?>
                    <option value="<?= h(date('Y-m')) ?>">
                        <?= h(mes_nome(date('Y-m'))) ?>
                    </option>
                <?php endif; ?>

                <?php foreach ($meses as $m): ?>
                    <option value="<?= h($m['competencia']) ?>" <?= $competencia === $m['competencia'] ? 'selected' : '' ?>>
                        <?= h(mes_nome($m['competencia'])) ?>
                        — <?= (int)$m['total'] ?> serviço(s)
                        — <?= money($m['valor']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="hd-field">
            <label class="hd-label">Visualização</label>

            <div class="hd-actions">
                <button class="hd-btn hd-btn-primary" type="submit">🔎 Ver mês</button>
                <a class="hd-btn hd-btn-outline" href="?todos=1">Ver todos</a>
            </div>
        </div>

    </form>

    <?php if (!$itens): ?>

        <div class="hd-empty">
            💳
            <strong>Nenhum valor neste mês</strong>
            Escolha outro mês no filtro ou aguarde a equipe técnica lançar valores nos chamados.
        </div>

    <?php else: ?>

        <div class="hd-table-wrap">

            <table class="hd-table hd-bank-table">

                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Documento</th>
                        <th>Histórico / Serviço</th>
                        <th>Status</th>
                        <th>Valor</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($itens as $i): ?>

                    <?php
                    $titulo = primeiro_texto(
                        $i['titulo'] ?? '',
                        mb_substr((string)($i['descricao'] ?? ''), 0, 90),
                        'Serviço técnico'
                    );

                    $protocolo = primeiro_texto(
                        $i['protocolo'] ?? '',
                        '#' . ($i['id'] ?? '')
                    );

                    $categoria = primeiro_texto($i['categoria'] ?? '', 'Suporte técnico');

                    $equipamento = trim((string)($i['equipamento'] ?? ''));
                    $local = trim((string)($i['local_atendimento'] ?? ''));
                    $descricao = trim((string)($i['descricao'] ?? ''));
                    $obs = trim((string)($i['observacao'] ?? ''));
                    ?>

                    <tr>

                        <td class="hd-nowrap">
                            <strong><?= h(date('d/m/Y', strtotime($i['data'] ?? 'now'))) ?></strong>
                            <small><?= h(date('H:i', strtotime($i['data'] ?? 'now'))) ?></small>
                        </td>

                        <td>
                            <strong><?= h($protocolo) ?></strong>
                            <small>Chamado #<?= h($i['id'] ?? '-') ?></small>
                        </td>

                        <td>
                            <div class="hd-bank-service-title">
                                <a href="detalhes-servico.php?id=<?= (int)($i['id'] ?? 0) ?>" style="color:inherit;text-decoration:none">
                                    <?= h($titulo) ?>
                                </a>
                            </div>

                            <div class="hd-bank-service-meta">
                                Categoria: <?= h($categoria) ?>

                                <?php if ($equipamento): ?>
                                    • Equipamento: <?= h($equipamento) ?>
                                <?php endif; ?>

                                <?php if ($local): ?>
                                    • Local: <?= h($local) ?>
                                <?php endif; ?>
                            </div>

                            <?php if ($descricao): ?>
                                <div class="hd-bank-service-desc">
                                    <strong>Solicitação:</strong> <?= nl2br(h($descricao)) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($obs): ?>
                                <div class="hd-bank-service-desc">
                                    <strong>Execução técnica:</strong> <?= nl2br(h($obs)) ?>
                                </div>
                            <?php endif; ?>

                            <?php $miniItens = $itensPorSolicitacao[(int)($i['id'] ?? 0)] ?? []; ?>
                            <?php if ($miniItens): ?>
                                <div class="financeiro-itens-mini">
                                    <?php foreach (array_slice($miniItens, 0, 4) as $mi): ?>
                                        <?php $fotoMini = fotoItem($mi['foto_exibir'] ?? $mi['foto'] ?? ''); ?>
                                        <div class="financeiro-item-mini">
                                            <div class="financeiro-item-foto"><?php if ($fotoMini): ?><img src="<?= h($fotoMini) ?>" alt="Foto de <?= h($mi['nome']) ?>"><?php else: ?>📷<?php endif; ?></div>
                                            <div><strong><?= h($mi['nome']) ?></strong><small><?= h(ucfirst($mi['tipo'])) ?> • <?= money($mi['subtotal']) ?></small></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="hd-badge <?= h(status_class($i['status'] ?? 'pendente')) ?>">
                                <?= h(texto_status($i['status'] ?? 'pendente')) ?>
                            </span>
                        </td>

                        <td class="hd-money-cell">
                            <strong><?= money($i['preco'] ?? 0) ?></strong>
                            <div class="hd-actions hd-no-print" style="margin-top:8px;gap:6px">
                                <a class="hd-btn hd-btn-light" href="detalhes-servico.php?id=<?= (int)($i['id'] ?? 0) ?>">Detalhes</a>
                                <a class="hd-btn hd-btn-outline" href="../../controllers/gerar-recibo.php?id=<?= (int)($i['id'] ?? 0) ?>" target="_blank">Recibo</a>
                            </div>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="4">
                            Total do extrato <?= h($verTodos ? 'geral' : mes_nome($competencia)) ?>
                        </td>
                        <td><?= money($total) ?></td>
                    </tr>
                </tfoot>

            </table>

        </div>

    <?php endif; ?>

</section>

<section class="hd-card hd-card-pad hd-bank-notes">

    <h3>Observações financeiras</h3>

    <p>
        Este demonstrativo lista somente chamados com valor definido pela equipe técnica.
        Para contestar ou tirar dúvidas sobre qualquer lançamento, use a área de mensagens
        informando o protocolo do chamado.
    </p>

    <p class="hd-print-only">
        Assinatura/Conferência: ________________________________________________
    </p>

</section>

</div>
</main>

</div>

<script>
window.HD_CHAT_CONFIG = {
    endpointBase: '../../controllers/',
    chatUrl: 'mensagens.php'
};
</script>

<script src="../../assets/js/live-chat-alerts.js"></script>
<script src="../../assets/js/helpdesk-pro.js"></script>

</body>
</html>
