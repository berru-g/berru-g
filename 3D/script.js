// ============================================================
// 3D WEBSITE V2 — PHASE 1 « COLLECTATHON ZEN »
// gael-berru.com/3D/
// ------------------------------------------------------------
// Vanilla JS monofichier — drop-in replacement de la V1.
//
// INTÉGRATION :
//   Remplacer le <script src> de la V1 par ce fichier.
//   Dépendances inchangées : three.min.js + GLTFLoader (builds non-module, comme la V1).
//   HTML requis  : <div id="container">
//   HTML hérité de la V1 (tous OPTIONNELS, le script ne plantera pas s'ils manquent) :
//     #poi-list, #menu-panel, #target-display, #speed-display, #altitude-display,
//     #glb-file, .contact-form, .gallery-item a, cartes « xxx-card »
//
// NOUVEAUTÉS PHASE 1 :
//   ✅ Gemmes à collecter (InstancedMesh : 180 gemmes = 1 draw call)
//   ✅ Magnétisme + burst de particules + son pentatonique à combo
//   ✅ Compteur de complétion persistant (sauvegarde gemme par gemme)
//   ✅ Trophée final sobre (aucune promo : la récompense est l'accomplissement)
//   ✅ Delta-time corrigé partout (damp() — indépendant du framerate)
//   ✅ AudioContext unique déverrouillé au 1er geste utilisateur (corrige le bug mp3/Firefox)
//   ✅ Nuages décoratifs instanciés (~200 draw calls → 1)
//   ✅ Écran de chargement avec LoadingManager
//   ✅ Brouillard exponentiel pour la profondeur sans coût GPU
//
// NOUVEAUTÉS PHASE 2 :
//   ✅ Niveau 2 « L'épreuve des Portails » : débloqué à 180/180 gemmes via le trophée
//   ✅ Nouvel univers : bascule de skyTexture (nébuleuse) + brouillard sombre
//   ✅ Objet central du Niveau 2 : GLB dédié (CONFIG.LEVEL2_CORE_URL) + fallback cristal procédural
//   ✅ Course de 12 portails chronométrée en spirale autour du cristal, meilleur temps sauvegardé
//   ✅ Portail violet dans le monde 1 : accès libre au Niveau 2 une fois débloqué
//   ✅ Portail vert de retour dans le Niveau 2
//
// RESET POUR TESTER (console) :
//   localStorage.removeItem('berru3d_v2_gems');
//   localStorage.removeItem('berru3d_v2_points');
//   localStorage.removeItem('berru3d_v2_pois');
//   localStorage.removeItem('berru3d_v2_missions');
//   localStorage.removeItem('berru3d_v2_level2');
//   localStorage.removeItem('berru3d_v2_besttime');
//   location.reload();
// ============================================================

// ------------------------------------------------------------ CONFIG
const CONFIG = {
    SEED: 42,                  // graine → disposition des gemmes déterministe (stable entre sessions)
    GEM_COUNT: 40,
    GEMS_PER_POI: 14,          // amas autour de chaque POI + dispersion dans le monde
    GEM_MAGNET_RADIUS: 45,     // la gemme est attirée par le vaisseau
    GEM_COLLECT_RADIUS: 14,
    GEM_SCALE_MIN: 1.0,
    GEM_SCALE_MAX: 1.8,

    MAX_SPEED: 2.5,
    REVERSE_FACTOR: 0.25,
    TURN_SPEED: 0.015,
    VERTICAL_SPEED: 0.5,
    PITCH: 0.1,
    ROLL: 0.2,

    WORLD_BOUND: 900,
    MIN_ALTITUDE: 10,
    POI_TRIGGER_DISTANCE: 35,

    CLOUD_COUNT: 25,
    PUFFS_PER_CLOUD: 8,

    POINTS_PAR_POI: 10,

    SAVE_GEMS: 'berru3d_v2_gems',
    SAVE_POINTS: 'berru3d_v2_points',
    SAVE_POIS: 'berru3d_v2_pois',
    SAVE_MISSIONS: 'berru3d_v2_missions',
    SAVE_LEVEL2: 'berru3d_v2_level2',
    SAVE_BEST_TIME: 'berru3d_v2_besttime',

    // ---- NIVEAU 2 : l'épreuve des Portails (nouvel univers nébuleuse)
    LEVEL2_SKY_URL: 'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/nebula-hdri.webp',
    // 👉 GLB de l'objet central du Niveau 2 : remplace cette URL par ton modèle.
    //    S'il est absent ou en échec, un cristal procédural prend le relais (zéro crash).
    LEVEL2_CORE_URL: 'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/cyberpunk_city_small.glb',
    LEVEL2_CENTER: { x: 0, y: 160, z: 0 },
    LEVEL2_SPAWN: { x: 0, y: 180, z: 480 },
    RING_COUNT: 12,
    RING_RADIUS: 28,          // rayon du portail
    RING_PASS_DISTANCE: 30,    // tolérance de traversée
    RING_COURSE_TURNS: 0.85,   // la spirale fait ~1,7 tour autour du cristal
    PORTAL_TRIGGER_DISTANCE: 25,
    FOG_LEVEL1: { color: 0xbfd4ff, density: 0.0009 },
    FOG_LEVEL2: { color: 0x120b2e, density: 0.0011 }
};

// Compatibilité : damp() existe depuis r113, fallback sinon (indépendance framerate garantie)
const damp = (THREE.MathUtils && THREE.MathUtils.damp)
    ? THREE.MathUtils.damp.bind(THREE.MathUtils)
    : function (a, b, lambda, dt) { return THREE.MathUtils.lerp(a, b, 1 - Math.exp(-lambda * dt)); };

