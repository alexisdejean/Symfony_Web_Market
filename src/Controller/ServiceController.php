<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProduitsRepository;
use App\Entity\Produits;
use Doctrine\ORM\EntityManagerInterface;


final class ServiceController extends AbstractController
{
    #[Route('/service', name: 'app_service')]
    public function index(\App\Repository\ProduitsRepository $produitsRepository): Response
    {
        $produits = $produitsRepository->findAll();

        return $this->render('service/index.html.twig', [
            'produits' => $produits,
        ]);
    }
    #[Route('/service/show', name: 'app_service_show')]
    public function showProduits(ProduitsRepository $produitsRepository): Response
    {
        $produits = $produitsRepository->findAll();
        return $this->render('service/index.html.twig', [
            'produits' => $produits,
        ]);
    }
    #[Route('/service/add', name: 'app_service_add')]
    public function addProduit(EntityManagerInterface $entityManager): Response
    {
        $produit = new Produits();
        $produit->setNom('Montre haut de gamme');
        $produit->setDescription('Description du produit');
        $produit->setCouleur('Rouge');
        $produit->setMatiere('Coton');
        $produit->setForme('Carré');
        $produit->setImage('image.jpg');
        $produit->setPrix(9.99);
        $entityManager->persist($produit);
        $entityManager->flush();

        return $this->redirectToRoute('app_service');
    }
}
