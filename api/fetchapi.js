// ===== BERRU PROFILE API =====
(function() {
    'use strict';
    
    // Initialiser l'objet berru s'il n'existe pas
    window.berru = window.berru || {};
    
    // Configuration
    berru.profile = {
        apiUrl: 'https://raw.githubusercontent.com/berru-g/berru-g/main/api/profil_4_ai.json',
        cacheKey: 'berru_profile_cache',
        cacheDuration: 24 * 60 * 60 * 1000, // 24h en ms
        
        // Charger depuis l'API
        loadFromAPI: async function() {
            console.log('%c Chargement du profil_api depuis GitHub...', 'color: #8a6ff8;');
            
            try {
                const response = await fetch(this.apiUrl);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                
                // Sauvegarder dans le cache
                const cacheData = {
                    data: data,
                    timestamp: Date.now()
                };
                localStorage.setItem(this.cacheKey, JSON.stringify(cacheData));
                
                console.log('%c✅ Profil chargé avec succès!', 'color: #10b981;');
                return data;
                
            } catch (error) {
                console.error('%c❌ Erreur API:', 'color: #ef4444;', error.message);
                return null;
            }
        },
        
        // Récupérer (cache ou API)
        get: async function(forceRefresh = false) {
            // Si déjà chargé en mémoire, le retourner
            if (this.data && !forceRefresh) {
                return this.data;
            }
            
            // Vérifier le cache local
            if (!forceRefresh) {
                const cached = localStorage.getItem(this.cacheKey);
                if (cached) {
                    try {
                        const { data, timestamp } = JSON.parse(cached);
                        
                        // Vérifier si le cache est encore valide
                        if (Date.now() - timestamp < this.cacheDuration) {
                            console.log('%c💾 Données depuis cache', 'color: #f59e0b;');
                            this.data = data;
                            return data;
                        }
                    } catch (e) {
                        console.warn('Cache invalide, rechargement...');
                    }
                }
            }
            
            // Charger depuis l'API
            const freshData = await this.loadFromAPI();
            if (freshData) {
                this.data = freshData;
            }
            
            return this.data;
        },
        
        // Vérifier si le profil est chargé
        isLoaded: function() {
            return !!this.data;
        }
    };
    
    // Commandes d'exploration
    // ===== VERSION AUTO-ADAPTATIVE =====
berru.explore = {
    // Fonction intelligente qui trouve les données quel que soit le nom
    summary: async function() {
        const profile = await berru.profile.get();
        if (!profile) {
            console.log('%c❌ Profil non chargé', 'color: #ef4444;');
            return;
        }
        
        // Chercher les données automatiquement
        const findData = (possibleNames, defaultValue = 'Non spécifié') => {
            for (const name of possibleNames) {
                if (profile[name] !== undefined) {
                    return profile[name];
                }
            }
            return defaultValue;
        };
        
        // Noms possibles pour chaque champ
        const nom = findData(['nom', 'name', 'full_name', 'prenom_nom'], 'Gaël Berru');
        const description = findData(['description_courte', 'short_description', 'bio', 'description'], 'Développeur');
        const projets = findData(['projets', 'projects', 'works', 'realisations'], []);
        const competences = findData(['competences_techniques', 'skills', 'technologies', 'competences'], []);
        const services = findData(['services_principaux', 'services', 'offerings', 'expertise'], []);
        const email = findData(['coordonnees.email', 'contact.email', 'email', 'mail'], 'contact@gael-berru.com');
        
        // Afficher
        console.log(`
%c👤 ${nom}
%c${description}
%c
📊 Stats:
• ${Array.isArray(projets) ? projets.length : 0} projets
• ${Array.isArray(competences) ? competences.length : 0} compétences  
• ${Array.isArray(services) ? services.length : 0} services
• Contact: ${email}
        `,
        'color: #8a6ff8; font-size: 18px; font-weight: bold;',
        'color: #666; font-style: italic;',
        'color: #4cc9f0;');
        
        // Afficher les 3 premiers projets si existants
        if (Array.isArray(projets) && projets.length > 0) {
            console.log('%c🎯 3 derniers projets:', 'color: #10b981;');
            projets.slice(0, 3).forEach((p, i) => {
                const titre = p.titre || p.title || p.name || `Projet ${i + 1}`;
                console.log(`  ${i + 1}. ${titre}`);
            });
        }
    },
    
    // COMMANDES SIMPLES POUR LA CONSOLE
    // Pas besoin de "await" avec celles-ci
    me: function() {
        // Cette version retourne une Promise mais gère l'async automatiquement
        berru.explore.summary().catch(console.error);
    },
    
    stats: function() {
        berru.profile.get().then(profile => {
            if (!profile) return;
            
            console.log('%c📈 Statistiques:', 'color: #4361ee; font-weight: bold;');
            
            // Compter toutes les entrées
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
    
    // Afficher les clés disponibles
    keys: function() {
        berru.profile.get().then(profile => {
            console.log('%c🔑 Clés disponibles:', 'color: #f72585;');
            console.table(Object.keys(profile).map(key => ({
                'Clé': key,
                'Type': Array.isArray(profile[key]) ? `Array[${profile[key].length}]` : typeof profile[key],
                'Exemple': JSON.stringify(profile[key]).substring(0, 50) + '...'
            })));
        });
    }
};

// ===== COMMANDES RACCOURCI =====
// Pour utiliser SANS "await" dans la console
window.b = {
    me: () => berru.explore.me(),
    stats: () => berru.explore.stats(),
    keys: () => berru.explore.keys(),
    raw: () => berru.profile.get().then(p => console.log(p))
};
    
    // ===== INTÉGRATION AVEC LA RECHERCHE EXISTANTE =====
    // Modifier performExtendedSearch pour inclure ton profil
    const originalPerformExtendedSearch = window.performExtendedSearch;
    
    window.performExtendedSearch = async function(searchTerm) {
        // Appeler la fonction originale d'abord
        if (originalPerformExtendedSearch) {
            originalPerformExtendedSearch(searchTerm);
        }
        
        // Ensuite, chercher dans ton profil
        if (searchTerm && searchTerm.length >= 2) {
            await searchInProfile(searchTerm);
        }
    };
    
    async function searchInProfile(searchTerm) {
        const profile = await berru.profile.get();
        if (!profile) return;
        
        const term = searchTerm.toLowerCase();
        const matches = [];
        
        // Chercher dans les projets
        if (profile.projets) {
            profile.projets.forEach(project => {
                const searchSpace = [
                    project.titre,
                    project.description_courte,
                    project.description_longue,
                    project.categorie,
                    ...(project.tags || []),
                    ...(project.technologies_utilisees || [])
                ].join(' ').toLowerCase();
                
                if (searchSpace.includes(term)) {
                    matches.push({
                        type: 'profile_project',
                        data: project,
                        relevance: calculateProfileRelevance(project, term)
                    });
                }
            });
        }
        
        // Chercher dans les services
        if (profile.services_principaux) {
            profile.services_principaux.forEach(service => {
                const searchSpace = [
                    service.nom,
                    service.description,
                    service.phrase_accroche,
                    ...(service.mots_cles || [])
                ].join(' ').toLowerCase();
                
                if (searchSpace.includes(term)) {
                    matches.push({
                        type: 'profile_service',
                        data: service,
                        relevance: 3
                    });
                }
            });
        }
        
        // Si on a des matches, les afficher
        if (matches.length > 0) {
            console.log(`%c🔍 ${matches.length} résultat(s) dans ton profil`, 'color: #10b981;');
            
            matches.sort((a, b) => b.relevance - a.relevance);
            matches.slice(0, 3).forEach(match => {
                if (match.type === 'profile_project') {
                    console.log(`   🎯 ${match.data.titre} (projet)`);
                } else {
                    console.log(`   🛠️ ${match.data.nom} (service)`);
                }
            });
            
            // Optionnel: Afficher dans le preview aussi
            // showProfileResultsInPreview(matches, searchTerm);
        }
    }
    
    function calculateProfileRelevance(project, term) {
        let score = 0;
        
        if (project.titre?.toLowerCase().includes(term)) score += 5;
        if (project.tags?.some(tag => tag.toLowerCase().includes(term))) score += 4;
        if (project.categorie?.toLowerCase().includes(term)) score += 3;
        if (project.description_courte?.toLowerCase().includes(term)) score += 2;
        
        return score;
    }
    
    // ===== INITIALISATION =====
    // Charger le profil au démarrage (silencieusement)
    setTimeout(async () => {
        try {
            await berru.profile.get();
            
            // Message d'accueil dans la console
            console.log(`
%c
╔══════════════════════════════════╗
║        BERRU-G CONSOLE           ║
╚══════════════════════════════════╝
%c
Commandes disponibles:
• berru.explore.summary()      - Voir mon profil
• berru.explore.projects()     - Mes projets
• berru.explore.projects('SAAS') - Projets par catégorie
• berru.explore.services()     - Mes services

// si await merde :

b.me()       // Voir le résumé
b.stats()    // Voir les stats
b.keys()     // Voir les clés disponibles
b.raw()      // Voir le JSON brut

Recherche étendue activée ✓
            `,
            'color: #8a6ff8; font-family: monospace;',
            'color: #4cc9f0; font-size: 14px;');
            
        } catch (error) {
            console.warn('Profil non chargé:', error.message);
        }
    }, 3000);
    
})();