// PRNG déterministe (mulberry32) : même monde = mêmes gemmes, sauvegarde fiable
function mulberry32(seed) {
    return function () {
        seed |= 0; seed = (seed + 0x6D2B79F5) | 0;
        let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

// ------------------------------------------------------------ ÉTAT GLOBAL
let scene, camera, renderer, clock;
let aircraft = null, aircraftGLB = null, environmentGLB = null;
let aircraftSpeed = 0;
let experienceStarted = true;
let activePoi = null, currentCard = null;
let lastUserAction = Date.now();
let isFreeLookMode = false, isDragging = false;
let previousPointer = { x: 0, y: 0 };
const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
    || (window.matchMedia && window.matchMedia('(pointer: coarse)').matches);

if (isMobile) { CONFIG.MAX_SPEED = 1.0; }

// Touches clavier + états tactiles
const keys = {
    'ArrowUp': false, 'ArrowDown': false, 'ArrowLeft': false, 'ArrowRight': false,
    ' ': false, 's': false, 'S': false,
    'forward': false, 'backward': false, 'left': false, 'right': false, 'up': false, 'down': false
};

// Vecteurs temporaires réutilisés (zéro allocation dans la boucle de rendu)
const _dir = new THREE.Vector3();
const _camOffset = new THREE.Vector3();
const _camTarget = new THREE.Vector3();
const _lookTarget = new THREE.Vector3();
const _dummy = new THREE.Object3D();

function byId(id) { return document.getElementById(id); }

// ------------------------------------------------------------ AUDIO
// Un SEUL AudioContext, résumé au premier geste utilisateur.
// Fix du bug V1 : sur Firefox/Chrome, Audio() et l'oscillator ne démarrent
// pas avant une interaction — on démarre tout dans unlockAudio().
let audioContext = null;
let isSoundEnabled = true;
let backgroundMusic = null;
let poiSounds = {};
let gemCombo = 0, lastGemTime = 0;
const PENTATONIC = [523.25, 587.33, 659.25, 783.99, 880.0, 1046.5, 1174.66]; // Do Ré Mi Sol La Do Ré

function audioReady() {
    return isSoundEnabled && audioContext && audioContext.state === 'running';
}

function initAudio() {
    try {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
    } catch (e) {
        console.log('❌ Audio non supporté :', e);
        isSoundEnabled = false;
        return;
    }
    loadPOISounds();

    const unlock = () => {
        if (audioContext && audioContext.state === 'suspended') {
            audioContext.resume();
        }
        startBackgroundMusic();
        ['pointerdown', 'keydown', 'touchstart'].forEach(ev => window.removeEventListener(ev, unlock));
    };
    // Le premier geste (clic / touche / toucher) débloque TOUT l'audio
    ['pointerdown', 'keydown', 'touchstart'].forEach(ev => window.addEventListener(ev, unlock));
    console.log('✅ Audio initialisé (déverrouillage au premier geste)');
}

function startBackgroundMusic() {
    if (backgroundMusic || !isSoundEnabled) return;
    try {
        const audio = new Audio('../sounds/mixkit-relax-beat-292.mp3'); // chemin identique à la V1
        audio.loop = true;
        audio.volume = 0.4;
        audio.play().catch(err => console.log('⚠️ Lecture musique bloquée :', err.message));
        backgroundMusic = audio;
    } catch (e) {
        console.log('⚠️ Musique indisponible :', e);
    }
}

function loadPOISounds() {
    poiSounds = {
        'intro': createBeepSound(523.25, 0.3),
        'wam': createBeepSound(698.46, 0.3),
        'projects': createBeepSound(587.33, 0.3),
        'skills': createBeepSound(659.25, 0.3),
        'contact': createBeepSound(698.46, 0.3),
        'video-gallery': createBeepSound(783.99, 0.5),
        'quest': createChimeSound(),
        'portal': createPortalSound(),
        'social': createBeepSound(659.25, 0.3)
    };
}

function playPOISound(poiId) {
    if (poiSounds[poiId]) poiSounds[poiId]();
}

function createBeepSound(frequency, duration) {
    return function () {
        if (!audioReady()) return;
        const t = audioContext.currentTime;
        const osc = audioContext.createOscillator();
        const gain = audioContext.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(frequency, t);
        gain.gain.setValueAtTime(0.1, t);
        gain.gain.exponentialRampToValueAtTime(0.001, t + duration);
        osc.connect(gain); gain.connect(audioContext.destination);
        osc.start(t); osc.stop(t + duration);
    };
}

function createChimeSound() {
    return function () {
        if (!audioReady()) return;
        [523.25, 659.25, 783.99].forEach((freq, i) => {
            const t = audioContext.currentTime + i * 0.2;
            const osc = audioContext.createOscillator();
            const gain = audioContext.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(freq, t);
            gain.gain.setValueAtTime(0.1, t);
            gain.gain.exponentialRampToValueAtTime(0.001, t + 0.5);
            osc.connect(gain); gain.connect(audioContext.destination);
            osc.start(t); osc.stop(t + 0.5);
        });
    };
}

function createPortalSound() {
    return function () {
        if (!audioReady()) return;
        const t = audioContext.currentTime;
        const osc = audioContext.createOscillator();
        const gain = audioContext.createGain();
        const filter = audioContext.createBiquadFilter();
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(100, t);
        osc.frequency.exponentialRampToValueAtTime(800, t + 1);
        filter.type = 'lowpass';
        filter.frequency.setValueAtTime(1000, t);
        gain.gain.setValueAtTime(0.05, t);
        gain.gain.exponentialRampToValueAtTime(0.001, t + 1.5);
        osc.connect(filter); filter.connect(gain); gain.connect(audioContext.destination);
        osc.start(t); osc.stop(t + 1.5);
    };
}

// Son de gemme : monte la gamme pentatonique à chaque collecte rapprochée (combo)
function playGemSound() {
    if (!audioReady()) return;
    const now = performance.now();
    if (now - lastGemTime > 1500) gemCombo = 0;
    else gemCombo = Math.min(gemCombo + 1, PENTATONIC.length - 1);
    lastGemTime = now;

    const t = audioContext.currentTime;
    const freq = PENTATONIC[gemCombo];
    [[freq, 0.12], [freq * 2, 0.04]].forEach(([f, vol]) => {
        const osc = audioContext.createOscillator();
        const gain = audioContext.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(f, t);
        gain.gain.setValueAtTime(vol, t);
        gain.gain.exponentialRampToValueAtTime(0.001, t + 0.25);
        osc.connect(gain); gain.connect(audioContext.destination);
        osc.start(t); osc.stop(t + 0.25);
    });
}

// ------------------------------------------------------------ POIs (la promo V1 est retirée : la récompense finale est le trophée)
const predefinedPOIs = [
    { id: 'intro',         title: 'Introduction',        description: 'Comment naviguer ?',        position: new THREE.Vector3(-100, 80, 0) },
    { id: 'wam',           title: 'Qui suis-je ?',       description: 'Brève présentation',       position: new THREE.Vector3(0, 180, -10) },
    { id: 'projects',      title: 'Projets',             description: 'Mes réalisations',         position: new THREE.Vector3(-160, 100, -80) },
    { id: 'skills',        title: 'Avis',                description: 'Retour de mes clients',    position: new THREE.Vector3(150, 90, 60) },
    { id: 'contact',       title: 'Contact',             description: 'Travaillons ensemble',     position: new THREE.Vector3(100, 110, -150) },
    { id: 'video-gallery', title: 'Vidéo',               description: 'et motion design',         position: new THREE.Vector3(120, 40, -12) },
    { id: 'quest',         title: 'Noter l\'expérience', description: 'Laisser un avis Google',   position: new THREE.Vector3(24, 30, -87) },
    { id: 'portal',        title: 'Revenir au site',     description: 'Portail vers mon portfolio', position: new THREE.Vector3(-24, 0, 80) },
    { id: 'social',        title: 'Follow me',           description: 'Mes réseaux',              position: new THREE.Vector3(-140, 125, -60) }
];
const pointsOfInterest = [];

// ------------------------------------------------------------ GAMIFICATION : points, missions, gemmes
const missionSystem = {
    'wam':      { title: 'Découvrir mon profil',   action: 'Lire ma présentation',     reward: '+20 💎', points: 20 },
    'projects': { title: 'Explorer mes projets',   action: 'Voir 3 projets minimum',   reward: '+30 💎', points: 30 },
    'skills':   { title: 'Lire les témoignages',   action: 'Découvrir les avis clients', reward: '+25 💎', points: 25 },
    'contact':  { title: 'Mission spéciale : Contact', action: 'Envoyer un message test', reward: '+50 💎', points: 50, bonus: true }
};

let userPoints = 0;
let openedPOI = {};
let completedMissions = [];

// --- Gemmes
let gemMesh = null;
let gemBasePositions = [];   // positions déterministes (restaurées au chargement)
let gemPositions = [];       // positions runtime (le magnétisme les déplace)
let gemPhases = [];          // déphasages d'animation
let gemScales = [];
let gemCollected = [];       // booléens persistés
let gemsCollectedCount = 0;
let trophyShown = false;

// ---- Niveau 2
let currentLevel = 1;
let level2Unlocked = false;
let bestRingTime = null;
let skyMeshWorld1 = null, skyMeshWorld2 = null;
let level2Core = null;
let portalToLevel2 = null;
let returnPortal = null;
let raceRings = [];
let raceState = { started: false, running: false, startTime: 0, nextRing: 0 };
let level2Built = false;

function loadGamificationState() {
    try {
        const p = localStorage.getItem(CONFIG.SAVE_POINTS);
        userPoints = p ? parseInt(p, 10) : 0;
        openedPOI = JSON.parse(localStorage.getItem(CONFIG.SAVE_POIS) || '{}');
        completedMissions = JSON.parse(localStorage.getItem(CONFIG.SAVE_MISSIONS) || '[]');
        gemCollected = JSON.parse(localStorage.getItem(CONFIG.SAVE_GEMS) || '[]');
        if (!Array.isArray(gemCollected)) gemCollected = [];
    } catch (e) {
        console.log('⚠️ Sauvegarde corrompue, reset :', e);
        gemCollected = []; openedPOI = {}; completedMissions = [];
        level2Unlocked = false; bestRingTime = null;
    }
    gemsCollectedCount = gemCollected.filter(Boolean).length;
    level2Unlocked = localStorage.getItem(CONFIG.SAVE_LEVEL2) === '1';
    const savedBest = localStorage.getItem(CONFIG.SAVE_BEST_TIME);
    bestRingTime = savedBest ? parseFloat(savedBest) : null;
}

function saveGamificationState() {
    localStorage.setItem(CONFIG.SAVE_POINTS, String(userPoints));
    localStorage.setItem(CONFIG.SAVE_POIS, JSON.stringify(openedPOI));
    localStorage.setItem(CONFIG.SAVE_MISSIONS, JSON.stringify(completedMissions));
    localStorage.setItem(CONFIG.SAVE_GEMS, JSON.stringify(gemCollected));
    localStorage.setItem(CONFIG.SAVE_LEVEL2, level2Unlocked ? '1' : '0');
    if (bestRingTime !== null) localStorage.setItem(CONFIG.SAVE_BEST_TIME, String(bestRingTime));
}

function addPointsForPOI(poiId) {
    if (openedPOI[poiId]) return;
    openedPOI[poiId] = true;
    userPoints += CONFIG.POINTS_PAR_POI;
    saveGamificationState();
    updateGemsHUD();
    playAddPointAnimation('+' + CONFIG.POINTS_PAR_POI + ' 💎');
    if (poiSounds[poiId]) poiSounds[poiId]();
}

function completeMission(poiId) {
    const mission = missionSystem[poiId];
    if (mission && completedMissions.indexOf(poiId) === -1) {
        completedMissions.push(poiId);
        userPoints += mission.points;
        showMissionComplete(mission);
        saveGamificationState();
        updateGemsHUD();
    }
}

function showMissionComplete(mission) {
    const popup = document.createElement('div');
    popup.innerHTML = `
        <div style="font-size:1.4em; font-weight:700; color:#fff; background:linear-gradient(135deg, #2575fc 0%, #ab9ff2 100%);
                    border-radius:20px; padding:22px; text-align:center;">
            MISSION ACCOMPLIE !<br>
            <span style="font-size:0.8em; opacity:0.9;">${mission.title}</span><br>
            <div style="margin-top:12px; font-size:1.1em; color:#ffe953;">${mission.reward}</div>
        </div>`;
    popup.style.cssText = 'position:fixed; top:38%; left:50%; transform:translate(-50%,-50%); z-index:2000; transition:opacity .6s ease;';
    document.body.appendChild(popup);
    setTimeout(() => { popup.style.opacity = '0'; }, 3500);
    setTimeout(() => popup.remove(), 4200);
}

// Trophée final sobre : l'accomplissement EST la récompense (aucune promo).
function showTrophyPopup() {
    if (trophyShown) return;
    trophyShown = true;
    const popup = document.createElement('div');
    popup.innerHTML = `
        <div style="position:fixed; inset:0; background:rgba(10,10,25,0.75); z-index:3000;
                    display:flex; align-items:center; justify-content:center; transition:opacity .8s ease;">
            <div style="background:linear-gradient(160deg, #1c2340 0%, #2b3a67 100%); color:#f1f1f1;
                        border-radius:24px; padding:40px 50px; text-align:center; max-width:440px;
                        border:1px solid rgba(255,255,255,0.15); box-shadow:0 20px 60px rgba(0,0,0,0.5);">
                <div style="font-size:3.2em; line-height:1;">🏆</div>
                <h3 style="margin:14px 0 6px; letter-spacing:1px;">EXPLORATEUR LÉGENDAIRE</h3>
                <p style="opacity:0.85; font-size:0.95em; margin:0 0 18px;">
                    Tu as rassemblé les ${CONFIG.GEM_COUNT} gemmes de mon univers.<br>
                    Merci d'avoir exploré chaque recoin du ciel.
                </p>
                <p style="opacity:0.85; font-size:0.9em; margin:0 0 8px;">
                    Un nouvel univers vient de s'ouvrir à toi…
                </p>
                <p style="opacity:0.6; font-size:0.8em; margin:0 0 22px;">
                    Si l'expérience t'a plu, une étoile de plus peut toujours aider :
                    <a href="https://www.restosducoeur.org/" target="_blank" rel="noopener"
                       style="color:#8fd3ff;">les Restos du Cœur</a>.
                </p>
                <div style="display:flex; gap:10px; justify-content:center;">
                    <button id="trophy-level2" style="background:linear-gradient(135deg,#2575fc,#ab9ff2); color:#fff; border:none;
                            padding:10px 24px; border-radius:22px; cursor:pointer; font-size:0.95em; font-weight:bold;">→ Niveau 2</button>
                    <button id="trophy-close" style="background:rgba(255,255,255,0.12); color:#fff; border:1px solid rgba(255,255,255,0.25);
                            padding:10px 24px; border-radius:22px; cursor:pointer; font-size:0.95em;">Continuer à voler</button>
                </div>
            </div>
        </div>`;
    document.body.appendChild(popup);
    popup.querySelector('#trophy-close').addEventListener('click', () => popup.remove());
    popup.querySelector('#trophy-level2').addEventListener('click', () => {
        popup.remove();
        unlockLevel2();
        enterLevel2();
    });
    if (poiSounds['quest']) poiSounds['quest']();
}

// ------------------------------------------------------------ NIVEAU 2 : L'ÉPREUVE DES PORTAILS
// Nouvel univers nébuleuse + objet central (GLB dédié ou cristal procédural)
// + course de 12 portails en spirale. Débloqué à 180/180 gemmes ; ensuite,
// un portail violet reste en permanence dans le monde 1.

function unlockLevel2() {
    if (level2Unlocked) return;
    level2Unlocked = true;
    saveGamificationState();
    createPortalToLevel2();
    showToast('🌌 Niveau 2 débloqué : un portail violet est apparu dans le ciel', 5000);
}

// Fondu noir court : masque la bascule d'univers (ciel, brouillard, téléportation)
function fadeTransition(midFn) {
    const fade = document.createElement('div');
    fade.style.cssText = 'position:fixed; inset:0; background:#000; z-index:4000; opacity:0; transition:opacity .45s ease;';
    document.body.appendChild(fade);
    requestAnimationFrame(() => { fade.style.opacity = '1'; });
    setTimeout(() => {
        if (midFn) midFn();
        fade.style.opacity = '0';
        setTimeout(() => fade.remove(), 600);
    }, 500);
}

function enterLevel2() {
    if (currentLevel === 2) return;
    currentLevel = 2;
    fadeTransition(() => {
        // ---- Monde 1 en veille
        if (environmentGLB) environmentGLB.visible = false;
        if (cloudMesh) cloudMesh.visible = false;
        if (gemMesh) gemMesh.visible = false;
        pointsOfInterest.forEach(p => { p.visible = false; });
        if (portalToLevel2) portalToLevel2.visible = false;
        const gemsHud = byId('gems-hud');
        if (gemsHud) gemsHud.style.display = 'none';
        if (currentCard) closeCard(currentCard);
        activePoi = null;

        // ---- Bascule d'univers : ciel nébuleuse + brouillard sombre
        if (skyMeshWorld1) skyMeshWorld1.visible = false;
        if (skyMeshWorld2) skyMeshWorld2.visible = true;
        scene.fog.color.setHex(CONFIG.FOG_LEVEL2.color);
        scene.fog.density = CONFIG.FOG_LEVEL2.density;

        buildLevel2();

        aircraft.position.set(CONFIG.LEVEL2_SPAWN.x, CONFIG.LEVEL2_SPAWN.y, CONFIG.LEVEL2_SPAWN.z);
        aircraft.rotation.set(0, 0, 0);
        aircraftSpeed = 0;
        setText('target-display', 'Épreuve des Portails', '');
        resetRingCourse();
        updateRaceHUD();
        showToast('🌀 Traverse les 12 portails bleus · portail vert derrière toi pour revenir', 5000);
    });
}

function exitLevel2() {
    if (currentLevel === 1) return;
    currentLevel = 1;
    fadeTransition(() => {
        // ---- Niveau 2 en veille
        if (level2Core) level2Core.visible = false;
        if (returnPortal) returnPortal.visible = false;
        raceRings.forEach(r => { r.visible = false; });
        if (skyMeshWorld2) skyMeshWorld2.visible = false;
        if (skyMeshWorld1) skyMeshWorld1.visible = true;

        // ---- Retour au monde 1
        scene.fog.color.setHex(CONFIG.FOG_LEVEL1.color);
        scene.fog.density = CONFIG.FOG_LEVEL1.density;
        if (environmentGLB) environmentGLB.visible = true;
        if (cloudMesh) cloudMesh.visible = true;
        if (gemMesh) gemMesh.visible = true;
        pointsOfInterest.forEach(p => { p.visible = true; });
        if (portalToLevel2) portalToLevel2.visible = true;
        const gemsHud = byId('gems-hud');
        if (gemsHud) gemsHud.style.display = 'block';

        aircraft.position.set(-100, 90, -10);
        aircraft.rotation.set(0, Math.PI, 0);
        aircraftSpeed = 0;
        setText('target-display', 'Exploration', '');
        updateRaceHUD();
        showToast('☁️ Retour dans le monde des nuages', 3000);
    });
}

// Construit le Niveau 2 une seule fois (GLB central + portails + portail de retour),
// ensuite on se contente de basculer la visibilité.
function buildLevel2() {
    if (level2Built) {
        if (level2Core) level2Core.visible = true;
        if (returnPortal) returnPortal.visible = true;
        raceRings.forEach(r => { r.visible = true; });
        return;
    }
    level2Built = true;
    loadLevel2Core();
    createRingCourse();
    createReturnPortal();
}

// -------- Objet central : GLB dédié si dispo, sinon cristal procédural (aucun crash)
function loadLevel2Core() {
    const loader = new THREE.GLTFLoader();
    loader.load(CONFIG.LEVEL2_CORE_URL, (gltf) => {
        level2Core = gltf.scene;
        // Échelle automatique : ~120 unités pour un objet monumental
        const box = new THREE.Box3().setFromObject(level2Core);
        const size = box.getSize(new THREE.Vector3());
        level2Core.scale.setScalar(120 / Math.max(size.x, size.y, size.z, 0.001));
        level2Core.position.set(CONFIG.LEVEL2_CENTER.x, CONFIG.LEVEL2_CENTER.y, CONFIG.LEVEL2_CENTER.z);
        level2Core.traverse((child) => {
            if (child.isMesh) { child.castShadow = false; child.receiveShadow = false; }
        });
        level2Core.visible = currentLevel === 2;
        scene.add(level2Core);
        console.log('✅ Objet central du Niveau 2 chargé (GLB)');
    }, undefined, () => {
        level2Core = createProceduralCore();
        scene.add(level2Core);
        console.log('ℹ️ GLB central indisponible : cristal procédural utilisé — mets ton URL dans CONFIG.LEVEL2_CORE_URL');
    });
}

// Fallback : cristal violet + anneaux orbitaux + halo lumineux
function createProceduralCore() {
    const group = new THREE.Group();
    const crystal = new THREE.Mesh(
        new THREE.OctahedronGeometry(40, 1),
        new THREE.MeshPhongMaterial({
            color: 0x9f6fff, emissive: 0x5a2fd8, emissiveIntensity: 0.8,
            shininess: 120, transparent: true, opacity: 0.95
        })
    );
    group.add(crystal);
    [[80, 1.2, 0], [95, 0.8, Math.PI / 3], [110, 0.6, -Math.PI / 4]].forEach(([radius, tube, tilt], i) => {
        const ring = new THREE.Mesh(
            new THREE.TorusGeometry(radius, tube, 8, 48),
            new THREE.MeshBasicMaterial({ color: 0x8fd3ff, transparent: true, opacity: 0.35 })
        );
        ring.rotation.x = Math.PI / 2 + tilt;
        ring.userData.spin = 0.1 + i * 0.07;
        group.add(ring);
    });
    group.add(new THREE.PointLight(0x9f6fff, 3, 600));
    group.position.set(CONFIG.LEVEL2_CENTER.x, CONFIG.LEVEL2_CENTER.y, CONFIG.LEVEL2_CENTER.z);
    group.visible = currentLevel === 2;
    return group;
}

// -------- Course : 12 portails en spirale resserrée autour du cristal
function ringCoursePoint(t) {
    const cx = CONFIG.LEVEL2_CENTER.x, cy = CONFIG.LEVEL2_CENTER.y, cz = CONFIG.LEVEL2_CENTER.z;
    const angle = Math.PI / 2 + t * Math.PI * 2 * CONFIG.RING_COURSE_TURNS;
    const radius = 380 - t * 200;
    return new THREE.Vector3(
        cx + Math.cos(angle) * radius,
        cy + Math.sin(t * Math.PI * 3) * 60 + t * 40,
        cz + Math.sin(angle) * radius
    );
}

function createRingCourse() {
    for (let i = 0; i < CONFIG.RING_COUNT; i++) {
        const t = i / (CONFIG.RING_COUNT - 1);
        const ring = new THREE.Mesh(
            new THREE.TorusGeometry(CONFIG.RING_RADIUS, 1.8, 10, 32),
            new THREE.MeshBasicMaterial({ color: 0x66e0ff, transparent: true, opacity: 0.3 })
        );
        ring.position.copy(ringCoursePoint(t));
        ring.userData.index = i;
        // Oriente le portail face à la trajectoire (normal = tangente du parcours)
        ring.lookAt(ringCoursePoint(Math.min(1, t + 0.08)));
        raceRings.push(ring);
        scene.add(ring);
    }
    console.log('✅ Course des Portails créée : ' + CONFIG.RING_COUNT + ' portails en spirale');
}

function resetRingCourse() {
    raceState.started = false;
    raceState.running = false;
    raceState.startTime = 0;
    raceState.nextRing = 0;
}

function checkRingProximity() {
    if (!aircraft || !raceRings.length) return;
    const nextRing = raceRings[raceState.nextRing];
    if (!nextRing) return;
    const trigger2 = CONFIG.RING_PASS_DISTANCE * CONFIG.RING_PASS_DISTANCE;
    if (aircraft.position.distanceToSquared(nextRing.position) < trigger2) {
        if (!raceState.started) {
            raceState.started = true;
            raceState.running = true;
            raceState.startTime = performance.now();
        }
        playRingSound(raceState.nextRing);
        spawnParticleBurst(nextRing.position, 16);
        raceState.nextRing++;
        if (raceState.nextRing >= raceRings.length) {
            finishRingCourse();
        } else {
            showToast(`🌀 Portail ${raceState.nextRing} / ${raceRings.length}`, 1100);
        }
    }
}

function finishRingCourse() {
    const finalMs = performance.now() - raceState.startTime;
    raceState.running = false;
    raceState.started = false;
    raceState.nextRing = raceRings.length;
    const isBest = bestRingTime === null || finalMs < bestRingTime;
    if (isBest) {
        bestRingTime = finalMs;
        saveGamificationState();
    }
    updateRaceHUD();
    showLevel2Victory(finalMs, isBest);
    if (poiSounds['quest']) poiSounds['quest']();
}

function showLevel2Victory(finalMs, isBest) {
    const popup = document.createElement('div');
    popup.innerHTML = `
        <div style="position:fixed; inset:0; background:rgba(5,5,18,0.8); z-index:3000;
                    display:flex; align-items:center; justify-content:center; transition:opacity .8s ease;">
            <div style="background:linear-gradient(160deg, #14102e 0%, #2b1a5e 100%); color:#f1f1f1;
                        border-radius:24px; padding:38px 46px; text-align:center; max-width:420px;
                        border:1px solid rgba(159,111,255,0.35); box-shadow:0 20px 60px rgba(0,0,0,0.6);">
                <div style="font-size:3em; line-height:1;">🌀</div>
                <h3 style="margin:14px 0 6px; letter-spacing:1px;">ÉPREUVE DES PORTAILS RÉUSSIE</h3>
                <p style="font-size:1.2em; margin:8px 0;">
                    Ton temps : <strong style="color:#9ff7ff;">${formatRaceTime(finalMs)}</strong>
                </p>
                <p style="opacity:0.7; font-size:0.9em; margin:0 0 22px;">
                    Meilleur temps : ${formatRaceTime(bestRingTime)} ${isBest ? '🥇 Nouveau record !' : ''}
                </p>
                <div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                    <button id="race-replay" style="background:linear-gradient(135deg,#2575fc,#ab9ff2); color:#fff; border:none;
                            padding:10px 22px; border-radius:22px; cursor:pointer; font-weight:bold;">Rejouer la course</button>
                    <button id="race-stay" style="background:rgba(255,255,255,0.12); color:#fff; border:1px solid rgba(255,255,255,0.25);
                            padding:10px 22px; border-radius:22px; cursor:pointer;">Rester dans la nébuleuse</button>
                    <button id="race-back" style="background:rgba(255,255,255,0.12); color:#fff; border:1px solid rgba(255,255,255,0.25);
                            padding:10px 22px; border-radius:22px; cursor:pointer;">Monde 1</button>
                </div>
            </div>
        </div>`;
    document.body.appendChild(popup);
    popup.querySelector('#race-replay').addEventListener('click', () => {
        popup.remove();
        resetRingCourse();
        updateRaceHUD();
        showToast('🌀 Nouvelle course : passe le premier portail bleu !', 3000);
    });
    popup.querySelector('#race-stay').addEventListener('click', () => popup.remove());
    popup.querySelector('#race-back').addEventListener('click', () => { popup.remove(); exitLevel2(); });
}

function formatRaceTime(ms) {
    const total = ms / 1000;
    const m = Math.floor(total / 60);
    const s = (total - m * 60).toFixed(1);
    return m + ':' + (s.length < 4 ? '0' + s : s);
}

function updateRaceHUD() {
    let hud = byId('race-hud');
    if (currentLevel !== 2) {
        if (hud) hud.style.display = 'none';
        return;
    }
    if (!hud) {
        hud = document.createElement('div');
        hud.id = 'race-hud';
        hud.style.cssText = 'position:fixed; top:60px; left:50%; transform:translateX(-50%); padding:8px 22px;' +
            'background:rgba(20,16,46,0.9); color:#f1f1f1; border-radius:13px; z-index:1001; font-weight:bold; letter-spacing:1px;';
        document.body.appendChild(hud);
    }
    hud.style.display = 'block';
    if (raceState.running) {
        const elapsed = performance.now() - raceState.startTime;
        hud.textContent = `🌀 ${raceState.nextRing} / ${raceRings.length} portails · ${formatRaceTime(elapsed)}`;
    } else if (raceState.nextRing >= raceRings.length && raceRings.length) {
        hud.textContent = `🌀 Course terminée · ${formatRaceTime(bestRingTime || 0)}`;
    } else {
        hud.textContent = `🌀 ${raceState.nextRing} / ${raceRings.length} — passe le portail bleu pour lancer le chrono`;
    }
}

// Animations propres au Niveau 2 (cristal, portail actif, HUD course)
function updateLevel2(time) {
    if (level2Core) {
        level2Core.rotation.y = time * 0.12;
        level2Core.children.forEach((child) => {
            if (child.userData.spin) child.rotation.z = time * child.userData.spin;
        });
    }
    raceRings.forEach((ring, i) => {
        if (!ring.visible) return;
        const isActive = i === raceState.nextRing;
        ring.material.opacity = isActive ? 0.75 + Math.sin(time * 4) * 0.25 : 0.25;
        ring.scale.setScalar(isActive ? 1 + Math.sin(time * 3) * 0.05 : 1);
    });
    if (raceState.running) updateRaceHUD();
}

// -------- Portails de liaison entre les deux univers
function createPortalToLevel2() {
    if (portalToLevel2 || !scene) return;
    portalToLevel2 = new THREE.Group();
    const torus = new THREE.Mesh(
        new THREE.TorusGeometry(12, 1.4, 12, 40),
        new THREE.MeshBasicMaterial({ color: 0x9f6fff, transparent: true, opacity: 0.85 })
    );
    portalToLevel2.add(torus);
    portalToLevel2.add(new THREE.PointLight(0x9f6fff, 2, 80));
    // Position : près du POI Introduction, plan face au point de départ
    portalToLevel2.position.set(-100, 95, -60);
    scene.add(portalToLevel2);
}

function checkPortalToLevel2() {
    if (!aircraft || !portalToLevel2 || !portalToLevel2.visible) return;
    const trigger2 = CONFIG.PORTAL_TRIGGER_DISTANCE * CONFIG.PORTAL_TRIGGER_DISTANCE;
    if (aircraft.position.distanceToSquared(portalToLevel2.position) < trigger2) {
        enterLevel2();
    }
}

function createReturnPortal() {
    if (returnPortal) return;
    returnPortal = new THREE.Group();
    const torus = new THREE.Mesh(
        new THREE.TorusGeometry(10, 1.2, 12, 40),
        new THREE.MeshBasicMaterial({ color: 0x60d394, transparent: true, opacity: 0.8 })
    );
    returnPortal.add(torus);
    returnPortal.add(new THREE.PointLight(0x60d394, 2, 80));
    returnPortal.position.set(CONFIG.LEVEL2_SPAWN.x, CONFIG.LEVEL2_SPAWN.y + 10, CONFIG.LEVEL2_SPAWN.z + 60);
    scene.add(returnPortal);
}

function checkReturnPortal() {
    if (!aircraft || !returnPortal || !returnPortal.visible) return;
    const trigger2 = CONFIG.PORTAL_TRIGGER_DISTANCE * CONFIG.PORTAL_TRIGGER_DISTANCE;
    if (aircraft.position.distanceToSquared(returnPortal.position) < trigger2) {
        exitLevel2();
    }
}

// Son de portail : monte d'un demi-ton à chaque portail traversé
function playRingSound(index) {
    if (!audioReady()) return;
    const t = audioContext.currentTime;
    const osc = audioContext.createOscillator();
    const gain = audioContext.createGain();
    osc.type = 'sine';
    osc.frequency.setValueAtTime(440 * Math.pow(2, index / 12), t);
    gain.gain.setValueAtTime(0.12, t);
    gain.gain.exponentialRampToValueAtTime(0.001, t + 0.35);
    osc.connect(gain); gain.connect(audioContext.destination);
    osc.start(t); osc.stop(t + 0.35);
}

function playAddPointAnimation(text) {
    const el = document.createElement('div');
    el.textContent = text;
    el.style.cssText = 'position:fixed; top:20%; left:50%; transform:translate(-50%,0); font-size:1.9em; font-weight:600;' +
        'color:#60d394; text-shadow:0 2px 20px #222; z-index:2000; transition:all 2s cubic-bezier(.2,1.2,.2,1);';
    document.body.appendChild(el);
    setTimeout(() => {
        el.style.transform = 'translate(-50%,-110px)';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 800);
    }, 90);
}

function showToast(text, duration) {
    const el = document.createElement('div');
    el.textContent = text;
    el.style.cssText = 'position:fixed; bottom:90px; left:50%; transform:translateX(-50%); background:rgba(20,20,35,0.9);' +
        'color:#f1f1f1; padding:10px 22px; border-radius:22px; z-index:1500; font-size:0.95em; max-width:80vw; text-align:center;' +
        'border:1px solid rgba(255,255,255,0.12); transition:opacity .5s ease;';
    document.body.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; }, duration || 2600);
    setTimeout(() => el.remove(), (duration || 2600) + 600);
}

