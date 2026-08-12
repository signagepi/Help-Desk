<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login-admin.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 5;
$offset = ($pagina - 1) * $limite;

// Buscar notificações paginadas
$stmt = $pdo->prepare("
    SELECT m.id, m.mensagem, m.data, c.nome AS cliente_nome, m.remetente_id
    FROM mensagens m
    JOIN clientes c ON m.remetente_id = c.id
    WHERE m.destinatario_id = ? AND m.lida = 0
    ORDER BY m.data DESC
    LIMIT $limite OFFSET $offset
");
$stmt->execute([$admin_id]);
$novas = $stmt->fetchAll();

// Total de notificações
$stmtTotal = $pdo->prepare("
    SELECT COUNT(*) 
    FROM mensagens 
    WHERE destinatario_id = ? AND lida = 0
");
$stmtTotal->execute([$admin_id]);
$total = $stmtTotal->fetchColumn();
$totalPaginas = ceil($total / $limite);

// Buscar total de mensagens não lidas para o badge
$stmtNotif = $pdo->prepare("SELECT COUNT(*) FROM mensagens WHERE destinatario_id = ? AND lida = 0");
$stmtNotif->execute([$admin_id]);
$totalNaoLidas = $stmtNotif->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - Infoservice Admin</title>
    <link rel="stylesheet" href="../../assets/style.css">
    <link rel="stylesheet" href="../../assets/css/admin/notificacoes.css">
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
                        <h2 class="page-title">🔔 Notificações</h2>
                        <div class="page-stats">
                            <span class="total-notificacoes">Total: <?= $total ?> não lidas</span>
                            <?php if ($totalNaoLidas > 0): ?>
                                <span class="alert-badge"><?= $totalNaoLidas ?> novas</span>
                            <?php endif; ?>
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
                            Todas as Mensagens
                        </a>
                        <a href="limpar-mensagens.php" class="nav-button danger" 
                           onclick="return confirm('Tem certeza que deseja apagar TODAS as suas mensagens? Esta ação não pode ser desfeita.')">
                            <span class="nav-icon">🗑️</span>
                            Limpar Mensagens
                        </a>
                        <a href="../../auth/logout.php" class="nav-button logout">
                            <span class="nav-icon">🔒</span>
                            Sair
                        </a>
                    </div>
                </nav>

                <!-- Notificações List -->
                <div class="notificacoes-container">
                    <?php if (count($novas) > 0): ?>
                        <div class="notificacoes-list">
                            <?php foreach ($novas as $notificacao): ?>
                                <div class="notificacao-card">
                                    <!-- Cabeçalho da Notificação -->
                                    <div class="notificacao-header">
                                        <div class="notificacao-info">
                                            <div class="cliente-avatar">
                                                👤
                                            </div>
                                            <div class="notificacao-detalhes">
                                                <h3 class="cliente-nome"><?= htmlspecialchars($notificacao['cliente_nome']) ?></h3>
                                                <span class="notificacao-data">
                                                    📅 <?= date('d/m/Y H:i', strtotime($notificacao['data'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="notificacao-status">
                                            <span class="status-badge nova">🆕 Nova</span>
                                        </div>
                                    </div>

                                    <!-- Conteúdo da Notificação -->
                                    <div class="notificacao-body">
                                        <div class="mensagem-preview">
                                            <p><?= nl2br(htmlspecialchars($notificacao['mensagem'])) ?></p>
                                        </div>
                                        
                                        <!-- Ações -->
                                        <div class="notificacao-actions">
                                            <a href="conversa.php?cliente_id=<?= $notificacao['remetente_id'] ?>" class="btn-primary">
                                                <span class="btn-icon">💬</span>
                                                Abrir Conversa
                                            </a>
                                            <form method="POST" action="../../controllers/marcar-como-lida.php" class="action-form">
                                                <input type="hidden" name="mensagem_id" value="<?= $notificacao['id'] ?>">
                                                <button type="submit" class="btn-secondary">
                                                    <span class="btn-icon">✅</span>
                                                    Marcar como Lida
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- Estado Vazio -->
                        <div class="empty-state">
                            <div class="empty-icon">🎉</div>
                            <h3>Nenhuma notificação nova</h3>
                            <p>Você está em dia com todas as mensagens!</p>
                            <a href="mensagens.php" class="btn-primary empty-action">
                                <span class="btn-icon">📬</span>
                                Ver Todas as Mensagens
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                 </br>
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
        // Auto-refresh para novas notificações
        document.addEventListener('DOMContentLoaded', function() {
            // Atualizar a página a cada 30 segundos para verificar novas notificações
            setInterval(() => {
                // Você pode implementar uma verificação AJAX aqui
                // Por enquanto, vamos apenas recarregar a página
                // window.location.reload();
            }, 30000);
        });
    </script>
</body>
</html>