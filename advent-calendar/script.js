import { supabase } from './supabase.js'

// Configuration
const DAYS = 24;
const CURRENT_DAY = new Date().getDate();
const CURRENT_MONTH = new Date().getMonth() + 1;
const IS_DECEMBER = CURRENT_MONTH === 12;
const MAX_OPENABLE_DAY = IS_DECEMBER ? Math.min(CURRENT_DAY, DAYS) : 0;

let user = null;

// Initialisation
document.addEventListener('DOMContentLoaded', async () => {
    // Vérifier la session utilisateur
    const session = await supabase.auth.getSession();
    user = session?.data?.session?.user || null;
    
    if (user) {
        document.querySelector('.auth-section').style.display = 'none';
        updateTodayTheme();
    }
    
    // Écouter les changements d'authentification
    supabase.auth.onAuthStateChange((_event, session) => {
        user = session?.user;
        if (user) {
            document.querySelector('.auth-section').style.display = 'none';
            updateTodayTheme();
            generateCalendar();
        } else {
            document.querySelector('.auth-section').style.display = 'block';
        }
    });
    
    // Générer le calendrier
    generateCalendar();
});

// Authentification (identique au système pixel)
window.login = async () => {
    const emailInput = document.getElementById('email');
    const email = emailInput.value.trim();
    
    if (!email || !email.includes('@')) {
        Swal.fire({
            icon: 'warning',
            title: 'Email invalide',
            text: 'Veuillez entrer une adresse email valide.'
        });
        return;
    }
    
    const { error } = await supabase.auth.signInWithOtp({
        email,
        options: {
            emailRedirectTo: 'https://gael-berru.com/advent-calendar/' //window.location.origin
        }
    });
    
    if (error) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: error.message
        });
        return;
    }
    
    Swal.fire({
        icon: 'success',
        title: 'Lien envoyé !',
        html: `Un lien de connexion Supabase Auth a été envoyé à <strong>${email}</strong>.<br>Vérifiez votre boîte mail 📩`,
        confirmButtonColor: 'var(--primary-color)'
    });
    
    emailInput.value = '';
};

// Générer les cases du calendrier
function generateCalendar() {
    const grid = document.getElementById('calendar-grid');
    grid.innerHTML = '';
    
    // Thèmes des jours 
    const dayThemes = [
        "Jeu 3D OPEN SOURCE (.JS)", "Help desk terminal (.JS)", "Créer un dossier en un clic (.VBS)",
        "Génerateur de QR code .JS", "SQL editor to diagramm .SQL", "Créez une animation 3D au scroll (.NO-CODE)",
        "Créer votre prore éditeur de code (.JS)", "Bitcoin forensics toolkit .(API)", "Crypto Free Tools (.API)",
        "Heatmap crypto 3D (.API)", "Créer votre controlleur MIDI .(ARDUINO)", "Télechargez des obj 3D gratuit (.GLB)",
        "Créez votre réseau social (.PHP/SQL)", "Créez votre google analytics (.PHP/JS)", "Template (Three.js) : ANimation 3D",
        "Face Morph: Animer votre personnage 3D (.JS)", "Scrapper Reddit : chercher des mots clefs (.PYTHON)", "Vends des pixels: code source gratuit (.SUPABASE)",
        "Name color: le tool qui sert à rien (.JS)", "Phishing: Comment ça marche techniquement ? (.PHP/SQL)", "Automatise l'app WEWARD (.PYTHON)",
        "Crée ton Contrôleur MIDI code + gerber (.C++)", "Template : SAAS (.PHP/SQL)", "Surprise spéciale Noël 🎄"
    ];
    
    for (let day = 1; day <= DAYS; day++) {
        const door = document.createElement('div');
        door.className = 'advent-door';
        door.dataset.day = day;
        
        // Déterminer si la case est ouvrable
        const isOpenable = user && day <= MAX_OPENABLE_DAY;
        const isOpened = localStorage.getItem(`advent-day-${day}-opened`) === 'true';
        
        if (isOpened) {
            door.classList.add('opened');
        } else if (!isOpenable) {
            door.classList.add('locked');
        }
        
        // Contenu de la case
        door.innerHTML = `
            <div class="day-number">${day}</div>
            <div class="day-title">${dayThemes[day - 1] || `Jour ${day}`}</div>
            ${!isOpened && !isOpenable ? 
                '🎁<i class="fas fa-lock lock-icon"></i>' : 
                isOpened ? '<i class="fas fa-check-circle check-icon"></i>' : ''
            }
        `;
        
        // Gestion du clic
        door.addEventListener('click', () => handleDoorClick(day, isOpenable, isOpened, dayThemes[day - 1]));
        
        grid.appendChild(door);
    }
}

