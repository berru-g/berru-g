// Variables globales
let currentCard = null;

// Modifie la fonction createPointsOfInterest pour ajouter les labels
function createPointsOfInterest() {
    predefinedPOIs.forEach(poi => {
        // Repère 3D visible
        const poiGeometry = new THREE.ConeGeometry(3, 8, 4);
        const poiMaterial = new THREE.MeshPhongMaterial({
            color: 0x00ff88,
            emissive: 0x00aa55,
            emissiveIntensity: 0.5
        });
        const poiMesh = new THREE.Mesh(poiGeometry, poiMaterial);
        poiMesh.position.copy(poi.position);
        poiMesh.position.y = 4;
        poiMesh.rotation.x = Math.PI;
        poiMesh.userData = { poiInfo: poi };
        poiMesh.castShadow = true;
        scene.add(poiMesh);

        // Créer un label texte au-dessus
        createPOILabel(poiMesh, poi.title);

        // Lumière
        const pointLight = new THREE.PointLight(0x00ff88, 1, 30);
        pointLight.position.copy(poi.position);
        pointLight.position.y = 6;
        scene.add(pointLight);

        pointsOfInterest.push(poiMesh);
    });
}

// Fonction pour créer les labels des POIs
function createPOILabel(poiMesh, title) {
    // Cette fonction serait idéalement avec Three.js TextGeometry, 
    // mais pour simplifier on utilisera du HTML/CSS
    // On gérera l'affichage via la fonction updatePOILabels
}


let scene, camera, renderer, controls, vehicle;
let vehicleSpeed = 0, maxSpeed = 0.3, acceleration = 0.02;
let clock = new THREE.Clock();
let pointsOfInterest = [];
let activePoi = null;
let experienceStarted = false;

// État des touches
const keys = {
    'ArrowUp': false,
    'ArrowDown': false,
    'ArrowLeft': false,
    'ArrowRight': false
};

// Points d'intérêt
const predefinedPOIs = [
    {
        id: 'intro',
        title: 'Introduction',
        description: 'Découvrez mon parcours et mes compétences',
        position: new THREE.Vector3(-50, 0, -30)
    },
    {
        id: 'gallery',
        title: 'Galerie Projets',
        description: 'Explorez mes réalisations et créations',
        position: new THREE.Vector3(40, 0, -20)
    },
    {
        id: 'testimonials',
        title: '⭐ Avis Clients',
        description: 'Ce que disent mes clients et collaborateurs',
        position: new THREE.Vector3(-20, 0, 50)
    },
    {
        id: 'contact',
        title: 'Contact',
        description: 'Travaillons ensemble sur votre projet',
        position: new THREE.Vector3(30, 0, 40)
    }
];

function startExperience() {
    document.getElementById('instructions').classList.add('hidden');
    document.getElementById('loading').classList.add('hidden');
    experienceStarted = true;

    // Focus sur le renderer pour capturer les touches
    renderer.domElement.focus();
    setupControls();
}

function init() {
    console.log('Initialisation du portfolio 3D...');

    setupThreeJS();
    createScene();
    setupUI();

    // Cacher le loading après un délai
    setTimeout(() => {
        if (!experienceStarted) {
            document.getElementById('loading').classList.add('hidden');
        }
    }, 2000);
}

function setupThreeJS() {
    // Scene
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x001122);
    scene.fog = new THREE.Fog(0x001122, 50, 300);

    // Camera - positionnée pour bien voir le véhicule
    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 5, 8);

    // Renderer
    renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true
    });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.domElement.setAttribute('tabindex', '0');
    renderer.domElement.style.outline = 'none';
    document.getElementById('container').appendChild(renderer.domElement);

    // Controls
    controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.minDistance = 3;
    controls.maxDistance = 50;
}

function setupUI() {
    // Générer la liste des POIs
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
        poiList.appendChild(poiElement);
    });

    // Gestion des fichiers
    document.getElementById('glb-file').addEventListener('change', handleFileSelect);
}

