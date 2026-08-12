<?php
session_start();
require '../config/db.php';
$email = trim($_POST['email'] ?? '');
$senha = trim($_POST['senha'] ?? '');
if ($email === '' || $senha === '') {
    $_SESSION['login_error'] = 'Preencha email e senha.';
    header('Location: ../auth/login-admin.php'); exit;
}
try {
    $stmt = $pdo->prepare('SELECT * FROM administradores WHERE email = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin && password_verify($senha, $admin['senha'])) {
        $_SESSION['admin_id'] = (int)$admin['id'];
        header('Location: ../views/admin/dashboard.php'); exit;
    }
    $_SESSION['login_error'] = 'Email ou senha inválidos.';
} catch (Throwable $e) { $_SESSION['login_error'] = 'Erro ao acessar o sistema.'; }
header('Location: ../auth/login-admin.php'); exit;
