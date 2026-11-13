<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\MongoDBService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

// Toutes les routes /api/admin sont réservées à ROLE_ADMIN (security.yaml → access_control)
#[Route('/api/admin')]
final class AdminController extends AbstractController
{
    // ─ CREER UN COMPTE EMPLOYE
    #[Route('/employes', name: 'app_admin_create_employe', methods: ['POST'])]
    public function createEmploye(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UtilisateurRepository $utilisateurRepository
    ): JsonResponse {

        $data = json_decode($request->getContent(), true) ?? [];

        if (empty($data['email']) || empty($data['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'L\'email et le mot de passe sont obligatoires.'
            ], 400);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'success' => false,
                'message' => 'L\'adresse email n\'est pas valide.'
            ], 400);
        }

        // Même règle de mot de passe que pour les clients
        $password = $data['password'];
        if (
            strlen($password) < 10 ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[^A-Za-z0-9]/', $password)
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Le mot de passe doit contenir au moins 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.'
            ], 400);
        }

        $email = strtolower(trim($data['email']));
        if ($utilisateurRepository->findOneBy(['email' => $email])) {
            return $this->json([
                'success' => false,
                'message' => 'Cet email est déjà utilisé.'
            ], 409);
        }

        $employe = new Utilisateur();
        $employe->setEmail($email);
        $employe->setNom(mb_substr(trim($data['nom'] ?? ''), 0, 50));
        $employe->setPrenom(mb_substr(trim($data['prenom'] ?? ''), 0, 50));
        $employe->setTelephone(mb_substr(trim($data['telephone'] ?? ''), 0, 20));
        $employe->setAdresse(mb_substr(trim($data['adresse'] ?? ''), 0, 150));
        $employe->setVille(mb_substr(trim($data['ville'] ?? ''), 0, 50));
        $employe->setCodePostal(mb_substr(trim($data['codePostal'] ?? ''), 0, 10));
        $employe->setRoles(['ROLE_EMPLOYE']);
        $employe->setPassword($passwordHasher->hashPassword($employe, $password));

        $em->persist($employe);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Compte employé créé avec succès.'
        ], 201);
    }

    // ─ LISTE DES EMPLOYES
    #[Route('/employes', name: 'app_admin_employes_list', methods: ['GET'])]
    public function employes(UtilisateurRepository $utilisateurRepository): JsonResponse
    {
        $employes = $utilisateurRepository->findByRole('ROLE_EMPLOYE');

        $data = array_map(function (Utilisateur $employe) {
            return [
                'id'     => $employe->getId(),
                'email'  => $employe->getEmail(),
                'nom'    => $employe->getNom(),
                'prenom' => $employe->getPrenom(),
                'actif'  => $employe->isActif(),
            ];
        }, $employes);

        return $this->json([
            'success' => true,
            'total'   => count($data),
            'data'    => $data
        ]);
    }

    // ─ DESACTIVER UN COMPTE EMPLOYE
    #[Route('/employes/{id}/desactiver', name: 'app_admin_employe_desactiver', methods: ['PUT'])]
    public function desactiverEmploye(int $id, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $em): JsonResponse
    {
        return $this->changerStatutEmploye($id, false, $utilisateurRepository, $em);
    }

    // ─ REACTIVER UN COMPTE EMPLOYE
    #[Route('/employes/{id}/activer', name: 'app_admin_employe_activer', methods: ['PUT'])]
    public function activerEmploye(int $id, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $em): JsonResponse
    {
        return $this->changerStatutEmploye($id, true, $utilisateurRepository, $em);
    }

    private function changerStatutEmploye(int $id, bool $actif, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $em): JsonResponse
    {
        $employe = $utilisateurRepository->find($id);

        // On ne peut agir que sur un compte employé (pas sur un client ni sur un admin)
        if (!$employe || !in_array('ROLE_EMPLOYE', $employe->getRoles(), true) || in_array('ROLE_ADMIN', $employe->getRoles(), true)) {
            return $this->json([
                'success' => false,
                'message' => 'Employé introuvable.'
            ], 404);
        }

        // Le compte est conservé (historique) mais ne peut plus se connecter : voir App\Security\UserChecker
        $employe->setActif($actif);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => $actif ? 'Compte employé réactivé.' : 'Compte employé désactivé avec succès.'
        ]);
    }

    // ─ STATS COMMANDES PAR MENU (depuis MongoDB)
    #[Route('/stats/commandes', name: 'app_admin_stats_commandes', methods: ['GET'])]
    public function statsCommandes(MongoDBService $mongoService): JsonResponse
    {
        try {
            $data = $mongoService->getStatsByMenu();
        } catch (\Throwable) {
            return $this->json([
                'success' => false,
                'message' => 'Les statistiques sont momentanément indisponibles (MongoDB).'
            ], 503);
        }

        return $this->json([
            'success' => true,
            'data'    => $data
        ]);
    }

    // ─ CHIFFRE D'AFFAIRES PAR PÉRIODE (depuis MongoDB)
    #[Route('/stats/chiffre-affaires', name: 'app_admin_chiffre_affaires', methods: ['GET'])]
    public function chiffreAffaires(Request $request, MongoDBService $mongoService): JsonResponse
    {
        try {
            $dateDebut = $request->query->get('dateDebut')
                ? new \DateTime($request->query->get('dateDebut') . ' 00:00:00')
                : new \DateTime('-30 days');

            $dateFin = $request->query->get('dateFin')
                ? new \DateTime($request->query->get('dateFin') . ' 23:59:59')
                : new \DateTime();
        } catch (\Exception) {
            return $this->json(['success' => false, 'message' => 'Dates invalides.'], 400);
        }

        try {
            $data = $mongoService->getStatsByPeriode($dateDebut, $dateFin);
        } catch (\Throwable) {
            return $this->json([
                'success' => false,
                'message' => 'Les statistiques sont momentanément indisponibles (MongoDB).'
            ], 503);
        }

        return $this->json([
            'success'         => true,
            'dateDebut'       => $dateDebut->format('d/m/Y'),
            'dateFin'         => $dateFin->format('d/m/Y'),
            'chiffreAffaires' => array_sum(array_column($data, 'chiffreAffaires')),
            'data'            => $data
        ]);
    }
}
