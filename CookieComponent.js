/* Cookie Component V1
Use with 
<head>
<script type="module" src="cookie-widget.js"></script>
</head>
<body>
<cookie-banner></cookie-banner>
</body>
*/ 

class CookieBanner extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this.consent = null;
    }

    connectedCallback() {
        this.render();
        this.init();
    }

    render() {
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    --background-color: #f1f1f1;
                    --text-color: #000000;
                    --titre-color: grey;
                    --primary-color: #ab9ff2;
                    --border-color: #dcdcdc;
                    --shadow-color: rgba(0, 0, 0, 0.1);
                    --input-background: #f9f9f9;
                    --secondary-color: #2575fc;
                    --success-color: #60d394;
                    --error-color: #ee6055;
                    --jaune-color: #ffd97d;
                    display: block;
                }

                #cookie-banner {
                    position: fixed;
                    bottom: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 90%;
                    max-width: 500px;
                    background: white;
                    color: var(--text-color);
                    padding: 25px;
                    border-radius: 16px;
                    box-shadow: 0 8px 32px var(--shadow-color);
                    border: 1px solid var(--border-color);
                    z-index: 10000;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                    animation: slideUp 0.5s ease-out;
                }

                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translateX(-50%) translateY(20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(-50%) translateY(0);
                    }
                }

                .cookie-header {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    margin-bottom: 15px;
                }

                .cookie-icon {
                    width: 32px;
                    height: 32px;
                    background: var(--primary-color);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 16px;
                }

                .cookie-title {
                    font-weight: 600;
                    color: var(--text-color);
                    margin: 0;
                    font-size: 18px;
                }

                .cookie-description {
                    color: var(--titre-color);
                    line-height: 1.5;
                    margin-bottom: 20px;
                    font-size: 14px;
                }

                .cookie-buttons {
                    display: flex;
                    gap: 10px;
                    justify-content: flex-end;
                    flex-wrap: wrap;
                }

                .cookie-btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 500;
                    font-size: 14px;
                    transition: all 0.3s ease;
                    min-width: 120px;
                }

                .cookie-btn:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 4px 12px var(--shadow-color);
                }

                .accept-necessary {
                    background: var(--input-background);
                    color: var(--text-color);
                    border: 1px solid var(--border-color);
                }

                .accept-necessary:hover {
                    background: var(--border-color);
                }

                .accept-all {
                    background: var(--primary-color);
                    color: white;
                }

                .accept-all:hover {
                    background: #9a8de0;
                    box-shadow: 0 4px 12px rgba(171, 159, 242, 0.3);
                }

                .reject-all {
                    background: var(--error-color);
                    color: white;
                }

                .reject-all:hover {
                    background: #e0554a;
                }

                .cookie-footer {
                    margin-top: 15px;
                    font-size: 12px;
                    color: var(--titre-color);
                    text-align: center;
                }

                .cookie-footer a {
                    color: var(--primary-color);
                    text-decoration: none;
                }

                .cookie-footer a:hover {
                    text-decoration: underline;
                }

                @media (max-width: 768px) {
                    #cookie-banner {
                        bottom: 10px;
                        width: 95%;
                        padding: 20px;
                    }
                    
                    .cookie-buttons {
                        flex-direction: column;
                    }
                    
                    .cookie-btn {
                        min-width: auto;
                        width: 100%;
                    }
                }

                
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                
                @keyframes fadeOut {
                    from { opacity: 1; }
                    to { opacity: 0; }
                }
                
                @keyframes slideDown {
                    from { 
                        opacity: 1;
                        transform: translateX(-50%) translateY(0);
                    }
                    to { 
                        opacity: 0;
                        transform: translateX(-50%) translateY(20px);
                    }
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

                #cookie-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 9999;
                    animation: fadeIn 0.3s ease;
                }
            </style>

            <div id="cookie-banner" style="display: none;">
                <div class="cookie-header">
                    <div class="cookie-icon">🍪</div>
                    <h3 class="cookie-title">Vie privée et cookies</h3>
                </div>
                
                <p class="cookie-description">
                    Nous utilisons des cookies essentiels et des outils d'analyse pour comprendre comment vous utilisez notre site. 
                    Cela nous aide à améliorer votre expérience de navigation.
                </p>
                
                <div class="cookie-buttons">
                    <button class="cookie-btn reject-all" data-action="none">
                        Tout refuser
                    </button>
                    <button class="cookie-btn accept-necessary" data-action="necessary">
                        Essentiels seulement
                    </button>
                    <button class="cookie-btn accept-all" data-action="all">
                        Tout accepter
                    </button>
                </div>
                
                <div class="cookie-footer">
                    <a href="/privacy" target="_blank">Politique de confidentialité</a> • 
                    <a href="/cookies" target="_blank">Gérer les cookies</a>
                </div>
            </div>
        `;
    }

    init() {
        this.loadConsent();
        
        if (!this.consent) {
            setTimeout(() => {
                this.showBanner();
            }, 1500);
        } else {
            this.loadScripts();
        }

        // Écouteurs d'événements pour les boutons
        this.shadowRoot.querySelectorAll('.cookie-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = e.target.getAttribute('data-action');
                this.acceptCookies(action);
            });
        });
    }

    loadConsent() {
        this.consent = JSON.parse(localStorage.getItem('cookieConsent')) || null;
    }

    showBanner() {
        const banner = this.shadowRoot.getElementById('cookie-banner');
        banner.style.display = 'block';
        this.createOverlay();
    }

    createOverlay() {
        const overlay = document.createElement('div');
        overlay.id = 'cookie-overlay';
        document.body.appendChild(overlay);
    }

    removeOverlay() {
        const overlay = document.getElementById('cookie-overlay');
        if (overlay) {
            overlay.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => overlay.remove(), 300);
        }
    }

    acceptCookies(level) {
        this.consent = {
            level: level,
            date: new Date().toISOString(),
            necessary: true,
            analytics: level === 'all',
            marketing: level === 'all'
        };
        
        localStorage.setItem('cookieConsent', JSON.stringify(this.consent));
        this.animateClose();
        this.loadScripts();
        this.showConfirmation(level);
    }

    animateClose() {
        const banner = this.shadowRoot.getElementById('cookie-banner');
        banner.style.animation = 'slideDown 0.3s ease';
        setTimeout(() => {
            banner.style.display = 'none';
            this.removeOverlay();
        }, 300);
    }

    loadScripts() {
        if (!this.consent) return;
        
        if (this.consent.analytics) {
            this.loadGA4();
            this.loadHotjar();
        }
    }

    showConfirmation(level) {
        const message = level === 'all' ? 'Préférences enregistrées ✅' : 
                       level === 'necessary' ? 'Cookies essentiels activés ⚙️' : 
                       'Préférences sauvegardées 🔒';
        
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    loadGA4() {
        console.log('🚀 Chargement de Google Analytics...');
        // Votre code GA4 ici
    }

    loadHotjar() {
        console.log('📊 Chargement de Hotjar...');
        // Votre code Hotjar ici
    }
}

// Enregistrement du custom element
customElements.define('cookie-banner', CookieBanner);
