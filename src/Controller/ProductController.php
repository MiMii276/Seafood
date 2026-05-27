<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff/products')]
#[IsGranted('ROLE_STAFF')]
class ProductController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $products = $productRepository->findAll();
        } else {
            // Staff sees all products
            $products = $productRepository->findBy([], ['createdAt' => 'DESC']);
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'currentUser' => $user,
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setCreatedBy($this->getUser());
            $entityManager->persist($product);
            $entityManager->flush();

            $this->activityLogger->logProductCreate($this->getUser(), $product);
            $this->addFlash('success', 'Product created successfully.');

            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $product->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'You can only edit your own products.');
            return $this->redirectToRoute('app_product_index');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->activityLogger->logProductUpdate($this->getUser(), $product);
            $this->addFlash('success', 'Product updated successfully.');

            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $product->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'You can only delete your own products.');
            return $this->redirectToRoute('app_product_index');
        }

        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $name = $product->getName();
            $id = $product->getId();

            $entityManager->remove($product);
            $entityManager->flush();

            $this->activityLogger->logProductDelete($this->getUser(), $name, $id);
            $this->addFlash('success', 'Product deleted successfully.');
        }

        return $this->redirectToRoute('app_product_index');
    }
}
