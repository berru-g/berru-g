// Simuler le chargement
window.addEventListener('load', function () {
    setTimeout(function () {
        document.querySelector('.loader').style.opacity = '0';
        setTimeout(function () {
            document.querySelector('.loader').style.display = 'none';
        }, 800);
    }, 2000);
});

// Créer des particules décoratives
function createParticles() {
    const container = document.getElementById('particles');
    const particleCount = 30;

    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');

        // Propriétés aléatoires
        const size = Math.random() * 5 + 2;
        const posX = Math.random() * 100;
        const posY = Math.random() * 100;
        const delay = Math.random() * 5;
        const duration = Math.random() * 10 + 10;

        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.left = `${posX}%`;
        particle.style.top = `${posY}%`;
        particle.style.animationDelay = `${delay}s`;
        particle.style.animationDuration = `${duration}s`;

        // Couleur aléatoire
        const colors = ['#7b4bff', '#00e0ff', '#ff2a78'];
        particle.style.background = colors[Math.floor(Math.random() * colors.length)];

        container.appendChild(particle);
    }
}

createParticles();

// Variables globales
let scene, camera, renderer, model, mixer;
let controls, clock;
let initialScale = 0.5; // Stocker l'échelle initiale

// Initialisation de Three.js
function init() {
    // Créer la scène
    scene = new THREE.Scene();
    // Pour un fond transparent, on utilise null
    scene.background = null;

    // Créer la caméra
    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 0, 5);

    // Créer le renderer avec alpha pour transparence
    renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true // Important pour fond transparent
    });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.outputEncoding = THREE.sRGBEncoding;
    document.getElementById('container3D').appendChild(renderer.domElement);

    // Ajouter des contrôles orbitaux
    controls = new THREE.OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;

    // Ajouter des lumières
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(ambientLight);

    const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
    directionalLight.position.set(5, 5, 5);
    scene.add(directionalLight);

    // Ajouter une lumière supplémentaire pour mieux éclairer le modèle
    const pointLight = new THREE.PointLight(0x6b5ce7, 1, 100);
    pointLight.position.set(5, 5, 5);
    scene.add(pointLight);

    // Initialiser l'horloge pour les animations
    clock = new THREE.Clock();

    // Charger le modèle 3D
    loadModel();

    // Gestion du redimensionnement
    window.addEventListener('resize', onWindowResize);

    // Gestion du scroll
    window.addEventListener('scroll', onScroll);

    // Commencer l'animation
    animate();
}

function loadModel() {
    const loader = new THREE.GLTFLoader();

    // Utilisation d'un modèle 3D glb ou gltf plus léger assets/nebula_skybox_16k.glb
    //https://cdn.jsdelivr.net/gh/mrdoob/three.js@dev/examples/models/gltf/Parrot.glb
    const modelUrl = 'https://raw.githubusercontent.com/berru-g/3d-scroll-animate/main/assets/meteor.glb';
    //'https://raw.githubusercontent.com/berru-g/3d-scroll-animate/main/assets/cave_on_an_alien_planet_skybox.glb';

    loader.load(
        modelUrl,
        function (gltf) {
            model = gltf.scene;
            scene.add(model);

            // Ajuster l'échelle et la position si nécessaire
            initialScale = 0.5; // Définir l'échelle initiale
            model.scale.set(initialScale, initialScale, initialScale);
            model.position.set(0, -1, 0); // Ajusté pour mieux centrer

            // Configurer les animations s'il y en a
            if (gltf.animations && gltf.animations.length) {
                mixer = new THREE.AnimationMixer(model);
                gltf.animations.forEach((clip) => {
                    mixer.clipAction(clip).play();
                });
            }

            // Centrer le modèle et ajuster les contrôles
            const box = new THREE.Box3().setFromObject(model);
            const center = box.getCenter(new THREE.Vector3());
            const size = box.getSize(new THREE.Vector3());

            controls.target.copy(center);
            controls.update();

            // Cacher le loader une fois le modèle chargé
            if (document.querySelector('.loader')) {
                document.querySelector('.loader').style.opacity = 0;
                setTimeout(() => {
                    document.querySelector('.loader').style.display = 'none';
                }, 500);
            }
        },
        function (xhr) {
            // Progression du chargement
            console.log((xhr.loaded / xhr.total * 100) + '% loaded');
        },
        function (error) {
            console.error('Erreur lors du chargement du modèle:', error);
            // En cas d'erreur, afficher un cube à la place
            showFallbackModel();
        }
    );
}

