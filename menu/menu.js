// ===== VARIABLES GLOBALES =====
let searchTimeout;
const searchInput = document.getElementById('searchInput');
const navContainer = document.getElementById('navContainer');
const allNavItems = Array.from(navContainer.querySelectorAll('.nav-item'));
const allNavParents = Array.from(navContainer.querySelectorAll('.nav-parent'));

// ===== DETECTION MODE SOMBRE/CLAIR =====
function updateThemeIndicator() {
  const isDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
  //document.getElementById('modeIndicator').textContent =
    isDarkMode ? '🌙 Mode sombre' : '☀️ Mode clair';
}

// Surveiller les changements de thème
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateThemeIndicator);

// ===== EASTER EGG - MOT DE PASSE DANS LA RECHERCHE =====
const EASTER_EGG_PASSWORD = "amour"; // ee
const EASTER_EGG_URL = "./app/login.php"; // URL à ouvrir

function checkEasterEgg(searchTerm) {
  // Convertir en minuscules et supprimer les espaces
  const cleanTerm = searchTerm.toLowerCase().trim();
  
  // Vérifier si l'utilisateur a tapé le mot de passe
  if (cleanTerm === EASTER_EGG_PASSWORD) {
    // Ouvrir la page secrète
    window.open(EASTER_EGG_URL, '_blank');
    
    // Optionnel : vider la barre de recherche
    searchInput.value = '';
    
    // Optionnel : feedback visuel
    showEasterEggNotification();
    
    // Optionnel : logger l'accès
    console.log('Easter egg activé !');
    
    return true;
  }
  
  return false;
}

function showEasterEggNotification() {
  // Créer une notification stylée
  const notification = document.createElement('div');
  notification.innerHTML = `
    <style>
      .easter-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        z-index: 9999;
        animation: slideIn 0.5s ease, fadeOut 0.5s ease 2.5s forwards;
        font-family: 'Inter', sans-serif;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 300px;
      }
      @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      @keyframes fadeOut {
        to { opacity: 0; }
      }
      .easter-icon {
        font-size: 1.5em;
      }
    </style>
    <div class="easter-notification">
      <span class="easter-icon">🫡</span>
      <span>Accès secret activé ! Redirection...</span>
    </div>
  `;
  
  document.body.appendChild(notification);
  
  // Supprimer après l'animation
  setTimeout(() => {
    notification.remove();
  }, 3000);
}

// ===== RECHERCHE FONCTIONNELLE =====
function performSearch(searchTerm) {
  // Réinitialiser
  allNavItems.forEach(item => {
    item.classList.remove('match', 'related', 'hidden');
  });

  // Réinitialiser les parents
  allNavParents.forEach(parent => {
    parent.classList.remove('expanded');
  });

  if (!searchTerm || searchTerm.length < 2) {
    // Tout afficher
    allNavParents.forEach(parent => {
      parent.classList.add('expanded');
    });
    return;
  }

  const term = searchTerm.toLowerCase();
  const matches = new Set();
  const relatedParents = new Set();

  // Rechercher dans les items
  allNavItems.forEach(item => {
    const text = item.textContent.toLowerCase();
    const searchData = item.getAttribute('data-search') || '';

    if (text.includes(term) || searchData.includes(term)) {
      matches.add(item);
      item.classList.add('match');

      // Trouver le parent et l'étendre
      let parent = item.closest('.nav-parent');
      while (parent) {
        relatedParents.add(parent);
        parent.classList.add('expanded');
        parent = parent.parentElement.closest('.nav-parent');
      }
    }
  });

  // Marquer les items liés (dans les parents étendus)
  allNavItems.forEach(item => {
    const parent = item.closest('.nav-parent');
    if (parent && relatedParents.has(parent) && !matches.has(item)) {
      item.classList.add('related');
    }
  });

  // Cacher les items non pertinents
  allNavItems.forEach(item => {
    const parent = item.closest('.nav-parent');
    if (!matches.has(item) && !(parent && relatedParents.has(parent))) {
      item.classList.add('hidden');
    }
  });

  // Réduire les parents sans résultats
  allNavParents.forEach(parent => {
    const hasVisibleChildren = Array.from(parent.querySelectorAll('.nav-item'))
      .some(item => !item.classList.contains('hidden'));

    if (!hasVisibleChildren && !relatedParents.has(parent)) {
      parent.classList.remove('expanded');
    }
  });
}

