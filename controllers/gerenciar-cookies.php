<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login-admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $mensagem = trim($_POST['mensagem']);
    $politica_link = trim($_POST['politica_link']);

    $stmt = $pdo->prepare("UPDATE config_cookies SET ativo = ?, mensagem = ?, politica_link = ? WHERE id = 1");
    $stmt->execute([$ativo, $mensagem, $politica_link]);

    $_SESSION['success'] = 'Configurações de cookies atualizadas com sucesso!';
    header('Location: ../views/admin/config-cookies.php');
    exit;
}

// Buscar configurações atuais
$stmt = $pdo->prepare("SELECT * FROM config_cookies WHERE id = 1");
$stmt->execute();
$config = $stmt->fetch();
?>