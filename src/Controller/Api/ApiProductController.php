<?php
namespace App\Controller\Api;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ApiProductController extends AbstractController
{
    #[Route('/products', name: 'api_products', methods: ['GET'])]
    public function list(ProductRepository $repo): JsonResponse
    {
        $products = $repo->findAll();
        $data = array_map(fn($p) => [
            'id'          => $p->getId(),
            'name'        => $p->getName(),
            'description' => $p->getDescription(),
            'price'       => $p->getPrice(),
            'stock'       => $p->getStock(),
            'createdBy'   => $p->getCreatedBy()?->getName(),
            'createdAt'   => $p->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt'   => $p->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ], $products);

        return $this->json($data);
    }

    #[Route('/products/{id}', name: 'api_product_show', methods: ['GET'])]
    public function show(int $id, ProductRepository $repo): JsonResponse
    {
        $product = $repo->find($id);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        return $this->json([
            'id'          => $product->getId(),
            'name'        => $product->getName(),
            'description' => $product->getDescription(),
            'price'       => $product->getPrice(),
            'stock'       => $product->getStock(),
            'createdBy'   => $product->getCreatedBy()?->getName(),
            'createdAt'   => $product->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt'   => $product->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }
}