// ===== TOGGLE MENU =====
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
}

// ===== TOGGLE SOUS-MENUS =====
function setupToggleButtons() {
  document.querySelectorAll('.nav-parent > .nav-item').forEach(item => {
    item.addEventListener('click', (e) => {
      const parent = e.currentTarget.closest('.nav-parent');
      if (parent) {
        e.stopPropagation();
        parent.classList.toggle('expanded');

        // Si on ferme un parent, fermer aussi les enfants
        if (!parent.classList.contains('expanded')) {
          parent.querySelectorAll('.nav-parent').forEach(child => {
            child.classList.remove('expanded');
          });
        }
      }
    });
  });
}

// ===== RACCOURCI CLAVIER =====
function setupKeyboardShortcuts() {
  // Raccourci "/" pour focus la recherche
  document.addEventListener('keydown', (e) => {
    const tag = e.target.tagName.toLowerCase();
    const isInput = tag === 'input' || tag === 'textarea' || e.target.isContentEditable;

    if (e.key === '/' && !isInput) {
      e.preventDefault();
      searchInput.focus();
      searchInput.select();
    }

    // Échap pour vider la recherche
    if (e.key === 'Escape' && document.activeElement === searchInput) {
      searchInput.value = '';
      performSearch('');
    }
  });
}

// ===== INITIALISATION =====
document.addEventListener('DOMContentLoaded', () => {
  // Détection thème
  updateThemeIndicator();

// ===== MODIFIER L'ÉCOUTEUR DE RECHERCHE =====
searchInput.addEventListener('input', (e) => {
    const searchTerm = e.target.value.trim();
    
    // Easter egg d'abord
    if (checkEasterEgg(searchTerm)) {
        hideProjectPreview();
        return;
    }
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        // Recherche dans le menu (originale)
        performSearch(searchTerm);
        
        // Recherche étendue dans la base de projets
        performExtendedSearch(searchTerm);
    }, 150);
});

// Masquer le preview quand on vide la recherche
searchInput.addEventListener('keyup', (e) => {
    if (e.target.value.trim().length < 2) {
        hideProjectPreview();
    }
});

  // Toggle sidebar
  document.getElementById('toggleBtn').addEventListener('click', toggleSidebar);

  // Toggle sous-menus
  setupToggleButtons();

  // Raccourcis clavier
  setupKeyboardShortcuts();

  // Boutons footer
  document.getElementById('settingsBtn').addEventListener('click', () => {
    alert('Paramètres - Fonctionnalité à implémenter');
  });

  document.getElementById('feedbackBtn').addEventListener('click', () => {
    alert('Feedback - Envoyez vos retours à gael-berru.com');
  });

  // Fermer le sidebar en cliquant à l'extérieur (mobile)
  document.addEventListener('click', (e) => {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleBtn');

    if (window.innerWidth <= 768 &&
      sidebar.classList.contains('open') &&
      !sidebar.contains(e.target) &&
      !toggleBtn.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });

  console.log('✅ Menu Berru-g initialisé avec succès');
  console.log('🔍 Recherche fonctionnelle avec indexation');
  console.log('🌓 Mode sombre/clair auto-détecté');
});

// ===== EXPORT POUR DEBUG =====
window.menuManager = {
  toggleSidebar,
  performSearch,
  updateThemeIndicator
};


