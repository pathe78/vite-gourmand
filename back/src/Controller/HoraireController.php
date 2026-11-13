<?php

namespace App\Controller;

use App\Repository\HoraireRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class HoraireController extends AbstractController
{
    // ─ HORAIRES D'OUVERTURE (affichés dans le footer, publics)
    #[Route('/horaires', name: 'app_horaires', methods: ['GET'])]
    public function index(HoraireRepository $horaireRepository): JsonResponse
    {
        $data = array_map(fn ($h) => [
            'id'             => $h->getId(),
            'jour'           => $h->getJour(),
            'heureOuverture' => $h->getHeureOuverture(),
            'heureFermeture' => $h->getHeureFermeture(),
        ], $horaireRepository->findBy([], ['id' => 'ASC']));

        return $this->json(['success' => true, 'data' => $data]);
    }
}
