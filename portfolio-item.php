<?php
// PATH CORRETO para arquivos na RAIZ
$root_path = __DIR__;
require 'config/db.php';

if (!isset($_GET['slug'])) {
    header('Location: index.php');
    exit;
}

$slug = $_GET['slug'];

// Buscar projeto pelo slug
$stmt = $pdo->prepare("SELECT * FROM portfolio WHERE slug = ? AND ativo = 1");
$stmt->execute([$slug]);
$projeto = $stmt->fetch();

if (!$projeto) {
    header('Location: index.php');
    exit;
}

// Buscar projetos relacionados (mesma categoria)
$stmt = $pdo->prepare("SELECT * FROM portfolio WHERE categoria = ? AND id != ? AND ativo = 1 ORDER BY data_criacao DESC LIMIT 3");
$stmt->execute([$projeto['categoria'], $projeto['id']]);
$projetos_relacionados = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($projeto['titulo']) ?> - Infoservice</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .portfolio-detail {
            min-height: 100vh;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .portfolio-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0 40px;
            position: relative;
        }

        .header-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .btn-voltar {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            padding: 0.75rem 1.5rem;
            border: 2px solid #667eea;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .btn-voltar:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .project-title {
            font-size: 3rem;
            margin: 0 0 1rem 0;
            font-weight: 800;
            line-height: 1.2;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .project-subtitle {
            font-size: 1.3rem;
            margin: 0;
            opacity: 0.95;
            font-weight: 400;
            line-height: 1.5;
            max-width: 800px;
        }

        .portfolio-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .project-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            margin-bottom: 40px;
            border: 1px solid #eef2f7;
        }

        .project-image {
            width: 100%;
            height: 450px;
            object-fit: cover;
        }

        .project-info {
            padding: 3rem;
        }

        .project-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f1f3f4;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #5f6368;
            font-weight: 600;
            font-size: 1rem;
        }

        .meta-item i {
            color: #667eea;
            font-size: 1.1rem;
            width: 20px;
        }

        .tech-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin: 2rem 0;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
            border-radius: 15px;
            border: 1px solid #e6e8ff;
        }

        .tech-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .project-content .paragraph {
    margin-bottom: 1.5rem;
    text-align: justify;
    text-justify: inter-word;
    line-height: 1.8;
    font-size: 1.1rem;
    color: #2d3748;
    text-indent: 2rem;
}

.project-content .paragraph:first-of-type {
    text-indent: 0;
    font-weight: 500;
}

