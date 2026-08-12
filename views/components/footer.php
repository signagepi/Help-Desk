<?php
// Determinar o caminho base baseado na localização atual
$current_dir = __DIR__; // Pasta onde este arquivo está (views/components/)

// Se estamos em views/components/, o cookie-banner.php está na mesma pasta
$cookie_banner_path = $current_dir . '/cookie-banner.php';
?>

<!-- Footer Universal -->
<footer class="<?= isset($admin) ? 'admin-footer' : 'cliente-footer' ?>">
    <div class="footer-content">
        <div class="footer-cookie-free">
            &copy; <?= date('Y') ?> Infoservice. Todos os direitos reservados - Fabio Oliveira Web Master.
        </div>
    </div>
</footer>

<!-- Banner de Cookies -->
<?php 
if (file_exists($cookie_banner_path)) {
    include $cookie_banner_path;
} else {
    // Fallback: tentar caminhos alternativos
    $alternative_paths = [
        __DIR__ . '/cookie-banner.php',
        dirname(__DIR__) . '/components/cookie-banner.php',
        'components/cookie-banner.php'
    ];
    
    $found = false;
    foreach ($alternative_paths as $path) {
        if (file_exists($path)) {
            include $path;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        error_log("Cookie banner não encontrado. Procurou em: " . implode(', ', $alternative_paths));
    }
}
?>