<?php
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function out($data){ echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

$mensagem = trim((string)($_POST['mensagem'] ?? ''));
if ($mensagem === '') out(['ok'=>false,'error'=>'Digite uma mensagem.']);
if (mb_strlen($mensagem) > 2000) out(['ok'=>false,'error'=>'Mensagem muito grande. Limite de 2000 caracteres.']);

if (isset($_SESSION['admin_id'])) {
    $remetente = (int)$_SESSION['admin_id'];
    $destinatario = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : (int)($_POST['destinatario_id'] ?? 0);
    if ($destinatario <= 0) out(['ok'=>false,'error'=>'Cliente inválido.']);
} elseif (isset($_SESSION['cliente_id'])) {
    $remetente = (int)$_SESSION['cliente_id'];
    $destinatario = 1;
} else {
    out(['ok'=>false,'error'=>'Não autenticado.']);
}

$stmt = $pdo->prepare('INSERT INTO mensagens (remetente_id, destinatario_id, mensagem, data, lida, resposta_de) VALUES (?, ?, ?, NOW(), 0, NULL)');
$stmt->execute([$remetente, $destinatario, $mensagem]);
$id = (int)$pdo->lastInsertId();

out(['ok'=>true,'id'=>$id,'data'=>date('d/m/Y H:i')]);
