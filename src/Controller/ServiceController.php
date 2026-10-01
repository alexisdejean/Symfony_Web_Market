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
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 24;

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

        $total = (int) (clone $qb)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $produits = $qb
            ->orderBy('p.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('service/index.html.twig', [
            'produits' => $produits,
            'filter_values' => [
                'matiere' => $produitsRepository->findDistinctValues('matiere'),
                'couleur' => $produitsRepository->findDistinctValues('couleur'),
                'forme' => $produitsRepository->findDistinctValues('forme'),
            ],
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
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
        $produit->setStock(0);
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile instanceof UploadedFile) {
                if (!$this->isValidImageUpload($imageFile)) {
                    $this->addFlash('error', 'Le fichier image est invalide. Utilisez un JPEG, PNG ou WebP de moins de 2 Mo.');

                    return $this->render('service/form.html.twig', [
                        'form' => $form->createView(),
                        'page_title' => 'Ajouter un produit',
                        'button_label' => 'Ajouter',
                        'existing_values' => $this->getFilterValues($produitsRepository),
                    ]);
                }

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
            'existing_values' => $this->getFilterValues($produitsRepository),
        ]);
    }

    #[Route('/service/delete/{id_produit}', name: 'app_service_delete', methods: ['POST'])]
    public function deleteProduit(Request $request, EntityManagerInterface $entityManager, int $id_produit): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete' . $id_produit, (string) $request->request->get('_token', ''))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $produit = $entityManager->getRepository(Produits::class)->find($id_produit);
        if (!$produit) {
            throw $this->createNotFoundException('Ce produit n\'existe pas !');
        }

        foreach ($produit->getPanierContenus() as $panierContenu) {
            $entityManager->remove($panierContenu);
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
                if (!$this->isValidImageUpload($imageFile)) {
                    $this->addFlash('error', 'Le fichier image est invalide. Utilisez un JPEG, PNG ou WebP de moins de 2 Mo.');

                    return $this->render('service/form.html.twig', [
                        'form' => $form->createView(),
                        'page_title' => 'Modifier un produit',
                        'button_label' => 'Enregistrer',
                        'existing_values' => $this->getFilterValues($entityManager->getRepository(Produits::class)),
                    ]);
                }

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
            'existing_values' => $this->getFilterValues($entityManager->getRepository(Produits::class)),
        ]);
    }

    private function uploadImage(UploadedFile $file): string
    {
        $targetDirectory = $this->getParameter('kernel.project_dir').'/public/uploads/products';

        if (!is_dir($targetDirectory)) {
            if (!mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
                throw new \RuntimeException('Impossible de préparer le dossier des images.');
            }
        }

        $extensionsByMimeType = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $mimeType = $file->getMimeType();
        if (!is_string($mimeType) || !isset($extensionsByMimeType[$mimeType])) {
            throw new \RuntimeException('Extension de fichier non autorisée.');
        }

        $fileName = bin2hex(random_bytes(16)).'.'.$extensionsByMimeType[$mimeType];
        $file->move($targetDirectory, $fileName);

        return 'uploads/products/'.$fileName;
    }

    private function isValidImageUpload(UploadedFile $file): bool
    {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $mimeType = $file->getMimeType();

        if (!is_string($mimeType) || !in_array($mimeType, $allowedMimeTypes, true)) {
            return false;
        }

        $size = $file->getSize();
        if (!is_int($size) || $size < 1 || $size > 2 * 1024 * 1024) {
            return false;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file->getPathname());
        $imageInfo = @getimagesize($file->getPathname());

        return is_string($detectedMime)
            && in_array($detectedMime, $allowedMimeTypes, true)
            && $mimeType === $detectedMime
            && is_array($imageInfo)
            && $imageInfo['mime'] === $detectedMime
            && $imageInfo[0] <= 8000
            && $imageInfo[1] <= 8000
            && $imageInfo[0] * $imageInfo[1] <= 40000000;
    }

    private function getFilterValues(ProduitsRepository $produitsRepository): array
    {
        return [
            'matiere' => $produitsRepository->findDistinctValues('matiere'),
            'couleur' => $produitsRepository->findDistinctValues('couleur'),
            'forme' => $produitsRepository->findDistinctValues('forme'),
        ];
    }
}