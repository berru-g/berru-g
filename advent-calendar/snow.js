function createThreeJSSnow() {
    // 1. Container BIEN positionné
    const container = document.createElement('div');
    container.id = 'threejs-snow';
    container.style.position = 'fixed';
    container.style.top = '0';
    container.style.left = '0';
    container.style.width = '100vw';
    container.style.height = '100vh';
    container.style.pointerEvents = 'none'; // ← Les clics traversent
    container.style.zIndex = '-1'; // ← Derrière tout
    document.body.appendChild(container);
    
    // 2. Scene, Camera, Renderer
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, window.innerWidth/window.innerHeight, 0.1, 1000);
    camera.position.z = 5;
    
    const renderer = new THREE.WebGLRenderer({ 
        alpha: true,
        antialias: true,
        preserveDrawingBuffer: false // ← Optimisation
    });
    
    // 3. FOND TRANSPARANT (important)
    renderer.setClearColor(0x000000, 0); // Alpha à 0 = transparent
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.domElement.style.position = 'absolute';
    renderer.domElement.style.top = '0';
    renderer.domElement.style.left = '0';
    renderer.domElement.style.zIndex = '-1';
    
    container.appendChild(renderer.domElement);
    
    // 4. Création des flocons (version simplifiée)
    const snowflakes = [];
    const flakeGeometry = new THREE.SphereGeometry(0.05, 8, 8);
    const flakeMaterial = new THREE.MeshBasicMaterial({
        color: 0xffffff,
        transparent: true,
        opacity: 0.6
    });
    
    for(let i = 0; i < 300; i++) {
        const flake = new THREE.Mesh(flakeGeometry, flakeMaterial.clone());
        flake.position.x = Math.random() * 40 - 20;
        flake.position.y = Math.random() * 40 - 20;
        flake.position.z = Math.random() * 20 - 10;
        
        // Données pour l'animation
        flake.userData = {
            speed: Math.random() * 0.1 + 0.03,
            rotation: Math.random() * 0.02
        };
        
        scene.add(flake);
        snowflakes.push(flake);
    }
    
    // 5. Animation
    function animate() {
        requestAnimationFrame(animate);
        
        snowflakes.forEach(flake => {
            flake.position.y -= flake.userData.speed;
            flake.rotation.y += flake.userData.rotation;
            
            // Réapparition en haut
            if(flake.position.y < -15) {
                flake.position.y = 15;
                flake.position.x = Math.random() * 40 - 20;
            }
        });
        
        renderer.render(scene, camera);
    }
    
    // 6. Redimensionnement
    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
    
    animate();
    
    return {
        destroy: () => {
            window.removeEventListener('resize', onResize);
            container.remove();
        }
    };
}

// 7. Lancer avec un délai
setTimeout(() => {
    if(typeof THREE !== 'undefined') {
        window.snowEffect = createThreeJSSnow();
    }
}, 1000);