function setupControls() {
    // Gestion robuste des touches
    document.addEventListener('keydown', (event) => {
        if (keys.hasOwnProperty(event.key)) {
            keys[event.key] = true;
            event.preventDefault();
        }
    });

    document.addEventListener('keyup', (event) => {
        if (keys.hasOwnProperty(event.key)) {
            keys[event.key] = false;
            event.preventDefault();
        }
    });

    // Re-focus quand on clique sur la scène
    renderer.domElement.addEventListener('click', () => {
        renderer.domElement.focus();
    });
}

function createScene() {
    // Lumière ambiante
    const ambientLight = new THREE.AmbientLight(0x404040, 0.6);
    scene.add(ambientLight);

    // Lumière directionnelle
    const sunLight = new THREE.DirectionalLight(0xffffff, 1);
    sunLight.position.set(50, 100, 50);
    sunLight.castShadow = true;
    scene.add(sunLight);

    // Terrain avec collines
    createTerrain();

    // Véhicule
    createVehicle();

    // Points d'intérêt
    createPointsOfInterest();

    // Décorations
    createEnvironmentDecorations();
}

function createTerrain() {
    // Terrain procédural avec collines
    const groundGeometry = new THREE.PlaneGeometry(300, 300, 100, 100);
    const groundMaterial = new THREE.MeshLambertMaterial({
        color: 0x4a7b3d
    });

    const ground = new THREE.Mesh(groundGeometry, groundMaterial);
    ground.rotation.x = -Math.PI / 2;
    ground.receiveShadow = true;

    // Collines procédurales
    const vertices = groundGeometry.attributes.position;
    for (let i = 0; i < vertices.count; i++) {
        const x = vertices.getX(i) * 0.05;
        const y = vertices.getY(i) * 0.05;
        const height = Math.sin(x) * Math.cos(y) * 10 +
            Math.sin(x * 0.3) * Math.cos(y * 0.3) * 5;
        vertices.setZ(i, height);
    }
    vertices.needsUpdate = true;
    groundGeometry.computeVertexNormals();

    scene.add(ground);

    /* Grille de référence
    const gridHelper = new THREE.GridHelper(300, 30, 0x000000, 0x333333);
    gridHelper.position.y = 0.1;
    scene.add(gridHelper);*/
}

function createVehicle() {
    const carGroup = new THREE.Group();

    // Corps principal
    const bodyGeometry = new THREE.BoxGeometry(2.5, 1.2, 5);
    const bodyMaterial = new THREE.MeshPhongMaterial({
        color: 0xff3366,
        shininess: 100
    });
    const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
    body.castShadow = true;
    carGroup.add(body);

    // Habitacle
    const cabinGeometry = new THREE.BoxGeometry(1.8, 0.8, 2.5);
    const cabinMaterial = new THREE.MeshPhongMaterial({ color: 0xcc2255 });
    const cabin = new THREE.Mesh(cabinGeometry, cabinMaterial);
    cabin.position.set(0, 1.1, -0.5);
    carGroup.add(cabin);

    // Roues
    const wheelGeometry = new THREE.CylinderGeometry(0.6, 0.6, 0.4, 16);
    const wheelMaterial = new THREE.MeshPhongMaterial({ color: 0x222222 });

    const wheelPositions = [
        [1.2, -0.6, 1.8], [-1.2, -0.6, 1.8],
        [1.2, -0.6, -1.8], [-1.2, -0.6, -1.8]
    ];

    wheelPositions.forEach(pos => {
        const wheel = new THREE.Mesh(wheelGeometry, wheelMaterial);
        wheel.position.set(pos[0], pos[1], pos[2]);
        wheel.rotation.z = Math.PI / 2;
        wheel.castShadow = true;
        carGroup.add(wheel);
    });

    vehicle = carGroup;
    vehicle.position.set(0, 3, 0);
    scene.add(vehicle);
}

