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

$editarId = (int)($_GET['editar'] ?? 0);
$itemEditar = null;
if ($editarId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM catalogo_itens WHERE id=?');
    $stmt->execute([$editarId]);
    $itemEditar = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$filtro = $_GET['tipo'] ?? '';
$params = [];
$where = '';
if (in_array($filtro, ['produto', 'servico'], true)) {
    $where = ' WHERE tipo = ? ';
    $params[] = $filtro;
}

$stmt = $pdo->prepare("SELECT * FROM catalogo_itens $where ORDER BY ativo DESC, tipo ASC, nome ASC");
$stmt->execute($params);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success_msg'] ?? '';
$error = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catálogo - Infoservice</title>
<link rel="stylesheet" href="../../assets/css/helpdesk-pro.css">
<style>
.catalogo-foto-box{width:72px;height:72px;border-radius:14px;overflow:hidden;background:#eef2f7;border:1px solid var(--hd-border,#d8dee9);display:flex;align-items:center;justify-content:center;font-weight:900;color:#64748b;flex:0 0 72px}.catalogo-foto-box img{width:100%;height:100%;object-fit:cover}.catalogo-item-flex{display:flex;align-items:center;gap:12px}.catalogo-preview{display:flex;gap:12px;align-items:center;margin-top:8px;padding:10px;border:1px dashed var(--hd-border,#d8dee9);border-radius:14px;background:#f8fafc}.catalogo-preview small{display:block;color:#64748b;margin-top:2px}.catalogo-file-hint{font-size:12px;color:#64748b;margin-top:6px}.catalogo-remove-foto{display:flex;gap:8px;align-items:center;margin-top:8px;font-weight:700}
</style>
</head>
<body>
<div class="hd-page">
<header class="hd-topbar">
    <div class="hd-container hd-topbar-inner">
        <div class="hd-brand">
            <div class="hd-brand-icon">🧾</div>
            <div>
                <h1>Catálogo de produtos e serviços</h1>
                <p>Cadastre itens com foto para identificação rápida no painel técnico e na área do cliente.</p>
            </div>
        </div>
        <div class="hd-user">
            <a class="hd-chip" href="dashboard.php">Dashboard</a>
            <a class="hd-chip" href="financeiro.php">Financeiro</a>
            <a class="hd-chip" href="../../auth/logout.php">Sair</a>
        </div>
    </div>
</header>
<main class="hd-main">
<div class="hd-container">
<?php if ($success): ?><div class="hd-alert hd-alert-success"><?= h($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="hd-alert hd-alert-error"><?= h($error) ?></div><?php endif; ?>

<div class="hd-grid">
<section class="hd-card hd-card-pad">
    <div class="hd-section-head">
        <div>
            <h2><?= $itemEditar ? 'Editar item' : 'Novo item' ?></h2>
            <p>Use serviço para mão de obra e produto para peças, materiais e equipamentos.</p>
        </div>
    </div>
    <form method="POST" action="../../controllers/catalogo-salvar.php" enctype="multipart/form-data" class="hd-form-grid">
        <input type="hidden" name="id" value="<?= (int)($itemEditar['id'] ?? 0) ?>">
        <div class="hd-field">
            <label class="hd-label">Tipo</label>
            <select name="tipo" class="hd-select">
                <?php $tipoAtual = $itemEditar['tipo'] ?? 'servico'; ?>
                <option value="servico" <?= $tipoAtual === 'servico' ? 'selected' : '' ?>>Serviço</option>
                <option value="produto" <?= $tipoAtual === 'produto' ? 'selected' : '' ?>>Produto</option>
            </select>
        </div>
        <div class="hd-field">
            <label class="hd-label">Nome</label>
            <input class="hd-input" name="nome" required value="<?= h($itemEditar['nome'] ?? '') ?>" placeholder="Ex.: Instalação de roteador">
        </div>
        <div class="hd-field">
            <label class="hd-label">Preço padrão</label>
            <input class="hd-input" name="preco" value="<?= h(isset($itemEditar['preco']) ? number_format((float)$itemEditar['preco'], 2, ',', '.') : '') ?>" placeholder="0,00">
        </div>
        <div class="hd-field">
            <label class="hd-label">Unidade</label>
            <input class="hd-input" name="unidade" value="<?= h($itemEditar['unidade'] ?? 'un') ?>" placeholder="un, hora, metro">
        </div>
        <div class="hd-field">
            <label class="hd-label">Estoque atual opcional</label>
            <input class="hd-input" name="estoque_atual" value="<?= h($itemEditar['estoque_atual'] ?? '') ?>" placeholder="Opcional para produtos">
        </div>
        <div class="hd-field">
            <label class="hd-label">Status</label>
            <label style="display:flex;gap:8px;align-items:center;padding:12px 0;font-weight:800">
                <input type="checkbox" name="ativo" value="1" <?= !isset($itemEditar['ativo']) || (int)$itemEditar['ativo'] === 1 ? 'checked' : '' ?>> Ativo no catálogo
            </label>
        </div>
        <div class="hd-field hd-field-full">
            <label class="hd-label">Foto do produto/serviço</label>
            <input class="hd-input" type="file" name="foto" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="catalogo-file-hint">Formatos aceitos: JPG, PNG, WEBP ou GIF. Tamanho máximo: 4MB.</div>
            <?php if (!empty($itemEditar['foto'])): ?>
                <div class="catalogo-preview">
                    <div class="catalogo-foto-box"><img src="<?= h(fotoItem($itemEditar['foto'])) ?>" alt="Foto atual"></div>
                    <div>
                        <strong>Foto atual cadastrada</strong>
                        <small><?= h(basename($itemEditar['foto'])) ?></small>
                        <label class="catalogo-remove-foto"><input type="checkbox" name="remover_foto" value="1"> Remover foto atual</label>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="hd-field hd-field-full">
            <label class="hd-label">Descrição</label>
            <textarea class="hd-textarea" name="descricao" placeholder="Detalhes técnicos, observações ou padrão de execução."><?= h($itemEditar['descricao'] ?? '') ?></textarea>
        </div>
        <div class="hd-field hd-field-full">
            <button class="hd-btn hd-btn-primary" type="submit">Salvar item</button>
            <?php if ($itemEditar): ?><a class="hd-btn hd-btn-outline" href="catalogo.php">Cancelar edição</a><?php endif; ?>
        </div>
    </form>
</section>

<section class="hd-card hd-card-pad">
    <div class="hd-section-head">
        <div>
            <h2>Itens cadastrados</h2>
            <p>Produtos e serviços disponíveis para lançamento em chamados.</p>
        </div>
    </div>
    <div class="hd-actions" style="margin-bottom:14px">
        <a class="hd-btn hd-btn-outline" href="catalogo.php">Todos</a>
        <a class="hd-btn hd-btn-outline" href="?tipo=servico">Serviços</a>
        <a class="hd-btn hd-btn-outline" href="?tipo=produto">Produtos</a>
    </div>
    <?php if (!$itens): ?>
        <div class="hd-empty">📦<strong>Nenhum item cadastrado</strong>Cadastre o primeiro serviço ou produto.</div>
    <?php else: ?>
        <div class="hd-table-wrap">
            <table class="hd-table">
                <thead><tr><th>Item</th><th>Tipo</th><th>Preço</th><th>Status</th><th>Ações</th></tr></thead>
                <tbody>
                <?php foreach ($itens as $item): ?>
                    <?php $foto = fotoItem($item['foto'] ?? ''); ?>
                    <tr>
                        <td>
                            <div class="catalogo-item-flex">
                                <div class="catalogo-foto-box">
                                    <?php if ($foto): ?><img src="<?= h($foto) ?>" alt="Foto de <?= h($item['nome']) ?>"><?php else: ?>📷<?php endif; ?>
                                </div>
                                <div><strong><?= h($item['nome']) ?></strong><br><small><?= h($item['descricao'] ?? '') ?></small></div>
                            </div>
                        </td>
                        <td><span class="hd-badge <?= $item['tipo'] === 'produto' ? 'pr-alta' : 'pr-media' ?>"><?= h(ucfirst($item['tipo'])) ?></span></td>
                        <td><strong><?= money($item['preco']) ?></strong><br><small><?= h($item['unidade'] ?: 'un') ?></small></td>
                        <td><?= (int)$item['ativo'] === 1 ? '<span class="hd-badge st-finalizado">Ativo</span>' : '<span class="hd-badge st-cancelado">Inativo</span>' ?></td>
                        <td>
                            <div class="hd-actions">
                                <a class="hd-btn hd-btn-light" href="?editar=<?= (int)$item['id'] ?>">Editar</a>
                                <form method="POST" action="../../controllers/catalogo-excluir.php" onsubmit="return confirm('Desativar este item?')">
                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                    <button class="hd-btn hd-btn-danger" type="submit">Desativar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
</div>
</div>
</main>
</div>
<script>window.HD_CHAT_CONFIG={endpointBase:'../../controllers/',chatUrl:'mensagens.php'};</script>
<script src="../../assets/js/live-chat-alerts.js"></script>
<script src="../../assets/js/helpdesk-pro.js"></script>
</body>
</html>
