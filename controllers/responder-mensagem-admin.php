<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login-admin.php');
    exit;
}

$remetente_id = $_SESSION['admin_id'];
$destinatario_id = $_POST['destinatario_id'] ?? null;
$mensagem_id = $_POST['mensagem_id'] ?? null;
$mensagem = trim($_POST['mensagem'] ?? '');

if (!$destinatario_id || $mensagem === '') {
    echo "<script>alert('Mensagem não pode estar vazia'); history.back();</script>";
    exit;
}

// Enviar nova mensagem do admin para o cliente, vinculando à mensagem original
$stmt = $pdo->prepare("INSERT INTO mensagens (remetente_id, destinatario_id, mensagem, data, lida, resposta_de) VALUES (?, ?, ?, NOW(), 0, ?)");
$stmt->execute([$remetente_id, $destinatario_id, $mensagem, $mensagem_id]);

// Marcar a mensagem original como lida
if ($mensagem_id) {
    $pdo->prepare("UPDATE mensagens SET lida = 1 WHERE id = ?")->execute([$mensagem_id]);
}

echo "<script>alert('Resposta enviada com sucesso!'); window.location.href = '../views/admin/mensagens.php';</script>";
