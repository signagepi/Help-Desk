<?php
session_start();
require '../config/db.php';

$descricao = $_POST['descricao'];
$cliente_id = $_SESSION['cliente_id'];

$stmt = $pdo->prepare("INSERT INTO pedidos_suporte (cliente_id, descricao) VALUES (?, ?)");
$stmt->execute([$cliente_id, $descricao]);

header('Location: ../views/cliente/historico.php');