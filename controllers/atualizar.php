<?php
require '../config/db.php';

$id = $_POST['id'];
$status = $_POST['status'];
$resposta = $_POST['resposta'];
$valor = $_POST['valor'];

$stmt = $pdo->prepare("UPDATE pedidos_suporte SET status = ?, resposta = ?, valor = ?, data_resposta = NOW() WHERE id = ?");
$stmt->execute([$status, $resposta, $valor, $id]);

header('Location: ../views/admin/dashboard.php');