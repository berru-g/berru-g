// Éditeur 3D No-Code - Approche éprouvée
// Variables globales
let scene, camera, renderer, controls;
let model = null;
let keyframes = [];
let currentTab = 'position';
let isDragging = false;
let currentPercentage = 0;

// État de l'application
let appInitialized = false;

// Initialisation principale
function initApplication() {
    if (appInitialized) return;
    
    console.log('🚀 Initialisation de l\'application...');
    
    try {
        initThreeJS();
        setupEventListeners();
        generateCode();
        
        appInitialized = true;
        console.log('✅ Application initialisée avec succès');
        
    } catch (error) {
        console.error('❌ Erreur initialisation:', error);
        // Réessayer après 1s
        setTimeout(initApplication, 1000);
    }
}

// Initialisation Three.js (similaire à ton exemple)
function initThreeJS() {
    console.log('🎮 Initialisation Three.js...');
    
    // Créer la scène
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x11111b);

    // Créer la caméra
    const viewer = document.getElementById('viewer');
    camera = new THREE.PerspectiveCamera(75, viewer.offsetWidth / viewer.offsetHeight, 0.1, 1000);
    camera.position.set(5, 5, 5);

    // Créer le renderer
    renderer = new THREE.WebGLRenderer({ 
        antialias: true,
        alpha: true
    });
    renderer.setSize(viewer.offsetWidth, viewer.offsetHeight);
    viewer.appendChild(renderer.domElement);

    // Ajouter les contrôles Orbit
    controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;

    // Ajouter des lumières (comme ton exemple)
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    scene.add(ambientLight);

    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(10, 20, 15);
    scene.add(directionalLight);

    // Ajouter un sol de référence
    const gridHelper = new THREE.GridHelper(20, 20, 0x313244, 0x313244);
    scene.add(gridHelper);

    // Créer un modèle par défaut (cube)
    createDefaultModel();

    // Démarrer l'animation
    animate();
    
    console.log('✅ Three.js initialisé');
}

// Créer un modèle par défaut
function createDefaultModel() {
    const geometry = new THREE.BoxGeometry(1, 1, 1);
    const material = new THREE.MeshStandardMaterial({ 
        color: 0xcba6f7,
        metalness: 0.3,
        roughness: 0.4
    });
    
    model = new THREE.Mesh(geometry, material);
    scene.add(model);
    
    updateModelControls();
    console.log('✅ Modèle par défaut créé');
}

// Fonction d'animation (identique à ton exemple)
function animate() {
    requestAnimationFrame(animate);
    
    if (controls) {
        controls.update();
    }
    
    if (renderer && scene && camera) {
        renderer.render(scene, camera);
    }
}

// CHARGEMENT DE MODÈLE - Approche éprouvée
function loadModel(file) {
    if (!file) {
        console.log('❌ Aucun fichier sélectionné');
        return;
    }
    
    console.log('📁 Chargement du fichier:', file.name, `(${Math.round(file.size/1024)} KB)`);
    
    // Vérifier l'extension
    if (!file.name.toLowerCase().endsWith('.glb') && !file.name.toLowerCase().endsWith('.gltf')) {
        alert('Veuillez sélectionner un fichier GLB ou GLTF');
        return;
    }

    const loader = new THREE.GLTFLoader();
    const reader = new FileReader();

    reader.onload = function(event) {
        console.log('✅ Fichier lu en mémoire');
        
        try {
            loader.parse(event.target.result, '', 
                // Succès
                function(gltf) {
                    console.log('✅ Modèle 3D parsé avec succès');
                    handleLoadedModel(gltf.scene);
                },
                // Erreur
                function(error) {
                    console.error('❌ Erreur de parsing:', error);
                    alert('Erreur de chargement du modèle: ' + error.message);
                }
            );
        } catch (parseError) {
            console.error('❌ Erreur lors du parsing:', parseError);
            alert('Format de fichier non supporté');
        }
    };

    reader.onerror = function(error) {
        console.error('❌ Erreur de lecture:', error);
        alert('Erreur de lecture du fichier');
    };

    reader.onprogress = function(event) {
        if (event.lengthComputable) {
            const percent = Math.round((event.loaded / event.total) * 100);
            console.log(`📥 Progression: ${percent}%`);
        }
    };

    // Lancer la lecture
    reader.readAsArrayBuffer(file);
}

