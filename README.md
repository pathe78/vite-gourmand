# 🍽️ Vite & Gourmand

Application web d'un traiteur bordelais : consultation des menus, commande en ligne, suivi des commandes, avis clients, espace employé et espace administrateur avec statistiques.

Projet réalisé dans le cadre du **Titre Professionnel Développeur Web et Web Mobile** (Studi).

| | Lien |
|---|---|
| Site en ligne (front) | _à compléter après déploiement Vercel_ |
| API en ligne (back) | _à compléter après déploiement Railway_ |

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
| Déploiement | Vercel (front), Railway + Docker (API + MySQL) |
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

### API + MySQL sur Railway

1. Sur [railway.app](https://railway.app) : **New Project → Deploy from GitHub repo** → choisir ce dépôt.
2. Dans le service créé : **Settings → Root Directory** = `back`. Railway détecte le `Dockerfile`.
3. Dans le projet : **+ New → Database → MySQL**.
4. Dans le service de l'API, onglet **Variables** :

   | Variable | Valeur |
   |---|---|
   | `DATABASE_URL` | `${{MySQL.MYSQL_URL}}?serverVersion=8.0&charset=utf8mb4` |
   | `APP_SECRET` | une longue chaîne aléatoire |
   | `JWT_PASSPHRASE` | une phrase secrète |
   | `MONGODB_URL` | l'URL de connexion MongoDB Atlas |
   | `MONGODB_DB` | `vite_gourmand_stats` |
   | `CORS_ALLOW_ORIGIN` | `^https://vite-gourmand.*\.vercel\.app$` (adapter au domaine Vercel) |

5. **Settings → Networking → Generate Domain**, avec le port **8080**.

Au démarrage, le conteneur génère les clés JWT, crée les tables (migrations) et insère les données de démonstration si la base est vide (voir `back/docker/start.sh`).

> MongoDB Atlas : dans **Network Access**, autoriser `0.0.0.0/0` pour que Railway puisse se connecter.

### Front sur Vercel

1. Dans `front/js/config.js`, remplacer `API_PRODUCTION` par l'URL Railway suivie de `/api`, puis committer.
2. Sur [vercel.com](https://vercel.com) : **Add New → Project** → choisir ce dépôt → **Root Directory** = `front` → Deploy.

Le fichier `front/vercel.json` renvoie toutes les URL vers `index.html` pour que le routeur JavaScript fonctionne.

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
