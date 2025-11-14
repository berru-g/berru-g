// SCENE
const canvas = document.getElementById("scene");
const scene = new THREE.Scene();
scene.background = new THREE.Color(0x111111);

// CAMERA
const camera = new THREE.PerspectiveCamera(45, window.innerWidth/window.innerHeight, 0.1, 100);
camera.position.set(0, 1.6, 3);

// RENDERER
const renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true });
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setPixelRatio(window.devicePixelRatio);

// LIGHTS
const ambientLight = new THREE.AmbientLight(0x404040, 0.6);
scene.add(ambientLight);

const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
directionalLight.position.set(2, 5, 3);
scene.add(directionalLight);

// CONTROLES MAISON (remplacement OrbitControls)
let mouseDown = false;
let lastMouseX = 0;
let lastMouseY = 0;
let targetRotationX = 0;
let targetRotationY = 0;
let currentRotationX = 0;
let currentRotationY = 0;

canvas.addEventListener('mousedown', (e) => {
  mouseDown = true;
  lastMouseX = e.clientX;
  lastMouseY = e.clientY;
});

canvas.addEventListener('mouseup', () => {
  mouseDown = false;
});

canvas.addEventListener('mousemove', (e) => {
  if (!mouseDown) return;
  
  const deltaX = e.clientX - lastMouseX;
  const deltaY = e.clientY - lastMouseY;
  
  targetRotationY += deltaX * 0.01;
  targetRotationX += deltaY * 0.01;
  
  lastMouseX = e.clientX;
  lastMouseY = e.clientY;
});

// Zoom avec molette
canvas.addEventListener('wheel', (e) => {
  camera.position.z += e.deltaY * 0.01;
  camera.position.z = Math.max(1, Math.min(10, camera.position.z));
});

// VARIABLES GLOBALES
let avatar;
let isSpeaking = false;

// CHARGEMENT AVATAR
const loader = new THREE.GLTFLoader();

// Remplace cette URL par ton avatar ReadyPlayerMe
const avatarURL = "https://models.readyplayer.me/691732d6fa1ea12f834e291b.glb";

loader.load(
  avatarURL,
  (gltf) => {
    avatar = gltf.scene;
    avatar.position.set(10, 10, 10); // Position initiale haute 
    avatar.scale.set(2, 2, 2); // Agrandit un peu
    
    // Centre l'avatar
    const box = new THREE.Box3().setFromObject(avatar);
    const center = box.getCenter(new THREE.Vector3());
    avatar.position.sub(center);
    
    scene.add(avatar);
    document.getElementById("status").textContent = "Avatar chargé ! Parlez-lui !";
    console.log("Avatar chargé !");
  },
  undefined,
  (err) => {
    console.error("Erreur chargement avatar :", err);
    document.getElementById("status").textContent = "Erreur de chargement - utilisation du cube de secours";
    
    // Cube de secours
    const geometry = new THREE.BoxGeometry(1, 1, 1);
    const material = new THREE.MeshPhongMaterial({ color: 0x00ff00 });
    avatar = new THREE.Mesh(geometry, material);
    scene.add(avatar);
  }
);

// SYNTHÈSE VOCALE
function speakText(text) {
  if (!window.speechSynthesis) {
    console.error("Speech Synthesis non supporté");
    return Promise.resolve();
  }
  
  return new Promise((resolve) => {
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = "fr-FR";
    utterance.rate = 0.9;
    
    utterance.onstart = () => {
      isSpeaking = true;
      document.getElementById("status").textContent = "L'avatar parle...";
    };
    
    utterance.onend = () => {
      isSpeaking = false;
      document.getElementById("status").textContent = "En attente de message...";
      resolve();
    };
    
    utterance.onerror = () => {
      isSpeaking = false;
      document.getElementById("status").textContent = "Erreur de synthèse vocale";
      resolve();
    };
    
    window.speechSynthesis.speak(utterance);
  });
}

// SIMULATION IA
async function sendToAI(message) {
  document.getElementById("status").textContent = "L'avatar réfléchit...";
  
  // Simulation délai réseau
  await new Promise(resolve => setTimeout(resolve, 800 + Math.random() * 800));
  
  const responses = [
    "Bonjour ! Je suis votre assistant virtuel en 3D.",
    "C'est fascinant de pouvoir interagir comme ça, vous ne trouvez pas ?",
    "La technologie 3D temps réel ouvre tellement de possibilités !",
    "Je suis impressionné par comment l'IA et la 3D peuvent travailler ensemble.",
    "Merci de cette conversation ! Que voulez-vous savoir d'autre ?",
    "L'animation faciale arrive bientôt, patience !",
    "C'est un plaisir de discuter avec vous dans cet environnement 3D."
  ];
  
  return responses[Math.floor(Math.random() * responses.length)];
}

// GESTION DE L'UI
document.getElementById("send").onclick = async () => {
  const input = document.getElementById("prompt");
  const text = input.value.trim();
  
  if (!text) return;
  
  console.log("Message envoyé :", text);
  input.value = "";
  document.getElementById("send").disabled = true;
  
  try {
    const aiResponse = await sendToAI(text);
    console.log("Réponse IA:", aiResponse);
    await speakText(aiResponse);
  } catch (error) {
    console.error("Erreur:", error);
    document.getElementById("status").textContent = "Erreur de communication";
  } finally {
    document.getElementById("send").disabled = false;
  }
};

// Touche Entrée
document.getElementById("prompt").addEventListener("keypress", (e) => {
  if (e.key === "Enter") {
    document.getElementById("send").click();
  }
});

// BOUCLE D'ANIMATION
function animate() {
  requestAnimationFrame(animate);
  
  // Animation de rotation fluide
  currentRotationX += (targetRotationX - currentRotationX) * 0.05;
  currentRotationY += (targetRotationY - currentRotationY) * 0.05;
  
  if (avatar) {
    avatar.rotation.x = currentRotationX;
    avatar.rotation.y = currentRotationY;
    
    // Légère animation pendant la parole
    if (isSpeaking) {
      avatar.rotation.y += Math.sin(Date.now() * 0.01) * 0.02;
    }
  }
  
  renderer.render(scene, camera);
}

animate();

// REDIMENSIONNEMENT
window.addEventListener("resize", () => {
  camera.aspect = window.innerWidth / window.innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(window.innerWidth, window.innerHeight);
});

// Instructions pour l'utilisateur
console.log("🎮 Contrôles :");
console.log("- Clic + glisser pour tourner l'avatar");
console.log("- Molette pour zoomer/dézoomer");
console.log("- Tapez dans le champ texte pour dialoguer");