// ------------------------------------------------------------ ÉCRAN DE CHARGEMENT
let loadingOverlay = null;
function createLoadingOverlay() {
    loadingOverlay = document.createElement('div');
    loadingOverlay.style.cssText = 'position:fixed; inset:0; background:#101326; z-index:5000; display:flex;' +
        'flex-direction:column; align-items:center; justify-content:center; color:#dfe6ff; transition:opacity .8s ease;';
    loadingOverlay.innerHTML = `
        <div style="font-size:1.6em; letter-spacing:2px; margin-bottom:18px;">✈ &nbsp;CHARGEMENT DE L'UNIVERS…</div>
        <div style="width:240px; height:8px; background:rgba(255,255,255,0.12); border-radius:6px; overflow:hidden;">
            <div id="v2-loading-bar" style="width:0%; height:100%; background:linear-gradient(90deg,#2575fc,#ab9ff2); transition:width .3s;"></div>
        </div>`;
    document.body.appendChild(loadingOverlay);
    // Filet de sécurité : ne jamais bloquer le joueur sur l'écran de chargement
    setTimeout(hideLoadingOverlay, 8000);
}

function hideLoadingOverlay() {
    if (!loadingOverlay) return;
    loadingOverlay.style.opacity = '0';
    setTimeout(() => { if (loadingOverlay && loadingOverlay.parentNode) loadingOverlay.remove(); loadingOverlay = null; }, 900);
}

