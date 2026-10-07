// ============================================
// BERRU PROFILE API - v2 (console easter egg)
// 3 commandes : b.manifest() / b.skill() / b.git()
// ============================================

window.berru = window.berru || {};

berru.profile = {
    apiUrl: 'https://raw.githubusercontent.com/berru-g/berru-g/main/api/profil_4_ai.json',
    cacheKey: 'berru_profile_cache',
    cacheDuration: 24 * 60 * 60 * 1000,

    async loadFromAPI() {
        console.log('%c📡 Chargement du profil depuis GitHub...', 'color: #8a6ff8;');
        try {
            const response = await fetch(this.apiUrl);
            if (!response.ok) throw new Error(`Erreur HTTP ${response.status}`);
            const data = await response.json();
            localStorage.setItem(this.cacheKey, JSON.stringify({ data, timestamp: Date.now() }));
            console.log('%c✅ Profil chargé !', 'color: #10b981;');
            return data;
        } catch (error) {
            console.error('%c❌ Erreur :', 'color: #ef4444;', error.message);
            return null;
        }
    },

    async get(forceRefresh = false) {
        if (this.data && !forceRefresh) return this.data;

        if (!forceRefresh) {
            const cached = localStorage.getItem(this.cacheKey);
            if (cached) {
                try {
                    const { data, timestamp } = JSON.parse(cached);
                    if (Date.now() - timestamp < this.cacheDuration) {
                        console.log('%c💾 Données depuis le cache', 'color: #f59e0b;');
                        this.data = data;
                        return data;
                    }
                } catch (e) {
                    console.warn('Cache invalide, rechargement...');
                }
            }
        }

        const fresh = await this.loadFromAPI();
        if (fresh) this.data = fresh;
        return this.data;
    }
};

// ============================================
// 🎯 RACCOURCIS CONSOLE : window.b
// ============================================

window.b = {
    // 1️⃣ MANIFESTE — qui je suis, ce que je défends
    manifest: function () {
        berru.profile.get().then(p => {
            if (!p || !p.identite) return;
            const i = p.identite;
            console.log(`%c👤 ${i.pseudo} — ${i.titre}`,
                'color: #8a6ff8; font-size: 18px; font-weight: bold;');
            console.log(`%c"${i.manifeste}"`, 'color: #666; font-style: italic;');
            console.log('%c🧭 Principes :', 'color: #4361ee; font-weight: bold;');
            (i.principes || []).forEach((pr, idx) => console.log(`  ${idx + 1}. ${pr}`));
        }).catch(console.error);
    },

    // 2️⃣ SKILL — compétences par catégorie
    skill: function (categorie = '') {
        berru.profile.get().then(p => {
            if (!p || !p.competences) return;
            // Normalisation : tolère une clé mal formée ("front\nend")
            const comp = {};
            Object.keys(p.competences).forEach(k => {
                comp[k.replace(/\s+/g, '')] = p.competences[k];
            });

            if (categorie && comp[categorie]) {
                console.log(`%c🔧 ${categorie.toUpperCase()}`, 'color: #f72585; font-weight: bold;');
                console.log(comp[categorie].join('  •  '));
            } else {
                console.log('%c🛠️ Compétences', 'color: #f72585; font-weight: bold;');
                console.table(comp);
                console.log('%c💡 Usage: b.skill("backend")', 'color: #888;');
            }
        }).catch(console.error);
    },

    // 3️⃣ GIT — les repos publics (profil_4_ai.json → github.repos)
    git: function (search = '') {
        berru.profile.get().then(p => {
            if (!p || !p.github) return;
            let repos = p.github.repos || [];
            if (search) {
                const t = search.toLowerCase();
                repos = repos.filter(r =>
                    r.nom.toLowerCase().includes(t) ||
                    (r.description || '').toLowerCase().includes(t) ||
                    (r.langages || []).some(l => l.toLowerCase().includes(t))
                );
            }
            console.log(`%c📦 Repos GitHub (${p.github.username}) — ${repos.length}`, 'color: #ffd700; font-weight: bold;');
            repos.forEach(r => {
                console.log(`%c▸ ${r.nom}`, 'color: #8a6ff8; font-weight: bold;');
                if (r.description) console.log(`  ${r.description}`);
                if (r.langages) console.log(`  🏷️  ${r.langages.join(', ')}`);
                if (r.lien) console.log(`  🔗 ${r.lien}`);
                console.log('');
            });
            console.log('%c💡 Usage: b.git("php") pour filtrer', 'color: #888;');
        }).catch(console.error);
    }
};

// ============================================
// 🚀 BANNIÈRE D'ACCUEIL CONSOLE
// ============================================

setTimeout(async () => {
    try {
        await berru.profile.get();
        console.log(
`%c
╔══════════════════════════════════╗
║      berru-g · console API       ║
╚══════════════════════════════════╝
%cSalut, curieux 👋 Commandes disponibles :

%c b.manifest()      👤  Manifeste & principes
%c b.skill()         🛠️  Compétences (b.skill("backend"))
%c b.git()           📦  Repos GitHub (b.git("php"))
`,
            'color: #8a6ff8; font-family: monospace; font-weight: bold;',
            'color: #4cc9f0;',
            'color: #8a6ff8;',
            'color: #8a6ff8;',
            'color: #8a6ff8;'
        );
    } catch (error) {
        console.warn('API non chargée :', error.message);
    }
}, 2000);