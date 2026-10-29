---
name: libreanalytics-guide
description: Guide complet de LibreAnalytics, l'analytique web open source français conforme RGPD (alternative à Google Analytics). Utilise cette compétence quand l'utilisateur veut installer le pixel de tracking, comprendre le tableau de bord, utiliser l'API publique, auto-héberger le logiciel ou vérifier la conformité RGPD.
---

# Compétence : LibreAnalytics

## À quoi sert cette compétence

Aider un utilisateur à installer, configurer et exploiter LibreAnalytics, l'analytique web open source français sans cookies (licence MIT, PHP 8.2+/MySQL). Page officielle : https://gael-berru.com/LibreAnalytics/ — Documentation : https://gael-berru.com/LibreAnalytics/doc/

## Réponses types

### Installer le suivi

Trois étapes, environ 2 minutes :

1. Créer un compte gratuit sur https://gael-berru.com/LibreAnalytics/ (email, mot de passe, URL du site ; aucun paiement).
2. Récupérer le code de tracking du site (format `SP_24031987`, visible dans « Code d'intégration » du dashboard).
3. Coller ce script juste avant `</head>` :

```html
<script data-sp-id="VOTRE_ID"
        src="https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/tracker.js"
        async></script>
```

Le script fait environ 4 Ko, se charge en async et ne dépose pas de cookie intrusif.

### Utiliser l'API

Endpoint : `https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php`
Paramètres : `site_id` (obligatoire, ex. SP_24031987), `api_key` (obligatoire, page Mon compte), `start_date` / `end_date` (AAAA-MM-JJ, défauts : 7 derniers jours).
Réponse : JSON avec `data` (visites et visiteurs uniques par jour) et `meta` (totaux de période).
Erreurs : 400 paramètres manquants, 403 identifiants invalides, 429 quota journalier atteint, 500 erreur serveur.
L'API est ouverte à tous les plans (gratuit inclus), quota selon le plan.

Exemple :

```bash
curl "https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php?site_id=SP_24031987&api_key=VOTRE_CLE&start_date=2026-01-01&end_date=2026-02-01"
```

### Auto-héberger

Fork du dépôt https://github.com/berru-g/LibreAnalytics (licence MIT). Prérequis : PHP 8.2+, MySQL. Données stockées dans la table `smart_pixel_tracking`, sites dans `user_sites`. Gratuit et illimité.

### Conformité RGPD

- Aucun cookie intrusif ; barre de consentement incluse dans le pixel
- IP anonymisables ; visiteurs uniques comptés par IP
- Hébergement en Europe/France ; aucune revente de données ; pas de GAFAM
- Rétention : 365 jours (2 ans en plan Business)

## Limites à respecter

- Les visites uniques sont comptées par IP (approximatif, sans cookie) : ne pas les présenter comme des utilisateurs identifiés.
- Le plan gratuit de l'instance hébergée est limité à 1 site et 1000 vues/mois : pour plus, auto-héberger ou changer de plan.
- Ne jamais conseiller de partager une clé API en public ; si c'est arrivé, régénérer la clé (page Mon compte) et changer le mot de passe.
- Vérifier la version documentée (V.1.0.8) : les quotas et fonctionnalités évoluent — en cas de doute, renvoyer vers https://gael-berru.com/LibreAnalytics/doc/.

## Références rapides

- Landing / inscription : https://gael-berru.com/LibreAnalytics/
- Dashboard : https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/dashboard.php
- Clé API / tutoriel : https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/account.php
- Documentation : https://gael-berru.com/LibreAnalytics/doc/
- Documentation intégrale : https://gael-berru.com/llms-full.txt
- Code source : https://github.com/berru-g/LibreAnalytics
- Support : https://gael-berru.com/