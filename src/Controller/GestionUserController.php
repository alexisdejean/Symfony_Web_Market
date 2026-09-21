<?php

namespace App\Controller;

use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GestionUserController extends AbstractController
{
    #[Route('/gestion/user', name: 'app_gestion_user')]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $queryBuilder = $userRepository->createQueryBuilder('u');
        $total = (int) (clone $queryBuilder)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
        $users = $queryBuilder
            ->orderBy('u.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('gestion_user/index.html.twig', [
            'controller_name' => 'GestionUserController',
            'users' => $users,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }
    #[Route('/gestion/user/delete/{id}', name: 'app_gestion_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('delete_user_'.$id, (string) $request->request->get('_token', ''))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_gestion_user');
    }

    #[Route('/gestion/user/edit/{id}', name: 'app_gestion_user_edit')]
    public function editUser(Request $request, int $id, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) { 
            $entityManager->flush();

            return $this->redirectToRoute('app_gestion_user');
        }
        

       return $this->render('gestion_user/form.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Modifier un utilisateur',
            'button_label' => 'Enregistrer',
            
        ]);
    }
    #[Route('/gestion/user/block/{id}', name: 'app_gestion_user_block', methods: ['POST'])]
    public function blockUser(Request $request, int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('block_user_'.$id, (string) $request->request->get('_token', ''))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        $user->setStatus(true);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_gestion_user');
    }
    #[Route('/gestion/user/unblock/{id}', name: 'app_gestion_user_unblock', methods: ['POST'])]
    public function unblockUser(Request $request, int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('unblock_user_'.$id, (string) $request->request->get('_token', ''))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        $user->setStatus(false);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_gestion_user');
    }

}
