// public/tracker.js - Version finale harmonisée avec pixel.php multi-tenant
(function(window, document) {
    window.SmartPixel = window.SmartPixel || function() {
        (window.SmartPixel.q = window.SmartPixel.q || []).push(arguments);
    };

    SmartPixel.load = function(trackingCode) {
        this.config = {
            endpoint: 'https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/pixel.php', // ENDPOINT CENTRALISÉ
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

    // Récupération UTM
    SmartPixel.getUtmParam = function(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name) || '';
    };

    SmartPixel.trackPageView = function() {
        const params = new URLSearchParams({
            t: this.config.trackingCode,                  // tracking code
            sid: this.config.sessionId,                   // session
            viewport: window.innerWidth + 'x' + window.innerHeight, // viewport
            s: document.referrer ? new URL(document.referrer).hostname : 'direct', // source
            utm_campaign: this.getUtmParam('utm_campaign') || '', // campaign
            ref: document.referrer || 'direct'           // référence
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
            click: JSON.stringify(eventData)
        });

        new Image().src = this.config.endpoint + '?' + params;
    };

    SmartPixel.trackClicks = function() {
        document.addEventListener('click', (e) => {
            if (e.target.tagName === 'SCRIPT' || 
                e.target.tagName === 'LINK' || 
                e.target.closest('[data-sp-ignore]')) return;

            setTimeout(() => {
                this.trackEvent('click', {
                    tag: e.target.tagName,
                    id: e.target.id || '',
                    class: e.target.className || '',
                    text: e.target.textContent?.substr(0, 100) || '',
                    href: e.target.href || '',
                    x: e.clientX,
                    y: e.clientY
                });
            }, 100);
        }, { passive: true });
    };

    // Init auto à partir du script <script data-sp-id="TRACKING_CODE">
    document.addEventListener('DOMContentLoaded', function() {
        const scriptEl = document.querySelector('script[data-sp-id]');
        if (scriptEl) {
            const trackingCode = scriptEl.getAttribute('data-sp-id');
            SmartPixel.load(trackingCode);
        }
    });

})(window, document);
