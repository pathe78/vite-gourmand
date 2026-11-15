# 🍽️ Vite & Gourmand

Application web d'un traiteur bordelais : consultation des menus, commande en ligne, suivi des commandes, avis clients, espace employé et espace administrateur avec statistiques.

Projet réalisé dans le cadre du **Titre Professionnel Développeur Web et Web Mobile** (Studi).

| | Lien |
|---|---|
| Site en ligne (front) | https://vite-gourmand-front.vercel.app |
| API en ligne (back) | https://pathe78.alwaysdata.net/api |

---

## 📁 Structure du dépôt

```
vite-gourmand/
├── front/        ← Front-end : HTML5, SCSS/Bootstrap 5, JavaScript (routeur maison, sans framework)
├── back/         ← API REST : Symfony 6.4 (PHP 8.2), Doctrine, JWT, MySQL + MongoDB
├── database/     ← Scripts SQL : création de la base + données de démonstration
└── README.md
```

---

## 🛠️ Stack technique

| Catégorie | Technologie |
|---|---|
| Front-end | HTML5, SCSS, Bootstrap 5, JavaScript ES6 (modules), Chart.js |
| Back-end | PHP 8.2, Symfony 6.4 |
| ORM | Doctrine ORM + Doctrine Migrations |
| Base relationnelle | MySQL 8 (MariaDB en local avec XAMPP) |
| Base NoSQL | MongoDB Atlas (statistiques des commandes) |
| Authentification | JWT — LexikJWTAuthenticationBundle |
| Déploiement | Vercel (front), alwaysdata (API + MariaDB), MongoDB Atlas |
| Outils | VS Code, Git/GitHub, Composer, npm, Postman |

---

## 📋 Prérequis (installation locale)

