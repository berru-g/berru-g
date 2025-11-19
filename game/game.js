// Attendre que tout soit chargé
window.addEventListener('DOMContentLoaded', () => {
    // Vérifier que Three.js est chargé
    if (typeof THREE === 'undefined') {
        console.error('Three.js non chargé');
        alert('Three.js non chargé. Vérifie ta connexion internet.');
        return;
    }

    console.log('Three.js version:', THREE.REVISION);
    console.log('PointerLockControls disponible:', typeof THREE.PointerLockControls !== 'undefined');
    console.log('GLTFLoader disponible:', typeof THREE.GLTFLoader !== 'undefined');

    new GTAGame();
});

class GTAGame {
    constructor() {
        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        this.renderer = new THREE.WebGLRenderer({ antialias: true });
        
        this.setupRenderer();
        this.setupScene();
        this.setupWorld();
        
        this.isInCar = false;
        this.keys = {};
        this.velocity = new THREE.Vector3();
        
        // Variables pour les contrôles FPS
        this.moveForward = false;
        this.moveBackward = false;
        this.moveLeft = false;
        this.moveRight = false;
        this.prevTime = performance.now();
        
        this.player = null;
        this.car = null;
        this.mixer = null;
        this.animations = {};
        
        this.setupControls();
        this.setupEventListeners();
        this.loadAssets();
        this.animate();
    }
    
    setupRenderer() {
        this.renderer.setSize(window.innerWidth, window.innerHeight);
        this.renderer.setClearColor(0x87CEEB);
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        document.body.appendChild(this.renderer.domElement);
    }
    
    setupScene() {
        // Lumière ambiante
        const ambientLight = new THREE.AmbientLight(0x404040, 0.6);
        this.scene.add(ambientLight);
        
        // Lumière directionnelle (soleil)
        const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
        directionalLight.position.set(50, 50, 25);
        directionalLight.castShadow = true;
        directionalLight.shadow.mapSize.width = 1024;
        directionalLight.shadow.mapSize.height = 1024;
        this.scene.add(directionalLight);
        
        this.camera.position.set(0, 1.6, 0);
    }
    
    setupControls() {
        // Utiliser PointerLockControls si disponible
        if (typeof THREE.PointerLockControls !== 'undefined') {
            this.controls = new THREE.PointerLockControls(this.camera, document.body);
            console.log('PointerLockControls activé');
            
            this.controls.addEventListener('lock', () => {
                document.getElementById('instructions').style.display = 'none';
                document.getElementById('crosshair').style.display = 'block';
            });
            
            this.controls.addEventListener('unlock', () => {
                document.getElementById('instructions').style.display = 'block';
                document.getElementById('crosshair').style.display = 'none';
            });
            
            // Demander le verrouillage au clic
            this.renderer.domElement.addEventListener('click', () => {
                if (!this.controls.isLocked) {
                    this.controls.lock();
                }
            });
            
        } else {
            console.warn('Contrôles FPS basiques activés');
            this.setupBasicControls();
        }
    }
    
    setupBasicControls() {
        // Contrôles basiques sans PointerLock
        document.addEventListener('keydown', (event) => {
            switch (event.code) {
                case 'ArrowUp':
                case 'KeyW':
                    this.moveForward = true;
                    break;
                case 'ArrowLeft':
                case 'KeyA':
                    this.moveLeft = true;
                    break;
                case 'ArrowDown':
                case 'KeyS':
                    this.moveBackward = true;
                    break;
                case 'ArrowRight':
                case 'KeyD':
                    this.moveRight = true;
                    break;
            }
        });
        
        document.addEventListener('keyup', (event) => {
            switch (event.code) {
                case 'ArrowUp':
                case 'KeyW':
                    this.moveForward = false;
                    break;
                case 'ArrowLeft':
                case 'KeyA':
                    this.moveLeft = false;
                    break;
                case 'ArrowDown':
                case 'KeyS':
                    this.moveBackward = false;
                    break;
                case 'ArrowRight':
                case 'KeyD':
                    this.moveRight = false;
                    break;
            }
        });
        
        // Rotation caméra avec la souris
        let isMouseDown = false;
        let previousMouseX = 0;
        let previousMouseY = 0;
        
        this.renderer.domElement.addEventListener('mousedown', (event) => {
            isMouseDown = true;
            previousMouseX = event.clientX;
            previousMouseY = event.clientY;
        });
        
        this.renderer.domElement.addEventListener('mouseup', () => {
            isMouseDown = false;
        });
        
        this.renderer.domElement.addEventListener('mousemove', (event) => {
            if (!isMouseDown) return;
            
            const deltaX = event.clientX - previousMouseX;
            const deltaY = event.clientY - previousMouseY;
            
            this.camera.rotation.y -= deltaX * 0.002;
            this.camera.rotation.x -= deltaY * 0.002;
            this.camera.rotation.x = Math.max(-Math.PI/2, Math.min(Math.PI/2, this.camera.rotation.x));
            
            previousMouseX = event.clientX;
            previousMouseY = event.clientY;
        });
    }
    
