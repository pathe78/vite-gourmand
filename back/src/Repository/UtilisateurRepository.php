<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * Trouve tous les utilisateurs ayant un rôle spécifique
     * @return Utilisateur[]
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            // Les rôles sont stockés en JSON (ex: ["ROLE_EMPLOYE"]) : recherche portable MySQL / MariaDB
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"' . $role . '"%')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}