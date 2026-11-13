# ============================================================
#  Vite & Gourmand — image de production de l'API (Railway)
#  PHP 8.2 + Apache + extensions MySQL et MongoDB
# ============================================================
FROM php:8.2-apache

# Dépendances système + extensions PHP (pdo_mysql pour MySQL, mongodb pour les statistiques)
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libssl-dev pkg-config \
    && docker-php-ext-install pdo_mysql intl opcache \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache : dossier public/ de Symfony, réécriture vers index.php, en-tête Authorization transmis à PHP (JWT)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite headers

WORKDIR /var/www/html

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1

# Dépendances PHP d'abord (meilleur cache Docker), puis le code
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress

COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && mkdir -p var config/jwt \
    && chmod -R a+rX /var/www/html \
    && chown -R www-data:www-data var config/jwt

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 8080
CMD ["/usr/local/bin/start.sh"]
