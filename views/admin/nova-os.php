<?php
session_start();
require '../../config/db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: ../../auth/login-admin.php'); exit; }
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$clientes=$pdo->query('SELECT id,nome,email FROM clientes ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
$tecnicos=$pdo->query('SELECT id,nome FROM administradores ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
$dados=$_SESSION['nova_os_dados']??[];$erro=$_SESSION['error_msg']??'';
unset($_SESSION['nova_os_dados'],$_SESSION['error_msg']);
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Nova O.S. - Central de Serviços</title><link rel="stylesheet" href="../../assets/css/helpdesk-pro.css"></head>
<body><div class="hd-page"><header class="hd-topbar"><div class="hd-container hd-topbar-inner"><div class="hd-brand"><div class="hd-brand-icon">🧾</div><div><h1>Nova O.S.</h1><p>Registre um atendimento em nome do cliente com rastreabilidade.</p></div></div><div class="hd-user"><a class="hd-chip" href="dashboard.php">← Voltar ao painel</a><a class="hd-chip" href="../../auth/logout.php">Sair</a></div></div></header>
<main class="hd-main"><div class="hd-container"><?php if($erro): ?><div class="hd-alert hd-alert-error"><?=h($erro)?></div><?php endif; ?><section class="hd-card hd-card-pad"><div class="hd-section-head"><div><h2>Novo atendimento</h2><p>A O.S. ficará visível no portal do cliente selecionado.</p></div><span class="hd-badge pr-media">Central de Serviços</span></div>
<?php if(!$clientes): ?><div class="hd-alert hd-alert-info">Cadastre um cliente antes de registrar uma O.S.</div><?php endif; ?>
<form method="post" action="../../controllers/nova-solicitacao-admin.php" class="hd-form-grid">
<div class="hd-field"><label class="hd-label">Cliente *</label><select class="hd-select" name="cliente_id" required><option value="">Selecione...</option><?php foreach($clientes as $c): ?><option value="<?=(int)$c['id']?>" <?=(string)($dados['cliente_id']??'')===(string)$c['id']?'selected':''?>><?=h($c['nome'])?> — <?=h($c['email'])?></option><?php endforeach; ?></select></div>
<div class="hd-field"><label class="hd-label">Solicitante</label><input class="hd-input" name="solicitante_nome" maxlength="150" value="<?=h($dados['solicitante_nome']??'')?>" placeholder="Ex.: Maria Silva"></div>
<div class="hd-field"><label class="hd-label">Origem *</label><select class="hd-select" name="origem" required><?php foreach(['Central de Serviços','Telefone','WhatsApp','Atendimento presencial','Técnico'] as $o): ?><option <?=($dados['origem']??'Central de Serviços')===$o?'selected':''?>><?=h($o)?></option><?php endforeach; ?></select></div>
<div class="hd-field"><label class="hd-label">Técnico responsável</label><select class="hd-select" name="tecnico_id"><option value="">Não atribuído</option><?php foreach($tecnicos as $t): ?><option value="<?=(int)$t['id']?>" <?=(string)($dados['tecnico_id']??'')===(string)$t['id']?'selected':''?>><?=h($t['nome'])?></option><?php endforeach; ?></select></div>
<div class="hd-field hd-field-full"><label class="hd-label">Título</label><input class="hd-input" name="titulo" maxlength="180" value="<?=h($dados['titulo']??'')?>" placeholder="Ex.: Impressora da secretaria não imprime"></div>
<div class="hd-field"><label class="hd-label">Categoria *</label><select class="hd-select" name="categoria" required><?php foreach(['Suporte técnico','Manutenção de computador','Rede e internet','Impressora','Instalação de software','Backup e recuperação','Visita técnica'] as $c): ?><option <?=($dados['categoria']??'Suporte técnico')===$c?'selected':''?>><?=h($c)?></option><?php endforeach; ?></select></div>
<div class="hd-field"><label class="hd-label">Prioridade *</label><select class="hd-select" name="prioridade" required><?php foreach(['baixa'=>'Baixa','media'=>'Normal','alta'=>'Alta','urgente'=>'Urgente'] as $v=>$r): ?><option value="<?=$v?>" <?=($dados['prioridade']??'media')===$v?'selected':''?>><?=$r?></option><?php endforeach; ?></select></div>
<div class="hd-field"><label class="hd-label">Equipamento</label><input class="hd-input" name="equipamento" maxlength="120" value="<?=h($dados['equipamento']??'')?>" placeholder="Ex.: Impressora HP"></div>
<div class="hd-field"><label class="hd-label">Local do atendimento</label><input class="hd-input" name="local_atendimento" maxlength="160" value="<?=h($dados['local_atendimento']??'')?>" placeholder="Ex.: Secretaria"></div>
<div class="hd-field hd-field-full"><label class="hd-label">Descrição *</label><textarea class="hd-textarea" name="descricao" maxlength="2000" required placeholder="Descreva o problema informado pelo cliente."><?=h($dados['descricao']??'')?></textarea></div>
<div class="hd-field hd-field-full"><div class="hd-actions"><a class="hd-btn hd-btn-outline" href="dashboard.php">Cancelar</a><button class="hd-btn hd-btn-primary" type="submit" <?=$clientes?'':'disabled'?>>Registrar O.S.</button></div></div>
</form></section></div></main></div></body></html>
