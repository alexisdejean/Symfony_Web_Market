<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\User;
use App\Form\ContactType;
use App\Security\FileRateLimiter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request): Response
    {
        $message = new Contact();
        $this->prefillContact($message);
        $form = $this->createForm(ContactType::class, $message);

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
            'message_fonctionnel' => '',
        ]);
    }

    #[Route('/contact/submit', name: 'app_contact_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $entityManager, FileRateLimiter $rateLimiter): Response
    {
        if (!$rateLimiter->consume('contact', (string) $request->getClientIp(), 5, 3600)) {
            $this->addFlash('error', 'Trop de messages envoyés. Réessayez plus tard.');

            return $this->redirectToRoute('app_contact');
        }

        $message = new Contact();
        $form = $this->createForm(ContactType::class, $message);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('contact/index.html.twig', [
                'form' => $form->createView(),
                'message_fonctionnel' => '',
            ]);
        }

        $utilisateur = $this->getUser();
        if ($utilisateur instanceof User) {
            $message->setIdentifiantUser($utilisateur->getNom() ?: $utilisateur->getIdentifiant());
            $message->setEmailUser((string) $utilisateur->getEmail());
            $message->setUser($utilisateur);
        }

        $message->setDateEnvoi(new \DateTimeImmutable());
        $entityManager->persist($message);
        $entityManager->flush();
        $this->addFlash('success', 'Votre message a bien été envoyé.');

        return $this->redirectToRoute('app_contact');
    }

    private function prefillContact(Contact $message): void
    {
        $utilisateur = $this->getUser();
        if ($utilisateur instanceof User) {
            $message->setIdentifiantUser($utilisateur->getNom() ?: $utilisateur->getIdentifiant());
            $message->setEmailUser((string) $utilisateur->getEmail());
        }
    }
}
