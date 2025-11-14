// ===================================================
// main.js - Version refaite (intégrée : notify, lipsync)
// ===================================================

// =============================================
// CONFIG / OPTIONS
// =============================================

// Si false -> on simule le lipsync à partir du texte avec SpeechSynthesis (par défaut)
// Si true  -> on utilisera un <audio src="..."> + WebAudio analyser (nécessite fichier audio prêt)
const USE_AUDIO_ANALYSER_FOR_LIPSYNC = false; // <- change to true if you have pre-rendered audio file

// Si false, l'avatar ne tournera pas automatiquement
const AVATAR_ROTATION_ENABLED = true; // ← décommente / change à false pour arrêter la rotation automatiquement

// Nom du fichier audio (si USE_AUDIO_ANALYSER_FOR_LIPSYNC === true)
// Place le fichier dans le même dossier ou fournis une URL CORS-compatible
const AUDIO_FILE_FOR_LIPSYNC = "voice.mp3";

// ID des éléments HTML (canvas, ui...) — adapte si besoin
const CANVAS_ID = "scene";
const PROMPT_ID = "prompt";
const SEND_BTN_ID = "send";
const STATUS_ID = "status";

// =============================================
// NOTIFY SYSTEM (System Notification -> fallback toast)
// =============================================

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
  const audio = document.getElementById("notify-sound");
  if (audio) { try { audio.currentTime = 0; audio.play().catch(() => { }); } catch (e) { } }
  setTimeout(() => {
    t.style.animation = "toast-out 0.35s forwards";
    setTimeout(() => t.remove(), 350);
  }, 3000);
}

async function notify(message, type = "info", opts = {}) {
  const title = (type === "error") ? "Erreur" : (type === "warn") ? "Attention" : "Notification";
  const silent = !!opts.silent;
  const tag = opts.tag || undefined;
  try {
    if ("Notification" in window) {
      if (Notification.permission === "granted") {
        const n = new Notification(title, { body: message, tag: tag, renotify: true, icon: opts.icon || undefined });
        if (!silent) {
          const audio = document.getElementById("notify-sound");
          if (audio) { try { audio.currentTime = 0; audio.play().catch(() => { }); } catch (e) { } }
        }
        return n;
      } else if (Notification.permission !== "denied") {
        const perm = await Notification.requestPermission();
        if (perm === "granted") {
          return notify(message, type, opts);
        } else {
          _createToastFallback(message, type);
          return null;
        }
      } else {
        _createToastFallback(message, type);
        return null;
      }
    } else {
      _createToastFallback(message, type);
      return null;
    }
  } catch (err) {
    console.warn("notify(): Notification API failed, fallback to toast", err);
    _createToastFallback(message, type);
    return null;
  }
}
function notifySuccess(m, o) { return notify(m, "success", o); }
function notifyWarn(m, o) { return notify(m, "warn", o); }
function notifyError(m, o) { return notify(m, "error", o); }
function notifyInfo(m, o) { return notify(m, "info", o); }

// =============================================
// INITIALISATION THREE.JS - SCÈNE 3D
// =============================================

// Récupère l'élément canvas HTML où la scène 3D sera affichée
const canvas = document.getElementById(CANVAS_ID);

// Crée la scène Three.js et définit la couleur de fond (noir)
const scene = new THREE.Scene();
scene.background = new THREE.Color(0x000000);

// Configure la caméra perspective
const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 100);
camera.position.set(0, 1.6, 3);

// Initialise le moteur de rendu WebGL
const renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true });
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setPixelRatio(window.devicePixelRatio);

// =============================================
// ÉCLAIRAGE DE LA SCÈNE
// =============================================
const ambientLight = new THREE.AmbientLight(0x404040, 0.6);
scene.add(ambientLight);

const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
directionalLight.position.set(2, 5, 3);
scene.add(directionalLight);

// =============================================
// VARIABLES GLOBALES
// =============================================
let avatar;                      // Référence vers le modèle 3D de l'avatar
let isSpeaking = false;          // État pour savoir si l'avatar est en train de parler

// ===== LIPSYNC =====
const mouthMeshes = [];          // { mesh, index } pour toutes les meshes contenant mouthOpen
let mouthIntensity = 0;          // intensité courante (lissée)
const MOUTH_SMOOTH = 0.16;       // coefficient de lissage (0..1)

