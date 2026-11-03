<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../lib/cofre-funcoes.php';
cofre_cabecalhos();
cofre_exigir_https();
if (!cofre_admin_id()) { header('Location: ../../auth/login-admin.php'); exit; }
function h_cofre($texto): string { return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$admin = cofre_admin_id();
$chaveOk = true;
$chaveErro = 'Não foi possível validar a configuração do cofre.';
try {
    $chave = cofre_chave();
    sodium_memzero($chave);
} catch (Throwable $e) {
    $chaveOk = false;
    // Apenas erros predefinidos e sem valores de segredos podem ser exibidos.
    $mensagensSeguras = [
        'O cofre necessita da extensão PHP sodium.',
        'O cofre não está configurado: defina HELPDESK_VAULT_KEY ou configure o arquivo privado.',
        'Configuração local do cofre inválida.',
        'A chave deve estar em um arquivo privado, fora das pastas públicas.',
        'Arquivo da chave inacessível ou com permissões inseguras (use 0600).',
        'A chave do cofre é inválida. Verifique a configuração sem divulgar seu conteúdo.',
    ];
    if ($e instanceof RuntimeException && in_array($e->getMessage(), $mensagensSeguras, true)) {
        $chaveErro = $e->getMessage();
    }
}
$clientes = $pdo->query('SELECT id, nome FROM clientes ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
$q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
$clienteFiltro = filter_var($_GET['cliente_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$limite = 20;
$filtros = ["(v.escopo = 'cliente' OR v.admin_criador_id = ?)"];
$params = [$admin];
if ($q !== '') { $filtros[] = '(v.titulo LIKE ? OR v.categoria LIKE ?)'; $params[] = '%' . $q . '%'; $params[] = '%' . $q . '%'; }
if ($clienteFiltro > 0) { $filtros[] = 'v.cliente_id = ?'; $params[] = $clienteFiltro; }
$where = implode(' AND ', $filtros);
$stmt = $pdo->prepare("SELECT COUNT(*) FROM cofre_credenciais v WHERE $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $limite));
$pagina = min($pagina, $totalPaginas);
$offset = ($pagina - 1) * $limite;
$stmt = $pdo->prepare("SELECT v.id, v.escopo, v.titulo, v.categoria, v.atualizado_em, c.nome AS cliente_nome FROM cofre_credenciais v LEFT JOIN clientes c ON c.id = v.cliente_id WHERE $where ORDER BY v.atualizado_em DESC, v.id DESC LIMIT $limite OFFSET $offset");
$stmt->execute($params);
$credenciais = $stmt->fetchAll(PDO::FETCH_ASSOC);
$aviso = $_SESSION['cofre_aviso'] ?? '';
$erro = $_SESSION['cofre_erro'] ?? '';
unset($_SESSION['cofre_aviso'], $_SESSION['cofre_erro']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cofre de Senhas | Infoservice</title>
<link rel="stylesheet" href="../../assets/css/helpdesk-pro.css">
<link rel="stylesheet" href="../../assets/css/cofre-senhas.css">
</head>
<body>
<div class="hd-page">
<header class="hd-topbar"><div class="hd-container hd-topbar-inner">
<div class="hd-brand"><div class="hd-brand-icon">🔐</div><div><h1>Cofre de Senhas</h1><p>Credenciais de acesso para atendimentos técnicos</p></div></div>
<nav class="hd-user" aria-label="Navegação administrativa"><a class="hd-chip" href="dashboard.php">Dashboard</a><a class="hd-chip" href="lista-clientes.php">Clientes</a><a class="hd-chip" href="../../auth/logout.php">Sair</a></nav>
</div></header>
<main class="hd-main"><div class="hd-container cofre-container">
<p class="cofre-intro">Guarde acessos pessoais ou de clientes. Usuário, senha, endereços e observações ficam criptografados. A consulta exige sua senha administrativa e fica registrada no histórico.</p>
<?php if (!$chaveOk): ?><div class="hd-alert hd-alert-error" role="alert">O cofre está indisponível: <?= h_cofre($chaveErro) ?> Não cadastre senhas até concluir a configuração.</div><?php endif; ?>
<?php if ($aviso): ?><div class="hd-alert hd-alert-success" role="status"><?= h_cofre($aviso) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="hd-alert hd-alert-error" role="alert"><?= h_cofre($erro) ?></div><?php endif; ?>
<section class="hd-card hd-card-pad cofre-card" aria-labelledby="cofre-titulo-lista">
<div class="hd-section-head"><div><h2 id="cofre-titulo-lista">Credenciais salvas</h2><p><?= (int) $total ?> registro(s) encontrado(s)</p></div><div class="hd-actions"><a class="hd-btn hd-btn-outline" href="cofre-auditoria.php">Histórico de acessos</a><a class="hd-btn hd-btn-primary" href="#cofre-formulario">+ Nova credencial</a></div></div>
<form class="hd-form-grid" method="get" action="cofre-senhas.php">
<div class="hd-field"><label class="hd-label" for="cofre-busca">Buscar por título ou categoria</label><input class="hd-input" id="cofre-busca" name="q" value="<?= h_cofre($q) ?>" maxlength="100"></div>
<div class="hd-field"><label class="hd-label" for="cofre-filtro-cliente">Filtrar cliente</label><select class="hd-select" name="cliente_id" id="cofre-filtro-cliente"><option value="">Todos / pessoais</option><?php foreach ($clientes as $cliente): ?><option value="<?= (int) $cliente['id'] ?>" <?= $clienteFiltro === (int) $cliente['id'] ? 'selected' : '' ?>><?= h_cofre($cliente['nome']) ?></option><?php endforeach; ?></select></div>
<div class="hd-field"><label class="hd-label" for="cofre-filtrar-btn">Pesquisa</label><button class="hd-btn hd-btn-outline" id="cofre-filtrar-btn" type="submit">Filtrar</button></div>
</form>
<div class="hd-table-wrap"><table class="hd-table"><thead><tr><th>Identificação</th><th>Categoria</th><th>Atualização</th><th>Operações</th></tr></thead><tbody>
<?php foreach ($credenciais as $c): ?>
<tr><td><strong><?= h_cofre($c['titulo']) ?></strong><div class="hd-ticket-meta"><?= $c['escopo'] === 'pessoal' ? '🔒 Pessoal' : '🏢 ' . h_cofre($c['cliente_nome'] ?? 'Cliente não encontrado') ?></div></td>
<td><?= h_cofre($c['categoria']) ?></td><td><?= h_cofre(date('d/m/Y H:i', strtotime($c['atualizado_em']))) ?></td>
<td><div class="hd-actions"><button class="hd-btn hd-btn-outline cofre-abrir" type="button" data-id="<?= (int) $c['id'] ?>" <?= !$chaveOk ? 'disabled' : '' ?>>Consultar / editar</button><button class="hd-btn hd-btn-danger cofre-excluir" type="button" data-id="<?= (int) $c['id'] ?>" <?= !$chaveOk ? 'disabled' : '' ?>>Excluir</button></div></td></tr>
<?php endforeach; ?>
<?php if (!$credenciais): ?><tr><td colspan="4">Nenhuma credencial cadastrada para este filtro.</td></tr><?php endif; ?>
</tbody></table></div>
<?php if ($totalPaginas > 1): ?><nav class="hd-pagination" aria-label="Páginas"><?php for ($i = 1; $i <= $totalPaginas; $i++): ?><a class="<?= $i === $pagina ? 'active' : '' ?>" href="?<?= h_cofre(http_build_query(['q' => $q, 'cliente_id' => $clienteFiltro, 'pagina' => $i])) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
</section>
<section class="hd-card hd-card-pad cofre-card" aria-labelledby="cofre-confirmacao-titulo">
<h2 id="cofre-confirmacao-titulo">Confirmação para consultar ou excluir</h2>
<p>Por segurança, informe sua senha de acesso ao painel antes de consultar uma credencial. A informação não será mantida após a operação.</p>
<div class="hd-field cofre-confirmacao"><label for="cofre-senha-consulta" class="hd-label">Sua senha administrativa</label><input id="cofre-senha-consulta" class="hd-input" type="password" autocomplete="current-password" <?= !$chaveOk ? 'disabled' : '' ?>></div>
<div id="cofre-resultado" class="cofre-resultado" hidden aria-live="polite">
<h3>Informações protegidas</h3><div class="hd-form-grid">
<div class="hd-field"><label class="hd-label" for="cofre-usuario-visual">Usuário</label><input class="hd-input" id="cofre-usuario-visual" readonly></div>
<div class="hd-field"><label class="hd-label" for="cofre-senha-visual">Senha</label><input class="hd-input" id="cofre-senha-visual" type="password" readonly></div>
<div class="hd-field"><label class="hd-label" for="cofre-endereco-visual">IP / URL / endereço</label><input class="hd-input" id="cofre-endereco-visual" readonly></div>
<div class="hd-field hd-field-full"><label class="hd-label" for="cofre-notas-visual">Observações</label><textarea class="hd-textarea" id="cofre-notas-visual" rows="3" readonly></textarea></div>
</div><div class="hd-actions"><button class="hd-btn hd-btn-outline" type="button" id="cofre-alternar">Mostrar / ocultar senha</button><button class="hd-btn hd-btn-primary" type="button" id="cofre-copiar">Copiar senha</button><button class="hd-btn hd-btn-light" type="button" id="cofre-preencher">Editar este registro</button><button class="hd-btn hd-btn-outline" type="button" id="cofre-fechar">Fechar</button></div><p class="hd-ticket-meta">Os campos serão ocultados automaticamente após 45 segundos.</p>
</div><p role="status" aria-live="polite" id="cofre-status"></p>
</section>
<section id="cofre-formulario" class="hd-card hd-card-pad cofre-card" aria-labelledby="cofre-form-titulo">
<div class="hd-section-head"><h2 id="cofre-form-titulo">Cadastrar credencial</h2></div>
<form method="post" action="../../controllers/cofre-credenciais.php" autocomplete="off" id="cofre-form" class="hd-form-grid">
<input type="hidden" name="csrf" value="<?= h_cofre(cofre_csrf()) ?>"><input type="hidden" id="cofre-edit-id" name="id" value=""><input type="hidden" name="acao" value="salvar">
<div class="hd-field"><label class="hd-label" for="cofre-escopo">Cofre</label><select class="hd-select" id="cofre-escopo" name="escopo" required><option value="cliente">Cliente</option><option value="pessoal">Pessoal</option></select></div>
<div class="hd-field"><label class="hd-label" for="cofre-cliente">Cliente</label><select class="hd-select" id="cofre-cliente" name="cliente_id"><option value="">Selecione o cliente</option><?php foreach ($clientes as $cliente): ?><option value="<?= (int) $cliente['id'] ?>"><?= h_cofre($cliente['nome']) ?></option><?php endforeach; ?></select></div>
<div class="hd-field"><label class="hd-label" for="cofre-titulo">Nome da credencial</label><input class="hd-input" id="cofre-titulo" name="titulo" maxlength="150" required placeholder="Ex.: Roteador principal"></div>
<div class="hd-field"><label class="hd-label" for="cofre-categoria">Categoria</label><select class="hd-select" id="cofre-categoria" name="categoria" required><?php foreach (['Roteador', 'Servidor', 'Computador', 'Hospedagem', 'Sistema', 'Banco de dados', 'Outro'] as $tipo): ?><option value="<?= h_cofre($tipo) ?>"><?= h_cofre($tipo) ?></option><?php endforeach; ?></select></div>
<div class="hd-field"><label class="hd-label" for="cofre-usuario">Usuário ou login</label><input class="hd-input" id="cofre-usuario" name="usuario" maxlength="250" autocomplete="off"></div>
<div class="hd-field"><label class="hd-label" for="cofre-senha">Senha ou chave</label><input class="hd-input" id="cofre-senha" name="senha" type="password" required maxlength="4096" autocomplete="new-password"></div>
<div class="hd-field hd-field-full"><label class="hd-label" for="cofre-endereco">Endereço, IP ou URL</label><input class="hd-input" id="cofre-endereco" name="endereco" maxlength="500" autocomplete="off"></div>
<div class="hd-field hd-field-full"><label class="hd-label" for="cofre-observacoes">Observações técnicas (também criptografadas)</label><textarea class="hd-textarea" id="cofre-observacoes" name="observacoes" maxlength="4000" rows="3"></textarea></div>
<div class="hd-field"><label class="hd-label" for="cofre-senha-admin-salvar">Confirme sua senha administrativa para salvar</label><input class="hd-input" id="cofre-senha-admin-salvar" name="senha_admin" type="password" required autocomplete="current-password"></div>
<div class="hd-field"><label class="hd-label" for="cofre-btn-salvar">Ação</label><button class="hd-btn hd-btn-primary" id="cofre-btn-salvar" type="submit" <?= !$chaveOk ? 'disabled' : '' ?>>Salvar credencial</button></div>
<div class="hd-field hd-field-full"><button id="cofre-limpar" class="hd-btn hd-btn-outline" type="button">Limpar formulário</button></div>
</form>
</section>
</div></main></div>
<form id="cofre-form-excluir" action="../../controllers/cofre-credenciais.php" method="post" hidden><input name="csrf" value="<?= h_cofre(cofre_csrf()) ?>"><input name="acao" value="excluir"><input name="id" id="cofre-excluir-id"><input name="senha_admin" id="cofre-excluir-senha" type="password"></form>
<script src="../../assets/js/cofre-senhas.js" defer></script>
</body></html>