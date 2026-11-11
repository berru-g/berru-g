<?php

?>
<html lang="fr">
<!-- 
    ╔══════════════════════════════════════════════════╗
    ║     ██████╗ ███████╗██████╗ ██████╗ ██╗   ██╗    ║
    ║     ██╔══██╗██╔════╝██╔══██╗██╔══██╗██║   ██║    ║
    ║     ██████╔╝█████╗  ██████╔╝██████╔╝██║   ██║    ║
    ║     ██╔══██╗██╔══╝  ██╔══██╗██╔══██╗██║   ██║    ║
    ║     ██████╔╝███████╗██║  ██║██║  ██║╚██████╔╝    ║
    ║     ╚═════╝ ╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝ ╚═════╝     ║
    ╠══════════════════════════════════════════════════╣
    ║  Project      : berru-g CV                       ║
    ║  First commit : February 27, 2020                ║
    ║  Version      : 5.0.0                            ║
    ║  Copyright    : 2025 Berru                       ║
    ║  GitHub       : https://github.com/berru-g/      ║
    ╚══════════════════════════════════════════════════╝
-->

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gaël Berru — Développeur web PHP &amp; logiciel à Vertou (44)</title>
  <link rel="shortcut icon" href="/img/berru-pieuvre.png" />
  <link rel="apple-touch-icon" href="/img/berru-pieuvre.png" />
  <meta name="keywords"
    content="site web souverains vertou, developpeur web Vertou, Gael Leberruyer, Gael Berru, outils open source, Libre Analytics, web3, crypto enthusiast, enquete blockchain, open source, developpeur Vertou,">
  <meta name="author" content="Gael Berru.">
  <meta name="robots" content="index, follow">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Gaël Berru — Développeur web PHP &amp; logiciel à Vertou (44)">
  <meta property="og:description"
    content="Sites, API REST et logiciels sur mesure en PHP natif, sans framework. Open source et souveraineté numérique, depuis 2020.">
  <meta property="og:image" content="https://berru-g.github.io/berru-g/img/berru-pieuvre.png">
  <meta property="og:locale" content="fr_FR">
  <meta name="twitter:card" content="summary">
  <link rel="alternate" type="text/plain" href="https://gael-berru.com/llms.txt" title="llms.txt">
  <meta name="theme-color" content="#9d86ff">
  <meta property="og:url" content="https://gael-berru.com/">
  <link rel="canonical" href="https://gael-berru.com/">
  <link rel="stylesheet" href="./menu/menu.css">
  <link rel="stylesheet" href="./menu/dynamic-search.css">
  <link rel="stylesheet" href="./LibreAnalytics/RGPD/cookie.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/introjs.min.css">
  <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
  <link rel="stylesheet" href="./menu/galerie-shop.css">
  <script data-sp-id="SP_7f9505cc" src="https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/tracker.js"
    async></script>

  <link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml">
  <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Gaël Leberruyer (Berru-G)",
  "url": "https://gael-berru.com",
  "description": "Développeur web à Vertou : sites, API REST et logiciels sur mesure en PHP natif, sans framework.",
  "jobTitle": "Développeur web PHP fullstack",
  "image": "https://berru-g.github.io/berru-g/img/logo.png",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Vertou",
    "addressRegion": "Pays de la Loire",
    "postalCode": "44120",
    "addressCountry": "FR"
  },
  "knowsLanguage": "fr",
  "sameAs": [
    "https://github.com/berru-g",
    "https://codepen.io/h-lautre",
    "https://medium.com/@gael-berru",
    "https://www.instagram.com/berru_aka/",
    "https://www.hackster.io/gleberruyer",
    "https://www.tindie.com/stores/makeandplay/",
    "https://gael-berru.com/LibreAnalytics/"
  ],
  "skills": ["Développement web", "Développeur PHP", "API REST", "Analytics souverain"],
  "hasOccupation": {
    "@type": "Occupation",
    "name": "Développeur web et logiciel",
    "description": "Conception d'outils web, d'API REST et de logiciels sur mesure",
    "skills": ["PHP", "SQL", "API REST", "JavaScript", "PCB design", "Arduino", "Easy EDA", "Hardware", "Open source"]
  },
  "knowsAbout": [
    "Développement web",
    "PHP natif",
    "API REST",
    "Open source",
    "Web souverain",
    "Développement SAAS",
    "Création de circuits électroniques"
  ]
}
</script>

</head>

