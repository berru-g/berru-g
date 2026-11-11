# /shop/ — Boutique créations électroniques (Berru)

Stack : **PHP vanilla + MySQL + Stripe Payment Links**. Zéro framework, zéro composer, zéro dépendance — compatible mutualisé classique (MAMP/oVH/etc.), conforme à la doctrine du site.

## Contenu

| Fichier | Rôle |
|---|---|
| `index.php` | Page catalogue : fiches produits lues depuis MySQL, bouton « Commander » → lien Stripe |
| `config.php` | PDO + clés Stripe (à remplir, puis à ne JAMAIS committer) |
| `webhook.php` | Reçoit `checkout.session.completed` de Stripe, vérifie la signature (HMAC maison), enregistre la commande + décrémente le stock |
| `database.sql` | Schéma + les 4 fiches produits avec les IDs Stripe LIVE réels |

        Stock à gérer de la bdd direct
        Prix a gerer de la bdd et stripe

## Fiches Stripe déjà créées (compte « mr », mode live, TVA incluse)

| Produit | Prix TTC | Payment Link |
|---|---|---|
| NFC Badge (NT3H2111) | 25,00 € | https://buy.stripe.com/6oU3cu93P5t7bmP0XT8og00 |
| RC Motor — kit radio 2 km | 90,00 € | https://buy.stripe.com/fZu00i6VH08N1Mf8ql8og01 |
| MIDI USB-C contrôleur DAW | 59,00 € | https://buy.stripe.com/5kQ00i3Jv08N3Unayt8og02 |
| Shield Arduino UNO LEGO | 19,00 € | https://buy.stripe.com/3cI28qbbX1cRez1gWR8og03 |

TVA : Stripe Tax activé (`automatic_tax` sur les liens, prix `inclusive`). **⚠️ Ajouter l'enregistrement TVA France dans Dashboard → Tax → Registrations** pour que la TVA 20 % soit réellement calculée.

## Déploiement (5 minutes)

1. **BDD** : importer `database.sql` (phpMyAdmin ou `mysql -u USER -p < database.sql`).
2. **config.php** : renseigner `DB_USER`, `DB_PASS`, `STRIPE_SECRET_KEY` (sk_live_…).
3. Envoyer le dossier `shop/` sur le serveur → `https://gael-berru.com/shop/`.
4. **Webhook Stripe** : Dashboard → Développeurs → Webhooks → *Add endpoint*
   - URL : `https://gael-berru.com/shop/webhook.php`
   - Événement : `checkout.session.completed`
   - Copier le `whsec_…` dans `STRIPE_WEBHOOK_SECRET`.
5. Tester un paiement réel avec un petit montant (Shield LEGO 19 € est le bon cobaye).

## Sécurité

- Signature du webhook vérifiée en HMAC-SHA256 natif (tolérance anti-replay 300 s), sans lib Stripe.
- Idempotence sur `stripe_session_id` (un paiement = une commande, jamais de double écriture).
- Aucune donnée carte ne transite par le site (Stripe héberge tout le checkout).
- `config.php` à exclure du repo : ajouter `shop/config.php` au `.gitignore`.

## Personnalisation

- Ajouter un produit : 1 fiche dans Stripe (produit + prix TTC `inclusive` + Payment Link `automatic_tax: enabled`), puis 1 `INSERT` dans la table `products` avec les IDs obtenus.
- Les stocks sont décrémentés automatiquement par le webhook ; 0 = « fabrication sur commande ».