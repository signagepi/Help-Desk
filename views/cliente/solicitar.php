<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$id = (int)$_SESSION['cliente_id'];
$stmt = $pdo->prepare('SELECT nome FROM clientes WHERE id = ?');
$stmt->execute([$id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT protocolo, titulo, status, data FROM solicitacoes WHERE cliente_id = ? ORDER BY data DESC LIMIT 4');
$stmt->execute([$id]);
$ultimas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success_msg'] ?? '';
$error = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abrir chamado - Infoservice</title>
    <link rel="stylesheet" href="../../assets/css/helpdesk-pro.css">
</head>
<body>
<div class="hd-page">
    <header class="hd-topbar">
        <div class="hd-container hd-topbar-inner">
            <div class="hd-brand">
                <div class="hd-brand-icon">➕</div>
                <div>
                    <h1>Abrir chamado</h1>
                    <p>Descreva sua necessidade para nossa equipe técnica atender com mais agilidade.</p>
                </div>
            </div>
            <div class="hd-user">
                <span class="hd-chip"><?= h($cliente['nome'] ?? 'Cliente') ?></span>
                <a class="hd-chip" href="dashboard.php">Voltar ao painel</a>
            </div>
        </div>
    </header>

    <main class="hd-main">
        <div class="hd-container">
            <?php if ($success): ?><div class="hd-alert hd-alert-success"><?= h($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="hd-alert hd-alert-error"><?= h($error) ?></div><?php endif; ?>

            <div class="hd-grid">
                <section class="hd-card hd-card-pad">
                    <div class="hd-section-head">
                        <div>
                            <h2>Dados da solicitação</h2>
                            <p class="hd-ticket-meta">Quanto mais claro o chamado, mais rápido conseguimos resolver.</p>
                        </div>
                    </div>

                    <form method="post" action="../../controllers/nova-solicitacao.php" class="hd-form-grid">
                        <div class="hd-field hd-field-full">
                            <label class="hd-label">Título do chamado</label>
                            <input class="hd-input" name="titulo" maxlength="180" placeholder="Ex.: Computador do caixa 2 sem internet">
                        </div>
                        <div class="hd-field">
                            <label class="hd-label">Categoria</label>
                            <select class="hd-select" name="categoria" required>
                                <option>Suporte técnico</option>
                                <option>Manutenção de computador</option>
                                <option>Rede e internet</option>
                                <option>Impressora</option>
                                <option>Instalação de software</option>
                                <option>Backup e recuperação</option>
                                <option>Visita técnica</option>
                            </select>
                        </div>
                        <div class="hd-field">
                            <label class="hd-label">Prioridade</label>
                            <select class="hd-select" name="prioridade" required>
                                <option value="baixa">Baixa - pode aguardar</option>
                                <option value="media" selected>Média - precisa resolver</option>
                                <option value="alta">Alta - serviço prejudicado</option>
                                <option value="urgente">Urgente - operação parada</option>
                            </select>
                        </div>
                        <div class="hd-field">
                            <label class="hd-label">Equipamento</label>
                            <input class="hd-input" name="equipamento" maxlength="120" placeholder="Notebook, PC, impressora, roteador...">
                        </div>
                        <div class="hd-field">
                            <label class="hd-label">Local do atendimento</label>
                            <input class="hd-input" name="local_atendimento" maxlength="160" placeholder="Casa, empresa, setor, caixa, sala...">
                        </div>
                        <div class="hd-field hd-field-full">
                            <label class="hd-label">Descrição detalhada</label>
                            <textarea class="hd-textarea" id="descricao" name="descricao" maxlength="2000" data-counter="#contador" required placeholder="Explique o que aconteceu, quando começou, se aparece erro, qual equipamento está envolvido e o que já foi tentado."></textarea>
                            <span class="hd-ticket-meta"><span id="contador">0</span>/2000 caracteres</span>
                        </div>
                        <div class="hd-field hd-field-full">
                            <button class="hd-btn hd-btn-primary" type="submit">📨 Enviar solicitação</button>
                        </div>
                    </form>
                </section>

                <aside class="hd-sidebar">
                    <section class="hd-card hd-card-pad">
                        <div class="hd-section-head"><h3>Modelos rápidos</h3></div>
                        <div class="hd-sidebar-list">
                            <button type="button" class="hd-btn hd-btn-light" data-target="#descricao" data-fill="Meu computador está muito lento para iniciar e abrir programas. Preciso de uma limpeza, verificação de vírus e otimização do sistema.">💻 Computador lento</button>
                            <button type="button" class="hd-btn hd-btn-light" data-target="#descricao" data-fill="Estou com problema de internet/rede. O sinal cai com frequência ou alguns equipamentos não conectam. Preciso de avaliação do roteador, cabos e configuração.">📶 Rede/internet</button>
                            <button type="button" class="hd-btn hd-btn-light" data-target="#descricao" data-fill="A impressora não está imprimindo corretamente. Apresenta falhas, erro de conexão ou não aparece na rede. Preciso de suporte para configurar e testar.">🖨️ Impressora</button>
                            <button type="button" class="hd-btn hd-btn-light" data-target="#descricao" data-fill="Preciso de uma visita técnica para avaliar o ambiente, organizar equipamentos e verificar melhorias necessárias.">👨‍🔧 Visita técnica</button>
                        </div>
                    </section>

                    <?php if ($ultimas): ?>
                    <section class="hd-card hd-card-pad" style="margin-top:16px">
                        <div class="hd-section-head"><h3>Últimos chamados</h3></div>
                        <div class="hd-sidebar-list">
                            <?php foreach ($ultimas as $u): ?>
                                <div class="hd-mini"><strong><?= h($u['protocolo'] ?: 'Chamado') ?></strong><p><?= h($u['titulo'] ?: 'Solicitação') ?> • <?= h(ucfirst($u['status'])) ?></p></div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
    </main>
</div>
<script src="../../assets/js/helpdesk-pro.js"></script>
</body>
</html>