// ------------------------------------------------------------ THREE.JS : SETUP
function setupThreeJS(loadManager) {
    scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0xbfd4ff, 0.0009); // profondeur quasi gratuite

    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 2000);
    camera.position.set(-100, 90, 20);

    renderer = new THREE.WebGLRenderer({ antialias: !isMobile, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.shadowMap.enabled = !isMobile;
    renderer.domElement.setAttribute('tabindex', '0');
    renderer.domElement.style.outline = 'none';
    renderer.domElement.style.cursor = 'grab';
    if ('outputColorSpace' in renderer && THREE.SRGBColorSpace) {
        renderer.outputColorSpace = THREE.SRGBColorSpace;
    }
    byId('container').appendChild(renderer.domElement);

    // NOTE V2 : plus d'OrbitControls — la caméra est 100 % pilotée par la
    // logique de suivi du vaisseau (le drag = regard libre). Un contrôleur de
    // moins qui se battait avec la caméra, et un script CDN de moins.
    clock = new THREE.Clock();

    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
}

function createScene(loadManager) {
    createSky(loadManager);
    loadEnvironmentGLB(loadManager);

    const sunLight = new THREE.DirectionalLight(0xffaa00, 4);
    sunLight.position.set(100, 100, 50);
    sunLight.castShadow = !isMobile;
    if (sunLight.castShadow) {
        sunLight.shadow.mapSize.set(2048, 2048);
        sunLight.shadow.camera.left = -400; sunLight.shadow.camera.right = 400;
        sunLight.shadow.camera.top = 400; sunLight.shadow.camera.bottom = -400;
    }
    scene.add(sunLight);
    scene.add(new THREE.AmbientLight(0xab9ff2, 0.35));

    createAircraft(loadManager);
    createDecorativeClouds();
    createPointsOfInterest();
    createGems();
    createParticleSystem();
}

function createSky(loadManager) {
    const skyTextures = [
        'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/cgpt.png',
        'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/nebula-hdri.webp',
        'https://raw.githubusercontent.com/imgntn/j360/refs/heads/master/screencap2.jpg',
        'https://raw.githubusercontent.com/berru-g/plane/main/avion/ciel-nuage.webp'
    ];
    const loader = new THREE.TextureLoader(loadManager);
    const skyTexture = loader.load(skyTextures[0], () => console.log('✅ Texture HDRI de ciel chargée'));
    if (THREE.SRGBColorSpace) skyTexture.colorSpace = THREE.SRGBColorSpace;

    const skyMaterial = new THREE.MeshBasicMaterial({ map: skyTexture, side: THREE.BackSide, fog: false });
    skyMeshWorld1 = new THREE.Mesh(new THREE.SphereGeometry(1000, 32, 32), skyMaterial);
    scene.add(skyMeshWorld1);

    // Ciel du Niveau 2 (nébuleuse) : préchargé dès le départ, masqué tant qu'on est dans le monde 1.
    // La bascule est instantanée pendant le fondu noir de la téléportation — zéro z-fighting.
    const level2Texture = loader.load(CONFIG.LEVEL2_SKY_URL, () => console.log('✅ Texture Niveau 2 (nébuleuse) chargée'));
    if (THREE.SRGBColorSpace) level2Texture.colorSpace = THREE.SRGBColorSpace;
    const sky2Material = new THREE.MeshBasicMaterial({ map: level2Texture, side: THREE.BackSide, fog: false });
    skyMeshWorld2 = new THREE.Mesh(new THREE.SphereGeometry(1000, 32, 32), sky2Material);
    skyMeshWorld2.visible = false;
    scene.add(skyMeshWorld2);
}

function loadEnvironmentGLB(loadManager) {
    const loader = new THREE.GLTFLoader(loadManager);
    const environmentURLs = [
        'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/fantasy_town.glb'
    ];

    (function tryLoadEnvironment(index) {
        if (index >= environmentURLs.length) {
            console.log('ℹ️ Environnement GLB indisponible, continuation sans décor 3D');
            return;
        }
        loader.load(environmentURLs[index], (gltf) => {
            environmentGLB = gltf.scene;
            environmentGLB.scale.set(100, 100, 100);
            environmentGLB.traverse((child) => {
                if (child.isMesh) {
                    child.receiveShadow = !isMobile;
                    child.castShadow = !isMobile;
                }
            });
            scene.add(environmentGLB);
            console.log('✅ Environnement GLB chargé');
        }, undefined, () => tryLoadEnvironment(index + 1));
    })(0);
}

// ------------------------------------------------------------ VAISSEAU (temporaire puis GLB)
function createAircraft(loadManager) {
    aircraft = createTempAircraft();
    aircraft.position.set(-100, 80, 0);
    aircraft.rotation.y = Math.PI;
    scene.add(aircraft);
    loadAircraftGLB(loadManager);
}

function createTempAircraft() {
    const g = new THREE.Group();
    const fuselage = new THREE.Mesh(
        new THREE.CylinderGeometry(0.5, 0.3, 8, 8),
        new THREE.MeshPhongMaterial({ color: 0xffffff })
    );
    fuselage.rotation.z = Math.PI / 2;
    g.add(fuselage);
    const wings = new THREE.Mesh(
        new THREE.BoxGeometry(10, 0.2, 2),
        new THREE.MeshPhongMaterial({ color: 0xcccccc })
    );
    g.add(wings);
    const tail = new THREE.Mesh(
        new THREE.BoxGeometry(1, 3, 0.2),
        new THREE.MeshPhongMaterial({ color: 0xffffff })
    );
    tail.position.set(-3, 1, 0);
    g.add(tail);
    return g;
}

function loadAircraftGLB(loadManager) {
    const loader = new THREE.GLTFLoader(loadManager);
    const aircraftURLs = [
        'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/cute_taxi_spaceship.glb'
    ];

    (function tryLoadGLB(index) {
        if (index >= aircraftURLs.length) {
            console.log('ℹ️ Modèle GLB indisponible, avion temporaire conservé');
            return;
        }
        loader.load(aircraftURLs[index], (gltf) => {
            const newAircraft = gltf.scene;
            const startPos = aircraft.position.clone();

            scene.remove(aircraft);
            newAircraft.scale.set(8, 8, 8);
            newAircraft.position.copy(startPos);
            newAircraft.rotation.set(0, Math.PI, 0);
            newAircraft.traverse((child) => {
                if (child.isMesh) { child.castShadow = !isMobile; child.receiveShadow = !isMobile; }
            });
            scene.add(newAircraft);
            aircraft = newAircraft;
            aircraftGLB = newAircraft;
            console.log('✅ Vaisseau GLB chargé');
        }, undefined, () => tryLoadGLB(index + 1));
    })(0);
}

// ------------------------------------------------------------ NUAGES DÉCORATIFS INSTANCIÉS (1 draw call)
const cloudData = [];
let cloudMesh = null;

function createDecorativeClouds() {
    const rand = mulberry32(7);
    const puffGeometry = new THREE.SphereGeometry(4, 6, 6);
    const puffMaterial = new THREE.MeshPhongMaterial({ color: 0xffffff, transparent: true, opacity: 0.3 });

    cloudMesh = new THREE.InstancedMesh(
        puffGeometry, puffMaterial, CONFIG.CLOUD_COUNT * CONFIG.PUFFS_PER_CLOUD
    );
    if (cloudMesh.instanceMatrix.setUsage) {
        cloudMesh.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
    }
    cloudMesh.frustumCulled = false; // les instances bougent : culling géré par le brouillard

    for (let c = 0; c < CONFIG.CLOUD_COUNT; c++) {
        const center = new THREE.Vector3(
            (rand() - 0.5) * 1500,
            rand() * 800 + 100,
            (rand() - 0.5) * 1500
        );
        const puffs = [];
        for (let p = 0; p < CONFIG.PUFFS_PER_CLOUD; p++) {
            puffs.push({
                x: (rand() - 0.5) * 10,
                y: (rand() - 0.5) * 6,
                z: (rand() - 0.5) * 10,
                s: rand() * 0.6 + 0.7
            });
        }
        cloudData.push({
            center,
            puffs,
            phase: rand() * Math.PI * 2,
            bobAmp: rand() * 2 + 1,
            bobSpeed: rand() * 0.3 + 0.15
        });
    }
    scene.add(cloudMesh);
}

function updateDecorativeClouds(time) {
    let i = 0;
    for (const cloud of cloudData) {
        const bobY = Math.sin(time * cloud.bobSpeed + cloud.phase) * cloud.bobAmp;
        for (const puff of cloud.puffs) {
            _dummy.position.set(cloud.center.x + puff.x, cloud.center.y + puff.y + bobY, cloud.center.z + puff.z);
            _dummy.rotation.set(0, 0, 0);
            _dummy.scale.setScalar(puff.s);
            _dummy.updateMatrix();
            cloudMesh.setMatrixAt(i++, _dummy.matrix);
        }
    }
    cloudMesh.instanceMatrix.needsUpdate = true;
}

// ------------------------------------------------------------ POIs (nuages colorés)
function createPointsOfInterest() {
    const colors = [0xab9ff2, 0x2575fc, 0x60d394, 0xee6055, 0xffff00];
    const geometries = [
        new THREE.OctahedronGeometry(6, 0),
        new THREE.TorusGeometry(4, 1.5, 8, 12),
        new THREE.ConeGeometry(5, 8, 6),
        new THREE.DodecahedronGeometry(5, 0)
    ];

    predefinedPOIs.forEach((poi, index) => {
        const poiColor = colors[index % colors.length];
        const group = new THREE.Group();

        const mainShape = new THREE.Mesh(
            geometries[Math.floor(index % geometries.length)],
            new THREE.MeshPhongMaterial({
                color: poiColor,
                emissive: poiColor,
                emissiveIntensity: 0.8,
                transparent: true,
                opacity: 0.9,
                shininess: 100
            })
        );
        group.add(mainShape);

        const pointLight = new THREE.PointLight(poiColor, 2, 50);
        pointLight.position.set(0, 3, 0);
        group.add(pointLight);

        const ring = new THREE.Mesh(
            new THREE.TorusGeometry(8, 0.3, 4, 24),
            new THREE.MeshBasicMaterial({ color: 0x32CD32, transparent: true, opacity: 0.6 })
        );
        ring.rotation.x = Math.PI / 2;
        group.add(ring);

        for (let i = 0; i < 8; i++) {
            const particle = new THREE.Mesh(
                new THREE.SphereGeometry(0.5, 4, 4),
                new THREE.MeshBasicMaterial({ color: 0x00ff88, transparent: true, opacity: 0.7 })
            );
            const angle = (i / 8) * Math.PI * 2;
            const radius = 10 + Math.random() * 5;
            particle.position.set(Math.cos(angle) * radius, Math.random() * 6 - 3, Math.sin(angle) * radius);
            group.add(particle);
        }

        group.position.copy(poi.position);
        group.userData = { poiInfo: poi, color: poiColor, phase: Math.random() * Math.PI * 2 };
        scene.add(group);
        pointsOfInterest.push(group);
    });
}

function updatePOIClouds(time) {
    for (const poiCloud of pointsOfInterest) {
        if (!poiCloud.visible) continue;
        const s = 1.2 + Math.sin(time * 2 + poiCloud.userData.phase) * 0.08;
        poiCloud.scale.setScalar(s);
    }
    // Pulsation des portails de liaison
    if (portalToLevel2 && portalToLevel2.visible) {
        portalToLevel2.rotation.z = time * 0.5;
        portalToLevel2.children[0].scale.setScalar(1 + Math.sin(time * 3) * 0.08);
    }
    if (returnPortal && returnPortal.visible) {
        returnPortal.rotation.z = time * 0.5;
        returnPortal.children[0].scale.setScalar(1 + Math.sin(time * 3) * 0.08);
    }
}

// ------------------------------------------------------------ GEMMES (InstancedMesh)
function generateGemPositions() {
    const rand = mulberry32(CONFIG.SEED);
    const positions = [];

    // Amas autour de chaque POI : le contenu du site guide l'exploration
    predefinedPOIs.forEach((poi) => {
        for (let i = 0; i < CONFIG.GEMS_PER_POI && positions.length < CONFIG.GEM_COUNT; i++) {
            const angle = rand() * Math.PI * 2;
            const radius = 30 + rand() * 90;
            const y = Math.max(20, Math.min(700, poi.position.y + (rand() - 0.5) * 90));
            positions.push(new THREE.Vector3(
                poi.position.x + Math.cos(angle) * radius,
                y,
                poi.position.z + Math.sin(angle) * radius
            ));
        }
    });

    // Dispersion dans tout le ciel : l'exploration libre paie aussi
    while (positions.length < CONFIG.GEM_COUNT) {
        positions.push(new THREE.Vector3(
            (rand() - 0.5) * 1600,
            20 + rand() * 620,
            (rand() - 0.5) * 1600
        ));
    }
    return positions;
}

function createGems() {
    gemBasePositions = generateGemPositions();
    gemPositions = gemBasePositions.map(p => p.clone());
    gemPhases = gemBasePositions.map((_, i) => i * 0.7 % (Math.PI * 2));
    gemScales = gemBasePositions.map(() =>
        CONFIG.GEM_SCALE_MIN + Math.random() * (CONFIG.GEM_SCALE_MAX - CONFIG.GEM_SCALE_MIN));

    const geometry = new THREE.OctahedronGeometry(1.6, 0);
    const material = new THREE.MeshPhongMaterial({
        color: 0x66e0ff,
        emissive: 0x22b8ff,
        emissiveIntensity: 0.9,
        shininess: 90
    });

    gemMesh = new THREE.InstancedMesh(geometry, material, CONFIG.GEM_COUNT);
    if (gemMesh.instanceMatrix.setUsage) {
        gemMesh.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
    }
    gemMesh.frustumCulled = false;

    // Restaurer les gemmes déjà collectées lors des sessions précédentes
    while (gemCollected.length < CONFIG.GEM_COUNT) gemCollected.push(false);
    for (let i = 0; i < CONFIG.GEM_COUNT; i++) {
        if (gemCollected[i]) hideGemInstance(i);
    }

    scene.add(gemMesh);
    console.log(`✅ ${CONFIG.GEM_COUNT} gemmes instanciées (1 draw call) — ${gemsCollectedCount} déjà collectées`);
}

function hideGemInstance(i) {
    _dummy.position.set(0, -99999, 0);
    _dummy.rotation.set(0, 0, 0);
    _dummy.scale.setScalar(0.0001);
    _dummy.updateMatrix();
    gemMesh.setMatrixAt(i, _dummy.matrix);
    gemMesh.instanceMatrix.needsUpdate = true;
}

function collectGem(i) {
    gemCollected[i] = true;
    gemsCollectedCount++;
    hideGemInstance(i);
    saveGamificationState();

    spawnParticleBurst(gemPositions[i], 14);
    playGemSound();
    updateGemsHUD(true);
    floatGemFeedback();

    if (gemsCollectedCount === CONFIG.GEM_COUNT) {
        unlockLevel2(); // persistant : le portail violet apparaît même si le popup est fermé sans cliquer
        setTimeout(showTrophyPopup, 400);
    }
}

function updateGems(time, delta) {
    if (!aircraft) return;
    const magnetR2 = CONFIG.GEM_MAGNET_RADIUS * CONFIG.GEM_MAGNET_RADIUS;
    const collectR2 = CONFIG.GEM_COLLECT_RADIUS * CONFIG.GEM_COLLECT_RADIUS;
    const magnetLerp = 1 - Math.exp(-5 * delta);

    gemMesh.material.emissiveIntensity = 0.7 + Math.sin(time * 2) * 0.25;

    for (let i = 0; i < CONFIG.GEM_COUNT; i++) {
        if (gemCollected[i]) continue;
        const p = gemPositions[i];
        const dSq = aircraft.position.distanceToSquared(p);

        if (dSq < magnetR2) {
            // Magnétisme : la gemme vole vers le vaisseau (confort zen)
            p.lerp(aircraft.position, magnetLerp);
            if (dSq < collectR2) { collectGem(i); continue; }
        } else {
            // Flottement doux autour de la position de base
            p.copy(gemBasePositions[i]);
            p.y += Math.sin(time * 1.3 + gemPhases[i]) * 1.5;
        }

        _dummy.position.copy(p);
        _dummy.rotation.set(Math.sin(time + gemPhases[i]) * 0.15, time * 0.8 + gemPhases[i], 0);
        _dummy.scale.setScalar(gemScales[i]);
        _dummy.updateMatrix();
        gemMesh.setMatrixAt(i, _dummy.matrix);
    }
    gemMesh.instanceMatrix.needsUpdate = true;
}

// ------------------------------------------------------------ PARTICULES (pool unique de THREE.Points)
const MAX_PARTICLES = 150;
let particlePoints = null;
let particleAttribute = null;
const particlePool = [];
let particleCursor = 0;

function createParticleSystem() {
    const positions = new Float32Array(MAX_PARTICLES * 3);
    for (let i = 0; i < MAX_PARTICLES; i++) {
        positions[i * 3 + 1] = -99999; // hors champ
        particlePool.push({ life: 0, ttl: 1, pos: new THREE.Vector3(), vel: new THREE.Vector3() });
    }
    const geometry = new THREE.BufferGeometry();
    particleAttribute = new THREE.BufferAttribute(positions, 3);
    geometry.setAttribute('position', particleAttribute);
    const material = new THREE.PointsMaterial({
        color: 0x8ff7ff,
        size: 2.5,
        transparent: true,
        opacity: 0.95,
        blending: THREE.AdditiveBlending,
        depthWrite: false,
        sizeAttenuation: true
    });
    particlePoints = new THREE.Points(geometry, material);
    particlePoints.frustumCulled = false;
    scene.add(particlePoints);
}

function spawnParticleBurst(origin, count) {
    for (let i = 0; i < count; i++) {
        const p = particlePool[particleCursor];
        particleCursor = (particleCursor + 1) % MAX_PARTICLES;
        p.pos.copy(origin);
        p.vel.set((Math.random() - 0.5) * 2.4, (Math.random() - 0.5) * 2.4, (Math.random() - 0.5) * 2.4);
        p.ttl = 0.6 + Math.random() * 0.5;
        p.life = p.ttl;
    }
}

function updateParticles(delta) {
    const arr = particleAttribute.array;
    for (let i = 0; i < MAX_PARTICLES; i++) {
        const p = particlePool[i];
        if (p.life <= 0) continue;
        p.life -= delta;
        if (p.life <= 0) { arr[i * 3 + 1] = -99999; continue; }
        p.pos.addScaledVector(p.vel, delta * 60);
        p.vel.multiplyScalar(1 - 2.5 * delta); // freinage doux
        arr[i * 3] = p.pos.x;
        arr[i * 3 + 1] = p.pos.y;
        arr[i * 3 + 2] = p.pos.z;
    }
    particleAttribute.needsUpdate = true;
}

// ------------------------------------------------------------ CONTRÔLES (clavier + drag unifié + tactile)
function setupControls() {
    document.addEventListener('keydown', (event) => {
        if (Object.prototype.hasOwnProperty.call(keys, event.key)) {
            keys[event.key] = true;
            lastUserAction = Date.now();
            event.preventDefault();
        }
    });
    document.addEventListener('keyup', (event) => {
        if (Object.prototype.hasOwnProperty.call(keys, event.key)) {
            keys[event.key] = false;
            lastUserAction = Date.now();
            event.preventDefault();
        }
    });

    // REGARD LIBRE : Pointer Events = souris + tactile en un seul code (fix du doublon V1)
    const canvas = renderer.domElement;
    canvas.addEventListener('pointerdown', (e) => {
        isDragging = true;
        isFreeLookMode = true;
        previousPointer = { x: e.clientX, y: e.clientY };
        canvas.style.cursor = 'grabbing';
        canvas.setPointerCapture && canvas.setPointerCapture(e.pointerId);
        lastUserAction = Date.now();
    });
    window.addEventListener('pointermove', (e) => {
        if (!isDragging) return;
        const rotationSpeed = isMobile ? 0.008 : 0.005;
        camera.rotation.y -= (e.clientX - previousPointer.x) * rotationSpeed;
        camera.rotation.x -= (e.clientY - previousPointer.y) * rotationSpeed;
        camera.rotation.x = Math.max(-Math.PI / 2, Math.min(Math.PI / 2, camera.rotation.x));
        previousPointer = { x: e.clientX, y: e.clientY };
        lastUserAction = Date.now();
    });
    const endDrag = () => {
        if (!isDragging) return;
        isDragging = false;
        isFreeLookMode = false;
        canvas.style.cursor = 'grab';
        lastUserAction = Date.now();
    };
    window.addEventListener('pointerup', endDrag);
    window.addEventListener('pointercancel', endDrag);
}

// -------- Contrôles tactiles (joystick + altitude)
const joystick = { active: false, startX: 0, startY: 0, currentX: 0, currentY: 0 };

function setupMobileControls() {
    createTouchInterface();
    if (isMobile) enableMobileMode();
    setupTouchEvents();
}

function createTouchInterface() {
    const controlsContainer = document.createElement('div');
    controlsContainer.id = 'mobile-controls';
    controlsContainer.style.cssText = 'position:fixed; bottom:20px; left:0; right:0; z-index:1000; pointer-events:none; display:none;';

    const joystickArea = document.createElement('div');
    joystickArea.innerHTML = `
        <div style="position:relative; width:120px; height:120px; margin-left:30px; pointer-events:auto;">
            <div id="joystick-base" style="width:80px; height:80px; background:rgba(255,255,255,0.2); border-radius:50%;
                 position:absolute; top:20px; left:20px; border:2px solid rgba(255,255,255,0.5); touch-action:none;"></div>
            <div id="joystick-handle" style="width:40px; height:40px; background:rgba(255,255,255,0.8); border-radius:50%;
                 position:absolute; top:40px; left:40px; transition:transform .1s;"></div>
        </div>`;

    const altitudeControls = document.createElement('div');
    altitudeControls.innerHTML = `
        <div style="display:flex; flex-direction:column; align-items:center; margin-right:30px; pointer-events:auto;">
            <button id="btn-up" style="width:60px; height:60px; background:rgba(0,0,0,0.4); border:none; border-radius:50%;
                    margin-bottom:10px; font-size:24px; color:white; touch-action:none;">⬆</button>
            <button id="btn-down" style="width:60px; height:60px; background:rgba(0,0,0,0.4); border:none; border-radius:50%;
                    font-size:24px; color:white; touch-action:none;">⬇</button>
        </div>`;

    const mainControls = document.createElement('div');
    mainControls.style.cssText = 'display:flex; justify-content:space-between; align-items:flex-end; padding:0 20px;';
    mainControls.appendChild(joystickArea);
    mainControls.appendChild(altitudeControls);
    controlsContainer.appendChild(mainControls);
    document.body.appendChild(controlsContainer);
}

function setupTouchEvents() {
    const joystickBase = byId('joystick-base');
    const joystickHandle = byId('joystick-handle');
    if (!joystickBase) return;

    joystickBase.addEventListener('touchstart', (e) => {
        e.preventDefault();
        const rect = joystickBase.getBoundingClientRect();
        joystick.active = true;
        joystick.startX = rect.left + rect.width / 2;
        joystick.startY = rect.top + rect.height / 2;
        joystick.currentX = e.touches[0].clientX;
        joystick.currentY = e.touches[0].clientY;
        updateJoystick(joystickHandle);
        lastUserAction = Date.now();
    }, { passive: false });

    document.addEventListener('touchmove', (e) => {
        if (!joystick.active) return;
        e.preventDefault();
        joystick.currentX = e.touches[0].clientX;
        joystick.currentY = e.touches[0].clientY;
        updateJoystick(joystickHandle);
        lastUserAction = Date.now();
    }, { passive: false });

    document.addEventListener('touchend', () => {
        if (!joystick.active) return;
        joystick.active = false;
        joystickHandle.style.transform = 'translate(0px, 0px)';
        keys['forward'] = keys['backward'] = keys['left'] = keys['right'] = false;
    });

    // Boutons d'altitude en Pointer Events (plus fiable que touchstart/touchend)
    [['btn-up', 'up'], ['btn-down', 'down']].forEach(([id, dir]) => {
        const btn = byId(id);
        if (!btn) return;
        const press = (e) => { e.preventDefault(); keys[dir] = true; btn.style.background = 'rgba(211,211,211,0.3)'; lastUserAction = Date.now(); };
        const release = (e) => { e.preventDefault(); keys[dir] = false; btn.style.background = 'rgba(0,0,0,0.4)'; };
        btn.addEventListener('pointerdown', press);
        btn.addEventListener('pointerup', release);
        btn.addEventListener('pointerleave', release);
        btn.addEventListener('pointercancel', release);
    });
}

function updateJoystick(joystickHandle) {
    const deltaX = joystick.currentX - joystick.startX;
    const deltaY = joystick.currentY - joystick.startY;
    const maxDistance = 35;
    const distance = Math.min(Math.sqrt(deltaX * deltaX + deltaY * deltaY), maxDistance);
    const angle = Math.atan2(deltaY, deltaX);

    joystickHandle.style.transform = `translate(${Math.cos(angle) * distance}px, ${Math.sin(angle) * distance}px)`;

    const deadZone = 15;
    keys['forward'] = deltaY < -deadZone;
    keys['backward'] = deltaY > deadZone;
    keys['left'] = deltaX < -deadZone;
    keys['right'] = deltaX > deadZone;
}

function enableMobileMode() {
    const mobileControls = byId('mobile-controls');
    if (mobileControls) mobileControls.style.display = 'block';
    showToast('🕹️ Joystick : se déplacer · Boutons : altitude', 4000);
}

// ------------------------------------------------------------ BOUCLE DE VOL (delta-time corrigé)
function updateAircraft(delta) {
    if (!aircraft || !experienceStarted) return;

    const input = {
        forward: keys[' '] || keys['forward'],
        back: keys['s'] || keys['S'] || keys['backward'],
        up: keys['ArrowUp'] || keys['up'],
        down: keys['ArrowDown'] || keys['down'],
        left: keys['ArrowLeft'] || keys['left'],
        right: keys['ArrowRight'] || keys['right']
    };
    const hasInput = input.forward || input.back || input.up || input.down || input.left || input.right;
    if (hasInput) lastUserAction = Date.now();

    // Vitesse cible puis amortissement exponentiel — indépendant du framerate
    let targetSpeed = 0;
    if (input.forward) targetSpeed = CONFIG.MAX_SPEED;
    else if (input.back) targetSpeed = -CONFIG.MAX_SPEED * CONFIG.REVERSE_FACTOR;
    aircraftSpeed = damp(aircraftSpeed, targetSpeed, 2.5, delta);

    let targetPitch = 0, targetRoll = 0, turn = 0, vertical = 0;
    if (input.up) { targetPitch = -CONFIG.PITCH; vertical = CONFIG.VERTICAL_SPEED; }
    if (input.down) { targetPitch = CONFIG.PITCH; vertical = -CONFIG.VERTICAL_SPEED; }
    if (input.left) { targetRoll = CONFIG.ROLL; turn = CONFIG.TURN_SPEED; }
    if (input.right) { targetRoll = -CONFIG.ROLL; turn = -CONFIG.TURN_SPEED; }

    // Repos zen : flottement doux après 5 s sans action
    if (!hasInput && Date.now() - lastUserAction > 5000) {
        vertical = Math.sin(clock.elapsedTime * 1.2) * 0.12;
    }

    aircraft.rotation.z = damp(aircraft.rotation.z, targetRoll, 4, delta);
    aircraft.rotation.x = damp(aircraft.rotation.x, targetPitch, 3, delta);
    aircraft.rotation.y += turn * delta * 60;

    _dir.set(0, 0, -1).applyQuaternion(aircraft.quaternion);
    aircraft.position.addScaledVector(_dir, aircraftSpeed * delta * 60);
    aircraft.position.y = Math.max(aircraft.position.y + vertical * delta * 60, CONFIG.MIN_ALTITUDE);

    // Limites du monde (douceur : on reste dans le décor)
    aircraft.position.x = THREE.MathUtils.clamp(aircraft.position.x, -CONFIG.WORLD_BOUND, CONFIG.WORLD_BOUND);
    aircraft.position.z = THREE.MathUtils.clamp(aircraft.position.z, -CONFIG.WORLD_BOUND, CONFIG.WORLD_BOUND);

    updateCamera(delta);
    if (currentLevel === 1) {
        checkPOIProximity();
        checkPortalToLevel2();
    } else {
        checkReturnPortal();
        checkRingProximity();
    }
    updateHUD();
}

function updateCamera(delta) {
    if (!aircraft) return;
    // Lissage exponentiel : identique à 30, 60 ou 144 fps
    const lerpFactor = 1 - Math.exp(-8 * delta);

    if (isFreeLookMode) {
        // Mode regard libre : la caméra garde son orientation mais suit le vaisseau
        _camOffset.set(0, 4, 10).applyEuler(camera.rotation);
        _camTarget.copy(aircraft.position).add(_camOffset);
        camera.position.lerp(_camTarget, lerpFactor);
        camera.lookAt(aircraft.position);
    } else {
        // Mode normal : caméra derrière le vaisseau
        _camOffset.set(0, 4, 10).applyQuaternion(aircraft.quaternion);
        _camTarget.copy(aircraft.position).add(_camOffset);
        camera.position.lerp(_camTarget, lerpFactor);

        _lookTarget.set(0, 0, -15).applyQuaternion(aircraft.quaternion).add(aircraft.position);
        camera.lookAt(_lookTarget);
    }
}

function checkPOIProximity() {
    if (!aircraft || currentLevel !== 1) return;

    let nearestPoi = null;
    let minDistance = Infinity;
    const trigger2 = CONFIG.POI_TRIGGER_DISTANCE * CONFIG.POI_TRIGGER_DISTANCE;

    for (const poiCloud of pointsOfInterest) {
        const dSq = aircraft.position.distanceToSquared(poiCloud.position);
        if (dSq < trigger2 && dSq < minDistance) {
            minDistance = dSq;
            nearestPoi = poiCloud.userData.poiInfo;
        }
    }

    if (nearestPoi && nearestPoi !== activePoi) {
        activePoi = nearestPoi;
        setText('target-display', nearestPoi.title,
            'position:fixed; top:12px; left:50%; transform:translateX(-50%); padding:8px 20px; background:rgba(30,30,40,0.88);' +
            'color:#f1f1f1; border-radius:13px; z-index:1001; font-weight:bold;');
        openCard(nearestPoi.id + '-card');
    } else if (!nearestPoi && activePoi) {
        activePoi = null;
        setText('target-display', 'Exploration', '');
        if (currentCard) closeCard(currentCard);
    }
}

// ------------------------------------------------------------ HUD
function setText(id, text, fallbackCss) {
    let el = byId(id);
    if (!el) {
        el = document.createElement('div');
        el.id = id;
        el.style.cssText = fallbackCss || '';
        document.body.appendChild(el);
    }
    if (el.textContent !== text) el.textContent = text;
    return el;
}

function updateHUD() {
    if (!aircraft) return;
    const speed = Math.abs(Math.round(aircraftSpeed * 300));
    const altitude = Math.round(aircraft.position.y);
    setText('speed-display', speed + ' km/h',
        'position:fixed; bottom:20px; left:16px; padding:8px 16px; background:rgba(30,30,40,0.88); color:#f1f1f1;' +
        'border-radius:13px; z-index:1001; font-weight:bold;');
    setText('altitude-display', altitude + ' m',
        'position:fixed; bottom:20px; left:130px; padding:8px 16px; background:rgba(30,30,40,0.88); color:#f1f1f1;' +
        'border-radius:13px; z-index:1001; font-weight:bold;');
}

function ensureGemsHUD() {
    let hud = byId('gems-hud');
    if (!hud) {
        hud = document.createElement('div');
        hud.id = 'gems-hud';
        hud.style.cssText = 'position:fixed; top:12px; right:14px; padding:10px 18px; background:rgba(30,30,40,0.88);' +
            'color:#f1f1f1; border-radius:13px; z-index:1001; font-weight:bold; min-width:170px;';
        hud.innerHTML = `
            <div id="gems-count" style="font-size:1.05em;">💎 0 / ${CONFIG.GEM_COUNT}</div>
            <div style="width:100%; height:6px; background:rgba(255,255,255,0.15); border-radius:4px; margin:7px 0 6px; overflow:hidden;">
                <div id="gems-bar" style="width:0%; height:100%; background:linear-gradient(90deg,#2575fc,#66e0ff); border-radius:4px; transition:width .4s ease;"></div>
            </div>
            <div id="score-line" style="font-size:0.85em; opacity:0.85;">Score : 0 💎</div>`;
        document.body.appendChild(hud);
    }
    return hud;
}

function updateGemsHUD(pulse) {
    ensureGemsHUD();
    const countEl = byId('gems-count');
    const barEl = byId('gems-bar');
    const scoreEl = byId('score-line');
    if (countEl) countEl.textContent = `💎 ${gemsCollectedCount} / ${CONFIG.GEM_COUNT}`;
    if (barEl) barEl.style.width = (gemsCollectedCount / CONFIG.GEM_COUNT * 100).toFixed(1) + '%';
    if (scoreEl) scoreEl.textContent = `Score : ${userPoints} 💎`;
    if (pulse && countEl && countEl.animate) {
        countEl.animate(
            [{ transform: 'scale(1.25)', color: '#9ff7ff' }, { transform: 'scale(1)' }],
            { duration: 280, easing: 'ease-out' }
        );
    }
}

function floatGemFeedback() {
    const el = document.createElement('div');
    el.textContent = '💎 +1';
    el.style.cssText = 'position:fixed; top:24%; right:24%; font-size:1.4em; font-weight:600; color:#9ff7ff;' +
        'text-shadow:0 2px 18px #222; z-index:1500; transition:all 1.4s cubic-bezier(.2,1.2,.2,1);';
    document.body.appendChild(el);
    setTimeout(() => {
        el.style.transform = 'translateY(-90px)';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 600);
    }, 60);
}

