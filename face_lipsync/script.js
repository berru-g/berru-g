// =============================================
// INITIALISATION THREE.JS - SCÈNE 3D
// =============================================

// Récupère l'élément canvas HTML où la scène 3D sera affichée
const canvas = document.getElementById("scene");

// Crée la scène Three.js et définit la couleur de fond (noir)
const scene = new THREE.Scene();
scene.background = new THREE.Color(0x111111);

// Configure la caméra perspective
// - 45° : angle de vision
// - window.innerWidth/window.innerHeight : ratio d'aspect
// - 0.1 : distance de rendu minimale
// - 100 : distance de rendu maximale
const camera = new THREE.PerspectiveCamera(45, window.innerWidth/window.innerHeight, 0.1, 100);
camera.position.set(0, 1.6, 3); // Position de la caméra (x, y, z)

// Initialise le moteur de rendu WebGL
const renderer = new THREE.WebGLRenderer({ 
    canvas: canvas, 
    antialias: true // Active l'antialiasing pour des bords plus lisses
});
renderer.setSize(window.innerWidth, window.innerHeight); // Taille du rendu
renderer.setPixelRatio(window.devicePixelRatio); // Adapte à la densité de pixels de l'écran

// =============================================
// ÉCLAIRAGE DE LA SCÈNE
// =============================================

// Lumière ambiante (éclaire uniformément toute la scène)
const ambientLight = new THREE.AmbientLight(0x404040, 0.6);
scene.add(ambientLight);

// Lumière directionnelle (simule le soleil)
const directionalLight = new THREE.DirectionalLight(0xffffff, 1.0);
directionalLight.position.set(2, 5, 3); // Position de la lumière
scene.add(directionalLight);

// =============================================
// VARIABLES GLOBALES
// =============================================

let avatar;      // Référence vers le modèle 3D de l'avatar
let isSpeaking = false; // État pour savoir si l'avatar est en train de parler

// =============================================
// CHARGEMENT DE L'AVATAR 3D
// =============================================

// Chargeur pour les fichiers GLTF/GLB (format 3D)
const loader = new THREE.GLTFLoader();

// URL du modèle ReadyPlayerMe (remplace par ton avatar)
const avatarURL = "https://models.readyplayer.me/691732d6fa1ea12f834e291b.glb";

// Charge l'avatar depuis l'URL
loader.load(
    avatarURL,
    // Fonction appelée si le chargement réussit
    (gltf) => {
        avatar = gltf.scene; // Récupère le modèle 3D
        
        // Positionne et redimensionne l'avatar
        avatar.position.set(0, -1.5, 0); // Descend l'avatar pour le centrer
        avatar.scale.set(1.8, 1.8, 1.8); // Agrandit l'avatar
        
        // Ajoute l'avatar à la scène
        scene.add(avatar);
        document.getElementById("status").textContent = "Avatar chargé ! Parlez-lui !";
        console.log("✅ Avatar chargé avec succès !");
        notify("Avatar chargé !", "success");
    },
    // Fonction de progression (non utilisée ici)
    undefined,
    // Fonction appelée en cas d'erreur
    (err) => {
        console.error("❌ Erreur chargement avatar :", err);
        document.getElementById("status").textContent = "Erreur de chargement de l'avatar";
    }
);

// =============================================
// CONFIGURATION DES VOIX DE SYNTHÈSE VOCALE
// =============================================

// Dictionnaire des différentes voix disponibles
const VOICES = {
    // 🧔 VOIX MASCULINE GRAVE (voix par défaut)
    MASCULINE: { rate: 1.0, pitch: 0.7, name: "Masculine Grave" },
    
    // 👨 VOIX MASCULINE NORMALE
    MASCULINE_NORMAL: { rate: 1.0, pitch: 1.0, name: "Masculine Normale" },
    
    // 👩 VOIX FÉMININE DOUCE
    FEMININE_SOFT: { rate: 1.1, pitch: 1.3, name: "Féminine Douce" },
    
    // 🤖 VOIX ROBOTIQUE
    ROBOT: { rate: 0.85, pitch: 0.5, name: "Robotique" },
    
    // 🎭 VOIX JOYEUSE
    HAPPY: { rate: 1.2, pitch: 1.1, name: "Joyeuse" },
    
    // 🐢 VOIX LENTE ET CALME
    CALM: { rate: 0.8, pitch: 0.9, name: "Calme" },
    
    // ⚡ VOIX RAPIDE ET DYNAMIQUE
    ENERGETIC: { rate: 1.3, pitch: 1.0, name: "Énergique" }
};

