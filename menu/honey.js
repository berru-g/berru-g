// ===== XSS DETECTION AVEC SON =====
function initXSSDetectorWithSound() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function(e) {
        const value = e.target.value.toLowerCase();
        
        // Détection des tentatives XSS basiques
        const xssPatterns = [
            '<script>', '</script>', 'javascript:', 
            'onclick=', 'onload=', 'onerror=', 'alert(',
            'eval(', 'document.cookie', 'localStorage'
        ];
        
        const isXSS = xssPatterns.some(pattern => value.includes(pattern));
        
        if (isXSS) {
            console.log('%c🚨 Tentative XSS détectée: ' + value.substring(0, 50), 
                       'color: #ef4444; font-weight: bold;');
            
            // 1. Jouer le son d'alerte
            sounds.error();
            
            // 2. Animation clignotante rouge sur TOUT le site
            triggerRedFlashAnimation();
            
            // 3. Afficher le projet "hack trap" s'il existe
            if (window.projectsDB) {
                const hackProject = window.projectsDB.find(p => 
                    p.id === 'hack' || p.id === 'hack-trap' || 
                    p.tags?.includes('<script>')
                );
                if (hackProject) {
                    showProjectPreview([{
                        type: 'project',
                        data: hackProject
                    }]);
                }
            }
            
            // 4. Effacer l'input après 1 seconde
            setTimeout(() => {
                searchInput.value = '';
                searchInput.blur();
            }, 1000);
            
            // 5. Optionnel: Vibration (si supporté)
            if (navigator.vibrate) {
                navigator.vibrate([200, 100, 200, 100, 200]);
            }
        }
    });
}

function playAlertSound(audioElement) {
    if (!audioElement) return;
    
    try {
        // Réinitialiser et jouer
        audioElement.currentTime = 0;
        audioElement.volume = 0.7; // Volume à 70%
        
        const playPromise = audioElement.play();
        
        // Gérer les erreurs de lecture automatique
        if (playPromise !== undefined) {
            playPromise.catch(error => {
                console.log('Lecture son bloquée, tentative manuelle nécessaire');
                // Option: afficher un message pour cliquer pour débloquer
            });
        }
    } catch (error) {
        console.warn('Erreur lecture son:', error);
    }
}

function triggerRedFlashAnimation() {
    // Créer un overlay rouge clignotant
    const overlay = document.createElement('div');
    overlay.id = 'xss-alert-overlay';
    overlay.innerHTML = `
        <style>
            #xss-alert-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(239, 68, 68, 0);
                z-index: 9998;
                pointer-events: none;
                animation: redFlash 0.5s ease 6 forwards; /* 3 secondes */
            }
            
            @keyframes redFlash {
                0% { opacity: 0; background-color: rgba(239, 68, 68, 0); }
                50% { opacity: 1; background-color: rgba(239, 68, 68, 0.25); }
                100% { opacity: 0; background-color: rgba(239, 68, 68, 0); }
            }
        </style>
    `;
    
    document.body.appendChild(overlay);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        if (overlay.parentNode) {
            overlay.remove();
        }
    }, 3000);
}

// Version avec bouton "débloquer audio" pour les navigateurs restrictifs
function addAudioUnlockButton() {
    // Certains navigateurs bloquent l'audio sans interaction utilisateur
    const audioUnlocker = document.createElement('button');
    audioUnlocker.id = 'audio-unlocker';
    audioUnlocker.innerHTML = '🔊 Activer les sons';
    audioUnlocker.style.cssText = `
        position: fixed;
        bottom: 70px;
        right: 20px;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 20px;
        padding: 8px 16px;
        font-size: 12px;
        cursor: pointer;
        z-index: 10000;
        opacity: 0.8;
        transition: opacity 0.3s;
    `;
    
    audioUnlocker.addEventListener('click', function() {
        // Jouer un son silencieux pour débloquer l'audio
        const silentAudio = new Audio('data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAZGF0YQQ=');
        silentAudio.volume = 0.001;
        silentAudio.play().then(() => {
            console.log('✅ Audio débloqué');
            this.style.opacity = '0';
            setTimeout(() => this.remove(), 500);
        });
    });
    
    document.body.appendChild(audioUnlocker);
    
    // Cacher après 10 secondes
    setTimeout(() => {
        if (audioUnlocker.parentNode) {
            audioUnlocker.style.opacity = '0';
            setTimeout(() => audioUnlocker.remove(), 500);
        }
    }, 10000);
}

// Initialiser au chargement
document.addEventListener('DOMContentLoaded', () => {
    // Ajouter le bouton de déblocage audio (optionnel)
    addAudioUnlockButton();
    
    // Démarrer la détection après 1 seconde
    setTimeout(initXSSDetectorWithSound, 1000);
});

// Option: commande console pour tester
window.testXSSAlert = function() {
    console.log('🔊 Test alerte XSS...');
    const audio = new Audio('./sounds/notification-error.mp3');
    audio.volume = 0.5;
    audio.play();
    triggerRedFlashAnimation();
};