    setupWorld() {
        // Sol
        const groundGeometry = new THREE.PlaneGeometry(200, 200);
        const groundMaterial = new THREE.MeshLambertMaterial({ color: 0x3a7d3a });
        const ground = new THREE.Mesh(groundGeometry, groundMaterial);
        ground.rotation.x = -Math.PI / 2;
        ground.receiveShadow = true;
        this.scene.add(ground);
        
        // Grille pour repères
        const gridHelper = new THREE.GridHelper(200, 20, 0x000000, 0x000000);
        gridHelper.material.opacity = 0.2;
        gridHelper.material.transparent = true;
        this.scene.add(gridHelper);
        
        // Bâtiments simples
        this.createBuildings();
        
        // Routes
        this.createRoads();
    }
    
    createBuildings() {
        const buildingColors = [0x888888, 0x666666, 0x777777, 0x999999];
        
        for (let i = 0; i < 15; i++) {
            const width = 8 + Math.random() * 8;
            const height = 10 + Math.random() * 20;
            const depth = 8 + Math.random() * 8;
            
            const buildingGeometry = new THREE.BoxGeometry(width, height, depth);
            const buildingMaterial = new THREE.MeshLambertMaterial({ 
                color: buildingColors[Math.floor(Math.random() * buildingColors.length)] 
            });
            
            const building = new THREE.Mesh(buildingGeometry, buildingMaterial);
            building.position.set(
                (Math.random() - 0.5) * 180,
                height / 2,
                (Math.random() - 0.5) * 180
            );
            building.castShadow = true;
            building.receiveShadow = true;
            this.scene.add(building);
        }
    }
    
    createRoads() {
        const roadMaterial = new THREE.MeshLambertMaterial({ color: 0x333333 });
        
        // Route principale X
        const roadX = new THREE.Mesh(new THREE.PlaneGeometry(200, 10), roadMaterial);
        roadX.rotation.x = -Math.PI / 2;
        roadX.position.set(0, 0.02, 0);
        this.scene.add(roadX);
        
        // Route principale Z
        const roadZ = new THREE.Mesh(new THREE.PlaneGeometry(10, 200), roadMaterial);
        roadZ.rotation.x = -Math.PI / 2;
        roadZ.position.set(0, 0.02, 0);
        this.scene.add(roadZ);
    }
    
    loadAssets() {
        this.createFallbackPlayer();
        this.createFallbackCar();
        
        // Charger les modèles 3D si GLTFLoader disponible
        if (typeof THREE.GLTFLoader !== 'undefined') {
            this.loadPlayerModel();
            this.loadCarModel();
        }
    }
    
    async loadPlayerModel() {
        try {
            const loader = new THREE.GLTFLoader();
            const gltf = await new Promise((resolve, reject) => {
                loader.load(
                    'https://models.readyplayer.me/69177cac28f4be8b0cc83e05.glb',
                    resolve,
                    undefined,
                    reject
                );
            });
            
            // Remplacer le joueur basique par le modèle RPM
            this.scene.remove(this.player);
            
            this.player = gltf.scene;
            this.player.scale.set(1, 1, 1);
            this.player.position.set(4, 4, 4);
            
            this.player.traverse((child) => {
                if (child.isMesh) {
                    child.castShadow = true;
                    child.receiveShadow = true;
                }
            });
            
            this.scene.add(this.player);
            
            // Animation mixer
            this.mixer = new THREE.AnimationMixer(this.player);
            
            if (gltf.animations && gltf.animations.length > 0) {
                gltf.animations.forEach((clip) => {
                    this.animations[clip.name] = this.mixer.clipAction(clip);
                });
                
                // Jouer la première animation disponible
                const firstAnim = this.animations[Object.keys(this.animations)[0]];
                if (firstAnim) {
                    firstAnim.play();
                }
            }
            
            console.log('Personnage ReadyPlayerMe chargé avec succès');
            
        } catch (error) {
            console.error('Erreur chargement modèle RPM:', error);
            // Garder le joueur basique
        }
    }
    
