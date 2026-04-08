<?php
namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ApiUserController extends AbstractController
{
    #[Route('/profile', name: 'api_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();
        return $this->json([
            'id'        => $user->getId(),
            'name'      => $user->getName(),
            'email'     => $user->getEmail(),
            'roles'     => $user->getRoles(),
            'role'      => $user->getRoleDisplay(),
            'isActive'  => $user->isActive(),
            'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }
}