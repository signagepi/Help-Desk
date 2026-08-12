<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login-admin.php');
    exit;
}

// Buscar configurações
$stmt = $pdo->prepare("SELECT * FROM config_cookies WHERE id = 1");
$stmt->execute();
$config = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cookies - Admin</title>
    <link rel="stylesheet" href="../../assets/css/admin/dashboard.css">
    <style>
        .config-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 30px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #28a745;
        }
        
        input:checked + .slider:before {
            transform: translateX(30px);
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <!-- Header e navegação existentes -->
        
        <main class="admin-main">
            <div class="container">
                <div class="page-header">
                    <h2>⚙️ Gerenciar Configurações de Cookies</h2>
                    <p>Configure o banner de cookies do sistema</p>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?= $_SESSION['success'] ?>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="../../controllers/gerenciar-cookies.php">
                    <div class="config-section">
                        <h3>🔧 Configurações Gerais</h3>
                        
                        <div class="form-group">
                            <label class="toggle-switch">
                                <input type="checkbox" name="ativo" <?= $config['ativo'] ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                            <span style="margin-left: 10px; font-weight: 600;">
                                Sistema de Cookies <?= $config['ativo'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </div>

                        <div class="form-group">
                            <label>Mensagem do Banner:</label>
                            <textarea name="mensagem" class="form-input" rows="4" placeholder="Digite a mensagem que aparecerá no banner de cookies..."><?= htmlspecialchars($config['mensagem']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Link da Política de Cookies:</label>
                            <input type="text" name="politica_link" class="form-input" value="<?= htmlspecialchars($config['politica_link']) ?>" placeholder="/politica-cookies.php">
                        </div>

                        <button type="submit" class="btn-primary">
                            💾 Salvar Configurações
                        </button>
                    </div>
                </form>

                <div class="config-section">
                    <h3>📊 Estatísticas de Aceitação</h3>
                    <p>Total de usuários que aceitaram cookies: <strong id="acceptanceCount">Carregando...</strong></p>
                    <p>Taxa de aceitação: <strong id="acceptanceRate">Carregando...</strong></p>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Estatísticas simples (em produção, você salvaria no banco)
        function loadStatistics() {
            const accepted = localStorage.getItem('cookie_acceptances') || 0;
            const total = localStorage.getItem('cookie_show_count') || 0;
            
            document.getElementById('acceptanceCount').textContent = accepted;
            
            if (total > 0) {
                const rate = ((accepted / total) * 100).toFixed(1);
                document.getElementById('acceptanceRate').textContent = rate + '%';
            } else {
                document.getElementById('acceptanceRate').textContent = '0%';
            }
        }
        
        loadStatistics();
    </script>
</body>
</html>