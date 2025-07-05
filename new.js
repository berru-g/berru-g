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
    "📱 En 2025, 75% du trafic web viendra des mobiles. (Statista)",
    "🎨 94% des premières impressions sont liées au design d’un site. (ResearchGate)",
    "🔍 75% des utilisateurs ne cliquent que sur les 5 premiers résultats Google. (Advanced Web Ranking)",
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
