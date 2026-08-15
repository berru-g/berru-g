// ============================================
// BERRU PROFILE API - Version Clean
// ============================================

// 📌 INITIALISATION
// L'objet 'berru' est créé s'il n'existe pas déjà
window.berru = window.berru || {};

// ============================================
// 📦 PARTIE 1 : GESTION DU CACHE ET API
// ============================================

berru.profile = {
    // 🔗 URL de ton API JSON sur GitHub
    apiUrl: 'https://raw.githubusercontent.com/berru-g/berru-g/main/api/profil_4_ai.json',
    
    // 💾 Clé pour le cache localStorage
    cacheKey: 'berru_profile_cache',
    
    // ⏰ Durée de validité du cache : 24 heures
    cacheDuration: 24 * 60 * 60 * 1000, // en millisecondes
    
    // 📥 Fonction : Charger les données depuis GitHub
    loadFromAPI: async function() {
        console.log('%c📡 Chargement du profil depuis GitHub...', 'color: #8a6ff8;');
        
        try {
            // 1. Faire la requête HTTP
            const response = await fetch(this.apiUrl);
            
            // 2. Vérifier si la requête a réussi
            if (!response.ok) {
                throw new Error(`Erreur HTTP ${response.status}`);
            }
            
            // 3. Convertir la réponse en JSON
            const data = await response.json();
            
            // 4. Sauvegarder dans le cache localStorage
            const cacheData = {
                data: data,           // Les données brutes
                timestamp: Date.now() // Date/heure du cache
            };
            localStorage.setItem(this.cacheKey, JSON.stringify(cacheData));
            
            console.log('%c✅ Profil chargé avec succès !', 'color: #10b981;');
            return data;
            
        } catch (error) {
            console.error('%c❌ Erreur :', 'color: #ef4444;', error.message);
            return null;
        }
    },
    
    // 🔄 Fonction : Récupérer les données (cache ou API)
    get: async function(forceRefresh = false) {
        // Si on a déjà les données en mémoire, on les retourne
        if (this.data && !forceRefresh) {
            return this.data;
        }
        
        // Vérifier le cache localStorage
        if (!forceRefresh) {
            const cached = localStorage.getItem(this.cacheKey);
            if (cached) {
                try {
                    const { data, timestamp } = JSON.parse(cached);
                    
                    // Vérifier si le cache est encore frais (< 24h)
                    if (Date.now() - timestamp < this.cacheDuration) {
                        console.log('%c💾 Données depuis le cache', 'color: #f59e0b;');
                        this.data = data; // Mettre en mémoire
                        return data;
                    }
                } catch (e) {
                    console.warn('Cache invalide, rechargement...');
                }
            }
        }
        
        // Sinon, charger depuis l'API
        const freshData = await this.loadFromAPI();
        if (freshData) {
            this.data = freshData; // Mettre en mémoire
        }
        
        return this.data;
    }
};

// ============================================
// 🛠️ PARTIE 2 : COMMANDES UTILISABLES DANS LA CONSOLE
// ============================================