// ------------------------------------------------------------ UI (menu, cartes, formulaires)
function setupUI() {
    // Liste des POIs dans le menu
    const poiList = byId('poi-list');
    if (poiList) {
        predefinedPOIs.forEach(poi => {
            const el = document.createElement('div');
            el.style.cssText = 'background:rgba(255,255,255,0.05); padding:10px; margin:5px 0; border-radius:5px; cursor:pointer; transition:all .3s;';
            el.innerHTML = `<strong>${poi.title}</strong><br><small style="opacity:0.7">${poi.description}</small>`;
            el.addEventListener('click', () => navigateToPOI(poi.id));
            el.addEventListener('mouseenter', () => { el.style.background = 'rgba(255,170,0,0.2)'; });
            el.addEventListener('mouseleave', () => { el.style.background = 'rgba(255,255,255,0.05)'; });
            poiList.appendChild(el);
        });
    }

    // Formulaire de contact (si présent)
    const form = document.querySelector('.contact-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            alert('Message envoyé ! Merci pour votre contact.');
            closeCard('contact-card');
        });
    }

    // Galerie : liens en nouvel onglet
    document.querySelectorAll('.gallery-item a').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            window.open(this.href, '_blank');
        });
    });

    setupGLBUpload();
}

function toggleMenu() {
    const menu = byId('menu-panel');
    if (menu) menu.classList.toggle('active');
}

