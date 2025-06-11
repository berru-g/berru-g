document.addEventListener('DOMContentLoaded', function () {
    const navTabs = document.querySelectorAll('.nav-tab');
    const pageContainer = document.getElementById('pageContainer');

    // Gestion du clic sur les onglets
    navTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            // Animation de l'onglet
            navTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Animation de la page
            const target = this.getAttribute('data-target');
            const pages = ['home', 'services', 'gallery', 'reviews', 'contact'];
            const index = pages.indexOf(target);
            pageContainer.style.transform = `translateX(-${index * 20}%)`;
        });
    });

    // Simulation d'envoi du formulaire
    const contactForm = document.querySelector('.contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            e.preventDefault();
            alert('Message envoyé! Je vous répondrai dès que possible.');
            this.reset();
        });
    }
});

// gallery img
document.querySelectorAll('.gallery-img').forEach(img => {
    img.addEventListener('click', function() {
        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        lightbox.style.display = 'flex';
        lightboxImg.src = this.src;
    });
});

document.querySelector('.close-lightbox').addEventListener('click', function() {
    document.getElementById('lightbox').style.display = 'none';
});