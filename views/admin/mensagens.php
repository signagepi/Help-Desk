<?php
session_start();
require '../../config/db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: ../../auth/login-admin.php'); exit; }
function h($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');}
$admin_id=(int)$_SESSION['admin_id'];
$stmt=$pdo->prepare("SELECT c.id cliente_id, c.nome, c.email, c.telefone, c.logo,
       MAX(m.data) ultima_data,
       SUM(CASE WHEN m.destinatario_id = ? AND COALESCE(m.lida,0)=0 THEN 1 ELSE 0 END) nao_lidas,
       COUNT(m.id) total_mensagens,
       SUBSTRING_INDEX(GROUP_CONCAT(m.mensagem ORDER BY m.data DESC SEPARATOR '|||'), '|||', 1) ultima_msg
FROM clientes c
JOIN mensagens m ON (m.remetente_id=c.id AND m.destinatario_id=?) OR (m.remetente_id=? AND m.destinatario_id=c.id)
GROUP BY c.id, c.nome, c.email, c.telefone, c.logo
ORDER BY nao_lidas DESC, ultima_data DESC");
$stmt->execute([$admin_id,$admin_id,$admin_id]);
$conversas=$stmt->fetchAll(PDO::FETCH_ASSOC);
$totalNaoLidas=array_sum(array_map(fn($x)=>(int)$x['nao_lidas'],$conversas));
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Mensagens - Admin</title><link rel="stylesheet" href="../../assets/css/helpdesk-pro.css"></head><body><div class="hd-page"><header class="hd-topbar"><div class="hd-container hd-topbar-inner"><div class="hd-brand"><div class="hd-brand-icon">💬</div><div><h1>Central de Mensagens</h1><p>Fila de conversas com alerta visual e sonoro para atendimento em tempo hábil.</p></div></div><div class="hd-user"><a class="hd-chip" href="dashboard.php">Dashboard</a><a class="hd-chip" href="financeiro.php">Financeiro</a><a class="hd-chip" href="lista-clientes.php">Clientes</a><a class="hd-chip" href="../../auth/logout.php">Sair</a></div></div></header><main class="hd-main"><div class="hd-container"><section class="hd-kpis"><div class="hd-kpi"><span>Conversas</span><strong><?=count($conversas)?></strong><small>Clientes com histórico</small></div><div class="hd-kpi"><span>Não lidas</span><strong><?=$totalNaoLidas?></strong><small>Precisam de atenção</small></div><div class="hd-kpi"><span>Alerta</span><strong>Ativo</strong><small>Som + notificação visual</small></div></section><section class="hd-card hd-card-pad"><div class="hd-section-head"><div><h2>Conversas recentes</h2><p>Clique em uma conversa para abrir o live chat.</p></div><span class="hd-badge st-urgente">Tempo hábil</span></div><?php if(!$conversas):?><div class="hd-empty">📭<strong>Nenhuma mensagem recebida</strong>Quando clientes chamarem no atendimento, as conversas aparecerão aqui.</div><?php else:?><div class="hd-sidebar-list"><?php foreach($conversas as $c): $logo=!empty($c['logo'])?'../../uploads/clientes/logos/'.basename($c['logo']):''; ?><article class="hd-mini" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap"><div class="hd-client-card"><div class="hd-client-avatar"><?php if($logo):?><img src="<?=h($logo)?>" alt="Logo"><?php else:?><?=h(mb_strtoupper(mb_substr($c['nome'],0,1)))?><?php endif;?></div><div><strong><?=h($c['nome'])?></strong><p><?=h(mb_substr((string)$c['ultima_msg'],0,120))?></p><small class="hd-ticket-meta"><?=h($c['email'])?> • <?=date('d/m/Y H:i',strtotime($c['ultima_data']))?> • <?=(int)$c['total_mensagens']?> msg</small></div></div><div class="hd-actions"><?php if((int)$c['nao_lidas']>0):?><span class="hd-badge st-urgente"><?=(int)$c['nao_lidas']?> nova(s)</span><?php endif;?><a class="hd-btn hd-btn-primary" href="conversa.php?cliente_id=<?=(int)$c['cliente_id']?>">Abrir chat</a></div></article><?php endforeach;?></div><?php endif;?></section></div></main></div><script>window.HD_CHAT_CONFIG={endpointBase:'../../controllers/',chatUrl:'mensagens.php'};</script><script src="../../assets/js/live-chat-alerts.js"></script><script src="../../assets/js/helpdesk-pro.js"></script></body></html>