berru.explore = {
    // 👤 COMMANDE : Afficher un résumé de ton profil
    // USAGE dans console : b.me()  OU  await berru.explore.summary()
    summary: async function() {
        try {
            const profile = await berru.profile.get();
            if (!profile) return;
            
            // Trouver automatiquement les noms des champs
            const find = (names, def = '?') => {
                for (const name of names) {
                    if (profile[name] !== undefined) return profile[name];
                }
                return def;
            };
            
            // Extraire les données
            const nom = find(['nom', 'name'], 'Gaël Berru');
            const desc = find(['description_courte', 'short_description'], '');
            const projets = find(['projets', 'projects'], []);
            const email = find(['coordonnees.email', 'contact.email', 'email'], '');
            
            // Afficher
            console.log(`
%c👤 ${nom}
%c${desc}
%c
📊 Statistiques :
• ${Array.isArray(projets) ? projets.length : 0} projets
• Contact : ${email}
            `,
            'color: #8a6ff8; font-size: 18px; font-weight: bold;',
            'color: #666; font-style: italic;',
            'color: #4cc9f0;');
            
        } catch (error) {
            console.error('Erreur :', error);
        }
    },
    
    // 📊 COMMANDE : Afficher les statistiques
    // USAGE : b.stats()
    stats: function() {
        berru.profile.get().then(profile => {
            if (!profile) return;
            
            console.log('%c📈 Statistiques détaillées', 'color: #4361ee; font-weight: bold;');
            
            // Compter tous les tableaux
            const counts = {};
            Object.keys(profile).forEach(key => {
                const val = profile[key];
                if (Array.isArray(val)) {
                    counts[key] = val.length;
                }
            });
            
            console.table(counts);
        }).catch(console.error);
    },
    
    // 🔑 COMMANDE : Voir toutes les clés disponibles
    // USAGE : b.keys()
    keys: function() {
        berru.profile.get().then(profile => {
            console.log('%c🔑 Structure de ton API', 'color: #f72585;');
            
            const structure = Object.keys(profile).map(key => {
                const value = profile[key];
                return {
                    'Clé': key,
                    'Type': Array.isArray(value) ? `Tableau[${value.length}]` : typeof value,
                    'Valeur exemple': JSON.stringify(value).substring(0, 60) + '...'
                };
            });
            
            console.table(structure);
        }).catch(console.error);
    },
    
    // 📁 COMMANDE : Lister tes projets
    // USAGE : b.projets()  OU  b.projets('3D')
    projets: function(search = '') {
        berru.profile.get().then(profile => {
            if (!profile || !profile.projets) return;
            
            let projets = profile.projets;
            const term = search.toLowerCase();
            
            if (search) {
                projets = projets.filter(p => 
                    (p.titre && p.titre.toLowerCase().includes(term)) ||
                    (p.categorie && p.categorie.toLowerCase().includes(term)) ||
                    (p.tags && p.tags.some(tag => tag.toLowerCase().includes(term)))
                );
            }
            
            console.log(`%c📁 Projets (${projets.length})`, 'color: #10b981; font-weight: bold;');
            
            projets.forEach((p, i) => {
                console.log(`%c${i + 1}. ${p.titre || 'Sans titre'}`, 'color: #8a6ff8;');
                if (p.description_courte) console.log(`   ${p.description_courte}`);
                if (p.categorie) console.log(`   🏷️  ${p.categorie}`);
                if (p.lien) console.log(`   🔗 ${p.lien}`);
                console.log('');
            });
        }).catch(console.error);
    },
    
    // 🛠️ COMMANDE : Lister tes services
    // USAGE : b.services()
    services: function() {
        berru.profile.get().then(profile => {
            if (!profile || !profile.services_principaux) return;
            
            console.log('%c🛠️ Services proposés', 'color: #f72585; font-weight: bold;');
            
            profile.services_principaux.forEach((s, i) => {
                console.log(`%c${i + 1}. ${s.nom}`, 'color: #7209b7;');
                if (s.description) console.log(`   ${s.description}`);
                if (s.phrase_accroche) console.log(`   💬 "${s.phrase_accroche}"`);
                console.log('');
            });
        }).catch(console.error);
    }
};

// ============================================
// 🎯 PARTIE 3 : RACCOURCIS CONSOLE (PAS BESOIN DE AWAIT)
// ============================================