function navigateToPOI(poiId) {
    const poi = predefinedPOIs.find(p => p.id === poiId);
    if (poi && aircraft) {
        aircraft.position.copy(poi.position);
        aircraft.position.x += 30;
        aircraft.rotation.y = Math.PI;
        toggleMenu();
        openCard(poiId + '-card');
    }
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
    const poiId = cardId.replace(/-card$/, '');
    const poi = predefinedPOIs.find(p => p.id === poiId);

    const card = byId(cardId);
    if (card) {
        card.style.display = 'block';
        currentCard = cardId;
    } else if (poi) {
        // Fallback : pas de carte HTML → toast, l'expérience ne casse jamais
        showToast(`📍 ${poi.title} — ${poi.description}`, 3200);
    }

    addPointsForPOI(poiId);
    completeMission(poiId);
}

function closeCard(cardId) {
    const card = byId(cardId);
    if (card) card.style.display = 'none';
    currentCard = null;
}

// Upload d'un vaisseau GLB personnalisé
function setupGLBUpload() {
    const fileInput = byId('glb-file');
    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) loadCustomAircraftGLB(URL.createObjectURL(file));
        });
    }
}

function loadCustomAircraftGLB(url) {
    const loader = new THREE.GLTFLoader();
    loader.load(url, (gltf) => {
        if (aircraftGLB) scene.remove(aircraftGLB);
        if (aircraft && aircraft !== aircraftGLB) scene.remove(aircraft);

        const startPos = aircraft ? aircraft.position.clone() : new THREE.Vector3(-100, 80, 0);
        aircraftGLB = gltf.scene;
        aircraftGLB.position.copy(startPos);
        aircraftGLB.rotation.set(0, Math.PI, 0);

        // Échelle automatique : ~10 unités max
        const box = new THREE.Box3().setFromObject(aircraftGLB);
        const size = box.getSize(new THREE.Vector3());
        const scale = 10 / Math.max(size.x, size.y, size.z, 0.001);
        aircraftGLB.scale.setScalar(scale);
        aircraftGLB.traverse((child) => {
            if (child.isMesh) { child.castShadow = !isMobile; child.receiveShadow = !isMobile; }
        });

        scene.add(aircraftGLB);
        aircraft = aircraftGLB;
        console.log('✅ Vaisseau personnalisé chargé');
    }, undefined, () => {
        console.error('❌ Erreur chargement vaisseau personnalisé');
        alert('Erreur lors du chargement du fichier GLB');
    });
}

