# Guide Utilisateur

Bienvenue sur Libre Analytics, l'alternative française simple et respectueuse à Google Analytics. 
Ce guide vous aidera à installer, configurer et utiliser votre tableau de bord analytics dans son plein potentiel.

---

## Premiers pas

### 1. Créer votre compte gratuit
Rendez-vous sur [https://gael-berru.com/LibreAnalytics/](https://gael-berru.com/LibreAnalytics/) et cliquez sur **"CRÉER MON PREMIER DASHBOARD"**.

Vous aurez besoin de :
- Votre email
- Crée un mot de passe
- L'URL de votre site

✅ Le premier dashboard est gratuit.

### 2. Récupérer votre code de tracking
Une fois connecté, votre tableau de bord affiche votre **code d'intégration** Ewemple : 

```html
<script data-sp-id="SP_24031987" 
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

<img src="https://gael-berru.com/img/LibreAnalytics-dashboard.png" style="width:90%; display:flex;margin:20px auto; border-radius: 12px;">

### **Vue d'ensemble**
- **Visites totales** : nombre de pages vues
- **Visiteurs uniques** : comptés par adresse IP
- **Sources de trafic** : d'où viennent vos visiteurs
- **Évolution** : graphique sur un an

### **Géolocalisation**
- Carte interactive des pays visiteurs
- Top 10 des pays
- Villes principales

### **Device**
- Types d'appareils
- Naviguateur utilisé

## **Contenue**
- Pages consultées
- Sous domaine consultées ( parametre utm )
- Données de clics récentes

### **Détails**
- Liste complète des dernières visites
- Adresses IP (anonymisées)
- Pages visitées
- Horodatage
- Ville
- Pays

### **Insight**
- Analyse des Tendances
- Points d'améliorations

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
Ajoutez ces paramètres à vos URLs lors de partage ou backlink:

```
https://votre-site.fr?utm_source=facebook&utm_medium=social&utm_campaign=ete2026
```

### Paramètres disponibles
- `utm_source` : d'où vient le trafic (facebook, newsletter, google)
- `utm_medium` : le support (social, email, cpc)
- `utm_campaign` : nom de votre campagne (promo_ete, lancement)

👉 Ces données apparaîtront dans la colonne "Contenue" de votre tableau de bord.

---

##  Gérer plusieurs sites

Le plan gratuit vous permet de suivre **1 site**. Pour ajouter un site :

1. Dans la barre latérale, cliquez sur **"Ajouter un site"**
2. Donnez un nom à votre site
3. Entrez l'URL
4. Récupérez le nouveau code de tracking
5. Votre Api Key

Chaque site a son propre **tracking code** (ex: `SP_24031987`). Installez le code correspondant sur chaque site. Votre clef api est valable pour tout vos Tracking code.


## **Tester l’API** 

### Ajoutez votre site id avant votre token et insérez vos requete entre les deux !
    URL d’exemple :
    https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php?site_id=SP_24031987&start_date=2026-01-01&end_date=2026-02-01&api_key=TON_TOKEN


### Intégrer avec ton dashboard ou outils externes. Depuis un script JS :

```html
    fetch(`https://ton-domaine.com/smart_pixel_v2/public/api.php?site_id=SP_24031987&start_date=2026-01-01&end_date=2026-02-01&api_key=TON_TOKEN`)
    .then(response => response.json())
    .then(data => console.log(data));

```