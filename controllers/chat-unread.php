<?php
session_start();
require '../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function out($data){ echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

$role = null; $userId = 0;
if (isset($_SESSION['admin_id'])) { $role = 'admin'; $userId = (int)$_SESSION['admin_id']; }
elseif (isset($_SESSION['cliente_id'])) { $role = 'cliente'; $userId = (int)$_SESSION['cliente_id']; }
else { out(['ok'=>false,'count'=>0,'error'=>'Não autenticado']); }

$stmt = $pdo->prepare("SELECT COUNT(*) FROM mensagens WHERE destinatario_id = ? AND COALESCE(lida,0) = 0");
$stmt->execute([$userId]);
$count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT id, remetente_id, mensagem, data FROM mensagens WHERE destinatario_id = ? AND COALESCE(lida,0) = 0 ORDER BY data DESC, id DESC LIMIT 1");
$stmt->execute([$userId]);
$last = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

$fromName = '';
if ($last) {
    if ($role === 'admin') {
        $q = $pdo->prepare('SELECT nome FROM clientes WHERE id = ?');
        $q->execute([(int)$last['remetente_id']]);
        $fromName = (string)($q->fetchColumn() ?: 'Cliente');
    } else {
        $q = $pdo->prepare('SELECT nome FROM administradores WHERE id = ?');
        $q->execute([(int)$last['remetente_id']]);
        $fromName = (string)($q->fetchColumn() ?: 'Suporte Infoservice');
    }
}

out([
    'ok'=>true,
    'role'=>$role,
    'count'=>$count,
    'last'=>$last ? [
        'id'=>(int)$last['id'],
        'from'=>$fromName,
        'message'=>mb_substr((string)$last['mensagem'],0,130),
        'data'=>$last['data']
    ] : null
]);