function showFallbackModel() {
    // Créer un cube à la place si le modèle ne charge pas
    const geometry = new THREE.BoxGeometry(0.4, 0.4, 0.4);
    const material = new THREE.MeshPhongMaterial({
        color: 0x6b5ce7,
        shininess: 100,
        specular: 0xfd79a8
    });

    model = new THREE.Mesh(geometry, material);
    scene.add(model);

    // Cacher le loader
    if (document.querySelector('.loader')) {
        document.querySelector('.loader').style.opacity = 0;
        setTimeout(() => {
            document.querySelector('.loader').style.display = 'none';
        }, 500);
    }
}

function onWindowResize() {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
}

// function onScroll

// mouvement 360*360 + zoom + changement couleur + intensité lumière
function onScroll() {
    if (!model) return;

    // Calculer la progression du scroll (0 à 1)
    const scrollY = window.scrollY;
    const totalHeight = document.body.scrollHeight - window.innerHeight;
    const scrollPercentage = Math.min(scrollY / totalHeight, 1);

    // Appliquer des transformations basées sur le scroll
    model.rotation.x = scrollPercentage * Math.PI * 0.1;
    model.rotation.y = scrollPercentage * Math.PI * 0.2;
    model.rotation.z = scrollPercentage * Math.PI;

    // Modifier l'échelle en fonction du scroll en utilisant l'échelle initiale comme base
    const scale = initialScale + scrollPercentage * 7;
    model.scale.set(scale, scale, scale);

    // Modifier la position en Y pour un effet de "lévitation"
    model.position.y = -1 + scrollPercentage * 2;

    // Changer la couleur du matériau si c'est un Mesh (pour le fallback)
    if (model.material) {
        const hue = (scrollPercentage * 360) % 360;
        model.material.color.setHSL(hue / 360, 0.8, 0.5);
    }

    // Ajuster l'intensité des lumières en fonction du scroll
    const lights = scene.children.filter(child => child.isLight);
    lights.forEach(light => {
        light.intensity = 0.5 + scrollPercentage * 1.5;
    });
}

/*
// variante simple rotation + translation horizontale
function onScroll() {
    if (!model) return;

    const scrollY = window.scrollY;
    const totalHeight = document.body.scrollHeight - window.innerHeight;
    const scrollPercentage = Math.min(scrollY / totalHeight, 1);

    // Rotation
    model.rotation.y = scrollPercentage * Math.PI * 2;
    
    // Translation horizontale
    model.position.x = (scrollPercentage - 0.5) * 4;
}

// Apparition progressive + rotation
function onScroll() {
    if (!model) return;

    const scrollY = window.scrollY;
    const totalHeight = document.body.scrollHeight - window.innerHeight;
    const scrollPercentage = Math.min(scrollY / totalHeight, 1);

    // Rotation
    model.rotation.y = scrollPercentage * Math.PI * 2;
    
    // Apparition progressive (si ton matériau le supporte)
    if (model.material) {
        model.material.opacity = scrollPercentage;
        model.material.transparent = true;
    }
}

// Rotation avec oscillation (effet de vague)
function onScroll() {
    if (!model) return;

    const scrollY = window.scrollY;
    const totalHeight = document.body.scrollHeight - window.innerHeight;
    const scrollPercentage = Math.min(scrollY / totalHeight, 1);

    // Rotation principale
    model.rotation.y = scrollPercentage * Math.PI * 2;
    
    // Oscillation supplémentaire (effet de vague)
    model.rotation.x = Math.sin(scrollPercentage * Math.PI * 4) * 0.5;
    model.position.y = Math.sin(scrollPercentage * Math.PI * 2) * 2;
}
    

// variante zoom + tour sur lui meme
function onScroll() {
    if (!model) return;

    const scrollY = window.scrollY;
    const totalHeight = document.body.scrollHeight - window.innerHeight;
    const scrollPercentage = Math.min(scrollY / totalHeight, 1);

    // Rotation simple
    model.rotation.y = scrollPercentage * Math.PI * 2;
    
    // Zoom progressif
    const scale = initialScale + scrollPercentage;
    model.scale.set(scale, scale, scale);
}
*/

// Animate function
function animate() {
    requestAnimationFrame(animate);

    // Mettre à jour les animations du modèle
    if (mixer) {
        mixer.update(clock.getDelta());
    }

    // Mise à jour des contrôles
    controls.update();

    // Rendu de la scène
    renderer.render(scene, camera);
}

// Démarrer l'application après le chargement de la page
window.addEventListener('load', function () {
    // Petite temporisation pour s'assurer que tout est chargé
    setTimeout(init, 100);
});