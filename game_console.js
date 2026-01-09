// ===== EASTER EGGS CONSOLE =====
(function() {
    'use strict';
    
    // Attendre que la page soit chargée
    setTimeout(() => {
        // Message d'accueil ASCII
        console.log(`%c
◲══════════════════════════════◱
 ║            SALUT            ║
◳══════════════════════════════◰
`, 'color: #8a6ff8; font-family: monospace;');
        
        // Initialiser l'objet berru si pas déjà fait
        window.berru = window.berru || {};
        
        // Combinaison secrète pour débloquer plus
        let konami = [];
        const konamiCode = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 
                           'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 
                           'b', 'a'];
        
        document.addEventListener('keydown', (e) => {
            konami.push(e.key);
            if (konami.length > konamiCode.length) {
                konami.shift();
            }
            
            if (konami.join(',') === konamiCode.join(',')) {
                console.log('%c🎮 KONAMI CODE ACTIVÉ !', 
                    'color: #ff0000; font-size: 24px; font-weight: bold;');
                if (window.berru.egg) berru.egg();
                konami = []; // Reset
            }
        });
        
    }, 2000);
})();

console.log('Encodeur b64 integre ici : ');
console.log("tape berru.b64('ton message');")
berru.b64 = function(text, decode = false) {
    if (decode) {
        return atob(text);
    } else {
        return btoa(text);
    }
};

berru.color = function(hex) {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return { r, g, b, hsl: `hsl(${r}, ${g}%, ${b}%)` };
};

berru.now = function() {
    return {
        timestamp: Date.now(),
        iso: new Date().toISOString(),
        local: new Date().toLocaleString(),
        unix: Math.floor(Date.now() / 1000)
    };
};