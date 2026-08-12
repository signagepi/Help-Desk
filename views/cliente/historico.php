<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: ../../auth/login-cliente.php');
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

// Buscar dados do cliente
$stmtCliente = $pdo->prepare("SELECT nome FROM clientes WHERE id = ?");
$stmtCliente->execute([$cliente_id]);
$cliente = $stmtCliente->fetch();

// Paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Buscar solicitações do cliente
$stmt = $pdo->prepare("
    SELECT * FROM solicitacoes 
    WHERE cliente_id = ? 
    ORDER BY data DESC 
    LIMIT $limite OFFSET $offset
");
$stmt->execute([$cliente_id]);
$solicitacoes = $stmt->fetchAll();

// Total de solicitações
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM solicitacoes WHERE cliente_id = ?");
$stmtTotal->execute([$cliente_id]);
$total = $stmtTotal->fetchColumn();
$totalPaginas = ceil($total / $limite);

// Estatísticas
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
        SUM(CASE WHEN status = 'finalizado' THEN 1 ELSE 0 END) as finalizados,
        SUM(CASE WHEN status = 'nao resolvido' THEN 1 ELSE 0 END) as nao_resolvidos,
        SUM(preco) as total_gasto
    FROM solicitacoes 
    WHERE cliente_id = ? AND preco IS NOT NULL
");
$stmtStats->execute([$cliente_id]);
$estatisticas = $stmtStats->fetch();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Serviços - Infoservice Cliente</title>
    <link rel="stylesheet" href="../../assets/style.css">
    <link rel="stylesheet" href="../../assets/css/cliente/historico.css">
</head>
<body>
    <div class="cliente-dashboard">
        <!-- Header -->
        <header class="cliente-header">
            <div class="header-content">
                <h1 class="logo">Infoservice - Área do Cliente</h1>
                <div class="user-info">
                    <span class="user-name">👋 <?= htmlspecialchars($cliente['nome']) ?></span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="cliente-main">
            <div class="container">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="page-title-section">
                        <h2 class="page-title">📋 Histórico de Serviços</h2>
                        <p class="page-subtitle">Acompanhe todas as suas solicitações e o andamento dos serviços.</p>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="cliente-nav">
                    <div class="nav-buttons">
                        <a href="dashboard.php" class="nav-button">
                            <span class="nav-icon">🔙</span>
                            Voltar ao Dashboard
                        </a>
                        <a href="solicitar.php" class="nav-button">
                            <span class="nav-icon">📝</span>
                            Nova Solicitação
                        </a>
                        <a href="mensagens.php" class="nav-button">
                            <span class="nav-icon">💬</span>
                            Mensagens
                        </a>
                        <a href="calendario.php" class="nav-button">
                            <span class="nav-icon">🗓️</span>
                            Calendário
                        </a>
                        <a href="../../auth/logout.php" class="nav-button logout">
                            <span class="nav-icon">🔒</span>
                            Sair
                        </a>
                    </div>
                </nav>

                <!-- Estatísticas Rápidas -->
                <div class="estatisticas-grid">
                    <div class="estatistica-card">
                        <div class="estatistica-icon">📊</div>
                        <div class="estatistica-info">
                            <span class="estatistica-numero"><?= $total ?></span>
                            <span class="estatistica-label">Total de Solicitações</span>
                        </div>
                    </div>
                    <div class="estatistica-card">
                        <div class="estatistica-icon">⏳</div>
                        <div class="estatistica-info">
                            <span class="estatistica-numero"><?= $estatisticas['pendentes'] ?? 0 ?></span>
                            <span class="estatistica-label">Pendentes</span>
                        </div>
                    </div>
                    <div class="estatistica-card">
                        <div class="estatistica-icon">✅</div>
                        <div class="estatistica-info">
                            <span class="estatistica-numero"><?= $estatisticas['finalizados'] ?? 0 ?></span>
                            <span class="estatistica-label">Finalizados</span>
                        </div>
                    </div>
                    <div class="estatistica-card">
                        <div class="estatistica-icon">💰</div>
                        <div class="estatistica-info">
                            <span class="estatistica-numero">R$ <?= number_format($estatisticas['total_gasto'] ?? 0, 2, ',', '.') ?></span>
                            <span class="estatistica-label">Total Investido</span>
                        </div>
                    </div>
                </div>

                <!-- Filtros e Ações -->
                <div class="filtros-section">
                    <div class="filtros-header">
                        <h3 class="filtros-title">📄 Todas as Solicitações</h3>
                        <div class="filtros-actions">
                            <button onclick="imprimirRelatorio()" class="action-button secondary">
                                <span class="action-icon">🖨️</span>
                                Imprimir Relatório
                            </button>
                            <button onclick="exportarParaCSV()" class="action-button secondary">
                                <span class="action-icon">📥</span>
                                Exportar CSV
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lista de Solicitações -->
                <div class="solicitacoes-container" id="relatorio">
                    <?php if (count($solicitacoes) > 0): ?>
                        <div class="solicitacoes-list">
                            <?php foreach ($solicitacoes as $solicitacao): ?>
                                <div class="solicitacao-card status-<?= str_replace(' ', '-', strtolower($solicitacao['status'])) ?>">
                                    <div class="solicitacao-header">
                                        <div class="solicitacao-info">
                                            <span class="solicitacao-id">#<?= $solicitacao['id'] ?></span>
                                            <span class="solicitacao-data">
                                                📅 <?= date('d/m/Y H:i', strtotime($solicitacao['data'])) ?>
                                            </span>
                                        </div>
                                        <div class="solicitacao-status">
                                            <span class="status-badge status-<?= str_replace(' ', '-', strtolower($solicitacao['status'])) ?>">
                                                <?= ucfirst($solicitacao['status']) ?>
                                            </span>
                                            <?php if ($solicitacao['preco'] !== null): ?>
                                                <span class="solicitacao-preco-badge">
                                                    💰 R$ <?= number_format($solicitacao['preco'], 2, ',', '.') ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="solicitacao-body">
                                        <div class="solicitacao-descricao">
                                            <h4 class="descricao-title">Descrição do Serviço</h4>
                                            <p class="descricao-text"><?= nl2br(htmlspecialchars($solicitacao['descricao'])) ?></p>
                                        </div>

                                        <?php if (!empty($solicitacao['observacao'])): ?>
                                            <div class="observacao-box">
                                                <div class="observacao-header">
                                                    <span class="observacao-icon">📝</span>
                                                    <strong>Observações do Técnico</strong>
                                                </div>
                                                <p class="observacao-text"><?= nl2br(htmlspecialchars($solicitacao['observacao'])) ?></p>
                                            </div>
                                        <?php endif; ?>

                                        <div class="solicitacao-metadata">
                                            <div class="metadata-item">
                                                <span class="metadata-label">🆔 ID:</span>
                                                <span class="metadata-value">#<?= $solicitacao['id'] ?></span>
                                            </div>
                                            <div class="metadata-item">
                                                <span class="metadata-label">📅 Data:</span>
                                                <span class="metadata-value"><?= date('d/m/Y H:i', strtotime($solicitacao['data'])) ?></span>
                                            </div>
                                            <?php if ($solicitacao['preco'] !== null): ?>
                                                <div class="metadata-item">
                                                    <span class="metadata-label">💰 Valor:</span>
                                                    <span class="metadata-value valor">R$ <?= number_format($solicitacao['preco'], 2, ',', '.') ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="solicitacao-actions">
                                        <button type="button" class="btn-outline small" onclick="copiarSolicitacao(<?= $solicitacao['id'] ?>)">
                                            <span class="btn-icon">📋</span>
                                            Copiar Descrição
                                        </button>
                                        <?php if ($solicitacao['status'] === 'pendente'): ?>
                                            <a href="mensagens.php" class="btn-primary small">
                                                <span class="btn-icon">💬</span>
                                                Enviar Mensagem
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- Estado Vazio -->
                        <div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <h3>Nenhuma solicitação encontrada</h3>
                            <p>Você ainda não fez nenhuma solicitação de serviço.</p>
                            <a href="solicitar.php" class="btn-primary">
                                <span class="btn-icon">📝</span>
                                Fazer Primeira Solicitação
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Paginação -->
                <?php if ($totalPaginas > 1): ?>
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="?pagina=<?= $pagina - 1 ?>" class="pagination-link pagination-prev">
                                ← Anterior
                            </a>
                        <?php endif; ?>

                        <div class="pagination-numbers">
                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <a href="?pagina=<?= $i ?>" 
                                   class="pagination-link <?= $i === $pagina ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>

                        <?php if ($pagina < $totalPaginas): ?>
                            <a href="?pagina=<?= $pagina + 1 ?>" class="pagination-link pagination-next">
                                Próxima →
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Resumo por Status -->
                <div class="resumo-section">
                    <div class="section-header">
                        <h3 class="section-title">📊 Resumo por Status</h3>
                    </div>
                    <div class="resumo-grid">
                        <div class="resumo-item pendente">
                            <div class="resumo-icon">⏳</div>
                            <div class="resumo-content">
                                <span class="resumo-count"><?= $estatisticas['pendentes'] ?? 0 ?></span>
                                <span class="resumo-label">Pendentes</span>
                            </div>
                        </div>
                        <div class="resumo-item finalizado">
                            <div class="resumo-icon">✅</div>
                            <div class="resumo-content">
                                <span class="resumo-count"><?= $estatisticas['finalizados'] ?? 0 ?></span>
                                <span class="resumo-label">Finalizados</span>
                            </div>
                        </div>
                        <div class="resumo-item nao-resolvido">
                            <div class="resumo-icon">❌</div>
                            <div class="resumo-content">
                                <span class="resumo-count"><?= $estatisticas['nao_resolvidos'] ?? 0 ?></span>
                                <span class="resumo-label">Não Resolvidos</span>
                            </div>
                        </div>
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
        function imprimirRelatorio() {
            const conteudo = document.getElementById('relatorio').innerHTML;
            const janela = window.open('', '', 'width=800,height=600');
            
            janela.document.write(`
                <html>
                    <head>
                        <title>Relatório de Solicitações - <?= htmlspecialchars($cliente['nome']) ?></title>
                        <style>
                            body { 
                                font-family: "Segoe UI", sans-serif; 
                                padding: 20px; 
                                color: #333;
                                line-height: 1.4;
                            }
                            .solicitacao-card { 
                                border: 1px solid #ddd; 
                                border-radius: 8px; 
                                padding: 15px; 
                                margin-bottom: 15px;
                                page-break-inside: avoid;
                            }
                            .status-badge { 
                                padding: 4px 8px; 
                                border-radius: 4px; 
                                color: white; 
                                font-weight: bold;
                                font-size: 12px;
                            }
                            .status-pendente { background: #f39c12; }
                            .status-finalizado { background: #27ae60; }
                            .status-nao-resolvido { background: #e74c3c; }
                            .solicitacao-header {
                                display: flex;
                                justify-content: space-between;
                                margin-bottom: 10px;
                            }
                            .solicitacao-preco-badge {
                                background: #27ae60;
                                color: white;
                                padding: 4px 8px;
                                border-radius: 4px;
                                font-weight: bold;
                            }
                            .observacao-box {
                                background: #f8f9fa;
                                padding: 10px;
                                border-left: 3px solid #007bff;
                                margin: 10px 0;
                            }
                            @media print {
                                .solicitacao-card {
                                    break-inside: avoid;
                                }
                            }
                        </style>
                    </head>
                    <body>
                        <h2>Relatório de Solicitações</h2>
                        <p><strong>Cliente:</strong> <?= htmlspecialchars($cliente['nome']) ?></p>
                        <p><strong>Data do Relatório:</strong> ${new Date().toLocaleDateString('pt-BR')}</p>
                        <hr>
                        ${conteudo}
                    </body>
                </html>
            `);
            
            janela.document.close();
            janela.print();
        }

        function exportarParaCSV() {
            // Simulação de exportação CSV
            alert('Funcionalidade de exportação CSV será implementada em breve!');
        }

        function copiarSolicitacao(id) {
            const solicitacao = document.querySelector(`.solicitacao-card .descricao-text`);
            if (solicitacao) {
                const texto = solicitacao.innerText;
                navigator.clipboard.writeText(texto).then(() => {
                    alert('Descrição copiada para a área de transferência!');
                }).catch(() => {
                    alert('Erro ao copiar descrição.');
                });
            }
        }

        // Ordenação por data (mais recente/mais antiga)
        function ordenarPorData(crescente = true) {
            const container = document.querySelector('.solicitacoes-list');
            const cards = Array.from(container.querySelectorAll('.solicitacao-card'));
            
            cards.sort((a, b) => {
                const dataA = new Date(a.querySelector('.solicitacao-data').textContent.replace('📅 ', ''));
                const dataB = new Date(b.querySelector('.solicitacao-data').textContent.replace('📅 ', ''));
                return crescente ? dataA - dataB : dataB - dataA;
            });
            
            // Limpar e reordenar
            container.innerHTML = '';
            cards.forEach(card => container.appendChild(card));
        }

        // Filtro por status
        function filtrarPorStatus(status) {
            const cards = document.querySelectorAll('.solicitacao-card');
            cards.forEach(card => {
                if (status === 'todos' || card.classList.contains(`status-${status}`)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar tooltips
            const tooltips = document.querySelectorAll('[title]');
            tooltips.forEach(tooltip => {
                tooltip.addEventListener('mouseenter', function() {
                    // Implementar tooltip customizado se necessário
                });
            });
        });
    </script>
        <!-- No final do arquivo, antes do </body> -->
<?php include '../../components/cookie-banner.php'; ?>
</body>
</html>