// ------------------------------------------------------------ BOUCLE PRINCIPALE
function animate() {
    requestAnimationFrame(animate);
    // Clamp du delta : pas de saut de physique après un onglet caché
    const delta = Math.min(clock.getDelta(), 0.05);
    const time = clock.elapsedTime;

    updateAircraft(delta);
    if (currentLevel === 1) updateGems(time, delta);
    updateParticles(delta);
    if (cloudMesh && cloudMesh.visible) updateDecorativeClouds(time);
    updatePOIClouds(time);
    if (currentLevel === 2) updateLevel2(time);

    renderer.render(scene, camera);
}

// ------------------------------------------------------------ INIT
function init() {
    if (typeof THREE === 'undefined') {
        document.body.innerHTML = '<p style="color:#fff; padding:40px; font-family:sans-serif;">Three.js n\'est pas chargé — vérifie le script CDN.</p>';
        return;
    }
    console.log('🚀 Initialisation 3D V2 — Phase 1 « Collectathon zen »...');

    createLoadingOverlay();
    loadGamificationState();

    const loadManager = new THREE.LoadingManager();
    loadManager.onProgress = (url, loaded, total) => {
        const barEl = byId('v2-loading-bar');
        if (barEl && total > 0) barEl.style.width = Math.round(loaded / total * 100) + '%';
    };
    loadManager.onLoad = () => setTimeout(hideLoadingOverlay, 300);
    // Garde-fou si onLoad ne se déclenche jamais (asset en échec silencieux)
    setTimeout(hideLoadingOverlay, 9000);

    setupThreeJS(loadManager);
    setupUI();
    createScene(loadManager);
    if (level2Unlocked) createPortalToLevel2();
    setupControls();
    setupMobileControls();
    initAudio();
    ensureGemsHUD();
    updateGemsHUD();

    animate();
    console.log('✅ Monde V2 prêt — gemmes, missions et ciel chargés');
}

init();