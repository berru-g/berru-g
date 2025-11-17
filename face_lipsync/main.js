// ===================================================
// main.js - Version v4 (expressive + eye contact)
// ===================================================

// =============================================
// CONFIG / OPTIONS
// =============================================

const USE_AUDIO_ANALYSER_FOR_LIPSYNC = false;
const AVATAR_ROTATION_ENABLED = true;
const AUDIO_FILE_FOR_LIPSYNC = "voice.mp3";

const CANVAS_ID = "scene";
const PROMPT_ID = "prompt";
const SEND_BTN_ID = "send";
const STATUS_ID = "status";

// =============================================
// NOTIFY SYSTEM
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
    if (audio) { try { audio.currentTime = 0; audio.play().catch(()=>{}); } catch(e){} }
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
                    if (audio) { try { audio.currentTime = 0; audio.play().catch(()=>{}); } catch(e){} }
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
function notifySuccess(m, o){ return notify(m,"success",o); }
function notifyWarn(m, o){ return notify(m,"warn",o); }
function notifyError(m, o){ return notify(m,"error",o); }
function notifyInfo(m, o){ return notify(m,"info",o); }

// =============================================
// THREE.JS SCENE
// =============================================

const canvas = document.getElementById(CANVAS_ID);
const scene = new THREE.Scene();
scene.background = null;

const camera = new THREE.PerspectiveCamera(45, window.innerWidth/window.innerHeight, 0.1, 100);
camera.position.set(0, 1.6, 3);

const renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
renderer.setClearColor(0x000000, 0);
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setPixelRatio(window.devicePixelRatio);

const ambientLight = new THREE.AmbientLight(0x404040, 0.6);
scene.add(ambientLight);

const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
directionalLight.position.set(2, 5, 3);
scene.add(directionalLight);

// =============================================
// VARIABLES GLOBALES
// =============================================

let avatar, isSpeaking = false;
const mouthMeshes = [];
let mouthIntensity = 0;
const MOUTH_SMOOTH = 0.16;

let visemeTimeline = [];
let speechStartTime = 0;
let estimatedSpeechDuration = 0;

let blinkTimer = 0, blinkProgress = 0;
const faceMorphs = {};
let headBone = null, spineBone = null;
let eyeLeftBone = null, eyeRightBone = null;

let mouseX = 0, mouseY = 0;
const EYE_LOOK_SPEED = 0.1;

// =============================================
// LOAD AVATAR
// =============================================

const loader = new THREE.GLTFLoader();
const avatarURL = "https://models.readyplayer.me/691732d6fa1ea12f834e291b.glb"; // Wam
const avatarURL1 = "https://models.readyplayer.me/6918df9a786317131c6318b4.glb"; // Otto foiré
const avatarURL2 = "https://models.readyplayer.me/691779f5132e61458cc3366d.glb"; // femme
const avatarURL3 = "https://models.readyplayer.me/69177cac28f4be8b0cc83e05.glb"; // futur
const avatarURL4 = "../img/neo_futuriste_faceMorph_readyplayer.glb"; // test3D local  FONCTIONNEL = UPLOAD GRATOS D OBJET 3D + FACEMOPRH SUR READY PLAYER ( pour l'instant )

loader.load(
    avatarURL3,
    (gltf) => {
        avatar = gltf.scene;
        avatar.position.set(0, -10.4, 0);
        avatar.scale.set(7, 7, 7);
        scene.add(avatar);
        findMorphTargets(avatar);
        notifySuccess("Avatar chargé !");
        if(!AVATAR_ROTATION_ENABLED && avatar) avatar.rotation.set(0,0,0);
    },
    undefined,
    (err) => { console.error(err); notifyError("Erreur de chargement de l'avatar"); }
);

// =============================================
// MORPH TARGETS / BONES
// =============================================

