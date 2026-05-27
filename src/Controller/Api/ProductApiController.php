<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/products')]
class ProductApiController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('', name: 'api_products_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): JsonResponse
    {
        $products = array_map(
            fn (Product $product): array => $this->serializeProduct($product),
            $productRepository->findBy([], ['createdAt' => 'DESC'])
        );

        return $this->success([
            'products' => $products,
        ], 'Products retrieved successfully');
    }

    #[Route('/{id}', name: 'api_products_show', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return $this->success([
            'product' => $this->serializeProduct($product),
        ], 'Product retrieved successfully');
    }

    private function serializeProduct(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'stock' => $product->getStock(),
            'createdAt' => $product->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $product->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }
}