// Gestion du modèle chargé (approche robuste)
function handleLoadedModel(loadedModel) {
    // Supprimer l'ancien modèle
    if (model) {
        scene.remove(model);
        console.log('🗑️ Ancien modèle supprimé');
    }

    model = loadedModel;
    scene.add(model);
    console.log('✅ Nouveau modèle ajouté à la scène');

    // Configuration du modèle
    setupModel();
    updateModelControls();
    
    console.log('🎉 Modèle chargé et configuré avec succès!');
}

// Configuration du modèle chargé
function setupModel() {
    if (!model) return;

    // Calculer la bounding box pour le centrage
    const box = new THREE.Box3().setFromObject(model);
    const center = box.getCenter(new THREE.Vector3());
    const size = box.getSize(new THREE.Vector3());

    console.log('📦 Bounding Box:', {
        center: { x: center.x.toFixed(2), y: center.y.toFixed(2), z: center.z.toFixed(2) },
        size: { x: size.x.toFixed(2), y: size.y.toFixed(2), z: size.z.toFixed(2) }
    });

    // Centrer le modèle
    model.position.x -= center.x;
    model.position.y -= center.y;
    model.position.z -= center.z;

    // Ajuster la caméra selon la taille du modèle
    const maxDim = Math.max(size.x, size.y, size.z);
    const fov = camera.fov * (Math.PI / 180);
    const cameraDistance = Math.max(maxDim * 2, 5); // Au moins 5 unités
    
    camera.position.set(cameraDistance, cameraDistance, cameraDistance);
    controls.target.set(0, 0, 0);
    controls.update();

    console.log('📷 Caméra positionnée à:', camera.position);
}

// Mettre à jour les contrôles UI
function updateModelControls() {
    if (!model) return;
    
    document.getElementById('pos-x').value = model.position.x;
    document.getElementById('pos-y').value = model.position.y;
    document.getElementById('pos-z').value = model.position.z;
    
    document.getElementById('rot-x').value = THREE.MathUtils.radToDeg(model.rotation.x);
    document.getElementById('rot-y').value = THREE.MathUtils.radToDeg(model.rotation.y);
    document.getElementById('rot-z').value = THREE.MathUtils.radToDeg(model.rotation.z);
    
    document.getElementById('scale-x').value = model.scale.x;
    document.getElementById('scale-y').value = model.scale.y;
    document.getElementById('scale-z').value = model.scale.z;
}

// GESTION DES KEYFRAMES
function addKeyframe() {
    const percentage = parseInt(document.getElementById('keyframe-percentage').value);
    
    const keyframe = {
        percentage: percentage,
        position: { x: model.position.x, y: model.position.y, z: model.position.z },
        rotation: { 
            x: model.rotation.x, 
            y: model.rotation.y, 
            z: model.rotation.z 
        },
        scale: { x: model.scale.x, y: model.scale.y, z: model.scale.z }
    };
    
    // Remplacer ou ajouter
    const existingIndex = keyframes.findIndex(k => k.percentage === percentage);
    if (existingIndex !== -1) {
        keyframes[existingIndex] = keyframe;
    } else {
        keyframes.push(keyframe);
        keyframes.sort((a, b) => a.percentage - b.percentage);
    }
    
    updateKeyframesList();
    updateRulerMarkers();
    generateCode();
}

