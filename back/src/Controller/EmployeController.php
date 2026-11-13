<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\AvisRepository;
use App\Repository\CommandeRepository;
use App\Repository\HoraireRepository;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/employe')]
final class EmployeController extends AbstractController
{
    // ─ LISTE DES COMMANDES
    #[Route('/commandes', name: 'app_employe_commandes', methods: ['GET'])]
    public function commandes(
        Request $request,
        CommandeRepository $commandeRepository
    ): JsonResponse {

        $statut = $request->query->get('statut');
        $client = $request->query->get('client');

        $qb = $commandeRepository->createQueryBuilder('c')
            ->leftJoin('c.utilisateur', 'u')
            ->leftJoin('c.menu', 'm')
            ->addSelect('u', 'm')
            ->orderBy('c.dateCommande', 'DESC');

        if ($statut) {
            $qb->andWhere('c.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($client) {
            $qb->andWhere('u.nom LIKE :client OR u.prenom LIKE :client OR u.email LIKE :client')
               ->setParameter('client', '%' . $client . '%');
        }

        $commandes = $qb->getQuery()->getResult();

        $data = array_map(function ($commande) {
            return [
                'id'             => $commande->getId(),
                'numeroCommande' => $commande->getNumeroCommande(),
                'dateCommande'   => $commande->getDateCommande()->format('d/m/Y'),
                'datePrestation' => $commande->getDatePrestation()->format('d/m/Y'),
                'heureLivraison' => $commande->getHeureLivraison(),
                'nombrePersonne' => $commande->getNombrePersonne(),
                'prixMenu'       => $commande->getPrixMenu(),
                'prixLivraison'  => $commande->getPrixLivraison(),
                'statut'         => $commande->getStatut(),
                'menu'           => $commande->getMenu()->getTitre(),
                'client'         => $commande->getUtilisateur()->getPrenom() . ' ' . $commande->getUtilisateur()->getNom(),
                'email'          => $commande->getUtilisateur()->getEmail(),
                'telephone'      => $commande->getUtilisateur()->getTelephone(),
                'adresseLivraison' => $commande->getAdresseLivraison(),
                'motifAnnulation'  => $commande->getMotifAnnulation(),
                'telephone'      => $commande->getUtilisateur()->getTelephone(),
            ];
        }, $commandes);

        return $this->json([
            'success' => true,
            'total'   => count($data),
            'data'    => $data
        ]);
    }

    // ─ METTRE À JOUR LE STATUT D'UNE COMMANDE
    #[Route('/commandes/{id}/statut', name: 'app_employe_commande_statut', methods: ['PUT'])]
    public function updateStatut(
        int $id,
        Request $request,
        CommandeRepository $commandeRepository,
        EntityManagerInterface $em
    ): JsonResponse {

        $commande = $commandeRepository->find($id);

        if (!$commande) {
            return $this->json([
                'success' => false,
                'message' => 'Commande introuvable.'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        $statutsValides = [
            'accepte',
            'en_preparation',
            'en_cours_livraison',
            'livre',
            'en_attente_materiel',
            'termine',
            'annule'
        ];

        if (empty($data['statut']) || !in_array($data['statut'], $statutsValides)) {
            return $this->json([
                'success' => false,
                'message' => 'Statut invalide.'
            ], 400);
        }

        // Annulation : motif et mode de contact obligatoires
        if ($data['statut'] === 'annule') {
            if (empty($data['motifAnnulation']) || empty($data['modeContact'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le motif d\'annulation et le mode de contact sont obligatoires.'
                ], 400);
            }
            if (!in_array($data['modeContact'], ['telephone', 'email'], true)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le mode de contact doit être "telephone" ou "email".'
                ], 400);
            }
            $commande->setMotifAnnulation(mb_substr(trim($data['motifAnnulation']), 0, 1000));
            $commande->setModeContact($data['modeContact']);

            // Le menu redevient disponible
            if ($commande->getStatut() !== 'annule') {
                $commande->getMenu()->setQuantiteRestante($commande->getMenu()->getQuantiteRestante() + 1);
            }
        }

        $commande->setStatut($data['statut']);
        $em->flush();


        return $this->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès.'
        ]);
    }

