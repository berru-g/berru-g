// Mobile Navigation Toggle
document.addEventListener('DOMContentLoaded', function () {
    const mobileToggle = document.getElementById('mobileToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const mobileMenuClose = document.getElementById('mobileMenuClose');
    const mobileMenuLinks = document.querySelectorAll('.mobile-menu-link');
    const navLinks = document.querySelectorAll('.nav-link');

    // Function to open mobile menu
    function openMobileMenu() {
        console.log('Opening mobile menu');
        mobileToggle.classList.add('active');
        mobileMenu.classList.add('active');
        mobileMenuOverlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent body scroll
    }

    // Function to close mobile menu
    function closeMobileMenu() {
        console.log('Closing mobile menu');
        mobileToggle.classList.remove('active');
        mobileMenu.classList.remove('active');
        mobileMenuOverlay.classList.remove('active');
        document.body.style.overflow = ''; // Restore body scroll
    }

    // Toggle mobile menu when hamburger is clicked
    mobileToggle.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (mobileMenu.classList.contains('active')) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    });

    // Close mobile menu when close button is clicked
    mobileMenuClose.addEventListener('click', function (e) {
        e.preventDefault();
        closeMobileMenu();
    });

    // Close mobile menu when overlay is clicked
    mobileMenuOverlay.addEventListener('click', function () {
        closeMobileMenu();
    });

    // Close mobile menu when clicking on mobile menu links
    mobileMenuLinks.forEach(link => {
        link.addEventListener('click', function () {
            closeMobileMenu();

            // Remove active class from all mobile links
            mobileMenuLinks.forEach(l => l.classList.remove('active'));
            // Add active class to clicked link
            this.classList.add('active');

            // Also update desktop nav active state
            const href = this.getAttribute('href');
            navLinks.forEach(navLink => {
                navLink.classList.remove('active');
                if (navLink.getAttribute('href') === href) {
                    navLink.classList.add('active');
                }
            });
        });
    });

    // Close mobile menu when clicking on desktop nav links
    navLinks.forEach(link => {
        link.addEventListener('click', function () {
            closeMobileMenu();

            // Remove active class from all links
            navLinks.forEach(l => l.classList.remove('active'));
            // Add active class to clicked link (except CTA button)
            if (!this.classList.contains('cta-button')) {
                this.classList.add('active');

                // Also update mobile nav active state
                const href = this.getAttribute('href');
                mobileMenuLinks.forEach(mobileLink => {
                    mobileLink.classList.remove('active');
                    if (mobileLink.getAttribute('href') === href) {
                        mobileLink.classList.add('active');
                    }
                });
            }
        });
    });

    // Close mobile menu on escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
            closeMobileMenu();
        }
    });

    // Navbar scroll effect - Remove auto-hide, keep it sticky
    window.addEventListener('scroll', function () {
        const navbar = document.querySelector('.navbar-container');
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        // Add/remove scroll class for styling changes if needed
        if (scrollTop > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Add hover effect to floating circles
    const floatingCircles = document.querySelectorAll('.floating-circle');
    floatingCircles.forEach(circle => {
        circle.addEventListener('mouseenter', function () {
            this.style.transform = 'scale(1.2)';
        });

        circle.addEventListener('mouseleave', function () {
            this.style.transform = 'scale(1)';
        });
    });

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Handle window resize
    window.addEventListener('resize', function () {
        if (window.innerWidth > 992 && mobileMenu.classList.contains('active')) {
            closeMobileMenu();
        }
    });
});

// réduction de 10%
/*
let visits = parseInt(localStorage.getItem('visits') || '0') + 1;
localStorage.setItem('visits', visits);

// Affiche la popup à chaque visite
window.addEventListener("DOMContentLoaded", () => {
    if (visits % 12 === 0) {
        Swal.fire({
            icon: 'success',
            title: `🎉 Bravo ! Vous êtes le ${visits}ᵉ visiteur.`,
            text: "Vous avez gagné -10% sur votre prochaine commande !",
            footer: "<b>Code à utiliser :</b> <code>I-AM-THE-BEST</code>",
            confirmButtonText: "Super !",
            timer: 8000,
            timerProgressBar: true
        });
    } else {
        Swal.fire({
            icon: 'info',
            title: `👀 Vous êtes le ${visits}ᵉ visiteur aujourd'hui`,
            text: "Pas de réduction cette fois-ci... Revenez plus tard 😉",
            confirmButtonText: "OK",
            timer: 5000,
            timerProgressBar: true
        });
    }
});
*/
// Mini quizz
const quiz = [
    {
        question: "Quel est le principal objectif d’un site vitrine ?",
        options: ["Informer", "Divertir", "Convertir", "Faire joli"],
        answer: "Convertir"
    },
    {
        question: "Un site lent peut-il nuire à vos ventes ?",
        options: ["Non", "Oui", "Uniquement sur mobile", "Seulement si trop lent"],
        answer: "Oui"
    },
    {
        question: "Que signifie 'responsive' ?",
        options: ["Réactif", "Rapide", "Adapté à tous les écrans", "Avec animations"],
        answer: "Adapté à tous les écrans"
    }
];

async function launchQuiz() {
    let score = 0;

    for (let i = 0; i < quiz.length; i++) {
        const q = quiz[i];
        const { value: userAnswer } = await Swal.fire({
            title: `Question ${i + 1}`,
            text: q.question,
            input: "select",
            inputOptions: q.options.reduce((acc, opt) => {
                acc[opt] = opt;
                return acc;
            }, {}),
            inputPlaceholder: "Choisissez une réponse",
            showCancelButton: false
        });

        if (userAnswer === q.answer) {
            score++;
        }
    }

    if (score === quiz.length) {
        Swal.fire({
            icon: 'success',
            title: '🧠 Bravo !',
            text: "Toutes les réponses sont correctes. Vous gagnez -10% avec le code I-KNOW-MY-WEB.",
            confirmButtonText: 'Yes !'
        });
    } else {
        Swal.fire({
            icon: 'info',
            title: 'Presque !',
            text: `Vous avez ${score}/${quiz.length} bonnes réponses. Réessayez pour débloquer la réduction.`,
            confirmButtonText: 'OK'
        });
    }
}

document.getElementById("launch-quiz").addEventListener("click", launchQuiz);

// FAQ
document.querySelectorAll('.faq-question').forEach(button => {
    button.addEventListener('click', () => {
        const answer = button.nextElementSibling;

        // Ferme toutes les autres réponses
        document.querySelectorAll('.faq-answer').forEach(a => {
            if (a !== answer) {
                a.style.maxHeight = null;
                a.style.opacity = 0;
            }
        });

        // Toggle la réponse cliquée
        if (answer.style.maxHeight) {
            answer.style.maxHeight = null;
            answer.style.opacity = 0;
        } else {
            answer.style.maxHeight = answer.scrollHeight + "px";
            answer.style.opacity = 1;
        }
    });
});

// tips toast
const tips = [
    "💡 75% des internautes jugent une entreprise à son site.",
    "📱 Un site responsive est indispensable en 2025.",
    "🚀 Un site rapide augmente vos chances de conversion de 2x.",
    "🎯 Un appel à l’action clair booste vos demandes de devis.",
    "👁️‍🗨️ Less is more : un design épuré est souvent plus impactant.",
    "🔒 La sécurité (https) inspire confiance à vos visiteurs.",
    "📊 53% des visiteurs quittent un site qui met plus de 3 secondes à charger. (Google, 2023)",
    "🛒 78% des clients vérifient un site avant d’acheter, même en magasin physique. (RetailDive)",
    "📱 En 2026, 75% du trafic web viendra des mobiles. (Statista)",
    "🎨 94% des premières impressions sont liées au design d’un site. (ResearchGate)",
    "🔍 75% des utilisateurs ne cliquent que sur les 5 premiers résultats Google. (Advanced Web Ranking)",
    "🥚 Ce site contient 8 Easter Egg ( curiosité à découvrir )",
    "🔒 85% des acheteurs en ligne évitent les sites non sécurisés (sans HTTPS). (GlobalSign)"
];

function showToast(message) {
    const toast = document.createElement("div");
    toast.className = "custom-toast";
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 6000);
}

