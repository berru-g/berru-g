// intro-tour.js - Tutoriel d'utilisation
(function () {
    'use strict';

    // Vérifier si l'utilisateur a déjà vu le tutoriel
    const hasSeenTutorial = localStorage.getItem('berrug_tutorial_seen');

    // Attendre que tout soit chargé
    window.addEventListener('DOMContentLoaded', function () {
        // Délai pour que l'utilisateur voie d'abord la page
        setTimeout(function () {
            if (!hasSeenTutorial) {
                startTutorial();
            } else {
                // Optionnel : Afficher un message de bienvenue discret
                showWelcomeBack();
            }
        }, 1500);
    });

    function startTutorial() {
        const tour = introJs();

        tour.setOptions({
            steps: [
                {
                    element: '#searchInput',
                    intro: `
                        <div style="text-align: center; margin-bottom: 10px;">
                            <span style="font-size: 2em;">🔍</span>
                        </div>
                        <h3>Bienvenue</h3>
                        <p>Plutôt que de vous présenter mes compétences, je vous propose de chercher des mots clef dans ma base de projets.</p>
                        <p><small>Essayez "3D", "dashboard", "arduino" etc.</small></p>
                        
                    `,
                    position: 'bottom'
                },
                {
                    element: document.querySelector('.nav-parent:first-child .nav-toggle'),
                    intro: `
                        <div style="text-align: center; margin-bottom: 10px;">
                            <span style="font-size: 2em;">📂</span>
                        </div>
                        <h3>Vos recherches s'affichent ici !</h3>
                        <p>Commencez par écrire,<strong>'bienvenue'</strong>, dans la barre de recherche ou <a href="./skill/index.html" style="text-decoration: none; color: #9d86ff;" target="blank">aller directement au site</a>.</p>
                    `,
                    position: 'right'
                }
            ],
            nextLabel: '▷',
            prevLabel: '◁',
            skipLabel: '×',
            doneLabel: 'Commencer',
            showProgress: true,
            showBullets: true,
            showStepNumbers: false,
            keyboardNavigation: true,
            overlayOpacity: 0.5,
            tooltipPosition: 'auto',
            helperElementPadding: 10,
            exitOnOverlayClick: true,
            exitOnEsc: true
        });

        // Style personnalisé pour le tutoriel
        tour.onbeforechange(function () {
            // S'assurer que le sidebar est ouvert sur mobile
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar')?.classList.add('open');
            }
        });

        // Quand le tutoriel est terminé
        tour.oncomplete(function () {
            localStorage.setItem('berrug_tutorial_seen', 'true');
            showCompletionMessage();
            sounds.alert();
        });

        // Si l'utilisateur skip
        tour.onexit(function () {
            localStorage.setItem('berrug_tutorial_seen', 'true');
        });

        // Démarrer
        setTimeout(function () {
            tour.start();
        }, 500);
    }

    function showWelcomeBack() {
        // Message discret de bienvenue
        const welcome = document.createElement('div');
        welcome.className = 'welcome-notification';
        welcome.innerHTML = `
            <style>
                .welcome-notification {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: #151515;
                    border: 1px solid rgba(255, 255, 255, 0.15);
                    border-radius: 12px;
                    padding: 1rem 1.5rem;
                    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
                    z-index: 9999;
                    animation: slideInUp 0.5s ease;
                    max-width: 300px;
                }
                @keyframes slideInUp {
                    from { transform: translateY(100%); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                .welcome-content {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .welcome-icon {
                    font-size: 1.5em;
                }
                .welcome-text {
                    font-size: 0.9rem;
                    color: var(--text-color);
                }
                .welcome-close {
                    background: transparent;
                    border: none;
                    color: var(--text-secondary);
                    cursor: pointer;
                    padding: 0;
                    margin-left: auto;
                }
            </style>
            <div class="welcome-content">
                <span class="welcome-icon">🟦</span>
                <span class="welcome-text">Bon retour ! </span>
                <button class="welcome-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        document.body.appendChild(welcome);

        // Auto-remove après 5 secondes
        setTimeout(() => {
            if (welcome.parentNode) {
                welcome.remove();
            }
        }, 5000);
    }

    function showCompletionMessage() {
        const completion = document.createElement('div');
        completion.className = 'completion-notification';
        completion.innerHTML = `
            <style>
                .completion-notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: var(--primary-color);
                    color: white;
                    padding: 1rem 1.5rem;
                    border-radius: 12px;
                    box-shadow: 0 8px 32px rgba(138, 111, 248, 0.4);
                    z-index: 9999;
                    animation: slideInRight 0.5s ease;
                    max-width: 300px;
                }
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            </style>
            <div style="text-align: center;">
                <div style="font-size: 2em; margin-bottom: 10px;"></div>
                <h3 style="margin: 0 0 10px 0;">API active</h3>
                <p style="margin: 0; opacity: 0.9;">b.skill()</p>
            </div>
        `;

        document.body.appendChild(completion);

        // Auto-remove après 4 secondes
        setTimeout(() => {
            if (completion.parentNode) {
                completion.style.opacity = '0';
                completion.style.transform = 'translateX(100%)';
                setTimeout(() => completion.remove(), 300);
            }
        }, 4000);
    }

    // Exposer une fonction pour redémarrer le tutoriel (pour debug ou bouton "aide")
    window.restartTutorial = function () {
        localStorage.removeItem('berrug_tutorial_seen');
        startTutorial();
    };

    // Ajouter un bouton d'aide dans le footer (optionnel)
    function addHelpButton() {
        const helpBtn = document.createElement('button');
        helpBtn.className = 'footer-btn';
        helpBtn.id = 'helpBtn';
        helpBtn.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        `;

        helpBtn.addEventListener('click', function () {
            restartTutorial();
        });

        const footer = document.querySelector('.sidebar-footer');
        if (footer) {
            // Insérer avant le bouton paramètres
            const settingsBtn = document.getElementById('settingsBtn');
            if (settingsBtn) {
                footer.insertBefore(helpBtn, settingsBtn);
            } else {
                footer.appendChild(helpBtn);
            }
        }
    }

    // Ajouter le bouton d'aide après chargement
    setTimeout(addHelpButton, 1000);
})();