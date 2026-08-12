<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// PATH CORRETO para views/admin/
$root_path = __DIR__ . '/../..';
require $root_path . '/config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . $root_path . '/auth/login-admin.php');
    exit;
}

// Buscar notificações de forma segura
$notificacoes = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM mensagens WHERE destinatario_id = ? AND lida = 0");
    $stmt->execute([$_SESSION['admin_id']]);
    $result = $stmt->fetch();
    $notificacoes = $result['total'] ?? 0;
} catch (Exception $e) {
    $notificacoes = 0;
}

// Função para gerar slug
function gerarSlug($titulo) {
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $titulo);
    $slug = trim($slug, '-');
    $slug = strtolower($slug);
    return $slug;
}

// Função segura para exibir dados
function safe_html($data) {
    if ($data === null || $data === '') {
        return '';
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Processar adição/edição de item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    
    // Validar e sanitizar dados
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $resumo = trim($_POST['resumo'] ?? '');
    $conteudo_completo = trim($_POST['conteudo_completo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $tecnologias = trim($_POST['tecnologias'] ?? '');
    $link_projeto = trim($_POST['link_projeto'] ?? '');
    $destaque = isset($_POST['destaque']) ? 1 : 0;
    
    // Validar campos obrigatórios
    if (empty($titulo) || empty($descricao) || empty($resumo) || empty($conteudo_completo) || empty($categoria)) {
        $_SESSION['error'] = "Todos os campos obrigatórios devem ser preenchidos!";
        header('Location: gerenciar-portfolio.php');
        exit;
    }
    
    $slug = gerarSlug($titulo);
    
    // Verificar se slug já existe
    $stmt = $pdo->prepare("SELECT id FROM portfolio WHERE slug = ? AND id != ?");
    $id_edicao = $_POST['id'] ?? 0;
    $stmt->execute([$slug, $id_edicao]);
    if ($stmt->fetch()) {
        $slug = $slug . '-' . time();
    }
    
    if ($acao === 'adicionar') {
        // Validar upload de imagem
        if ($_FILES['imagem']['error'] !== 0) {
            $_SESSION['error'] = "É necessário selecionar uma imagem para o projeto!";
            header('Location: gerenciar-portfolio.php');
            exit;
        }
        
        // Upload da imagem
        $imagem_nome = '';
        if ($_FILES['imagem']['error'] === 0) {
            $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
            $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($extensao, $extensoes_permitidas)) {
                $_SESSION['error'] = "Formato de imagem não permitido. Use: JPG, PNG, GIF ou WEBP";
                header('Location: gerenciar-portfolio.php');
                exit;
            }
            
            $imagem_nome = 'portfolio_' . time() . '.' . $extensao;
            $upload_path = $root_path . '/assets/images/portfolio/' . $imagem_nome;
            
            // Criar pasta se não existir
            if (!is_dir(dirname($upload_path))) {
                mkdir(dirname($upload_path), 0755, true);
            }
            
            if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $upload_path)) {
                $_SESSION['error'] = "Erro ao fazer upload da imagem!";
                header('Location: gerenciar-portfolio.php');
                exit;
            }
        }
        
        // Inserir no banco
        try {
            $stmt = $pdo->prepare("INSERT INTO portfolio (titulo, slug, descricao, resumo, conteudo_completo, categoria, tecnologias, imagem, link_projeto, destaque) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$titulo, $slug, $descricao, $resumo, $conteudo_completo, $categoria, $tecnologias, $imagem_nome, $link_projeto, $destaque]);
            
            if ($result) {
                $_SESSION['success'] = "Projeto adicionado com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao adicionar projeto!";
            }
            
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erro no banco de dados: " . $e->getMessage();
        }
        
    } else if ($acao === 'editar') {
        // Edição
        $id = intval($_POST['id']);
        
        if ($_FILES['imagem']['error'] === 0) {
            // Nova imagem foi enviada
            $extensao = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
            $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($extensao, $extensoes_permitidas)) {
                $_SESSION['error'] = "Formato de imagem não permitido. Use: JPG, PNG, GIF ou WEBP";
                header('Location: gerenciar-portfolio.php');
                exit;
            }
            
            $imagem_nome = 'portfolio_' . time() . '.' . $extensao;
            $upload_path = $root_path . '/assets/images/portfolio/' . $imagem_nome;
            
            if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $upload_path)) {
                $_SESSION['error'] = "Erro ao fazer upload da imagem!";
                header('Location: gerenciar-portfolio.php');
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE portfolio SET titulo = ?, slug = ?, descricao = ?, resumo = ?, conteudo_completo = ?, categoria = ?, tecnologias = ?, imagem = ?, link_projeto = ?, destaque = ? WHERE id = ?");
            $result = $stmt->execute([$titulo, $slug, $descricao, $resumo, $conteudo_completo, $categoria, $tecnologias, $imagem_nome, $link_projeto, $destaque, $id]);
        } else {
            // Manter imagem atual
            $stmt = $pdo->prepare("UPDATE portfolio SET titulo = ?, slug = ?, descricao = ?, resumo = ?, conteudo_completo = ?, categoria = ?, tecnologias = ?, link_projeto = ?, destaque = ? WHERE id = ?");
            $result = $stmt->execute([$titulo, $slug, $descricao, $resumo, $conteudo_completo, $categoria, $tecnologias, $link_projeto, $destaque, $id]);
        }
        
        if ($result) {
            $_SESSION['success'] = "Projeto atualizado com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao atualizar projeto!";
        }
    }
    
    header('Location: gerenciar-portfolio.php');
    exit;
}

