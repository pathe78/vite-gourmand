#!/bin/bash
# ============================================================
#  Mise en production sur alwaysdata (à lancer en SSH)
#  Usage : bash ~/vite-gourmand/back/bin/deploy.sh
#  Prérequis : back/.env.local rempli avec les valeurs de production
# ============================================================
set -e
cd "$(dirname "$0")/.."

echo "→ Récupération de la dernière version (branche main)"
git pull origin main

echo "→ Dépendances PHP (sans les outils de développement)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "→ Clés JWT (créées une seule fois)"
php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction

echo "→ Base de données : migrations puis données de démonstration si la base est vide"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console app:seed-demo --no-interaction

echo "→ Cache de production"
php bin/console cache:clear --no-interaction

echo "✅ Déploiement terminé"