    async loadCarModel() {
        try {
            const loader = new THREE.GLTFLoader();
            
            // Voiture simple depuis un repository public
            const carUrl = 'https://threejs.org/examples/models/gltf/racing_helmet/racing_helmet.glb';
            // Alternative: https://threejs.org/examples/models/gltf/LeePerrySmith/LeePerrySmith.glb
            
            const gltf = await new Promise((resolve, reject) => {
                loader.load(
                    carUrl,
                    resolve,
                    undefined,
                    reject
                );
            });
            
            // Remplacer la voiture basique
            this.scene.remove(this.car);
            
            this.car = gltf.scene;
            this.car.scale.set(2, 2, 2);
            this.car.position.set(8, 0, 8);
            this.car.rotation.y = Math.PI / 4;
            
            this.car.traverse((child) => {
                if (child.isMesh) {
                    child.castShadow = true;
                    child.receiveShadow = true;
                }
            });
            
            this.scene.add(this.car);
            console.log('Modèle de voiture chargé avec succès');
            
        } catch (error) {
            console.error('Erreur chargement voiture:', error);
            // Garder la voiture basique
        }
    }
    
    createFallbackPlayer() {
        const group = new THREE.Group();
        
        // Corps (CapsuleGeometry n'existe pas dans r128, on utilise BoxGeometry)
        const bodyGeometry = new THREE.BoxGeometry(0.6, 1.8, 0.3);
        const bodyMaterial = new THREE.MeshLambertMaterial({ color: 0x00ff00 });
        const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
        body.position.y = 0.9;
        group.add(body);
        
        // Tête
        const headGeometry = new THREE.SphereGeometry(0.2, 8, 8);
        const headMaterial = new THREE.MeshLambertMaterial({ color: 0xffaa00 });
        const head = new THREE.Mesh(headGeometry, headMaterial);
        head.position.y = 1.7;
        group.add(head);
        
        this.player = group;
        this.player.position.set(2, 0, 2);
        this.scene.add(this.player);
    }
    
    createFallbackCar() {
        const group = new THREE.Group();
        
        // Corps de la voiture
        const carBodyGeometry = new THREE.BoxGeometry(2, 0.8, 4);
        const carBodyMaterial = new THREE.MeshLambertMaterial({ color: 0xff0000 });
        const carBody = new THREE.Mesh(carBodyGeometry, carBodyMaterial);
        carBody.position.y = 0.4;
        group.add(carBody);
        
        // Toit
        const roofGeometry = new THREE.BoxGeometry(1.5, 0.6, 1.5);
        const roofMaterial = new THREE.MeshLambertMaterial({ color: 0xcc0000 });
        const roof = new THREE.Mesh(roofGeometry, roofMaterial);
        roof.position.y = 1.1;
        roof.position.z = -0.5;
        group.add(roof);
        
        // Roues
        const wheelGeometry = new THREE.CylinderGeometry(0.3, 0.3, 0.2, 8);
        wheelGeometry.rotateZ(Math.PI / 2); // Rotation pour que le cylindre soit horizontal
        
        const wheelMaterial = new THREE.MeshLambertMaterial({ color: 0x333333 });
        
        const positions = [
            [1, 0.3, 1.2],   // avant droite
            [1, 0.3, -1.2],  // arrière droite
            [-1, 0.3, 1.2],  // avant gauche
            [-1, 0.3, -1.2]  // arrière gauche
        ];
        
        positions.forEach((pos) => {
            const wheel = new THREE.Mesh(wheelGeometry, wheelMaterial);
            wheel.position.set(pos[0], pos[1], pos[2]);
            group.add(wheel);
        });
        
        this.car = group;
        this.car.position.set(8, 0, 8);
        this.car.rotation.y = Math.PI / 4;
        
        this.car.traverse((child) => {
            if (child.isMesh) {
                child.castShadow = true;
                child.receiveShadow = true;
            }
        });
        
        this.scene.add(this.car);
    }
    
    setupEventListeners() {
        document.addEventListener('keydown', (event) => {
            this.keys[event.code] = true;
            
            if (event.code === 'KeyE') {
                this.toggleCar();
            }
            
            if (event.code === 'Escape' && this.controls && this.controls.isLocked) {
                this.controls.unlock();
            }
        });
        
        document.addEventListener('keyup', (event) => {
            this.keys[event.code] = false;
        });
        
        window.addEventListener('resize', () => {
            this.camera.aspect = window.innerWidth / window.innerHeight;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(window.innerWidth, window.innerHeight);
        });
    }
    
