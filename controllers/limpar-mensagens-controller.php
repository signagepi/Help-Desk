<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login-admin.php');
    exit;
}

$tipo = $_POST['tipo'] ?? '';
$cliente_id = $_POST['cliente_id'] ?? null;

switch ($tipo) {
    case 'todas':
        $pdo->exec("DELETE FROM mensagens");
        $msg = "Todas as mensagens foram apagadas.";
        break;

    case 'nao_respondidas':
        $pdo->exec("DELETE FROM mensagens WHERE resposta_de IS NULL AND destinatario_id = 1");
        $msg = "Mensagens não respondidas foram apagadas.";
        break;

    case 'por_cliente':
        if ($cliente_id) {
            $stmt = $pdo->prepare("DELETE FROM mensagens WHERE remetente_id = ? OR destinatario_id = ?");
            $stmt->execute([$cliente_id, $cliente_id]);
            $msg = "Mensagens do cliente selecionado foram apagadas.";
        } else {
            $msg = "Nenhum cliente selecionado.";
        }
        break;

    default:
        $msg = "Tipo de limpeza inválido.";
}

echo "<script>alert('$msg'); window.location.href = '../views/admin/dashboard.php';</script>";