function updateKeyframesList() {
    const list = document.getElementById('keyframes-list');
    list.innerHTML = '';
    
    if (keyframes.length === 0) {
        list.innerHTML = '<div style="text-align: center; color: #a6adc8; padding: 20px;">Aucune keyframe</div>';
        return;
    }
    
    keyframes.forEach((keyframe, index) => {
        const item = document.createElement('div');
        item.className = 'keyframe-item';
        item.innerHTML = `
            <div>
                <div class="keyframe-percentage">${keyframe.percentage}%</div>
                <div style="font-size: 12px; color: #a6adc8;">
                    Position: (${keyframe.position.x.toFixed(1)}, ${keyframe.position.y.toFixed(1)}, ${keyframe.position.z.toFixed(1)})
                </div>
            </div>
            <div class="keyframe-actions">
                <button class="edit-keyframe" data-index="${index}">✏️</button>
                <button class="delete-keyframe" data-index="${index}">🗑️</button>
            </div>
        `;
        list.appendChild(item);
    });
    
    // Événements
    document.querySelectorAll('.edit-keyframe').forEach(btn => {
        btn.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            editKeyframe(index);
        });
    });
    
    document.querySelectorAll('.delete-keyframe').forEach(btn => {
        btn.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            deleteKeyframe(index);
        });
    });
}

function editKeyframe(index) {
    const keyframe = keyframes[index];
    
    // Mettre à jour l'UI
    document.getElementById('keyframe-percentage').value = keyframe.percentage;
    document.getElementById('percentage-value').textContent = `${keyframe.percentage}%`;
    
    // Mettre à jour le modèle
    if (model) {
        model.position.set(keyframe.position.x, keyframe.position.y, keyframe.position.z);
        model.rotation.set(keyframe.rotation.x, keyframe.rotation.y, keyframe.rotation.z);
        model.scale.set(keyframe.scale.x, keyframe.scale.y, keyframe.scale.z);
    }
    
    updateRulerPosition(keyframe.percentage);
    updateModelControls();
}

function deleteKeyframe(index) {
    keyframes.splice(index, 1);
    updateKeyframesList();
    updateRulerMarkers();
    generateCode();
}

// RÈGLE DE SCROLL
function updateRulerMarkers() {
    const track = document.getElementById('ruler-track');
    track.querySelectorAll('.ruler-marker').forEach(marker => marker.remove());
    
    keyframes.forEach(keyframe => {
        const marker = document.createElement('div');
        marker.className = 'ruler-marker';
        marker.style.left = `${keyframe.percentage}%`;
        
        const label = document.createElement('div');
        label.className = 'ruler-percentage';
        label.textContent = `${keyframe.percentage}%`;
        marker.appendChild(label);
        
        marker.addEventListener('click', () => {
            const index = keyframes.findIndex(k => k.percentage === keyframe.percentage);
            if (index !== -1) editKeyframe(index);
        });
        
        track.appendChild(marker);
    });
}

function updateRulerPosition(percentage) {
    document.getElementById('ruler-handle').style.left = `${percentage}%`;
    document.getElementById('preview-handle').style.left = `${percentage}%`;
    document.getElementById('preview-percentage').textContent = `${percentage}%`;
    updateModelByScroll(percentage);
}

function updateModelByScroll(percentage) {
    if (!model || keyframes.length === 0) return;
    
    // Trouver les keyframes adjacentes
    let prev = null, next = null;
    
    for (let i = 0; i < keyframes.length; i++) {
        if (keyframes[i].percentage <= percentage) prev = keyframes[i];
        if (keyframes[i].percentage >= percentage) {
            next = keyframes[i];
            break;
        }
    }
    
    if (!prev && next) prev = next;
    if (!next && prev) next = prev;
    
    // Interpolation
    if (prev && next && prev !== next) {
        const t = (percentage - prev.percentage) / (next.percentage - prev.percentage);
        
        model.position.x = THREE.MathUtils.lerp(prev.position.x, next.position.x, t);
        model.position.y = THREE.MathUtils.lerp(prev.position.y, next.position.y, t);
        model.position.z = THREE.MathUtils.lerp(prev.position.z, next.position.z, t);
        
        model.rotation.x = THREE.MathUtils.lerp(prev.rotation.x, next.rotation.x, t);
        model.rotation.y = THREE.MathUtils.lerp(prev.rotation.y, next.rotation.y, t);
        model.rotation.z = THREE.MathUtils.lerp(prev.rotation.z, next.rotation.z, t);
        
        model.scale.x = THREE.MathUtils.lerp(prev.scale.x, next.scale.x, t);
        model.scale.y = THREE.MathUtils.lerp(prev.scale.y, next.scale.y, t);
        model.scale.z = THREE.MathUtils.lerp(prev.scale.z, next.scale.z, t);
        
    } else if (prev) {
        model.position.set(prev.position.x, prev.position.y, prev.position.z);
        model.rotation.set(prev.rotation.x, prev.rotation.y, prev.rotation.z);
        model.scale.set(prev.scale.x, prev.scale.y, prev.scale.z);
    }
}

