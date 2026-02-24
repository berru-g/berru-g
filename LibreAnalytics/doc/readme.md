# STEP

        Reorga : analytics-souverain.fr/app
        - tout les dossier dans /smart_pixel_v2/ doivent descendre et on efface ce dossier vide.
        On renome /LibreAnalytics/ par /analytics-souverain/ u le ndd chosi (.io, .fr ?)
        On renome /public/ par /app/.
        - Penser à renomer tout les lien, les url endpoint et tracker et les appel à config "/../../config.php" à descendre également.

        - Etendre l'api des users à tout les tables sql.

        - Réorga le system d'abonnement :
                - gratuit     = 1 dashboard
                - pro 9€/mois = 20 dashboard + api
                - pro 90€/ans = 20 dashboard + api

        - Acheter ndd et heberger le site, refaiire bdd etc.

        - Démarcher les PME, Agences, dev , local qui utilise GA et leur proposer mes services. Liste :

INSERT INTO leads (company_name, email, sector, website, status, notes) VALUES
('La Boîte à Sel', 'contact@laboiteasel.fr', 'Épicerie en ligne', 'https://www.laboiteasel.fr', 'à faire', 'PME nantaise - épicerie en ligne. Intéressée par la souveraineté des données.'),
('L\'Atelier du Vélociste', 'contact@latelierduvelociste.fr', 'Vélos', 'https://www.latelierduvelociste.fr', 'à faire', 'Artisan local avec boutique en ligne. Besoin de performance et conformité RGPD.'),
('Nantes Tourisme', 'contact@nantes-tourisme.com', 'Tourisme', 'https://www.nantes-tourisme.com', 'à faire', 'Site institutionnel - trafic important mais sensible à la souveraineté.'),
('La Fabrique à Gâteaux', 'contact@lafabriqueagateaux.com', 'Traiteur', 'https://www.lafabriqueagateaux.com', 'à faire', 'PME alimentaire en ligne. Sensible à la confiance client.'),
('Koukaki', 'contact@koukaki.com', 'Agence Web', 'https://www.koukaki.com', 'à faire', 'Agence web nantaise - prescripteur potentiel pour d\'autres clients.'),
('Le Comptoir du Miel', 'contact@lecomptoirdumiel.fr', 'Apiculture', 'https://www.lecomptoirdumiel.fr', 'à faire', 'PME agricole en ligne. Éthique et transparence importantes.'),
('L\'Épicerie Moderne', 'bonjour@lepiceriemoderne.fr', 'Épicerie', 'https://www.lepiceriemoderne.fr', 'à faire', 'Commerce local avec forte identité éthique.'),
('Atlantique Digital', 'contact@atlantiquedigital.fr', 'Agence SEO', 'https://www.atlantiquedigital.fr', 'à faire', 'Agence SEO - peut recommander Smart Pixel à ses clients.'),
('La Maison du Jardin', 'contact@lamaisondujardin.fr', 'Jardinerie', 'https://www.lamaisondujardin.fr', 'à faire', 'PME locale avec boutique en ligne. Sensible à la confiance client.'),
('Nantes Métropole', 'contact@nantesmetropole.fr', 'Institution', 'https://www.nantesmetropole.fr', 'à faire', 'Portail économique - besoin de souveraineté et budget pour une solution pro.');

**mail type :**
Bonjour [Prénom ou "l’équipe La Boîte à Sel"],

En parcourant [laboiteasel.fr](https://www.laboiteasel.fr), j’ai remarqué que vous utilisez **Google Analytics** pour suivre votre trafic. Comme vous le mentionnez dans votre [politique de confidentialité](https://www.laboiteasel.fr/politique-de-confidentialite), le RGPD est une priorité pour vous – mais saviez-vous que **GA transfère les données de vos clients vers les États-Unis**, ce qui n’est pas pleinement conforme ?

**Smart Pixel** est une alternative **nantaise, open source et 100% hébergée en France**, conçue pour des PME comme la vôtre :
✅ **Conforme RGPD** : Pas de transfert de données hors UE, anonymisation des IP.
✅ **Léger et rapide** : Script de 4KB (vs 60KB pour GA), sans impact sur votre site.
✅ **Gratuit pour 1 site** : Idéal pour tester sans risque.
✅ **Support local** : Je suis basé en Loire-Atlantique et peux vous accompagner en direct.

**Essayez gratuitement** :
👉 [Créer un compte gratuit](https://gael-berru.com/LibreAnalytics/)
👉 [Voir la démo](https://gael-berru.com/LibreAnalytics/doc/)
👉 [Réserver un appel avec moi](lien-calendly.com/tonlien) (15 min pour répondre à vos questions).

Je reste disponible pour échanger par retour de mail ou par téléphone.

Bien cordialement,
**Gaël Berru**
Fondateur de Smart Pixel (Nantes)
📧 contact@gael-berru.com
🌐 [https://gael-berru.com/LibreAnalytics/](https://gael-berru.com/LibreAnalytics/)

**PS** : Comme vous, des épiceries locales ont migré vers Smart Pixel pour éviter les risques RGPD. Je peux vous partager leur retour si ça vous intéresse !

Nom Entreprise,Email,Secteur,Statut,Réponse,Prochaine Action
La Boîte à Sel,[contact@laboiteasel.fr](mailto:contact@laboiteasel.fr),Épicerie,Email envoyé,-,Relance le 10/03
L’Atelier du Vélociste,contact@...,Vélos,Intéressé,Oui,Planifier démo le 12/03