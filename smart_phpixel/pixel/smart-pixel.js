// smart-pixel.js - À INTÉGRER DANS LE SITE
class SmartPixel {
    constructor() {
        this.endpoint = 'https://gael-berru.com/smart_phpixel/pixel.php';
        this.sessionId = this.getSessionId();
        this.trackPageView();
        this.trackClicks();
        this.trackScroll();
    }
    
    getSessionId() {
        let id = sessionStorage.getItem('sp_session');
        if (!id) {
            id = 'sess_' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('sp_session', id);
        }
        return id;
    }
    
    trackPageView() {
        const params = new URLSearchParams({
            source: 'website',
            session_id: this.sessionId,
            viewport: window.innerWidth + 'x' + window.innerHeight
        });
        
        new Image().src = this.endpoint + '?' + params;
    }
    
    trackClicks() {
        document.addEventListener('click', (e) => {
            const target = e.target;
            const clickData = JSON.stringify({
                element: target.tagName,
                id: target.id || '',
                class: target.className || '',
                text: target.textContent?.substring(0, 50) || '',
                href: target.href || '',
                x: e.clientX,
                y: e.clientY
            });
            
            const params = new URLSearchParams({
                source: 'website', 
                session_id: this.sessionId,
                click_data: clickData
            });
            
            new Image().src = this.endpoint + '?' + params;
        });
    }
    
    trackScroll() {
        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            if (Math.abs(lastScroll - window.scrollY) > 500) {
                lastScroll = window.scrollY;
                // Track scroll majeur
            }
        });
    }
}

// INIT AUTOMATIQUE
new SmartPixel();