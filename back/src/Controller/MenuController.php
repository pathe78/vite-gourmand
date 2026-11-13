<?php

namespace App\Controller;

use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class MenuController extends AbstractController
{
    // ─ LISTE DE TOUS LES MENUS
    #[Route('/menus', name: 'app_menus', methods: ['GET'])]
    public function index(Request $request, MenuRepository $menuRepository): JsonResponse
    {
        // Récupération des filtres
        $prixMax    = $request->query->get('prixMax');
        $prixMin    = $request->query->get('prixMin');
        $theme      = $request->query->get('theme');
        $regime     = $request->query->get('regime');
        $personnes  = $request->query->get('personnes');

        // Construction de la requête avec filtres
        $qb = $menuRepository->createQueryBuilder('m')
            ->leftJoin('m.theme', 't')
            ->leftJoin('m.regime', 'r')
            ->addSelect('t', 'r');

        if ($prixMax) {
            $qb->andWhere('m.prixParPersonne <= :prixMax')
               ->setParameter('prixMax', $prixMax);
        }

        if ($prixMin) {
            $qb->andWhere('m.prixParPersonne >= :prixMin')
               ->setParameter('prixMin', $prixMin);
        }

        if ($theme) {
            $qb->andWhere('t.libelle = :theme')
               ->setParameter('theme', $theme);
        }

        if ($regime) {
            $qb->andWhere('r.libelle = :regime')
               ->setParameter('regime', $regime);
        }

        if ($personnes) {
            $qb->andWhere('m.nombrePersonneMinimum >= :personnes')
               ->setParameter('personnes', $personnes);
        }

        $menus = $qb->getQuery()->getResult();

    
        $data = array_map(function ($menu) {
            return [
                'id'                 => $menu->getId(),
                'titre'              => $menu->getTitre(),
                'description'        => $menu->getDescription(),
                'nombrePersonneMinimum' => $menu->getNombrePersonneMinimum(),
                'prixParPersonne'    => $menu->getPrixParPersonne(),
                'quantiteRestante'   => $menu->getQuantiteRestante(),
                'theme'              => $menu->getTheme()?->getLibelle(),
                'regime'             => $menu->getRegime()?->getLibelle(),
            ];
        }, $menus);

        return $this->json([
            'success' => true,
            'total'   => count($data),
            'data'    => $data
        ]);
    }

    // ─ DETAIL D'UN MENU 
    #[Route('/menus/{id}', name: 'app_menu_detail', methods: ['GET'])]
    public function show(int $id, MenuRepository $menuRepository): JsonResponse
    {
        $menu = $menuRepository->find($id);

        if (!$menu) {
            return $this->json([
                'success' => false,
                'message' => 'Menu introuvable.'
            ], 404);
        }

        // Récupération des plats avec leurs allergènes
        $plats = array_map(function ($plat) {
            return [
                'id'       => $plat->getId(),
                'titre'    => $plat->getTitre(),
                'typePlat' => $plat->getTypePlat(),
                'allergenes' => array_map(function ($allergene) {
                    return $allergene->getLibelle();
                }, $plat->getAllergenes()->toArray()),
            ];
        }, $menu->getPlats()->toArray());

        return $this->json([
            'success' => true,
            'data'    => [
                'id'                 => $menu->getId(),
                'titre'              => $menu->getTitre(),
                'description'        => $menu->getDescription(),
                'nombrePersonneMinimum' => $menu->getNombrePersonneMinimum(),
                'prixParPersonne'    => $menu->getPrixParPersonne(),
                'quantiteRestante'   => $menu->getQuantiteRestante(),
                'theme'              => $menu->getTheme()?->getLibelle(),
                'regime'             => $menu->getRegime()?->getLibelle(),
                'plats'              => $plats,
            ]
        ]);
    }
}