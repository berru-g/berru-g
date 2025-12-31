// smart-cookie-component.js
// Composant autonome Smart Pixel + Cookie Manager
// Usage : <script src="smart-cookie-component.js" data-site-id="ton-site"></script>

class SmartCookieComponent {
    constructor(config = {}) {
        this.siteId = config.siteId || 'default';
        this.pixelEndpoint = config.pixelEndpoint || './smart_phpixel/pixel.php';
        this.debug = config.debug || false;
        
        this.init();
    }

    init() {
        // 1. Injecter le CSS du banner
        this.injectStyles();
        
        // 2. Injecter le HTML du banner
        this.injectBannerHTML();
        
        // 3. Initialiser le gestionnaire
        this.initCookieManager();
        
        // 4. Injecter le pixel si consenti
        this.loadPixelIfConsented();
    }

    injectStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .smart-cookie-banner {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                width: 90%;
                max-width: 500px;
                background: #fff;
                color: #000;
                padding: 25px;
                border-radius: 16px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                border: 1px solid #dcdcdc;
                z-index: 10000;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                animation: smartCookieSlideUp 0.5s ease-out;
                display: none;
            }

            @keyframes smartCookieSlideUp {
                from { opacity: 0; transform: translateX(-50%) translateY(20px); }
                to { opacity: 1; transform: translateX(-50%) translateY(0); }
            }

            .smart-cookie-header {
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 15px;
            }

            .smart-cookie-icon {
                width: 32px;
                height: 32px;
                background: #ab9ff2;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 16px;
            }

            .smart-cookie-title {
                font-weight: 600;
                color: #000;
                margin: 0;
            }

            .smart-cookie-description {
                color: grey;
                line-height: 1.5;
                margin-bottom: 20px;
                font-size: 14px;
            }

            .smart-cookie-buttons {
                display: flex;
                gap: 10px;
                justify-content: center;
                flex-wrap: wrap;
            }

            .smart-cookie-btn {
                padding: 10px 20px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 500;
                font-size: 14px;
                transition: all 0.3s ease;
                min-width: 120px;
            }

            .smart-cookie-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .smart-cookie-necessary {
                background: #f9f9f9;
                color: #000;
                border: 1px solid #dcdcdc;
            }

            .smart-cookie-necessary:hover {
                background: #dcdcdc;
            }

            .smart-cookie-accept {
                background: #ab9ff2;
                color: #fff;
            }

            .smart-cookie-accept:hover {
                background: #9a8de0;
                box-shadow: 0 4px 12px rgba(171, 159, 242, 0.3);
            }

            .smart-cookie-footer {
                margin-top: 15px;
                font-size: 12px;
                color: grey;
                text-align: center;
            }

            .smart-cookie-footer a {
                color: #ab9ff2;
                text-decoration: none;
            }

            .smart-cookie-footer a:hover {
                text-decoration: underline;
            }

