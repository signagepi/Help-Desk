<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$id = $_POST['id'];
$cliente_id = $_SESSION['cliente_id'];

$stmt = $pdo->prepare("DELETE FROM mensagens WHERE id = ? AND destinatario_id = ?");
$stmt->execute([$id, $cliente_id]);

header('Location: ../views/cliente/mensagens.php');
exit;