    // ─ VALIDER OU REFUSER UN AVIS 
    #[Route('/avis/{id}', name: 'app_employe_avis_update', methods: ['PUT'])]
    public function updateAvis(
        int $id,
        Request $request,
        AvisRepository $avisRepository,
        EntityManagerInterface $em
    ): JsonResponse {

        $avis = $avisRepository->find($id);

        if (!$avis) {
            return $this->json([
                'success' => false,
                'message' => 'Avis introuvable.'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['statut']) || !in_array($data['statut'], ['valide', 'refuse'])) {
            return $this->json([
                'success' => false,
                'message' => 'Statut invalide. Utilisez "valide" ou "refuse".'
            ], 400);
        }

        $avis->setStatut($data['statut']);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Avis ' . $data['statut'] . ' avec succès.'
        ]);
    }

    // ─ LISTE DES AVIS EN ATTENTE 
    #[Route('/avis', name: 'app_employe_avis_list', methods: ['GET'])]
    public function avis(AvisRepository $avisRepository): JsonResponse
    {
        $avis = $avisRepository->findBy(
            ['statut' => 'en_attente'],
            ['id' => 'DESC']
        );

        $data = array_map(function ($avis) {
            return [
                'id'          => $avis->getId(),
                'note'        => $avis->getNote(),
                'description' => $avis->getDescription(),
                'statut'      => $avis->getStatut(),
                'auteur'      => $avis->getUtilisateur()->getPrenom() . ' ' . $avis->getUtilisateur()->getNom(),
            ];
        }, $avis);

        return $this->json([
            'success' => true,
            'total'   => count($data),
            'data'    => $data
        ]);
    }

    // ─ MODIFIER UN MENU 
    #[Route('/menus/{id}', name: 'app_employe_menu_update', methods: ['PUT'])]
    public function updateMenu(
        int $id,
        Request $request,
        MenuRepository $menuRepository,
        EntityManagerInterface $em
    ): JsonResponse {

        $menu = $menuRepository->find($id);

        if (!$menu) {
            return $this->json([
                'success' => false,
                'message' => 'Menu introuvable.'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['prixParPersonne']) && (!is_numeric($data['prixParPersonne']) || $data['prixParPersonne'] <= 0)) {
            return $this->json(['success' => false, 'message' => 'Le prix doit être un nombre positif.'], 400);
        }
        if (isset($data['quantiteRestante']) && (!is_numeric($data['quantiteRestante']) || $data['quantiteRestante'] < 0)) {
            return $this->json(['success' => false, 'message' => 'Le stock doit être un nombre positif ou nul.'], 400);
        }
        if (isset($data['nombrePersonneMinimum']) && (!is_numeric($data['nombrePersonneMinimum']) || $data['nombrePersonneMinimum'] < 1)) {
            return $this->json(['success' => false, 'message' => 'Le nombre de personnes minimum doit être au moins 1.'], 400);
        }

        if (!empty($data['titre']))                $menu->setTitre(mb_substr(trim($data['titre']), 0, 100));
        if (!empty($data['description']))          $menu->setDescription(trim($data['description']));
        if (isset($data['prixParPersonne']))       $menu->setPrixParPersonne((float) $data['prixParPersonne']);
        if (isset($data['quantiteRestante']))      $menu->setQuantiteRestante((int) $data['quantiteRestante']);
        if (isset($data['nombrePersonneMinimum'])) $menu->setNombrePersonneMinimum((int) $data['nombrePersonneMinimum']);

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Menu mis à jour avec succès.'
        ]);
    }

    // ─ SUPPRIMER UN MENU 
    #[Route('/menus/{id}', name: 'app_employe_menu_delete', methods: ['DELETE'])]
    public function deleteMenu(
        int $id,
        MenuRepository $menuRepository,
        EntityManagerInterface $em
    ): JsonResponse {

        $menu = $menuRepository->find($id);

        if (!$menu) {
            return $this->json([
                'success' => false,
                'message' => 'Menu introuvable.'
            ], 404);
        }

        // On ne supprime pas un menu qui a déjà des commandes (historique client)
        if ($em->getRepository(\App\Entity\Commande::class)->count(['menu' => $menu]) > 0) {
            return $this->json([
                'success' => false,
                'message' => 'Ce menu a déjà été commandé : mettez son stock à 0 plutôt que de le supprimer.'
            ], 409);
        }

        $em->remove($menu);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Menu supprimé avec succès.'
        ]);
    }

    // ─ MODIFIER LES HORAIRES 
    #[Route('/horaires/{id}', name: 'app_employe_horaire_update', methods: ['PUT'])]
    public function updateHoraire(
        int $id,
        Request $request,
        HoraireRepository $horaireRepository,
        EntityManagerInterface $em
    ): JsonResponse {

        $horaire = $horaireRepository->find($id);

        if (!$horaire) {
            return $this->json([
                'success' => false,
                'message' => 'Horaire introuvable.'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        // Format HH:MM attendu, ou "Fermé"
        foreach (['heureOuverture', 'heureFermeture'] as $champ) {
            if (!empty($data[$champ]) && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$|^Fermé$/u', $data[$champ])) {
                return $this->json(['success' => false, 'message' => 'Format d\'heure invalide (HH:MM ou "Fermé").'], 400);
            }
        }

        if (!empty($data['heureOuverture'])) $horaire->setHeureOuverture($data['heureOuverture']);
        if (!empty($data['heureFermeture'])) $horaire->setHeureFermeture($data['heureFermeture']);

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Horaire mis à jour avec succès.'
        ]);
    }
}