// Variable stockant la voix actuellement utilisée
let currentVoice = VOICES.ENERGETIC;

// =============================================
// FONCTION DE SYNTHÈSE VOCALE
// =============================================

/**
 * Convertit un texte en parole using la synthèse vocale du navigateur
 * @param {string} text - Le texte à prononcer
 * @returns {Promise} Une promesse résolue quand la parole est terminée
 */
function speakText(text) {
    // Vérifie que la synthèse vocale est supportée par le navigateur
    if (!window.speechSynthesis) {
        console.error("❌ Synthèse vocale non supportée par ce navigateur");
        notifyError("Erreur chargement Synthèse vocale");
        return Promise.resolve();
    }
    
    return new Promise((resolve) => {
        // Crée un nouvel énoncé vocal
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = "fr-FR"; // Langue française
        
        // Applique les paramètres de la voix sélectionnée
        utterance.rate = currentVoice.rate;   // Vitesse de parole
        utterance.pitch = currentVoice.pitch; // Hauteur de la voix
        utterance.volume = 1.0;               // Volume maximum
        
        console.log(`🎤 Voix: ${currentVoice.name} | Texte: "${text}"`);
        
        // Événement déclenché quand la parole commence
        utterance.onstart = () => {
            isSpeaking = true;
            document.getElementById("status").textContent = `L'avatar parle... (${currentVoice.name})`;
        };
        
        // Événement déclenché quand la parole se termine
        utterance.onend = () => {
            isSpeaking = false;
            document.getElementById("status").textContent = "En attente de message...";
            resolve(); // Résoud la promesse
        };
        
        // Événement en cas d'erreur
        utterance.onerror = (error) => {
            console.error("❌ Erreur synthèse vocale:", error);
            notifyError("Erreur chargement Voice");
            isSpeaking = false;
            resolve(); // Résoud quand même la promesse
        };
        
        // Démarre la synthèse vocale
        window.speechSynthesis.speak(utterance);
    });
}

// =============================================
// FONCTIONS POUR TESTER ET CHANGER LES VOIX
// =============================================

/**
 * Teste toutes les voix disponibles dans l'ordre
 * Chaque voix prononce un texte de démonstration
 */
function testerToutesLesVoix() {
    console.log("🎵 DÉMARRAGE DU TEST COMPLET DES VOIX...");
    
    // Liste ordonnée des voix à tester
    const voixATester = [
        { key: 'MASCULINE', text: 'Je suis la voix masculine grave, assez grave pour vous ?' },
        { key: 'MASCULINE_NORMAL', text: 'Je suis la voix masculine normale, plutôt standard.' },
        { key: 'FEMININE_SOFT', text: 'Je suis la voix féminine douce, elle vous plaît ?' },
        { key: 'ROBOT', text: 'Je suis la voix robotique, bip boup.' },
        { key: 'HAPPY', text: 'Je suis la voix joyeuse, super contente de vous parler !' },
        { key: 'CALM', text: 'Je suis la voix calme et lente, très relaxante.' },
        { key: 'ENERGETIC', text: 'Je suis la voix énergique, rapide et dynamique !' }
    ];
    
    // Teste chaque voix séquentiellement avec un délai
    voixATester.forEach((voix, index) => {
        setTimeout(() => {
            currentVoice = VOICES[voix.key];
            console.log(`🔊 Test ${index + 1}/7: ${currentVoice.name}`);
            speakText(voix.text);
        }, index * 5000); // 5 secondes entre chaque voix
    });
}

/**
 * Change la voix actuelle et teste immédiatement
 * @param {string} nouvelleVoix - Clé de la voix dans VOICES
 * @returns {string|null} Nom de la voix ou null si non trouvée
 */