// Pour timeline-based lipsync (quand on utilise SpeechSynthesis)
let visemeTimeline = [];         // [{time, intensity, ch}, ...]
let speechStartTime = 0;         // performance.now()/1000 when started
let estimatedSpeechDuration = 0; // seconds

// ===== EXPRESSIONS FACIALES =====
let faceMorphs = {
  blinkLeft: null,
  blinkRight: null,
  browUp: null,
  browDown: null,
  mouthSmile: null
};

let headBone = null;
let neckBone = null;
let spineBone = null;

// blinking system
let blinkTimer = 0;
let blinkProgress = 0;

// micro eye movement
let eyeMoveX = 0;
let eyeMoveY = 0;
let eyeTargetX = 0;
let eyeTargetY = 0;

let mouseX = 0;
let mouseY = 0;
const EYE_LOOK_SPEED = 0.1;
let eyeLeftBone = null;
let eyeRightBone = null;


// =============================================
// CHARGEMENT DE L'AVATAR 3D
// =============================================

const loader = new THREE.GLTFLoader();
const avatarURL = "https://models.readyplayer.me/691732d6fa1ea12f834e291b.glb";

loader.load(
  avatarURL,
  (gltf) => {
    avatar = gltf.scene;
    avatar.position.set(0, -2.4, 0);
    avatar.scale.set(2.5, 2.5, 2.5);
    scene.add(avatar);
    // recherche des morph targets mouthOpen
    findMouthMeshes(avatar);
    notifySuccess("Avatar chargé ! Parlez-lui !");
    console.log("✅ Avatar chargé avec succès !");
    const statusEl = document.getElementById(STATUS_ID);
    if (statusEl) statusEl.textContent = "Avatar chargé ! Parlez-lui !";

    // Si rotation désactivée globalement, on met rotation à 0
    if (!AVATAR_ROTATION_ENABLED && avatar) avatar.rotation.set(0, 0, 0);
  },
  undefined,
  (err) => {
    console.error("❌ Erreur chargement avatar :", err);
    notifyError("Erreur de chargement de l'avatar");
    const statusEl = document.getElementById(STATUS_ID);
    if (statusEl) statusEl.textContent = "Erreur de chargement de l'avatar";
  }
);

// =============================================
// CONFIGURATION DES VOIX DE SYNTHÈSE VOCALE
// =============================================

const VOICES = {
  MASCULINE: { rate: 1.0, pitch: 0.7, name: "Masculine Grave" },
  MASCULINE_NORMAL: { rate: 1.0, pitch: 1.0, name: "Masculine Normale" },
  FEMININE_SOFT: { rate: 1.1, pitch: 1.3, name: "Féminine Douce" },
  ROBOT: { rate: 0.85, pitch: 0.5, name: "Robotique" },
  HAPPY: { rate: 1.2, pitch: 1.1, name: "Joyeuse" },
  CALM: { rate: 0.8, pitch: 0.9, name: "Calme" },
  ENERGETIC: { rate: 1.3, pitch: 1.0, name: "Énergique" }
};

let currentVoice = VOICES.MASCULINE;

// =============================================
// FONCTION DE SYNTHÈSE VOCALE (avec timeline)
// =============================================

/**
 * Build a simple timeline from text — used for approximated lipsync with SpeechSynthesis
 * returns estimated total duration
 */
function buildTimelineFromText(text, rate = 1.0) {
  const cleaned = text.replace(/\s+/g, ' ');
  const chars = Array.from(cleaned);
  const baseCharDur = 0.06;
  const charDur = baseCharDur / rate;
  visemeTimeline = chars.map((ch, i) => {
    const isVowel = 'aeiouyàâäéèêëîïôöùûüAEIOUYÀÂÄÉÈÊËÎÏÔÖÙÛÜ'.includes(ch);
    const v = isVowel ? 1.0 : (ch === ' ' ? 0.02 : 0.35);
    const intensity = Math.min(1, Math.max(0, v + (Math.random() * 0.25 - 0.12)));
    return { time: i * charDur, intensity, ch };
  });
  estimatedSpeechDuration = chars.length * charDur;
  return estimatedSpeechDuration;
}

