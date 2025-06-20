// animation 3D
const scene = new THREE.Scene();

const camera = new THREE.PerspectiveCamera(75, 1, 0.1, 1000);
camera.position.z = 5;

const renderer = new THREE.WebGLRenderer({
    canvas: document.getElementById('dessertCanvas'),
    alpha: true // important : ne touche pas au fond défini en CSS
});
renderer.setSize(300, 300);

// GÉOMÉTRIE
const geometry = new THREE.DodecahedronGeometry(1.5, 0);
const material = new THREE.MeshStandardMaterial({
    color: 0x2575fc, // Bleu profond que tu m’as demandé
    metalness: 0.5,
    roughness: 0.2
});
const mesh = new THREE.Mesh(geometry, material);
scene.add(mesh);

// LUMIÈRES
const light = new THREE.PointLight(0xffffff, 1.2);
light.position.set(5, 5, 5);
scene.add(light);

const ambient = new THREE.AmbientLight(0xffffff, 0.6);
scene.add(ambient);

// ANIMATION
function animate() {
    requestAnimationFrame(animate);
    mesh.rotation.y += 0.01;
    mesh.rotation.x += 0.005;
    renderer.render(scene, camera);
}
animate();

// DISPARITION DU LOADER
window.addEventListener('load', () => {
    setTimeout(() => {
        const loader = document.getElementById('loader-3d');
        loader.style.opacity = '0';
        loader.style.pointerEvents = 'none';
    }, 1400);
});