function changerVoix(nouvelleVoix) {
    if (VOICES[nouvelleVoix]) {
        currentVoice = VOICES[nouvelleVoix];
        console.log(`✅ Voix changée: ${currentVoice.name}`);
        
        // Teste immédiatement la nouvelle voix
        speakText(`Voix changée pour ${currentVoice.name}`);
        
        return currentVoice.name;
    } else {
        console.warn("❌ Voix non trouvée. Voix disponibles:", Object.keys(VOICES));
        return null;
    }
}

/*
// =============================================
// INTÉGRATION CHATGPT (À DÉBLOQUER PLUS TARD)
// =============================================

/**
 * Envoie le message à ChatGPT et retourne la réponse
 * @param {string} message - Message de l'utilisateur
 * @returns {string} Réponse de ChatGPT
 
async function sendToAI(message) {
    document.getElementById("status").textContent = "L'avatar réfléchit...";
    
    // =============================================
    // DÉCOMMENTEZ CETTE SECTION POUR ACTIVER CHATGPT
    // =============================================
    
    try {
        // Remplacez 'VOTRE_CLE_API' par votre clé OpenAI
        const apiKey = 'VOTRE_CLE_API';
        
        const response = await fetch('https://api.openai.com/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${apiKey}`
            },
            body: JSON.stringify({
                model: "gpt-3.5-turbo",  // ou "gpt-4" pour plus de puissance
                messages: [
                    {
                        role: "system", 
                        content: "Tu es un assistant vocal 3D friendly et enthousiaste. Tes réponses doivent être concises (max 2 phrases) et naturelles à l'oral."
                    },
                    {
                        role: "user", 
                        content: message
                    }
                ],
                max_tokens: 100,        // Limite la longueur des réponses
                temperature: 0.7        // Contrôle la créativité (0-1)
            })
        });
        
        if (!response.ok) {
            throw new Error(`Erreur API: ${response.status}`);
        }
        
        const data = await response.json();
        const reponseChatGPT = data.choices[0].message.content;
        
        console.log("🤖 Réponse ChatGPT:", reponseChatGPT);
        return reponseChatGPT;
        
    } catch (error) {
        console.error("❌ Erreur ChatGPT:", error);
        return "Désolé, je rencontre des difficultés techniques. Pouvez-vous répéter ?";
    }
    
    
    // =============================================
    // SIMULATION (À SUPPRIMER QUAND CHATGPT EST ACTIVÉ)
    // =============================================
    
    // Simulation du délai de traitement
    const delai = 800 + Math.random() * 800;
    await new Promise(resolve => setTimeout(resolve, delai));
    
    // Réponses simulées (à supprimer quand ChatGPT est activé)
    const reponsesSimulation = [
        "Bonjour ! Je suis votre assistant virtuel en 3D. Bienvenue !",
        "Actuellement en mode simulation, mais bientôt je serai connecté à ChatGPT !",
        "Pour toute information, contactez mon créateur sur gael-berru.com.",
        "La connexion ChatGPT sera disponible prochainement.",
        "N'hésitez pas à tester les différentes voix disponibles en attendant.",
        "Merci pour votre visite et à bientôt pour l'intelligence artificielle !"
    ];
    
    // Sélectionne la réponse dans l'ordre
    const reponse = reponsesSimulation[compteurReponses % reponsesSimulation.length];
    compteurReponses++;
    
    console.log("🤖 Réponse simulation:", reponse);
    return reponse;
}

// =============================================
// INSTRUCTIONS POUR ACTIVER CHATGPT
// =============================================

console.log("🚀 POUR ACTIVER CHATGPT :");
console.log("1. ✅ Obtenez une clé API sur https://platform.openai.com/api-keys");
console.log("2. 🔧 Décommentez la section ChatGPT dans la fonction sendToAI");
console.log("3. 🔑 Remplacez 'VOTRE_CLE_API' par votre vraie clé");
console.log("4. 🗑️ Supprimez la section SIMULATION");
console.log("5. 💾 Sauvegardez et rechargez la page");
*/
// =============================================
// SIMULATION en attendant l'ia
// =============================================

