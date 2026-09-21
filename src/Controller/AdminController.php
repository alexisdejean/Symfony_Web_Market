<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(ContactRepository $contactRepository, Request $request): Response
    {   
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $queryBuilder = $contactRepository->createQueryBuilder('c');
        $total = (int) (clone $queryBuilder)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
        $contactMessages = $queryBuilder
            ->orderBy('c.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
            'contact_messages' => $contactMessages,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }

    #[Route('/admin/contact/delete/{id}', name: 'app_admin_contact_delete', methods: ['POST'])]
    public function deleteContact(
        int $id,
        Request $request,
        ContactRepository $contactRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete_contact_'.$id, (string) $request->request->get('_token', ''))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $contact = $contactRepository->find($id);
        if (!$contact) {
            throw $this->createNotFoundException('Message non trouvé.');
        }

        $entityManager->remove($contact);
        $entityManager->flush();

        $this->addFlash('success', 'Le message a été supprimé.');

        return $this->redirectToRoute('app_admin');
    }
}
