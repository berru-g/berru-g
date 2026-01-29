// Fonction pour changer d'onglet
function openTab(tabName) {
    // Masquer tous les contenus d'onglets
    const tabContents = document.getElementsByClassName('tab-content');
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove('active');
    }

    // Désactiver tous les onglets
    const tabs = document.getElementsByClassName('tab');
    for (let i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('active');
    }

    // Activer l'onglet sélectionné
    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');
}

// Fonction pour changer la période
function changePeriod(period) {
    window.location.href = `?period=${period}`;
}

// Données pour les graphiques
const dailyStats = <?= json_encode($dailyStats) ?>;
const sources = <?= json_encode($sources) ?>;
const devices = <?= json_encode($devices) ?>;
const browsers = <?= json_encode($browsers) ?>;
const countries = <?= json_encode($countries) ?>;

// Configuration commune pour les petits graphiques
const smallChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        }
    }
};

// Graphique d'évolution du trafic
const trafficCtx = document.getElementById('trafficChart').getContext('2d');
const trafficChart = new Chart(trafficCtx, {
    type: 'line',
    data: {
        labels: dailyStats.map(stat => stat.date),
        datasets: [
            {
                label: 'Visites',
                data: dailyStats.map(stat => stat.visits),
                borderColor: '#9d86ff',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                tension: 0.3,
                fill: true
            },
            {
                label: 'Visiteurs uniques',
                data: dailyStats.map(stat => stat.unique_visitors),
                borderColor: '#4ecdc4',
                backgroundColor: 'rgba(76, 201, 240, 0.1)',
                tension: 0.3,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Graphique des sources (aperçu)
const sourcesCtx = document.getElementById('sourcesChart').getContext('2d');
const sourcesChart = new Chart(sourcesCtx, {
    type: 'doughnut',
    data: {
        labels: sources.map(s => s.source),
        datasets: [{
            data: sources.map(s => s.count),
            backgroundColor: [
                '#9d86ff', '#4ecdc4', '#ff6b8b', '#ffe66d', '#7ae582'
            ]
        }]
    },
    options: smallChartOptions
});

// Graphique des appareils (aperçu)
const devicesCtx = document.getElementById('devicesChart').getContext('2d');
const devicesChart = new Chart(devicesCtx, {
    type: 'pie',
    data: {
        labels: devices.map(d => d.device),
        datasets: [{
            data: devices.map(d => d.count),
            backgroundColor: ['#9d86ff', '#4ecdc4', '#ff6b8b']
        }]
    },
    options: smallChartOptions
});

// Graphique des pays (aperçu)
const countriesOverviewCtx = document.getElementById('countriesOverviewChart').getContext('2d');
const countriesOverviewChart = new Chart(countriesOverviewCtx, {
    type: 'bar',
    data: {
        labels: countries.map(c => c.country),
        datasets: [{
            label: 'Visites',
            data: countries.map(c => c.visits),
            backgroundColor: '#7ae582'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                beginAtZero: true
            }
        }
    }
});

// Graphique des sources (trafic)
const sourcesTrafficCtx = document.getElementById('sourcesTrafficChart').getContext('2d');
const sourcesTrafficChart = new Chart(sourcesTrafficCtx, {
    type: 'doughnut',
    data: {
        labels: sources.map(s => s.source),
        datasets: [{
            data: sources.map(s => s.count),
            backgroundColor: [
                '#9d86ff', '#4ecdc4', '#ff6b8b', '#ffe66d', '#7ae582'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            }
        }
    }
});

// Graphique des navigateurs
const browsersCtx = document.getElementById('browsersChart').getContext('2d');
const browsersChart = new Chart(browsersCtx, {
    type: 'bar',
    data: {
        labels: browsers.map(b => b.browser),
        datasets: [{
            label: 'Utilisations',
            data: browsers.map(b => b.count),
            backgroundColor: '#7ae582'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Graphique des pays
const countriesCtx = document.getElementById('countriesChart').getContext('2d');
const countriesChart = new Chart(countriesCtx, {
    type: 'bar',
    data: {
        labels: countries.map(c => c.country),
        datasets: [{
            label: 'Visites',
            data: countries.map(c => c.visits),
            backgroundColor: '#9d86ff'
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        scales: {
            x: {
                beginAtZero: true
            }
        }
    }
});

// Graphique des types d'appareils
const deviceTypesCtx = document.getElementById('deviceTypesChart').getContext('2d');
const deviceTypesChart = new Chart(deviceTypesCtx, {
    type: 'doughnut',
    data: {
        labels: devices.map(d => d.device),
        datasets: [{
            data: devices.map(d => d.count),
            backgroundColor: ['#9d86ff', '#4ecdc4', '#ff6b8b']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            }
        }
    }
});

// Graphique des types de navigateurs
const browserTypesCtx = document.getElementById('browserTypesChart').getContext('2d');
const browserTypesChart = new Chart(browserTypesCtx, {
    type: 'pie',
    data: {
        labels: browsers.map(b => b.browser),
        datasets: [{
            data: browsers.map(b => b.count),
            backgroundColor: [
                '#9d86ff', '#4ecdc4', '#ff6b8b', '#ffe66d', '#7ae582'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            }
        }
    }
});


// Gestion du toggle de la sidebar
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const sidebarOverlay = document.getElementById('sidebarOverlay');

function toggleSidebar() {
    sidebar.classList.toggle('collapsed');
    
    // Sauvegarder l'état dans un cookie (valide 30 jours)
    const isCollapsed = sidebar.classList.contains('collapsed');
    document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=${60*60*24*30}`;
    
    // Mettre à jour le contenu principal
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.style.marginLeft = isCollapsed ? '70px' : '280px';
    }
}

function toggleMobileMenu() {
    sidebar.classList.toggle('mobile-open');
}

function closeMobileMenu() {
    sidebar.classList.remove('mobile-open');
}

// Événements
if (sidebarToggle) {
    sidebarToggle.addEventListener('click', toggleSidebar);
}

if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', toggleMobileMenu);
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', closeMobileMenu);
}

// Fermer le menu mobile en cliquant sur un lien
document.querySelectorAll('.sidebar a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 1024) {
            closeMobileMenu();
        }
    });
});

// Fonction de copie du code
function copyCode() {
    const codeElement = document.querySelector('.integration-code');
    if (!codeElement) return;
    
    const textToCopy = codeElement.innerText;
    navigator.clipboard.writeText(textToCopy).then(() => {
        // Feedback visuel
        const copyBtn = document.querySelector('.copy-btn');
        if (copyBtn) {
            const originalHTML = copyBtn.innerHTML;
            copyBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
            copyBtn.style.color = 'var(--positive)';
            setTimeout(() => {
                copyBtn.innerHTML = originalHTML;
                copyBtn.style.color = '';
            }, 2000);
        }
    }).catch(err => {
        console.error('Erreur lors de la copie : ', err);
    });
}

// Fonction de déconnexion
function confirmLogout() {
    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        window.location.href = 'logout.php';
    }
}

// Fermer le menu mobile lors du redimensionnement
window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
        closeMobileMenu();
    }
});