function findMorphTargets(root){
    root.traverse(obj=>{
        if(obj.morphTargetDictionary && obj.morphTargetInfluences){
            if("mouthOpen" in obj.morphTargetDictionary) {
                mouthMeshes.push({mesh:obj,index:obj.morphTargetDictionary["mouthOpen"]});
            }
            // brows
            const keys = Object.keys(obj.morphTargetDictionary);
            keys.forEach(k=>{
                const low = k.toLowerCase();
                if(low.includes("brow")) faceMorphs.browUp = {mesh:obj,index:obj.morphTargetDictionary[k]};
                if(low.includes("blink")) {
                    if(low.includes("left")) faceMorphs.blinkLeft = {mesh:obj,index:obj.morphTargetDictionary[k]};
                    if(low.includes("right")) faceMorphs.blinkRight = {mesh:obj,index:obj.morphTargetDictionary[k]};
                }
            });
        }
        if(obj.isBone){
            const n = obj.name.toLowerCase();
            if(n.includes("head")) headBone = obj;
            if(n.includes("spine")) spineBone = obj;
            if(n.includes("eye") && n.includes("left")) eyeLeftBone = obj;
            if(n.includes("eye") && n.includes("right")) eyeRightBone = obj;
        }
    });
    console.log("mouthMeshes:",mouthMeshes.map(m=>m.mesh.name));
}

// =============================================
// VOICES
// =============================================

const VOICES = {
    MASCULINE: { rate: 1.0, pitch: 0.4, name: "Masculine Grave" },
    MASCULINE_NORMAL: { rate: 1.0, pitch: 1.0, name: "Masculine Normale" },
    FEMININE_SOFT: { rate: 1.1, pitch: 1.3, name: "Féminine Douce" },
    ROBOT: { rate: 0.85, pitch: 0.5, name: "Robotique" },
    HAPPY: { rate: 1.2, pitch: 1.1, name: "Joyeuse" },
    CALM: { rate: 0.8, pitch: 0.9, name: "Calme" },
    ENERGETIC: { rate: 1.3, pitch: 1.0, name: "Énergique" }
};
let currentVoice = VOICES.MASCULINE;

// =============================================
// LIPSYNC (SpeechSynthesis timeline fallback)
// =============================================

function buildTimelineFromText(text, rate=1.0){
    const chars = Array.from(text.replace(/\s+/g,' '));
    const baseCharDur = 0.06/rate;
    visemeTimeline = chars.map((ch,i)=>{
        const isVowel='aeiouyàâäéèêëîïôöùûüAEIOUYÀÂÄÉÈÊËÎÏÔÖÙÛÜ'.includes(ch);
        const v = isVowel?1.0:(ch===' '?0.02:0.35);
        return {time:i*baseCharDur, intensity:Math.min(1,Math.max(0,v+(Math.random()*0.25-0.12))), ch};
    });
    estimatedSpeechDuration = chars.length*baseCharDur;
    return estimatedSpeechDuration;
}

function speakText(text){
    if(!window.speechSynthesis) { notifyError("Synthèse vocale non supportée"); return Promise.resolve();}
    buildTimelineFromText(text,currentVoice.rate);
    return new Promise(resolve=>{
        const u = new SpeechSynthesisUtterance(text);
        u.lang="fr-FR"; u.rate=currentVoice.rate; u.pitch=currentVoice.pitch;
        u.onstart=()=>{ isSpeaking=true; speechStartTime=performance.now()/1000; notifyInfo("L'avatar parle...",{silent:true}); };
        u.onend=()=>{ isSpeaking=false; mouthIntensity=0; resolve(); };
        u.onerror=(e)=>{ console.error(e); isSpeaking=false; notifyError("Erreur TTS"); resolve(); };
        window.speechSynthesis.cancel(); window.speechSynthesis.speak(u);
    });
}

// =============================================
// UPDATE EXPRESSIONS
// =============================================

function updateLipSyncFromTimeline(){
    const now = performance.now()/1000;
    const t = now - speechStartTime;
    let target=0.02;
    if(visemeTimeline.length>0){
        const idx = Math.floor(t/0.06);
        if(idx>=0 && idx<visemeTimeline.length) target = visemeTimeline[idx].intensity;
        else if(t>estimatedSpeechDuration) target=0.02;
    } else target=0.2+Math.abs(Math.sin(now*20))*0.3;
    mouthIntensity += (target - mouthIntensity)*MOUTH_SMOOTH;
    applyMouthIntensity(mouthIntensity);
}

function applyMouthIntensity(val){
    const v=Math.min(1,Math.max(0,val));
    mouthMeshes.forEach(m=>{ m.mesh.morphTargetInfluences[m.index] = v*1.5; }); // plus expressif
}