// GÉNÉRATION DE CODE
// GÉNÉRATION DE CODE COMPLET
function generateCode() {
    if (keyframes.length === 0) {
        document.getElementById('generated-code').value = '// Ajoutez des keyframes pour générer le code';
        document.getElementById('full-html-code').value = '<!-- Ajoutez des keyframes pour générer le code complet -->';
        document.getElementById('full-css-code').value = '/* Ajoutez des keyframes pour générer le code complet */';
        document.getElementById('full-js-code').value = '// Ajoutez des keyframes pour générer le code complet';
        return;
    }
    
    // Générer le JS (identique à avant)
    const jsCode = generateJSCode();
    document.getElementById('generated-code').value = jsCode;
    
    // Générer le HTML complet
    const htmlCode = generateHTMLCode();
    document.getElementById('full-html-code').value = htmlCode;
    
    // Générer le CSS complet
    const cssCode = generateCSSCode();
    document.getElementById('full-css-code').value = cssCode;
    
    // Générer le JS pour l'export
    document.getElementById('full-js-code').value = jsCode;
}

function generateJSCode() {
    return `// Code généré - Éditeur 3D No-Code
let scene, camera, renderer, model;
const keyframes = ${JSON.stringify(keyframes, null, 2)};

function init() {
    // Initialisation de base
    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    document.body.appendChild(renderer.domElement);
    
    // Lumières
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    scene.add(ambientLight);
    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(10, 20, 15);
    scene.add(directionalLight);
    
    // Charger votre modèle (remplacez l'URL)
    //ex: 'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/drone.glb'
    const loader = new THREE.GLTFLoader();
    loader.load('https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/drone.glb', function(gltf) {
        model = gltf.scene;
        scene.add(model);
        
        // Centrage automatique
        const box = new THREE.Box3().setFromObject(model);
        const center = box.getCenter(new THREE.Vector3());
        model.position.sub(center);
        
        // Configuration caméra
        camera.position.set(5, 5, 5);
        camera.lookAt(0, 0, 0);
        
        updateModelByScroll(0);
    });
    
    // Événements
    window.addEventListener('resize', onWindowResize);
    window.addEventListener('scroll', onScroll);
    
    // Animation
    animate();
}

function onWindowResize() {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
}

function onScroll() {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
    const scrollPercentage = (scrollTop / scrollHeight) * 100;
    
    updateModelByScroll(scrollPercentage);
}

function updateModelByScroll(percentage) {
    if (!model || keyframes.length === 0) return;
    
    let prev = null, next = null;
    
    // Trouver les keyframes adjacentes
    for (let i = 0; i < keyframes.length; i++) {
        if (keyframes[i].percentage <= percentage) prev = keyframes[i];
        if (keyframes[i].percentage >= percentage) {
            next = keyframes[i];
            break;
        }
    }
    
    if (!prev && next) prev = next;
    if (!next && prev) next = prev;
    
    if (prev && next && prev !== next) {
        const t = (percentage - prev.percentage) / (next.percentage - prev.percentage);
        
        // Interpolation linéaire
        model.position.x = THREE.MathUtils.lerp(prev.position.x, next.position.x, t);
        model.position.y = THREE.MathUtils.lerp(prev.position.y, next.position.y, t);
        model.position.z = THREE.MathUtils.lerp(prev.position.z, next.position.z, t);
        
        model.rotation.x = THREE.MathUtils.lerp(prev.rotation.x, next.rotation.x, t);
        model.rotation.y = THREE.MathUtils.lerp(prev.rotation.y, next.rotation.y, t);
        model.rotation.z = THREE.MathUtils.lerp(prev.rotation.z, next.rotation.z, t);
        
        model.scale.x = THREE.MathUtils.lerp(prev.scale.x, next.scale.x, t);
        model.scale.y = THREE.MathUtils.lerp(prev.scale.y, next.scale.y, t);
        model.scale.z = THREE.MathUtils.lerp(prev.scale.z, next.scale.z, t);
        
    } else if (prev) {
        model.position.set(prev.position.x, prev.position.y, prev.position.z);
        model.rotation.set(prev.rotation.x, prev.rotation.y, prev.rotation.z);
        model.scale.set(prev.scale.x, prev.scale.y, prev.scale.z);
    }
}

function animate() {
    requestAnimationFrame(animate);
    renderer.render(scene, camera);
}

// Démarrer
init();`;
}