function createPointsOfInterest() {
    predefinedPOIs.forEach(poi => {
        // Repère 3D visible
        const poiGeometry = new THREE.ConeGeometry(3, 8, 4);
        const poiMaterial = new THREE.MeshPhongMaterial({
            color: 0x00ff88,
            emissive: 0x00aa55,
            emissiveIntensity: 0.5
        });
        const poiMesh = new THREE.Mesh(poiGeometry, poiMaterial);
        poiMesh.position.copy(poi.position);
        poiMesh.position.y = 4;
        poiMesh.rotation.x = Math.PI;
        poiMesh.userData = { poiInfo: poi };
        poiMesh.castShadow = true;
        scene.add(poiMesh);

        // Lumière
        const pointLight = new THREE.PointLight(0x00ff88, 1, 30);
        pointLight.position.copy(poi.position);
        pointLight.position.y = 6;
        scene.add(pointLight);

        pointsOfInterest.push(poiMesh);
    });
}

function createEnvironmentDecorations() {
    // Arbres
    for (let i = 0; i < 50; i++) {
        createTree(
            (Math.random() - 0.5) * 280,
            (Math.random() - 0.5) * 280
        );
    }

    // Rochers
    for (let i = 0; i < 30; i++) {
        createRock(
            (Math.random() - 0.5) * 280,
            (Math.random() - 0.5) * 280
        );
    }
}

function createTree(x, z) {
    const treeGroup = new THREE.Group();

    const trunkGeometry = new THREE.CylinderGeometry(0.3, 0.5, 4, 8);
    const trunkMaterial = new THREE.MeshLambertMaterial({ color: 0x8B4513 });
    const trunk = new THREE.Mesh(trunkGeometry, trunkMaterial);
    treeGroup.add(trunk);

    const leavesGeometry = new THREE.SphereGeometry(2.5, 8, 6);
    const leavesMaterial = new THREE.MeshLambertMaterial({ color: 0x228B22 });
    const leaves = new THREE.Mesh(leavesGeometry, leavesMaterial);
    leaves.position.y = 3;
    treeGroup.add(leaves);

    treeGroup.position.set(x, 2, z);
    scene.add(treeGroup);
}

function createRock(x, z) {
    const rockGeometry = new THREE.DodecahedronGeometry(1 + Math.random() * 2, 1);
    const rockMaterial = new THREE.MeshLambertMaterial({ color: 0x666666 });
    const rock = new THREE.Mesh(rockGeometry, rockMaterial);
    rock.position.set(x, 1, z);
    rock.rotation.set(Math.random() * Math.PI, Math.random() * Math.PI, Math.random() * Math.PI);
    scene.add(rock);
}

function updateVehicle() {
    if (!vehicle || !experienceStarted) return;

    const delta = clock.getDelta();

    // Contrôles
    if (keys['ArrowUp']) {
        vehicleSpeed = Math.min(vehicleSpeed + acceleration, maxSpeed);
    }
    if (keys['ArrowDown']) {
        vehicleSpeed = Math.max(vehicleSpeed - acceleration, -maxSpeed * 0.5);
    }

    if (keys['ArrowLeft']) {
        vehicle.rotation.y += 0.03;
    }
    if (keys['ArrowRight']) {
        vehicle.rotation.y -= 0.03;
    }

    // Appliquer le mouvement
    const direction = new THREE.Vector3(0, 0, -1);
    direction.applyQuaternion(vehicle.quaternion);
    vehicle.position.add(direction.multiplyScalar(vehicleSpeed));

    // Ajuster la hauteur selon le terrain
    adjustVehicleHeight();

    // Friction
    vehicleSpeed *= 0.95;
    if (Math.abs(vehicleSpeed) < 0.001) vehicleSpeed = 0;

    // Mettre à jour la caméra pour suivre parfaitement le véhicule
    updateCamera();

    // Vérifier les points d'intérêt
    checkPOIProximity();

    // Mettre à jour l'interface
    updateHUD();
}

function adjustVehicleHeight() {
    // Simulation de hauteur de terrain
    const terrainHeight = Math.sin(vehicle.position.x * 0.02) * Math.cos(vehicle.position.z * 0.02) * 8 + 2;
    vehicle.position.y = terrainHeight + 1.5;
}