function updateEyeBlink(){
    const now=performance.now();
    if(blinkTimer===0) blinkTimer = now + 1200 + Math.random()*1800;
    if(now>blinkTimer){
        blinkProgress+=0.25;
        const v=Math.sin(blinkProgress);
        const blinkVal = v>0?v:0;
        if(faceMorphs.blinkLeft) faceMorphs.blinkLeft.mesh.morphTargetInfluences[faceMorphs.blinkLeft.index] = blinkVal*1.5;
        if(faceMorphs.blinkRight) faceMorphs.blinkRight.mesh.morphTargetInfluences[faceMorphs.blinkRight.index] = blinkVal*1.5;
        if(blinkProgress>=Math.PI){ blinkProgress=0; blinkTimer=0; }
    }
}

function updateBrows(intensity){
    if(faceMorphs.browUp) faceMorphs.browUp.mesh.morphTargetInfluences[faceMorphs.browUp.index] = intensity*1.2;
}

function updateHeadMotion(intensity){
    if(!headBone) return;
    headBone.rotation.x = Math.sin(performance.now()*0.002)*0.07 + intensity*0.15;
    headBone.rotation.y = Math.sin(performance.now()*0.0015)*0.05;
}

function updateBodyMotion(){
    if(!spineBone) return;
    spineBone.rotation.y = Math.sin(performance.now()*0.001)*0.05;
    spineBone.rotation.x = Math.sin(performance.now()*0.0008)*0.03;
}

function updateEyeContact(){
    if(!eyeLeftBone || !eyeRightBone) return;
    const targetRotY = mouseX*0.5;
    const targetRotX = -mouseY*0.3;
    eyeLeftBone.rotation.y += (targetRotY-eyeLeftBone.rotation.y)*EYE_LOOK_SPEED;
    eyeRightBone.rotation.y += (targetRotY-eyeRightBone.rotation.y)*EYE_LOOK_SPEED;
    eyeLeftBone.rotation.x += (targetRotX-eyeLeftBone.rotation.x)*EYE_LOOK_SPEED;
    eyeRightBone.rotation.x += (targetRotX-eyeRightBone.rotation.x)*EYE_LOOK_SPEED;
}

window.addEventListener("mousemove",e=>{
    mouseX = (e.clientX/window.innerWidth)*2-1;
    mouseY = (e.clientY/window.innerHeight)*2-1;
});

// =============================================
// UI + AI SIMULATION
// =============================================

let compteurReponses = 0;
const reponsesOrdonnees = [
  "Bonjour ! Je suis votre assistant virtuel en 3D. Bienvenue !",
  "Pour toute information, contactez mon créateur sur gael-berru.com.",
  "La synthèse vocale fonctionne correctement !"
];

async function sendToAI(message){
    const statusEl = document.getElementById(STATUS_ID);
    if(statusEl) statusEl.textContent = "L'avatar réfléchit...";
    await new Promise(r=>setTimeout(r,800+Math.random()*800));
    const reponse = reponsesOrdonnees[compteurReponses];
    compteurReponses = (compteurReponses+1)%reponsesOrdonnees.length;
    return reponse;
}

document.getElementById(SEND_BTN_ID).onclick = async ()=>{
    const inputEl = document.getElementById(PROMPT_ID);
    const text = inputEl.value.trim();
    if(!text) return;
    inputEl.value="";
    try{
        const aiResponse = await sendToAI(text);
        await speakText(aiResponse);
    } catch(e){ console.error(e); notifyError("Erreur de communication"); }
};

document.getElementById(PROMPT_ID).addEventListener("keypress",e=>{
    if(e.key==="Enter") document.getElementById(SEND_BTN_ID).click();
});

// =============================================
// ANIMATE LOOP
// =============================================

function animate(){
    requestAnimationFrame(animate);

    if(avatar){
        if(AVATAR_ROTATION_ENABLED && !isSpeaking){
           // avatar.rotation.y += 0.003;
           updateEyeBlink();
           updateBodyMotion();
           //notifyInfo("L'avatar est prêt. Parlez-lui !");
        }
    }

    if(isSpeaking) updateLipSyncFromTimeline();
    else { mouthIntensity += (0-mouthIntensity)*MOUTH_SMOOTH; applyMouthIntensity(mouthIntensity); }

    updateEyeBlink();
    updateBrows(mouthIntensity);
    updateHeadMotion(mouthIntensity);
    updateBodyMotion();
    updateEyeContact();

    renderer.render(scene,camera);
}
animate();

// =============================================
// RESIZE
// =============================================
window.addEventListener("resize",()=>{
    camera.aspect=window.innerWidth/window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth,window.innerHeight);
});

// =============================================
// UTILITAIRES
// =============================================
console.log("➡️ Voix dispo :",Object.keys(VOICES));
