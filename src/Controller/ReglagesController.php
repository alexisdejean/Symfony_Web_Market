<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReglagesController extends AbstractController
{
    #[Route('/reglages', name: 'app_reglages')]
    public function index(): Response
    {
        return $this->render('reglages/index.html.twig', [
            'controller_name' => 'ReglagesController',
        ]);
    }
}