function updateCamera() {
    if (!vehicle) return;

    // La caméra suit le véhicule de manière fixe
    // Position relative derrière et au-dessus du véhicule
    const cameraOffset = new THREE.Vector3(0, 3, 6);
    cameraOffset.applyQuaternion(vehicle.quaternion);

    const targetCameraPos = vehicle.position.clone().add(cameraOffset);

    // Déplacement progressif de la caméra
    camera.position.lerp(targetCameraPos, 0.1);

    // Regarder légèrement devant le véhicule
    const lookAtPos = vehicle.position.clone();
    const lookAtOffset = new THREE.Vector3(0, 0, -3);
    lookAtOffset.applyQuaternion(vehicle.quaternion);
    camera.lookAt(lookAtPos.add(lookAtOffset));

    // Mettre à jour le point de focus des contrôles orbitaux
    controls.target.copy(vehicle.position);
    controls.target.y += 1;
}

function checkPOIProximity() {
    if (!vehicle) return;

    let nearestPoi = null;
    let minDistance = Infinity;

    pointsOfInterest.forEach(poiMesh => {
        const distance = vehicle.position.distanceTo(poiMesh.position);
        if (distance < 15 && distance < minDistance) {
            minDistance = distance;
            nearestPoi = poiMesh.userData.poiInfo;
        }
    });

    if (nearestPoi && nearestPoi !== activePoi) {
        activePoi = nearestPoi;
        document.getElementById('target-display').textContent = nearestPoi.title;
        document.getElementById('target-display').style.color = '#00ff88';

        // Ouvre automatiquement la carte quand on arrive sur le POI
        openCard(nearestPoi.id + '-card');
    } else if (!nearestPoi && activePoi) {
        activePoi = null;
        document.getElementById('target-display').textContent = 'Exploration';
        document.getElementById('target-display').style.color = '#00ff88';

        // Ferme la carte quand on quitte le POI
        if (currentCard) {
            closeCard(currentCard);
        }
    }
}

// Fonctions pour gérer les cartes
function openCard(cardId) {
    // Ferme la carte actuelle si il y en a une
    if (currentCard) {
        closeCard(currentCard);
    }

    // Ouvre la nouvelle carte
    const card = document.getElementById(cardId);
    if (card) {
        card.style.display = 'block';
        currentCard = cardId;
    }
}

function closeCard(cardId) {
    const card = document.getElementById(cardId);
    if (card) {
        card.style.display = 'none';
        currentCard = null;
    }
}

// Gestion du formulaire de contact
document.querySelector('.contact-form')?.addEventListener('submit', function (e) {
    e.preventDefault();
    alert('Message envoyé ! Merci pour votre contact.');
    closeCard('contact-card');
});

// Pour les liens de la gallery
document.querySelectorAll('.gallery-item a').forEach(link => {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        alert('Ouverture du projet en détail...');
    });
});

function updateHUD() {
    const speed = Math.abs(Math.round(vehicleSpeed * 100));
    const posX = Math.round(vehicle.position.x);
    const posZ = Math.round(vehicle.position.z);

    document.getElementById('speed-display').textContent = `${speed} km/h`;
    document.getElementById('position-display').textContent = `${posX}, ${posZ}`;
}

function toggleMenu() {
    document.getElementById('menu-panel').classList.toggle('active');
}

function navigateToPOI(poiId) {
    const poi = predefinedPOIs.find(p => p.id === poiId);
    if (poi && vehicle) {
        vehicle.position.copy(poi.position);
        vehicle.position.x += 8;
        vehicle.rotation.y = Math.PI;
        toggleMenu();
    }
}

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;
    console.log('Chargement du fichier:', file.name);
    // Implémentation du chargement GLB à venir
}

function loadSampleScene() {
    while (scene.children.length > 0) {
        scene.remove(scene.children[0]);
    }
    createScene();
    toggleMenu();
}

function resetVehicle() {
    if (vehicle) {
        vehicle.position.set(0, 3, 0);
        vehicle.rotation.set(0, 0, 0);
        vehicleSpeed = 0;
    }
    toggleMenu();
}

function onWindowResize() {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
}

function animate() {
    requestAnimationFrame(animate);
    updateVehicle();
    controls.update();
    renderer.render(scene, camera);
}

// Initialisation
window.addEventListener('resize', onWindowResize);
init();
animate();

console.log('Clique sur "Commencer l\'exploration" puis utilise les flèches !');