<body>
  <div class="app-container">

    <!-- SIDEBAR PROPRE -->
    <aside class="sidebar-clean" id="sidebar">

      <!-- Header -->
      <div class="sidebar-header">
        <!--<div class="sidebar-title"> Berru-g</div>-->

        <div class="search-box">
          <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input type="text" class="search-input" id="searchInput" placeholder="Rechercher ..." autocomplete="on">
          <kbd class="search-shortcut">/</kbd>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="nav-clean" id="navContainer">

        <!-- Section 1 -->
        <div class="nav-section">
          <div class="nav-section-title">Visitez</div>
          <a href="project-base-of-berru-g.php" class="nav-item" data-search="Base des projets de berru bdd">
            <span>La Base des projets</span>
          </a>
          <a href="#" target="_blank" class="nav-item active" id="popup"
            data-search="projets galerie portfolio réalisations 3D saas">
            <span>Le Site</span>
          </a>
          <a href="3D/index.html" target="_blank" class="nav-item"
            data-search="3D threejs three.js webgl game gamification score universe 3D">
            <span>La version 3D</span>
          </a>
          <a href="#shop" class="nav-item"
            data-search="shop vente pcb conception electronique achat stripe noel cadeaux création diy">
            <span>Le shop</span>
          </a>
        </div>

        <!-- Section 2 -->
        <div class="nav-section">
          <div class="nav-section-title">Mes travaux depuis 2020</div>

          <!-- SAAS -->
          <div class="nav-parent">
            <button class="nav-item" data-search="saas app tool software">
              <span>Software</span>
              <svg class="nav-toggle" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
            </button>
            <div class="submenu">
              <a href="https://gael-berru.com/LibreAnalytics/" target="_blank" class="nav-item"
                data-search="alternative souverain Google Analytics free Libre Analytics">
                <span>Libre Analytics</span>
              </a>
              <a href="https://berru-g.github.io/enquete/" target="_blank" class="nav-item"
                data-search="blockchain crypto bitcoin enquete forensic arnaque scam wallet">
                <span>Blockchain Explorer</span>
              </a>
              <a href="https://github.com/berru-g/3Dscrollanimator" target="_blank" class="nav-item"
                data-search="snippet css threejs systems système 3D saas gamification solana payement">
                <span>3D Scroll Animator</span>
              </a>
            </div>
          </div>

          <!-- HARDWARE -->
          <div class="nav-parent">
            <button class="nav-item" data-search="hardware arduino outils">
              <span>Hardware</span>
              <svg class="nav-toggle" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
            </button>
            <div class="submenu">
              <a href="https://www.hackster.io/gleberruyer" target="_blank" class="nav-item"
                data-search="electronique arduino pcb music daw midi usbc shield arduino lego">
                <span>Arduino project</span>
              </a>
              <a href="https://www.tindie.com/stores/makeandplay/" target="_blank" class="nav-item"
                data-search="electronic pcb arduino controller électronique">
                <span>PCB Design</span>
              </a>

            </div>
          </div>

          <!-- TOOLS -->
          <div class="nav-parent">
            <button class="nav-item" data-search="ui ux design interface utilisateur expérience prototype figma">
              <span>Tools</span>
              <svg class="nav-toggle" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
            </button>
            <div class="submenu">

              <a href="https://gael-berru.com/LibreAnalytics/doc/" target="_blank" class="nav-item"
                data-search="api rest libreanalytics open source php natif sans framework noia ">
                <span>API</span>
              </a>
              <a href="https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/cli.php" target="_blank"
                class="nav-item" data-search="pseudo cli api analyse data terminal cmd commande">
                <span>Pseudo CLI</span>
              </a>

            </div>
          </div>

          <!-- RESSOURCES -->
          <div class="nav-parent">
            <button class="nav-item" data-search="ressources enquete articles blockchain crypto btc">
              <span>Find me</span>
              <svg class="nav-toggle" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
            </button>

            <div class="submenu">

              <a href="https://medium.com/@gael-berru" target="_blank" class="nav-item"
                data-search="articles blog écrits publication blockchain crypto web3">
                <span><i class="fab fa-medium"></i> Medium</span>
              </a>
              <a href="https://codepen.io/h-lautre" target="_blank" class="nav-item"
                data-search="front-end responsive codepen animations">
                <span><i class="fab fa-codepen"></i> Codepen</span>
              </a>
              <a href="https://www.hackster.io/gleberruyer" target="_blank" class="nav-item"
                data-search="pcb éléctronique arduino hackster diy circuit daw midi usbc shield">
                <span><svg class="social-icon" viewBox="0 0 24 24">
                    <path
                      d="M11.9 12.9003C11.9 12.6003 12.1 12.4003 12.3 12.3003L15.1 11.5003C15.3 11.5003 15.5 11.5003 15.5 11.8003V12.8003C15.5 13.0003 15.3 13.2003 15.1 13.2003H12.3C12.1 13.2003 11.9 13.0003 11.9 12.8003V12.9003ZM23.4 15.4003H20.5999C20.3999 15.4003 20.2 15.2003 20.2 15.0003V10.6003C20.2 10.4003 20.3999 10.1003 20.5999 10.1003L23.4 9.30029C23.6 9.30029 23.7999 9.30029 23.7999 9.60029V15.0003C23.7999 15.2003 23.6 15.4003 23.4 15.4003ZM23.7999 23.0003C23.7999 23.3003 23.6 23.5003 23.4 23.6003L20.5999 24.4003C20.3999 24.4003 20.2 24.4003 20.2 24.1003V23.1003C20.2 22.9003 20.3999 22.7003 20.5999 22.7003H23.4C23.6 22.7003 23.7999 22.9003 23.7999 23.1003V23.0003ZM12.3 20.5003H15.1C15.3 20.5003 15.5 20.7003 15.5 20.9003V25.3003C15.5 25.5003 15.3 25.8003 15.1 25.8003L12.3 26.6003C12.1 26.6003 11.9 26.6003 11.9 26.3003V20.9003C11.9 20.7003 12.1 20.5003 12.3 20.5003ZM7.79993 16.1003C7.79993 16.1003 8.19993 16.0003 8.39993 15.9003L15.2 14.0003C15.4 14.0003 15.6 14.0003 15.6 14.3003V16.1003C15.6 16.3003 15.8 16.5003 16 16.5003H23.5C23.7 16.5003 23.9 16.7003 23.9 16.9003V18.9003C23.9 19.1003 24.0999 19.3003 24.2999 19.3003H27.9C28 19.3003 28.2 19.4003 28.2 19.6003C28.2 19.8003 28.2 19.7003 28.2 19.8003C28.2 19.8003 27.7999 19.9003 27.5999 20.0003C26.2999 20.4003 22.0999 21.5003 20.7999 21.9003C20.5999 21.9003 20.4 21.9003 20.4 21.6003V19.8003C20.4 19.6003 20.2 19.4003 20 19.4003H12.5C12.3 19.4003 12.1 19.2003 12.1 19.0003V16.9003C12.1 16.7003 11.9 16.5003 11.7 16.5003H8.09993C7.99993 16.5003 7.79993 16.4003 7.79993 16.2003C7.79993 16.0003 7.79993 16.1003 7.79993 16.0003V16.1003Z"
                      fill="var(--staticWhite)"></path>
                  </svg> Hackster</span>
              </a>
              <a href="https://github.com/berru-g/" target="_blank" class="nav-item"
                data-search="github code open source javascript python php sql">
                <span><i class="fab fa-github"></i> Github</span>
              </a>

            </div>
          </div>

        </div>

        <!--FORMULAIRE-->
        <div class="nav-section">
          <div class="nav-section-title">Contact</div>

          <a href="mailto:contact@gael-berru.com?subject=Salut Berru" target="_blank" class="nav-item"
            data-search="contact mail email formulaire">
            <span><i class="fas fa-envelope"></i> Par Mail ou ↷</span>
          </a>

          <div class="contact-form-inside"
            style="padding: 1rem; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 10px; margin: 0.5rem 0;">
            <form id="inlineContactForm" action="https://formspree.io/f/xqppgrnb" method="POST">
              <div class="form-group" style="margin-bottom: 1rem;">
                <input type="email" name="email"
                  style="width: 100%; padding: 0.625rem 0.75rem; background: var(--search-bg); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-color); font-size: 0.875rem;"
                  placeholder="Votre email" required>
              </div>
              <div class="form-group" style="margin-bottom: 1rem;">
                <textarea name="message"
                  style="width: 100%; min-height: 80px; padding: 0.625rem 0.75rem; background: var(--search-bg); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-color); font-size: 0.875rem; resize: vertical;"
                  placeholder="Votre message..." required></textarea>
              </div>
              <button type="submit"
                style="width: 100%; padding: 0.625rem; background: var(--primary-color); color: white; border: none; border-radius: 6px; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer; transition: opacity 0.2s;">
                <span>Envoyer</span>

                <i class="fa-regular fa-paper-plane"></i>
              </button>
            </form>
            <div id="formMessage"
              style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-secondary); text-align: center;"></div>
          </div>
        </div>

      </nav>

      <!-- Footer -->

      <div class="sidebar-footer">


        <button class="footer-btn" id="settingsBtn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </button>

      </div>

    </aside>

    <!-- Bouton toggle -->
    <button class="toggle-clean" id="toggleBtn" aria-label="Ouvrir le menu">

      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <rect x="2" y="2" width="20" height="20" rx="2" stroke-linejoin="round" />
        <path stroke-linecap="round" d="M6.8 2V22" />
      </svg>
      <!--<img src="./img/wamcache.png">-->
    </button>

    <!-- Main content -->
    <main class="main-content">
      <!-- Section main avec description SEO -->

      <div id="galerie-section">
        <div class="shell">

          <!-- ===== EN-TÊTE ===== -->
          <section class="galerie-report">
            <div>
              <h1 class="galerie-title" data-aos="fade-right" data-aos-delay="100">Gael<br><span
                  class="stroke">Berru</span></h1>
              <p class="galerie-meta" data-aos="fade-right" data-aos-delay="200">Développeur web PHP &amp; logiciel —
                Vertou — since 2020
              </p>
              <p class="galerie-meta" data-aos="fade-right" data-aos-delay="300">Je conçois des <strong>sites
                  web</strong>,
                des <strong>API REST</strong> et des <strong>logiciels sur mesure</strong> en <strong>PHP
                  natif</strong>, sans framework. Hardware &amp; software, for the fun.</p>
            </div>
            <div class="galerie-hint" data-aos="fade-left" data-aos-delay="200">
              <i class="fas fa-terminal"></i>
              <span>Tous mes projets sont <strong>open source</strong>. Si un de
                mes outils vous est utile et que vous souhaitez <strong>faire un don</strong>, faites-le plutôt aux <a
                  href="https://dons.restosducoeur.org/faire-un-don/~mon-don/" target="_blank"><strong>Restos du
                    coeur</strong></a>. Pour toute <strong>commande sur mesure, contactez-moi</strong> via le
                formulaire. Merci.</span>
            </div>
          </section>

          <!-- ===== GRILLE ===== -->
          <div class="galerie-grid">

            <!-- Projet 1: LibreAnalytics -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('./LibreAnalytics/smart_pixel_v2/assets/img/LibreAnalytics-horizontal.png', 'LibreAnalytics', 'Alternative libre et souveraine à Google Analytics. Développée en PHP natif et JavaScript vanilla, sans framework. Solution de tracking éthique, respectueuse de la vie privée, avec tableau de bord complet et API REST.')">
              <div class="galerie-image-container">
                <img src="./LibreAnalytics/smart_pixel_v2/assets/img/LibreAnalytics-horizontal.png"
                  alt="LibreAnalytics - Alternative open-source à Google Analytics" loading="lazy">
                <span class="galerie-index">01</span>
              </div>
              <div class="galerie-caption">
                <h4>LibreAnalytics</h4>
                <p>Alternative <strong>libre et souveraine</strong> à Google Analytics. Développée en <strong>PHP
                    natif</strong> sans framework. <a href="https://gael-berru.com/LibreAnalytics/" target="_blank"
                    rel="noopener noreferrer">Reprendre le pouvoir de ses données →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#Analytics</span>
                  <span class="tag">#PHP</span>
                  <span class="tag">#Privacy</span>
                </div>
              </div>
            </div>

            <!-- Projet 2: Suivi Blockchain -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('./img/bitcoin-explorer.png', 'Suivi Blockchain', 'Outil d\'analyse de transactions blockchain pour traquer les wallets d\'arnaqueurs. Visualisation des flux de cryptomonnaies et investigation de fraudes.')">
              <div class="galerie-image-container">
                <img src="./img/bitcoin-explorer.png"
                  alt="Suivi blockchain - Analyse de transactions Bitcoin et cryptomonnaies" loading="lazy">
                <span class="galerie-index">02</span>
              </div>
              <div class="galerie-caption">
                <h4>Suivi Blockchain</h4>
                <p>Outil de<strong>visualisation graphique de transactions blockchain</strong> pour traquer les wallets
                  d'arnaqueurs. <a href="https://github.com/berru-g/crypto-tool/blob/main/scam-radar/enquete/readme.md"
                    target="_blank" rel="noopener noreferrer">Visualiser la blockchain →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#Blockchain</span>
                  <span class="tag">#Bitcoin</span>
                  <span class="tag">#Investigation</span>
                </div>
              </div>
            </div>

            <!-- Projet 03: Make & Play USB C -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('./img/make&play.jpg', 'Make & Play USB C', 'Conception du circuit électronique et du PCB d\'un microcontrôleur USB MIDI low-cost. Plug & play sur tout logiciel DAW comme Ableton, Arena ou tout autre logiciel de création musicale ou vidéo.')">
              <div class="galerie-image-container">
                <img src="./img/make&play.jpg" alt="Make & Play USB C - Contrôleur MIDI Arduino plug & play pour DAW"
                  loading="lazy">
                <span class="galerie-index">03</span>
              </div>
              <div class="galerie-caption">
                <h4>Make &amp; Play USB C</h4>
                <p><strong><span style="color:#ab9ff2">Dispo sur commande</span></strong>. Conception du circuit et PCB
                  d'un <strong>microcontrôleur USB MIDI low-cost</strong>, plug &amp; play
                  sur tout <strong>DAW</strong> (Ableton, Arena...). <a href="https://www.hackster.io/gleberruyer"
                    target="_blank" rel="noopener noreferrer">Voir sur
                    Hackster →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#Arduino</span>
                  <span class="tag">#PCB</span>
                  <span class="tag">#MIDI</span>
                </div>
              </div>
            </div>

            <!-- Projet 04: Ornithopter -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('./img/ornithopter.gif', 'Ornithopter', 'Ornithoptère mécanique en cours de conception. Projet DIY combinant mécanique, électronique et aérodynamique pour créer un engin volant inspiré de la nature.')">
              <div class="galerie-image-container">
                <img src="./img/ornithopter.gif" alt="Ornithopter - Ornithoptère mécanique DIY en développement"
                  loading="lazy">
                <span class="galerie-index">04</span>
              </div>
              <div class="galerie-caption">
                <h4>Ornithopter</h4>
                <p><strong>Ornithoptère mécanique</strong> en cours de conception. Projet DIY combinant mécanique et
                  électronique.</p>
                <div class="galerie-tags">
                  <span class="tag">#DIY</span>
                  <span class="tag">#Mécanique</span>
                  <span class="tag">#Électronique</span>
                </div>
              </div>
            </div>

            <!-- Projet 05: Dashboard LibreAnalytics -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('./img/dashboard-betaV2.png', 'Dashboard LibreAnalytics', 'Dashboard personnel V2 regroupant l\'analytics de mon logiciel LibreAnalytics et de mon site, mes wallets crypto (SOL, SUI, AMI, USDC) via l\'API CoinGecko, une chart TradingView avec sentiment du jour, mon agenda Google Calendar, la météo et les infos utiles du quotidien. Envoi automatique de rapports clients et de mails de prospection par cron. Développé en PHP sans framework depuis la V0.1 en 2020.')">
              <div class="galerie-image-container">
                <img src="./img/dashboard-betaV2.png" alt="Dashboard analytics, crypto et agenda en PHP" loading="lazy">
                <span class="galerie-index">05</span>
              </div>
              <div class="galerie-caption">
                <h4>Dashboard LibreAnalytics</h4>
                <p><strong><span style="color:#ab9ff2">Dispo sur commande</span></strong>. <strong>Analytics, crypto &
                    automatisation</strong> réunis sur un seul écran. PHP sans framework,
                  depuis
                  2020.</p>
                <div class="galerie-tags">
                  <span class="tag">#PHP</span>
                  <span class="tag">#API</span>
                  <span class="tag">#Analytics</span>
                </div>
              </div>
            </div>


            <!-- Projet 05: RC Plane -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('https://github.com/berru-g/rc-plane/raw/main/img/pcb-final.png', 'RC Plane', 'Système radio commandé complet DIY pour avion, bateau ou voiture RC. Conception de A à Z : schémas électroniques, PCB, code embarqué et télécommande.')">
              <div class="galerie-image-container">
                <img src="https://github.com/berru-g/rc-plane/raw/main/img/pcb-final.png"
                  alt="RC Plane - Système radio commandé DIY pour avion bateau voiture" loading="lazy">
                <span class="galerie-index">05</span>
              </div>
              <div class="galerie-caption">
                <h4>RC Plane</h4>
                <p><strong><span style="color:#ab9ff2">Dispo sur commande</span></strong>. Conception d'un
                  <strong>système radio commandé DIY</strong>, émetteur et récepteur de A à Z, pour
                  avion, bateau ou voiture.
                  <strong>Plan électronique, Gerber et code</strong> ➡️ <a href="https://github.com/berru-g/rc-plane"
                    target="_blank" rel="noopener noreferrer">Voir sur GitHub →</a>
                </p>
                <div class="galerie-tags">
                  <span class="tag">#RC</span>
                  <span class="tag">#Arduino</span>
                  <span class="tag">#Électronique</span>
                </div>
              </div>
            </div>

            <!-- Projet 06: Dragonfly -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('./img/pcb-in-progress.png', 'Dragonfly', 'Conception d\'une libellule décorative interactive avec Arduino. Projet DIY alliant esthétique et électronique : PCB personnalisé, LED et code open-source.')">
              <div class="galerie-image-container">
                <img src="./img/pcb-in-progress.png"
                  alt="Dragonfly - Libellule décorative Arduino DIY avec PCB personnalisé" loading="lazy">
                <span class="galerie-index">06</span>
              </div>
              <div class="galerie-caption">
                <h4>Dragonfly</h4>
                <p><strong><span style="color:#ab9ff2">Dispo sur commande</span></strong>. Conception d'une
                  <strong>libellule décorative + Arduino</strong>. <strong>Plan électronique, Gerber
                    et code</strong> ➡️ <a href="https://github.com/berru-g/Dragonfly" target="_blank"
                    rel="noopener noreferrer">Voir sur GitHub →</a>
                </p>
                <div class="galerie-tags">
                  <span class="tag">#Arduino</span>
                  <span class="tag">#DIY</span>
                  <span class="tag">#Décoration</span>
                </div>
              </div>
            </div>

            <!-- Projet 07: NFC Badge (LE PLUS IMPORTANT POUR LE SEO) -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('https://github.com/berru-g/NFC_Badge/raw/main/img/PCB-scarabe-3D.png', 'NFC Badge', 'Badge NFC reprogrammable open-source. Basé sur la puce NT3H2111 (1024 bytes EEPROM). Sans batterie, compatible smartphones. Idéal pour makers, événements, éducation. Schémas, BOM et fichiers Gerber fournis.')">
              <div class="galerie-image-container">
                <img src="https://github.com/berru-g/NFC_Badge/raw/main/img/Scarabe-nfc-broche.png"
                  alt="NFC Badge - Badge NFC reprogrammable open-source DIY avec puce NT3H2111" loading="lazy">
                <span class="galerie-index">07</span>
              </div>
              <div class="galerie-caption">
                <h4>NFC Badge</h4>
                <p><strong><span style="color:#ab9ff2">Dispo sur commande</span></strong>. <strong>Badge NFC
                    reprogrammable open-source</strong>. Basé sur la puce <strong>NT3H2111</strong>.
                  <strong>Plan électronique, Gerber et BOM</strong> ➡️ <a href="https://github.com/berru-g/NFC_Badge"
                    target="_blank" rel="noopener noreferrer">Voir sur GitHub →</a>
                </p>
                <div class="galerie-tags">
                  <span class="tag">#NFC</span>
                  <span class="tag">#DIY</span>
                  <span class="tag">#NT3H2111</span>
                  <span class="tag">#OpenSource</span>
                </div>
              </div>
            </div>

            <!-- Projet 08: Lego/arduino for kids -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('https://github.com/berru-g/NFC_Badge/raw/main/img/PCB-scarabe-3D.png', 'NFC Badge', 'Badge NFC reprogrammable open-source. Basé sur la puce NT3H2111 (1024 bytes EEPROM). Sans batterie, compatible smartphones. Idéal pour makers, événements, éducation. Schémas, BOM et fichiers Gerber fournis.')">
              <div class="galerie-image-container">
                <img src="./img/shield-arduino-uno-lego.jpg"
                  alt="Shield Arduino Uno & Lego - Shield éducatif pour vos projets robotique, daw et pour laisser les kids exprimer leur créativité"
                  loading="lazy">
                <span class="galerie-index">08</span>
              </div>
              <div class="galerie-caption">
                <h4>Shield Arduino Uno & Lego</h4>
                <p><strong><span style="color:#ab9ff2">Dispo sur commande</span></strong>. Shield Arduino uno adaptable
                  au format Lego. Shield éducatif pour vos projets robotique, daw et pour
                  laisser les kids exprimer leur créativité.
                  Plan électronique, Gerber et BOM</strong> ➡️ <a
                    href="https://www.hackster.io/gleberruyer/shield-arduino-uno-lego-bc2029" target="_blank"
                    rel="noopener noreferrer">A quoi ça sert ? →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#2020</span>
                  <span class="tag">#DIY</span>
                  <span class="tag">#shield</span>
                  <span class="tag">#arduino-uno</span>
                </div>
              </div>
            </div>

            <!-- Projet 0097: Berru Clothing -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('./img/berru-clothing-2017.png', 'Berru Clothing', 'Création d\'une marque de vêtements et de son site e-commerce de 2016 à 2018. De la conception de la marque au développement complet de la boutique en ligne.')">
              <div class="galerie-image-container">
                <img src="./img/berru-clothing-2017.png"
                  alt="Berru Clothing - Marque de vêtements et site e-commerce 2016-2018" loading="lazy">
                <span class="galerie-index">09</span>
              </div>
              <div class="galerie-caption">
                <h4>Berru Clothing</h4>
                <p>Création d'une <strong>marque de vêtements</strong> et de son <strong>site e-commerce</strong>, de
                  2016 à 2018. <a href="https://berru-clothing.com" target="_blank" rel="noopener noreferrer">Voir la
                    marque →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#E-commerce</span>
                  <span class="tag">#Marque</span>
                  <span class="tag">#2016</span>
                </div>
              </div>
            </div>

            <!-- Projet 10: Les Flaneries -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('./img/flanerie22.gif', 'Les Flaneries', 'Création d\'une guinguette open air au parc de la Gaudinière à Nantes, de 2018 à 2022. Concerts, DJ sets et organisation d\'événements associatifs.')">
              <div class="galerie-image-container">
                <img src="./img/flanerie22.gif"
                  alt="Les Flaneries - Guinguette open air au parc de la Gaudinière à Nantes" loading="lazy">
                <span class="galerie-index">10</span>
              </div>
              <div class="galerie-caption">
                <h4>Les Flaneries</h4>
                <p>Création d'une <strong>guinguette open air</strong> au parc de la Gaudinière à Nantes, de 2018 à
                  2022. Concerts et DJ sets. <a href="https://berru-g.github.io/assoberru/" target="_blank"
                    rel="noopener noreferrer">Voir l\'asso →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#Guinguette</span>
                  <span class="tag">#Concert</span>
                  <span class="tag">#Nantes</span>
                </div>
              </div>
            </div>

            <!-- Projet 11: 3D Scroll Animator -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('./img/tutoexpress.gif', '3D Scroll Animator', 'SAAS de création d\'animations 3D synchronisées au scroll de votre site en 2 minutes. Interface drag & drop avec génération automatique des scripts à intégrer. Paiement Euro/Solana.')">
              <div class="galerie-image-container">
                <img src="./img/tutoexpress.gif" alt="3D Scroll Animator - SAAS d'animations 3D synchronisées au scroll"
                  loading="lazy">
                <span class="galerie-index">11</span>
              </div>
              <div class="galerie-caption">
                <h4>3D Scroll Animator</h4>
                <p><strong>SAAS <span style="color:#ff86e9">obsolète depuis l'IA</span></strong> — créez des
                  <strong>animations 3D synchronisées au scroll</strong> en 2 minutes.
                  Génération automatique des scripts à intégrer. <a href="https://3dscrollanimator.com" target="_blank"
                    rel="noopener noreferrer">Essayer le SAAS →</a>
                </p>
                <div class="galerie-tags">
                  <span class="tag">#SAAS</span>
                  <span class="tag">#WebGL</span>
                  <span class="tag">#Threejs</span>
                </div>
              </div>
            </div>

            <!-- Projet 12: Animation Stop Motion -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('./img/jeanjean2024.mp4', 'Animation Stop Motion', 'Création d\'animations stop motion avec une interface simple et intuitive.')">
              <div class="galerie-image-container">
                <img src="./img/jeanjean2024.mp4" alt="Animation stop motion - Création de vidéos image par image"
                  loading="lazy">
                <span class="galerie-index">12</span>
              </div>
              <div class="galerie-caption">
                <h4>Animation Stop Motion</h4>
                <p>Création d'<strong>animations stop motion</strong> avec une interface simple et intuitive. <a
                    href="https://youtube.com/shorts/XRlEr9VIdyc?si=SYAYvrhyyUXAokOh" target="_blank"
                    rel="noopener noreferrer">Voir l\'animation →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#Animation</span>
                  <span class="tag">#StopMotion</span>
                  <span class="tag">#Vidéo</span>
                </div>
              </div>
            </div>

            <!-- Projet 11: SQL Editor to Map -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('./img/sql-editor.png', 'SQL Editor to Map', 'SAAS de partage et visualisation graphique de fichiers CSV, Excel, JSON avec éditeur SQL to Map et cartographie automatique.')">
              <div class="galerie-image-container">
                <img src="./img/sql-editor.png"
                  alt="SQL Editor to Map - Visualisation de données SQL, CSV, Excel et JSON" loading="lazy">
                <span class="galerie-index">13</span>
              </div>
              <div class="galerie-caption">
                <h4>SQL Editor to Map</h4>
                <p><strong>SAAS <span style="color:#ff86e9">obsolète depuis l'IA</span></strong> — partage et
                  <strong>visualisation graphique</strong> de fichiers CSV, Excel,
                  JSON avec éditeur SQL to Map. <a href="https://agora-dataviz.com" target="_blank"
                    rel="noopener noreferrer">Explorer vos données →</a>
                </p>
                <div class="galerie-tags">
                  <span class="tag">#SAAS</span>
                  <span class="tag">#DataViz</span>
                  <span class="tag">#SQL</span>
                </div>
              </div>
            </div>

            <!-- Projet 12: Agora Dataviz -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('./img/agora.png', 'Agora Dataviz', 'Réseau social et plateforme de partage et visualisation graphique de fichiers CSV, Excel, JSON avec éditeur SQL to Map. Messagerie incluse.')">
              <div class="galerie-image-container">
                <img src="./img/agora.png" alt="Agora Dataviz - Réseau social et plateforme de visualisation de données"
                  loading="lazy">
                <span class="galerie-index">14</span>
              </div>
              <div class="galerie-caption">
                <h4>Agora Dataviz</h4>
                <p><span style="color:#ff86e9">obsolète depuis l'IA</span><strong> Réseau social</strong> et plateforme
                  de
                  <strong>visualisation de données</strong> SQL/JSON
                  avec messagerie. <a href="https://agora-dataviz.com" target="_blank"
                    rel="noopener noreferrer">Rejoindre Agora →</a>
                </p>
                <div class="galerie-tags">
                  <span class="tag">#SAAS</span>
                  <span class="tag">#RéseauSocial</span>
                  <span class="tag">#DataViz</span>
                </div>
              </div>
            </div>

            <!-- Projet 13: Blockchain Explorer -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('./img/V2.png', 'Blockchain Explorer', 'Outil d\'investigation blockchain : suivi de transactions Bitcoin, identification d\'exchanges et de plateformes KYC, création de diagrammes automatiques.')">
              <div class="galerie-image-container">
                <img src="./img/V2.png" alt="Blockchain Explorer - Suivi de transactions Bitcoin et identification KYC"
                  loading="lazy">
                <span class="galerie-index">15</span>
              </div>
              <div class="galerie-caption">
                <h4>Blockchain Explorer</h4>
                <p>Outil d'<strong>investigation blockchain</strong> : suivi de transactions Bitcoin, identification
                  d'exchanges et <strong>diagrammes automatiques</strong>. <a
                    href="https://crypto-free-tools.netlify.app/scam-radar?utm_source=monsite/" target="_blank"
                    rel="noopener noreferrer">Traquer les transactions →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#Blockchain</span>
                  <span class="tag">#BTC</span>
                  <span class="tag">#Investigation</span>
                </div>
              </div>
            </div>

            <!-- Projet 14: Interface Admin -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('./img/admin2.png', 'Interface Admin', 'Dashboard de gestion de bases de données. Accédez à toutes vos bases dans un dashboard unique avec fonctionnalités développées sur mesure : multi-bases, interface personnalisable, export des données.')">
              <div class="galerie-image-container">
                <img src="./img/admin2.png" alt="Interface Admin - Dashboard de gestion de bases de données sur mesure"
                  loading="lazy">
                <span class="galerie-index">16</span>
              </div>
              <div class="galerie-caption">
                <h4>Interface Admin</h4>
                <p><strong><span style="color:#ab9ff2">Dispo sur commande</span></strong>. <strong>Dashboard de
                    gestion</strong> de bases de données. Toutes vos bases dans un dashboard unique
                  avec fonctionnalités <strong>développées sur mesure</strong>. <a href="./board/login.php">Ouvrir le
                    dashboard →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#Admin</span>
                  <span class="tag">#Database</span>
                  <span class="tag">#PHP</span>
                </div>
              </div>
            </div>

            <!-- Projet 15: Golden Dessert CMS -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('./img/devgoldendessert.png', 'Golden Dessert CMS', 'Site vitrine avec CMS pour chef pâtissier : formulaire de contact, galerie photo CMS et envoi automatique de mails. Design responsive.')">
              <div class="galerie-image-container">
                <img src="./img/devgoldendessert.png" alt="Golden Dessert - Site vitrine avec CMS pour chef pâtissier"
                  loading="lazy">
                <span class="galerie-index">17</span>
              </div>
              <div class="galerie-caption">
                <h4>Golden Dessert CMS</h4>
                <p>Site vitrine avec <strong>CMS</strong> pour chef pâtissier : formulaire, <strong>galerie photo
                    dynamique</strong> et envoi automatique de mails. <a
                    href="https://goldendessert.fr?utm_source=monsite" target="_blank" rel="noopener noreferrer">Voir le
                    site →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#CMS</span>
                  <span class="tag">#Vitrine</span>
                  <span class="tag">#PHP</span>
                </div>
              </div>
            </div>

            <!-- Projet 16: Crypto Free Tools -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('./img/crypto-tool.gif', 'Crypto Free Tools', 'Suite d\'outils gratuits pour traders crypto : analyse de marché et trading via les API TradingView, CoinGecko et Binance.')">
              <div class="galerie-image-container">
                <img src="./img/crypto-tool.gif" alt="Crypto Free Tools - Suite d'outils gratuits pour traders crypto"
                  loading="lazy">
                <span class="galerie-index">18</span>
              </div>
              <div class="galerie-caption">
                <h4>Crypto Free Tools</h4>
                <p><strong>Suite d'outils gratuits</strong> pour traders : analyse de marché et trading via les API
                  TradingView, CoinGecko et Binance. <a href="https://crypto-free-tools.netlify.app/?utm_source=monsite"
                    target="_blank" rel="noopener noreferrer">Trader gratuitement →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#Crypto</span>
                  <span class="tag">#Trading</span>
                  <span class="tag">#API</span>
                </div>
              </div>
            </div>

            <!-- Projet 17: Local Network Chat -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('./img/smbchat.png', 'Local Network Chat', 'Communication chiffrée en réseau local via le protocole SMB. Messages chiffrés AES-128 + HMAC-SHA256, transfert de fichiers, sans serveur externe.')">
              <div class="galerie-image-container">
                <img src="./img/smbchat.png" alt="Local Network Chat - Chat chiffré AES-128 en réseau local via SMB"
                  loading="lazy">
                <span class="galerie-index">18</span>
              </div>
              <div class="galerie-caption">
                <h4>Local Network Chat</h4>
                <p><strong>Chat chiffré en réseau local</strong> via le protocole SMB. <strong>AES-128 +
                    HMAC-SHA256</strong>, sans serveur externe. <a
                    href="https://berru-g.github.io/OTTO/SMBchat/SMBchatV2/" target="_blank"
                    rel="noopener noreferrer">Discuter en local →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#Security</span>
                  <span class="tag">#SMB</span>
                  <span class="tag">#Chiffrement</span>
                </div>
              </div>
            </div>

            <!-- Projet 18: Heat Map 3D -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('./img/heatmap-forest.gif', 'Heat Map 3D', 'Explorez les volumes comme jamais : la carte thermique des capitalisations crypto sous forme de forêt 3D. Données API CoinGecko, rendu Three.js/WebGL.')">
              <div class="galerie-image-container">
                <img src="./img/heatmap-forest.gif"
                  alt="Heat Map 3D - Carte thermique des capitalisations crypto en forêt 3D" loading="lazy">
                <span class="galerie-index">18</span>
              </div>
              <div class="galerie-caption">
                <h4>Heat Map 3D</h4>
                <p>Explorez les volumes comme jamais : la <strong>carte thermique des capitalisations crypto</strong>
                  sous forme de <strong>forêt 3D</strong>. <a
                    href="https://crypto-free-tools.netlify.app/heatmap-forest/" target="_blank"
                    rel="noopener noreferrer">Explorer la forêt →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#3D</span>
                  <span class="tag">#Crypto</span>
                  <span class="tag">#Threejs</span>
                </div>
              </div>
            </div>

            <!-- Projet 19: Guide Crypto Débutant -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('./img/guide.png', 'Guide Crypto Débutant', 'Pas pour devenir riche mais pour apprendre : transactions sécurisées, wallet décentralisé sans KYC, stacking sur le web3 et récupération de vos fonds.')">
              <div class="galerie-image-container">
                <img src="./img/guide.png"
                  alt="Guide Crypto Débutant - Transactions sécurisées, wallet sans KYC, stacking Solana"
                  loading="lazy">
                <span class="galerie-index">19</span>
              </div>
              <div class="galerie-caption">
                <h4>Guide Crypto Débutant</h4>
                <p><strong>Guide crypto débutant</strong> : transactions sécurisées, wallet décentralisé <strong>sans
                    KYC</strong> et stacking Solana. <a
                    href="https://crypto-free-tools.netlify.app/guide-pour-debutants/" target="_blank"
                    rel="noopener noreferrer">Apprendre sans se faire plumer →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#Guide</span>
                  <span class="tag">#Crypto</span>
                  <span class="tag">#Solana</span>
                </div>
              </div>
            </div>

            <!-- Projet 20: Immersion 3D -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('./img/berru3D.gif', 'Immersion 3D', 'Visitez mon site en immersion : plongez dans un univers avec des missions et un système de gamification pour récupérer le trésor caché.')">
              <div class="galerie-image-container">
                <img src="./img/berru3D.gif" alt="Immersion 3D - Visite du site en jeu 3D avec missions et gamification"
                  loading="lazy">
                <span class="galerie-index">20</span>
              </div>
              <div class="galerie-caption">
                <h4>Immersion 3D</h4>
                <p>Visitez mon site en <strong>immersion</strong> : un univers avec missions et <strong>système de
                    gamification</strong> pour récupérer le trésor caché. <a href="./3D/">Entrer dans l\'univers
                    →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#3D</span>
                  <span class="tag">#Jeu</span>
                  <span class="tag">#WebGL</span>
                </div>
              </div>
            </div>

            <!-- Projet 21: Help Desk -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('./img/helpdesk.png', 'Help Desk', 'Terminal de surveillance système HELP DESK avec scan réseau style NMAP. Outil Python en ligne de commande.')">
              <div class="galerie-image-container">
                <img src="./img/helpdesk.png"
                  alt="Help Desk - Terminal de surveillance système avec scan réseau style NMAP" loading="lazy">
                <span class="galerie-index">21</span>
              </div>
              <div class="galerie-caption">
                <h4>Help Desk</h4>
                <p><strong>Terminal de surveillance système</strong> HELP DESK avec <strong>scan réseau style
                    NMAP</strong>. <a href="https://github.com/berru-g/OTTO/tree/main/secu" target="_blank"
                    rel="noopener noreferrer">Voir sur GitHub →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#Python</span>
                  <span class="tag">#Terminal</span>
                  <span class="tag">#Scan</span>
                </div>
              </div>
            </div>

            <!-- Projet 22: Help Desk Basic JS (image à remplacer : '#' dans la base) -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('./img/helpdesk.png', 'Help Desk Basic', 'Accédez aux informations basiques de votre machine via un pseudo terminal. 2021.')">
              <div class="galerie-image-container">
                <img src="./img/helpdesk.png" alt="Help Desk Basic - Pseudo terminal JavaScript d'informations système"
                  loading="lazy">
                <span class="galerie-index">22</span>
              </div>
              <div class="galerie-caption">
                <h4>Help Desk Basic</h4>
                <p>Accédez aux <strong>informations basiques de votre machine</strong> via un pseudo terminal. 2021. <a
                    href="https://berru-g.github.io/console-interactive/" target="_blank"
                    rel="noopener noreferrer">Ouvrir le terminal →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#JavaScript</span>
                  <span class="tag">#Terminal</span>
                  <span class="tag">#2021</span>
                </div>
              </div>
            </div>

            <!-- Projet 23: GitHub -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('./img/profilgit.png', 'GitHub', 'Explorez mes travaux ligne après ligne depuis 2020 : tous mes scripts et codes sources open-source.')">
              <div class="galerie-image-container">
                <img src="./img/profilgit.png" alt="GitHub berru-g - Tous mes travaux open-source depuis 2020"
                  loading="lazy">
                <span class="galerie-index">23</span>
              </div>
              <div class="galerie-caption">
                <h4>GitHub</h4>
                <p>Explorez mes travaux <strong>ligne après ligne depuis 2020</strong>. Tous mes scripts open-source
                  sont ici. <a href="https://github.com/berru-g/" target="_blank" rel="noopener noreferrer">Voir mon
                    GitHub →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#Code</span>
                  <span class="tag">#GitHub</span>
                  <span class="tag">#OpenSource</span>
                </div>
              </div>
            </div>

            <!-- Projet 24: QR-code Generator -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('./img/btcP.png', 'QR-code Generator', 'Créez votre QR-code sans pub et gratuitement. 2021.')">
              <div class="galerie-image-container">
                <img src="./img/btcP.png" alt="QR-code Generator - Générateur de QR-code gratuit sans pub"
                  loading="lazy">
                <span class="galerie-index">24</span>
              </div>
              <div class="galerie-caption">
                <h4>QR-code Generator</h4>
                <p>Créez votre <strong>QR-code sans pub</strong> et gratuitement. 2021.<span
                    style="color:#ff86e9">obsolète
                    depuis l'IA</span> <a href="https://berru-g.github.io/generate-qrcode/" target="_blank"
                    rel="noopener noreferrer">Générer
                    un QR-code →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#QRcode</span>
                  <span class="tag">#Gratuit</span>
                  <span class="tag">#2021</span>
                </div>
              </div>
            </div>

            <!-- Projet 25: Philosophie et algorithme -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('https://user-images.githubusercontent.com/61543927/230213178-fda2cb13-9329-49e6-9712-83f87fe51736.png', 'Philosophie et algorithme', 'npm install resilience. Une exploration entre philosophie et code.')">
              <div class="galerie-image-container">
                <img
                  src="https://user-images.githubusercontent.com/61543927/230213178-fda2cb13-9329-49e6-9712-83f87fe51736.png"
                  alt="Philosophie et algorithme - npm install resilience" loading="lazy">
                <span class="galerie-index">25</span>
              </div>
              <div class="galerie-caption">
                <h4>Philosophie &amp; Algorithme</h4>
                <p><em>npm install resilience</em> — install ce que tu veux. Philosophie, envie, joie et résilience en
                  code. <a href="https://github.com/berru-g/phylorythme/tree/main" target="_blank"
                    rel="noopener noreferrer">Voir le repo →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#Philosophie</span>
                  <span class="tag">#npm</span>
                  <span class="tag">#Résilience</span>
                </div>
              </div>
            </div>


            <!-- Projet 27: Midi Copper Key -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('./img/make&play_cooper.jpg', 'Midi Copper Key', 'Conception du circuit électronique et du PCB d\'un microcontrôleur pour tout logiciel DAW comme Ableton. Code de 2020, des mises à jour sont certainement nécessaires.')">
              <div class="galerie-image-container">
                <img src="./img/make&play_cooper.jpg" alt="Midi Copper Key - Contrôleur DAW Arduino en cuivre fait main"
                  loading="lazy">
                <span class="galerie-index">27</span>
              </div>
              <div class="galerie-caption">
                <h4>Midi Copper Key</h4>
                <p>Conception du circuit et PCB d'un <strong>microcontrôleur en cuivre</strong> pour tout logiciel DAW
                  comme Ableton. <a href="https://www.hackster.io/gleberruyer/midi-copper-key-144c42" target="_blank"
                    rel="noopener noreferrer">Voir sur Hackster →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#Arduino</span>
                  <span class="tag">#Cuivre</span>
                  <span class="tag">#DAW</span>
                </div>
              </div>
            </div>

            <!-- Projet 28: Conception PCB -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('https://user-images.githubusercontent.com/61543927/230877531-184f9646-a850-4e15-8ea6-a71579c1232b.png', 'Conception PCB', 'Conception de PCB optimisé.')">
              <div class="galerie-image-container">
                <img
                  src="https://user-images.githubusercontent.com/61543927/230877531-184f9646-a850-4e15-8ea6-a71579c1232b.png"
                  alt="Conception PCB - Circuit électronique optimisé" loading="lazy">
                <span class="galerie-index">28</span>
              </div>
              <div class="galerie-caption">
                <h4>Conception PCB</h4>
                <p><strong><span style="color:#ab9ff2">Dispo sur commande</span></strong>. Conception de <strong>PCB
                    optimisé</strong>. Plan électronique et fichiers de fabrication. <a
                    href="https://github.com/berru-g/MAKE_PLAY-1/" target="_blank" rel="noopener noreferrer">Voir sur
                    GitHub →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#PCB</span>
                  <span class="tag">#Électronique</span>
                  <span class="tag">#EasyEDA</span>
                </div>
              </div>
            </div>

            <!-- Projet 29: Conception PCB 16 potards -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="100"
              onclick="openModal('https://github.com/berru-g/MAKE_PLAY-1/blob/master/img/xiao16.jpg?raw=true', 'Conception PCB 16 potards', 'Conception de PCB (circuit électronique) optimisé. Contrôleur MIDI USB 16 potards mappables et plug & play. Code de 2020, des mises à jour sont certainement nécessaires.')">
              <div class="galerie-image-container">
                <img src="https://github.com/berru-g/MAKE_PLAY-1/blob/master/img/xiao16.jpg?raw=true"
                  alt="Conception PCB - Contrôleur MIDI USB 16 potards mappables" loading="lazy">
                <span class="galerie-index">29</span>
              </div>
              <div class="galerie-caption">
                <h4>Conception PCB 16 potards</h4>
                <p><strong><span style="color:#ab9ff2">Dispo sur commande</span></strong>. Conception de <strong>PCB
                    optimisé</strong> : contrôleur MIDI USB <strong>16 potards
                    mappables</strong>
                  et plug &amp; play. <a href="https://github.com/berru-g/MAKE_PLAY-1/" target="_blank"
                    rel="noopener noreferrer">Voir sur GitHub →</a></p>
                <div class="galerie-tags">
                  <span class="tag">#PCB</span>
                  <span class="tag">#MIDI</span>
                  <span class="tag">#Arduino</span>
                </div>
              </div>
            </div>

            <!-- Projet 30: Advent Calendar -->
            <div class="galerie-item" data-aos="fade-up" data-aos-delay="200"
              onclick="openModal('./img/advent-calendar.png', 'Advent Calendar', 'Calendrier de l\'avent de mes projets open-source : une surprise par jour.')">
              <div class="galerie-image-container">
                <img src="./img/advent-calendar.png"
                  alt="Advent Calendar - Calendrier de l'avent des projets open-source" loading="lazy">
                <span class="galerie-index">30</span>
              </div>
              <div class="galerie-caption">
                <h4>Advent Calendar</h4>
                <p><strong>Calendrier de l'avent</strong> de mes projets open-source. Une surprise par jour de décembre.
                  <a href="./advent-calendar/index.html">Ouvrir une case →</a>
                </p>
                <div class="galerie-tags">
                  <span class="tag">#Calendrier</span>
                  <span class="tag">#Noël</span>
                  <span class="tag">#OpenSource</span>
                </div>
              </div>
            </div>

            <!--<section class="galerie-report" data-aos="fade-up" lang="en">
              <div>
                <h2 class="galerie-meta" style="font-size:1rem;">Web developer in Vertou, Loire-Atlantique (44), France
                </h2>
                <p class="galerie-meta" style="font-weight:400; opacity:.8; max-width:60ch;">
                  Self-taught developer based in <strong>Vertou, France</strong>, I design
                  <strong>PCBs</strong>, <strong>REST APIs</strong>, <strong>dashboards</strong> and
                  <strong>business software</strong> in <strong>plain PHP</strong> — no frameworks,
                  no unnecessary dependencies — for fast, maintainable tools where
                  <strong>you own your data</strong>. See also my
                  <a href="https://github.com/berru-g/" target="_blank" rel="noopener noreferrer">GitHub</a>.
                </p>
              </div>
            </section>-->

          </div>

        </div>

        <!-- Modal pour l'affichage agrandi -->
        <div id="galerie-modal" class="modal">
          <span class="close-modal" onclick="closeModal()">&times;</span>
          <img class="modal-content" id="modal-image" alt="Image agrandie du projet">
          <div id="modal-caption"></div>
        </div>
      </div>

      <!-- Script galerie (logique conservée) -->
      <script>
        function openModal(imageUrl, title, description) {
          document.getElementById('galerie-modal').style.display = 'block';
          document.getElementById('modal-image').src = imageUrl;
          document.getElementById('modal-caption').innerHTML = '<h3>' + title + '</h3><p>' + description + '</p>';
          document.body.style.overflow = 'hidden';
        }

        function closeModal() {
          document.getElementById('galerie-modal').style.display = 'none';
          document.body.style.overflow = 'auto';
        }

        // Fermer le modal en cliquant en dehors ou avec la touche ESC
        window.addEventListener('click', function (e) {
          const modal = document.getElementById('galerie-modal');
          if (e.target === modal) {
            closeModal();
          }
        });

        window.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') {
            closeModal();
          }
        });
      </script>


      <?php include "LibreAnalytics/smart_pixel_v2/public/chatbot.php" ?>

      <!--LE SHOP -->
      <iframe style="display: block; width: 100%; height: 700px; border: none; border-radius: 8px;"
        src="https://gael-berru.com/shop/index.php" title="shop" loading="lazy" allowfullscreen>
      </iframe>


    </main>

  </div>

  <div id="cookie-banner" style="display: none;">
    <div class="cookie-container">
      <div class="cookie-header">
        <div class="cookie-icon">🛡️</div>
        <div class="cookie-title-wrapper">
          <h3 class="cookie-title">Transparence totale sur vos données</h3>
          <p class="cookie-subtitle">Respect RGPD • Open source</p>
        </div>
      </div>

      <div class="cookie-content">
        <p class="cookie-description">
          <strong>Ici, aucun de vos clics n'est vendu à Google ou Facebook.</strong><br>
          J'utilise <strong>Libre Analytics</strong>, mon propre système d'analyse souverain développé avec éthique,
          dans le respect
          des lois RGPD.
        </p>
        <p class="cookie-description">
          En autorisant l'analyse, vous m'aidez à améliorer ce site <strong>sans vendre ni exploiter vos
            données</strong>.
        </p>
      </div>

      <div class="cookie-buttons">
        <button class="cookie-btn accept-necessary" onclick="acceptCookies('necessary')">
          Non merci
        </button>
        <button class="cookie-btn accept-all" onclick="acceptCookies('all')">
          Ok pour moi
        </button>
      </div>

      <div class="cookie-footer">
        <a href="https://github.com/berru-g/LibreAnalytics" target="_blank" class="cookie-link">
          Voir le code source de Libre Analytics
        </a>
      </div>
    </div>
  </div>
  <script src="./LibreAnalytics/RGPD/cookie.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/intro.min.js"></script>
  <script src="./menu/menu.js"></script>
  <script src="./menu/projects-database.js"></script>
  <script src="./menu/welcome.js"></script>
  <script src="./api/fetchapi.js"></script>
  <script src="./menu/honey.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Initialiser AOS
    AOS.init({
      duration: 800,
      once: true, // Animation une seule fois
      offset: 100
    });

    console.log('✅ AOS initialisé');

  </script>
</body>

</html>