/**
 * speakText - uses SpeechSynthesis and timeline-based lipsync fallback
 * If USE_AUDIO_ANALYSER_FOR_LIPSYNC is true, we provide helper to play an audio file separately
 */
function speakText(text) {
  if (!window.speechSynthesis) {
    console.error("❌ Synthèse vocale non supportée par ce navigateur");
    notifyError("Synthèse vocale non supportée");
    return Promise.resolve();
  }

  // Build timeline for lipsync (approximate)
  const rate = currentVoice.rate || 1.0;
  buildTimelineFromText(text, rate);

  return new Promise((resolve) => {
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = "fr-FR";
    utterance.rate = currentVoice.rate;
    utterance.pitch = currentVoice.pitch;
    utterance.volume = 1.0;

    utterance.onstart = () => {
      isSpeaking = true;
      speechStartTime = performance.now() / 1000;
      notifyInfo("L'avatar parle...", { silent: true });
      const statusEl = document.getElementById(STATUS_ID);
      if (statusEl) statusEl.textContent = `L'avatar parle... (${currentVoice.name})`;
    };

    utterance.onend = () => {
      isSpeaking = false;
      // gently close mouth
      mouthIntensity = 0;
      // reset influences slowly handled in animation loop
      const statusEl = document.getElementById(STATUS_ID);
      if (statusEl) statusEl.textContent = "En attente de message...";
      resolve();
    };

    utterance.onerror = (err) => {
      console.error("❌ Erreur synthèse vocale:", err);
      isSpeaking = false;
      notifyError("Erreur synthèse vocale");
      resolve();
    };

    // speak
    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(utterance);
  });
}

// =============================================
// Mode alternatif: utiliser un fichier audio + analyser (plus précis)
// =============================================

let audioContext = null;
let analyser = null;
let dataArray = null;
let audioElement = null;
let audioSourceNode = null;

function setupAudioAnalyserForFile(url) {
  audioElement = new Audio(url);
  audioElement.crossOrigin = "anonymous";
  audioContext = new (window.AudioContext || window.webkitAudioContext)();
  analyser = audioContext.createAnalyser();
  analyser.fftSize = 512;
  dataArray = new Uint8Array(analyser.frequencyBinCount);
  try {
    audioSourceNode = audioContext.createMediaElementSource(audioElement);
    audioSourceNode.connect(analyser);
    analyser.connect(audioContext.destination);
  } catch (e) {
    console.warn("Impossible de connecter audio analyser (CORS?)", e);
  }
}

// Helper to play analysed audio and set isSpeaking
function playAudioWithAnalyser() {
  if (!audioElement) {
    console.warn("No audio element configured for analyser.");
    return;
  }
  if (!audioContext) setupAudioAnalyserForFile(AUDIO_FILE_FOR_LIPSYNC);
  audioContext.resume().catch(() => { });
  audioElement.currentTime = 0;
  audioElement.play().catch(err => console.warn("audio play blocked:", err));
  isSpeaking = true;
  audioElement.onended = () => { isSpeaking = false; };
}

// =============================================
// RECUPERATION DES MORPH TARGETS
// =============================================

function findMouthMeshes(root) {
  mouthMeshes.length = 0;

  avatar.traverse(obj => {
    if (obj.isBone) {
      const name = obj.name.toLowerCase();
      if (name.includes("eye") && name.includes("left")) eyeLeftBone = obj;
      if (name.includes("eye") && name.includes("right")) eyeRightBone = obj;
    }
  });


  root.traverse(obj => {
    if (obj.isBone) {
      if (obj.name.toLowerCase().includes("head")) headBone = obj;
      if (obj.name.toLowerCase().includes("neck")) neckBone = obj;
      if (obj.name.toLowerCase().includes("spine")) spineBone = obj;
    }

    if (obj.morphTargetDictionary && obj.morphTargetInfluences) {
      const dict = obj.morphTargetDictionary;

      // --- MOUTH ---
      for (let k in dict) {
        let low = k.toLowerCase();

        if (low.includes("mouth") && low.includes("open")) {
          mouthMeshes.push({ mesh: obj, index: dict[k] });
        }

        if (low.includes("smile")) {
          faceMorphs.mouthSmile = { mesh: obj, index: dict[k] };
        }

        if (low.includes("brow") && low.includes("up")) {
          faceMorphs.browUp = { mesh: obj, index: dict[k] };
        }

        if (low.includes("brow") && low.includes("down")) {
          faceMorphs.browDown = { mesh: obj, index: dict[k] };
        }

        if (low.includes("blink") && low.includes("left")) {
          faceMorphs.blinkLeft = { mesh: obj, index: dict[k] };
        }

        if (low.includes("blink") && low.includes("right")) {
          faceMorphs.blinkRight = { mesh: obj, index: dict[k] };
        }
      }
    }
  });

  console.log("🔍 MorphTargets détectés :", faceMorphs, mouthMeshes);
}