function generateHTMLCode() {
    return `<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animation 3D avec Scroll</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Animation 3D Interactive</h1>
            <p>Faites défiler pour voir l'animation</p>
        </header>
        
        <div class="scroll-space"></div>
        
        <footer>
            <p>Créé avec l'Éditeur 3D No-Code</p>
        </footer>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.min.js"></script>
    <script src="script.js"></script>
</body>
</html>`;
}

function generateCSSCode() {
    return `/* Styles pour l'animation 3D avec scroll */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #1e1e2e 0%, #181825 100%);
    color: #cdd6f4;
    overflow-x: hidden;
}

.container {
    position: relative;
    z-index: 1;
}

header {
    height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    padding: 2rem;
}

header h1 {
    font-size: 3rem;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, #cba6f7 0%, #f5c2e7 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

header p {
    font-size: 1.2rem;
    opacity: 0.8;
}

.scroll-space {
    height: 200vh;
    position: relative;
}

footer {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    opacity: 0.7;
}

/* Canvas Three.js */
canvas {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
}

/* Responsive */
@media (max-width: 768px) {
    header h1 {
        font-size: 2rem;
    }
    
    header p {
        font-size: 1rem;
    }
}`;
}

// CONFIGURATION DES ÉVÉNEMENTS
function setupEventListeners() {
    // Import model
    document.getElementById('import-btn').addEventListener('click', () => {
        document.getElementById('model-input').click();
    });
    
    document.getElementById('model-input').addEventListener('change', (e) => {
        if (e.target.files[0]) loadModel(e.target.files[0]);
    });
    
    // Tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            
            currentTab = this.getAttribute('data-tab');
            document.getElementById(`${currentTab}-controls`).style.display = 'block';
        });
    });
    
    // Percentage slider
    document.getElementById('keyframe-percentage').addEventListener('input', function() {
        const percentage = this.value;
        document.getElementById('percentage-value').textContent = `${percentage}%`;
        updateRulerPosition(parseInt(percentage));
    });
    
    // Model controls
    const controls = ['pos-x', 'pos-y', 'pos-z', 'rot-x', 'rot-y', 'rot-z', 'scale-x', 'scale-y', 'scale-z'];
    controls.forEach(control => {
        document.getElementById(control).addEventListener('input', function() {
            if (!model) return;
            
            const value = parseFloat(this.value);
            
            if (control.startsWith('pos-')) {
                const axis = control.split('-')[1];
                model.position[axis] = value;
            } else if (control.startsWith('rot-')) {
                const axis = control.split('-')[1];
                model.rotation[axis] = THREE.MathUtils.degToRad(value);
            } else if (control.startsWith('scale-')) {
                const axis = control.split('-')[1];
                model.scale[axis] = value;
            }
        });
    });
    
    // Model scale
    document.getElementById('model-scale').addEventListener('input', function() {
        if (model) {
            const scale = parseFloat(this.value);
            model.scale.set(scale, scale, scale);
        }
    });
    
    // Add keyframe
    document.getElementById('add-keyframe').addEventListener('click', addKeyframe);
    
    // Copy code
    document.getElementById('copy-code').addEventListener('click', function() {
        const textarea = document.getElementById('generated-code');
        textarea.select();
        document.execCommand('copy');
        alert('✅ Code copié !');
    });
    
    // Ruler interaction
    const rulerTrack = document.getElementById('ruler-track');
    rulerTrack.addEventListener('mousedown', (e) => {
        isDragging = true;
        updateRulerFromEvent(e);
    });
    
    document.addEventListener('mousemove', (e) => {
        if (isDragging) updateRulerFromEvent(e);
    });
    
    document.addEventListener('mouseup', () => {
        isDragging = false;
    });
    
    function updateRulerFromEvent(e) {
        const rect = rulerTrack.getBoundingClientRect();
        let percentage = ((e.clientX - rect.left) / rect.width) * 100;
        percentage = Math.max(0, Math.min(100, Math.round(percentage)));
        
        document.getElementById('keyframe-percentage').value = percentage;
        document.getElementById('percentage-value').textContent = `${percentage}%`;
        updateRulerPosition(percentage);
    }
    
    // Preview scroll
    const previewScroll = document.getElementById('preview-scroll');
    previewScroll.addEventListener('mousedown', (e) => {
        isDragging = true;
        updatePreviewFromEvent(e);
    });
    
    function updatePreviewFromEvent(e) {
        const rect = previewScroll.getBoundingClientRect();
        let percentage = ((e.clientX - rect.left) / rect.width) * 100;
        percentage = Math.max(0, Math.min(100, Math.round(percentage)));
        
        document.getElementById('keyframe-percentage').value = percentage;
        document.getElementById('percentage-value').textContent = `${percentage}%`;
        updateRulerPosition(percentage);
    }
    
    // Window resize
    window.addEventListener('resize', () => {
        if (renderer && camera) {
            const viewer = document.getElementById('viewer');
            camera.aspect = viewer.offsetWidth / viewer.offsetHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(viewer.offsetWidth, viewer.offsetHeight);
        }
    });
}

