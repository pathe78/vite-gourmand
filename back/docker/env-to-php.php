<?php
/**
 * Génère .env.local.php à partir de .env + des VRAIES variables d'environnement
 * (celles définies dans Railway). Symfony lit ce fichier en priorité.
 *
 * Pourquoi ? Sous Apache (mod_php), PHP ne voit pas les variables d'environnement
 * du conteneur : sans ce fichier, Symfony utiliserait les valeurs par défaut de .env.
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$projet = dirname(__DIR__);

// 1. Valeurs par défaut du fichier .env
$valeurs = (new Dotenv())->parse(file_get_contents($projet . '/.env'));

// 2. Les variables définies sur le serveur remplacent les valeurs par défaut
foreach (array_keys($valeurs) as $nom) {
    $reelle = getenv($nom);
    if ($reelle !== false && $reelle !== '') {
        $valeurs[$nom] = $reelle;
    }
}

$valeurs['APP_ENV'] = 'prod';
$valeurs['APP_DEBUG'] = '0';

file_put_contents(
    $projet . '/.env.local.php',
    "<?php\n\n// Fichier généré au démarrage par docker/env-to-php.php — ne pas versionner\nreturn " . var_export($valeurs, true) . ";\n"
);

echo "[OK] .env.local.php généré (" . count($valeurs) . " variables)\n";