            .smart-cookie-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9999;
                animation: smartCookieFadeIn 0.3s ease;
            }

            @keyframes smartCookieFadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            @media (max-width: 768px) {
                .smart-cookie-banner {
                    bottom: 10px;
                    width: 95%;
                    padding: 20px;
                }
                .smart-cookie-buttons {
                    flex-direction: column;
                }
                .smart-cookie-btn {
                    width: 100%;
                }
            }
        `;
        document.head.appendChild(style);
    }

    injectBannerHTML() {
        const bannerHTML = `
            <div class="smart-cookie-banner" id="smart-cookie-banner">
                <div class="smart-cookie-header">
                    <div class="smart-cookie-icon">🛡️</div>
                    <h3 class="smart-cookie-title">Transparence totale sur vos données</h3>
                </div>
                <p class="smart-cookie-description">
                    <strong>Ici, aucun de vos clics n'est vendu à Google ou Facebook.</strong><br>
                    J'utilise <strong>Smart Pixel</strong>, mon propre système d'analyse développé avec éthique.
                    <br><br>
                    En autorisant l'analyse, vous m'aidez à améliorer ce site <strong>sans enrichir les GAFAM</strong>.
                    C'est ma manière de construire un web plus souverain.
                </p>
                <div class="smart-cookie-buttons">
                    <button class="smart-cookie-btn smart-cookie-necessary" id="smart-cookie-necessary">
                        Non merci
                    </button>
                    <button class="smart-cookie-btn smart-cookie-accept" id="smart-cookie-accept">
                        Ok pour moi
                    </button>
                </div>
                <div class="smart-cookie-footer">
                    <a href="https://github.com/berru-g/berru-g/tree/main/smart_phpixel" target="_blank">
                        Voir le code de Smart Pixel
                    </a>
                </div>
            </div>
        `;
        
        const div = document.createElement('div');
        div.innerHTML = bannerHTML;
        document.body.appendChild(div.firstElementChild);
        
        // Ajouter les écouteurs d'événements
        document.getElementById('smart-cookie-necessary').addEventListener('click', () => this.acceptCookies('necessary'));
        document.getElementById('smart-cookie-accept').addEventListener('click', () => this.acceptCookies('all'));
    }

    getConsent() {
        return JSON.parse(localStorage.getItem('smart_cookie_consent'));
    }

    setConsent(level) {
        const consent = {
            level: level,
            date: new Date().toISOString(),
            siteId: this.siteId,
            analytics: level === 'all'
        };
        localStorage.setItem('smart_cookie_consent', JSON.stringify(consent));
    }

    acceptCookies(level) {
        this.setConsent(level);
        this.hideBanner();
        
        if (level === 'all') {
            this.injectPixel();
            this.showNotification('Analytics souverain activé 🛡️');
        } else {
            this.showNotification('Navigation privée activée 🔒');
        }
    }

    hideBanner() {
        const banner = document.getElementById('smart-cookie-banner');
        const overlay = document.getElementById('smart-cookie-overlay');
        
        if (banner) banner.style.display = 'none';
        if (overlay) overlay.remove();
    }

    showNotification(message) {
        const notification = document.createElement('div');
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #60d394;
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 10001;
            animation: slideInRight 0.3s ease;
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    initCookieManager() {
        const consent = this.getConsent();
        if (consent && consent.siteId === this.siteId) {
            // Consentement déjà donné pour ce site
            if (consent.analytics) {
                this.injectPixel();
            }
        } else {
            // Afficher le banner après un délai
            setTimeout(() => this.showBanner(), 1500);
        }
    }

    showBanner() {
        const banner = document.getElementById('smart-cookie-banner');
        if (!banner) return;
        
        // Créer l'overlay
        const overlay = document.createElement('div');
        overlay.className = 'smart-cookie-overlay';
        overlay.id = 'smart-cookie-overlay';
        document.body.appendChild(overlay);
        
        // Afficher le banner
        banner.style.display = 'block';
        
        // Empêcher la fermeture par clic sur l'overlay
        overlay.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }

    injectPixel() {
        // Injection du pixel de tracking
        const pixelScript = document.createElement('script');
        pixelScript.src = './smart_phpixel/smart-pixel.js'; // Chemin à adapter
        pixelScript.async = true;
        
        pixelScript.onload = () => {
            if (this.debug) console.log('✅ Smart Pixel chargé');
            
            // Pixel invisible pour le tracking
            const pixelImg = document.createElement('img');
            pixelImg.src = `${this.pixelEndpoint}?source=${this.siteId}&action=pageview`;
            pixelImg.width = 1;
            pixelImg.height = 1;
            pixelImg.style.display = 'none';
            document.body.appendChild(pixelImg);
        };
        
        document.head.appendChild(pixelScript);
    }

    loadPixelIfConsented() {
        const consent = this.getConsent();
        if (consent && consent.analytics && consent.siteId === this.siteId) {
            this.injectPixel();
        }
    }
}

// Initialisation automatique si script inclus sans config
document.addEventListener('DOMContentLoaded', () => {
    // Récupérer la config depuis les data-attributs du script
    const currentScript = document.currentScript;
    if (currentScript) {
        const siteId = currentScript.getAttribute('data-site-id') || 'default';
        const debug = currentScript.getAttribute('data-debug') === 'true';
        
        // Initialiser le composant
        window.SmartCookie = new SmartCookieComponent({
            siteId: siteId,
            debug: debug
        });
    }
});