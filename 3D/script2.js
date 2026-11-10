// 3D website V2 avec hero.glb + decor.glb + sphere.hdri + son.mp3 + modal (POI) + gamification
//gael-berru.com/3D/

// SYSTÈME AUDIO -
let audioContext;
let backgroundMusic;
let poiSounds = {};
let isSoundEnabled = true;
let lastUserAction = Date.now();
let idleTimer = null;

// INITIALISATION AUDIO 
function initAudio() {
    try {
        // Créer le contexte audio
        audioContext = new (window.AudioContext || window.webkitAudioContext)();

        // Charger les sons de fond (ambiance légère)
        loadBackgroundMusic();
        loadPOISounds();

        console.log('✅ Audio initialisé');
    } catch (error) {
        console.log('❌ Audio non supporté:', error);
        isSoundEnabled = false;
    }
}

function loadBackgroundMusic() {
    const audio = new Audio('../sounds/mixkit-relax-beat-292.mp3'); // mp3 ou .wav mais ne fonctionne pas sur firefox, prob url raw files ou conflit avec > oscillator.start(); ?
    audio.loop = true; // boucle
    audio.volume = 0.4; // volume (0 → 1)
    audio.play().catch(err => console.log("⚠️ Lecture auto bloquée:", err));

    backgroundMusic = audio; // garder la ref si besoin (pause/stop) en front
}

function loadPOISounds() {
    // Sons différents pour chaque type de POI
    poiSounds = {
        'intro': createBeepSound(523.25, 0.3),    // Do
        'wam': createBeepSound(698.46, 0.3),  // Fa
        'projects': createBeepSound(587.33, 0.3), // Ré
        'skills': createBeepSound(659.25, 0.3),   // Mi
        'contact': createBeepSound(698.46, 0.3),  // Fa
        'video-gallery': createBeepSound(783.99, 0.5), // Sol plus long
        'quest': createChimeSound(),              // Carillon spécial
        'portal': createPortalSound(),             // Son de portail
        'promo': createBeepSound(587.33, 0.3),  // Ré
        'social': createBeepSound(659.25, 0.3), // Mi
    };
}

function createBeepSound(frequency, duration) {
    return function () {
        if (!isSoundEnabled || !audioContext) return;

        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(frequency, audioContext.currentTime);

        gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + duration);

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.start();
        oscillator.stop(audioContext.currentTime + duration);
    };
}

function createChimeSound() {
    return function () {
        if (!isSoundEnabled || !audioContext) return;

        // Carillon avec plusieurs fréquences
        const frequencies = [523.25, 659.25, 783.99];
        frequencies.forEach((freq, index) => {
            setTimeout(() => {
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.type = 'triangle';
                oscillator.frequency.setValueAtTime(freq, audioContext.currentTime);

                gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.5);

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.start();
                oscillator.stop(audioContext.currentTime + 0.5);
            }, index * 200);
        });
    };
}

function createPortalSound() {
    return function () {
        if (!isSoundEnabled || !audioContext) return;

        // Son de portail style SF
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        const filter = audioContext.createBiquadFilter();

        oscillator.type = 'sawtooth';
        oscillator.frequency.setValueAtTime(100, audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(800, audioContext.currentTime + 1);

        filter.type = 'lowpass';
        filter.frequency.setValueAtTime(1000, audioContext.currentTime);

        gainNode.gain.setValueAtTime(0.05, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 1.5);

        oscillator.connect(filter);
        filter.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.start();
        oscillator.stop(audioContext.currentTime + 1.5);
    };
}

function playPOISound(poiId) {
    if (poiSounds[poiId]) {
        poiSounds[poiId]();
    }
}


// 3D 3D 3D 3D 3D 3D 3D
// Variables globales Début du jeu 3D avec threejs
let scene, camera, renderer, controls, aircraft;
let aircraftSpeed = 0, maxSpeed = 1.5, acceleration = 0.03;
let clock = new THREE.Clock();
let pointsOfInterest = [];
let activePoi = null;
let experienceStarted = true;
let currentCard = null;
let aircraftGLB = null; // variable pour le GLB
let environmentGLB = null; // NOUVEAU: variable pour l'environnement GLB
// gestion du click & drag
let isMouseDown = false;
let previousMousePosition = { x: 0, y: 0 };
let isFreeLookMode = false;

// MISSIONS GAMIFIÉES
let professionalMissions = [];
let completedMissions = [];
let currentMission = null;

// MISSIONS PAR POI
const missionSystem = {
    'wam': {
        title: "Découvrir mon profil",
        action: "Lire ma présentation",
        reward: "+20 💎",
        points: 20
    },
    'projects': {
        title: "Explorer mes projets",
        action: "Voir 3 projets minimum",
        reward: "+30 💎",
        points: 30
    },
    'skills': {
        title: "Lire les témoignages",
        action: "Découvrir les avis clients",
        reward: "+25 💎",
        points: 25
    },
    'contact': {
        title: "Mission spéciale : Contact",
        action: "Envoyer un message test",
        reward: "+50 💎 + 🎁 Bonus",
        points: 50,
        bonus: true
    }
};

function initMissions() {
    // Ajouter les missions au chargement
    professionalMissions = Object.values(missionSystem);
    updateMissionsHUD();
}

function completeMission(poiId) {
    const mission = missionSystem[poiId];
    if (mission && !completedMissions.includes(poiId)) {
        completedMissions.push(poiId);
        userPoints += mission.points;

        // Animation de récompense
        showMissionComplete(mission);

        // Bonus spécial pour le contact
        if (mission.bonus) {
            showSpecialOffer();
        }

        updatePointsHUD();
        updateMissionsHUD();
        saveGamificationState();
    }
}

function showMissionComplete(mission) {
    const popup = document.createElement('div');
    popup.innerHTML = `
        <div style="font-size:1.5em; font-weight:700; color:#fff; background:linear-gradient(135deg, #2575fc 0%, #ab9ff2 100%); border-radius:20px; padding:25px; text-align:center; animation:missionComplete 0.8s;">
            MISSION ACCOMPLIE !<br>
            <span style="font-size:0.8em; opacity:0.9;">${mission.title}</span><br>
            <div style="margin-top:15px; font-size:1.2em; color:#ffe953;">${mission.reward}</div>
        </div>
    `;
    popup.style.cssText = `
        position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%);
        z-index: 2000; animation: missionComplete 0.8s;
    `;
    document.body.appendChild(popup);

    setTimeout(() => popup.remove(), 5000);
}

// État des touches sur mobile
// MOBILE TOUCH CONTROLS
let touchControls = {
    forward: false,
    backward: false,
    left: false,
    right: false,
    up: false,
    down: false
};

let joystick = {
    active: false,
    startX: 0,
    startY: 0,
    currentX: 0,
    currentY: 0
};

// MODIFIER LES CONTRÔLES EXISTANTS POUR INCLURE LE TOUCH
const keys = {
    'ArrowUp': false, 'ArrowDown': false, 'ArrowLeft': false, 'ArrowRight': false,
    ' ': false, 's': false, 'S': false,
    // Ajouter les contrôles tactiles
    'forward': false, 'backward': false, 'left': false, 'right': false, 'up': false, 'down': false
};

function setupMobileControls() {
    // Créer l'interface de contrôle mobile
    createTouchInterface();

    // Détection automatique du mobile
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        enableMobileMode();
    }

    // Événements tactiles pour le joystick
    setupTouchEvents();
}

