<?php

namespace App\Controller;

use App\Entity\Produits;
use App\Form\ProduitType;
use App\Repository\ProduitsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ServiceController extends AbstractController
{
    #[Route('/service', name: 'app_service')]
    public function index(ProduitsRepository $produitsRepository, Request $request): Response
    {
        $forme = $request->query->get('forme');
        $matiere = $request->query->get('matiere');
        $couleur = $request->query->get('couleur');
        $prix = $request->query->get('prix');

        $qb = $produitsRepository->createQueryBuilder('p');

        if ($forme) {
            $qb->andWhere('p.forme = :forme')->setParameter('forme', $forme);
        }

        if ($matiere) {
            $qb->andWhere('p.matiere = :matiere')->setParameter('matiere', $matiere);
        }

        if ($couleur) {
            $qb->andWhere('p.couleur = :couleur')->setParameter('couleur', $couleur);
        }

        if ($prix === 'low') {
            $qb->andWhere('p.prix < 50');
        } elseif ($prix === 'medium') {
            $qb->andWhere('p.prix BETWEEN 50 AND 100');
        } elseif ($prix === 'high') {
            $qb->andWhere('p.prix > 100');
        }

        $produits = $qb->getQuery()->getResult();

        return $this->render('service/index.html.twig', [
            'produits' => $produits,
            'filter_values' => $this->getExistingValues($produitsRepository),
            'selected_filters' => [
                'forme' => $forme,
                'matiere' => $matiere,
                'couleur' => $couleur,
                'prix' => $prix,
            ],
        ]);
    }

    #[Route('/service/add', name: 'app_service_add', methods: ['GET', 'POST'])]
    public function addProduit(Request $request, EntityManagerInterface $entityManager, ProduitsRepository $produitsRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $produit = new Produits();
        $produit->setImage('images/default-product.jpg');
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile instanceof UploadedFile) {
                $produit->setImage($this->uploadImage($imageFile));
            } elseif (empty($produit->getImage())) {
                $produit->setImage('uploads/products/default-product.svg');
            }

            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash('success', 'Le produit a bien été ajouté.');

            return $this->redirectToRoute('app_service');
        }

        return $this->render('service/form.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Ajouter un produit',
            'button_label' => 'Ajouter',
            'existing_values' => $this->getExistingValues($produitsRepository),
        ]);
    }

    #[Route('/service/delete/{id_produit}', name: 'app_service_delete', methods: ['POST', 'DELETE', 'GET'])]
    public function deleteProduit(EntityManagerInterface $entityManager, int $id_produit): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $produit = $entityManager->getRepository(Produits::class)->find($id_produit);
        if (!$produit) {
            throw $this->createNotFoundException('Ce produit n\'existe pas !');
        }

        $entityManager->remove($produit);
        $entityManager->flush();

        return $this->redirectToRoute('app_service');
    }

    #[Route('/service/update/{id_produit}', name: 'app_service_update', methods: ['GET', 'POST'])]
    public function updateProduit(Request $request, EntityManagerInterface $entityManager, int $id_produit): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $produit = $entityManager->getRepository(Produits::class)->find($id_produit);
        if (!$produit) {
            throw $this->createNotFoundException('Ce produit n\'existe pas !');
        }

        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile instanceof UploadedFile) {
                $produit->setImage($this->uploadImage($imageFile));
            } elseif (empty($produit->getImage())) {
                $produit->setImage('uploads/products/default-product.svg');
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le produit a bien été modifié.');

            return $this->redirectToRoute('app_service');
        }

        return $this->render('service/form.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Modifier un produit',
            'button_label' => 'Enregistrer',
            'existing_values' => $this->getExistingValues($entityManager->getRepository(Produits::class)),
        ]);
    }

    private function uploadImage(UploadedFile $file): string
    {
        $targetDirectory = $this->getParameter('kernel.project_dir').'/public/uploads/products';

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }

        $extension = $file->guessExtension() ?: 'jpg';
        $fileName = uniqid().'.'.$extension;
        $file->move($targetDirectory, $fileName);

        return 'uploads/products/'.$fileName;
    }

    private function getExistingValues(ProduitsRepository $produitsRepository): array
    {
        $produits = $produitsRepository->findAll();

        return [
            'matiere' => array_values(array_unique(array_filter(array_map(static fn (Produits $produit): ?string => $produit->getMatiere(), $produits)))),
            'couleur' => array_values(array_unique(array_filter(array_map(static fn (Produits $produit): ?string => $produit->getCouleur(), $produits)))),
            'forme' => array_values(array_unique(array_filter(array_map(static fn (Produits $produit): ?string => $produit->getForme(), $produits)))),
        ];
    }
}