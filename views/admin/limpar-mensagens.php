<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login-admin.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Buscar estatísticas para exibir
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM mensagens WHERE destinatario_id = ?");
$stmtTotal->execute([$admin_id]);
$totalMensagens = $stmtTotal->fetchColumn();

$stmtNaoRespondidas = $pdo->prepare("
    SELECT COUNT(*) FROM mensagens 
    WHERE destinatario_id = ? 
    AND id NOT IN (SELECT resposta_de FROM mensagens WHERE resposta_de IS NOT NULL)
");
$stmtNaoRespondidas->execute([$admin_id]);
$naoRespondidas = $stmtNaoRespondidas->fetchColumn();

// Buscar clientes
$clientes = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Mensagens - Infoservice Admin</title>
    <link rel="stylesheet" href="../../assets/style.css">
    <link rel="stylesheet" href="../../assets/css/admin/limpar-mensagens.css">
</head>
<body>
    <div class="admin-dashboard">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-content">
                <h1 class="logo">Infoservice - Admin</h1>
                <div class="user-info">
                    <span>Administrador</span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="page-title-section">
                        <h2 class="page-title">🧹 Gerenciar Mensagens</h2>
                        <div class="page-stats">
                            <span class="stat-badge total">Total: <?= $totalMensagens ?></span>
                            <span class="stat-badge pendentes">Não respondidas: <?= $naoRespondidas ?></span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="admin-nav">
                    <div class="nav-buttons">
                        <a href="dashboard.php" class="nav-button">
                            <span class="nav-icon">🔙</span>
                            Voltar ao Dashboard
                        </a>
                        <a href="mensagens.php" class="nav-button">
                            <span class="nav-icon">📬</span>
                            Ver Todas as Mensagens
                        </a>
                        <a href="notificacoes.php" class="nav-button">
                            <span class="nav-icon">🔔</span>
                            Notificações
                        </a>
                        <a href="../../auth/logout.php" class="nav-button logout">
                            <span class="nav-icon">🔒</span>
                            Sair
                        </a>
                    </div>
                </nav>

                <!-- Aviso Importante -->
                <div class="warning-banner">
                    <div class="warning-icon">⚠️</div>
                    <div class="warning-content">
                        <h3>Atenção: Ação Destrutiva</h3>
                        <p>As operações de limpeza são irreversíveis. Certifique-se do que deseja excluir antes de prosseguir.</p>
                    </div>
                </div>

                <!-- Formulário de Limpeza -->
                <div class="cleanup-container">
                    <div class="cleanup-form-card">
                        <form method="POST" action="../../controllers/limpar-mensagens-controller.php" id="cleanupForm">
                            <div class="form-section">
                                <h3 class="section-title">📋 Selecione o Tipo de Limpeza</h3>
                                
                                <div class="form-group">
                                    <label for="tipo" class="form-label">
                                        <span class="label-icon">🎯</span>
                                        Tipo de Limpeza
                                    </label>
                                    <select name="tipo" id="tipo" required class="form-select">
                                        <option value="">-- Selecione o tipo de limpeza --</option>
                                        <option value="todas">🗑️ Apagar todas as minhas mensagens</option>
                                        <option value="nao_respondidas">📭 Apagar apenas mensagens não respondidas</option>
                                        <option value="por_cliente">👤 Apagar mensagens de um cliente específico</option>
                                    </select>
                                </div>

                                <div class="form-group cliente-group" id="clienteGroup" style="display: none;">
                                    <label for="cliente_id" class="form-label">
                                        <span class="label-icon">👤</span>
                                        Selecione o Cliente
                                    </label>
                                    <select name="cliente_id" id="cliente_id" class="form-select">
                                        <option value="">-- Selecione um cliente --</option>
                                        <?php foreach ($clientes as $cliente): ?>
                                            <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Resumo da Ação -->
                            <div class="action-summary" id="actionSummary" style="display: none;">
                                <h4 class="summary-title">📊 Resumo da Ação</h4>
                                <div class="summary-content" id="summaryContent"></div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn-secondary" onclick="window.history.back()">
                                    <span class="btn-icon">↩️</span>
                                    Cancelar
                                </button>
                                <button type="submit" class="btn-danger" id="submitBtn">
                                    <span class="btn-icon">🧹</span>
                                    Executar Limpeza
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Cards de Informação -->
                    <div class="info-cards">
                        <div class="info-card">
                            <div class="info-icon">🗑️</div>
                            <h4>Limpeza Total</h4>
                            <p>Remove todas as mensagens da sua caixa de entrada, incluindo conversas respondidas.</p>
                            <div class="info-stats">Afetará: <?= $totalMensagens ?> mensagens</div>
                        </div>

                        <div class="info-card">
                            <div class="info-icon">📭</div>
                            <h4>Não Respondidas</h4>
                            <p>Remove apenas mensagens que ainda não foram respondidas, mantendo as conversas ativas.</p>
                            <div class="info-stats">Afetará: <?= $naoRespondidas ?> mensagens</div>
                        </div>

                        <div class="info-card">
                            <div class="info-icon">👤</div>
                            <h4>Por Cliente</h4>
                            <p>Remove mensagens de um cliente específico, útil para limpar conversas individuais.</p>
                            <div class="info-stats">Clientes disponíveis: <?= count($clientes) ?></div>
                        </div>
                        </br>
                    </div>
                </div>
            </div>
        </main>

         <!-- Footer -->
        <footer class="cliente-footer">
             <div class="copyright">
          <!-- No final do arquivo, antes do </body> -->
           <?php include '../components/footer.php'; ?>
            </div>     
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tipoSelect = document.getElementById('tipo');
            const clienteGroup = document.getElementById('clienteGroup');
            const actionSummary = document.getElementById('actionSummary');
            const summaryContent = document.getElementById('summaryContent');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('cleanupForm');

            // Estatísticas (você pode buscar via AJAX se preferir)
            const stats = {
                total: <?= $totalMensagens ?>,
                nao_respondidas: <?= $naoRespondidas ?>,
                clientes: <?= count($clientes) ?>
            };

            tipoSelect.addEventListener('change', function() {
                const tipo = this.value;
                
                // Mostrar/ocultar seleção de cliente
                if (tipo === 'por_cliente') {
                    clienteGroup.style.display = 'block';
                } else {
                    clienteGroup.style.display = 'none';
                }

                // Atualizar resumo da ação
                updateActionSummary(tipo);
            });

            function updateActionSummary(tipo) {
                if (!tipo) {
                    actionSummary.style.display = 'none';
                    return;
                }

                let summaryText = '';
                let confirmMessage = '';

                switch(tipo) {
                    case 'todas':
                        summaryText = `🗑️ <strong>Limpeza Total</strong><br>
                                      Serão removidas <strong>${stats.total} mensagens</strong> da sua caixa de entrada.`;
                        confirmMessage = `Tem certeza que deseja apagar TODAS as ${stats.total} mensagens? Esta ação não pode ser desfeita.`;
                        break;
                    
                    case 'nao_respondidas':
                        summaryText = `📭 <strong>Limpeza de Não Respondidas</strong><br>
                                      Serão removidas <strong>${stats.nao_respondidas} mensagens não respondidas</strong>.`;
                        confirmMessage = `Tem certeza que deseja apagar ${stats.nao_respondidas} mensagens não respondidas?`;
                        break;
                    
                    case 'por_cliente':
                        const clienteSelect = document.getElementById('cliente_id');
                        const clienteNome = clienteSelect.options[clienteSelect.selectedIndex]?.text || 'um cliente';
                        summaryText = `👤 <strong>Limpeza por Cliente</strong><br>
                                      Serão removidas as mensagens de <strong>${clienteNome}</strong>.`;
                        confirmMessage = `Tem certeza que deseja apagar todas as mensagens de ${clienteNome}?`;
                        break;
                }

                summaryContent.innerHTML = summaryText;
                actionSummary.style.display = 'block';

                // Atualizar confirmação
                form.onsubmit = function() {
                    return confirm(confirmMessage);
                };
            }

            // Atualizar resumo quando cliente for selecionado
            document.getElementById('cliente_id').addEventListener('change', function() {
                if (tipoSelect.value === 'por_cliente') {
                    updateActionSummary('por_cliente');
                }
            });

            // Prevenir envio duplo
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="btn-icon">⏳</span> Executando...';
            });
        });
    </script>
</body>
</html>