// =============================================
// ANIMATION DE LA BOUCHE (APPLIQUE LES INFLUENCES)
// =============================================

function updateLipSyncFromAnalyser() {
  if (!analyser || !dataArray) return;
  analyser.getByteFrequencyData(dataArray);
  let sum = 0;
  for (let i = 0; i < dataArray.length; i++) sum += dataArray[i];
  const avg = sum / dataArray.length; // 0..255
  const intensityRaw = Math.min(1, avg / 80); // normalized
  // smoothing
  mouthIntensity += (intensityRaw - mouthIntensity) * MOUTH_SMOOTH;
  applyMouthIntensity(mouthIntensity);
}

function updateLipSyncFromTimeline() {
  const now = performance.now() / 1000;
  const t = now - speechStartTime;
  let target = 0.02;
  if (visemeTimeline.length > 0) {
    // index based on char duration
    const idx = Math.floor(t / 0.06); // rough approx - matches buildTimeline charDur base
    if (idx >= 0 && idx < visemeTimeline.length) {
      target = visemeTimeline[idx].intensity;
    } else if (t > estimatedSpeechDuration) {
      target = 0.02;
    }
  } else {
    // fallback sine-like motion
    target = 0.2 + Math.abs(Math.sin(now * 20)) * 0.3;
  }
  mouthIntensity += (target - mouthIntensity) * MOUTH_SMOOTH;
  applyMouthIntensity(mouthIntensity);
}

function applyMouthIntensity(val) {
  if (mouthMeshes.length === 0) return;
  // small clamp & optional exponent for perceptual feel
  const v = Math.min(1, Math.max(0, val));
  mouthMeshes.forEach(m => {
    // apply to each morph target influence with some local smoothing possibility
    m.mesh.morphTargetInfluences[m.index] = v;
  });
}

// =============================================
// FONCTIONS POUR TESTER ET CHANGER LES VOIX
// =============================================

function testerToutesLesVoix() {
  console.log("🎵 DÉMARRAGE DU TEST COMPLET DES VOIX...");
  const voixATester = [
    { key: 'MASCULINE', text: 'Je suis la voix masculine grave, assez grave pour vous ?' },
    { key: 'MASCULINE_NORMAL', text: 'Je suis la voix masculine normale, plutôt standard.' },
    { key: 'FEMININE_SOFT', text: 'Je suis la voix féminine douce, elle vous plaît ?' },
    { key: 'ROBOT', text: 'Je suis la voix robotique, bip boup.' },
    { key: 'HAPPY', text: 'Je suis la voix joyeuse, super contente de vous parler !' },
    { key: 'CALM', text: 'Je suis la voix calme et lente, très relaxante.' },
    { key: 'ENERGETIC', text: 'Je suis la voix énergique, rapide et dynamique !' }
  ];
  voixATester.forEach((voix, index) => {
    setTimeout(() => {
      currentVoice = VOICES[voix.key];
      console.log(`🔊 Test ${index + 1}/7: ${currentVoice.name}`);
      speakText(voix.text);
    }, index * 5000);
  });
}

function changerVoix(nouvelleVoix) {
  if (VOICES[nouvelleVoix]) {
    currentVoice = VOICES[nouvelleVoix];
    console.log(`✅ Voix changée: ${currentVoice.name}`);
    speakText(`Voix changée pour ${currentVoice.name}`);
    return currentVoice.name;
  } else {
    console.warn("❌ Voix non trouvée. Voix disponibles:", Object.keys(VOICES));
    return null;
  }
}

