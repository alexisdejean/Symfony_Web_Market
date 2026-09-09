<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\PanierContenu;
use App\Entity\Produits;
use App\Entity\User;
use App\Repository\PanierContenuRepository;
use App\Repository\PanierRepository;
use App\Repository\ProduitsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PanierController extends AbstractController
{
    #[Route('/panier', name: 'app_panier')]
    public function index(PanierRepository $panierRepository): Response
    {
        $utilisateur = $this->getAuthenticatedUser();
        $panier = $panierRepository->findOneBy(['user' => $utilisateur]);
        $total = 0.0;

        if ($panier !== null) {
            foreach ($panier->getContenus() as $contenu) {
                $total += (float) $contenu->getProduit()->getPrix() * $contenu->getQuantite();
            }
        }

        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
            'total' => $total,
        ]);
    }

    #[Route('/panier/ajouter/{id}', name: 'app_panier_ajouter', methods: ['POST'])]
    public function add(
        Request $request,
        int $id,
        ProduitsRepository $produitsRepository,
        PanierRepository $panierRepository,
        PanierContenuRepository $contenuRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $utilisateur = $this->getAuthenticatedUser();
        $produit = $produitsRepository->find($id);

        if (!$produit) {
            throw $this->createNotFoundException('Produit non trouvé.');
        }

        if (!$this->isCsrfTokenValid('add_to_cart_'.$id, (string) $request->request->get('_token', ''))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $panier = $this->getOrCreateCart($utilisateur, $panierRepository, $entityManager);
        $contenu = $contenuRepository->findOneBy(['panier' => $panier, 'produit' => $produit]);
        $quantiteActuelle = $contenu?->getQuantite() ?? 0;

        if ($produit->getStock() < $quantiteActuelle + 1) {
            $this->addFlash('error', 'La quantité demandée dépasse le stock disponible.');

            return $this->redirectToRoute('app_service');
        }

        if ($contenu === null) {
            $contenu = new PanierContenu();
            $contenu->setPanier($panier);
            $contenu->setProduit($produit);
            $contenu->setQuantite(1);
            $entityManager->persist($contenu);
        } else {
            $contenu->setQuantite($quantiteActuelle + 1);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/modifier/{id}', name: 'app_panier_modifier', methods: ['POST'])]
    public function update(
        Request $request,
        int $id,
        PanierContenuRepository $contenuRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $utilisateur = $this->getAuthenticatedUser();
        $contenu = $contenuRepository->find($id);

        if (!$contenu || $contenu->getPanier()?->getUser()?->getId() !== $utilisateur->getId()) {
            throw $this->createNotFoundException('Article du panier non trouvé.');
        }

        if (!$this->isCsrfTokenValid('update_cart_'.$id, (string) $request->request->get('_token', ''))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $quantite = filter_var($request->request->get('quantite'), FILTER_VALIDATE_INT);
        $stock = $contenu->getProduit()->getStock();

        if ($quantite === false || $quantite < 1 || $quantite > $stock) {
            $this->addFlash('error', 'Quantité invalide ou supérieure au stock disponible.');

            return $this->redirectToRoute('app_panier');
        }

        $contenu->setQuantite($quantite);
        $entityManager->flush();

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/supprimer/{id}', name: 'app_panier_supprimer', methods: ['POST'])]
    public function remove(
        Request $request,
        int $id,
        PanierContenuRepository $contenuRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $utilisateur = $this->getAuthenticatedUser();
        $contenu = $contenuRepository->find($id);

        if (!$contenu || $contenu->getPanier()?->getUser()?->getId() !== $utilisateur->getId()) {
            throw $this->createNotFoundException('Article du panier non trouvé.');
        }

        if (!$this->isCsrfTokenValid('remove_from_cart_'.$id, (string) $request->request->get('_token', ''))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $entityManager->remove($contenu);
        $entityManager->flush();

        return $this->redirectToRoute('app_panier');
    }

    private function getAuthenticatedUser(): User
    {
        if (!$this->isGranted('ROLE_USER') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $utilisateur;
    }

    private function getOrCreateCart(
        User $utilisateur,
        PanierRepository $panierRepository,
        EntityManagerInterface $entityManager,
    ): Panier {
        $panier = $panierRepository->findOneBy(['user' => $utilisateur]);
        if ($panier !== null) {
            return $panier;
        }

        $panier = new Panier();
        $panier->setUser($utilisateur);
        $entityManager->persist($panier);

        return $panier;
    }
}
