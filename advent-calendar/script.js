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
            emailRedirectTo: window.location.origin
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
        html: `Un lien de connexion magique a été envoyé à <strong>${email}</strong>.<br>Vérifiez votre boîte mail 📩`,
        confirmButtonColor: 'var(--primary-color)'
    });
    
    emailInput.value = '';
};

// Générer les cases du calendrier
function generateCalendar() {
    const grid = document.getElementById('calendar-grid');
    grid.innerHTML = '';
    
    // Thèmes des jours (exemples - à remplacer par vos propres surprises)
    const dayThemes = [
        "Outil Python : Automatisation PDF", "Template CSS 3D", "Script Arduino LED RGB",
        "Modèle 3D : Robot éducatif", "Extension VS Code : Palettes couleurs", "Projet PCB : Station météo",
        "Bibliothèque JS : Animations canvas", "Template Figma : Dashboard IoT", "Script : Optimisation images",
        "Projet Raspberry : Serveur média", "CSS : Effets néon interactifs", "Outil : Générateur de QR Code",
        "Modèle Fusion 360 : Support téléphone", "Plugin Blender : Export optimisé", "API Node.js : Webhooks",
        "Template React : Portfolio dev", "Projet ESP32 : Capteur CO2", "Shaders GLSL : Effets visuels",
        "Outil CLI : Gestion de projets", "Template Three.js : Galerie 3D", "Script : Backup automatique",
        "PCB : Contrôleur MIDI personnalisé", "Template : Site e-commerce", "Surprise spéciale Noël 🎄"
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
                '<i class="fas fa-lock lock-icon"></i>' : 
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
        "https://gael-berru.com/3D/",
        "https://codepen.io/collection/3d-css",
        "https://github.com/berru-g/arduino-rgb-led",
        // ... ajoutez vos 24 liens
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