// =============================================
// SIMULATION IA (sendToAI) - conserve ta logique
// =============================================

let compteurReponses = 0;
const reponsesOrdonnees = [
  "Bonjour ! Je suis votre assistant virtuel en 3D. Bienvenue ! Actuellement en phase de test, je ne peux pas répondre à vos questions pour le moment.",
  "Pour toute information, contactez mon créateur sur gael-berru.com.",
  "La synthèse vocale fonctionne correctement, c'est prometteur !",
  "N'hésitez pas à tester les différentes voix disponibles dans la console en attendant l'ia. Merci pour votre visite et à bientôt pour plus de fonctionnalités."
];

async function sendToAI(message) {
  const statusEl = document.getElementById(STATUS_ID);
  if (statusEl) statusEl.textContent = "L'avatar réfléchit...";
  // simulation
  const delai = 800 + Math.random() * 800;
  await new Promise(resolve => setTimeout(resolve, delai));
  const reponse = reponsesOrdonnees[compteurReponses];
  compteurReponses = (compteurReponses + 1) % reponsesOrdonnees.length;
  console.log(`🤖 Réponse ${compteurReponses}/${reponsesOrdonnees.length}: ${reponse}`);
  return reponse;
}

// =============================================
// GESTION DE L'INTERFACE UTILISATEUR
// =============================================

document.getElementById(SEND_BTN_ID).onclick = async () => {
  const inputEl = document.getElementById(PROMPT_ID);
  const text = inputEl.value.trim();
  if (!text) return;
  inputEl.value = "";
  console.log("✉️ Message utilisateur:", text);

  try {
    // si on veut analyser un mp3 généré, jouer en audio avec analyser :
    if (USE_AUDIO_ANALYSER_FOR_LIPSYNC) {
      // ici on assume que tu as un mp3 généré correspondant au texte (TTS service offline)
      setupAudioAnalyserForFile(AUDIO_FILE_FOR_LIPSYNC);
      // on peut aussi call a TTS service that returns an audio file and set audioElement.src = url
      // play it
      playAudioWithAnalyser();
      // optionally still call sendToAI to get text reply, here we use sendToAI simulation
      const aiResponse = await sendToAI(text);
      // if we have pre-generated audio for the aiResponse, set audioElement.src accordingly before playAudioWithAnalyser
      // else fallback to speakText
      // here fallback:
      await speakText(aiResponse);
    } else {
      // Standard flow: get response and speak with SpeechSynthesis (timeline-based lipsync)
      const aiResponse = await sendToAI(text);
      await speakText(aiResponse);
    }
  } catch (error) {
    console.error("❌ Erreur:", error);
    notifyError("Erreur de communication");
    const statusEl = document.getElementById(STATUS_ID);
    if (statusEl) statusEl.textContent = "Erreur de communication";
  }
};

// Entrée pour la touche Enter
document.getElementById(PROMPT_ID).addEventListener("keypress", (e) => {
  if (e.key === "Enter") {
    document.getElementById(SEND_BTN_ID).click();
  }
});

// =============================================
// BOUCLE D'ANIMATION PRINCIPALE
// =============================================
function updateEyeBlink() {
  const now = performance.now();

  if (blinkTimer === 0)
    blinkTimer = now + 1500 + Math.random() * 2000; // plus rapide

  if (now > blinkTimer) {
    blinkProgress += 0.25; // plus rapide pour un blink visible

    const v = Math.sin(blinkProgress);
    const blinkVal = v > 0 ? v : 0;

    if (faceMorphs.blinkLeft)
      faceMorphs.blinkLeft.mesh.morphTargetInfluences[faceMorphs.blinkLeft.index] = blinkVal * 1.5;

    if (faceMorphs.blinkRight)
      faceMorphs.blinkRight.mesh.morphTargetInfluences[faceMorphs.blinkRight.index] = blinkVal * 1.5;

    if (blinkProgress >= Math.PI) {
      blinkProgress = 0;
      blinkTimer = 0;
    }
  }
}

