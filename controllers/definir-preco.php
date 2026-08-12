<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login-admin.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$preco = str_replace(['.', ','], ['', '.'], (string)($_POST['preco'] ?? '0'));
$preco = is_numeric($preco) ? (float)$preco : 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE solicitacoes SET preco = ?, atualizado_em = NOW() WHERE id = ?");
        $stmt->execute([$preco, $id]);

        $stmt = $pdo->prepare("INSERT INTO solicitacao_atividades (solicitacao_id, usuario_tipo, usuario_id, acao, descricao) VALUES (?, 'admin', ?, 'valor', ?)");
        $stmt->execute([$id, (int)$_SESSION['admin_id'], 'Valor definido: R$ ' . number_format($preco, 2, ',', '.')]);

        $_SESSION['success_msg'] = 'Valor atualizado com sucesso.';
    } catch (Throwable $e) {
        error_log('Erro ao definir preço: ' . $e->getMessage());
        $_SESSION['error_msg'] = 'Erro ao atualizar valor.';
    }
}

header('Location: ../views/admin/dashboard.php');
exit;
