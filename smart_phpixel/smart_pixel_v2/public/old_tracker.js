// public/tracker.js - Version SaaS CORRIGÉE
(function(window, document, script, trackerId) {
    window.SmartPixel = window.SmartPixel || function() {
        (window.SmartPixel.q = window.SmartPixel.q || []).push(arguments);
    };
    
    SmartPixel.load = function(trackingCode) {
        // Configuration AVEC BONNE URL
        this.config = {
            endpoint: 'http://localhost/smart_phpixel/smart_pixel_v2/public/pixel.php',
            trackingCode: trackingCode,
            sessionId: this.getOrCreateSessionId(),
            pageLoaded: false
        };
        
        this.trackPageView();
        this.trackClicks();
    };
    
    SmartPixel.getOrCreateSessionId = function() {
        let sessionId = sessionStorage.getItem('sp_session_id');
        if (!sessionId) {
            sessionId = 'sess_' + Math.random().toString(36).substr(2, 12);
            sessionStorage.setItem('sp_session_id', sessionId);
        }
        return sessionId;
    };
    
    // FONCTION POUR RÉCUPÉRER LES UTM
    SmartPixel.getUtmParam = function(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name) || '';
    };
    
    SmartPixel.trackPageView = function() {
        const params = new URLSearchParams({
            t: this.config.trackingCode,
            sid: this.config.sessionId,
            vp: window.innerWidth + 'x' + window.innerHeight,
            s: document.referrer ? new URL(document.referrer).hostname : 'direct', // CORRIGÉ : paramètre 's' pour 'source'
            utm_campaign: this.getUtmParam('utm_campaign') || '', // AJOUTÉ : pour la colonne 'campaign'
            ref: document.referrer || 'direct' // Gardé pour log
        });
        
        new Image().src = this.config.endpoint + '?' + params;
        this.config.pageLoaded = true;
    };
    
    SmartPixel.trackEvent = function(eventName, eventData = {}) {
        if (!this.config.pageLoaded) return;
        
        const params = new URLSearchParams({
            t: this.config.trackingCode,
            sid: this.config.sessionId,
            e: eventName,
            click: JSON.stringify(eventData) // CORRIGÉ : paramètre 'click' pour 'click_data'
        });
        
        new Image().src = this.config.endpoint + '?' + params;
    };
    
    SmartPixel.trackClicks = function() {
        document.addEventListener('click', (e) => {
            if (e.target.tagName === 'SCRIPT' || 
                e.target.tagName === 'LINK' || 
                e.target.closest('[data-sp-ignore]')) {
                return;
            }
            
            setTimeout(() => {
                this.trackEvent('click', {
                    tag: e.target.tagName,
                    id: e.target.id || '',
                    class: e.target.className || '',
                    text: e.target.textContent?.substr(0, 100) || '',
                    x: e.clientX,
                    y: e.clientY
                });
            }, 100);
        }, { passive: true });
    };
    
    document.addEventListener('DOMContentLoaded', function() {
        const scriptEl = document.querySelector('script[data-sp-id]');
        if (scriptEl) {
            const trackingCode = scriptEl.getAttribute('data-sp-id');
            SmartPixel.load(trackingCode);
        }
    });
    
})(window, document, 'script');