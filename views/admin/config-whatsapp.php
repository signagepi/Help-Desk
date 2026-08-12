<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login-admin.php');
    exit;
}

// Buscar configuração atual do WhatsApp
$stmt = $pdo->query("SELECT * FROM config_whatsapp ORDER BY id DESC LIMIT 1");
$config = $stmt->fetch();

// Se não existir, criar padrão
if (!$config) {
    $pdo->exec("INSERT INTO config_whatsapp (numero_whatsapp, mensagem_padrao, ativo, posicao) 
                VALUES ('5565996495476', 'Olá! Gostaria de mais informações sobre os serviços.', TRUE, 'direita')");
    $stmt = $pdo->query("SELECT * FROM config_whatsapp ORDER BY id DESC LIMIT 1");
    $config = $stmt->fetch();
}

// Processar atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_whatsapp'])) {
    $numero_whatsapp = trim($_POST['numero_whatsapp']);
    $mensagem_padrao = trim($_POST['mensagem_padrao']);
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $posicao = $_POST['posicao'];
    
    try {
        if ($config) {
            // Atualizar
            $stmt = $pdo->prepare("UPDATE config_whatsapp SET 
                numero_whatsapp = ?, 
                mensagem_padrao = ?, 
                ativo = ?, 
                posicao = ? 
                WHERE id = ?");
            $stmt->execute([$numero_whatsapp, $mensagem_padrao, $ativo, $posicao, $config['id']]);
        } else {
            // Inserir
            $stmt = $pdo->prepare("INSERT INTO config_whatsapp 
                (numero_whatsapp, mensagem_padrao, ativo, posicao) 
                VALUES (?, ?, ?, ?)");
            $stmt->execute([$numero_whatsapp, $mensagem_padrao, $ativo, $posicao]);
        }
        
        $_SESSION['success_msg'] = "Configurações do WhatsApp atualizadas com sucesso!";
        header('Location: config-whatsapp.php');
        exit;
        
    } catch (PDOException $e) {
        $error_msg = "Erro ao salvar configurações: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar WhatsApp - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin/dashboard.css">
    <style>
        .config-container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            border-color: #3498db;
            outline: none;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn-save {
            background: #25D366;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            margin-right: 10px;
        }
        
        .btn-save:hover {
            background: #128C7E;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .preview-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            border-left: 4px solid #25D366;
        }
        
        .preview-title {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .whatsapp-preview {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            margin-top: 10px;
        }
        
        .info-text {
            color: #6c757d;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-checkbox {
            width: 18px;
            height: 18px;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .config-container {
                padding: 20px;
                margin: 10px;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <header class="admin-header">
            <div class="header-content">
                <h1 class="logo">Infoservice - Admin</h1>
                <div class="user-info">
                    <span>Administrador</span>
                </div>
            </div>
        </header>

        <main class="admin-main">
            <div class="container">
                <div class="page-header">
                    <h2 class="page-title">💬 Configurar WhatsApp</h2>
                    <div class="page-actions">
                        <a href="dashboard.php" class="btn-secondary">← Voltar ao Dashboard</a>
                    </div>
                </div>

                <nav class="admin-nav">
                    <div class="nav-buttons">
                        <a href="dashboard.php" class="nav-button">
                            <span class="nav-icon">📋</span>
                            Solicitações
                        </a>
                        <a href="config-pix.php" class="nav-button">
                            <span class="nav-icon">💰</span>
                            Configurar PIX
                        </a>
                        <a href="config-whatsapp.php" class="nav-button active">
                            <span class="nav-icon">💬</span>
                            Configurar WhatsApp
                        </a>
                        <a href="mensagens.php" class="nav-button">
                            <span class="nav-icon">📬</span>
                            Mensagens
                        </a>
                        <a href="../../auth/logout.php" class="nav-button logout">
                            <span class="nav-icon">🔒</span>
                            Logoff
                        </a>
                    </div>
                </nav>

                <div class="config-container">
                    <?php if (isset($_SESSION['success_msg'])): ?>
                        <div class="alert-success">
                            <?= $_SESSION['success_msg'] ?>
                            <?php unset($_SESSION['success_msg']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_msg)): ?>
                        <div class="alert-error">
                            <?= $error_msg ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">📱 Número do WhatsApp</label>
                            <input type="text" 
                                   name="numero_whatsapp" 
                                   value="<?= htmlspecialchars($config['numero_whatsapp']) ?>" 
                                   class="form-input"
                                   placeholder="5565999999999 (com código do país)"
                                   required>
                            <div class="info-text">
                                Formato: 5565999999999 (código do Brasil + DDD + número, sem espaços ou caracteres especiais)
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">💭 Mensagem Padrão</label>
                            <textarea name="mensagem_padrao" 
                                      class="form-textarea"
                                      placeholder="Mensagem que será enviada automaticamente quando o cliente clicar no botão"><?= htmlspecialchars($config['mensagem_padrao']) ?></textarea>
                            <div class="info-text">
                                Esta mensagem será pré-preenchida quando o cliente clicar no botão do WhatsApp
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">📍 Posição do Botão</label>
                            <select name="posicao" class="form-select">
                                <option value="direita" <?= $config['posicao'] === 'direita' ? 'selected' : '' ?>>Direita</option>
                                <option value="esquerda" <?= $config['posicao'] === 'esquerda' ? 'selected' : '' ?>>Esquerda</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" 
                                       name="ativo" 
                                       id="ativo"
                                       class="form-checkbox"
                                       <?= $config['ativo'] ? 'checked' : '' ?>>
                                <label for="ativo" class="form-label" style="margin: 0;">
                                    Ativar Botão do WhatsApp
                                </label>
                            </div>
                            <div class="info-text">
                                Quando desativado, o botão não será exibido no site
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="salvar_whatsapp" value="1" class="btn-save">
                                💾 Salvar Configurações
                            </button>
                            <a href="dashboard.php" class="btn-cancel">
                                ❌ Cancelar
                            </a>
                        </div>
                    </form>

                    <!-- Preview -->
                    <div class="preview-section">
                        <h3 class="preview-title">👀 Visualização do Botão</h3>
                        
                        <div class="whatsapp-preview">
                            <p><strong>Status:</strong> 
                                <span style="color: <?= $config['ativo'] ? '#25D366' : '#dc3545' ?>;">
                                    <?= $config['ativo'] ? '✅ ATIVADO' : '❌ DESATIVADO' ?>
                                </span>
                            </p>
                            
                            <p><strong>Número:</strong> <?= htmlspecialchars($config['numero_whatsapp']) ?></p>
                            
                            <p><strong>Posição:</strong> 
                                <?= $config['posicao'] === 'direita' ? 'Direita inferior' : 'Esquerda inferior' ?>
                            </p>
                            
                            <p><strong>Mensagem padrão:</strong></p>
                            <div style="background: #f1f3f4; padding: 10px; border-radius: 5px; margin-top: 5px;">
                                "<?= htmlspecialchars($config['mensagem_padrao']) ?>"
                            </div>
                        </div>
                        
                        <div class="info-text" style="margin-top: 15px;">
                            O botão será exibido como um ícone flutuante do WhatsApp na posição configurada.
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="cliente-footer">
            <div class="copyright">
                <?php include '../components/footer.php'; ?>
            </div>     
        </footer>
    </div>
</body>
</html>