// Compteur pour suivre l'ordre des réponses
let compteurReponses = 0;

// Liste des réponses dans l'ordre (plus naturelles)
const reponsesOrdonnees = [
    "Bonjour ! Je suis votre assistant virtuel en 3D. Bienvenue ! Actuellement en phase de test, je ne peux pas répondre à vos questions pour le moment.",
    "Pour toute information, contactez mon créateur sur gael-berru.com.",
    "La synthèse vocale fonctionne correctement, c'est prometteur !",
    "N'hésitez pas à tester les différentes voix disponibles dans la console en attendant l\'ia. Merci pour votre visite et à bientôt pour plus de fonctionnalités."
];

/**
 * Simule une réponse d'IA (dans l'ordre défini)
 * @param {string} message - Message de l'utilisateur
 * @returns {string} Réponse de l'assistant
 */
async function sendToAI(message) {
    document.getElementById("status").textContent = "L'avatar réfléchit...";
    
    // Simule un temps de traitement (800ms - 1600ms)
    const delai = 800 + Math.random() * 800;
    await new Promise(resolve => setTimeout(resolve, delai));
    
    // Sélectionne la réponse dans l'ordre, puis recommence
    const reponse = reponsesOrdonnees[compteurReponses];
    compteurReponses = (compteurReponses + 1) % reponsesOrdonnees.length;
    
    console.log(`🤖 Réponse ${compteurReponses}/${reponsesOrdonnees.length}: ${reponse}`);
    return reponse;
}

// =============================================
// GESTION DE L'INTERFACE UTILISATEUR
// =============================================

// Gestion du clic sur le bouton "Envoyer"
document.getElementById("send").onclick = async () => {
    const input = document.getElementById("prompt");
    const text = input.value.trim();
    
    // Ignore si le champ est vide
    if (!text) return;
    
    console.log("✉️ Message utilisateur:", text);
    input.value = ""; // Vide le champ après envoi
    
    try {
        // Obtient une réponse de l'IA et la prononce
        const aiResponse = await sendToAI(text);
        await speakText(aiResponse);
    } catch (error) {
        console.error("❌ Erreur:", error);
        document.getElementById("status").textContent = "Erreur de communication";
    }
};

// Gestion de la touche Entrée dans le champ texte
document.getElementById("prompt").addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
        document.getElementById("send").click();
    }
});

// =============================================
// BOUCLE D'ANIMATION PRINCIPALE
// =============================================

/**
 * Boucle d'animation principale (appelée 60 fois par seconde)
 * Gère le rendu et les animations
 */
function animate() {
    requestAnimationFrame(animate); // Planifie la prochaine frame
    
    // Animation de rotation lente de l'avatar (seulement quand il ne parle pas)
    if (avatar && !isSpeaking) {
        //avatar.rotation.y += 0.005; // Rotation très lente
    }
    
    // Rend la scène avec la caméra
    renderer.render(scene, camera);
}

// Démarre la boucle d'animation
animate();


// =============================================
// GESTION DU REDIMENSIONNEMENT DE LA FENÊTRE
// =============================================

// Adapte la scène quand la fenêtre est redimensionnée
window.addEventListener("resize", () => {
    camera.aspect = window.innerWidth / window.innerHeight; // Nouveau ratio
    camera.updateProjectionMatrix(); // Met à jour la caméra
    renderer.setSize(window.innerWidth, window.innerHeight); // Redimensionne le rendu
});

// =============================================
// INSTRUCTIONS POUR L'UTILISATEUR
// =============================================

// Affiche les instructions dans la console au chargement
console.log("🎮 INSTRUCTIONS D'UTILISATION :");
console.log("==========================================");
console.log("1. 💬 Tapez un message et cliquez 'Envoyer' pour parler à l'avatar");
console.log("2. 🎵 Pour tester TOUTES les voix, tapez : testerToutesLesVoix()");
console.log("3. 🔄 Pour changer de voix, tapez : changerVoix('NOM_DE_LA_VOIX')");
console.log("");
console.log("📢 VOIX DISPONIBLES :");
Object.keys(VOICES).forEach(key => {
    console.log(`   - changerVoix('${key}')  →  ${VOICES[key].name}`);
});
console.log("");
console.log("🎯 Exemples :");
console.log("   changerVoix('FEMININE_SOFT')  → Voix féminine douce");
console.log("   changerVoix('ROBOT')          → Voix robotique");
console.log("   changerVoix('HAPPY')          → Voix joyeuse");
console.log("");
console.log("🔄 Les réponses sont maintenant dans l'ordre défini !");