// DÉMARRAGE DE L'APPLICATION
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM chargé, démarrage de l\'application...');

        // Gestion des onglets de code
    document.querySelectorAll('.code-exporter .tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Désactiver tous les tabs
            document.querySelectorAll('.code-exporter .tab').forEach(t => t.classList.remove('active'));
            // Activer ce tab
            this.classList.add('active');
            
            // Cacher tous les contenus
            document.querySelectorAll('.code-tab').forEach(content => {
                content.style.display = 'none';
            });
            
            // Afficher le contenu correspondant
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).style.display = 'block';
        });
    });
    
    /* Boutons de copie
    document.getElementById('copy-html').addEventListener('click', function() {
        copyToClipboard('full-html-code');
        alert('✅ HTML copié !');
    });
    
    document.getElementById('copy-css').addEventListener('click', function() {
        copyToClipboard('full-css-code');
        alert('✅ CSS copié !');
    });
    
    document.getElementById('copy-js').addEventListener('click', function() {
        copyToClipboard('full-js-code');
        alert('✅ JS copié !');
    });
    
    document.getElementById('copy-all').addEventListener('click', function() {
        const allCode = `=== HTML ===\n${document.getElementById('full-html-code').value}\n\n=== CSS ===\n${document.getElementById('full-css-code').value}\n\n=== JS ===\n${document.getElementById('full-js-code').value}`;
        copyTextToClipboard(allCode);
        alert('✅ Tout le code copié !');
    });
    */
    
    // Fonction utilitaire de copie
    function copyToClipboard(elementId) {
        const textarea = document.getElementById(elementId);
        textarea.select();
        document.execCommand('copy');
    }
    
    function copyTextToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
    }
    
    // Attendre que Three.js soit disponible
    function waitForThreeJS() {
        if (typeof THREE === 'undefined') {
            console.log('⏳ En attente de Three.js...');
            setTimeout(waitForThreeJS, 100);
            return;
        }
        
        if (typeof THREE.OrbitControls === 'undefined' || typeof THREE.GLTFLoader === 'undefined') {
            console.log('⏳ En attente des modules Three.js...');
            setTimeout(waitForThreeJS, 100);
            return;
        }
        
        console.log('✅ Three.js et modules détectés');
        initApplication();
    }
    
    waitForThreeJS();
});