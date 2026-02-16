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
const EASTER_EGG_PASSWORD = "honeypot26"; // ee
const EASTER_EGG_URL = "./smart_phpixel/pixel/dashboard.php"; // URL à ouvrir

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

// Précharger le son (optionnel mais recommandé)
// ===== FONCTION SON GLOBALE =====
window.playSound = function(soundFile, volume = 0.6) {
    try {
        const audio = new Audio(`./sounds/${soundFile}`);
        audio.volume = volume;
        audio.play().catch(() => {});
        return audio;
    } catch (e) {
        return null;
    }
};

// Raccourcis pour les sons fréquents
// utilisation - sounds.success();
window.sounds = {
    success: () => playSound('notification-success.mp3'),
    error: () => playSound('notification-error.mp3'),
    alert: () => playSound('gems.mp3', 0.4),
    click: () => playSound('point.mp3', 0.9)
};

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

  // ===== BACKDOOR ADMIN  egotrip api test =====
  document.getElementById('settingsBtn').addEventListener('click', () => {
    Swal.fire({
      title: 'Access for aisistant',
      html: `
            <div style="text-align: left; margin: 20px 0;">
                <p style="color: var(--text-secondary); margin-bottom: 15px;">
                  _/<br>
                </p>
                <input 
                    type="password" 
                    id="adminPassword" 
                    class="swal2-input" 
                    placeholder="Mdp..."
                    style="font-family: monospace; letter-spacing: 2px;"
                >
            </div>
        `,
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: 'Accéder',
      cancelButtonText: 'Annuler',
      focusConfirm: false,
      preConfirm: () => {
        const password = document.getElementById('adminPassword').value;
        const validPasswords = ['acceapi'];

        if (!validPasswords.includes(password)) {
          Swal.showValidationMessage('❌ Accéssible aux curieux uniquement');
          return false;
        }
        return password;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        showAdminPanel(result.value);
      }
    });
  });

  function showAdminPanel(password) {
    // Chercher les projets cachés (avec champ hidden: true)
    const hiddenProjects = window.projectsDB.filter(p => p.hidden === true);
    const secretTools = [
      {
        id: 'json-editor',
        name: 'Éditeur JSON',
        icon: '{}',
        action: () => openJsonEditor()
      },
      {
        id: 'api-tester',
        name: 'Testeur API',
        icon: '🌐',
        action: () => testAPI()
      },
      {
        id: 'logs-viewer',
        name: 'Journal Console',
        icon: '📋',
        action: () => showConsoleLogs()
      },
      {
        id: 'cache-clear',
        name: 'Vider Cache',
        icon: '🧹',
        action: () => clearAllCache()
      }
    ];

    Swal.fire({
      title: `🔧 Panel Admin (${password === 'berru' ? 'Mode View' : 'Accès Standard'})`,
      html: `
            <div style="text-align: left; max-height: 400px; overflow-y: auto;">
                <h3 style="color: var(--primary-color); margin-top: 0;">Projets Cachés (${hiddenProjects.length})</h3>
                ${hiddenProjects.map(p => `
                    <div style="background: var(--hover-bg); padding: 10px; border-radius: 8px; margin-bottom: 10px; border-left: 3px solid var(--primary-color);">
                        <strong>${p.title}</strong><br>
                        <small style="color: var(--text-secondary);">${p.shortDesc}</small>
                        <button onclick="window.open('${p.link}', '_blank')" style="margin-top: 5px; padding: 3px 10px; background: var(--primary-color); color: white; border: none; border-radius: 4px; font-size: 12px;">
                            Ouvrir
                        </button>
                    </div>
                `).join('')}
                
                <h3 style="color: var(--primary-color); margin-top: 20px;">Tools</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
                    ${secretTools.map(tool => `
                        <button onclick="window.adminTools['${tool.id}']()" style="padding: 15px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                            <div style="font-size: 24px; margin-bottom: 5px;">${tool.icon}</div>
                            <div style="font-size: 12px;">${tool.name}</div>
                        </button>
                    `).join('')}
                </div>
                
                <h3 style="color: var(--primary-color); margin-top: 20px;">Stat</h3>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px;">
                    <div style="text-align: center; padding: 10px; background: var(--hover-bg); border-radius: 6px;">
                        <div style="font-size: 20px; font-weight: bold;">${window.projectsDB?.length || 0}</div>
                        <small>Projets DB</small>
                    </div>
                    <div style="text-align: center; padding: 10px; background: var(--hover-bg); border-radius: 6px;">
                        <div style="font-size: 20px; font-weight: bold;">${localStorage.length}</div>
                        <small>Items Cache</small>
                    </div>
                    <div style="text-align: center; padding: 10px; background: var(--hover-bg); border-radius: 6px;">
                        <div style="font-size: 20px; font-weight: bold;">${JSON.parse(localStorage.getItem('eggs_found') || '[]').length}</div>
                        <small>Easter Eggs</small>
                    </div>
                </div>
            </div>
        `,
      width: 600,
      showConfirmButton: false,
      showCloseButton: true
    });
  }

  // ===== OUTILS ADMIN =====
  window.adminTools = {
    'json-editor': function () {
      Swal.fire({
        title: '{} View JSON',
        html: `
                <textarea id="jsonInput" style="width: 100%; height: 200px; font-family: monospace; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;" 
                          placeholder='{"test": "json"}'>${JSON.stringify(window.projectsDB.slice(0, 2), null, 2)}</textarea>
            `,
        showCancelButton: true,
        confirmButtonText: '...',
        cancelButtonText: 'Valider',
        preConfirm: () => {
          const input = document.getElementById('jsonInput').value;
          try {
            const parsed = JSON.parse(input);
            return JSON.stringify(parsed, null, 2);
          } catch (e) {
            Swal.showValidationMessage('JSON invalide');
            return false;
          }
        }
      }).then(result => {
        if (result.isConfirmed) {
          document.getElementById('jsonInput').value = result.value;
        }
      });
    },

    'api-tester': function () {
      Swal.fire({
        title: '🌐 Testeur API',
        html: `
                <select id="apiEndpoint" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
                    <option value="profil">Profil Berru (GitHub)</option>
                    <option value="projects">Projets DB</option>
                    <option value="services">Services DB</option>
                </select>
                <button onclick="testSelectedAPI()" style="width: 100%; padding: 10px; background: var(--primary-color); color: white; border: none; border-radius: 8px;">
                    Tester l'API
                </button>
                <div id="apiResult" style="margin-top: 15px; padding: 10px; background: var(--hover-bg); border-radius: 8px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;"></div>
            `,
        width: 500,
        showConfirmButton: false
      });
    },

    'logs-viewer': function () {
      // Capture les derniers logs de console
      const originalLog = console.log;
      let logs = [];
      console.log = function (...args) {
        logs.push({ type: 'log', args, time: new Date().toLocaleTimeString() });
        originalLog.apply(console, args);
      };

      Swal.fire({
        title: '📋 Journal Console',
        html: `
                <div style="text-align: left; max-height: 300px; overflow-y: auto; background: #1e1e1e; color: #f0f0f0; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px;">
                    ${logs.slice(-20).map(log => `
                        <div style="color: #4cc9f0; margin-bottom: 5px;">
                            [${log.time}] ${log.args.map(arg =>
          typeof arg === 'string' ? arg : JSON.stringify(arg)
        ).join(' ')}
                        </div>
                    `).join('')}
                </div>
                <button onclick="console.clear(); Swal.close();" style="margin-top: 15px; padding: 8px 15px; background: var(--primary-light); color: var(--primary-color); border: none; border-radius: 6px;">
                    Vider Console
                </button>
            `,
        width: 700,
        showConfirmButton: false
      });
    },

    'cache-clear': function () {
      Swal.fire({
        title: '🧹 Vider le Cache',
        html: `
                <p>Quel cache vider ?</p>
                <div style="display: grid; gap: 10px; margin-top: 15px;">
                    <button onclick="clearCache('all')" style="padding: 10px; background: #ef4444; color: white; border: none; border-radius: 6px;">
                        🗑️ Tout le cache
                    </button>
                    <button onclick="clearCache('profile')" style="padding: 10px; background: var(--primary-light); color: var(--primary-color); border: none; border-radius: 6px;">
                        📁 Cache Profil
                    </button>
                    <button onclick="clearCache('search')" style="padding: 10px; background: var(--primary-light); color: var(--primary-color); border: none; border-radius: 6px;">
                        🔍 Cache Recherche
                    </button>
                </div>
            `,
        showConfirmButton: false,
        showCloseButton: true
      });
    }
  };

  function clearCache(type) {
    switch (type) {
      case 'all':
        localStorage.clear();
        sessionStorage.clear();
        Swal.fire('✅ Succès', 'Tous les caches ont été vidés.', 'success');
        break;
      case 'profile':
        localStorage.removeItem('berru_profile_cache');
        Swal.fire('✅ Succès', 'Cache du profil vidé.', 'success');
        break;
      case 'search':
        // Supprimer toutes les clés de cache de recherche
        Object.keys(localStorage).forEach(key => {
          if (key.includes('search') || key.includes('cache')) {
            localStorage.removeItem(key);
          }
        });
        Swal.fire('✅ Succès', 'Cache de recherche vidé.', 'success');
        break;
    }
  }

  // ===== TESTER L'API =====
  window.testSelectedAPI = function () {
    const endpoint = document.getElementById('apiEndpoint').value;
    const resultDiv = document.getElementById('apiResult');

    resultDiv.innerHTML = '⏳ Chargement...';

    setTimeout(() => {
      let data;
      switch (endpoint) {
        case 'profil':
          data = window.berru?.profile?.data || 'Profil non chargé';
          break;
        case 'projects':
          data = window.projectsDB || 'DB non chargée';
          break;
        case 'services':
          data = window.servicesDB || 'DB non chargée';
          break;
      }

      resultDiv.innerHTML = `<pre style="margin: 0;">${JSON.stringify(data, null, 2).substring(0, 1000)}...</pre>`;
    }, 500);
  };
  // fin du trip console 
  /*
    document.getElementById('feedbackBtn').addEventListener('click', () => {
      alert('Rien ici pour le moment');
    });
  */
  // Script pour le formulaire inline
  document.getElementById('inlineContactForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const messageDiv = document.getElementById('formMessage');
    const submitBtn = form.querySelector('button[type="submit"]');

    // Sauvegarde l'état original
    const originalContent = submitBtn.innerHTML;
    const originalBg = submitBtn.style.background;

    // Animation
    submitBtn.innerHTML = 'Envoi...';
    submitBtn.style.background = 'var(--text-secondary)';
    submitBtn.disabled = true;
    messageDiv.textContent = '';

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json' }
      });

      if (response.ok) {
        sounds.success();
        messageDiv.textContent = '✅ Message envoyé !';
        messageDiv.style.color = 'var(--primary-color)';
        form.reset();

        // Feedback visuel temporaire
        submitBtn.innerHTML = '✔️ Envoyé !';
        submitBtn.style.background = '#10b981';
        setTimeout(() => {
          submitBtn.innerHTML = originalContent;
          submitBtn.style.background = originalBg;
          submitBtn.disabled = false;
          messageDiv.textContent = '';
        }, 3000);
      } else {
        throw new Error();
      }
    } catch (error) {
      sounds.error();
      messageDiv.textContent = '❌ Erreur, réessayez ou utilisez contact@gael-berru.com';
      messageDiv.style.color = '#ef4444';
      submitBtn.innerHTML = originalContent;
      submitBtn.style.background = originalBg;
      submitBtn.disabled = false;
    }
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
    showProjectPreview(matches); // Envoie TOUT le tableau
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
  // MODIFICATION : Accepter un tableau OU un seul match
  const matches = Array.isArray(match) ? match : [match];

  const mainContent = document.querySelector('.main-content');
  if (!mainContent) return;

  // Créer ou mettre à jour le preview
  let preview = document.getElementById('dynamic-preview');
  //sounds.success();
  if (!preview) {
    preview = document.createElement('div');
    preview.id = 'dynamic-preview';
    preview.className = 'dynamic-preview';
    mainContent.prepend(preview);
  }

  // MODIFICATION : Construire le HTML pour tous les matches
  let html = '';

  // Header avec nombre de résultats si multiple
  html += `
        <div class="preview-header" data-aos="fade-up" data-aos-delay="200">
            <span class="preview-badge" data-aos="fade-up" data-aos-delay="250">
                ${matches.length === 1 ? 'Projet correspondant' : `${matches.length} résultats`}
            </span>
            <button class="preview-close" onclick="hideProjectPreview()">×</button>
        </div>
    `;

  // Pour chaque match, ajouter le HTML
  matches.forEach((singleMatch, index) => {
    if (singleMatch.type === 'project') {
      const project = singleMatch.data;


      // MODIFICATION : Ajouter un séparateur entre les résultats (sauf pour le premier)
      const separatorStyle = index > 0 ? 'style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color, #e0e0e0);"' : '';

      html += `
                <div class="preview-content" data-aos="fade-up" data-aos-delay="${250 + (index * 50)}" ${separatorStyle}>
                    <div class="preview-image">
                        <img src="${project.image}" alt="${project.title}" loading="lazy">
                    </div>
                    <div class="preview-details" data-aos="fade-up" data-aos-delay="${250 + (index * 50)}">
                        <h3 class="preview-title">${project.title}</h3>
                        <div class="preview-tags">
                            ${project.tags.map(tag => `<span class="preview-tag" data-aos="fade-up" data-aos-delay="${200 + (index * 50)}"># ${tag}</span>`).join('')}
                        </div>
                        <p class="preview-desc">${project.longDesc}</p>
                        <div class="preview-features" data-aos="fade-up" data-aos-delay="${250 + (index * 50)}">
                            ${project.features.map(feature => `<span class="preview-feature" data-aos="fade-up" data-aos-delay="${300 + (index * 50)}">✓ ${feature}</span>`).join('')}
                        </div>
                        <a href="${project.link}" target="_blank" class="preview-link">
                            Voir le projet →
                        </a>
                    </div>
                </div>
            `;
    } else {
      const service = singleMatch.data;
      html += `
                <div class="preview-content service-preview" data-aos="fade-up" data-aos-delay="${200 + (index * 50)}">
                    <div class="preview-icon">${service.icon}</div>
                    <div class="preview-details">
                        <h3 class="preview-title" data-aos="fade-up" data-aos-delay="${250 + (index * 50)}">${service.title}</h3>
                        <p class="preview-desc" data-aos="fade-up" data-aos-delay="${300 + (index * 50)}">${service.desc}</p>
                        <div class="preview-keywords">
                            ${service.keywords.map(kw => `<span class="preview-keyword" data-aos="fade-up" data-aos-delay="${350 + (index * 50)}">#${kw}</span>`).join('')}
                        </div>
                    </div>
                </div>
            `;
    }
  });

  // MODIFICATION : Mettre tout le HTML d'un coup
  preview.innerHTML = html;

  // Animation d'entrée (inchangée)
  preview.style.opacity = '0';
  preview.style.transform = 'translateX(-20px)';

  requestAnimationFrame(() => {
    preview.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
    preview.style.opacity = '1';
    preview.style.transform = 'translateX(0)';
  });
}



// hideProjectPreview reste exactement pareil
function hideProjectPreview() {
  const preview = document.getElementById('dynamic-preview');
  if (preview) {
    preview.style.opacity = '0';
    preview.style.transform = 'translateX(-20px)';
    setTimeout(() => preview.remove(), 400);
  }
}