function updateBrows(intensity) {
  if (faceMorphs.browUp)
    faceMorphs.browUp.mesh.morphTargetInfluences[faceMorphs.browUp.index] = intensity * 1.2;

  if (faceMorphs.browDown)
    faceMorphs.browDown.mesh.morphTargetInfluences[faceMorphs.browDown.index] = Math.max(0, (0.3 - intensity)) * 1.5;
}

function updateHeadMotion(intensity) {
  if (!headBone) return;

  headBone.rotation.x = Math.sin(performance.now() * 0.002) * 0.07 + intensity * 0.15;
  headBone.rotation.y = Math.sin(performance.now() * 0.0015) * 0.05;
}

function updateBodyMotion() {
  if (!spineBone) return;

  spineBone.rotation.y = Math.sin(performance.now() * 0.001) * 0.05;
  spineBone.rotation.x = Math.sin(performance.now() * 0.0008) * 0.03;
}

function updateEyeContact() {
  if (!eyeLeftBone || !eyeRightBone) return;

  const targetRotY = mouseX * 0.5; // rotation horizontale max
  const targetRotX = -mouseY * 0.3; // rotation verticale max

  // lissage
  eyeLeftBone.rotation.y += (targetRotY - eyeLeftBone.rotation.y) * EYE_LOOK_SPEED;
  eyeRightBone.rotation.y += (targetRotY - eyeRightBone.rotation.y) * EYE_LOOK_SPEED;

  eyeLeftBone.rotation.x += (targetRotX - eyeLeftBone.rotation.x) * EYE_LOOK_SPEED;
  eyeRightBone.rotation.x += (targetRotX - eyeRightBone.rotation.x) * EYE_LOOK_SPEED;
}


function animate() {
  requestAnimationFrame(animate);

  // Rotation lente de l'avatar si activée et si avatar présent et pas en train de parler
  if (avatar) {
    if (AVATAR_ROTATION_ENABLED && !isSpeaking) {
      //avatar.rotation.y += 0.005;
      updateEyeBlink();
      updateBrows(mouthIntensity); // sourcils réagissent à l'intensité vocale
      // --- Head & Body ---
      updateHeadMotion(mouthIntensity);
      updateBodyMotion();
    }
  }

  // Update lipsync selon le mode
  if (isSpeaking) {
    if (USE_AUDIO_ANALYSER_FOR_LIPSYNC) {
      updateLipSyncFromAnalyser();
      updateBrows(mouthIntensity);
    } else {
      updateLipSyncFromTimeline();
      updateBodyMotion();
    }
  } else {
    // refermer doucement la bouche quand il ne parle pas
    mouthIntensity += (0 - mouthIntensity) * MOUTH_SMOOTH;
    applyMouthIntensity(mouthIntensity);
  }
  // --- Expressive Facial Movements ---
  updateEyeBlink();
  updateBrows(mouthIntensity);
  updateHeadMotion(mouthIntensity);
  updateBodyMotion();
  updateEyeContact(); // <- eye contact



  renderer.render(scene, camera);
}
animate();

window.addEventListener("mousemove", (e) => {
  mouseX = (e.clientX / window.innerWidth) * 2 - 1;
  mouseY = (e.clientY / window.innerHeight) * 2 - 1;
});

// =============================================
// GESTION DU REDIMENSIONNEMENT DE LA FENÊTRE
// =============================================
window.addEventListener("resize", () => {
  camera.aspect = window.innerWidth / window.innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(window.innerWidth, window.innerHeight);
});

// =============================================
// UTILITAIRES / INSTRUCTIONS
// =============================================

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
console.log("🔄 Les réponses sont maintenant dans l'ordre défini !");
console.log("");
console.log("⚙️ Options utiles :");
console.log("  - USE_AUDIO_ANALYSER_FOR_LIPSYNC =", USE_AUDIO_ANALYSER_FOR_LIPSYNC);
console.log("  - AVATAR_ROTATION_ENABLED =", AVATAR_ROTATION_ENABLED);
console.log("");
console.log("🔎 Pour vérifier les morph targets détectés : tapez dans la console => mouthMeshes");

// Auto-test voix actuelle après chargement
setTimeout(() => { console.log(`🔊 Voix actuelle: ${currentVoice.name}`); }, 1000);
