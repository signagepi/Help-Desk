<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login-admin.php');
    exit;
}

// Apagar todas as mensagens do sistema
$pdo->prepare("DELETE FROM mensagens")->execute();

echo "<script>alert('Todas as mensagens foram apagadas com sucesso.'); window.location.href = 'dashboard.php';</script>";