// Gérer le clic sur une case
function handleDoorClick(day, isOpenable, isOpened, theme) {
    if (!user) {
        Swal.fire({
            icon: 'warning',
            title: 'Connexion requise',
            text: 'Inscrivez-vous pour ouvrir les cases !',
            confirmButtonColor: 'var(--primary-color)'
        });
        return;
    }
    
    if (!isOpenable && !isOpened) {
        Swal.fire({
            icon: 'info',
            title: 'Patience !',
            html: `Cette case s'ouvrira le <strong>${day} décembre</strong>. Revenez ce jour-là !`,
            confirmButtonColor: 'var(--primary-color)'
        });
        return;
    }
    
    if (isOpened) {
        showSurprise(day, theme, true);
        return;
    }
    
    // Ouvrir la case
    Swal.fire({
        title: `Jour ${day} - ${theme}`,
        html: `
            <div class="surprise-modal-content">
                <i class="fas fa-gift"></i>
                <h3>🎁 Surprise du jour !</h3>
                <p>Vous avez débloqué : <strong>${theme}</strong></p>
                <p>Cliquez sur le lien ci-dessous pour accéder à la ressource :</p>
                <a href="#" class="surprise-link" id="surprise-link">
                    <i class="fas fa-external-link-alt"></i> Accéder à la surprise
                </a>
            </div>
        `,
        showCloseButton: true,
        confirmButtonText: 'Fermer',
        confirmButtonColor: 'var(--primary-color)',
        didOpen: () => {
            // Simuler un lien (à remplacer par vos vraies URLs)
            const link = document.getElementById('surprise-link');
            link.href = getSurpriseLink(day);
            link.target = '_blank';
            
            // Marquer comme ouvert
            localStorage.setItem(`advent-day-${day}-opened`, 'true');
            
            // Mettre à jour l'affichage
            const door = document.querySelector(`.advent-door[data-day="${day}"]`);
            if (door) {
                door.classList.add('opened');
                door.classList.remove('locked');
            }
            
            // Enregistrer l'ouverture dans Supabase (optionnel)
            recordDoorOpening(day);
        }
    });
}

// Générer un lien de surprise (exemple)
function getSurpriseLink(day) {
    // À remplacer par vos propres URLs
    const links = [
        "https://gael-berru.com/3D/", //1 Jeu 3D OPEN SOURCE
        "https://berru-g.github.io/console-interactive/", //2 Help desk terminal
        "https://github.com/berru-g/OTTO/blob/main/front-end-files-auto/Create-front-folders.vbs", //3 Créer dossier front-end via un simple double clic.
        "https://berru-g.github.io/generate-qrcode/",//4 Génerateur de QR code
        "https://agora-dataviz.com", //5 SQL editor to diagramm
        "https://3dscrollanimator.com", //6 Créez une animation 3D au scroll 
        "https://gael-berru.com/codepen/", //7 Créer votre prore éditeur de code 
        "https://crypto-free-tools.netlify.app/scam-radar/", //8 Bitcoin forensics toolkit
        "https://crypto-free-tools.netlify.app", //9 Crypto Free Tools
        "https://crypto-free-tools.netlify.app/heatmap-forest/", //10 Heatmap crypto 3D
        "https://github.com/berru-g/Microcontroller-USB-midi", //11 Créer votre controlleur MIDI
        "https://sketchfab.com/", //12 Sketchfab 
        "https://github.com/berru-g/projet-messagerie/blob/main/pages/home.php", //13 Créez votre réseau social en PHP/SQL
        "https://github.com/berru-g/cookie-tracking/tree/main", //14 Créez votre google analytics
        "https://codepen.io/h-lautre/pen/LENyZKb", //15 Template Threejs
        "https://codepen.io/h-lautre/pen/EaKKrpN", //16 Face Morph
        "https://github.com/berru-g/OTTO/blob/main/scrap/PainScraper/scrap-mot-clef.py", //17 Scrapper Reddit
        "https://github.com/berru-g/pixelearth", //18 Vendre des pixels
        "https://berru-g.github.io/name-generate-color/", //19 Name color
        "https://github.com/berru-g/prevention_phishing", //20 Apprends le phishing
        "https://github.com/berru-g/weward-auto", //21 Automatise l'app WEWARD
        "https://github.com/berru-g/MAKE_PLAY-1", //22 PCB make&play
        "https://github.com/berru-g/3Dscrollanimator", //23 Template SAAS
        "https://github.com/berru-g/OTTO/chatSMB/" //24 Surprise spéciale Noël
        
    ];
    return links[day - 1] || "https://gael-berru.com/advent-calendar/";
}

// Enregistrer l'ouverture dans Supabase
async function recordDoorOpening(day) {
    if (!user) return;
    
    const { error } = await supabase
        .from('advent_opens')
        .insert({
            user_id: user.id,
            user_email: user.email,
            day: day,
            opened_at: new Date().toISOString()
        });
    
    if (error) {
        console.error('Erreur enregistrement:', error);
    }
}

// Mettre à jour le thème du jour
function updateTodayTheme() {
    if (!IS_DECEMBER || CURRENT_DAY > DAYS) return;
    
    const themes = [
        "Python", "CSS 3D", "Arduino", "Modélisation 3D", "VS Code", 
        "PCB Design", "JavaScript", "UI/UX", "Optimisation", "Raspberry Pi",
        "CSS Avancé", "QR Codes", "Fusion 360", "Blender", "Node.js",
        "React", "ESP32", "Shaders", "CLI Tools", "Three.js",
        "Automatisation", "MIDI Controllers", "E-commerce", "Spécial Noël"
    ];
    
    const todayElement = document.getElementById('today-theme');
    if (todayElement && CURRENT_DAY <= themes.length) {
        todayElement.textContent = themes[CURRENT_DAY - 1];
    }
}

// Vérifier si on revient d'une authentification
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('access_token')) {
    // L'utilisateur vient de se connecter via le lien magique
    setTimeout(() => {
        Swal.fire({
            icon: 'success',
            title: 'Bienvenue !',
            text: 'Vous êtes maintenant connecté. Ouvrez vos cases !',
            confirmButtonColor: 'var(--primary-color)'
        });
    }, 1000);
}

// SNOW effect
