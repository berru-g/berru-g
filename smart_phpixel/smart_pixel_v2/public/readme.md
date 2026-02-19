# **Tutoriel : Utiliser l’API Smart Pixel Analytics**
*Alternative open-source à Google Analytics*

---

## **🔑 Prérequis**
- Un **compte Smart Pixel** (gratuit pour 1 site).
- Une **clé API** (disponible dans ton [tableau de bord](https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/account.php)).
- Le **code de tracking** de ton site (ex: `SP_7f9505cc`).

---

## **📌 1. Récupérer ta clé API et ton code de tracking**
### **Étape 1 : Accède à ton compte**
1. Connecte-toi à ton [tableau de bord Smart Pixel](https://gael-berru.com/smart_phpixel/smart_pixel_v2/dashboard.php).
2. Clique sur **"Mon compte"** dans le menu.

   ![Exemple de menu](https://via.placeholder.com/600x200/4a6bff/ffffff?text=Menu+Smart+Pixel)

### **Étape 2 : Copie ta clé API**
- Dans la section **"Clé API"**, clique sur l’icône **🖉** pour copier ta clé.
- **Ne partage jamais cette clé** (elle donne accès à tes données).

   ![Exemple de clé API](https://via.placeholder.com/600x300/4a6bff/ffffff?text=Cl%C3%A9+API%3A+sk_1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p)

### **Étape 3 : Récupère ton code de tracking**
1. Va dans **"Mes sites"** dans le menu.
2. Copie le **code de tracking** (ex: `SP_7f9505cc`).

   ![Exemple de code de tracking](https://via.placeholder.com/600x200/4a6bff/ffffff?text=Code+de+tracking%3A+SP_7f9505cc)

---

## **🔗 2. Construire l’URL de l’API**
L’URL de base pour accéder à tes données est :
```
https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/api.php
```

### **Paramètres obligatoires**
| Paramètre     | Description                          | Exemple                     |
|---------------|--------------------------------------|-----------------------------|
| `site_id`     | Code de tracking de ton site.        | `SP_7f9505cc`               |
| `api_key`     | Ta clé API (copiée plus tôt).        | `sk_1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p` |

### **Paramètres optionnels**
| Paramètre     | Description                          | Exemple       | Défaut          |
|---------------|--------------------------------------|---------------|-----------------|
| `start_date`  | Date de début (format `AAAA-MM-JJ`). | `2026-01-01`  | Il y a 7 jours  |
| `end_date`    | Date de fin (format `AAAA-MM-JJ`).   | `2026-02-01`  | Aujourd’hui     |

### **Exemple d’URL complète**
```
https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/api.php?
site_id=SP_7f9505cc&
api_key=sk_1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p&
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
curl "https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/api.php?
site_id=SP_7f9505cc&
api_key=sk_1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p&
start_date=2026-01-01&
end_date=2026-02-01"
```

### **Méthode 3 : Avec JavaScript (fetch)**
```javascript
const siteId = 'SP_7f9505cc';
const apiKey = 'sk_1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p';
const startDate = '2026-01-01';
const endDate = '2026-02-01';

fetch(`https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/api.php?
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
    "site_id": "SP_7f9505cc",
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
     =IMPORTDATA("https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/api.php?site_id=SP_7f9505cc&api_key=sk_1a2b3c...")
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
  <h1>Visites par jour</h1>
  <canvas id="visitsChart" width="800" height="400"></canvas>

  <script>
    const siteId = 'SP_7f9505cc';
    const apiKey = 'sk_1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p';
    const startDate = '2026-01-01';
    const endDate = '2026-02-01';

    fetch(`https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/api.php?
      site_id=${siteId}&
      api_key=${apiKey}&
      start_date=${startDate}&
      end_date=${endDate}`)
      .then(response => response.json())
      .then(data => {
        const labels = data.data.map(item => item.date);
        const visits = data.data.map(item => item.visits);
        new Chart(document.getElementById('visitsChart'), {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: 'Visites',
              data: visits,
              borderColor: '#4a6bff',
              backgroundColor: 'rgba(74, 107, 255, 0.1)',
              tension: 0.3
            }]
          },
          options: {
            responsive: true,
            scales: {
              y: { beginAtZero: true }
            }
          }
        });
      });
  </script>
</body>
</html>
```

---
## **⚠️ 6. Gérer les erreurs**
| Code d’erreur | Cause probable                          | Solution                                  |
|---------------|-----------------------------------------|-------------------------------------------|
| `400`         | Paramètres manquants (`site_id` ou `api_key`). | Vérifie l’URL.                           |
| `403`         | Clé API ou code de tracking invalide.   | Vérifie tes identifiants dans "Mon compte". |
| `404`         | Site non trouvé.                        | Vérifie que le `site_id` est correct.      |
| `500`         | Erreur serveur.                         | Contacte le support (avec le message d’erreur). |

---
## **🔄 7. Régénérer ta clé API**
Si ta clé API est compromise :
1. Va dans **"Mon compte"**.
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
  site_id=SP_7f9505cc&
  api_key=sk_1a2b3c...&
  start_date=${formattedStartDate}&
  end_date=${today}`)
```

### **B. Agrégat par pays**
Modifie l’URL pour inclure des données géographiques :
```
https://gael-berru.com/.../api.php?
site_id=SP_7f9505cc&
api_key=sk_1a2b3c...&
group_by=country
```
*(À implémenter côté serveur si besoin.)*

---
## **📢 9. Support et contact**
- **Problème technique** ? Ouvre un ticket via [le formulaire de contact](https://gael-berru.com/smart_phpixel/smart_pixel_v2/public/contact.php).
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