function createTouchInterface() {
    const controlsContainer = document.createElement('div');
    controlsContainer.id = 'mobile-controls';
    controlsContainer.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 0;
        right: 0;
        z-index: 1000;
        pointer-events: none;
        display: none;
    `;

    // Joystick de mouvement
    const joystickArea = document.createElement('div');
    joystickArea.innerHTML = `
        <div style="position: relative; width: 120px; height: 120px; margin-left: 30px;">
            <div id="joystick-base" style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; position: absolute; top: 20px; left: 20px; border: 2px solid rgba(255,255,255,0.5);"></div>
            <div id="joystick-handle" style="width: 40px; height: 40px; background: rgba(255,255,255,0.8); border-radius: 50%; position: absolute; top: 40px; left: 40px; transition: transform 0.1s;"></div>
        </div>
    `;
    joystickArea.style.pointerEvents = 'auto';

    // Contrôles d'altitude
    const altitudeControls = document.createElement('div');
    altitudeControls.innerHTML = `
        <div style="display: flex; flex-direction: column; align-items: center; margin-right: 30px;">
            <button id="btn-up" style="width: 60px; height: 60px; background: rgba(0, 0, 0, 0.4);; border: none; border-radius: 50%; margin-bottom: 10px; font-size: 24px; color: white; touch-action: manipulation;">⬆</button>
            <button id="btn-down" style="width: 60px; height: 60px; background: rgba(0, 0, 0, 0.4);; border: none; border-radius: 50%; font-size: 24px; color: white; touch-action: manipulation;">⬇</button>
        </div>
    `;
    altitudeControls.style.cssText = 'pointer-events: auto; display: flex; flex-direction: column; align-items: center;';

    // Conteneur principal
    const mainControls = document.createElement('div');
    mainControls.style.cssText = `
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        padding: 0 20px;
    `;

    mainControls.appendChild(joystickArea);
    mainControls.appendChild(altitudeControls);
    controlsContainer.appendChild(mainControls);

    document.body.appendChild(controlsContainer);
}

function setupTouchEvents() {
    const joystickBase = document.getElementById('joystick-base');
    const joystickHandle = document.getElementById('joystick-handle');
    const btnUp = document.getElementById('btn-up');
    const btnDown = document.getElementById('btn-down');

    if (!joystickBase) return;

    // Joystick events
    joystickBase.addEventListener('touchstart', (e) => {
        e.preventDefault();
        const touch = e.touches[0];
        const rect = joystickBase.getBoundingClientRect();

        joystick.active = true;
        joystick.startX = rect.left + rect.width / 2;
        joystick.startY = rect.top + rect.height / 2;
        joystick.currentX = touch.clientX;
        joystick.currentY = touch.clientY;

        updateJoystick();
    });

    document.addEventListener('touchmove', (e) => {
        if (!joystick.active) return;
        e.preventDefault();

        const touch = e.touches[0];
        joystick.currentX = touch.clientX;
        joystick.currentY = touch.clientY;

        updateJoystick();
    });

    document.addEventListener('touchend', (e) => {
        if (!joystick.active) return;

        joystick.active = false;
        resetJoystick();
    });

    // Boutons d'altitude
    btnUp.addEventListener('touchstart', (e) => {
        e.preventDefault();
        keys['up'] = true;
        btnUp.style.background = 'rgba(211, 211, 211, 0.3)';
    });

    btnUp.addEventListener('touchend', (e) => {
        e.preventDefault();
        keys['up'] = false;
        btnUp.style.background = 'rgba(211, 211, 211, 0.9)';
    });

    btnDown.addEventListener('touchstart', (e) => {
        e.preventDefault();
        keys['down'] = true;
        btnDown.style.background = 'rgba(211, 211, 211, 0.3)';
    });

    btnDown.addEventListener('touchend', (e) => {
        e.preventDefault();
        keys['down'] = false;
        btnDown.style.background = 'rgba(211, 211, 211, 0.9)';
    });
}

function updateJoystick() {
    if (!joystick.active) return;

    const joystickHandle = document.getElementById('joystick-handle');
    if (!joystickHandle) return;

    const deltaX = joystick.currentX - joystick.startX;
    const deltaY = joystick.currentY - joystick.startY;

    // Limiter le mouvement du joystick
    const maxDistance = 35;
    const distance = Math.min(Math.sqrt(deltaX * deltaX + deltaY * deltaY), maxDistance);
    const angle = Math.atan2(deltaY, deltaX);

    const moveX = Math.cos(angle) * distance;
    const moveY = Math.sin(angle) * distance;

    // Mettre à jour la position visuelle du joystick
    joystickHandle.style.transform = `translate(${moveX}px, ${moveY}px)`;

    // Mettre à jour les contrôles (seuils ajustés pour mobile)
    const deadZone = 15;

    // Avant/Arrière
    keys['forward'] = deltaY < -deadZone;
    keys['backward'] = deltaY > deadZone;

    // Gauche/Droite
    keys['left'] = deltaX < -deadZone;
    keys['right'] = deltaX > deadZone;
}

function resetJoystick() {
    const joystickHandle = document.getElementById('joystick-handle');
    if (joystickHandle) {
        joystickHandle.style.transform = 'translate(0px, 0px)';
    }

    // Réinitialiser tous les contrôles
    keys['forward'] = false;
    keys['backward'] = false;
    keys['left'] = false;
    keys['right'] = false;
}

function enableMobileMode() {
    const mobileControls = document.getElementById('mobile-controls');
    if (mobileControls) {
        mobileControls.style.display = 'block';
    }

    // Ajuster les paramètres pour mobile
    maxSpeed = 1.0; // Vitesse réduite pour mobile
    acceleration = 0.02;

    // Message d'instructions pour mobile
    showMobileInstructions();
}

function showMobileInstructions() {
    const instructions = document.createElement('div');
    instructions.innerHTML = `
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%); background: rgba(0,0,0,0.9); border: 1px solid white; color: white; padding: 20px; border-radius: 12px; text-align: center; z-index: 2000; max-width: 300px;">
            <h3>Contrôles Mobile</h3>
            <p>• <strong>Joystick gauche</strong> : Avancer/Reculer/Tourner</p>
            <p>• <strong>Boutons droite</strong> : Monter/Descendre</p>
            <button onclick="this.parentElement.remove()" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 20px; margin-top: 15px; cursor: pointer;">
                Compris !
            </button>
        </div>
    `;
    document.body.appendChild(instructions);
}

// POI Points d'intérêt dans les nuages POI
/* Les 3 axes dans Three.js :
        position: new THREE.Vector3(X, Y, Z)
// X = Gauche/Droite (horizontal)
// Y = Haut/Bas (vertical)  
// Z = Avant/Arrière (profondeur)
*/

const predefinedPOIs = [
    {
        id: 'intro',
        title: 'Introduction',
        description: 'Comment naviguer ?',
        position: new THREE.Vector3(-100, 80, 0)
    },
    {
        id: 'wam',
        title: 'Qui suis-je ?',
        description: 'Brève présentation',
        position: new THREE.Vector3(0, 180, -10)
    },
    {
        id: 'projects',
        title: 'Projets',
        description: 'Mes réalisations',
        position: new THREE.Vector3(-160, 100, -80)
    },
    {
        id: 'skills',
        title: 'Avis',
        description: 'Retour de mes clients',
        position: new THREE.Vector3(150, 90, 60)
    },
    {
        id: 'contact',
        title: 'Contact',
        description: 'Travaillons ensemble',
        position: new THREE.Vector3(100, 110, -150)
    },
    {
        id: 'video-gallery',
        title: 'Vidéo',
        description: 'et motion design',
        position: new THREE.Vector3(120, 40, -12)
    },
    {
        id: 'quest',
        title: 'Noter l\'expérience',
        description: 'Laisser un avis Google',
        position: new THREE.Vector3(24, 30, -87)
    },
    {
        id: 'portal',
        title: 'Revenir au site',
        description: 'Portal vers mon portfolio',
        position: new THREE.Vector3(-24, 0, 80) //position: new THREE.Vector3(Gauche/Droite, Haut/Bas, Avant/Arrière)
    },
    {
        id: 'promo',
        title: 'Offre',
        description: '10% de réduction caché dans ce site',
        position: new THREE.Vector3(-100, 115, 150)
    },
    {
        id: 'social',
        title: 'Follow me',
        description: '...',
        position: new THREE.Vector3(-140, 125, -60)
    },
];

// System de point a chaque POI ouvert + compteur
// SYSTÈME DE POINTS GAMIFIÉ POUR LES POI
const POINTS_PAR_POI = 10;
let userPoints = 0;
let openedPOI = {}; // { poiId: true }
const TOTAL_POI = predefinedPOIs.length;

/* Saved points dans le localStorage

// juste pour bosser sur les POI et repartir le compteur a zéro depuis la console : 
// localStorage.clear(); location.reload();
//  ou
localStorage.removeItem('userPoints');
// PUIS REFRESH
location.reload();
*/
function loadGamificationState() {
    const savedPoints = localStorage.getItem('userPoints');
    const savedOpened = localStorage.getItem('openedPOI');
    userPoints = savedPoints ? parseInt(savedPoints, 10) : 0;
    openedPOI = savedOpened ? JSON.parse(savedOpened) : {};
    updatePointsHUD();
}

function saveGamificationState() {
    localStorage.setItem('userPoints', userPoints);
    localStorage.setItem('openedPOI', JSON.stringify(openedPOI));
}

function addPointsForPOI(poiId) {
    if (!openedPOI[poiId]) {
        userPoints += POINTS_PAR_POI;
        openedPOI[poiId] = true;
        updatePointsHUD();
        saveGamificationState();
        playAddPointAnimation(); // Optionnel : animation affichant "+10"
        createChimeSound(); // Son de carillon
        // Vérifier si le joueur a gagné
        if (Object.keys(openedPOI).length === TOTAL_POI) {
            showDiscountPopup();
        }
    }
}

// Affiche un popup de succès avec animation
// showDiscountPopup(); dans la console ;)
function showDiscountPopup() {
    const popup = document.createElement('div');
    popup.innerHTML = `
        <div class="special-offer-overlay">
            <div class="special-offer-content">
                <div class="offer-header">
                    <div class="confetti">💎</div>
                    <h3>OFFRE SPÉCIALE DÉCOUVREUR !</h3>
                    <div class="confetti">🪙</div>
                </div>
                
                <div class="offer-badge">EXCLUSIF</div>
                
                <p class="offer-subtitle">Pour avoir exploré tout mon univers, je t'offre :</p>
                
                <div class="offer-features">
                    <div class="feature-item">
                        <span class="feature-icon">✅</span>
                        <div>
                            <strong>un Audit gratuit</strong> de ton site actuel
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">✅</span>
                        <div>
                            <strong>Conseil stratégique 30min</strong> personnalisé
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">✅</span>
                        <div>
                            <strong>10% de réduction</strong> sur ton projet
                        </div>
                    </div>
                </div>

                <div class="offer-highlight">
                    <span class="discount-badge">-10%</span>
                    <span class="offer-code">Code: <strong>EXPLORER-25</strong></span>
                </div>

                <button onclick="openCard('contact-card'); this.closest('.special-offer-overlay').remove()" class="offer-btn">
                 Clamer mon offre exclusive
                </button>
                
                <div class="offer-footer">
                    <small>Offre valable pour les explorateurs audacieux ✨</small>
                </div>
                
                <button class="close-offer" onclick="this.closest('.special-offer-overlay').remove()">×</button>
            </div>
        </div>
    `;

    document.body.appendChild(popup);
    
    // Animation d'entrée
    setTimeout(() => {
        const content = popup.querySelector('.special-offer-content');
        content.style.transform = 'scale(1)';
        content.style.opacity = '1';
    }, 100);

    // Fermeture automatique après 15 secondes
    setTimeout(() => {
        if (popup.parentNode) {
            popup.style.opacity = '0';
            popup.style.transition = 'opacity 0.8s ease';
            setTimeout(() => {
                if (popup.parentNode) document.body.removeChild(popup);
            }, 800);
        }
    }, 15000);
}

// Optionnel : animation "+10" over HUD
function playAddPointAnimation() {
    const el = document.createElement('div');
    el.textContent = '+10 💎';
    el.style.position = 'fixed';
    el.style.top = '20%';
    el.style.left = '50%';
    el.style.transform = 'translate(-50%,0)';
    el.style.fontSize = '2em';
    el.style.fontWeight = 600;
    el.style.color = '#60d394';
    el.style.textShadow = '0 2px 20px #222';
    el.style.zIndex = 2000;
    el.style.opacity = '1';
    el.style.transition = 'all 2s cubic-bezier(.2,1.2,.2,1)';
    document.body.appendChild(el);
    setTimeout(() => {
        el.style.transform = 'translate(-50%,-110px)';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 800);
    }, 90);
}

// HUD Points : créer un simple affichage quelque part (ex : dans setupUI)
function updatePointsHUD() {
    let el = document.getElementById('points-hud');
    if (!el) {
        el = document.createElement('div');
        el.id = 'points-hud';
        el.style.position = 'fixed';
        el.style.top = '12px';
        el.style.right = '14px';
        el.style.padding = '10px 18px';
        el.style.background = 'rgba(30,30,40,0.88)';
        el.style.color = '#f1f1f1';
        el.style.fontWeight = 'bold';
        el.style.borderRadius = '13px';
        el.style.fontSize = '1.1em';
        el.style.zIndex = 1001;
        document.body.appendChild(el);
    }
    el.textContent = `Score : ${userPoints} 💎`;
}

// INIT INIT INIT 
function init() {
    console.log('🚀 Initialisation de l\'expérience aérienne...');
    loadGamificationState();
    setupThreeJS();
    setupUI(); // 
    createScene();
    setupControls();
    setupMobileControls();// CONTROLES MOBILE a regler
    initAudio(); // SON
    setupIdleAnimation();
    animate();
}

function setupThreeJS() {
    // Scene avec ciel dégradé
    scene = new THREE.Scene();

    // Camera
    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 2000);
    camera.position.set(0, 10, 20);

    // Renderer
    renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true
    });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.shadowMap.enabled = true;
    renderer.domElement.setAttribute('tabindex', '0');
    renderer.domElement.style.outline = 'none';
    document.getElementById('container').appendChild(renderer.domElement);

    // Controls
    controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.minDistance = 5;
    controls.maxDistance = 100;
}

function setupUI() {
    // Générer la liste des POIs dans le menu
    const poiList = document.getElementById('poi-list');
    predefinedPOIs.forEach(poi => {
        const poiElement = document.createElement('div');
        poiElement.style.cssText = `
            background: rgba(255,255,255,0.05); 
            padding: 10px; 
            margin: 5px 0; 
            border-radius: 5px; 
            cursor: pointer;
            transition: all 0.3s;
        `;
        poiElement.innerHTML = `
            <strong>${poi.title}</strong><br>
            <small style="opacity:0.7">${poi.description}</small>
        `;
        poiElement.addEventListener('click', () => navigateToPOI(poi.id));
        poiElement.addEventListener('mouseenter', () => {
            poiElement.style.background = 'rgba(255,170,0,0.2)';
        });
        poiElement.addEventListener('mouseleave', () => {
            poiElement.style.background = 'rgba(255,255,255,0.05)';
        });
        poiList.appendChild(poiElement);
    });

    // Gestion du formulaire 
    document.querySelector('.contact-form').addEventListener('submit', function (e) {
        e.preventDefault();
        alert('Message envoyé ! Merci pour votre contact.');
        closeCard('contact-card');
    });

    // Liens de la gallery - OUVRE LES LIENS DANS UN NOUVEL ONGLET
    document.querySelectorAll('.gallery-item a').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            // Ouvrir le lien dans un nouvel onglet
            window.open(this.href, '_blank');
        });
    });

    // Setup l'upload GLB
    setupGLBUpload();
}

function createScene() {
    // Créer le ciel HDRI en premier (fond)
    createSky();

    // Charger l'environnement GLB dans la scène
    loadEnvironmentGLB();

    // Lumière directionnelle (soleil)
    const sunLight = new THREE.DirectionalLight(0xffaa00, 4); // Lumière chaude et intensitée
    sunLight.position.set(100, 100, 50);
    sunLight.castShadow = true;
    scene.add(sunLight);

    // Lumière ambiante chaude
    const ambientLight = new THREE.AmbientLight(0xab9ff2, 0.3); // Lumière ambiante douce
    scene.add(ambientLight);

    // Créer l'avion
    createAircraft();

    // Créer les nuages décoratifs
    createDecorativeClouds();

    // Créer les points d'intérêt (nuages colorés)
    createPointsOfInterest();
}

// FONCTION RÉTABLIE : Créer le ciel HDRI
function createSky() {
    const skyGeometry = new THREE.SphereGeometry(1000, 32, 32);

    // Texture de ciel HDRI rendu sphérique 
    const skyTextures = [
        'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/cgpt.png',
        'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/nebula-hdri.webp',

        'https://raw.githubusercontent.com/imgntn/j360/refs/heads/master/screencap2.jpg',
        'https://raw.githubusercontent.com/berru-g/plane/main/avion/ciel-nuage.webp',
        'https://cdn.polyhaven.com/asset_img/primary/dikhololo_night.png?height=760&quality=95',
        'https://raw.githubusercontent.com/berru-g/plane/main/avion/cloudy.png'
    ];

    const textureLoader = new THREE.TextureLoader();
    const skyTexture = textureLoader.load(skyTextures[0], () => {
        console.log('✅ Texture HDRI de ciel chargée');
    });

    skyTexture.colorSpace = THREE.SRGBColorSpace;

    const skyMaterial = new THREE.MeshBasicMaterial({
        map: skyTexture,
        side: THREE.BackSide
    });

    const sky = new THREE.Mesh(skyGeometry, skyMaterial);
    scene.add(sky);
}

// NOUVELLE FONCTION: Charger l'environnement GLB
function loadEnvironmentGLB() {
    const loader = new THREE.GLTFLoader();

    // URLs d'environnements GLB 
    const environmentURLs = [
        //'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/space.glb', //"Map_tkgcz" (https://skfb.ly/pyOyZ) by amogusstrikesback2 is licensed under Creative Commons Attribution (http://creativecommons.org/licenses/by/4.0/).
        //'https://raw.githubusercontent.com/berru-g/crypto-tool/main/heatmap-forest/assets/iss.glb',
        //'https://raw.githubusercontent.com/berru-g/3d-scroll-animate/main/assets/....glb',
        'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/fantasy_town.glb',
    ];

    function tryLoadEnvironment(urlIndex) {
        if (urlIndex >= environmentURLs.length) {
            console.log('ℹ️ Tous les environnements GLB ont échoué, continuation sans environnement 3D');
            return;
        }

        loader.load(environmentURLs[urlIndex], (gltf) => {
            environmentGLB = gltf.scene;

            // Ajuster l'échelle et la position
            environmentGLB.scale.set(100, 100, 100);
            environmentGLB.position.set(0, 0, 0);

            // Configurer les matériaux pour l'environnement
            environmentGLB.traverse((child) => {
                if (child.isMesh) {
                    child.receiveShadow = true;
                    child.castShadow = true;
                    // Transparence de l'environnement 3D
                    if (child.material) {
                        child.material.transparent = true;
                        child.material.opacity = 1; // 0 -> 1 = opaque 
                    }
                }
            });

            scene.add(environmentGLB);
            console.log('✅ Environnement GLB chargé avec succès');

        }, undefined, (error) => {
            console.log('❌ Échec du chargement environnement:', environmentURLs[urlIndex]);
            // Essayer l'URL suivant
            tryLoadEnvironment(urlIndex + 1);
        });
    }

    tryLoadEnvironment(0);
}
// au cas ou l'obj 3D ne charge pas, on a un avion temporaire 
function createAircraft() {
    // D'abord créer un avion simple temporaire
    const tempAircraft = createTempAircraft();
    aircraft = tempAircraft;
    aircraft.position.set(-100, 80, 0);
    aircraft.rotation.y = Math.PI;
    scene.add(aircraft);

    // Essayer de charger un GLB d'avion
    loadAircraftGLB();
}

function createTempAircraft() {
    // Avion simple temporaire
    const aircraftGroup = new THREE.Group();

    const fuselage = new THREE.Mesh(
        new THREE.CylinderGeometry(0.5, 0.3, 8, 8),
        new THREE.MeshPhongMaterial({ color: 0xffffff })
    );
    fuselage.rotation.z = Math.PI / 2;
    aircraftGroup.add(fuselage);

    const wings = new THREE.Mesh(
        new THREE.BoxGeometry(10, 0.2, 2),
        new THREE.MeshPhongMaterial({ color: 0xcccccc })
    );
    aircraftGroup.add(wings);

    const tail = new THREE.Mesh(
        new THREE.BoxGeometry(1, 3, 0.2),
        new THREE.MeshPhongMaterial({ color: 0xffffff })
    );
    tail.position.set(-3, 1, 0);
    aircraftGroup.add(tail);

    return aircraftGroup;
}

function loadAircraftGLB() {
    const loader = new THREE.GLTFLoader();

    // URLs de modèles d'avion GLB gratuits
    const aircraftURLs = [
        //'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/drone.glb',
        'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/cute_taxi_spaceship.glb',
    ];

    // Essayer chaque URL jusqu'à ce qu'un fonctionne
    tryLoadGLB(loader, aircraftURLs, 0);
}

// SYSTÈME D'ANIMATION DRONE AU REPOS
function setupIdleAnimation() {
    lastUserAction = Date.now();

    // Vérifier toutes les secondes si l'utilisateur est inactif
    setInterval(() => {
        const idleTime = Date.now() - lastUserAction;

        if (idleTime > 5000 && !isDroneAnimating && aircraft && experienceStarted) {
            startIdleAnimation();
        }
    }, 1000);
}

let isDroneAnimating = false;

function startIdleAnimation() {
    if (isDroneAnimating) return;

    isDroneAnimating = true;
    console.log('🎬 Début animation drone repos');

    const startY = aircraft.position.y;

    function animateIdle() {
        if (!isDroneAnimating) return;
        // Flottement vertical doux
        aircraft.position.y = startY + Math.sin(Date.now() * 0.002) * 2;

        requestAnimationFrame(animateIdle);
    }

    animateIdle();
}

function stopIdleAnimation() {
    if (!isDroneAnimating) return;
    isDroneAnimating = false;
    console.log('⏹️ Animation drone repos arrêtée');
}

function tryLoadGLB(loader, urls, index) {
    if (index >= urls.length) {
        console.log('ℹ️ Tous les modèles GLB ont échoué, utilisation de l\'avion par défaut');
        return;
    }

    loader.load(urls[index], (gltf) => {
        // Supprimer l'avion temporaire
        scene.remove(aircraft);

        // Configurer le nouvel avion GLB
        aircraftGLB = gltf.scene;
        aircraftGLB.scale.set(8, 8, 8); // Ajuster l'échelle de l'avion drone=2, taxi = 8
        aircraftGLB.position.set(-100, 80, 0);
        aircraftGLB.rotation.set(0, Math.PI, 0);

        // Activer les ombres
        aircraftGLB.traverse((child) => {
            if (child.isMesh) {
                child.castShadow = true;
                child.receiveShadow = true;
            }
        });

        scene.add(aircraftGLB);
        aircraft = aircraftGLB;

        console.log('✅ Avion GLB chargé avec succès:', urls[index]);

    }, undefined, (error) => {
        console.log('❌ Échec du chargement:', urls[index]);
        // Essayer l'URL suivant
        tryLoadGLB(loader, urls, index + 1);
    });
}

function createDecorativeClouds() {
    // crzate nebulas
    const cloudCount = 25;

    for (let i = 0; i < cloudCount; i++) {
        createCloud(
            (Math.random() - 0.5) * 1500,
            Math.random() * 800 + 100,
            (Math.random() - 0.5) * 1500,
            Math.random() * 100 + 50,
            false // Pas un POI
        );
    }
}

function createCloud(x, y, z, isPOI = false) {
    const cloudGroup = new THREE.Group();

    if (isPOI) {
        // NUAGE POI - FORME ET COULEUR DISTINCTIVE
        createPOICloud(cloudGroup, x, y, z);
    } else {
        // NUAGE DÉCORATIF CLASSIQUE
        createDecorativeCloud(cloudGroup, x, y, z);
    }

    scene.add(cloudGroup);
    return cloudGroup;
}

function createPOICloud(cloudGroup, x, y, z) {
    // Forme plus structurée et géométrique pour les POIs
    const geometries = [
        new THREE.OctahedronGeometry(6, 0),  // Forme cristalline
        new THREE.TorusGeometry(4, 1.5, 8, 12),  // Anneau
        new THREE.ConeGeometry(5, 8, 6),  // Cône
        new THREE.DodecahedronGeometry(5, 0)  // Polyèdre
    ];

    const poiGeometry = geometries[Math.floor(Math.random() * geometries.length)];
    const poiMaterial = new THREE.MeshPhongMaterial({
        color: 0x4B0082, // Couleur violet
        emissive: 0xff4500,
        emissiveIntensity: 0.8,
        transparent: true,
        opacity: 0.9,
        shininess: 100
    });

    const mainShape = new THREE.Mesh(poiGeometry, poiMaterial);
    cloudGroup.add(mainShape);

    // Effet de pulsation lumineuse
    const pointLight = new THREE.PointLight(0xff6b35, 2, 50);
    pointLight.position.set(0, 3, 0);
    cloudGroup.add(pointLight);

    // Anneau lumineux autour du POI
    const ringGeometry = new THREE.TorusGeometry(8, 0.3, 4, 24);
    const ringMaterial = new THREE.MeshBasicMaterial({
        color: 0x32CD32,
        transparent: true,
        opacity: 0.6
    });
    const ring = new THREE.Mesh(ringGeometry, ringMaterial);
    ring.rotation.x = Math.PI / 2;
    cloudGroup.add(ring);

    // Particules flottantes autour
    for (let i = 0; i < 8; i++) {
        const particleGeometry = new THREE.SphereGeometry(0.5, 4, 4);
        const particleMaterial = new THREE.MeshBasicMaterial({
            color: 0x00ff88,
            transparent: true,
            opacity: 0.7
        });
        const particle = new THREE.Mesh(particleGeometry, particleMaterial);

        const angle = (i / 8) * Math.PI * 2;
        const radius = 10 + Math.random() * 5;
        particle.position.set(
            Math.cos(angle) * radius,
            Math.random() * 6 - 3,
            Math.sin(angle) * radius
        );
        cloudGroup.add(particle);
    }

    cloudGroup.position.set(x, y, z);
    cloudGroup.scale.set(1.2, 1.2, 1.2);
}

function createDecorativeCloud(cloudGroup, x, y, z) {
    // Nuage décoratif classique - doux et naturel
    const cloudGeometry = new THREE.SphereGeometry(4, 6, 6);
    const cloudMaterial = new THREE.MeshPhongMaterial({
        color: 0xffffff,
        transparent: true,
        opacity: 0.3
    });

    const spherePositions = [
        [0, 0, 0], [3, 1, -2], [-3, 1, 2],
        [4, -1, 0], [-4, -1, 0], [0, 3, 3],
        [2, -2, -1], [-2, -2, 1]
    ];

    spherePositions.forEach(pos => {
        const sphere = new THREE.Mesh(cloudGeometry, cloudMaterial);
        sphere.position.set(pos[0], pos[1], pos[2]);
        sphere.scale.set(
            Math.random() * 0.6 + 0.7,
            Math.random() * 0.4 + 0.6,
            Math.random() * 0.6 + 0.7
        );
        cloudGroup.add(sphere);
    });

    cloudGroup.position.set(x, y, z);

    // Légère animation pour les nuages décoratifs
    cloudGroup.userData = {
        originalY: y,
        speed: Math.random() * 0.02 + 0.01,
        time: Math.random() * Math.PI * 2
    };
}

function animateClouds() {
    scene.children.forEach(child => {
        if (child.userData && child.userData.originalY !== undefined) {
            // Animation de flottement pour les nuages décoratifs
            child.userData.time += child.userData.speed;
            child.position.y = child.userData.originalY + Math.sin(child.userData.time) * 2;
        }

        if (child.userData && child.userData.poiInfo) {
            // Animation de pulsation pour les POIs
            const scale = 1 + Math.sin(Date.now() * 0.001) * 0.1;
            child.scale.setScalar(scale);

            // Faire tourner l'anneau
            const ring = child.children.find(child => child.geometry instanceof THREE.TorusGeometry);
            if (ring) {
                ring.rotation.y += 0.01;
            }
        }
    });
}

function createPointsOfInterest() {
    predefinedPOIs.forEach((poi, index) => {
        const poiCloud = createCloud(
            poi.position.x,
            poi.position.y,
            poi.position.z,
            true // C'est un POI
        );

        // Couleur différente pour chaque POI
        const colors = [0xab9ff2, 0x2575fc, 0x60d394, 0xee6055, 0xffff00];
        const poiColor = colors[index % colors.length];

        // Appliquer la couleur au matériau principal
        const mainMesh = poiCloud.children[0];
        if (mainMesh && mainMesh.material) {
            mainMesh.material.color.set(poiColor);
            mainMesh.material.emissive.set(poiColor);
        }

        poiCloud.userData = {
            poiInfo: poi,
            color: poiColor
        };
        pointsOfInterest.push(poiCloud);
    });
}

function setupControls() {
    document.addEventListener('keydown', (event) => {
        if (keys.hasOwnProperty(event.key)) {
            keys[event.key] = true;
            stopIdleAnimation();
            lastUserAction = Date.now();
            event.preventDefault();
        }
        // AJOUTER LES CONTRÔLES TACTILES POUR LA CAMÉRA
        renderer.domElement.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) { // Un seul doigt = rotation caméra
                isMouseDown = true;
                isFreeLookMode = true;
                previousMousePosition = {
                    x: e.touches[0].clientX,
                    y: e.touches[0].clientY
                };
                controls.enabled = false;
                lastUserAction = Date.now();
                stopIdleAnimation();
            }
        }, { passive: false });

        renderer.domElement.addEventListener('touchmove', (e) => {
            if (isMouseDown && isFreeLookMode && e.touches.length === 1) {
                e.preventDefault();

                const deltaMove = {
                    x: e.touches[0].clientX - previousMousePosition.x,
                    y: e.touches[0].clientY - previousMousePosition.y
                };

                // Sensibilité adaptée pour mobile
                const rotationSpeed = 0.008;

                camera.rotation.y -= deltaMove.x * rotationSpeed;
                camera.rotation.x -= deltaMove.y * rotationSpeed;
                camera.rotation.x = Math.max(-Math.PI / 2, Math.min(Math.PI / 2, camera.rotation.x));

                previousMousePosition = {
                    x: e.touches[0].clientX,
                    y: e.touches[0].clientY
                };
                lastUserAction = Date.now();
            }
        }, { passive: false });

        renderer.domElement.addEventListener('touchend', () => {
            isMouseDown = false;
            isFreeLookMode = false;
            controls.enabled = true;
            lastUserAction = Date.now();
        });

    });

    document.addEventListener('keyup', (event) => {
        if (keys.hasOwnProperty(event.key)) {
            keys[event.key] = false;
            stopIdleAnimation();
            lastUserAction = Date.now();
            event.preventDefault();
        }
    });

    // ÉVÉNEMENTS SOURIS POUR REGARDER AUTOUR
    renderer.domElement.addEventListener('mousedown', (event) => {
        isMouseDown = true;
        isFreeLookMode = true;
        previousMousePosition = { x: event.clientX, y: event.clientY };

        // Désactiver temporairement OrbitControls
        controls.enabled = false;

        // Style du curseur
        renderer.domElement.style.cursor = 'grabbing';
        lastUserAction = Date.now();
        stopIdleAnimation();
    });

    renderer.domElement.addEventListener('mousemove', (event) => {
        if (isMouseDown && isFreeLookMode) {
            const deltaMove = {
                x: event.clientX - previousMousePosition.x,
                y: event.clientY - previousMousePosition.y
            };

            // Sensibilité de rotation
            const rotationSpeed = 0.005;

            // Rotation horizontale (autour de l'axe Y)
            camera.rotation.y -= deltaMove.x * rotationSpeed;

            // Rotation verticale (autour de l'axe X) avec limites
            camera.rotation.x -= deltaMove.y * rotationSpeed;
            camera.rotation.x = Math.max(-Math.PI / 2, Math.min(Math.PI / 2, camera.rotation.x));

            previousMousePosition = { x: event.clientX, y: event.clientY };
            lastUserAction = Date.now();
        }
    });

    renderer.domElement.addEventListener('mouseup', () => {
        isMouseDown = false;
        isFreeLookMode = false;
        controls.enabled = true;
        renderer.domElement.style.cursor = 'grab';
        lastUserAction = Date.now();
    });

    renderer.domElement.addEventListener('mouseleave', () => {
        if (isMouseDown) {
            isMouseDown = false;
            isFreeLookMode = false;
            controls.enabled = true;
            renderer.domElement.style.cursor = 'grab';
        }
    });

    // Curseur par défaut
    renderer.domElement.style.cursor = 'grab';

    // Événements existants (UNIQUEMENT CES DEUX-LÀ)
    document.addEventListener('mousemove', () => {
        lastUserAction = Date.now();
        stopIdleAnimation();
    });

    document.addEventListener('click', () => {
        lastUserAction = Date.now();
        stopIdleAnimation();
    });

    renderer.domElement.addEventListener('click', () => {
        renderer.domElement.focus();
        lastUserAction = Date.now();
        stopIdleAnimation();
    });

    renderer.domElement.focus();
}



// function updateAircraft smooth fluide
function updateAircraft() {
    if (!aircraft || !experienceStarted) return;

    const delta = clock.getDelta();

    // Détection d'input (inclut maintenant le mobile)
    const hasUserInput = keys[' '] || keys['s'] || keys['S'] ||
        keys['ArrowUp'] || keys['ArrowDown'] || keys['ArrowLeft'] || keys['ArrowRight'] ||
        keys['forward'] || keys['backward'] || keys['left'] || keys['right'] || keys['up'] || keys['down'];

    if (hasUserInput) {
        lastUserAction = Date.now();
    }

    // CONTRÔLES UNIFIÉS (Desktop + Mobile)
    let targetPitch = 0;
    let verticalSpeed = 0;
    let targetRoll = 0;
    let rotationSpeed = 0;

    // AVANT/ARRIÈRE (Espace/S + Joystick avant/arrière)
    if (keys[' '] || keys['forward']) {
        aircraftSpeed = THREE.MathUtils.lerp(aircraftSpeed, maxSpeed, 0.1 * delta * 60);
    } else if (keys['s'] || keys['S'] || keys['backward']) {
        aircraftSpeed = THREE.MathUtils.lerp(aircraftSpeed, -maxSpeed * 0.2, 0.15 * delta * 60);
    } else {
        aircraftSpeed = THREE.MathUtils.lerp(aircraftSpeed, 0, 0.05 * delta * 60);
    }

    // LIMITER LA VITESSE
    aircraftSpeed = Math.max(Math.min(aircraftSpeed, maxSpeed), -maxSpeed * 0.3);

    // ALTITUDE (Flèches + Boutons mobile)
    if (keys['ArrowUp'] || keys['up']) {
        targetPitch = -0.1;
        verticalSpeed = 0.3; // Réduit pour mobile
    }
    if (keys['ArrowDown'] || keys['down']) {
        targetPitch = 0.1;
        verticalSpeed = -0.3; // Réduit pour mobile
    }

    // ROTATION (Flèches + Joystick gauche/droite)
    if (keys['ArrowLeft'] || keys['left']) {
        targetRoll = 0.2;
        rotationSpeed = 0.015; // Réduit pour mobile
    } else if (keys['ArrowRight'] || keys['right']) {
        targetRoll = -0.2;
        rotationSpeed = -0.015; // Réduit pour mobile
    }

    // APPLIQUER LES MOUVEMENTS
    aircraft.position.y += verticalSpeed * delta * 60;
    aircraft.position.y = Math.max(aircraft.position.y, 10);

    // ROTATIONS FLUIDES
    aircraft.rotation.z = THREE.MathUtils.lerp(aircraft.rotation.z, targetRoll, 0.2 * delta * 60);
    aircraft.rotation.y += rotationSpeed * delta * 60;
    aircraft.rotation.x = THREE.MathUtils.lerp(aircraft.rotation.x, targetPitch, 0.15 * delta * 60);

    // MOUVEMENT AVANT
    const direction = new THREE.Vector3(0, 0, -1);
    direction.applyQuaternion(aircraft.quaternion);
    aircraft.position.add(direction.multiplyScalar(aircraftSpeed * delta * 60));

    // Mettre à jour la caméra
    updateCamera();

    // Vérifier les points d'intérêt
    checkPOIProximity();

    // Mettre à jour le HUD
    updateHUD();
}

// MODIFIER updateCamera() POUR GÉRER LES DEUX MODES
function updateCamera() {
    if (!aircraft) return;

    if (isFreeLookMode) {
        // Mode libre : la caméra reste fixe sur sa rotation actuelle
        // mais suit quand même la position de l'avion
        const targetCameraPos = aircraft.position.clone();
        const cameraOffset = new THREE.Vector3(0, 4, 10);

        // Appliquer la rotation actuelle de la caméra à l'offset
        cameraOffset.applyEuler(camera.rotation);
        targetCameraPos.add(cameraOffset);

        camera.position.lerp(targetCameraPos, 0.1);

        // Regarder vers l'avion depuis la position de la caméra
        const lookAtPos = aircraft.position.clone();
        camera.lookAt(lookAtPos);

    } else {
        // Mode normal : caméra derrière l'avion
        const cameraOffset = new THREE.Vector3(0, 4, 10);
        cameraOffset.applyQuaternion(aircraft.quaternion);

        const targetCameraPos = aircraft.position.clone().add(cameraOffset);
        camera.position.lerp(targetCameraPos, 0.1);

        const lookAtPos = aircraft.position.clone();
        const lookAtOffset = new THREE.Vector3(0, 0, -15);
        lookAtOffset.applyQuaternion(aircraft.quaternion);
        camera.lookAt(lookAtPos.add(lookAtOffset));

        controls.target.copy(aircraft.position);
    }
}


function checkPOIProximity() {
    if (!aircraft) return;

    let nearestPoi = null;
    let minDistance = Infinity;

    pointsOfInterest.forEach(poiCloud => {
        const distance = aircraft.position.distanceTo(poiCloud.position);
        if (distance < 35 && distance < minDistance) {
            minDistance = distance;
            nearestPoi = poiCloud.userData.poiInfo;
        }
    });

    if (nearestPoi && nearestPoi !== activePoi) {
        activePoi = nearestPoi;
        document.getElementById('target-display').textContent = nearestPoi.title;
        openCard(nearestPoi.id + '-card');

        // ← AJOUTER L'APPEL DU SON
        playPOISound(nearestPoi.id);

    } else if (!nearestPoi && activePoi) {
        activePoi = null;
        document.getElementById('target-display').textContent = 'Exploration';
        if (currentCard) closeCard(currentCard);
    }
}

function updateHUD() {
    const speed = Math.abs(Math.round(aircraftSpeed * 300));
    const altitude = Math.round(aircraft.position.y);

    document.getElementById('speed-display').textContent = speed + ' km/h';
    document.getElementById('altitude-display').textContent = altitude + ' km';

    // Indicateur mode libre
    let freeLookIndicator = document.getElementById('free-look-indicator');
    if (!freeLookIndicator) {
        freeLookIndicator = document.createElement('div');
        freeLookIndicator.id = 'free-look-indicator';
        freeLookIndicator.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        `;
        freeLookIndicator.innerHTML = '🎥 Mode caméra libre • Relâchez pour revenir';
        document.body.appendChild(freeLookIndicator);
    }

    if (isFreeLookMode) {
        freeLookIndicator.style.opacity = '1';
    } else {
        freeLookIndicator.style.opacity = '0';
    }
}