    toggleCar() {
        if (!this.car || !this.player) return;
        
        const distance = this.player.position.distanceTo(this.car.position);
        
        if (distance < 5) {
            this.isInCar = !this.isInCar;
            
            if (this.isInCar) {
                this.enterCar();
            } else {
                this.exitCar();
            }
            
            document.getElementById('status').textContent = 
                `Mode: ${this.isInCar ? 'Voiture' : 'À pied'}`;
        }
    }
    
    enterCar() {
        this.player.visible = false;
        
        if (this.controls && this.controls.isLocked) {
            this.controls.unlock();
        }
    }
    
    exitCar() {
        this.player.visible = true;
        this.player.position.copy(this.car.position);
        
        const direction = new THREE.Vector3();
        this.car.getWorldDirection(direction);
        direction.multiplyScalar(-3);
        
        this.player.position.add(direction);
        this.player.position.y = 0;
        
        this.camera.position.copy(this.player.position);
        this.camera.position.y += 1.6;
        this.camera.rotation.set(0, 0, 0);
    }
    
    updatePlayer(delta) {
        if (!this.player || this.isInCar) return;
        
        const speed = this.keys['ShiftLeft'] ? 8 : 4;
        
        if (this.controls && this.controls.isLocked) {
            // Contrôles PointerLock
            this.velocity.x = 0;
            this.velocity.z = 0;
            
            if (this.keys['KeyW']) this.velocity.z = -speed;
            if (this.keys['KeyS']) this.velocity.z = speed;
            if (this.keys['KeyA']) this.velocity.x = -speed;
            if (this.keys['KeyD']) this.velocity.x = speed;
            
            this.controls.moveForward(-this.velocity.z * delta);
            this.controls.moveRight(this.velocity.x * delta);
            
            // Mettre à jour la position du joueur
            this.player.position.copy(this.camera.position);
            this.player.position.y = 0;
            
        } else {
            // Contrôles basiques
            const actualSpeed = speed * delta;
            
            if (this.moveForward) {
                this.camera.translateZ(-actualSpeed);
                this.player.position.copy(this.camera.position);
                this.player.position.y = 0;
            }
            if (this.moveBackward) {
                this.camera.translateZ(actualSpeed);
                this.player.position.copy(this.camera.position);
                this.player.position.y = 0;
            }
            if (this.moveLeft) {
                this.camera.translateX(-actualSpeed);
                this.player.position.copy(this.camera.position);
                this.player.position.y = 0;
            }
            if (this.moveRight) {
                this.camera.translateX(actualSpeed);
                this.player.position.copy(this.camera.position);
                this.player.position.y = 0;
            }
        }
        
        this.updateAnimations();
    }
    
    updateCar(delta) {
        if (!this.car || !this.isInCar) return;
        
        const carSpeed = this.keys['ShiftLeft'] ? 20 : 10;
        const rotationSpeed = 2;
        
        if (this.keys['KeyW'] || this.moveForward) {
            this.car.translateZ(-carSpeed * delta);
        }
        if (this.keys['KeyS'] || this.moveBackward) {
            this.car.translateZ(carSpeed * delta);
        }
        if (this.keys['KeyA'] || this.moveLeft) {
            this.car.rotation.y += rotationSpeed * delta;
        }
        if (this.keys['KeyD'] || this.moveRight) {
            this.car.rotation.y -= rotationSpeed * delta;
        }
        
        // Faire suivre la caméra
        const carDirection = new THREE.Vector3();
        this.car.getWorldDirection(carDirection);
        
        const cameraOffset = carDirection.clone().multiplyScalar(-8);
        cameraOffset.y = 5;
        
        this.camera.position.copy(this.car.position).add(cameraOffset);
        this.camera.lookAt(this.car.position);
    }
    
    updateAnimations() {
        if (this.mixer) {
            this.mixer.update(0.016);
        }
    }
    
    animate() {
        requestAnimationFrame(() => this.animate());
        
        const time = performance.now();
        const delta = Math.min((time - this.prevTime) / 1000, 0.1);
        this.prevTime = time;
        
        if (this.isInCar) {
            this.updateCar(delta);
        } else {
            this.updatePlayer(delta);
        }
        
        this.renderer.render(this.scene, this.camera);
    }
}