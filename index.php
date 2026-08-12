<?php
require 'config/db.php';
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$portfolio = [];
try {
    $stmt = $pdo->query("SELECT * FROM portfolio WHERE ativo = 1 ORDER BY destaque DESC, data_criacao DESC LIMIT 6");
    $portfolio = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Erro ao buscar portfólio: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infoservice | Suporte técnico, tecnologia e helpdesk</title>
    <meta name="description" content="Infoservice: manutenção de computadores, redes, suporte técnico, sites, streaming e atendimento via central helpdesk.">
    <link rel="stylesheet" href="assets/css/helpdesk-pro.css">
    <style>
        .hero{padding:46px 0 28px}.hero-grid{display:grid;grid-template-columns:1.08fr .92fr;gap:28px;align-items:center}.hero h2{font-size:clamp(34px,5vw,60px);line-height:1.02;margin:0 0 16px}.hero p{font-size:18px;color:rgba(255,255,255,.82);line-height:1.6;max-width:650px}.hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}.hero-panel{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);border-radius:26px;padding:22px;backdrop-filter:blur(10px)}.hero-panel .hd-mini{background:rgba(255,255,255,.10);border-color:rgba(255,255,255,.16);color:#fff}.hero-panel .hd-mini p{color:rgba(255,255,255,.72);font-size:14px}.site-section{padding:52px 0}.service-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.service-card{padding:22px}.service-card .icon{font-size:34px;margin-bottom:12px}.portfolio-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.portfolio-card{overflow:hidden}.portfolio-card img{width:100%;height:170px;object-fit:cover;display:block}.portfolio-card .body{padding:18px}.portfolio-card h3,.service-card h3{margin:0 0 8px}.portfolio-card p,.service-card p{color:var(--hd-muted);line-height:1.55}.cta{background:linear-gradient(135deg,#0f172a,#1d4ed8);color:#fff;border-radius:28px;padding:34px;display:flex;justify-content:space-between;gap:18px;align-items:center}.footer{padding:28px 0;color:var(--hd-muted);text-align:center}@media(max-width:900px){.hero-grid,.service-grid,.portfolio-grid{grid-template-columns:1fr}.cta{align-items:flex-start;flex-direction:column}}
    </style>
</head>
<body>
<div class="hd-page">
    <header class="hd-topbar" style="padding-bottom:70px">
        <div class="hd-container hd-topbar-inner">
            <div class="hd-brand">
                <div class="hd-brand-icon">💻</div>
                <div>
                    <h1>Infoservice</h1>
                    <p>Serviços de tecnologia com central de atendimento profissional</p>
                </div>
            </div>
            <div class="hd-user">
                <a class="hd-chip" href="auth/login.php">Área do cliente</a>
                <a class="hd-chip" href="auth/login-admin.php">Painel técnico</a>
            </div>
        </div>
        <section class="hd-container hero">
            <div class="hero-grid">
                <div>
                    <h2>Suporte técnico com controle, prazo e atendimento de verdade.</h2>
                    <p>Abra chamados, acompanhe protocolos, receba observações técnicas e centralize seus serviços de manutenção, redes, informática e soluções digitais.</p>
                    <div class="hero-actions">
                        <a class="hd-btn hd-btn-primary" href="auth/login.php">🎧 Abrir chamado</a>
                        <a class="hd-btn hd-btn-light" href="#servicos">Ver serviços</a>
                    </div>
                </div>
                <div class="hero-panel">
                    <div class="hd-sidebar-list">
                        <div class="hd-mini"><strong>✅ Protocolo automático</strong><p>Cada solicitação recebe código para acompanhamento.</p></div>
                        <div class="hd-mini"><strong>⏱️ SLA por prioridade</strong><p>Chamados urgentes, altos, médios e baixos com prazo estimado.</p></div>
                        <div class="hd-mini"><strong>📊 Painel helpdesk</strong><p>Fila técnica, status, valores, observações e histórico.</p></div>
                    </div>
                </div>
            </div>
        </section>
    </header>

    <main class="hd-main" style="margin-top:-48px">
        <div class="hd-container">
            <section class="hd-kpis">
                <div class="hd-kpi"><span>Atendimento</span><strong>Portal</strong><small>Cliente acompanha online</small></div>
                <div class="hd-kpi"><span>Serviços</span><strong>TI</strong><small>Computadores, redes e sistemas</small></div>
                <div class="hd-kpi"><span>Gestão</span><strong>SLA</strong><small>Prioridade e prazo</small></div>
                <div class="hd-kpi"><span>Registro</span><strong>Histórico</strong><small>Observações e status</small></div>
            </section>
        </div>

        <section id="servicos" class="site-section">
            <div class="hd-container">
                <div class="hd-section-head"><h2>Serviços principais</h2></div>
                <div class="service-grid">
                    <article class="hd-card service-card"><div class="icon">🧰</div><h3>Manutenção e suporte</h3><p>Diagnóstico, limpeza, formatação, instalação de programas, otimização e correção de falhas.</p></article>
                    <article class="hd-card service-card"><div class="icon">📶</div><h3>Redes e internet</h3><p>Configuração de roteadores, cabeamento, Wi-Fi, impressoras em rede e organização técnica.</p></article>
                    <article class="hd-card service-card"><div class="icon">🌐</div><h3>Soluções digitais</h3><p>Sites, sistemas, landing pages, suporte para negócios locais e organização de serviços online.</p></article>
                </div>
            </div>
        </section>

        <?php if ($portfolio): ?>
        <section class="site-section" style="padding-top:0">
            <div class="hd-container">
                <div class="hd-section-head"><h2>Portfólio e soluções</h2></div>
                <div class="portfolio-grid">
                    <?php foreach ($portfolio as $item): ?>
                        <article class="hd-card portfolio-card">
                            <?php if (!empty($item['imagem'])): ?><img src="assets/images/portfolio/<?= h($item['imagem']) ?>" alt="<?= h($item['titulo']) ?>"><?php endif; ?>
                            <div class="body">
                                <h3><?= h($item['titulo']) ?></h3>
                                <p><?= h(mb_strimwidth($item['resumo'] ?: $item['descricao'], 0, 150, '...')) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section class="site-section" style="padding-top:0">
            <div class="hd-container">
                <div class="cta">
                    <div><h2 style="margin:0 0 8px">Precisa de atendimento?</h2><p style="margin:0;color:rgba(255,255,255,.78)">Entre na área do cliente e abra uma solicitação com protocolo.</p></div>
                    <a class="hd-btn hd-btn-primary" href="auth/login.php">Acessar central</a>
                </div>
            </div>
        </section>
    </main>
    <footer class="footer">Infoservice © <?= date('Y') ?> — Tecnologia, suporte e soluções digitais.</footer>
</div>
</body>
</html>
