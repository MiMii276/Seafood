<?php

namespace App\Controller\Api;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/customer', name: 'api_customer_')]
class CustomerApiController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private Connection $connection
    ) {}

    #[Route('/products', name: 'products', methods: ['GET'])]
    public function getProducts(): JsonResponse
    {
        try {
            $sql = 'SELECT id, name, description, price, stock FROM product';
            $products = $this->connection->fetchAllAssociative($sql);

            return $this->success($products, 'Products retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to load products: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/catering', name: 'catering', methods: ['GET'])]
    public function getCatering(): JsonResponse
    {
        try {
            $sql = 'SELECT 
                        id, 
                        name, 
                        description,
                        price, 
                        status,
                        event_date AS eventDate, 
                        number_of_guests AS numberOfGuests 
                    FROM catering';
            
            $cateringBookings = $this->connection->fetchAllAssociative($sql);

            return $this->success($cateringBookings, 'Catering data retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to load catering options: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        $user = $this->getUser();

        $profileData = [
            'id' => $user ? $user->getId() : 1,
            'name' => $user && method_exists($user, 'getName') ? $user->getName() : 'Valued Seafoodie Member',
            'email' => $user ? $user->getUserIdentifier() : 'customer@example.com',
            'role' => 'ROLE_CUSTOMER',
            'isActive' => true,
            'createdAt' => '2026-05-22'
        ];

        return $this->success($profileData, 'Customer profile retrieved successfully');
    }
}