- [XAMPP](https://www.apachefriends.org/) (PHP ≥ 8.2 et MySQL/MariaDB)
- Extension PHP **mongodb** activée (DLL disponible sur [pecl.php.net/package/mongodb](https://pecl.php.net/package/mongodb), à ajouter dans `php.ini` : `extension=mongodb`)
- [Composer](https://getcomposer.org/), [Symfony CLI](https://symfony.com/download), [Node.js](https://nodejs.org/) ≥ 18, [Git](https://git-scm.com/)

---

## 🚀 Installation locale

### 1. Cloner le dépôt

```bash
git clone https://github.com/pathe78/vite-gourmand.git
cd vite-gourmand
```

### 2. Back-end (API Symfony)

```bash
cd back
composer install
```

Créer le fichier de configuration local (jamais versionné) :

```bash
cp .env.local.example .env.local
```

Puis renseigner dans `back/.env.local` : `DATABASE_URL`, `JWT_PASSPHRASE`, `APP_SECRET` et l'URL MongoDB Atlas (`MONGODB_URL`).

Générer les clés JWT (elles ne sont jamais dans Git) :

```bash
php bin/console lexik:jwt:generate-keypair
```

### 3. Base de données

**Option A — avec Doctrine (recommandé)**

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console app:seed-demo
```

**Option B — avec les scripts SQL** (phpMyAdmin → Importer, dans cet ordre) :

1. `database/01_creation_base.sql` — crée la base `vite_gourmand` et ses tables
2. `database/02_donnees_demo.sql` — insère les menus, plats, horaires et comptes de test

### 4. Lancer l'API

```bash
symfony server:start --no-tls
```

L'API répond sur `http://127.0.0.1:8000/api` (ex. : `http://127.0.0.1:8000/api/menus`).

### 5. Front-end

```bash
cd ../front
npm install
npm start
```

Ouvrir `http://127.0.0.1:5500`. (Live Server de VS Code fonctionne aussi, sur le port 5500.)

Pour modifier les styles : `npm run scss` (compile `scss/main.scss` en `scss/main.css` à chaque sauvegarde).

---

## 🔑 Comptes de test

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur | admin@vitegourmand.fr | Admin1234!@ |
| Employé | employe@vitegourmand.fr | Employe1234!@ |
| Utilisateur | test@test.com | Test1234!@ |

---

## ☁️ Déploiement

| Partie | Hébergeur | Pourquoi |
|---|---|---|
| Front-end | **Vercel** (gratuit) | Site statique servi par CDN, HTTPS, déploiement automatique à chaque push |
| API + base MariaDB | **alwaysdata** (gratuit, France) | Toujours disponible (pas de mise en veille), même SGBD qu'en local, accès SSH + Composer, données en France (RGPD) |
| Statistiques | **MongoDB Atlas** (M0 gratuit) | Base NoSQL managée |

### API sur alwaysdata — https://pathe78.alwaysdata.net/api

1. **Environnement → PHP** : choisir PHP 8.2 ou plus.
2. **Bases de données → MySQL** : créer la base `pathe78_vitegourmand` (hôte `mysql-pathe78.alwaysdata.net`).
3. **Accès distant → SSH** : activer la connexion par mot de passe, puis se connecter (ou utiliser le SSH web du panneau).
4. En SSH :

   ```bash
   git clone https://github.com/pathe78/vite-gourmand.git
   ad_install_pecl mongodb          # compile l'extension PHP MongoDB
   ```

   Ajouter la ligne `extension = /home/pathe78/mongodb.so` (chemin affiché par la commande) dans **Environnement → PHP → php.ini**.

5. Créer `~/vite-gourmand/back/.env.local` (jamais versionné) :

   ```dotenv
   APP_ENV=prod
   APP_DEBUG=0
   APP_SECRET=une_longue_chaine_aleatoire
   DATABASE_URL="mysql://pathe78:MOT_DE_PASSE@mysql-pathe78.alwaysdata.net:3306/pathe78_vitegourmand?serverVersion=10.11.0-MariaDB&charset=utf8mb4"
   JWT_PASSPHRASE=une_phrase_secrete
   MONGODB_URL="mongodb+srv://UTILISATEUR:MOT_DE_PASSE@cluster0.xxxxx.mongodb.net/?appName=Cluster0"
   MONGODB_DB=vite_gourmand_stats
   CORS_ALLOW_ORIGIN='^https://vite-gourmand-front\.vercel\.app$'
   ```

6. Lancer le script de mise en production : `bash ~/vite-gourmand/back/bin/deploy.sh`
   (dépendances, clés JWT, migrations, données de démonstration, cache). Le même script sert pour chaque mise à jour.
7. **Web → Sites** : modifier le site `pathe78.alwaysdata.net`, type **PHP**, racine `/vite-gourmand/back/public`.
8. MongoDB Atlas → **Network Access** : autoriser `0.0.0.0/0`.

Le fichier `back/public/.htaccess` envoie toutes les requêtes vers Symfony et transmet l'en-tête `Authorization` (token JWT) à PHP.

### Front sur Vercel

**Add New → Project** → dépôt `vite-gourmand` → **Root Directory** = `front` → Deploy.
L'URL de l'API de production est définie dans `front/js/config.js`. Le fichier `front/vercel.json` renvoie toutes les URL vers `index.html` pour le routeur JavaScript.

### Alternative : Docker

Le dossier `back/` contient aussi un `Dockerfile` (PHP 8.2 + Apache) et `back/docker/start.sh`, qui permettent de déployer l'API sur un hébergeur de conteneurs (Railway, Render…).

---

## 🌐 Routes de l'API

| Méthode | Route | Description | Accès |
|---|---|---|---|
| POST | `/api/login` | Connexion (renvoie un token JWT) | Public |
| POST | `/api/inscription` | Créer un compte | Public |
| GET | `/api/menus` | Liste des menus (filtres : `prixMin`, `prixMax`, `theme`, `regime`, `personnes`) | Public |
| GET | `/api/menus/{id}` | Détail d'un menu avec ses plats et allergènes | Public |
| GET | `/api/avis` | Avis validés | Public |
| GET | `/api/horaires` | Horaires d'ouverture | Public |
| POST | `/api/contact` | Envoyer un message | Public |
| GET / PUT | `/api/compte/profil` | Voir / modifier son profil | Connecté |
| POST | `/api/commandes` | Passer une commande | Connecté |
| GET | `/api/commandes` | Mes commandes | Connecté |
| PUT | `/api/commandes/{id}/annuler` | Annuler sa commande (si en attente) | Connecté |
| POST | `/api/avis` | Donner un avis (commande terminée) | Connecté |
| GET | `/api/employe/commandes` | Toutes les commandes (filtres `statut`, `client`) | Employé |
| PUT | `/api/employe/commandes/{id}/statut` | Changer le statut / annuler avec motif | Employé |
| GET | `/api/employe/avis` | Avis en attente | Employé |
| PUT | `/api/employe/avis/{id}` | Valider / refuser un avis | Employé |
| PUT / DELETE | `/api/employe/menus/{id}` | Modifier / supprimer un menu | Employé |
| PUT | `/api/employe/horaires/{id}` | Modifier un horaire | Employé |
| GET / POST | `/api/admin/employes` | Lister / créer des employés | Admin |
| PUT | `/api/admin/employes/{id}/desactiver` · `/activer` | Désactiver / réactiver un employé | Admin |
| GET | `/api/admin/stats/commandes` | Commandes par menu (MongoDB) | Admin |
| GET | `/api/admin/stats/chiffre-affaires` | Chiffre d'affaires par période (MongoDB) | Admin |

Un administrateur hérite des droits employé, un employé de ceux d'un utilisateur (`role_hierarchy`).

---

## 🔒 Sécurité (résumé)

- Mots de passe hachés (algorithme `auto` de Symfony : bcrypt/argon2), règle de complexité de 10 caractères minimum
- API sans session (stateless) avec tokens JWT signés par clé RSA, valables 1 heure
- Contrôle d'accès par rôle (`access_control`) et vérification que la commande ou l'avis appartient à l'utilisateur
- Comptes employés désactivables (`UserChecker` : connexion refusée et token rejeté)
- Requêtes paramétrées avec Doctrine (protection contre l'injection SQL)
- Échappement des données affichées côté front (`escapeHtml`) contre les failles XSS
- CORS limité aux domaines du front
- Aucun secret dans Git : `.env.local` et les clés JWT sont ignorés, et la production utilise des variables d'environnement

---

## 🌿 Organisation Git

- `main` : version stable, déployée
- `develop` : intégration des fonctionnalités
- `feature/*` : une branche par fonctionnalité, fusionnée dans `develop`, puis `develop` dans `main` pour une mise en production