.project-content .paragraph:first-of-type::first-letter {
    font-size: 1.4rem;
    font-weight: bold;
    color: #667eea;
    float: left;
    line-height: 1;
    margin-right: 0.2rem;
}

        .project-content {
            line-height: 1.8;
            color: #2d3748;
            font-size: 1.15rem;
        }

        .project-content h2 {
            color: #2c3e50;
            margin: 2.5rem 0 1.5rem 0;
            font-size: 2rem;
            font-weight: 700;
            border-bottom: 3px solid #667eea;
            padding-bottom: 0.5rem;
        }

        .project-content h3 {
            color: #374151;
            margin: 2rem 0 1rem 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .project-content p {
            margin-bottom: 1.5rem;
            text-align: justify;
        }

        .project-content ul, .project-content ol {
            margin: 1.5rem 0;
            padding-left: 2rem;
        }

        .project-content li {
            margin-bottom: 0.75rem;
            line-height: 1.6;
        }

        .project-content strong {
            color: #2c3e50;
            font-weight: 700;
        }

        .project-content em {
            color: #6b7280;
            font-style: italic;
        }

        .project-content blockquote {
            border-left: 4px solid #667eea;
            padding: 1rem 1.5rem;
            margin: 2rem 0;
            background: #f8f9ff;
            border-radius: 0 8px 8px 0;
            font-style: italic;
        }

        .project-actions {
            text-align: center;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid #f1f3f4;
        }

        .project-link {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            padding: 1rem 2rem;
            border: 2px solid #667eea;
            border-radius: 12px;
            background: white;
        }

        .project-link:hover {
            background: #667eea;
            color: white;
            gap: 1rem;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .related-projects {
            margin-top: 5rem;
        }

        .related-title {
            color: #2c3e50;
            margin-bottom: 2rem;
            font-size: 2.2rem;
            font-weight: 700;
            text-align: center;
            position: relative;
        }

        .related-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 1rem auto;
            border-radius: 2px;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2.5rem;
            margin-top: 2rem;
        }

        .portfolio-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            color: #333;
            border: 1px solid #f0f0f0;
        }

        .portfolio-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }

        .portfolio-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .portfolio-content-inner {
            padding: 2rem;
        }

        .portfolio-category {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .portfolio-title-card {
            font-size: 1.4rem;
            margin-bottom: 1rem;
            color: #2c3e50;
            font-weight: 700;
            line-height: 1.3;
        }

        .portfolio-description {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .portfolio-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            padding: 0.75rem 1.5rem;
            border: 2px solid #667eea;
            border-radius: 10px;
        }

        .portfolio-link:hover {
            background: #667eea;
            color: white;
            gap: 0.75rem;
        }

        .no-image-detail {
            width: 100%;
            height: 450px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            flex-direction: column;
            gap: 1rem;
        }

        .no-image {
            width: 100%;
            height: 220px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            flex-direction: column;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .project-title {
                font-size: 2.2rem;
            }
            
            .project-subtitle {
                font-size: 1.1rem;
            }
            
            .project-image {
                height: 300px;
            }
            
            .project-info {
                padding: 2rem;
            }
            
            .project-meta {
                flex-direction: column;
                gap: 1rem;
            }
            
            .portfolio-header {
                padding: 40px 0 30px;
            }
            
            .portfolio-content {
                padding: 20px 15px;
            }

            .related-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .tech-badges {
                padding: 1rem;
            }

            .project-content {
                font-size: 1.05rem;
            }
        }

        @media (max-width: 480px) {
            .project-title {
                font-size: 1.8rem;
            }
            
            .project-info {
                padding: 1.5rem;
            }
            
            .btn-voltar {
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="portfolio-detail">
        <!-- Header -->
        <header class="portfolio-header">
            <div class="header-container">
                <a href="index.php#portfolio" class="btn-voltar">
                    <i class="fas fa-arrow-left"></i>
                    Voltar ao Portfólio
                </a>
                <h1 class="project-title"><?= htmlspecialchars($projeto['titulo']) ?></h1>
                <p class="project-subtitle"><?= htmlspecialchars($projeto['resumo']) ?></p>
            </div>
        </header>

        <!-- Conteúdo do Projeto -->
        <div class="portfolio-content">
            <div class="project-card">
                <?php if($projeto['imagem'] && file_exists($root_path . '/assets/images/portfolio/' . $projeto['imagem'])): ?>
                    <img src="assets/images/portfolio/<?= $projeto['imagem'] ?>" 
                         alt="<?= htmlspecialchars($projeto['titulo']) ?>" 
                         class="project-image">
                <?php else: ?>
                    <div class="no-image-detail">
                        <i class="fas fa-image" style="font-size: 4rem;"></i>
                        <span style="font-size: 1.2rem;">Imagem não disponível</span>
                    </div>
                <?php endif; ?>
                
                <div class="project-info">
                    <div class="project-meta">
                        <div class="meta-item">
                            <i class="fas fa-folder"></i>
                            <span><?= htmlspecialchars($projeto['categoria']) ?></span>
                        </div>
                        <?php if($projeto['tecnologias']): ?>
                        <div class="meta-item">
                            <i class="fas fa-code"></i>
                            <span>Tecnologias Utilizadas</span>
                        </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <i class="fas fa-calendar"></i>
                            <span><?= date('d/m/Y', strtotime($projeto['data_criacao'])) ?></span>
                        </div>
                        <?php if($projeto['destaque']): ?>
                        <div class="meta-item">
                            <i class="fas fa-star"></i>
                            <span>Projeto em Destaque</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if($projeto['tecnologias']): ?>
                    <div class="tech-badges">
                        <?php 
                        $techs = explode(',', $projeto['tecnologias']);
                        foreach($techs as $tech): 
                            if(trim($tech)): 
                        ?>
                            <span class="tech-badge"><?= htmlspecialchars(trim($tech)) ?></span>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="project-content">
    <?php
    // Processar o conteúdo para formatação de livro
    $conteudo = htmlspecialchars($projeto['conteudo_completo']);
    
    // Dividir o conteúdo em parágrafos baseados em pontos finais seguidos de espaço e letra maiúscula
    $paragrafos = preg_split('/(?<=\.)\s+(?=[A-ZÀ-Ú])/', $conteudo);
    
    // Remover parágrafos vazios e aplicar formatação
    $conteudo_formatado = '';
    foreach ($paragrafos as $paragrafo) {
        $paragrafo = trim($paragrafo);
        if (!empty($paragrafo)) {
            // Garantir que o parágrafo termine com ponto final
            if (substr($paragrafo, -1) !== '.') {
                $paragrafo .= '.';
            }
            $conteudo_formatado .= '<p class="paragraph">' . $paragrafo . '</p>';
        }
    }
    
    echo $conteudo_formatado;
    ?>
</div>

                    <?php if($projeto['link_projeto']): ?>
                    <div class="project-actions">
                        <a href="<?= htmlspecialchars($projeto['link_projeto']) ?>" target="_blank" class="project-link">
                            <i class="fas fa-external-link-alt"></i>
                            Acessar Projeto Online
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Projetos Relacionados -->
            <?php if(!empty($projetos_relacionados)): ?>
            <div class="related-projects">
                <h2 class="related-title">Projetos Relacionados</h2>
                <div class="related-grid">
                    <?php foreach($projetos_relacionados as $relacionado): ?>
                    <div class="portfolio-card">
                        <?php if($relacionado['imagem'] && file_exists($root_path . '/assets/images/portfolio/' . $relacionado['imagem'])): ?>
                            <img src="assets/images/portfolio/<?= $relacionado['imagem'] ?>" 
                                 alt="<?= htmlspecialchars($relacionado['titulo']) ?>" 
                                 class="portfolio-image">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-image" style="font-size: 2rem;"></i>
                                <span>Sem imagem</span>
                            </div>
                        <?php endif; ?>
                        <div class="portfolio-content-inner">
                            <span class="portfolio-category"><?= htmlspecialchars($relacionado['categoria']) ?></span>
                            <h3 class="portfolio-title-card"><?= htmlspecialchars($relacionado['titulo']) ?></h3>
                            <p class="portfolio-description"><?= htmlspecialchars($relacionado['resumo']) ?></p>
                            <a href="portfolio-item.php?slug=<?= $relacionado['slug'] ?>" class="portfolio-link">
                                Ver Detalhes 
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>