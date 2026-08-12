<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login-admin.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$observacao = trim((string)($_POST['observacao'] ?? ''));

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE solicitacoes SET observacao = ?, atualizado_em = NOW() WHERE id = ?");
        $stmt->execute([$observacao, $id]);

        $stmt = $pdo->prepare("INSERT INTO solicitacao_atividades (solicitacao_id, usuario_tipo, usuario_id, acao, descricao) VALUES (?, 'admin', ?, 'observacao', ?)");
        $stmt->execute([$id, (int)$_SESSION['admin_id'], 'Observação técnica atualizada.']);

        $_SESSION['success_msg'] = 'Observação atualizada.';
    } catch (Throwable $e) {
        error_log('Erro ao atualizar observação: ' . $e->getMessage());
        $_SESSION['error_msg'] = 'Erro ao atualizar observação.';
    }
}

header('Location: ../views/admin/dashboard.php');
exit;
