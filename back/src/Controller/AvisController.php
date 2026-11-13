<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Utilisateur;
use App\Repository\AvisRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class AvisController extends AbstractController
{
    // ─ CREER UN AVIS
    #[Route('/avis', name: 'app_avis_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        CommandeRepository $commandeRepository,
        AvisRepository $avisRepository
    ): JsonResponse {

        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            return $this->json([
                'success' => false,
                'message' => 'Non authentifié.'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        // Vérification des champs obligatoires
        if (empty($data['note']) || empty($data['description']) || empty($data['commandeId'])) {
            return $this->json([
                'success' => false,
                'message' => 'La note, le commentaire et la commande sont obligatoires.'
            ], 400);
        }

        // Vérification de la note entre 1 et 5
        $note = (int) $data['note'];
        if ($note < 1 || $note > 5) {
            return $this->json([
                'success' => false,
                'message' => 'La note doit être entre 1 et 5.'
            ], 400);
        }

        // Récupération de la commande
        $commande = $commandeRepository->find($data['commandeId']);
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
                'message' => 'Vous n\'êtes pas autorisé à donner un avis sur cette commande.'
            ], 403);
        }

        // Vérification que la commande est terminée
        if ($commande->getStatut() !== 'termine') {
            return $this->json([
                'success' => false,
                'message' => 'Vous ne pouvez donner un avis que sur une commande terminée.'
            ], 400);
        }

        // Un seul avis par commande
        if ($avisRepository->findOneBy(['commande' => $commande])) {
            return $this->json([
                'success' => false,
                'message' => 'Vous avez déjà donné un avis pour cette commande.'
            ], 409);
        }

        $description = trim($data['description']);
        if (mb_strlen($description) > 1000) {
            return $this->json(['success' => false, 'message' => 'Le commentaire ne doit pas dépasser 1000 caractères.'], 400);
        }

        // Création de l'avis
        $avis = new Avis();
        $avis->setNote($note);
        $avis->setDescription($description);
        $avis->setDateAvis(new \DateTime());
        $avis->setStatut('en_attente');
        $avis->setUtilisateur($utilisateur);
        $avis->setCommande($commande);

        $em->persist($avis);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Avis soumis avec succès, il sera visible après validation par notre équipe.'
        ], 201);
    }

    // ─ LISTE DES AVIS VALIDES
    #[Route('/avis', name: 'app_avis_list', methods: ['GET'])]
    public function list(AvisRepository $avisRepository): JsonResponse
    {
        $avis = $avisRepository->findBy(
            ['statut' => 'valide'],
            ['id' => 'DESC']
        );

        $data = array_map(function ($avis) {
            return [
                'id'          => $avis->getId(),
                'note'        => $avis->getNote(),
                'description' => $avis->getDescription(),
                'auteur'      => $avis->getUtilisateur()->getPrenom() . ' ' . substr($avis->getUtilisateur()->getNom(), 0, 1) . '.',
            ];
        }, $avis);

        return $this->json([
            'success' => true,
            'total'   => count($data),
            'data'    => $data
        ]);
    }
}