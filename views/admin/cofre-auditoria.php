<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../lib/cofre-funcoes.php';
cofre_cabecalhos();
cofre_exigir_https();
if (!cofre_admin_id()) { header('Location: ../../auth/login-admin.php'); exit; }
function h_cofre_aud($v): string { return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$admin = cofre_admin_id();
$stmt = $pdo->prepare("SELECT a.acao, a.criado_em, a.admin_id, a.credencial_id, v.titulo, v.escopo, ad.email AS admin_email
    FROM cofre_auditoria a
    LEFT JOIN cofre_credenciais v ON v.id = a.credencial_id
    LEFT JOIN administradores ad ON ad.id = a.admin_id
    WHERE a.admin_id = ? OR v.escopo = 'cliente'
    ORDER BY a.id DESC LIMIT 100");
$stmt->execute([$admin]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Auditoria do Cofre | Infoservice</title><link rel="stylesheet" href="../../assets/css/helpdesk-pro.css"><link rel="stylesheet" href="../../assets/css/cofre-senhas.css"></head>
<body><div class="hd-page"><header class="hd-topbar"><div class="hd-container hd-topbar-inner"><div class="hd-brand"><div class="hd-brand-icon">🛡️</div><div><h1>Histórico do Cofre</h1><p>Consulta e alterações de credenciais</p></div></div><nav class="hd-user" aria-label="Navegação administrativa"><a class="hd-chip" href="cofre-senhas.php">Voltar ao cofre</a><a class="hd-chip" href="dashboard.php">Dashboard</a></nav></div></header>
<main class="hd-main"><div class="hd-container"><section class="hd-card hd-card-pad"><div class="hd-section-head"><div><h2>Últimos 100 eventos</h2><p>O histórico não armazena usuários, senhas, endereços ou notas criptografadas.</p></div></div><div class="hd-table-wrap"><table class="hd-table"><thead><tr><th>Data</th><th>Administrador</th><th>Ação</th><th>Credencial</th></tr></thead><tbody>
<?php foreach ($eventos as $e): ?><tr><td><?= h_cofre_aud(date('d/m/Y H:i:s', strtotime($e['criado_em']))) ?></td><td><?= h_cofre_aud($e['admin_email'] ?? 'ID ' . $e['admin_id']) ?></td><td><?= h_cofre_aud(str_replace('_', ' ', $e['acao'])) ?></td><td><?= h_cofre_aud($e['titulo'] ?? ($e['credencial_id'] ? 'Registro removido #' . $e['credencial_id'] : 'Confirmação administrativa')) ?></td></tr><?php endforeach; ?>
<?php if (!$eventos): ?><tr><td colspan="4">Ainda não existem registros de acesso.</td></tr><?php endif; ?></tbody></table></div></section></div></main></div></body></html>