function toggleMenu() {
    document.getElementById('menu-panel').classList.toggle('active');
}

function navigateToPOI(poiId) {
    const poi = predefinedPOIs.find(p => p.id === poiId);
    if (poi && aircraft) {
        aircraft.position.copy(poi.position);
        aircraft.position.x += 30; // Arriver à côté du POI
        aircraft.rotation.y = Math.PI; // Faire face au POI

        // Fermer le menu
        toggleMenu();

        // Ouvrir la carte correspondante
        openCard(poiId + '-card');
    }
}

// GESTION DES Fichiers GLB personnalisés :
function setupGLBUpload() {
    const fileInput = document.getElementById('glb-file');
    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const url = URL.createObjectURL(file);
                loadCustomAircraftGLB(url);
            }
        });
    }
}

function loadCustomAircraftGLB(url) {
    const loader = new THREE.GLTFLoader();

    loader.load(url, (gltf) => {
        // Supprimer l'ancien avion
        if (aircraftGLB) {
            scene.remove(aircraftGLB);
        }
        if (aircraft && aircraft !== aircraftGLB) {
            scene.remove(aircraft);
        }

        // Configurer le nouveau modèle
        aircraftGLB = gltf.scene;
        aircraftGLB.scale.set(4, 4, 4);
        aircraftGLB.position.set(-100, 80, 0);
        aircraftGLB.rotation.set(0, Math.PI, 0);

        // Ajuster l'échelle automatiquement selon la taille
        const box = new THREE.Box3().setFromObject(aircraftGLB);
        const size = box.getSize(new THREE.Vector3());
        const maxDim = Math.max(size.x, size.y, size.z);
        const scale = 10 / maxDim; // Ajuster pour avoir ~10 unités de taille max
        aircraftGLB.scale.set(scale, scale, scale);

        aircraftGLB.traverse((child) => {
            if (child.isMesh) {
                child.castShadow = true;
                child.receiveShadow = true;
            }
        });

        scene.add(aircraftGLB);
        aircraft = aircraftGLB;

        console.log('✅ Avion personnalisé chargé avec succès');

    }, undefined, (error) => {
        console.error('❌ Erreur chargement avion personnalisé:', error);
        alert('Erreur lors du chargement du fichier GLB');
    });
}

function resetAircraft() {
    if (aircraft) {
        aircraft.position.set(-100, 80, 0);
        aircraft.rotation.set(0, Math.PI, 0);
        aircraftSpeed = 0;
    }
    toggleMenu();
}

function openCard(cardId) {
    if (currentCard) closeCard(currentCard);
    const card = document.getElementById(cardId);
    if (card) {
        card.style.display = 'block';
        currentCard = cardId;
        // prendre l'id du poi
        const poiBaseId = cardId.replace('-card', '');
        addPointsForPOI(poiBaseId);
    }
}

function closeCard(cardId) {
    const card = document.getElementById(cardId);
    if (card) {
        card.style.display = 'none';
        currentCard = null;
    }
}

function animate() {
    requestAnimationFrame(animate);
    updateAircraft();
    animateClouds();
    controls.update();
    renderer.render(scene, camera);
}

window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
});

// Démarrer
init();