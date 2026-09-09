<?php

namespace App\Controller;
use App\Entity\User;
use App\Form\ReglagesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ReglagesController extends AbstractController
{
    #[Route('/reglages/{id_compte}', name: 'app_reglages')]
    public function index(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher, int $id_compte): Response
    {
        if (!$this->isGranted('ROLE_USER') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $utilisateur = $entityManager->getRepository(User::class)->find($id_compte);
        if (!$utilisateur) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $utilisateurConnecte = $this->getUser();
        if (!$utilisateurConnecte instanceof User || $utilisateurConnecte->getId() !== $utilisateur->getId()) {
            throw $this->createAccessDeniedException();
        }

        $formulaire = $this->createForm(ReglagesType::class, $utilisateur);
        $formulaire->handleRequest($request);
        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $plainPassword = $formulaire->get('plainPassword')->getData();
            if (is_string($plainPassword) && $plainPassword !== '') {
                $utilisateur->setPassword($userPasswordHasher->hashPassword($utilisateur, $plainPassword));
            }

            $entityManager->flush();
            return $this->redirectToRoute('app_reglages', ['id_compte' => $id_compte]);
        }


        return $this->render('reglages/form.html.twig', [
            'form' => $formulaire->createView(),
            'page_title' => 'Réglages du compte',
            'button_label' => 'Enregistrer',
        ]);
    }
}
