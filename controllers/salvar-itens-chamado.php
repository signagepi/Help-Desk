<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login-admin.php');
    exit;
}

function valorDecimal($valor) {
    $valor = trim((string)$valor);
    if ($valor === '') return 0;
    $valor = str_replace(['.', ','], ['', '.'], $valor);
    return is_numeric($valor) ? (float)$valor : 0;
}

function recalcularTotalChamado(PDO $pdo, int $solicitacaoId): void {
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(subtotal),0) FROM solicitacao_itens WHERE solicitacao_id=?');
    $stmt->execute([$solicitacaoId]);
    $total = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare('UPDATE solicitacoes SET preco=?, atualizado_em=NOW() WHERE id=?');
    $stmt->execute([$total, $solicitacaoId]);
}

$solicitacaoId = (int)($_POST['solicitacao_id'] ?? $_GET['solicitacao_id'] ?? 0);
$acao = (string)($_POST['acao'] ?? 'adicionar_catalogo');

if ($solicitacaoId <= 0) {
    $_SESSION['error_msg'] = 'Chamado não informado.';
    header('Location: ../views/admin/dashboard.php');
    exit;
}

try {
    if ($acao === 'remover') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId <= 0) throw new Exception('Item não informado.');

        $stmt = $pdo->prepare('DELETE FROM solicitacao_itens WHERE id=? AND solicitacao_id=?');
        $stmt->execute([$itemId, $solicitacaoId]);
        recalcularTotalChamado($pdo, $solicitacaoId);

        $stmt = $pdo->prepare("INSERT INTO solicitacao_atividades (solicitacao_id, usuario_tipo, usuario_id, acao, descricao) VALUES (?, 'admin', ?, 'item_removido', ?)");
        $stmt->execute([$solicitacaoId, (int)$_SESSION['admin_id'], 'Item removido do chamado.']);
        $_SESSION['success_msg'] = 'Item removido e total recalculado.';
    } else {
        $catalogoId = (int)($_POST['catalogo_item_id'] ?? 0);
        $quantidade = valorDecimal($_POST['quantidade'] ?? '1');
        $quantidade = $quantidade > 0 ? $quantidade : 1;
        $foto = null;

        if ($catalogoId > 0) {
            $stmt = $pdo->prepare('SELECT * FROM catalogo_itens WHERE id=? AND ativo=1');
            $stmt->execute([$catalogoId]);
            $catalogo = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$catalogo) throw new Exception('Item do catálogo não encontrado ou inativo.');

            $tipo = $catalogo['tipo'];
            $nome = $catalogo['nome'];
            $descricao = $catalogo['descricao'];
            $foto = !empty($catalogo['foto']) ? basename((string)$catalogo['foto']) : null;
            $valorUnitario = valorDecimal($_POST['valor_unitario'] ?? '');
            if ($valorUnitario <= 0) $valorUnitario = (float)$catalogo['preco'];
        } else {
            $tipo = $_POST['tipo_manual'] ?? 'servico';
            $tipo = in_array($tipo, ['produto', 'servico'], true) ? $tipo : 'servico';
            $nome = trim((string)($_POST['nome_manual'] ?? ''));
            $descricao = trim((string)($_POST['descricao_manual'] ?? ''));
            $valorUnitario = valorDecimal($_POST['valor_unitario'] ?? '0');
            if ($nome === '') throw new Exception('Informe o nome do item manual.');
        }

        $subtotal = round($quantidade * $valorUnitario, 2);
        $stmt = $pdo->prepare('INSERT INTO solicitacao_itens (solicitacao_id, catalogo_item_id, tipo, nome, descricao, foto, quantidade, valor_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$solicitacaoId, $catalogoId > 0 ? $catalogoId : null, $tipo, $nome, $descricao, $foto, $quantidade, $valorUnitario, $subtotal]);

        recalcularTotalChamado($pdo, $solicitacaoId);

        $stmt = $pdo->prepare("INSERT INTO solicitacao_atividades (solicitacao_id, usuario_tipo, usuario_id, acao, descricao) VALUES (?, 'admin', ?, 'item_adicionado', ?)");
        $stmt->execute([$solicitacaoId, (int)$_SESSION['admin_id'], 'Item adicionado: ' . $nome . ' - R$ ' . number_format($subtotal, 2, ',', '.')]);
        $_SESSION['success_msg'] = 'Item lançado e total recalculado.';
    }
} catch (Throwable $e) {
    error_log('Erro ao salvar itens do chamado: ' . $e->getMessage());
    $_SESSION['error_msg'] = $e->getMessage();
}

header('Location: ../views/admin/itens-chamado.php?id=' . $solicitacaoId);
exit;
