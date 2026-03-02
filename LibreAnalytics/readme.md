# Libre Analytics v2 - Analytics Platform

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Installation](https://img.shields.io/badge/Installation-1_ligne-brightgreen)](https://gael-berru.com/LibreAnalytics/#installation)
[![Made in France](https://img.shields.io/badge/Made%20in-France-0055A4?logo=fr)](https://gael-berru.com)
[![Open Source](https://img.shields.io/badge/Open%20Source-100%25-important?logo=github)](https://github.com/berru-g/LibreAnalytics)
[![No Cookies](https://img.shields.io/badge/No%20Cookies-RGPD%20Friendly-blueviolet)](https://gael-berru.com/LibreAnalytics/)
[![Lightweight](https://img.shields.io/badge/Lightweight-4KB-success?logo=lightning)](https://gael-berru.com/LibreAnalytics/)
[![Sovereign](https://img.shields.io/badge/Sovereign-No%20GAFAM-007EC6?logo=europeanunion)](https://gael-berru.com/LibreAnalytics/)
[![Status](https://img.shields.io/badge/Status-Actively%20Developed-brightgreen)](https://github.com/berru-g/LibreAnalytics/commits/main)

**Alternative 100% française et open source à Google Analytics**

Analysez votre trafic sans compromettre la vie privée de vos visiteurs, avec un outil 100% européen et open source

   ![LibreAnalytics-Dashboard](https://gael-berru.com/img/demo_dashboard.gif)

Disponible

    ✅ V.0.0.1 pixel auto hebergé | 2024 | statut - gratuit open source
    ✅ V.0.1.0 pixel multi tenant | 2025 | statut - gratuit pour 1 dashboard
    ✅ V.1.0.1 software friendly  | 2026 | statut - MVP fonctionnel + API
    ▶️ V.1.0.5 software fullindé  | 2026 | statut - en cours de dev ...



## Sommaire
1. [Fonctionnalités](#fonctionnalités)
2. [Versions et statut](#-actuellement-disponibles)
3. **Guide Utilisateur**
   - [Créer un compte](#1-créer-votre-compte-gratuit)
   - [Installer le code de tracking](#2-récupérer-votre-code-de-tracking)
   - [Tableau de bord](#découvrir-votre-tableau-de-bord)
   - [Gérer plusieurs sites](#-gérer-plusieurs-sites)
   - [Paramètres UTM](#-Utiliser-les-paramètres-UTM)
4. **Tutoriel API** 
   - [Récupérer ta clé API](#-prérequis)
   - [Construire l’URL de l’API](#-2-construire-lurl-de-lapi)
   - [Exemples de requêtes](#-3-récupérer-les-données)
   - [Intégrations](#-5-intégrer-les-données-avec-des-outils)
   - [Gestion des erreurs](#-6-gérer-les-erreurs)
5. [Mise à niveau](#-mettre-à-niveau-votre-compte)
6. [FAQ & Support](#-faq)
7. [Glossaire](#glossaire)



## Pourquoi choisir LibreAnalytics ? :

**Architecture optimisée pour les PME, agence et indé.**

  - *Base de données légère* : LibreAnalytics utilise une base de données MySQL pour stocker les données de manière efficace, sans dépendre de solutions externes.
  - *Pas de frameworks lourds* : Développé en PHP natif, sans dépendances inutiles, pour une maintenance simple et des performances maximales.
  - *Hébergement souverain* : 

## Points clés

### **1. Architecture et Sécurité**
- **Base de données MySQL** : Stockage structuré et sécurisé des données (tables `user_sites`, `smart_pixel_tracking`).
- **Authentification robuste** : Gestion des sessions PHP, vérification des droits d’accès, protection contre les accès non autorisés.
- **Génération de clés uniques** : `tracking_code` et `public_key` aléatoires pour chaque site, garantissant l’isolation des données.
- **Limitation des sites par plan** : Logique de quota (ex: 1 site en gratuit, 10 en Pro), avec messages d’erreur clairs.

### **2. Fonctionnalités avancées du dashboard**
- **Multi-sites** : Gestion de plusieurs sites depuis un seul compte, avec bascule facile entre les tableaux de bord.
- **Filtrage par période** : 7 jours, 30 jours, 90 jours, 1 an.
- **Statistiques en temps réel** :
  - Vues totales, visiteurs uniques, pages/session, temps moyen.
  - Sources de trafic (Google, réseaux sociaux, direct, etc.).
  - Géolocalisation (pays, villes).
  - Appareils (mobile, desktop, tablette).
  - Navigateurs (Chrome, Firefox, Safari, etc.).
- **Visualisation des données** :
  - Graphiques interactifs (Chart.js, amCharts).
  - Cartes géographiques des visiteurs.
  - Tableaux de données détaillées (IP, pages visitées, horodatage).
- **Insights automatisés** :
  - Analyse des tendances (ex: "+20% de trafic cette semaine").
  - Recommandations d’amélioration (ex: "Votre taux de rebond est élevé, optimisez vos landing pages").

### **3. API et Intégrations**
- **Accès programmatique** : Récupération des données via API (JSON/CSV), idéal pour les devs et les intégrations externes.
- **Exemples d’utilisation** :
  - Intégration avec Google Data Studio, Excel, ou des dashboards custom (HTML/JS).
  - Webhooks et notifications en temps réel (en développement).

### **4. Expérience utilisateur**
- **Design moderne et responsive** : Sidebar rétractable, interface intuitive, dark mode.
- **Code d’intégration simplifié** : Un seul script à copier-coller dans le `<head>`.
- **Gestion des limites** : Messages clairs quand l’utilisateur atteint sa limite de sites/visites.

### **5. Souveraineté et Conformité**
- **Hébergement 100% français** : Pas de dépendance aux GAFAM, conformité RGPD native.
- **Anonymisation des IP** : Respect de la vie privée.
- **Pas de cookies intrusifs** : Solution "no cookies" ou barre de consentement intégrée.



## Fonctionnalité

### ✅ **Actuellement disponibles**

#### Plan Gratuit

- **Tracking en temps réel** : Page views, sessions, utilisateurs uniques
- **Géolocalisation** : Pays et ville des visiteurs (via IP)
- **Sources de trafic** : Référents, campagnes UTM, médias sociaux
- **Clics utilisateur** : Tracking automatique des interactions, section, cta, etc.
- **Données techniques** : Viewport, user-agent, résolution
- **Sessions** : Identification unique par visite
- **Formulaire** : Page contact avec captcha fait maison ( aucun appel vers google captcha)
- **rgpd cookie** : barre des cookies
- **Doc compléte** : doc pour l'installation analytics + auto hebergé.
- **Smart Assistant** : recevez des insight actionnable et conseil SEO adapté à vos résultat.

#### Plan Pro

- **Toutes les fonctionnalité précedemment cité plus :**
- **Dashboard multi-sites** : Gérer plusieurs sites par compte
- **API** : Accéder à vos données d'un simple appel API avec votre Key unique. 




### 🚀 **En développement (version Premium)**
- **Inclure la barre des cookies dans le pixel id**
- **API RESTful** : Accès programmatique à vos données ( acceder a votre profil pour obtenir votre api key )
- **Export JSON/CSV** : Données brutes pour traitement externe
- **Webhooks** : Notifications en temps réel
- **Intégrations** : ... software Européen ...
- **Limites personnalisées** : Plans selon le volume de données



[Crée ton premier dashboard gratuitement](https://gael-berru.com/LibreAnalytics/)



# Guide Utilisateur - Libre Analytics

Bienvenue sur Libre Analytics, l'alternative française simple et respectueuse à Google Analytics. Ce guide vous aidera à installer, configurer et utiliser votre tableau de bord analytics.

---

## Premiers pas

### 1. Créer votre compte gratuit
Rendez-vous sur [https://gael-berru.com/LibreAnalytics/](https://gael-berru.com/LibreAnalytics/) et cliquez sur **"CRÉER MON PREMIER DASHBOARD"**.

Vous aurez besoin de :
- Votre email
- Un mot de passe
- L'URL de votre site web

✅ Le premier dashboard est gratuit.

### 2. Récupérer votre code de tracking
Une fois connecté, votre tableau de bord affiche votre **code d'intégration** : 

```html
<script data-sp-id="SP_79747769" 
        src="https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/tracker.js" 
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
https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/tracker.js?utm_source=facebook&utm_medium=social&utm_campaign=ete2026
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


## **Tester l’API** ( plan pro only )

### Ajoutez votre site id avant votre token et insérez vos requete entre les deux !
    URL d’exemple :
    https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php?site_id=SP_12345&start_date=2026-01-01&end_date=2026-02-01&api_token=TON_TOKEN


### Intégrer avec ton dashboard ou outils externes. Depuis un script JS :

```html
    fetch(`https://ton-domaine.com/smart_pixel_v2/public/api.php?site_id=SP_12345&start_date=2026-01-01&end_date=2026-02-01&api_key=TON_TOKEN`)
    .then(response => response.json())
    .then(data => console.log(data));

```


# Tutoriel : Utiliser l’API Libre Analytics
*Alternative open-source à Google Analytics*

---

## **🔑 Prérequis**
- Un **compte Libre Analytics** (gratuit pour 1 site).
- Une **clé API** (disponible dans ton [tableau de bord](https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/account.php)).
- Le **code de tracking** de ton site (ex: `SP_2m4789lg`).

---

## **📌 1. Récupérer ta clé API et ton code de tracking**
### **Étape 1 : Accède à ton compte**
1. Connecte-toi à ton [tableau de bord Libre Analytics](https://gael-berru.com/LibreAnalytics/smart_pixel_v2/dashboard.php).
2. Clique sur **"Parametre"** dans le menu puis sur **L'API et sa Documentation**


### **Étape 2 : Copie ta clé API**
- Dans la section **"Clé API"**, clique sur l’icône pour copier ta clé.
- **Ne partage jamais cette clé** (elle donne accès à tes données).


### **Étape 3 : Récupère ton code de tracking**
1. Retour au menu dans la section **Code d'intégration**
2. Copie le **code de tracking** (ex: `SP_2m4789lg`).

---

## **🔗 2. Construire l’URL de l’API**
L’URL de base pour accéder à tes données est :
```
https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php
```

### **Paramètres obligatoires**
| Paramètre     | Description                          | Exemple                     |
|---------------|--------------------------------------|-----------------------------|
| `site_id`     | Code de tracking de ton site.        | `SP_2m4789lg`               |
| `api_key`     | Ta clé API (copiée plus tôt).        | `1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p` |

### **Paramètres optionnels**
| Paramètre     | Description                          | Exemple       | Défaut          |
|---------------|--------------------------------------|---------------|-----------------|
| `start_date`  | Date de début (format `AAAA-MM-JJ`). | `2026-01-01`  | Il y a 7 jours  |
| `end_date`    | Date de fin (format `AAAA-MM-JJ`).   | `2026-02-01`  | Aujourd’hui     |

### **Exemple d’URL complète**
```
https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php?
site_id=SP_2m4789lg&
api_key=1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p&
start_date=2026-01-01&
end_date=2026-02-01
```

---

## **📥 3. Récupérer les données**
### **Méthode 1 : Depuis un navigateur**
1. Copie-colle l’URL complète dans ton navigateur.
2. Tu verras un **fichier JSON** avec tes données.

   ![Exemple de réponse JSON](https://via.placeholder.com/600x400/4a6bff/ffffff?text=%7B%22success%22%3Atrue%2C%22data%22%3A%5B...%5D%7D)

### **Méthode 2 : Avec cURL (terminal)**
```bash
curl "https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php?
site_id=SP_2m4789lg&
api_key=1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p&
start_date=2026-01-01&
end_date=2026-02-01"
```

### **Méthode 3 : Avec JavaScript (fetch)**
```javascript
const siteId = 'SP_2m4789lg';
const apiKey = '1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p';
const startDate = '2026-01-01';
const endDate = '2026-02-01';

fetch(`https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php?
  site_id=${siteId}&
  api_key=${apiKey}&
  start_date=${startDate}&
  end_date=${endDate}`)
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Erreur:', error));
```

---

## **📊 4. Exemple de réponse JSON**
Voici à quoi ressemble une réponse typique :
```json
{
  "success": true,
  "data": [
    {
      "date": "2026-01-01",
      "visits": 42,
      "unique_visitors": 30,
      "sessions": 35
    },
    {
      "date": "2026-01-02",
      "visits": 50,
      "unique_visitors": 38,
      "sessions": 40
    }
  ],
  "meta": {
    "site_id": "SP_2m4789lg",
    "start_date": "2026-01-01",
    "end_date": "2026-02-01",
    "total_visits": 92,
    "total_unique_visitors": 68
  }
}
```

| Champ               | Description                                  |
|---------------------|----------------------------------------------|
| `date`              | Date des données (format `AAAA-MM-JJ`).      |
| `visits`            | Nombre total de visites.                    |
| `unique_visitors`   | Nombre de visiteurs uniques (par IP).       |
| `sessions`          | Nombre de sessions.                         |
| `total_visits`     | Somme des visites sur la période.           |


#### **Ajouts techniques**
- **Exemple de requête SQL** (pour les devs qui veulent self-hoster) :
  ```sql
  -- Exemple de requête pour récupérer les stats par jour
  SELECT
      DATE(timestamp) as date,
      COUNT(*) as visits,
      COUNT(DISTINCT ip_address) as unique_visitors
  FROM smart_pixel_tracking
  WHERE site_id = 'SP_12345'
  GROUP BY DATE(timestamp)
  ORDER BY date ASC;
  ```
- **Intégration de l’API en Python** :
  ```python
  import requests
  response = requests.get(
      "https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php",
      params={
          "site_id": "SP_12345",
          "api_key": "VOTRE_CLE_API",
          "start_date": "2026-01-01",
          "end_date": "2026-02-01"
      }
  )
  data = response.json()
  print(data["data"])
  ```
- **Cas d’usage avancé** :
  - Comment utiliser vos données LibreAnalytics via l'api pour alimenter un bot Discord ou un script d’alertes (ex: "Si trafic > 1000 visites/jour, envoyer une alerte").
  
---

## **📈 5. Intégrer les données avec des outils**
### **A. Google Data Studio**
1. **Crée une nouvelle source de données** :
   - Sélectionne **"Connexion personnalisée"** > **"URL"**.
   - Colle ton URL d’API.
2. **Mappe les champs** :
   - `date` → Dimension (date).
   - `visits` → Métrique (nombre).
3. **Crée un graphique** :
   - Sélectionne un graphique en lignes ou en barres.
   - Ajoute `date` en axe X et `visits` en axe Y.

   ![Exemple Google Data Studio](https://via.placeholder.com/600x400/4a6bff/ffffff?text=Graphique+Google+Data+Studio)

### **B. Excel ou Google Sheets**
1. **Dans Excel** :
   - Va dans **Données** > **À partir d’une source Web**.
   - Colle ton URL d’API.
2. **Dans Google Sheets** :
   - Utilise la formule `=IMPORTDATA()` :
     ```excel
     =IMPORTDATA("https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php?site_id=SP_2m4789lg&api_key=1a2b3c...")
     ```

### **C. Tableau de bord custom (HTML/JS)**
```html
<!DOCTYPE html>
<html>
<head>
  <title>Dashboard Smart Pixel</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <h1>Dashboard pour les utilisateurs de <a href="https://gael-berru.com/smart_phpixel/">LibreAnalytics, l'analytics souverains"</a></h1>
  <div id="status" class="loading">Chargement des données...</div>
  <div class="chart-container">
    <canvas id="visitsChart"></canvas>
  </div>

  <script>
    const siteId = 'SP_ton_id';  // Remplace par ton vrai site_id
    const apiKey = 'ton_api_key';  // Remplace par ta vraie api_key
    const startDate = '2026-01-01';
    const endDate = '2026-02-26';

    // Remplace l'URL dans ton code JS par :
const proxyUrl = 'https://cors-anywhere.herokuapp.com/';
const url = `${proxyUrl}https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php?
  site_id=${encodeURIComponent(siteId)}&
  api_key=${encodeURIComponent(apiKey)}&
  start_date=${encodeURIComponent(startDate)}&
  end_date=${encodeURIComponent(endDate)}`;


    console.log("URL de l'API :", url);  // Affiche l'URL dans la console

    fetch(url)
      .then(response => {
        if (!response.ok) {
          throw new Error(`Erreur HTTP : ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        document.getElementById('status').textContent = "Données chargées avec succès !";
        console.log("Données reçues :", data);  // Affiche les données dans la console

        const labels = data.data.map(item => item.date);
        const visits = data.data.map(item => item.visits);

        new Chart(document.getElementById('visitsChart'), {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: 'Visites',
              data: visits,
              borderColor: '#9d86ff',
              backgroundColor: 'rgba(74, 107, 255, 0.1)',
              tension: 0.3,
              fill: true
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: { beginAtZero: true }
            }
          }
        });
      })
      .catch(error => {
        console.error("Erreur :", error);
        document.getElementById('status').textContent = `Erreur : ${error.message}`;
        document.getElementById('status').className = "error";
      });
  </script>
</body>
</html>
```

[Ouvrir le template dasn codepen](https://codepen.io/h-lautre/pen/EayBqeE?editors=1000)


---
## **⚠️ 6. Gérer les erreurs**
| Code d’erreur | Cause probable                          | Solution                                  |
|---------------|-----------------------------------------|-------------------------------------------|
| `400`         | Paramètres manquants (`site_id` ou `api_key`). | Vérifie l’URL.                           |
| `403`         | Clé API ou code de tracking invalide.   | Vérifie tes identifiants dans "Parametre". |
| `404`         | Site non trouvé.                        | Vérifie que le `site_id` est correct.      |
| `500`         | Erreur serveur.                         | Contacte le support (avec le message d’erreur). |

---
## **🔄 7. Régénérer ta clé API**
Si ta clé API est compromise :
1. Va dans **"Parametre"**.
2. Clique sur **"Régénérer la clé API"**.
3. **Met à jour tes intégrations** avec la nouvelle clé.

---
## **📌 8. Exemples d’utilisation avancée**
### **A. Filtrer par période dynamique**
```javascript
// Récupérer les données des 30 derniers jours
const today = new Date().toISOString().split('T')[0];
const startDate = new Date();
startDate.setDate(startDate.getDate() - 30);
const formattedStartDate = startDate.toISOString().split('T')[0];

fetch(`https://gael-berru.com/.../api.php?
  site_id=SP_2m4789lg&
  api_key=1a2b3c...&
  start_date=${formattedStartDate}&
  end_date=${today}`)
```

### **B. Agrégat par pays**
Modifie l’URL pour inclure des données géographiques :
```
https://gael-berru.com/.../api.php?
site_id=SP_2m4789lg&
api_key=1a2b3c...&
group_by=country
```
*(À implémenter côté serveur si besoin.)*

---
## **📢 9. Support et contact**
- **Problème technique** ? Ouvre un ticket via [le formulaire de contact](https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/contact.php).
- **Idée d’amélioration** ? Propose-la sur [GitHub](https://github.com/berru-g/smart_pixel_v2).

---

## **🎉 Félicitations !**
Tu peux maintenant :
✅ **Exporter tes données** vers Excel, Google Sheets, ou Data Studio.
✅ **Créer des tableaux de bord personnalisés** avec Chart.js.
✅ **Automatiser tes rapports** avec des scripts.

---
**Besoin d’aide pour une intégration spécifique ?** [Contacte-nous](mailto:contact@gael-berru.com) ! 😊

---
**Prochaine étape** :
- [ ] Tester l’API avec ton site.
- [ ] Créer un tableau de bord custom.
- [ ] Partager tes feedbacks !

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
**Oui.** Libre Analytics est hébergé en France. Aucune donnée n'est vendue à des tiers. Pas de GAFAM, pas de revente. Le code est [open source](https://github.com/berru-g/smart_pixel_v2).

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
- 💬 Discord : [Rejoindre le serveur Gitingest ](https://discord.gg/#) ( à venir )
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


**Note pour toi utilisateur** 
Pour tendre vers la souveraineté total de nos données, je suis confronter à remplacer des outils gafam qu'il m'as fallu développer au fur et à mesure. Des tool tel que :

  - L'API / en pur PHP et JavaScript
  - System de Captcha / idem
  - Séquence email automatique / via cron et email() mais pour des raisons de limitaion journaliére je passerais sur SymfonyMailer ou PHPmailer qui sont des solution php native et souveraines.

Si vous avez un besoin spécifique [n'hésitez pas à me contacter](mailto:contact@gael-berru.com)






