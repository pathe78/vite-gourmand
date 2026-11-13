<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Bloque la connexion (et l'utilisation d'un token JWT déjà émis)
 * pour un compte désactivé par l'administrateur.
 */
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof Utilisateur && !$user->isActif()) {
            throw new CustomUserMessageAccountStatusException('Ce compte a été désactivé.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
