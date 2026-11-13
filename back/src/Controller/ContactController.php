<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ContactController extends AbstractController
{
    public function __construct(private string $contactEmail)
    {
    }

    // ─ ENVOYER UN MESSAGE DE CONTACT 
    #[Route('/contact', name: 'app_contact', methods: ['POST'])]
    public function contact(
        Request $request,
        MailerInterface $mailer
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        // Vérification des champs obligatoires
        if (
            empty($data['titre']) ||
            empty($data['description']) ||
            empty($data['email'])
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Le titre, le message et l\'email sont obligatoires.'
            ], 400);
        }

        // Validation de l'email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'success' => false,
                'message' => 'L\'adresse email n\'est pas valide.'
            ], 400);
        }

        if (mb_strlen($data['titre']) > 150 || mb_strlen($data['description']) > 3000) {
            return $this->json([
                'success' => false,
                'message' => 'Le titre ou le message est trop long.'
            ], 400);
        }

        // Envoi de l'email à l'entreprise.
        // L'expéditeur est l'adresse du site (et non celle saisie par le visiteur,
        // qui pourrait être usurpée) ; on répond au visiteur grâce à replyTo.
        $email = (new Email())
            ->from($this->contactEmail)
            ->replyTo($data['email'])
            ->to($this->contactEmail)
            ->subject('[Contact] ' . $data['titre'])
            ->text(
                "Nouveau message de contact\n\n" .
                "De : " . $data['email'] . "\n" .
                "Titre : " . $data['titre'] . "\n\n" .
                "Message :\n" . $data['description']
            );

        $mailer->send($email);

        return $this->json([
            'success' => true,
            'message' => 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.'
        ]);
    }
}