// 🚀 Raccourcis pour la console : Commence par 'b.'
// === NOUVELLES COMMANDES "b." POUR TON PROFIL RÉEL ===
window.b = {
  // 1. Voir ton manifeste et tes principes
  me: function() {
    berru.profile.get().then(profile => {
      console.log(`%c👤 ${profile.identite_philosophie.pseudo}`, 'color: #8a6ff8; font-size: 18px; font-weight: bold;');
      console.log(`%c"${profile.identite_philosophie.manifeste}"`, 'color: #666; font-style: italic; margin-bottom: 15px;');
      console.log('%c🧭 Principes Fondateurs :', 'color: #4361ee; font-weight: bold;');
      profile.identite_philosophie.principes_fondateurs.forEach((p, i) => {
        console.log(`  ${i+1}. ${p}`);
      });
    });
  },

  // 2. Explorer tes compétences par catégorie
  skill: function(categorie = '') {
    berru.profile.get().then(profile => {
      const comp = profile.competences_techniques;
      if(categorie && comp[categorie]) {
        console.log(`%c🔧 ${categorie.toUpperCase()}`, 'color: #f72585; font-weight: bold;');
        console.log(comp[categorie]);
      } else {
        console.log('%c🛠️  Catégories de Compétences', 'color: #f72585; font-weight: bold;');
        Object.keys(comp).forEach(key => console.log(`  • ${key}`));
        console.log('\n%c💡 Usage: b.skill("frontend")', 'color: #888;');
      }
    });
  },

  // 3. Lister tes projets en cours (la vraie liste !)
  projetsEnCours: function() {
    berru.profile.get().then(profile => {
      const projets = profile.projets_en_cours;
      console.log('%c🚀 Projets en Cours', 'color: #10b981; font-weight: bold;');
      Object.keys(projets).forEach(key => {
        const p = projets[key];
        console.log(`\n%c📌 ${key.replace(/_/g, ' ').toUpperCase()}`, 'color: #8a6ff8;');
        console.log(`  Statut: ${p.statut}`);
        console.log(`  Desc: ${p.description}`);
      });
    });
  },

  // 4. Voir tes projets de référence (comme Smart Pixel)
  projetsRefs: function() {
    berru.profile.get().then(profile => {
      console.log('%c🏆 Projets Clés de Référence', 'color: #ffd700; font-weight: bold;');
      profile.references_projets_cle.forEach((proj, i) => {
        console.log(`\n%c${i+1}. ${proj.nom}`, 'color: #7209b7;');
        console.log(`  ${proj.description}`);
        console.log(`  Tags: ${proj.tags.join(', ')}`);
      });
    });
  },

  // 5. Voir la roadmap
  roadmap: function() {
    berru.profile.get().then(profile => {
      console.log('%c🗺️  Roadmap Technique', 'color: #4cc9f0; font-weight: bold;');
      Object.keys(profile.roadmap_technique).forEach(periode => {
        console.log(`\n%c${periode.replace(/_/g, ' ').toUpperCase()}:`, 'color: #8a6ff8;');
        profile.roadmap_technique[periode].forEach(item => console.log(`  • ${item}`));
      });
    });
  }
};

// ============================================
// 🚀 PARTIE 4 : INITIALISATION AUTOMATIQUE
// ============================================

// Charger le profil automatiquement au démarrage
setTimeout(async () => {
    try {
        // Charger en arrière-plan
        await berru.profile.get();
        
        // Afficher un message d'accueil
        console.log(`
%c
╔══════════════════════════════════╗
║        API berru-g 4 GEEK        ║
╚══════════════════════════════════╝
%c
Liste des commandes :

b.me()               // 👤 Pour voir mon manifeste
b.skill()       // 🛠️  Pour lister toutes mes compétences
b.skill("frontend") // 🔧 Pour explorer une catégorie spécifique

        `,
        'color: #8a6ff8; font-family: monospace;',
        'color: #4cc9f0;',
        'color: #8a6ff8; font-weight: bold;',
        'color: #4cc9f0;',
        'color: #8a6ff8; font-weight: bold;',
        'color: #4cc9f0;',
        'color: #8a6ff8; font-weight: bold;',
        'color: #4cc9f0;');
        
    } catch (error) {
        console.warn('API non chargée :', error.message);
    }
}, 2000);

// ============================================
// 📝 GUIDE D'UTILISATION RAPIDE
// ============================================

/*

1. Liste des commandes :
b.me()               // 👤 Pour voir ton manifeste
b.skill()       // 🛠️  Pour lister toutes les catégories de compétences
b.skill("frontend") // 🔧 Pour explorer une catégorie spécifique
b.projetsEnCours()    // 🚀 Pour voir tes vrais projets actuels (honeypot, mixer...)
b.projetsRefs()       // 🏆 Pour la liste de tes projets phares
b.roadmap()           // 🗺️  Pour tes prochaines étapes
*/
