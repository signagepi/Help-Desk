<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login-admin.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare('UPDATE catalogo_itens SET ativo=0, atualizado_em=NOW() WHERE id=?');
        $stmt->execute([$id]);
        $_SESSION['success_msg'] = 'Item desativado do catálogo.';
    } catch (Throwable $e) {
        error_log('Erro ao desativar item: ' . $e->getMessage());
        $_SESSION['error_msg'] = 'Erro ao desativar item.';
    }
}

header('Location: ../views/admin/catalogo.php');
exit;
