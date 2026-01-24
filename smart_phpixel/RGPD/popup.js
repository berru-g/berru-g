// Fonction pour copier le code
function copyCode(button) {
    const codeBlock = button.closest('.code-container').querySelector('code');
    const textArea = document.createElement('textarea');
    textArea.value = codeBlock.textContent;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);

    // Animation de confirmation
    const originalText = button.textContent;
    button.textContent = '✓ Copié!';
    button.style.backgroundColor = 'var(--accent-green)';
    setTimeout(() => {
        button.textContent = originalText;
        button.style.backgroundColor = '';
    }, 2000);
}

// Génération d'image de tracking démo
function generateTrackingImage() {
    const campaign = document.getElementById('campaignName').value;
    const source = document.getElementById('trafficSource').value;
    const partner = document.getElementById('partnerName').value;

    // Simulation d'URL de tracking (dans la réalité, c'est votre endpoint PHP)
    const imageUrl = `https://ton-site.com/smart_phpixel/pixel.php?${encodeURIComponent(campaign)}%0ASource:+${encodeURIComponent(source)}%0APartenaire:+${encodeURIComponent(partner)}`;

    // Mise à jour de l'aperçu
    document.getElementById('trackingPreview').src = imageUrl;

    // Génération du code HTML
    const htmlCode = `<img src="${imageUrl}" width="1" height="1" style="display:none;" alt="Smart Pixel Tracking">`;
    document.getElementById('htmlCode').textContent = htmlCode;

    // Affichage de la section
    document.getElementById('previewSection').style.display = 'block';

    // Scroll vers la prévisualisation
    document.getElementById('previewSection').scrollIntoView({ behavior: 'smooth' });
}

// Navigation fluide
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;

        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            const headerHeight = document.querySelector('.header').offsetHeight;
            const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - headerHeight;

            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });

            // Mise à jour du menu actif
            document.querySelectorAll('.sidebar-links a').forEach(link => {
                link.classList.remove('active');
            });
            this.classList.add('active');
        }
    });
});

// Gestionnaire de consentement RGPD (identique à ton code)
const CookieManager = {
    init() {
        if (!this.getConsent()) {
            this.showBanner();
        } else {
            this.loadScripts();
        }
    },

    showBanner() {
        setTimeout(() => {
            const banner = document.getElementById('cookie-banner');
            banner.style.display = 'block';
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
            this.showConfirmation(consent.level);
        }
    },

    showConfirmation(level) {
        const message = level === 'all' ? 'Préférences enregistrées ✅' :
            level === 'necessary' ? 'Navigation privée activée 😷' :
                'Préférences sauvegardées 🔒';

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
    }
};

// Exposition globale
window.acceptCookies = CookieManager.acceptCookies.bind(CookieManager);

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', function () {
    CookieManager.init();
    // Génère une image par défaut
    generateTrackingImage();
});