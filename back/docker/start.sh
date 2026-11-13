#!/bin/bash
# Script de démarrage du conteneur (exécuté à chaque déploiement Railway)
set -e

# Railway impose le port d'écoute via la variable PORT
export PORT="${PORT:-8080}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf

cd /var/www/html

# 0. Variables d'environnement : Apache (mod_php) ne transmet pas celles de Railway à PHP.
#    On les "compile" dans .env.local.php, que Symfony lit en priorité sur .env
php docker/env-to-php.php

# 1. Clés JWT : générées au premier démarrage (elles ne sont jamais dans Git)
php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction

# 2. Base de données : création des tables, puis données de démonstration si la base est vide
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console app:seed-demo --no-interaction

# 3. Cache de production
php bin/console cache:clear --no-interaction
php bin/console cache:warmup --no-interaction

chown -R www-data:www-data var config/jwt

exec apache2-foreground
