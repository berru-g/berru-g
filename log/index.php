<?php
$to = 'g.leberruyer@gmail.com';  
$subject = 'Accés aux Honey Pot';
$message = 'Un acces à /log/ non autorisé. Voir https://gael-berru.com/LibreAnalytics/';
$headers = 'From: contact@gael-berru.com' . "\r\n";
if (mail($to, $subject, $message, $headers)) {
    echo "Email envoyé avec succès !";
} else {
    echo "Échec de l'envoi.";
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BACKUP ADMIN - CREDENTIALS 2026 [CONFIDENTIEL]</title>
    <meta name="robots" content="noindex, nofollow">
    <script src="https://gael-berru.com/LibreAnalytics/pixel/smart-pixel.js"></script>
    <script data-sp-id="SP_7f9505cc" src="https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/tracker.js"
        async></script>
</head>

<body>
    <div class="container">
        <img src="https://gael-berru.com/LibreAnalytics/pixel/pixel.php" width="1" height="1"
            style="display:none;">

        <div class="warning-banner">
            ACCÈS NON AUTORISÉ - FICHIER CONFIDENTIEL - NE PAS OUVRIR
        </div>

        <!-- En-tête avec IP et timestamp -->
        <div class="header">
            <div class="timestamp">
                <span id="datetime"></span> UTC+2
            </div>
            <div class="ip-info">
                <span id="visitor-ip">[DÉTECTION IP]</span> -
                <span id="visitor-location">[GÉOLOCALISATION]</span>
            </div>
        </div>

        <!-- Message d'alerte -->
        <div style="background: #330000; border: 1px solid red; padding: 1rem; margin-bottom: 2rem; color: #ff9999;">
            <strong>⚠️ ALERTE SÉCURITÉ :</strong> Ce fichier a été exposé accidentellement. Contient des identifiants
            critiques.
            <span style="color: yellow; font-weight: bold;">VOTRE IP A ÉTÉ ENREGISTRÉE</span>
        </div>

        <!-- Grille principale -->
        <div class="grid">
            <!-- Carte 1 : Accès Admin -->
            <div class="card">
                <div class="card-header">
                    <span style="font-size: 2rem;">👑</span>
                    <h2>Accès Super Admin</h2>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Admin URL</div>
                    <div class="credential-value">https://admin.smart-pixel.fr/secret/manager/</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Username</div>
                    <div class="credential-value highlight">super.admin@smart-pixel.fr</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Password</div>
                    <div class="credential-value highlight">Azerty123!@#SuperSecure2026</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">2FA Secret</div>
                    <div class="credential-value">JBSWY3DPEHPK3PXP (QR code disponible)</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Dernière connexion</div>
                    <div class="credential-value">2026-02-14 03:42:17 (IP: 185.234.12.45 - Russie)</div>
                </div>
            </div>

            <!-- Carte 2 : Base de données -->
            <div class="card">
                <div class="card-header">
                    <span style="font-size: 2rem;">🗄️</span>
                    <h2>Bases de données</h2>
                </div>
                <div class="credential-item">
                    <div class="credential-label">MySQL Production (read/write)</div>
                    <div class="credential-value">mysql://db-prod.smart-pixel.internal:3306/smartpixel_prod</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Username</div>
                    <div class="credential-value">root_prod</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Password</div>
                    <div class="credential-value highlight">P@ssw0rd!Prod2026_Secure</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Redis Cache</div>
                    <div class="credential-value">redis://cache.internal:6379 (auth: redis_prod_2026)</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">MongoDB Backup</div>
                    <div class="credential-value">mongodb://backup_user:Backup2026!@mongo-backup.internal:27017/admin
                    </div>
                </div>
            </div>

            <!-- Carte 3 : API Keys (tentantes) -->
            <div class="card">
                <div class="card-header">
                    <span style="font-size: 2rem;">🔑</span>
                    <h2>API Keys - Services externes</h2>
                </div>
                <div class="api-grid">
                    <div class="api-key">
                        <div>Stripe (Live)</div>
                        <div class="key">sk_live_51H2a4eKLj8s9d7f6g5h4j3k2l1</div>
                    </div>
                    <div class="api-key">
                        <div>PayPal Business</div>
                        <div class="key">AZXcVbNmQwErTyUiOp1234567890</div>
                    </div>
                    <div class="api-key">
                        <div>AWS S3</div>
                        <div class="key">AKIAIOSFODNN7EXAMPLE123456</div>
                    </div>
                    <div class="api-key">
                        <div>Google Cloud</div>
                        <div class="key">AIzaSyDdIoFpJLmKqRzXwCvBnMsQ12345</div>
                    </div>
                    <div class="api-key">
                        <div>Mailgun SMTP</div>
                        <div class="key">smtp:postmaster@mg.smart-pixel.fr:SuperMail2026</div>
                    </div>
                    <div class="api-key">
                        <div>Twilio SMS</div>
                        <div class="key">AC1234567890abcdefghijklmnopqrst</div>
                    </div>
                </div>
                <div class="credential-item" style="margin-top: 1rem;">
                    <div class="credential-label">JWT Secret</div>
                    <div class="credential-value highlight">jwt_secret_key_2026_super_secure_256bits_aes_encryption_key
                    </div>
                </div>
            </div>

            <!-- Carte 4 : RIB / IBAN (très attractif) -->
            <div class="card">
                <div class="card-header">
                    <span style="font-size: 2rem;">💶</span>
                    <h2>Coordonnées bancaires - Société</h2>
                </div>
                <div class="bank-details">
                    <div class="bank-row">
                        <span class="bank-label">IBAN</span>
                        <span class="bank-value highlight">FR76 3000 4005 6700 0102 3045 678</span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">BIC/SWIFT</span>
                        <span class="bank-value">BNPAFRPPXXX</span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">Titulaire</span>
                        <span class="bank-value">SMART PIXEL SAS</span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">Banque</span>
                        <span class="bank-value">BNP Paribas Paris</span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">RIB</span>
                        <span class="bank-value">30004 00567 00010203045 67</span>
                    </div>
                </div>
                <div style="margin-top: 1.5rem; background: #003322; padding: 1rem; border-radius: 5px;">
                    <div style="color: #88ff88;">💰 Solde actuel : 1,247,890.45 €</div>
                    <div style="color: #ff8888; font-size: 0.8rem;">Dernier mouvement : virement SEPA 45,000€ vers CY
                        (Chypre)</div>
                </div>
            </div>

            <!-- Carte 5 : Accès SSH/Serveurs -->
            <div class="card">
                <div class="card-header">
                    <span style="font-size: 2rem;">🖥️</span>
                    <h2>Accès serveurs (SSH)</h2>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Serveur principal (prod-01)</div>
                    <div class="credential-value">ssh root@185.145.34.22 -p 2222</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Mot de passe</div>
                    <div class="credential-value highlight">S3rv3rRoot!2026</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Clé privée (RSA)</div>
                    <div class="credential-value" style="font-size: 0.7rem;">-----BEGIN RSA PRIVATE KEY-----
                        MIIEpAIBAAKCAQEA1+UqK9LqXk8JqYq5pL8qK9LqXk8JqYq5pL8qK9LqXk8JqYq5
                        pL8qK9LqXk8JqYq5pL8qK9LqXk8JqYq5pL8qK9LqXk8JqYq5pL8qK9LqXk8JqYq5
                        [... CLÉ TRONQUÉE ...]
                        -----END RSA PRIVATE KEY-----</div>
                </div>
                <div class="ssh-log">
                    <div class="ssh-line"><span class="timestamp">2026-02-14 04:12:33</span> Failed password for invalid
                        user admin from 45.155.205.33 port 54321</div>
                    <div class="ssh-line"><span class="timestamp">2026-02-14 04:11:22</span> Accepted password for root
                        from 185.145.34.22 port 40192</div>
                    <div class="ssh-line"><span class="timestamp">2026-02-14 04:10:01</span> session opened for user
                        backup</div>
                </div>
            </div>

            <!-- Carte 6 : FTP / Wordpress -->
            <div class="card">
                <div class="card-header">
                    <span style="font-size: 2rem;">🌐</span>
                    <h2>Accès FTP / CMS</h2>
                </div>
                <div class="credential-item">
                    <div class="credential-label">WordPress Admin</div>
                    <div class="credential-value">https://blog.smart-pixel.fr/wp-admin</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Login WP</div>
                    <div class="credential-value">admin_wp</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Password WP</div>
                    <div class="credential-value">WordPress2026!Secure</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">FTP (vsftpd)</div>
                    <div class="credential-value">ftp.smart-pixel.fr / user: ftp_prod / pass: FtpAccess2026</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">cPanel</div>
                    <div class="credential-value">https://cpanel.smart-pixel.fr:2083 (admin:cPanel2026!)</div>
                </div>
            </div>
        </div>

        <!-- Section deuxième niveau - Documents sensibles -->
        <div style="margin-top: 3rem;">
            <h2 style="color: #00ff9d; margin-bottom: 1.5rem;">📁 Documents internes (RESTREINT)</h2>
            <div class="grid">
                <!-- Contrats fournisseurs -->
                <div class="card">
                    <h3 style="margin-bottom: 1rem;">🤝 Contrats fournisseurs</h3>
                    <div class="credential-item">
                        <div>OVHcloud - Contrat PRO</div>
                        <div class="credential-value">N°OVH-PRO-2026-7890 / Code promo: OVH42POWER</div>
                    </div>
                    <div class="credential-item">
                        <div>Microsoft Azure - Enterprise Agreement</div>
                        <div class="credential-value">EA-1234567 / Subscription: 1234-5678-9012-3456</div>
                    </div>
                </div>

                <!-- Ressources humaines -->
                <div class="card">
                    <h3 style="margin-bottom: 1rem;">👥 RH - Salaires</h3>
                    <div class="credential-item">
                        <div>Fichier Excel salaires</div>
                        <div class="credential-value">\\server\RH\salaires_2026_protected.xlsx (pass: RH2026!)</div>
                    </div>
                    <div class="credential-item">
                        <div>Contrat CEO</div>
                        <div class="credential-value">Salaire annuel: 180,000€ + bonus</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section backup S3 -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <span style="font-size: 2rem;">☁️</span>
                <h2>Backups S3 - Accès direct</h2>
            </div>
            <div class="credential-item">
                <div class="credential-label">Bucket URL</div>
                <div class="credential-value">https://s3.eu-west-3.amazonaws.com/smart-pixel-backups-prod/</div>
            </div>
            <div class="credential-item">
                <div class="credential-label">Access Key ID</div>
                <div class="credential-value">AKIAIOSFODNN7EXAMPLE</div>
            </div>
            <div class="credential-item">
                <div class="credential-label">Secret Access Key</div>
                <div class="credential-value highlight">wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY</div>
            </div>
            <div class="credential-item">
                <div class="credential-label">Dernier backup complet</div>
                <div class="credential-value">2026-02-13 23:00:01 (4.2 TB)</div>
            </div>
        </div>

        <!-- Fausse note "IMPORTANT" -->
        <div
            style="margin: 3rem 0; padding: 2rem; background: #1a3300; border: 2px solid #ffaa00; border-radius: 10px;">
            <div style="display: flex; align-items: center; gap: 2rem;">
                <span style="font-size: 4rem;">📌</span>
                <div>
                    <h3 style="color: #ffaa00; margin-bottom: 1rem;">NOTE DE SERVICE URGENTE (NE PAS DIFFUSER)</h3>
                    <p style="color: #dddddd;">Suite à l'incident de sécurité du 12/02, tous les mots de passe doivent
                        être changés pour le format: [NomProjet][Année]!Secure.
                        Ne pas utiliser de caractères spéciaux supplémentaires. Le fichier Excel avec tous les mots de
                        passe est ici: \\secure-server\internal\passwords_master.xlsx (mot de passe du fichier:
                        PasswordMaster2026!)</p>
                    <p style="color: #ff8888; margin-top: 1rem;">🔴 SIGNALER TOUT ACCÈS SUSPECT À
                        security@smart-pixel.fr</p>
                </div>
            </div>
        </div>

        <!-- Footer piège -->
        <div
            style="margin-top: 4rem; padding: 1rem; border-top: 1px solid #00ff9d33; font-size: 0.8rem; color: #669966; display: flex; justify-content: space-between;">
            <div>© Smart Pixel Internal - Tous droits réservés - Document classé confidentiel</div>
            <div>Fichier: backup_credentials_2026-02-14.html</div>
            <div>SHA256: 3a4e8f9c1b2d5a7e6f8c9b1a3d5f7e9c2b4a6d8f0e1c3a5b7d9f0e2c4a6b8d0</div>
        </div>

        <!-- Marqueur honey pot invisible -->
        <div class="honeypot-marker">
            HONEYPOT - TRACKING ACTIF - NE PAS TOUCHER
        </div>
    </div>

    <script>
        // Script pour rendre plus réaliste (fausse détection IP)
        document.addEventListener('DOMContentLoaded', function () {
            // Afficher la date/heure actuelle
            function updateDateTime() {
                const now = new Date();
                const datetime = now.toISOString().replace('T', ' ').substring(0, 19);
                document.getElementById('datetime').textContent = datetime;
            }
            updateDateTime();
            setInterval(updateDateTime, 1000);

            // Simuler une IP (mais en vrai c'est le pixel qui track)
            const fakeIPs = ['88.174.123.45', '92.184.76.32', '78.125.43.21', '82.64.132.98'];
            const fakeLocations = ['Paris, France', 'Lyon, France', 'Marseille, France', 'Bordeaux, France'];

            // Prendre une IP aléatoire (mais en vrai on aura la vraie IP dans le pixel)
            const randomIndex = Math.floor(Math.random() * fakeIPs.length);
            document.getElementById('visitor-ip').textContent = fakeIPs[randomIndex];
            document.getElementById('visitor-location').textContent = fakeLocations[randomIndex];

            // Log dans la console pour faire peur
            console.log('%c⚠️ ALERTE SÉCURITÉ ⚠️', 'background: red; color: white; font-size: 20px; padding: 10px;');
            console.log('%cVotre IP a été enregistrée et sera analysée.', 'font-size: 14px; color: orange;');
            console.log('%cCe fichier est un honeypot. Toute tentative d\'utilisation sera signalée.', 'font-size: 12px; color: #888;');

            // Tentative d'envoi d'un événement supplémentaire via SmartPixel
            if (window.SmartPixel) {
                // Attendre que le pixel soit chargé
                setTimeout(() => {
                    try {
                        SmartPixel.trackEvent('honeypot_access', {
                            page: 'faux_credentials',
                            timestamp: new Date().toISOString(),
                            user_agent: navigator.userAgent,
                            language: navigator.language,
                            screen: `${screen.width}x${screen.height}`,
                            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
                        });
                        console.log('📡 Données envoyées au honeypot');
                    } catch (e) {
                        console.log('Pixel pas encore prêt');
                    }
                }, 2000);
            }

            // Faire clignoter les éléments "highlight"
            setInterval(() => {
                document.querySelectorAll('.highlight').forEach(el => {
                    el.style.backgroundColor = el.style.backgroundColor === 'rgba(255, 0, 0, 0.3)' ? 'rgba(255, 0, 0, 0.2)' : 'rgba(255, 0, 0, 0.3)';
                });
            }, 500);

            // Simulation de keylogger (pour le fun)
            document.addEventListener('keydown', function (e) {
                if (e.ctrlKey && e.key === 'c') {
                    console.log('⚠️ Tentative de copie détectée');
                }
            });
        });
    </script>

    <!-- Pixel de tracking supplémentaire (image invisible) -->
    <img src="https://gael-berru.com/LibreAnalytics/pixel/pixel.php?utm_source=honeypot" width="1" height="1"
        style="display:none;" alt="">

    <!-- Script de tracking renforcé 
    <script>
        // Envoi des données au chargement et à la fermeture
        (function() {
            const data = {
                url: window.location.href,
                referrer: document.referrer,
                timestamp: new Date().toISOString(),
                screen: `${window.innerWidth}x${window.innerHeight}`,
                userAgent: navigator.userAgent,
                language: navigator.language,
                cookiesEnabled: navigator.cookieEnabled,
                doNotTrack: navigator.doNotTrack,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                plugins: Array.from(navigator.plugins).map(p => p.name),
                webdriver: navigator.webdriver,
                hardwareConcurrency: navigator.hardwareConcurrency,
                deviceMemory: navigator.deviceMemory,
                connection: navigator.connection ? {
                    effectiveType: navigator.connection.effectiveType,
                    rtt: navigator.connection.rtt,
                    downlink: navigator.connection.downlink
                } : null
            };

            // Envoyer via beacon si possible
            if (navigator.sendBeacon) {
                const blob = new Blob([JSON.stringify(data)], {type: 'application/json'});
                navigator.sendBeacon('https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/pixel.php?t=SP_7f9505cc&event=beacon', blob);
            }

            // Envoyer aussi au beforeunload
            window.addEventListener('beforeunload', function() {
                const img = new Image();
                img.src = `https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/pixel.php?t=SP_7f9505cc&event=exit&time=${Date.now()}`;
            });
        })();
    </script>-->
    <!-- Version corrigée du script honeypot - SANS beacon, SANS CORS -->
    <script>
        SmartPixel.load('SP_7f9505cc'); // Chargement manuel
        SmartPixel.trackEvent('eventName', { data }); // Événement personnalisé
        SmartPixel.getOrCreateSessionId(); // Récupère l'ID de session
        (function () {
            // Récupérer le vrai tracking code
            const trackingCode = 'SP_7f9505cc'; // REMPLACEZ PAR VOTRE VRAI CODE

            // 1. Déjà tracké automatiquement par tracker.js (c'est le principal)

            // 2. Pixel supplémentaire pour les données enrichies (toujours en GET)
            function trackEvent(eventName, extraData = {}) {
                const params = new URLSearchParams({
                    t: trackingCode,
                    event: eventName,
                    timestamp: Date.now(),
                    url: window.location.href,
                    referrer: document.referrer || 'direct',
                    screen: `${window.innerWidth}x${window.innerHeight}`,
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
                });

                // Ajouter des données supplémentaires si besoin
                if (Object.keys(extraData).length > 0) {
                    params.set('data', JSON.stringify(extraData));
                }

                // Technique de l'image - PAS de CORS
                new Image().src = `https://gael-berru.com/LibreAnalytics/pixel/pixel.php?${params}`;
            }

            // Tracker la visite de la page
            trackEvent('honeypot_view');

            // Tracker quand on quitte la page
            window.addEventListener('beforeunload', function () {
                // Dernier recours : image synchrone (bloque mais fonctionne)
                const img = new Image();
                img.src = `https://gael-berru.com/LibreAnalytics/pixel/pixel.php?t=${trackingCode}&event=honeypot_exit&time=${Date.now()}`;
                // Pas besoin de l'ajouter au DOM
            });

            // Tracker les clics sur les éléments sensibles
            document.addEventListener('click', function (e) {
                const target = e.target.closest('.credential-value, .bank-value, .api-key');
                if (target) {
                    trackEvent('honeypot_click', {
                        element: target.className,
                        text: target.innerText.substring(0, 30)
                    });
                }
            });

            // Alerte console
            console.log('%c🔴 HONEYPOT ACTIF - Votre visite est enregistrée', 'background: #ff0000; color: white; font-size: 16px; padding: 10px;');
        })();
    </script>
</body>

</html>