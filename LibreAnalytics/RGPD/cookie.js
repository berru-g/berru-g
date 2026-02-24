// Gestionnaire de consentement RGPD avec votre style
setTimeout(() => {

    const CookieManager = {
        init() {
            // Vérifier si le consentement existe déjà
            if (!this.getConsent()) {
                this.showBanner();
            } else {
                this.loadScripts();
            }
        },

        showBanner() {
            // Afficher après un délai pour ne pas gêner immédiatement
            setTimeout(() => {
                const banner = document.getElementById('cookie-banner');
                banner.style.display = 'block';

                // Ajouter une overlay pour plus d'attention
                this.createOverlay();
            }, 1500);
        },

        createOverlay() {
            const overlay = document.createElement('div');
            overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        `;
            overlay.id = 'cookie-overlay';
            document.body.appendChild(overlay);

            // Fermer en cliquant sur l'overlay
            overlay.addEventListener('click', () => {
                // Empêcher la fermeture au clic sur l'overlay
                // Forcer l'utilisateur à faire un choix explicite
            });
        },

        removeOverlay() {
            const overlay = document.getElementById('cookie-overlay');
            if (overlay) {
                overlay.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => overlay.remove(), 300);
            }
        },

        acceptCookies(level) {
            const consent = {
                level: level,
                date: new Date().toISOString(),
                necessary: true,
                analytics: level === 'all',
                marketing: level === 'all'
            };

            localStorage.setItem('cookieConsent', JSON.stringify(consent));
            this.animateClose();
            this.loadScripts();
        },

        animateClose() {
            const banner = document.getElementById('cookie-banner');
            banner.style.animation = 'slideDown 0.3s ease';
            setTimeout(() => {
                banner.style.display = 'none';
                this.removeOverlay();
            }, 300);
        },

        getConsent() {
            return JSON.parse(localStorage.getItem('cookieConsent'));
        },

        loadScripts() {
            const consent = this.getConsent();
            if (!consent) return;

            if (consent.analytics) {
                this.loadGA4();
                this.loadHotjar();
            }

            // Animation de confirmation discrète
            this.showConfirmation(consent.level);
        },

        showConfirmation(level) {
            const message = level === 'all' ? 'Préférences enregistrées ✅' :
                level === 'necessary' ? 'Naviguation privé activé 😷' :
                    'Préférences sauvegardées 🔒';

            // Petite notification discrète
            const notification = document.createElement('div');
            notification.textContent = message;
            notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success-color);
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
        },

        loadGA4() {
            console.log('🚀 NON Chargement de Google Analytics...');
            // GA déguage

        },

        loadHotjar() {
            console.log('📊 Chargement de Smart_phpixel');
            // Hotjar aussi
            //smart_phpixel.js
        }
    };

    // Ajouter les animations CSS supplémentaires
    const style = document.createElement('style');
    style.textContent = `
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
`;
    document.head.appendChild(style);

    // Exposer la fonction globalement
    window.acceptCookies = CookieManager.acceptCookies.bind(CookieManager);

    // Démarrer au chargement de la page
    document.addEventListener('DOMContentLoaded', function () {
        CookieManager.init();
    });

}, 5000);