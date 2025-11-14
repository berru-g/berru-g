import * as THREE from "https://unpkg.com/three@0.164.0/build/three.module.js";
import { GLTFLoader } from "https://unpkg.com/three@0.164.0/examples/jsm/loaders/GLTFLoader.js";

const canvas = document.getElementById("scene");

// SCENE
const scene = new THREE.Scene();

const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 100);
camera.position.set(0, 1.6, 2);

// RENDERER
const renderer = new THREE.WebGLRenderer({
  canvas: canvas,
  antialias: true,
});
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setPixelRatio(window.devicePixelRatio);

// LIGHTS
const light = new THREE.DirectionalLight(0xffffff, 1);
light.position.set(1, 1, 1);
scene.add(light);

// LOAD READY PLAYER ME AVATAR
const loader = new GLTFLoader();
const avatarURL = "https://models.readyplayer.me/63b4e71f409c0d001c8d26df.glb"; // avatar public RPM

let avatar;

loader.load(
  avatarURL,
  (gltf) => {
    avatar = gltf.scene;
    avatar.position.set(0, -1.3, 0);
    avatar.scale.set(1, 1, 1);
    scene.add(avatar);
  },
  undefined,
  (err) => console.error("Erreur chargement avatar RPM:", err)
);

// RENDER LOOP
function animate() {
  requestAnimationFrame(animate);
  renderer.render(scene, camera);
}
animate();

// SIMPLE CHAT (à améliorer ensuite)
document.getElementById("send").onclick = () => {
  let text = document.getElementById("prompt").value;
  console.log("Message envoyé:", text);
};