// Test automatique de la voix actuelle après le chargement
setTimeout(() => {
    console.log(`🔊 Voix actuelle: ${currentVoice.name}`);
}, 1000);

// ----------------------------
// NOTIFY (system fallback -> in-page toast) 
// ----------------------------

/**
 * Create an in-page toast (used as fallback)
 * @param {string} message
 * @param {"success"|"warn"|"error"|"info"} type
 */
function _createToastFallback(message, type = "info") {
    const container = document.getElementById("toast-container");
    if (!container) return;

    const t = document.createElement("div");
    t.className = "toast " + (type || "info");

    let icon = "fa-circle-info";
    if (type === "success") icon = "fa-check-circle";
    if (type === "error") icon = "fa-times-circle";
    if (type === "warn") icon = "fa-triangle-exclamation";

    t.innerHTML = `<i class="fa-solid ${icon} icon"></i><span>${message}</span>`;
    container.appendChild(t);

    // play sound if exists and allowed
    const audio = document.getElementById("notify-sound");
    if (audio) {
        try { audio.currentTime = 0; audio.play().catch(()=>{}); } catch(e){ }
    }

    // auto-remove
    setTimeout(() => {
        t.style.animation = "toast-out 0.35s forwards";
        setTimeout(() => t.remove(), 350);
    }, 3000);
}

/**
 * Display a notification: tries system Notification, falls back to in-page toast
 * @param {string} message
 * @param {"success"|"warn"|"error"|"info"} [type]
 * @param {Object} [opts] optional {silent: boolean, tag: string}
 */
async function notify(message, type = "info", opts = {}) {
    const title = (type === "error") ? "Erreur" : (type === "warn") ? "Attention" : "Notification";
    const silent = !!opts.silent;
    const tag = opts.tag || undefined;

    // Try System Notification API if available and allowed (works on https:// or localhost)
    try {
        if ("Notification" in window) {
            if (Notification.permission === "granted") {
                // show it
                const n = new Notification(title, {
                    body: message,
                    tag: tag,
                    renotify: true,
                    icon: opts.icon || undefined // optional icon URL
                });
                // play sound (some browsers block autoplay if no user gesture)
                if (!silent) {
                    const audio = document.getElementById("notify-sound");
                    if (audio) { try { audio.currentTime = 0; audio.play().catch(()=>{}); } catch(e){} }
                }
                return n;
            } else if (Notification.permission !== "denied") {
                // request permission once (user gesture recommended; browsers may block the prompt otherwise)
                const perm = await Notification.requestPermission();
                if (perm === "granted") {
                    return notify(message, type, opts); // recall to show notification
                } else {
                    // fallback to toast
                    _createToastFallback(message, type);
                    return null;
                }
            } else {
                // permission denied -> fallback
                _createToastFallback(message, type);
                return null;
            }
        } else {
            // Notification API not supported -> fallback
            _createToastFallback(message, type);
            return null;
        }
    } catch (err) {
        // any runtime error -> fallback
        console.warn("notify(): Notification API failed, fallback to toast", err);
        _createToastFallback(message, type);
        return null;
    }
}

// ----- Convenience wrappers -----
function notifySuccess(msg, opts) { return notify(msg, "success", opts); }
function notifyWarn(msg, opts)    { return notify(msg, "warn", opts); }
function notifyError(msg, opts)   { return notify(msg, "error", opts); }
function notifyInfo(msg, opts)    { return notify(msg, "info", opts); }

/* Usage examples:
notify("Avatar chargé !", "success");
notifyWarn("Rotation désactivée", { tag: "rotation" });
notifyError("Erreur chargement GLB");
*/