// ===== RECHERCHE ÉTENDUE AVEC AFFICHAGE DANS MAIN =====
function performExtendedSearch(searchTerm) {
    if (!searchTerm || searchTerm.length < 2) {
        hideProjectPreview();
        return;
    }
    
    const term = searchTerm.toLowerCase();
    const matches = [];
    
    // Recherche dans les projets
    projectsDatabase.forEach(project => {
        const searchSpace = [
            project.title,
            project.shortDesc,
            project.longDesc,
            ...project.keywords,
            ...project.tags.map(t => t.toLowerCase())
        ].join(' ').toLowerCase();
        
        if (searchSpace.includes(term) || 
            project.keywords.some(kw => kw.includes(term)) ||
            project.title.toLowerCase().includes(term)) {
            matches.push({
                type: 'project',
                data: project,
                relevance: calculateRelevance(project, term)
            });
        }
    });
    
    // Recherche dans les services
    servicesDatabase.forEach(service => {
        const searchSpace = [
            service.title,
            service.desc,
            ...service.keywords
        ].join(' ').toLowerCase();
        
        if (searchSpace.includes(term)) {
            matches.push({
                type: 'service',
                data: service,
                relevance: 1
            });
        }
    });
    
    // Trier par pertinence
    matches.sort((a, b) => b.relevance - a.relevance);
    
    // Afficher le meilleur résultat
    if (matches.length > 0) {
        showProjectPreview(matches[0]);
    } else {
        hideProjectPreview();
    }
}

function calculateRelevance(project, term) {
    let relevance = 0;
    
    // Titre exact = haute priorité
    if (project.title.toLowerCase().includes(term)) relevance += 5;
    
    // Mots-clés exacts
    if (project.keywords.some(kw => kw === term)) relevance += 3;
    
    // Contenu de la description
    if (project.longDesc.toLowerCase().includes(term)) relevance += 2;
    if (project.shortDesc.toLowerCase().includes(term)) relevance += 1;
    
    return relevance;
}


// ===== AFFICHAGE DANS MAIN =====
function showProjectPreview(match) {
    const mainContent = document.querySelector('.main-content');
    if (!mainContent) return;
    
    // Créer ou mettre à jour le preview
    let preview = document.getElementById('dynamic-preview');
    
    if (!preview) {
        preview = document.createElement('div');
        preview.id = 'dynamic-preview';
        preview.className = 'dynamic-preview';
        mainContent.prepend(preview);
    }
    
    if (match.type === 'project') {
        console.log('Match');
        const project = match.data;
        preview.innerHTML = `
            <div class="preview-header" data-aos="fade-up" data-aos-delay="200">
                <span class="preview-badge">Projet correspondant</span>
                <button class="preview-close" onclick="hideProjectPreview()">×</button>
            </div>
            <div class="preview-content"data-aos="fade-up" data-aos-delay="250">
                <div class="preview-image">
                    <img src="${project.image}" alt="${project.title}" loading="lazy">
                </div>
                <div class="preview-details" data-aos="fade-up" data-aos-delay="300">
                    <h3 class="preview-title">${project.title}</h3>
                    <div class="preview-tags">
                        ${project.tags.map(tag => `<span class="preview-tag"># ${tag}</span>`).join('')}
                    </div>
                    <p class="preview-desc">${project.longDesc}</p>
                    <div class="preview-features">
                        ${project.features.map(feature => `<span class="preview-feature">✓ ${feature}</span>`).join('')}
                    </div>
                    <a href="${project.link}" target="_blank" class="preview-link">
                        Voir le projet →
                    </a>
                </div>
            </div>
        `;
    } else {
        const service = match.data;
        preview.innerHTML = `
            <div class="preview-header" data-aos="fade-up" data-aos-delay="200">
                <span class="preview-badge">Service correspondant</span>
                <button class="preview-close" onclick="hideProjectPreview()">×</button>
            </div>
            <div class="preview-content service-preview" data-aos="fade-up" data-aos-delay="200">
                <div class="preview-icon">${service.icon}</div>
                <div class="preview-details">
                    <h3 class="preview-title">${service.title}</h3>
                    <p class="preview-desc">${service.desc}</p>
                    <div class="preview-keywords">
                        ${service.keywords.map(kw => `<span class="preview-keyword">#${kw}</span>`).join('')}
                    </div>
                </div>
            </div>
        `;
    }
    
    // Animation d'entrée
    preview.style.opacity = '0';
    preview.style.transform = 'translateX(-20px)';
    
    requestAnimationFrame(() => {
        preview.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
        preview.style.opacity = '1';
        preview.style.transform = 'translateX(0)';
    });
}

function hideProjectPreview() {
    const preview = document.getElementById('dynamic-preview');
    if (preview) {
        preview.style.opacity = '0';
        preview.style.transform = 'translateX(-20px)';
        setTimeout(() => preview.remove(), 400);
    }
}

