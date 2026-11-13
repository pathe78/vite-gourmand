<?php

namespace App\Command;

use App\Entity\Allergene;
use App\Entity\Avis;
use App\Entity\Commande;
use App\Entity\Horaire;
use App\Entity\Menu;
use App\Entity\Plat;
use App\Entity\Regime;
use App\Entity\Theme;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Remplit la base avec un jeu de données de démonstration.
 * Usage : php bin/console app:seed-demo
 * Ne fait rien si des menus existent déjà (sauf avec --force sur une base vide de commandes).
 */
#[AsCommand(name: 'app:seed-demo', description: 'Insère les données de démonstration (menus, plats, comptes de test...)')]
class SeedDemoCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('if-empty', null, InputOption::VALUE_NONE, 'Ne rien faire si la base contient déjà des menus');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->em->getRepository(Menu::class)->count([]) > 0) {
            $io->note('La base contient déjà des données : rien à faire.');
            return Command::SUCCESS;
        }

        // ── Référentiels ──
        $themes = [];
        foreach (['Classique', 'Noël', 'Pâques', 'Événement'] as $libelle) {
            $themes[$libelle] = (new Theme())->setLibelle($libelle);
            $this->em->persist($themes[$libelle]);
        }

        $regimes = [];
        foreach (['Classique', 'Végétarien', 'Vegan'] as $libelle) {
            $regimes[$libelle] = (new Regime())->setLibelle($libelle);
            $this->em->persist($regimes[$libelle]);
        }

        $allergenes = [];
        foreach (['Gluten', 'Lait', 'Œufs', 'Fruits à coque', 'Poisson', 'Crustacés', 'Soja', 'Céleri', 'Moutarde', 'Sésame'] as $libelle) {
            $allergenes[$libelle] = (new Allergene())->setLibelle($libelle);
            $this->em->persist($allergenes[$libelle]);
        }

        // ── Plats : [titre, type, allergènes] ──
        $platsData = [
            'Velouté de potimarron'          => ['Entrée', ['Lait', 'Céleri']],
            'Foie gras maison, chutney figue' => ['Entrée', ['Gluten']],
            'Salade de chèvre chaud'          => ['Entrée', ['Lait', 'Gluten', 'Fruits à coque']],
            'Houmous et crudités'             => ['Entrée', ['Sésame']],
            'Saumon gravlax à l\'aneth'       => ['Entrée', ['Poisson', 'Moutarde']],
            'Dinde farcie aux marrons'        => ['Plat', ['Gluten', 'Œufs']],
            'Gigot d\'agneau de 7 heures'     => ['Plat', []],
            'Magret de canard, sauce cèpes'   => ['Plat', ['Lait']],
            'Risotto aux champignons'         => ['Plat', ['Lait', 'Céleri']],
            'Curry de légumes au lait de coco' => ['Plat', ['Soja']],
            'Dos de cabillaud, beurre blanc'  => ['Plat', ['Poisson', 'Lait']],
            'Bûche chocolat praliné'          => ['Dessert', ['Gluten', 'Lait', 'Œufs', 'Fruits à coque']],
            'Nid de Pâques au chocolat'       => ['Dessert', ['Lait', 'Œufs']],
            'Tarte fine aux pommes'           => ['Dessert', ['Gluten', 'Lait']],
            'Salade de fruits frais'          => ['Dessert', []],
            'Canelés bordelais'               => ['Dessert', ['Gluten', 'Lait', 'Œufs']],
        ];
        $plats = [];
        foreach ($platsData as $titre => [$type, $alls]) {
            $plat = (new Plat())->setTitre($titre)->setTypePlat($type);
            foreach ($alls as $a) {
                $plat->addAllergene($allergenes[$a]);
            }
            $this->em->persist($plat);
            $plats[$titre] = $plat;
        }

        // ── Menus : [titre, thème, régime, min pers, prix, stock, description, plats] ──
        $menusData = [
            ['Menu Tradition Bordelaise', 'Classique', 'Classique', 6, 32.0, 15,
                'Les grands classiques de notre maison : magret, canelés et produits du Sud-Ouest.',
                ['Salade de chèvre chaud', 'Magret de canard, sauce cèpes', 'Canelés bordelais']],
            ['Menu de Noël Prestige', 'Noël', 'Classique', 8, 45.0, 10,
                'Un réveillon raffiné, du foie gras maison à la bûche chocolat praliné.',
                ['Foie gras maison, chutney figue', 'Dinde farcie aux marrons', 'Bûche chocolat praliné']],
            ['Menu de Pâques en Famille', 'Pâques', 'Classique', 6, 38.0, 12,
                'Le gigot de 7 heures et son dessert gourmand pour un déjeuner de Pâques convivial.',
                ['Saumon gravlax à l\'aneth', 'Gigot d\'agneau de 7 heures', 'Nid de Pâques au chocolat']],
            ['Menu Végétarien du Marché', 'Classique', 'Végétarien', 4, 27.0, 20,
                'Une cuisine de saison sans viande ni poisson, généreuse et colorée.',
                ['Velouté de potimarron', 'Risotto aux champignons', 'Tarte fine aux pommes']],
            ['Menu Vegan Gourmand', 'Événement', 'Vegan', 4, 29.0, 8,
                '100 % végétal : houmous maison, curry coco et fruits frais.',
                ['Houmous et crudités', 'Curry de légumes au lait de coco', 'Salade de fruits frais']],
            ['Menu Cocktail Entreprise', 'Événement', 'Classique', 15, 24.0, 5,
                'Idéal pour vos séminaires et soirées d\'entreprise.',
                ['Saumon gravlax à l\'aneth', 'Dos de cabillaud, beurre blanc', 'Canelés bordelais']],
        ];
        $menus = [];
        foreach ($menusData as [$titre, $theme, $regime, $min, $prix, $stock, $desc, $platsMenu]) {
            $menu = (new Menu())
                ->setTitre($titre)
                ->setTheme($themes[$theme])
                ->setRegime($regimes[$regime])
                ->setNombrePersonneMinimum($min)
                ->setPrixParPersonne($prix)
                ->setQuantiteRestante($stock)
                ->setDescription($desc);
            foreach ($platsMenu as $p) {
                $menu->addPlat($plats[$p]);
            }
            $this->em->persist($menu);
            $menus[] = $menu;
        }

        // ── Horaires ──
        foreach ([
            ['Lundi', '09:00', '19:00'], ['Mardi', '09:00', '19:00'], ['Mercredi', '09:00', '19:00'],
            ['Jeudi', '09:00', '19:00'], ['Vendredi', '09:00', '19:00'], ['Samedi', '09:00', '17:00'],
            ['Dimanche', '10:00', '14:00'],
        ] as [$jour, $ouv, $ferm]) {
            $this->em->persist((new Horaire())->setJour($jour)->setHeureOuverture($ouv)->setHeureFermeture($ferm));
        }

        // ── Comptes de test ──
        $admin = $this->creerUtilisateur('admin@vitegourmand.fr', 'Admin1234!@', 'José', 'Martin', ['ROLE_ADMIN']);
        $employe = $this->creerUtilisateur('employe@vitegourmand.fr', 'Employe1234!@', 'Julie', 'Bernard', ['ROLE_EMPLOYE']);
        $client = $this->creerUtilisateur('test@test.com', 'Test1234!@', 'Sophie', 'Dupont', ['ROLE_UTILISATEUR']);

        // ── Quelques commandes (pour les espaces client / employé) ──
        $commandeTerminee = $this->creerCommande($client, $menus[0], 8, '-20 days', 'termine');
        $this->creerCommande($client, $menus[3], 6, '+10 days', 'en_attente');
        $this->creerCommande($client, $menus[1], 10, '+30 days', 'accepte');

        $avis = (new Avis())
            ->setNote(5)
            ->setDescription('Repas délicieux et livraison à l\'heure, nos invités ont adoré les canelés !')
            ->setStatut('valide')
            ->setDateAvis(new \DateTime('-15 days'))
            ->setUtilisateur($client)
            ->setCommande($commandeTerminee);
        $this->em->persist($avis);

        $this->em->flush();

        $io->success('Données de démonstration insérées (6 menus, 16 plats, 3 comptes de test).');

        return Command::SUCCESS;
    }

    private function creerUtilisateur(string $email, string $password, string $prenom, string $nom, array $roles): Utilisateur
    {
        $u = (new Utilisateur())
            ->setEmail($email)
            ->setPrenom($prenom)
            ->setNom($nom)
            ->setTelephone('0556000000')
            ->setAdresse('10 cours de l\'Intendance')
            ->setVille('Bordeaux')
            ->setCodePostal('33000')
            ->setRoles($roles);
        $u->setPassword($this->hasher->hashPassword($u, $password));
        $this->em->persist($u);

        return $u;
    }

    private function creerCommande(Utilisateur $client, Menu $menu, int $personnes, string $date, string $statut): Commande
    {
        $prix = $personnes * $menu->getPrixParPersonne();
        if ($personnes >= $menu->getNombrePersonneMinimum() + 5) {
            $prix *= 0.90;
        }

        $c = (new Commande())
            ->setNumeroCommande('CMD-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6)))
            ->setDateCommande(new \DateTime($date . ' -7 days'))
            ->setDatePrestation(new \DateTime($date))
            ->setHeureLivraison('12:00')
            ->setNombrePersonne($personnes)
            ->setPrixMenu($prix)
            ->setPrixLivraison(0)
            ->setStatut($statut)
            ->setPretMateriel(false)
            ->setRestitionMateriel(false)
            ->setAdresseLivraison('10 cours de l\'Intendance, 33000 Bordeaux')
            ->setUtilisateur($client)
            ->setMenu($menu);
        $this->em->persist($c);

        return $c;
    }
}
