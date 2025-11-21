// tracking.js - Système de tracking avancé
class AdvancedTracker {
    constructor() {
        this.endpoint = 'https://gael-berru.com/treck/track.php';
        this.visitorId = this.getVisitorId();
        this.sessionId = this.generateSessionId();
        this.startTime = Date.now();
        this.init();
    }

    // Génère un ID visiteur unique
    getVisitorId() {
        let visitorId = localStorage.getItem('adv_visitor_id');
        if (!visitorId) {
            visitorId = 'vis_' + this.generateUUID();
            localStorage.setItem('adv_visitor_id', visitorId);
        }
        return visitorId;
    }

    // Génère un ID de session
    generateSessionId() {
        return 'sess_' + this.generateUUID();
    }

    // Génère un UUID v4
    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // Initialise le tracking
    init() {
        this.trackPageView();
        this.trackClicks();
        this.trackScroll();
        this.trackFormSubmissions();
        this.trackTimeSpent();
        this.trackBrowserEvents();
    }

    // Track la vue de page
    trackPageView() {
        const pageData = {
            type: 'pageview',
            visitor_id: this.visitorId,
            session_id: this.sessionId,
            page_url: window.location.href,
            page_title: document.title,
            referrer: document.referrer,
            timestamp: new Date().toISOString(),
            
            // Informations navigateur
            user_agent: navigator.userAgent,
            language: navigator.language,
            languages: navigator.languages ? navigator.languages.join(',') : '',
            cookie_enabled: navigator.cookieEnabled,
            
            // Informations écran
            screen_resolution: screen.width + 'x' + screen.height,
            viewport_size: window.innerWidth + 'x' + window.innerHeight,
            color_depth: screen.colorDepth,
            pixel_ratio: window.devicePixelRatio,
            
            // Informations techniques
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            online_status: navigator.onLine,
            java_enabled: navigator.javaEnabled ? navigator.javaEnabled() : false,
            
            // Performance
            connection_type: this.getConnectionType(),
            device_memory: navigator.deviceMemory || 'unknown',
            hardware_concurrency: navigator.hardwareConcurrency || 'unknown'
        };

        this.sendData(pageData);
    }

    // Track les clics
    trackClicks() {
        document.addEventListener('click', (e) => {
            const target = e.target;
            const clickData = {
                type: 'click',
                visitor_id: this.visitorId,
                session_id: this.sessionId,
                timestamp: new Date().toISOString(),
                
                // Informations sur l'élément cliqué
                target_tag: target.tagName,
                target_id: target.id || 'none',
                target_class: target.className || 'none',
                target_text: this.getTextContent(target).substring(0, 100),
                target_href: target.href || 'none',
                
                // Position du clic
                click_x: e.clientX,
                click_y: e.clientY,
                page_x: e.pageX,
                page_y: e.pageY,
                
                // Méta-données de l'événement
                alt_key: e.altKey,
                ctrl_key: e.ctrlKey,
                shift_key: e.shiftKey,
                meta_key: e.metaKey
            };

            this.sendData(clickData);
        }, { capture: true });
    }

    // Track le défilement
    trackScroll() {
        let lastScrollY = window.scrollY;
        let scrollTimeout;
        
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                const scrollData = {
                    type: 'scroll',
                    visitor_id: this.visitorId,
                    session_id: this.sessionId,
                    timestamp: new Date().toISOString(),
                    scroll_y: window.scrollY,
                    scroll_percentage: this.getScrollPercentage(),
                    document_height: document.documentElement.scrollHeight
                };
                
                if (Math.abs(lastScrollY - window.scrollY) > 100) {
                    this.sendData(scrollData);
                    lastScrollY = window.scrollY;
                }
            }, 500);
        });
    }

    // Track les soumissions de formulaire
    trackFormSubmissions() {
        document.addEventListener('submit', (e) => {
            const formData = {
                type: 'form_submit',
                visitor_id: this.visitorId,
                session_id: this.sessionId,
                timestamp: new Date().toISOString(),
                form_id: e.target.id || 'none',
                form_class: e.target.className || 'none',
                form_action: e.target.action || 'none',
                form_method: e.target.method || 'none',
                input_count: e.target.querySelectorAll('input, select, textarea').length
            };
            
            this.sendData(formData);
        });
    }

    // Track le temps passé
    trackTimeSpent() {
        window.addEventListener('beforeunload', () => {
            const timeSpent = Date.now() - this.startTime;
            const timeData = {
                type: 'time_spent',
                visitor_id: this.visitorId,
                session_id: this.sessionId,
                timestamp: new Date().toISOString(),
                time_spent_ms: timeSpent,
                time_spent_sec: Math.round(timeSpent / 1000)
            };
            
            // Utilise sendBeacon pour les données de fermeture
            navigator.sendBeacon(this.endpoint, new URLSearchParams(timeData));
        });
    }

    // Track les événements navigateur
    trackBrowserEvents() {
        // Changement de visibilité d'onglet
        document.addEventListener('visibilitychange', () => {
            const visibilityData = {
                type: 'visibility_change',
                visitor_id: this.visitorId,
                session_id: this.sessionId,
                timestamp: new Date().toISOString(),
                visibility_state: document.visibilityState
            };
            this.sendData(visibilityData);
        });

        // Redimensionnement de fenêtre
        window.addEventListener('resize', () => {
            const resizeData = {
                type: 'resize',
                visitor_id: this.visitorId,
                session_id: this.sessionId,
                timestamp: new Date().toISOString(),
                viewport_size: window.innerWidth + 'x' + window.innerHeight
            };
            this.sendData(resizeData);
        });
    }

    // Méthodes utilitaires
    getScrollPercentage() {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        return docHeight > 0 ? Math.round((scrollTop / docHeight) * 100) : 0;
    }

    getTextContent(element) {
        return element.textContent || element.innerText || '';
    }

    getConnectionType() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (connection) {
            return {
                effectiveType: connection.effectiveType,
                downlink: connection.downlink,
                rtt: connection.rtt,
                saveData: connection.saveData
            };
        }
        return 'unknown';
    }

    // Envoi des données via pixel
    sendData(data) {
        const params = new URLSearchParams();
        
        // Ajoute toutes les données aux paramètres
        Object.keys(data).forEach(key => {
            if (data[key] !== null && data[key] !== undefined) {
                params.append(key, data[key].toString());
            }
        });

        // Crée le pixel de tracking
        const pixel = new Image();
        pixel.src = `${this.endpoint}?${params.toString()}`;
        pixel.style.display = 'none';
        
        // Ajoute au DOM pour déclencher le chargement
        document.body.appendChild(pixel);
        setTimeout(() => {
            if (document.body.contains(pixel)) {
                document.body.removeChild(pixel);
            }
        }, 1000);
    }
}

// Démarre le tracking une fois le DOM chargé
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new AdvancedTracker();
    });
} else {
    new AdvancedTracker();
}