# Smart Pixel v2 - Analytics Platform

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Installation: 1 ligne](https://img.shields.io/badge/Installation-1_ligne-green.svg)](#installation)

Avec Smart Pixel vos graphiques racontent une histoire et guident vers des insights actionnables.
Analysez votre trafic sans compromettre la vie privée de vos visiteurs, avec un outil 100% européen et open source

Disponible

    ✅ V.0.0.1 pixel auto hebergé | 2024 | statut - gratuit open source
    ✅ V.0.1.0 pixel multi tenant | 2025 | statut - gratuit pour 1 dashboard
    ▶️ V.1.0.1 software friendly  | 2026 | statut - en cour de dev, MVP fonctionnel 


## Fonctionnalités

### ✅ **Actuellement disponibles**
- **Tracking en temps réel** : Page views, sessions, utilisateurs uniques
- **Géolocalisation** : Pays et ville des visiteurs (via IP)
- **Sources de trafic** : Référents, campagnes UTM, médias sociaux
- **Clics utilisateur** : Tracking automatique des interactions
- **Dashboard multi-sites** : Gérer plusieurs sites par compte
- **Données techniques** : Viewport, user-agent, résolution
- **Sessions** : Identification unique par visite

- **Captcha** : Page contact avec captcha fait maison ( aucun appel vers google captcha)
- **rgpd cookie** : barre des cookies
- **Doc compléte** : doc pour l'installation analytics + auto hebergé.




### 🚀 **En développement (version Premium)**
- **Inclure la barre des cookies dans le pixel id**
- **API RESTful** : Accès programmatique à vos données
- **Export JSON/CSV** : Données brutes pour traitement externe
- **Webhooks** : Notifications en temps réel
- **Intégrations** : ... software Européen ...
- **Limites personnalisées** : Plans selon le volume de données



[Crée ton premier dashboard gratuitement](https://gael-berru.com/smart_phpixel/)



# Guide Utilisateur - Smart Pixel Analytics

Bienvenue sur Smart Pixel, l'alternative française simple et respectueuse à Google Analytics. Ce guide vous aidera à installer, configurer et utiliser votre tableau de bord analytics.

---

## Premiers pas

### 1. Créer votre compte gratuit
Rendez-vous sur [https://gael-berru.com/smart_phpixel/](https://gael-berru.com/smart_phpixel/) et cliquez sur **"CRÉER MON PREMIER DASHBOARD"**.

Vous aurez besoin de :
- Votre email
- Un mot de passe
- L'URL de votre site web

✅ Le premier dashboard est gratuit.

### 2. Récupérer votre code de tracking
Une fois connecté, votre tableau de bord affiche votre **code d'intégration** :

```html
<script data-sp-id="SP_79747769" 
        src="https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/tracker.js" 
        async>
</script>
```

### 3. Installer le script sur votre site
Copiez-collez cette ligne **juste avant la balise `</head>`** de votre site web.

**Le script :**
- Se charge en arrière-plan (async)
- Ne ralentit pas votre site (4KB seulement)
- Commence à tracker instantanément

---

## Découvrir votre tableau de bord

Une fois connecté, votre tableau de bord se compose de plusieurs onglets :

### **Vue d'ensemble**
- **Visites totales** : nombre de pages vues
- **Visiteurs uniques** : comptés par adresse IP
- **Sources de trafic** : d'où viennent vos visiteurs
- **Évolution** : graphique des 7 derniers jours

### **Géolocalisation**
- Carte interactive des pays visiteurs
- Top 10 des pays
- Villes principales

### **Détails**
- Liste complète des dernières visites
- Pages consultées
- Adresses IP (anonymisées)
- Horodatage

### **Technique**
- Répartition par appareil (mobile/desktop/tablette)
- Navigateurs utilisés
- Résolutions d'écran

---

## Comprendre vos données

### Les métriques essentielles

| Métrique | Définition |
|----------|------------|
| **Visites** | Nombre total de pages vues (un visiteur peut faire plusieurs visites) |
| **Visiteurs uniques** | Compté par adresse IP (approximatif, sans cookie) |
| **Source** | D'où vient le visiteur (Google, lien direct, réseau social) |
| **Pages vues** | Combien de pages ont été consultées |

### Les sources de trafic expliquées
- **Direct** : visiteur a tapé votre URL directement
- **Google / Bing** : vient d'un moteur de recherche
- **Facebook / Twitter** : vient d'un réseau social
- **email** : vient d'une campagne email

---

## Utiliser les paramètres UTM

Les paramètres UTM vous permettent de **tracer précisément vos campagnes marketing**.

### Comment ça marche ?
Ajoutez ces paramètres à vos URLs :

```
https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/tracker.js?utm_source=facebook&utm_medium=social&utm_campaign=ete2026
```

### Paramètres disponibles
- `utm_source` : d'où vient le trafic (facebook, newsletter, google)
- `utm_medium` : le support (social, email, cpc)
- `utm_campaign` : nom de votre campagne (promo_ete, lancement)

👉 Ces données apparaîtront dans la colonne "Campagne" de votre tableau de bord.

---

##  Gérer plusieurs sites

Le plan gratuit vous permet de suivre **1 site**. Pour ajouter un site :

1. Dans la barre latérale, cliquez sur **"Ajouter un site"**
2. Donnez un nom à votre site
3. Entrez l'URL
4. Récupérez le nouveau code de tracking

Chaque site a son propre **tracking code** (ex: `SP_79747769`). Installez le code correspondant sur chaque site.

---

## Mettre à niveau votre compte

### Plans disponibles

| Fonctionnalité | Gratuit | Pro (9€/mois) | Business (29€/mois) |
|----------------|---------|----------------|---------------------|
| Sites | 1 | 10 | 50 |
| Vues/mois | 1 000 | 100 000 | Illimité |
| Historique | 365 jours | 365 jours | 2 ans |
| API | ❌ | ✅ | ✅ |
| Support | Communauté | Prioritaire | Téléphone |
| Export données | ❌ | ✅ | ✅ |

### Comment passer en Pro ?
1. Allez dans l'onglet **"Mise à niveau"**
2. Choisissez votre plan
3. Renseignez votre email
4. Paiement sécurisé via Lemon Squeezy
5. Votre compte est mis à jour **instantanément**

---

## ❓ F.A.Q

### "Mes données sont-elles vraiment privées ?"
**Oui.** Smart Pixel est hébergé en France. Aucune donnée n'est vendue à des tiers. Pas de GAFAM, pas de revente. Le code est [open source](https://github.com/berru-g/smart_pixel_v2).

### "Est-ce que le pixel ralentit mon site ?"
**Non.** Le script fait 4KB et se charge en async. C'est 15 fois plus léger que Google Analytics.

### "Combien de temps les données sont-elles conservées ?"
**365 jours** pour tous les plans. Le plan Business passe à 2 ans.

### "Puis-je exporter mes données ?"
**Oui** (plans payants). Format CSV ou JSON disponible dans l'onglet "Export".

### "Le RGPD est-il géré ?"
**Complètement.** Le script inclut une gestion des cookies conforme. Les données IP sont anonymisables.

---

## Support & contact

### Besoin d'aide ?
- 📧 Email : contact@gael-berru.com
- 💬 Discord : [Rejoindre le serveur Gitingest ](https://discord.gg/zerRaGK9EC) ( à venir )
- 🐛 Signaler un bug : [GitHub Issues](https://github.com/berru-g/smart_pixel_v2/issues)

---

## Glossaire

| Terme | Définition |
|-------|------------|
| **Pixel** | Image 1x1 transparente qui enregistre une visite |
| **Tracking code** | Identifiant unique de votre site (ex: SP_79747769) |
| **Session** | Ensemble des actions d'un visiteur pendant une visite |
| **Source** | Origine du trafic (moteur, site, direct) |
| **UTM** | Paramètres d'URL pour tracer les campagnes |
| **RGPD** | Règlement européen sur la protection des données |

---

*Document généré le 14 février 2026 - Version 1.0.1*

**Vous avez une question ?** N'hésitez pas à demander, cette documentation est faite pour vous !
