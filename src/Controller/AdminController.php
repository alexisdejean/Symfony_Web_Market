<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Contact;
use App\Repository\ContactRepository;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(ContactRepository $contactRepository): Response
    {   
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $contact_messages =  $contactRepository->findBy([], ['id' => 'DESC'], 10, 0);   

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
            'contact_messages' => $contact_messages,
        ]);
    }
}
