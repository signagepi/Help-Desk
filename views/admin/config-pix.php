<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login-admin.php');
    exit;
}

// Verificar e atualizar a estrutura da tabela se necessário
try {
    $pdo->query("SELECT 1 FROM config_empresa LIMIT 1");
    
    // Verificar se os campos bancários existem, se não, adicionar
    $stmt = $pdo->query("DESCRIBE config_empresa");
    $campos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $campos_necessarios = ['banco_nome', 'banco_agencia', 'banco_conta', 'banco_tipo'];
    $campos_faltantes = array_diff($campos_necessarios, $campos);
    
    if (!empty($campos_faltantes)) {
        foreach ($campos_faltantes as $campo) {
            $tipo = 'VARCHAR(100)';
            if ($campo === 'banco_agencia' || $campo === 'banco_conta') {
                $tipo = 'VARCHAR(20)';
            } elseif ($campo === 'banco_tipo') {
                $tipo = 'VARCHAR(20)';
            }
            
            $pdo->exec("ALTER TABLE config_empresa ADD COLUMN $campo $tipo");
        }
    }
    
} catch (Exception $e) {
    // Criar tabela se não existir
    $pdo->exec("
        CREATE TABLE config_empresa (
            id INT PRIMARY KEY AUTO_INCREMENT,
            chave_pix VARCHAR(255),
            nome_empresa VARCHAR(100),
            telefone VARCHAR(20),
            email VARCHAR(100),
            endereco TEXT,
            banco_nome VARCHAR(100),
            banco_agencia VARCHAR(20),
            banco_conta VARCHAR(20),
            banco_tipo VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

// Buscar configuração atual
$stmt = $pdo->query("SELECT * FROM config_empresa ORDER BY id DESC LIMIT 1");
$config = $stmt->fetch();

// Inicializar mensagem
$mensagem = '';

// Salvar configuração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chave_pix'])) {
    try {
        $chave_pix = trim($_POST['chave_pix']);
        $nome_empresa = trim($_POST['nome_empresa']);
        $telefone = trim($_POST['telefone']);
        $email = trim($_POST['email']);
        $endereco = trim($_POST['endereco']);
        $banco_nome = trim($_POST['banco_nome'] ?? '');
        $banco_agencia = trim($_POST['banco_agencia'] ?? '');
        $banco_conta = trim($_POST['banco_conta'] ?? '');
        $banco_tipo = trim($_POST['banco_tipo'] ?? '');
        
        // Verificar se já existe configuração
        if ($config) {
            // Atualizar
            $stmt = $pdo->prepare("UPDATE config_empresa SET 
                chave_pix = ?, 
                nome_empresa = ?, 
                telefone = ?, 
                email = ?, 
                endereco = ?,
                banco_nome = ?,
                banco_agencia = ?,
                banco_conta = ?,
                banco_tipo = ?
                WHERE id = ?");
            
            $stmt->execute([
                $chave_pix, 
                $nome_empresa, 
                $telefone, 
                $email, 
                $endereco,
                $banco_nome, 
                $banco_agencia, 
                $banco_conta, 
                $banco_tipo,
                $config['id']
            ]);
            $mensagem = "✅ Configurações atualizadas com sucesso!";
        } else {
            // Inserir
            $stmt = $pdo->prepare("INSERT INTO config_empresa 
                (chave_pix, nome_empresa, telefone, email, endereco, banco_nome, banco_agencia, banco_conta, banco_tipo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $chave_pix, 
                $nome_empresa, 
                $telefone, 
                $email, 
                $endereco,
                $banco_nome, 
                $banco_agencia, 
                $banco_conta, 
                $banco_tipo
            ]);
            $mensagem = "✅ Configurações salvas com sucesso!";
        }
        
        // Recarregar configuração
        $stmt = $pdo->query("SELECT * FROM config_empresa ORDER BY id DESC LIMIT 1");
        $config = $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log("Erro ao salvar configurações: " . $e->getMessage());
        $mensagem = "❌ Erro ao salvar configurações. Verifique os logs para mais detalhes.";
    } catch (Exception $e) {
        error_log("Erro geral: " . $e->getMessage());
        $mensagem = "❌ Erro ao processar solicitação.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar PIX - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin/dashboard.css">
    <style>
        .config-container {
            max-width: 700px;
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
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .btn-save:hover {
            background: #219a52;
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
            border-left: 4px solid #3498db;
        }
        
        .preview-title {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .chave-pix-preview {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
        
        .info-text {
            color: #6c757d;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .section-title {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin: 30px 0 20px 0;
            font-size: 1.2rem;
        }
        
        .banco-info-preview {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            margin-top: 10px;
        }
        
        .banco-info-item {
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }
        
        .banco-info-label {
            font-weight: bold;
            color: #555;
        }
        
        .banco-info-value {
            color: #333;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .config-container {
                padding: 20px;
                margin: 10px;
            }
        }
    </style>
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
                    <h2 class="page-title">💰 Configurar Pagamentos</h2>
                    <div class="page-actions">
                        <a href="dashboard.php" class="btn-secondary">← Voltar ao Dashboard</a>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="admin-nav">
                    <div class="nav-buttons">
                        <a href="dashboard.php" class="nav-button">
                            <span class="nav-icon">📋</span>
                            Solicitações
                        </a>
                        <a href="config-pix.php" class="nav-button active">
                            <span class="nav-icon">💰</span>
                            Configurar Pagamentos
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

                <!-- Configuração PIX e Dados Bancários -->
                <div class="config-container">
                    <?php if (!empty($mensagem)): ?>
                        <div class="<?= strpos($mensagem, '❌') !== false ? 'alert-error' : 'alert-success' ?>">
                            <?= $mensagem ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <h3 class="section-title">🏢 Informações da Empresa</h3>
                        
                        <div class="form-group">
                            <label class="form-label">🏷️ Nome da Empresa</label>
                            <input type="text" 
                                   name="nome_empresa" 
                                   value="<?= htmlspecialchars($config['nome_empresa'] ?? 'Infoservice') ?>" 
                                   class="form-input"
                                   required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">📱 Chave PIX</label>
                            <input type="text" 
                                   name="chave_pix" 
                                   value="<?= htmlspecialchars($config['chave_pix'] ?? '') ?>" 
                                   class="form-input"
                                   placeholder="CPF, CNPJ, telefone, email ou chave aleatória"
                                   required>
                            <div class="info-text">
                                Exemplos: 123.456.789-00 (CPF) | contato@empresa.com (Email) | +5511999999999 (Telefone)
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">📞 Telefone</label>
                            <input type="text" 
                                   name="telefone" 
                                   value="<?= htmlspecialchars($config['telefone'] ?? '(11) 99999-9999') ?>" 
                                   class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">📧 Email</label>
                            <input type="email" 
                                   name="email" 
                                   value="<?= htmlspecialchars($config['email'] ?? 'contato@infoservice.com') ?>" 
                                   class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">🏢 Endereço</label>
                            <textarea name="endereco" 
                                      class="form-textarea"
                                      placeholder="Endereço completo da empresa"><?= htmlspecialchars($config['endereco'] ?? 'Rua Exemplo, 123 - Centro - São Paulo/SP') ?></textarea>
                        </div>

                        <h3 class="section-title">🏦 Dados Bancários</h3>
                        
                        <div class="form-group">
                            <label class="form-label">🏦 Nome do Banco</label>
                            <input type="text" 
                                   name="banco_nome" 
                                   value="<?= htmlspecialchars($config['banco_nome'] ?? '') ?>" 
                                   class="form-input"
                                   placeholder="Ex: Banco do Brasil, Itaú, Bradesco...">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">📊 Agência</label>
                                <input type="text" 
                                       name="banco_agencia" 
                                       value="<?= htmlspecialchars($config['banco_agencia'] ?? '') ?>" 
                                       class="form-input"
                                       placeholder="Número da agência">
                            </div>

                            <div class="form-group">
                                <label class="form-label">🔢 Conta</label>
                                <input type="text" 
                                       name="banco_conta" 
                                       value="<?= htmlspecialchars($config['banco_conta'] ?? '') ?>" 
                                       class="form-input"
                                       placeholder="Número da conta">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">💳 Tipo de Conta</label>
                            <select name="banco_tipo" class="form-select">
                                <option value="">Selecione o tipo...</option>
                                <option value="corrente" <?= (isset($config['banco_tipo']) && $config['banco_tipo'] === 'corrente') ? 'selected' : '' ?>>Conta Corrente</option>
                                <option value="poupanca" <?= (isset($config['banco_tipo']) && $config['banco_tipo'] === 'poupanca') ? 'selected' : '' ?>>Conta Poupança</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-save">
                            💾 Salvar Configurações
                        </button>
                    </form>

                    <!-- Preview -->
                    <div class="preview-section">
                        <h3 class="preview-title">👀 Visualização no Cliente</h3>
                        
                        <p><strong>Chave PIX que será exibida:</strong></p>
                        <div class="chave-pix-preview">
                            <?= htmlspecialchars($config['chave_pix'] ?? 'Nenhuma chave configurada') ?>
                        </div>
                        
                        <?php if (!empty($config['banco_nome']) || !empty($config['banco_agencia'])): ?>
                        <p style="margin-top: 20px;"><strong>Dados Bancários que serão exibidos:</strong></p>
                        <div class="banco-info-preview">
                            <?php if (!empty($config['banco_nome'])): ?>
                                <div class="banco-info-item">
                                    <span class="banco-info-label">Banco:</span>
                                    <span class="banco-info-value"><?= htmlspecialchars($config['banco_nome']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($config['banco_agencia'])): ?>
                                <div class="banco-info-item">
                                    <span class="banco-info-label">Agência:</span>
                                    <span class="banco-info-value"><?= htmlspecialchars($config['banco_agencia']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($config['banco_conta'])): ?>
                                <div class="banco-info-item">
                                    <span class="banco-info-label">Conta:</span>
                                    <span class="banco-info-value"><?= htmlspecialchars($config['banco_conta']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($config['banco_tipo'])): ?>
                                <div class="banco-info-item">
                                    <span class="banco-info-label">Tipo:</span>
                                    <span class="banco-info-value">
                                        <?= $config['banco_tipo'] === 'corrente' ? 'Conta Corrente' : 'Conta Poupança' ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                            <p style="margin-top: 15px; color: #666; font-style: italic;">
                                Nenhum dado bancário configurado. Os clientes verão apenas a chave PIX.
                            </p>
                        <?php endif; ?>
                        
                        <div class="info-text" style="margin-top: 15px;">
                            Estas informações serão exibidas quando os clientes clicarem em "Transferência Bancária" no financeiro
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="cliente-footer">
            <div class="copyright">
                <?php include '../components/footer.php'; ?>
            </div>     
        </footer>
    </div>
</body>
</html>