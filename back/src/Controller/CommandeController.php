<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Repository\AvisRepository;
use App\Repository\CommandeRepository;
use App\Repository\MenuRepository;
use App\Service\MongoDBService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/api')]
final class CommandeController extends AbstractController
{
    // ─ CREER UNE COMMANDE 
    #[Route('/commandes', name: 'app_commande_create', methods: ['POST'])]
    public function create(
    Request $request,
    EntityManagerInterface $em,
    MenuRepository $menuRepository,
    MongoDBService $mongoService
): JsonResponse {

        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            return $this->json([
                'success' => false,
                'message' => 'Vous devez être connecté pour commander.'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        // Vérification des champs obligatoires
        if (
            empty($data['menuId']) ||
            empty($data['datePrestation']) ||
            empty($data['heureLivraison']) ||
            empty($data['nombrePersonne'])
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Tous les champs sont obligatoires.'
            ], 400);
        }

        // Récupération du menu
        $menu = $menuRepository->find($data['menuId']);
        if (!$menu) {
            return $this->json([
                'success' => false,
                'message' => 'Menu introuvable.'
            ], 404);
        }

        // La date de prestation doit être valide et dans le futur
        try {
            $datePrestation = new \DateTime($data['datePrestation']);
        } catch (\Exception) {
            return $this->json(['success' => false, 'message' => 'Date de prestation invalide.'], 400);
        }
        if ($datePrestation < new \DateTime('today')) {
            return $this->json(['success' => false, 'message' => 'La date de prestation doit être dans le futur.'], 400);
        }

        // Vérification du nombre de personnes minimum
        $nombrePersonne = (int) $data['nombrePersonne'];
        if ($nombrePersonne < $menu->getNombrePersonneMinimum()) {
            return $this->json([
                'success' => false,
                'message' => 'Le nombre de personnes minimum est ' . $menu->getNombrePersonneMinimum() . '.'
            ], 400);
        }

        // Vérification du stock
        if ($menu->getQuantiteRestante() <= 0) {
            return $this->json([
                'success' => false,
                'message' => 'Ce menu n\'est plus disponible.'
            ], 400);
        }

        // Calcul du prix
        $prixMenu = $nombrePersonne * $menu->getPrixParPersonne();

        // Réduction
        if ($nombrePersonne >= $menu->getNombrePersonneMinimum() + 5) {
            $prixMenu = $prixMenu * 0.90;
        }

        // Calcul livraison
        $prixLivraison = 0;
        // Gratuit à Bordeaux, 5 € ailleurs (la ville est extraite de l'adresse saisie)
        $adresse = trim($data['adresseLivraison'] ?? $data['villeLivraison'] ?? '');
        if ($adresse !== '' && !str_contains(mb_strtolower($adresse), 'bordeaux')) {
            $prixLivraison = 5.00;
        }

        // Création de la commande
        $commande = new Commande();
        $commande->setNumeroCommande('CMD-' . date('Y') . '-' . uniqid());
        $commande->setDateCommande(new \DateTime());
        $commande->setDatePrestation($datePrestation);
        $commande->setAdresseLivraison(mb_substr($adresse, 0, 255) ?: null);
        $commande->setLieuPrestation(isset($data['lieuPrestation']) ? mb_substr(trim($data['lieuPrestation']), 0, 255) : null);
        $commande->setHeureLivraison($data['heureLivraison']);
        $commande->setNombrePersonne($nombrePersonne);
        $commande->setPrixMenu(round($prixMenu, 2));
        $commande->setPrixLivraison($prixLivraison);
        $commande->setStatut('en_attente');
        $commande->setPretMateriel(false);
        $commande->setRestitionMateriel(false);
        $commande->setUtilisateur($utilisateur);
        $commande->setMenu($menu);

        // Décrémentation du stock
        $menu->setQuantiteRestante($menu->getQuantiteRestante() - 1);

        $em->persist($commande);
        $em->flush();
        // Enregistrer la stat dans MongoDB
        $mongoService->enregistrerCommande(
            $menu->getId(),
            $menu->getTitre(),
            $commande->getPrixMenu() + $commande->getPrixLivraison()
        );


        return $this->json([
            'success' => true,
            'message' => 'Commande créée avec succès !',
            'data'    => [
                'id'             => $commande->getId(),
                'numeroCommande' => $commande->getNumeroCommande(),
                'prixMenu'       => $commande->getPrixMenu(),
                'prixLivraison'  => $commande->getPrixLivraison(),
                'statut'         => $commande->getStatut(),
            ]
        ], 201);
    }

    // ─ LISTE DES COMMANDES DE L'UTILISATEUR CONNECTE
    #[Route('/commandes', name: 'app_commandes_list', methods: ['GET'])]
    public function list(CommandeRepository $commandeRepository, AvisRepository $avisRepository): JsonResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            return $this->json([
                'success' => false,
                'message' => 'Non authentifié.'
            ], 401);
        }

        $commandes = $commandeRepository->findBy(
            ['utilisateur' => $utilisateur],
            ['dateCommande' => 'DESC']
        );

        $data = array_map(function ($commande) use ($avisRepository) {
            return [
                'id'              => $commande->getId(),
                'numeroCommande'  => $commande->getNumeroCommande(),
                'dateCommande'    => $commande->getDateCommande()->format('d/m/Y'),
                'datePrestation'  => $commande->getDatePrestation()->format('d/m/Y'),
                'heureLivraison'  => $commande->getHeureLivraison(),
                'nombrePersonne'  => $commande->getNombrePersonne(),
                'prixMenu'        => $commande->getPrixMenu(),
                'prixLivraison'   => $commande->getPrixLivraison(),
                'statut'          => $commande->getStatut(),
                'menu'            => $commande->getMenu()->getTitre(),
                'adresseLivraison'=> $commande->getAdresseLivraison(),
                'motifAnnulation' => $commande->getMotifAnnulation(),
                'avisDonne'       => $avisRepository->findOneBy(['commande' => $commande]) !== null,
            ];
        }, $commandes);

        return $this->json([
            'success' => true,
            'total'   => count($data),
            'data'    => $data
        ]);
    }

    // ─ ANNULER UNE COMMANDE 
    #[Route('/commandes/{id}/annuler', name: 'app_commande_annuler', methods: ['PUT'])]
    public function annuler(
        int $id,
        CommandeRepository $commandeRepository,
        EntityManagerInterface $em
    ): JsonResponse {

        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();

        $commande = $commandeRepository->find($id);

        if (!$commande) {
            return $this->json([
                'success' => false,
                'message' => 'Commande introuvable.'
            ], 404);
        }

        // Vérification que la commande appartient à l'utilisateur
        if ($commande->getUtilisateur()->getId() !== $utilisateur->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à annuler cette commande.'
            ], 403);
        }

        // On ne peut annuler que si la commande est en attente
        if ($commande->getStatut() !== 'en_attente') {
            return $this->json([
                'success' => false,
                'message' => 'Impossible d\'annuler une commande déjà acceptée.'
            ], 400);
        }

        $commande->setStatut('annule');

        // Restitution du stock
        $commande->getMenu()->setQuantiteRestante(
            $commande->getMenu()->getQuantiteRestante() + 1
        );

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Commande annulée avec succès.'
        ]);
    }
}