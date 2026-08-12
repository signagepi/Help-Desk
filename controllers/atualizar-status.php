<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login-admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['solicitacao_id'] ?? 0);
    $status = trim((string)($_POST['novo_status'] ?? ''));
    $permitidos = ['pendente', 'em andamento', 'finalizado', 'nao resolvido', 'cancelado'];

    if ($id > 0 && in_array($status, $permitidos, true)) {
        try {
            $finalizado = $status === 'finalizado' ? ', finalizado_em = NOW()' : '';
            $stmt = $pdo->prepare("UPDATE solicitacoes SET status = ?, atualizado_em = NOW() {$finalizado} WHERE id = ?");
            $stmt->execute([$status, $id]);

            $stmt = $pdo->prepare("INSERT INTO solicitacao_atividades (solicitacao_id, usuario_tipo, usuario_id, acao, descricao) VALUES (?, 'admin', ?, 'status', ?)");
            $stmt->execute([$id, (int)$_SESSION['admin_id'], 'Status alterado para: ' . $status]);

            $_SESSION['success_msg'] = 'Status atualizado com sucesso.';
        } catch (Throwable $e) {
            error_log('Erro ao atualizar status: ' . $e->getMessage());
            $_SESSION['error_msg'] = 'Erro ao atualizar status. Verifique se o SQL de modernização foi executado.';
        }
    }
}

header('Location: ../views/admin/dashboard.php');
exit;
