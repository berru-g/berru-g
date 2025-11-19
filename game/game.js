// Attendre que tout soit chargé
window.addEventListener('DOMContentLoaded', () => {
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
        this.renderer = new THREE.WebGLRenderer({
            antialias: true,
            powerPreference: "high-performance"
        });

        // Initialiser les textures avant tout
        this.textures = {};
        this.materials = {};

        this.setupRenderer();
        this.setupScene();

        this.isInCar = false;
        this.keys = {};
        this.velocity = new THREE.Vector3();
        this.playerOnGround = true;

        // Variables pour les contrôles FPS améliorés
        this.moveForward = false;
        this.moveBackward = false;
        this.moveLeft = false;
        this.moveRight = false;
        this.prevTime = performance.now();
        this.canJump = true;

        // Physique améliorée
        this.gravity = -30;
        this.jumpForce = 10;
        this.playerVelocity = new THREE.Vector3();

        this.player = null;
        this.car = null;
        this.mixer = null;
        this.animations = {};
        this.currentAnimation = null;

        // États d'animation étendus
        this.animationStates = {
            IDLE: 'idle',
            WALK: 'walk',
            RUN: 'run',
            JUMP: 'jump',
            SIT: 'sit',
            FALL: 'fall'
        };
        this.currentAnimationState = this.animationStates.IDLE;

        // État du jeu
        this.assetsLoaded = 0;
        this.totalAssets = 2;

        this.setupControls();
        this.setupEventListeners();

        // Charger les textures d'abord, puis le monde
        this.loadTextures().then(() => {
            this.setupWorld();
            this.loadAssets();
            this.animate();
        }).catch(error => {
            console.error('Erreur chargement textures:', error);
            // Créer des textures de fallback
            this.createFallbackTextures();
            this.setupWorld();
            this.loadAssets();
            this.animate();
        });
    }

    createFallbackTextures() {
        console.log('Création de textures de secours...');
        this.textures = {
            ground: null,
            road: null,
            building: null
        };
    }

    setupRenderer() {
        this.renderer.setSize(window.innerWidth, window.innerHeight);
        this.renderer.setClearColor(0x87CEEB);
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        this.renderer.physicallyCorrectLights = true;
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1;
        document.body.appendChild(this.renderer.domElement);
    }

    setupScene() {
        // Lumière ambiante améliorée
        const ambientLight = new THREE.AmbientLight(0x404040, 0.8);
        this.scene.add(ambientLight);

        // Lumière directionnelle améliorée
        const directionalLight = new THREE.DirectionalLight(0xffffff, 1.2);
        directionalLight.position.set(50, 100, 25);
        directionalLight.castShadow = true;
        directionalLight.shadow.mapSize.width = 2048;
        directionalLight.shadow.mapSize.height = 2048;
        directionalLight.shadow.camera.near = 0.5;
        directionalLight.shadow.camera.far = 500;
        directionalLight.shadow.camera.left = -100;
        directionalLight.shadow.camera.right = 100;
        directionalLight.shadow.camera.top = 100;
        directionalLight.shadow.camera.bottom = -100;
        this.scene.add(directionalLight);

        // Lumière supplémentaire pour meilleur éclairage
        const hemisphereLight = new THREE.HemisphereLight(0xffffbb, 0x080820, 0.4);
        this.scene.add(hemisphereLight);

        this.camera.position.set(0, 1.6, 5);

        // Brouillard pour ambiance
        this.scene.fog = new THREE.Fog(0x87CEEB, 50, 300);
    }

    async loadTextures() {
        console.log('Chargement des textures...');

        try {
            // Textures procédurales de base
            this.textures.ground = this.createProceduralTexture(0x3a7d3a, 'ground');
            this.textures.road = this.createProceduralTexture(0x333333, 'road');
            this.textures.building = this.createProceduralTexture(0x888888, 'building');

            console.log('Textures chargées avec succès');
        } catch (error) {
            console.warn('Erreur chargement textures, utilisation des couleurs basiques:', error);
            throw error; // Propager l'erreur pour le catch dans le constructor
        }
    }

    createProceduralTexture(baseColor, type) {
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 256;
        const context = canvas.getContext('2d');

        // Couleur de base
        context.fillStyle = `#${baseColor.toString(16).padStart(6, '0')}`;
        context.fillRect(0, 0, 256, 256);

        // Ajouter du bruit pour la texture
        if (type === 'ground') {
            context.fillStyle = '#2a6d2a';
            for (let i = 0; i < 500; i++) {
                context.fillRect(
                    Math.random() * 256,
                    Math.random() * 256,
                    2, 2
                );
            }
        } else if (type === 'road') {
            context.fillStyle = '#ffffff';
            // Lignes de route
            for (let y = 32; y < 256; y += 64) {
                context.fillRect(0, y, 256, 8);
            }
        } else if (type === 'building') {
            context.fillStyle = '#666666';
            // Motif de briques
            for (let x = 0; x < 256; x += 16) {
                for (let y = 0; y < 256; y += 8) {
                    if ((x / 16 + y / 8) % 2 === 0) {
                        context.fillRect(x, y, 14, 6);
                    }
                }
            }
        }

        const texture = new THREE.CanvasTexture(canvas);
        texture.wrapS = THREE.RepeatWrapping;
        texture.wrapT = THREE.RepeatWrapping;

        if (type === 'ground') {
            texture.repeat.set(20, 20);
        } else if (type === 'road') {
            texture.repeat.set(1, 20);
        } else if (type === 'building') {
            texture.repeat.set(4, 4);
        }

        return texture;
    }

    setupControls() {
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
                case 'Space':
                    if (this.canJump && this.playerOnGround) this.jump();
                    break;
                case 'ShiftLeft':
                    this.isRunning = true;
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
                case 'ShiftLeft':
                    this.isRunning = false;
                    break;
            }
        });

        let isMouseDown = false;
        let previousMouseX = 0;
        let previousMouseY = 0;

        this.renderer.domElement.addEventListener('mousedown', (event) => {
            isMouseDown = true;
            previousMouseX = event.clientX;
            previousMouseY = event.clientY;
            this.renderer.domElement.requestPointerLock();
        });

        document.addEventListener('mouseup', () => {
            isMouseDown = false;
        });

        document.addEventListener('mousemove', (event) => {
            if (!isMouseDown || document.pointerLockElement !== this.renderer.domElement) return;

            const movementX = event.movementX || 0;
            const movementY = event.movementY || 0;

            this.camera.rotation.y -= movementX * 0.002;
            this.camera.rotation.x -= movementY * 0.002;
            this.camera.rotation.x = Math.max(-Math.PI / 2, Math.min(Math.PI / 2, this.camera.rotation.x));
        });
    }

    setupWorld() {
        this.createGround();
        this.createRoads();
        this.createBuildings();
        this.createEnvironment();
    }

    createGround() {
        const groundGeometry = new THREE.PlaneGeometry(200, 200);

        // Utiliser la texture si disponible, sinon couleur basique
        const groundMaterial = this.textures.ground
            ? new THREE.MeshLambertMaterial({
                map: this.textures.ground,
                color: 0x3a7d3a
            })
            : new THREE.MeshLambertMaterial({ color: 0x3a7d3a });

        const ground = new THREE.Mesh(groundGeometry, groundMaterial);
        ground.rotation.x = -Math.PI / 2;
        ground.receiveShadow = true;
        this.scene.add(ground);

        const gridHelper = new THREE.GridHelper(200, 20, 0x000000, 0x000000);
        gridHelper.material.opacity = 0.1;
        gridHelper.material.transparent = true;
        this.scene.add(gridHelper);
    }

    createRoads() {
        // Material pour les routes avec texture ou couleur basique
        const roadMaterial = this.textures.road
            ? new THREE.MeshLambertMaterial({
                map: this.textures.road,
                color: 0x333333
            })
            : new THREE.MeshLambertMaterial({ color: 0x333333 });

        // Route principale X
        const roadX = new THREE.Mesh(new THREE.PlaneGeometry(200, 12), roadMaterial);
        roadX.rotation.x = -Math.PI / 2;
        roadX.position.set(0, 0.02, 0);
        roadX.receiveShadow = true;
        this.scene.add(roadX);

        // Route principale Z
        const roadZ = new THREE.Mesh(new THREE.PlaneGeometry(12, 200), roadMaterial);
        roadZ.rotation.x = -Math.PI / 2;
        roadZ.position.set(0, 0.02, 0);
        roadZ.receiveShadow = true;
        this.scene.add(roadZ);

        // Trottoirs
        this.createSidewalks();
    }

    createSidewalks() {
        const sidewalkMaterial = new THREE.MeshLambertMaterial({ color: 0xcccccc });

        // Trottoirs le long des routes
        const sidewalkPositions = [
            { position: [0, 0.03, -6], size: [200, 2] },
            { position: [0, 0.03, 6], size: [200, 2] },
            { position: [-6, 0.03, 0], size: [2, 200] },
            { position: [6, 0.03, 0], size: [2, 200] }
        ];

        sidewalkPositions.forEach(pos => {
            const sidewalk = new THREE.Mesh(
                new THREE.PlaneGeometry(pos.size[0], pos.size[1]),
                sidewalkMaterial
            );
            sidewalk.rotation.x = -Math.PI / 2;
            sidewalk.position.set(pos.position[0], pos.position[1], pos.position[2]);
            sidewalk.receiveShadow = true;
            this.scene.add(sidewalk);
        });
    }

    createBuildings() {
        const buildingColors = [0x888888, 0x666666, 0x777777, 0x999999, 0x555555];

        for (let i = 0; i < 20; i++) {
            const width = 8 + Math.random() * 12;
            const height = 15 + Math.random() * 25;
            const depth = 8 + Math.random() * 12;

            const buildingGeometry = new THREE.BoxGeometry(width, height, depth);

            // Utiliser texture building si disponible
            const buildingMaterial = this.textures.building
                ? new THREE.MeshLambertMaterial({
                    map: this.textures.building,
                    color: buildingColors[Math.floor(Math.random() * buildingColors.length)]
                })
                : new THREE.MeshLambertMaterial({
                    color: buildingColors[Math.floor(Math.random() * buildingColors.length)]
                });

            const building = new THREE.Mesh(buildingGeometry, buildingMaterial);

            // Positionner les bâtiments de manière plus réaliste
            let x, z;
            do {
                x = (Math.random() - 0.5) * 160;
                z = (Math.random() - 0.5) * 160;
            } while (Math.abs(x) < 20 && Math.abs(z) < 20); // Éviter le centre

            building.position.set(x, height / 2, z);
            building.castShadow = true;
            building.receiveShadow = true;

            // Ajouter des détails aux bâtiments
            this.addBuildingDetails(building, width, height, depth);
            this.scene.add(building);
        }
    }

    addBuildingDetails(building, width, height, depth) {
        // Ajouter des fenêtres
        const windowMaterial = new THREE.MeshLambertMaterial({ color: 0x87ceeb });
        const windowGeometry = new THREE.PlaneGeometry(1, 1.5);

        for (let i = 0; i < 3; i++) {
            for (let j = 0; j < 3; j++) {
                const window = new THREE.Mesh(windowGeometry, windowMaterial);
                window.position.set(
                    (i - 1) * (width / 3),
                    (j * height / 3) + 2,
                    depth / 2 + 0.1
                );
                building.add(window);
            }
        }
    }

    createEnvironment() {
        this.createTrees();
        this.createLamps();
        this.createProps();
    }

    createTrees() {
        const treeCount = 30;

        for (let i = 0; i < treeCount; i++) {
            const treeGroup = new THREE.Group();

            // Tronc
            const trunkGeometry = new THREE.CylinderGeometry(0.3, 0.4, 3, 8);
            const trunkMaterial = new THREE.MeshLambertMaterial({ color: 0x8B4513 });
            const trunk = new THREE.Mesh(trunkGeometry, trunkMaterial);
            trunk.position.y = 1.5;
            trunk.castShadow = true;
            treeGroup.add(trunk);

            // Feuillage
            const foliageGeometry = new THREE.SphereGeometry(2, 8, 6);
            const foliageMaterial = new THREE.MeshLambertMaterial({ color: 0x228B22 });
            const foliage = new THREE.Mesh(foliageGeometry, foliageMaterial);
            foliage.position.y = 4;
            foliage.castShadow = true;
            treeGroup.add(foliage);

            // Position aléatoire mais évitez les routes
            let x, z;
            do {
                x = (Math.random() - 0.5) * 180;
                z = (Math.random() - 0.5) * 180;
            } while (Math.abs(x) < 15 || Math.abs(z) < 15);

            treeGroup.position.set(x, 0, z);
            treeGroup.castShadow = true;
            this.scene.add(treeGroup);
        }
    }

    createLamps() {
        const lampPositions = [
            [-8, 0, -8], [8, 0, -8], [-8, 0, 8], [8, 0, 8],
            [-20, 0, -20], [20, 0, -20], [-20, 0, 20], [20, 0, 20]
        ];

        lampPositions.forEach(pos => {
            const lampGroup = new THREE.Group();

            // Poteau
            const poleGeometry = new THREE.CylinderGeometry(0.1, 0.1, 8, 8);
            const poleMaterial = new THREE.MeshLambertMaterial({ color: 0x333333 });
            const pole = new THREE.Mesh(poleGeometry, poleMaterial);
            pole.position.y = 4;
            pole.castShadow = true;
            lampGroup.add(pole);

            // Lumière
            const light = new THREE.PointLight(0xffffcc, 1, 15);
            light.position.set(0, 8, 0);
            light.castShadow = true;
            lampGroup.add(light);

            // Abat-jour
            const shadeGeometry = new THREE.ConeGeometry(0.8, 1, 8);
            const shadeMaterial = new THREE.MeshLambertMaterial({ color: 0x888888 });
            const shade = new THREE.Mesh(shadeGeometry, shadeMaterial);
            shade.position.y = 8.5;
            shade.rotation.x = Math.PI;
            lampGroup.add(shade);

            lampGroup.position.set(pos[0], 0, pos[1]);
            this.scene.add(lampGroup);
        });
    }

    createProps() {
        // Bancs
        this.createBench(15, 0, 10);
        this.createBench(-15, 0, -10);

        // Poubelles
        this.createTrashCan(12, 0, 8);
        this.createTrashCan(-12, 0, -8);
    }

    createBench(x, y, z) {
        const benchGroup = new THREE.Group();

        // Assise
        const seatGeometry = new THREE.BoxGeometry(3, 0.2, 1);
        const seatMaterial = new THREE.MeshLambertMaterial({ color: 0x8B4513 });
        const seat = new THREE.Mesh(seatGeometry, seatMaterial);
        seat.position.y = 0.5;
        benchGroup.add(seat);

        // Pieds
        const legGeometry = new THREE.BoxGeometry(0.1, 1, 0.1);
        const legPositions = [
            [-1.4, 0, -0.4], [-1.4, 0, 0.4],
            [1.4, 0, -0.4], [1.4, 0, 0.4]
        ];

        legPositions.forEach(pos => {
            const leg = new THREE.Mesh(legGeometry, seatMaterial);
            leg.position.set(pos[0], 0.5, pos[1]);
            benchGroup.add(leg);
        });

        benchGroup.position.set(x, y, z);
        benchGroup.castShadow = true;
        this.scene.add(benchGroup);
    }

    createTrashCan(x, y, z) {
        const trashGroup = new THREE.Group();

        // Corps
        const bodyGeometry = new THREE.CylinderGeometry(0.5, 0.4, 1, 8);
        const bodyMaterial = new THREE.MeshLambertMaterial({ color: 0x333333 });
        const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
        body.position.y = 0.5;
        trashGroup.add(body);

        trashGroup.position.set(x, y, z);
        trashGroup.castShadow = true;
        this.scene.add(trashGroup);
    }

    loadAssets() {
        this.loadPlayerModel();
        this.loadCarModel();
    }

    assetLoaded() {
        this.assetsLoaded++;
        if (this.assetsLoaded >= this.totalAssets) {
            document.getElementById('loading').style.display = 'none';
            console.log('Tous les assets sont chargés !');
        }
    }

    async loadPlayerModel() {
        try {
            const loader = new THREE.GLTFLoader();
            const gltf = await new Promise((resolve, reject) => {
                loader.load(
                    'https://models.readyplayer.me/69177cac28f4be8b0cc83e05.glb',
                    resolve,
                    (progress) => {
                        const percent = (progress.loaded / progress.total * 100).toFixed(2);
                        document.getElementById('loading').textContent = `Chargement du personnage... ${percent}%`;
                    },
                    reject
                );
            });

            this.player = gltf.scene;
            this.player.rotation.y = Math.PI;
            this.player.scale.set(0.5, 0.5, 0.5);
            this.player.position.set(8, 8, 8);

            this.player.traverse((child) => {
                if (child.isMesh) {
                    child.castShadow = true;
                    child.receiveShadow = true;

                    // Améliorer les matériaux
                    if (child.material) {
                        child.material.roughness = 0.8;
                        child.material.metalness = 0.2;
                    }
                }
            });

            this.scene.add(this.player);
            this.setupPlayerAnimations(gltf);
            console.log('Personnage ReadyPlayerMe chargé avec succès');
            this.assetLoaded();

        } catch (error) {
            console.error('Erreur chargement personnage:', error);
            this.createFallbackPlayer();
            this.assetLoaded();
        }
    }

    setupPlayerAnimations(gltf) {
        this.mixer = new THREE.AnimationMixer(this.player);
        this.animations = {};

        if (gltf.animations && gltf.animations.length > 0) {
            console.log('Animations disponibles:', gltf.animations.map(anim => anim.name));

            gltf.animations.forEach((clip) => {
                const action = this.mixer.clipAction(clip);
                action.setEffectiveTimeScale(1.0);
                this.animations[clip.name] = action;
            });

            this.setAnimationState(this.animationStates.IDLE);

        } else {
            console.warn('Aucune animation trouvée dans le modèle RPM');
            // Créer des animations basiques
            this.createBasicAnimations();
        }
    }

    createBasicAnimations() {
        // Animation basique pour le fallback
        const idleClip = new THREE.AnimationClip('idle', 1, []);
        const walkClip = new THREE.AnimationClip('walk', 0.5, []);
        const runClip = new THREE.AnimationClip('run', 0.3, []);

        this.animations.idle = this.mixer.clipAction(idleClip);
        this.animations.walk = this.mixer.clipAction(walkClip);
        this.animations.run = this.mixer.clipAction(runClip);

        this.setAnimationState(this.animationStates.IDLE);
    }

    setAnimationState(newState) {
        if (this.currentAnimationState === newState) return;

        const previousState = this.currentAnimationState;
        this.currentAnimationState = newState;

        console.log(`Changement d'animation: ${previousState} -> ${newState}`);

        let animationToPlay = this.findAnimationForState(newState);
        let animationToStop = this.findAnimationForState(previousState);

        if (animationToStop && animationToStop !== animationToPlay) {
            animationToStop.fadeOut(0.2);
        }

        if (animationToPlay) {
            animationToPlay
                .reset()
                .setEffectiveTimeScale(1.0)
                .fadeIn(0.2)
                .play();
        }

        this.currentAnimation = animationToPlay;
    }

    findAnimationForState(state) {
        const stateToAnimations = {
            [this.animationStates.IDLE]: ['idle', 'Idle', 'IDLE', 'Standing', 'standing'],
            [this.animationStates.WALK]: ['walk', 'Walk', 'WALK', 'Walking', 'walking'],
            [this.animationStates.RUN]: ['run', 'Run', 'RUN', 'Running', 'running', 'Jog', 'jog'],
            [this.animationStates.JUMP]: ['jump', 'Jump', 'JUMP', 'Jumping', 'jumping'],
            [this.animationStates.SIT]: ['sit', 'Sit', 'SIT', 'Sitting', 'sitting'],
            [this.animationStates.FALL]: ['fall', 'Fall', 'FALL', 'Falling', 'falling']
        };

        const possibleNames = stateToAnimations[state] || [state];

        for (const animName of possibleNames) {
            if (this.animations[animName]) {
                return this.animations[animName];
            }
        }

        // Fallback vers des animations similaires
        const allAnimations = Object.keys(this.animations);
        if (allAnimations.length > 0) {
            if (state === this.animationStates.IDLE) {
                return this.animations[allAnimations[0]];
            }
            if (state === this.animationStates.WALK || state === this.animationStates.RUN) {
                const movementAnims = allAnimations.filter(name =>
                    name.toLowerCase().includes('walk') ||
                    name.toLowerCase().includes('run') ||
                    name.toLowerCase().includes('jog') ||
                    name.toLowerCase().includes('move')
                );
                if (movementAnims.length > 0) {
                    return this.animations[movementAnims[0]];
                }
                return this.animations[allAnimations[Math.min(1, allAnimations.length - 1)]];
            }
        }

        return null;
    }

    async loadCarModel() {
        try {
            const loader = new THREE.GLTFLoader();
            const gltf = await new Promise((resolve, reject) => {
                loader.load(
                    '../img/classic_muscle_car.glb',
                    resolve,
                    (progress) => {
                        const percent = (progress.loaded / progress.total * 100).toFixed(2);
                        document.getElementById('loading').textContent = `Chargement de la voiture... ${percent}%`;
                    },
                    reject
                );
            });

            this.car = gltf.scene;
            this.car.scale.set(0.3, 0.3, 0.3);
            this.car.position.set(8, 0.5, 8);
            this.car.rotation.y = Math.PI / 4;

            this.car.traverse((child) => {
                if (child.isMesh) {
                    child.castShadow = true;
                    child.receiveShadow = true;

                    // Améliorer les matériaux de la voiture
                    if (child.material) {
                        child.material.roughness = 0.4;
                        child.material.metalness = 0.4;
                    }
                }
            });

            this.scene.add(this.car);
            console.log('Voiture chargée avec succès');
            this.assetLoaded();

        } catch (error) {
            console.error('Erreur chargement voiture:', error);
            this.createFallbackCar();
            this.assetLoaded();
        }
    }

    createFallbackPlayer() {
        const group = new THREE.Group();

        const bodyGeometry = new THREE.BoxGeometry(0.6, 1.8, 0.3);
        const bodyMaterial = new THREE.MeshLambertMaterial({
            color: 0x00ff00,
            roughness: 0.8,
            metalness: 0.2
        });
        const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
        body.position.y = 0.9;
        body.castShadow = true;
        group.add(body);

        const headGeometry = new THREE.SphereGeometry(0.2, 16, 16);
        const headMaterial = new THREE.MeshLambertMaterial({ color: 0xffaa00 });
        const head = new THREE.Mesh(headGeometry, headMaterial);
        head.position.y = 1.7;
        head.castShadow = true;
        group.add(head);

        this.player = group;
        this.player.position.set(0, 0, 0);
        this.scene.add(this.player);
    }

    createFallbackCar() {
        const group = new THREE.Group();

        const carBodyGeometry = new THREE.BoxGeometry(2, 0.8, 4);
        const carBodyMaterial = new THREE.MeshLambertMaterial({
            color: 0xff0000,
            roughness: 0.4,
            metalness: 0.8
        });
        const carBody = new THREE.Mesh(carBodyGeometry, carBodyMaterial);
        carBody.position.y = 0.4;
        carBody.castShadow = true;
        group.add(carBody);

        const roofGeometry = new THREE.BoxGeometry(1.5, 0.6, 1.5);
        const roofMaterial = new THREE.MeshLambertMaterial({ color: 0xcc0000 });
        const roof = new THREE.Mesh(roofGeometry, roofMaterial);
        roof.position.y = 1.1;
        roof.position.z = -0.5;
        roof.castShadow = true;
        group.add(roof);

        const wheelGeometry = new THREE.CylinderGeometry(0.3, 0.3, 0.2, 16);
        wheelGeometry.rotateZ(Math.PI / 2);
        const wheelMaterial = new THREE.MeshLambertMaterial({ color: 0x333333 });

        const positions = [
            [1, 0.3, 1.2], [1, 0.3, -1.2],
            [-1, 0.3, 1.2], [-1, 0.3, -1.2]
        ];

        positions.forEach((pos) => {
            const wheel = new THREE.Mesh(wheelGeometry, wheelMaterial);
            wheel.position.set(pos[0], pos[1], pos[2]);
            wheel.castShadow = true;
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

            if (event.code === 'ShiftLeft') {
                this.isRunning = true;
            }
        });

        document.addEventListener('keyup', (event) => {
            this.keys[event.code] = false;

            if (event.code === 'ShiftLeft') {
                this.isRunning = false;
            }
        });

        window.addEventListener('resize', () => {
            this.camera.aspect = window.innerWidth / window.innerHeight;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(window.innerWidth, window.innerHeight);
        });
    }

    toggleCar() {
        if (!this.car || !this.player) return;

        if (this.isInCar) {
            // Sortir de la voiture
            this.exitCar();
        } else {
            // Essayer de rentrer dans la voiture
            this.tryEnterCar();
        }
    }

    tryEnterCar() {
        if (!this.car || !this.player) return;

        const distance = this.player.position.distanceTo(this.car.position);

        if (distance < 5) {
            // Le joueur est assez proche de la voiture
            this.enterCar();
        } else {
            // Téléporter la voiture près du joueur
            this.teleportCarToPlayer();
            this.enterCar();
        }

        document.getElementById('status').textContent =
            `Mode: ${this.isInCar ? 'Voiture' : 'À pied'}`;
    }

    teleportCarToPlayer() {
        if (!this.car || !this.player) return;

        // Calculer une position devant le joueur
        const direction = new THREE.Vector3();
        this.camera.getWorldDirection(direction);
        direction.y = 0; // Garder la voiture au sol
        direction.normalize();

        // Positionner la voiture 3 unités devant le joueur
        const carPosition = this.player.position.clone();
        carPosition.add(direction.multiplyScalar(3));
        carPosition.y = 0; // S'assurer que la voiture est au sol

        // Orienter la voiture dans la même direction que le joueur
        this.car.position.copy(carPosition);
        this.car.rotation.y = this.player.rotation.y;

        console.log('Voiture téléportée près du joueur');
    }

    enterCar() {
        if (this.isInCar) return;

        this.isInCar = true;
        this.player.visible = false;

        if (this.controls && this.controls.isLocked) {
            this.controls.unlock();
        }

        this.setAnimationState(this.animationStates.SIT);

        // Positionner la caméra dans la voiture
        this.camera.position.copy(this.car.position);
        this.camera.position.y += 2;
        this.camera.rotation.copy(this.car.rotation);

        console.log('Entrée dans la voiture');
    }

    exitCar() {
        if (!this.isInCar) return;

        this.isInCar = false;
        this.player.visible = true;

        // Positionner le joueur à côté de la voiture
        const direction = new THREE.Vector3();
        this.car.getWorldDirection(direction);
        direction.multiplyScalar(-3); // 3 unités derrière la voiture

        this.player.position.copy(this.car.position).add(direction);
        this.player.position.y = 0;
        this.player.rotation.y = this.car.rotation.y + Math.PI;

        // Positionner la caméra sur le joueur
        this.camera.position.copy(this.player.position);
        this.camera.position.y += 1.6;
        this.camera.rotation.set(0, this.player.rotation.y - Math.PI, 0);

        this.setAnimationState(this.animationStates.IDLE);

        console.log('Sortie de la voiture');
    }

    jump() {
        if (this.canJump && this.playerOnGround) {
            this.setAnimationState(this.animationStates.JUMP);
            this.playerVelocity.y = this.jumpForce;
            this.canJump = false;
            this.playerOnGround = false;

            setTimeout(() => {
                this.canJump = true;
            }, 1000);
        }
    }

    updatePlayerPhysics(delta) {
        if (!this.player || this.isInCar) return;

        // Appliquer la gravité
        this.playerVelocity.y += this.gravity * delta;

        // Vérifier la collision avec le sol
        if (this.player.position.y <= 0) {
            this.player.position.y = 0;
            this.playerVelocity.y = 0;
            this.playerOnGround = true;

            if (this.currentAnimationState === this.animationStates.JUMP) {
                this.setAnimationState(this.animationStates.IDLE);
            }
        } else {
            this.playerOnGround = false;
            if (this.playerVelocity.y < 0 && this.currentAnimationState !== this.animationStates.JUMP) {
                this.setAnimationState(this.animationStates.FALL);
            }
        }

        // Appliquer la vélocité
        this.player.position.y += this.playerVelocity.y * delta;
    }

    updatePlayer(delta) {
        if (!this.player || this.isInCar) return;

        this.updatePlayerPhysics(delta);

        const baseSpeed = this.isRunning ? 8 : 4;
        const speed = this.playerOnGround ? baseSpeed : baseSpeed * 0.5; // Réduire la vitesse en l'air

        const isMoving = this.keys['KeyW'] || this.keys['KeyS'] || this.keys['KeyA'] || this.keys['KeyD'] ||
            this.moveForward || this.moveBackward || this.moveLeft || this.moveRight;

        let newAnimationState = this.animationStates.IDLE;

        if (isMoving && this.playerOnGround) {
            if (this.isRunning) {
                newAnimationState = this.animationStates.RUN;
            } else {
                newAnimationState = this.animationStates.WALK;
            }
        }

        if (this.keys['Space'] && this.canJump && this.playerOnGround) {
            this.jump();
        }

        if (newAnimationState !== this.currentAnimationState && this.playerOnGround) {
            this.setAnimationState(newAnimationState);
        }

        this.updateAnimationSpeed(isMoving, this.isRunning);

        if (this.controls && this.controls.isLocked) {
            this.velocity.x = 0;
            this.velocity.z = 0;

            if (this.keys['KeyW']) this.velocity.z = -speed;
            if (this.keys['KeyS']) this.velocity.z = speed;
            if (this.keys['KeyA']) this.velocity.x = -speed;
            if (this.keys['KeyD']) this.velocity.x = speed;

            this.controls.moveForward(-this.velocity.z * delta);
            this.controls.moveRight(this.velocity.x * delta);

            this.player.position.copy(this.camera.position);
            this.player.position.y = Math.max(0, this.player.position.y);
            this.player.rotation.y = this.camera.rotation.y + Math.PI;

        } else {
            const actualSpeed = speed * delta;

            if (this.moveForward) {
                this.camera.translateZ(-actualSpeed);
                this.updatePlayerPosition();
            }
            if (this.moveBackward) {
                this.camera.translateZ(actualSpeed);
                this.updatePlayerPosition();
            }
            if (this.moveLeft) {
                this.camera.translateX(-actualSpeed);
                this.updatePlayerPosition();
            }
            if (this.moveRight) {
                this.camera.translateX(actualSpeed);
                this.updatePlayerPosition();
            }
        }
    }

    updatePlayerPosition() {
        this.player.position.copy(this.camera.position);
        this.player.position.y = Math.max(0, this.player.position.y);
        this.player.rotation.y = this.camera.rotation.y + Math.PI;
    }

    updateAnimationSpeed(isMoving, isRunning) {
        if (!this.currentAnimation) return;

        let speed = 1.0;

        if (isMoving) {
            if (isRunning) {
                speed = 1.5;
            } else {
                speed = 1.0;
            }
        }

        this.currentAnimation.setEffectiveTimeScale(speed);
    }

    updateCar(delta) {
        if (!this.car || !this.isInCar) return;

        const carSpeed = this.keys['ShiftLeft'] ? 25 : 15;
        const rotationSpeed = 2.5;

        // Physique de voiture basique
        if (this.keys['KeyW'] || this.moveForward) {
            this.car.translateZ(-carSpeed * delta);
        }
        if (this.keys['KeyS'] || this.moveBackward) {
            this.car.translateZ(carSpeed * 0.7 * delta); // Plus lent en marche arrière
        }

        // Rotation plus réaliste
        if (this.keys['KeyA'] || this.moveLeft) {
            this.car.rotation.y += rotationSpeed * delta * (this.keys['KeyW'] ? 1 : 0.7);
        }
        if (this.keys['KeyD'] || this.moveRight) {
            this.car.rotation.y -= rotationSpeed * delta * (this.keys['KeyW'] ? 1 : 0.7);
        }

        // Caméra qui suit la voiture
        const carDirection = new THREE.Vector3();
        this.car.getWorldDirection(carDirection);

        const cameraDistance = 8;
        const cameraHeight = 5;

        const cameraOffset = carDirection.clone().multiplyScalar(-cameraDistance);
        cameraOffset.y = cameraHeight;

        const targetCameraPos = this.car.position.clone().add(cameraOffset);
        this.camera.position.lerp(targetCameraPos, 0.1);
        this.camera.lookAt(this.car.position);
    }

    animate() {
        requestAnimationFrame(() => this.animate());

        const time = performance.now();
        const delta = Math.min((time - this.prevTime) / 1000, 0.1);
        this.prevTime = time;

        if (this.mixer) {
            this.mixer.update(delta);
        }

        if (this.isInCar) {
            this.updateCar(delta);
        } else {
            this.updatePlayer(delta);
        }

        this.renderer.render(this.scene, this.camera);
    }
}