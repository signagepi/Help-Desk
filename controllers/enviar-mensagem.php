<?php
session_start();
require '../config/db.php';
if (!isset($_SESSION['cliente_id'])) { header('Location: ../auth/login.php'); exit; }
$remetente = (int)$_SESSION['cliente_id'];
$mensagem = trim($_POST['mensagem'] ?? '');
if ($mensagem === '') {
    $_SESSION['error_msg'] = 'Digite uma mensagem antes de enviar.';
    header('Location: ../views/cliente/mensagens.php'); exit;
}
try {
    $stmt = $pdo->query('SELECT id FROM administradores ORDER BY id ASC LIMIT 1');
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) { throw new Exception('Nenhum técnico cadastrado para receber mensagens.'); }
    $stmt = $pdo->prepare('INSERT INTO mensagens (remetente_id, destinatario_id, mensagem, data, lida) VALUES (?, ?, ?, NOW(), 0)');
    $stmt->execute([$remetente, (int)$admin['id'], $mensagem]);
    $_SESSION['success_msg'] = 'Mensagem enviada para o suporte.';
} catch (Throwable $e) {
    $_SESSION['error_msg'] = 'Não foi possível enviar a mensagem.';
}
header('Location: ../views/cliente/mensagens.php'); exit;
