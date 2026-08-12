<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login-admin.php');
    exit;
}

$remetente_id = $_SESSION['admin_id'];
$destinatario_id = $_POST['destinatario_id'];
$mensagem = trim($_POST['mensagem']);

if ($mensagem === '') {
    echo "<script>alert('Mensagem não pode estar vazia'); history.back();</script>";
    exit;
}

$stmt = $pdo->prepare("INSERT INTO mensagens (remetente_id, destinatario_id, mensagem, data) VALUES (?, ?, ?, NOW())");
$stmt->execute([$remetente_id, $destinatario_id, $mensagem]);

echo "<script>alert('Resposta enviada com sucesso!'); window.location.href = '../views/admin/mensagens.php';</script>";