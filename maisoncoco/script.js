// Initialisation de AOS
document.addEventListener('DOMContentLoaded', function () {
    AOS.init({
        duration: 1200,
        once: true,
        easing: 'ease-out-back'
    });
});

// Configuration Three.js
let scene, camera, renderer;
let geometry, material, mesh;
let particles, particleSystem;

function init() {
    // Création de la scène
    scene = new THREE.Scene();

    // Configuration de la caméra
    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.z = 5;

    // Configuration du rendu
    renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setClearColor(0x000000, 0);
    document.getElementById('three-container').appendChild(renderer.domElement);

    // Ajout de lumières
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(ambientLight);

    const directionalLight = new THREE.DirectionalLight(0xFFD700, 1);
    directionalLight.position.set(5, 5, 5);
    scene.add(directionalLight);

    // Création d'un objet 3D (sphère)
    geometry = new THREE.SphereGeometry(1, 32, 32);
    material = new THREE.MeshPhongMaterial({
        color: 0xFFD700,
        shininess: 100,
        transparent: true,
        opacity: 0.7
    });
    mesh = new THREE.Mesh(geometry, material);
    mesh.position.set(0, 0, 0);
    scene.add(mesh);

    // Création de particules
    const particlesGeometry = new THREE.BufferGeometry();
    const particlesCount = 1000;

    const posArray = new Float32Array(particlesCount * 3);
    const colorArray = new Float32Array(particlesCount * 3);

    for (let i = 0; i < particlesCount * 3; i++) {
        posArray[i] = (Math.random() - 0.5) * 10;
        colorArray[i] = Math.random();
    }

    particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
    particlesGeometry.setAttribute('color', new THREE.BufferAttribute(colorArray, 3));

    const particlesMaterial = new THREE.PointsMaterial({
        size: 0.05,
        vertexColors: true,
        transparent: true,
        opacity: 0.8
    });

    particleSystem = new THREE.Points(particlesGeometry, particlesMaterial);
    scene.add(particleSystem);

    // Gestion du redimensionnement
    window.addEventListener('resize', onWindowResize, false);
}

function onWindowResize() {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
}

function animate() {
    requestAnimationFrame(animate);

    // Animation de la sphère
    mesh.rotation.x += 0.005;
    mesh.rotation.y += 0.005;

    // Animation des particules
    particleSystem.rotation.y += 0.001;

    renderer.render(scene, camera);
}

// Lancement de l'animation Three.js
init();
animate();

// Effet de parallaxe au défilement
window.addEventListener('scroll', function () {
    const scrollY = window.scrollY;
    mesh.position.y = scrollY * 0.001;
    particleSystem.position.y = -scrollY * 0.0005;
});