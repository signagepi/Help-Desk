<?php
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function out($data){ echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function htxt($v){ return (string)$v; }

$role = null; $me = 0; $peer = 0;
if (isset($_SESSION['admin_id'])) {
    $role = 'admin';
    $me = (int)$_SESSION['admin_id'];
    $peer = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
    if ($peer <= 0) out(['ok'=>false,'error'=>'Cliente inválido']);
} elseif (isset($_SESSION['cliente_id'])) {
    $role = 'cliente';
    $me = (int)$_SESSION['cliente_id'];
    $peer = 1;
} else {
    out(['ok'=>false,'error'=>'Não autenticado']);
}

$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
$whereExtra = $lastId > 0 ? ' AND m.id > ? ' : '';
$params = [$me, $me, $peer, $peer, $me];
if ($lastId > 0) $params[] = $lastId;

$sql = "SELECT m.* FROM mensagens m
        WHERE ((m.remetente_id = ? AND m.destinatario_id = ?) OR (m.remetente_id = ? AND m.destinatario_id = ?))
        $whereExtra
        ORDER BY m.data ASC, m.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdo->prepare('UPDATE mensagens SET lida = 1 WHERE destinatario_id = ? AND remetente_id = ? AND COALESCE(lida,0) = 0')
    ->execute([$me, $peer]);

$messages = [];
foreach ($rows as $m) {
    $mine = ((int)$m['remetente_id'] === $me);
    $messages[] = [
        'id'=>(int)$m['id'],
        'mine'=>$mine,
        'origem'=>$mine ? 'me' : 'other',
        'mensagem'=>htxt($m['mensagem']),
        'data'=>date('d/m/Y H:i', strtotime($m['data'] ?? 'now')),
        'lida'=>(int)($m['lida'] ?? 0)
    ];
}
out(['ok'=>true,'role'=>$role,'messages'=>$messages]);