// Processar exclusão
if (isset($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM portfolio WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            $_SESSION['success'] = "Projeto excluído com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao excluir projeto!";
        }
    }
    
    header('Location: gerenciar-portfolio.php');
    exit;
}

// Buscar item para edição
$item_edicao = null;
if (isset($_GET['editar'])) {
    $id = intval($_GET['editar']);
    
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM portfolio WHERE id = ?");
        $stmt->execute([$id]);
        $item_edicao = $stmt->fetch();
    }
}

// Buscar itens do portfólio
$stmt = $pdo->query("SELECT * FROM portfolio ORDER BY data_criacao DESC");
$portfolio = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Portfólio - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ESTILOS DO PORTFÓLIO - MANTIDOS */
        .portfolio-admin { padding: 2rem 0; }
        .form-adicionar { background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border: 1px solid #e0e0e0; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; transition: border-color 0.3s ease; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #28a745; box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.1); }
        .form-textarea { resize: vertical; min-height: 100px; }
        .form-group.full-width { grid-column: 1 / -1; }
        .checkbox-group { display: flex; align-items: center; gap: 0.5rem; }
        .checkbox-group input[type="checkbox"] { width: auto; }
        .btn-primary { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3); }
        .btn-secondary { background: #6c757d; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 5px; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; margin-left: 1rem; transition: all 0.3s ease; }
        .btn-secondary:hover { background: #5a6268; transform: translateY(-2px); }
        .portfolio-grid-admin { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .portfolio-item-admin { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border: 1px solid #e0e0e0; position: relative; transition: all 0.3s ease; }
        .portfolio-item-admin:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .portfolio-image { width: 100%; height: 200px; object-fit: cover; border-bottom: 1px solid #e0e0e0; }
        .portfolio-content { padding: 1.5rem; }
        .portfolio-content h4 { margin: 0 0 1rem 0; color: #2c3e50; font-size: 1.2rem; }
        .portfolio-content p { margin: 0.5rem 0; color: #6c757d; font-size: 0.9rem; line-height: 1.4; }
        .portfolio-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .btn-editar { background: #28a745; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.9rem; transition: all 0.3s ease; }
        .btn-editar:hover { background: #218838; transform: translateY(-1px); }
        .btn-excluir { background: #dc3545; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.9rem; transition: all 0.3s ease; }
        .btn-excluir:hover { background: #c82333; transform: translateY(-1px); }
        .destaque-badge { position: absolute; top: 10px; right: 10px; background: #ffc107; color: #000; padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: bold; z-index: 1; }
        .no-image { width: 100%; height: 200px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #6c757d; flex-direction: column; border-bottom: 1px solid #e0e0e0; }
        .alert { padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border: 1px solid transparent; }
        .alert-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .page-header { margin-bottom: 2rem; }
        .page-title { color: #2c3e50; margin: 0 0 0.5rem 0; font-size: 2rem; }
        .page-header p { color: #6c757d; margin: 0; font-size: 1.1rem; }
        
        /* ESTILOS DO MENU */
        .admin-nav { background: white; padding: 1rem 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .nav-buttons { display: flex; gap: 1rem; flex-wrap: wrap; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .nav-button { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer; font-size: 0.9rem; }
        .nav-button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3); }
        .nav-button.logout { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); margin-left: auto; }
        .nav-button.logout:hover { box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3); }
        .nav-icon { font-size: 1.1rem; }
        .notification-badge { background: #ffc107; color: #000; border-radius: 50%; padding: 0.2rem 0.5rem; font-size: 0.8rem; font-weight: bold; margin-left: 0.5rem; }
        
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .portfolio-grid-admin { grid-template-columns: 1fr; }
            .portfolio-actions { flex-direction: column; }
            .btn-secondary { margin-left: 0; margin-top: 1rem; }
            .nav-buttons { flex-direction: column; }
            .nav-button.logout { margin-left: 0; margin-top: 1rem; }
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

        <nav class="admin-nav">
            <div class="nav-buttons">
                <a href="/views/admin/dashboard.php" class="nav-button">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="/views/admin/gerenciar-portfolio.php" class="nav-button" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <span class="nav-icon">🎨</span> Portfólio
                </a>
                <a href="/views/admin/mensagens.php" class="nav-button">
                    <span class="nav-icon">📬</span> Mensagens
                    <?php if ($notificacoes > 0): ?>
                        <span class="notification-badge"><?= $notificacoes ?></span>
                    <?php endif; ?>
                </a>
                <a href="/auth/logout.php" class="nav-button logout">
                    <span class="nav-icon">🔒</span> Sair
                </a>
            </div>
        </nav>

        <main class="admin-main">
            <div class="container">
                <div class="page-header">
                    <h2 class="page-title">🎨 Gerenciar Portfólio</h2>
                    <p>Adicione e gerencie os projetos exibidos na página inicial</p>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= safe_html($_SESSION['success']) ?><?php unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error"><?= safe_html($_SESSION['error']) ?><?php unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="form-adicionar">
                    <input type="hidden" name="acao" value="<?= $item_edicao ? 'editar' : 'adicionar' ?>">
                    <input type="hidden" name="id" value="<?= $item_edicao ? safe_html($item_edicao['id']) : '' ?>">
                    
                    <h3 style="margin: 0 0 1.5rem 0; color: #2c3e50; font-size: 1.5rem;">
                        <?= $item_edicao ? 'Editar Projeto' : 'Adicionar Novo Projeto' ?>
                    </h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Título do Projeto *</label>
                            <input type="text" name="titulo" value="<?= $item_edicao ? safe_html($item_edicao['titulo']) : '' ?>" required class="form-input">
                        </div>
                        <div class="form-group">
                            <label>Categoria *</label>
                            <select name="categoria" required class="form-select">
                                <option value="">Selecione...</option>
                                <option value="Desenvolvimento Web" <?= $item_edicao && $item_edicao['categoria'] == 'Desenvolvimento Web' ? 'selected' : '' ?>>Desenvolvimento Web</option>
                                <option value="Sistemas" <?= $item_edicao && $item_edicao['categoria'] == 'Sistemas' ? 'selected' : '' ?>>Sistemas</option>
                                <option value="Manutenção" <?= $item_edicao && $item_edicao['categoria'] == 'Manutenção' ? 'selected' : '' ?>>Manutenção</option>
                                <option value="Consultoria" <?= $item_edicao && $item_edicao['categoria'] == 'Consultoria' ? 'selected' : '' ?>>Consultoria</option>
                                <option value="Outros" <?= $item_edicao && $item_edicao['categoria'] == 'Outros' ? 'selected' : '' ?>>Outros</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label>Descrição Curta (Resumo) *</label>
                            <textarea name="resumo" rows="2" required class="form-textarea"><?= $item_edicao ? safe_html($item_edicao['resumo']) : '' ?></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label>Descrição Completa *</label>
                            <textarea name="descricao" rows="3" required class="form-textarea"><?= $item_edicao ? safe_html($item_edicao['descricao']) : '' ?></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label>Conteúdo Detalhado *</label>
                            <textarea name="conteudo_completo" rows="6" required class="form-textarea"><?= $item_edicao ? safe_html($item_edicao['conteudo_completo']) : '' ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Tecnologias (separadas por vírgula)</label>
                            <input type="text" name="tecnologias" value="<?= $item_edicao ? safe_html($item_edicao['tecnologias']) : '' ?>" class="form-input">
                        </div>
                        <div class="form-group">
                            <label>Link do Projeto (opcional)</label>
                            <input type="url" name="link_projeto" value="<?= $item_edicao ? safe_html($item_edicao['link_projeto']) : '' ?>" class="form-input">
                        </div>
                        <div class="form-group">
                            <label>Imagem do Projeto <?= $item_edicao ? '(deixe em branco para manter atual)' : '*' ?></label>
                            <input type="file" name="imagem" accept="image/*" <?= $item_edicao ? '' : 'required' ?> class="form-input">
                            <?php if($item_edicao && $item_edicao['imagem']): ?>
                                <small style="display: block; margin-top: 0.5rem; color: #6c757d;">Imagem atual: <?= safe_html($item_edicao['imagem']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="destaque" id="destaque" value="1" <?= $item_edicao && $item_edicao['destaque'] ? 'checked' : '' ?>>
                                <label for="destaque" style="margin: 0; font-weight: normal;">Destacar este projeto</label>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; align-items: center; margin-top: 1.5rem;">
                        <button type="submit" class="btn-primary"><?= $item_edicao ? 'Atualizar Projeto' : 'Adicionar Projeto' ?></button>
                        <?php if($item_edicao): ?>
                            <a href="gerenciar-portfolio.php" class="btn-secondary">Cancelar Edição</a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if (!empty($portfolio)): ?>
                <div class="portfolio-grid-admin">
                    <?php foreach($portfolio as $item): ?>
                    <div class="portfolio-item-admin">
                        <?php if($item['destaque']): ?><span class="destaque-badge">⭐ Destaque</span><?php endif; ?>
                        <?php if($item['imagem'] && file_exists($root_path . '/assets/images/portfolio/' . $item['imagem'])): ?>
                            <img src="/assets/images/portfolio/<?= $item['imagem'] ?>" alt="<?= safe_html($item['titulo']) ?>" class="portfolio-image">
                        <?php else: ?>
                            <div class="no-image"><i class="fas fa-image"></i><span>Sem imagem</span></div>
                        <?php endif; ?>
                        <div class="portfolio-content">
                            <h4><?= safe_html($item['titulo']) ?></h4>
                            <p><strong>Categoria:</strong> <?= safe_html($item['categoria']) ?></p>
                            <p><strong>Resumo:</strong> <?= substr(safe_html($item['resumo']), 0, 100) ?>...</p>
                            <div class="portfolio-actions">
                                <a href="gerenciar-portfolio.php?editar=<?= $item['id'] ?>" class="btn-editar"><i class="fas fa-edit"></i> Editar</a>
                                <a href="gerenciar-portfolio.php?excluir=<?= $item['id'] ?>" class="btn-excluir" onclick="return confirm('Tem certeza?')"><i class="fas fa-trash"></i> Excluir</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; background: white; border-radius: 10px; border: 1px solid #e0e0e0;">
                        <i class="fas fa-folder-open" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>
                        <h3 style="color: #6c757d; margin: 0 0 1rem 0;">Nenhum projeto cadastrado</h3>
                        <p style="color: #6c757d; margin: 0;">Adicione seu primeiro projeto usando o formulário acima.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>