<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class AuthController extends AbstractController
{
    // ─ INSCRIPTION 
    #[Route('/inscription', name: 'app_inscription', methods: ['POST'])]
    public function inscription(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UtilisateurRepository $utilisateurRepository
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        // Vérification des champs obligatoires
        if (
            empty($data['email']) ||
            empty($data['password']) ||
            empty($data['nom']) ||
            empty($data['prenom']) ||
            empty($data['telephone']) ||
            empty($data['adresse']) ||
            empty($data['ville']) ||
            empty($data['codePostal'])
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Tous les champs sont obligatoires.'
            ], 400);
        }

        // Format de l'email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 180) {
            return $this->json([
                'success' => false,
                'message' => 'L\'adresse email n\'est pas valide.'
            ], 400);
        }

        // Longueur maximale des champs texte (évite les données trop longues pour la BDD)
        foreach (['nom' => 50, 'prenom' => 50, 'telephone' => 20, 'adresse' => 150, 'ville' => 50, 'codePostal' => 10] as $champ => $max) {
            if (mb_strlen(trim($data[$champ])) > $max) {
                return $this->json([
                    'success' => false,
                    'message' => 'Le champ ' . $champ . ' ne doit pas dépasser ' . $max . ' caractères.'
                ], 400);
            }
        }

        // Vérification si l'email existe déjà
        $existingUser = $utilisateurRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json([
                'success' => false,
                'message' => 'Cet email est déjà utilisé.'
            ], 409);
        }

        // Validation du mot de passe
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

        // Création de l'utilisateur
        $utilisateur = new Utilisateur();
        $utilisateur->setEmail(strtolower(trim($data['email'])));
        $utilisateur->setNom(trim($data['nom']));
        $utilisateur->setPrenom(trim($data['prenom']));
        $utilisateur->setTelephone(trim($data['telephone']));
        $utilisateur->setAdresse(trim($data['adresse']));
        $utilisateur->setVille(trim($data['ville']));
        $utilisateur->setCodePostal(trim($data['codePostal']));
        $utilisateur->setRoles(['ROLE_UTILISATEUR']);

        // Hashage du mot de passe
        $hashedPassword = $passwordHasher->hashPassword($utilisateur, $password);
        $utilisateur->setPassword($hashedPassword);

        $em->persist($utilisateur);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Compte créé avec succès. Bienvenue chez Vite & Gourmand !'
        ], 201);
    }

    // ─ PROFIL (infos de l'utilisateur connecte) 
    #[Route('/compte/profil', name: 'app_profil', methods: ['GET'])]
    public function profil(): JsonResponse
    {
        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            return $this->json([
                'success' => false,
                'message' => 'Non authentifié.'
            ], 401);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id'         => $utilisateur->getId(),
                'email'      => $utilisateur->getEmail(),
                'nom'        => $utilisateur->getNom(),
                'prenom'     => $utilisateur->getPrenom(),
                'telephone'  => $utilisateur->getTelephone(),
                'adresse'    => $utilisateur->getAdresse(),
                'ville'      => $utilisateur->getVille(),
                'codePostal' => $utilisateur->getCodePostal(),
                'roles'      => $utilisateur->getRoles(),
                'role'       => in_array('ROLE_ADMIN', $utilisateur->getRoles(), true) ? 'admin'
                              : (in_array('ROLE_EMPLOYE', $utilisateur->getRoles(), true) ? 'employe' : 'utilisateur'),
            ]
        ]);
    }

    // ─ MODIFIER PROFIL 
    #[Route('/compte/profil', name: 'app_modifier_profil', methods: ['PUT'])]
    public function modifierProfil(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {

        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            return $this->json([
                'success' => false,
                'message' => 'Non authentifié.'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!empty($data['nom']))        $utilisateur->setNom($data['nom']);
        if (!empty($data['prenom']))     $utilisateur->setPrenom($data['prenom']);
        if (!empty($data['telephone'])) $utilisateur->setTelephone($data['telephone']);
        if (!empty($data['adresse']))   $utilisateur->setAdresse($data['adresse']);
        if (!empty($data['ville']))     $utilisateur->setVille($data['ville']);
        if (!empty($data['codePostal'])) $utilisateur->setCodePostal($data['codePostal']);

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès.'
        ]);
    }
}