<?php
// Versão sem banco de dados para evitar problemas de open_basedir
// Configurações fixas - o admin pode editar este arquivo manualmente
$cookieConfig = [
    'ativo' => true, // Mude para false para desativar
    'mensagem' => '🍪 Utilizamos cookies para melhorar sua experiência. Ao continuar navegando, você concorda com nossa Política de Cookies.',
    'politica_link' => '/politica-cookies.php'
];
?>

<?php if ($cookieConfig['ativo']): ?>
<!-- Sistema de Cookies -->
<div id="cookieBanner" class="cookie-banner" style="display: none;">
    <div class="cookie-content">
        <p class="cookie-message">
            <?= htmlspecialchars($cookieConfig['mensagem']) ?>
            <?php if ($cookieConfig['politica_link']): ?>
                <a href="<?= htmlspecialchars($cookieConfig['politica_link']) ?>" style="color: #20c997; text-decoration: underline;">Política de Cookies</a>.
            <?php endif; ?>
        </p>
        <div class="cookie-actions">
            <button class="cookie-btn decline" onclick="cookieManager.decline()">
                Recusar
            </button>
            <button class="cookie-btn accept" onclick="cookieManager.accept()">
                Aceitar
            </button>
        </div>
    </div>
</div>

<script>
// Sistema de Cookies Simplificado
class CookieManager {
    constructor() {
        this.cookieName = 'infoservice_cookies';
        this.cookieExpiry = 365;
        this.config = {
            ativo: <?= $cookieConfig['ativo'] ? 'true' : 'false' ?>,
            mensagem: `<?= addslashes($cookieConfig['mensagem']) ?>`,
            politicaLink: `<?= addslashes($cookieConfig['politica_link']) ?>`
        };
        this.init();
    }

    init() {
        if (!this.config.ativo) return;
        
        const consent = this.getConsent();
        
        if (consent === null) {
            this.showBanner();
        } else if (consent === 'accepted') {
            this.loadNecessaryCookies();
        }
    }

    showBanner() {
        const banner = document.getElementById('cookieBanner');
        setTimeout(() => {
            banner.style.display = 'block';
            setTimeout(() => banner.classList.add('show'), 100);
        }, 1000);
    }

    hideBanner() {
        const banner = document.getElementById('cookieBanner');
        banner.classList.remove('show');
        setTimeout(() => {
            banner.style.display = 'none';
        }, 500);
    }

    getConsent() {
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === this.cookieName) {
                return value;
            }
        }
        return null;
    }

    setConsent(consent) {
        const date = new Date();
        date.setTime(date.getTime() + (this.cookieExpiry * 24 * 60 * 60 * 1000));
        const expires = "expires=" + date.toUTCString();
        
        document.cookie = `${this.cookieName}=${consent}; ${expires}; path=/; SameSite=Lax`;
        
        this.hideBanner();
        
        if (consent === 'accepted') {
            this.loadNecessaryCookies();
            this.showToast('✅ Preferências salvas!', 'success');
        } else {
            this.removeOptionalCookies();
            this.showToast('🔒 Cookies opcionais bloqueados.', 'info');
        }
    }

    accept() {
        this.setConsent('accepted');
    }

    decline() {
        this.setConsent('declined');
    }

    loadNecessaryCookies() {
        console.log('🍪 Cookies necessários carregados');
        // Adicione aqui scripts de analytics se necessário
    }

    removeOptionalCookies() {
        // Remove cookies de analytics se existirem
        const optionalCookies = ['_ga', '_gid', '_gat'];
        optionalCookies.forEach(cookie => {
            document.cookie = `${cookie}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
        });
    }

    showToast(message, type) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : '#6c757d'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 1001;
            animation: slideInRight 0.3s ease;
        `;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Inicializar
const cookieManager = new CookieManager();

// CSS dinâmico
const cookieStyles = `
.cookie-banner {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    z-index: 1000;
    max-width: 90%;
    width: 500px;
    opacity: 0;
    transition: all 0.5s ease;
}

.cookie-banner.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

.cookie-content {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.cookie-message {
    margin: 0;
    font-size: 0.9rem;
    line-height: 1.4;
    text-align: center;
}

.cookie-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.cookie-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    flex: 1;
    min-width: 120px;
    text-align: center;
}

.cookie-btn.accept {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.cookie-btn.decline {
    background: transparent;
    color: #bdc3c7;
    border: 1px solid #bdc3c7;
}

.cookie-btn:hover {
    transform: translateY(-1px);
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideOutRight {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = cookieStyles;
document.head.appendChild(styleSheet);
</script>
<?php endif; ?>