document.getElementById("show-tip").addEventListener("click", (e) => {
    e.preventDefault();
    console.log("Bouton cliqué !");
    const randomTip = tips[Math.floor(Math.random() * tips.length)];
    showToast(randomTip);
});

// trhee.js 3D model
// Variables globales
let scene, camera, renderer, model, mixer;
let controls, clock;
let initialScale = 7; // Stocker l'échelle initiale

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

    // Utilisation d'un modèle 3D glb ou gltf plus léger
    //const modelUrl = 'https://raw.githubusercontent.com/berru-g/3d-scroll-animate/main/assets/scene.gltf';
    const modelUrl = 'https://raw.githubusercontent.com/berru-g/berru-g/refs/heads/main/img/space_boi.glb';
    //const modelUrl = '../img/stylized_mangrove_greenhouse.glb'; // modèle local

    loader.load(
        modelUrl,
        function (gltf) {
            model = gltf.scene;
            scene.add(model);

            // Ajuster l'échelle et la position si nécessaire
            initialScale = 30; // Définir l'échelle initiale
            model.scale.set(initialScale, initialScale, initialScale);
            model.position.set(60, -20, 60); // Ajusté pour mieux centrer

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
    const geometry = new THREE.BoxGeometry(3, 3, 3);
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
/* 
// mouvement 360*360 + zoom + changement couleur + intensité lumière
function onScroll() {
    if (!model) return;

    // Calculer la progression du scroll (0 à 1)
    const scrollY = window.scrollY;
    const totalHeight = document.body.scrollHeight - window.innerHeight;
    const scrollPercentage = Math.min(scrollY / totalHeight, 1);

    // Appliquer des transformations basées sur le scroll
    model.rotation.x = scrollPercentage * Math.PI * 1;
    model.rotation.y = scrollPercentage * Math.PI * 2;
    model.rotation.z = scrollPercentage * Math.PI;

    // Modifier l'échelle en fonction du scroll en utilisant l'échelle initiale comme base
    const scale = initialScale + scrollPercentage * 1.5;
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


// variante simple rotation + translation horizontale
function onScroll() {
    if (!model) return;

    const scrollY = window.scrollY;
    const totalHeight = document.body.scrollHeight - window.innerHeight;
    const scrollPercentage = Math.min(scrollY / totalHeight, 1);

    // Rotation
    model.rotation.y = scrollPercentage * Math.PI * 2;
    
    // Translation horizontale
    model.position.x = (scrollPercentage - 0.5) * 6;
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
*/
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
window.addEventListener('load', function() {
    // Petite temporisation pour s'assurer que tout est chargé
    setTimeout(init, 100);
});