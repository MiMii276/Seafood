<?php
namespace App\Controller\Api;

use App\Repository\CateringRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ApiCateringController extends AbstractController
{
    #[Route('/catering', name: 'api_catering', methods: ['GET'])]
    public function list(CateringRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        $items = $this->isGranted('ROLE_ADMIN')
            ? $repo->findAll()
            : $repo->findBy(['createdBy' => $user]);

        $data = array_map(fn($c) => [
            'id'             => $c->getId(),
            'name'           => $c->getName(),
            'description'    => $c->getDescription(),
            'eventDate'      => $c->getEventDate()->format('Y-m-d H:i:s'),
            'numberOfGuests' => $c->getNumberOfGuests(),
            'price'          => $c->getPrice(),
            'status'         => $c->getStatus(),
            'createdBy'      => $c->getCreatedBy()?->getName(),
            'createdAt'      => $c->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt'      => $c->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'products'       => $c->getProducts()->map(fn($p) => [
                'id'    => $p->getId(),
                'name'  => $p->getName(),
                'price' => $p->getPrice(),
            ])->toArray(),
        ], $items);

        return $this->json($data);
    }

    #[Route('/catering/{id}', name: 'api_catering_show', methods: ['GET'])]
    public function show(int $id, CateringRepository $repo): JsonResponse
    {
        $catering = $repo->find($id);
        if (!$catering) {
            return $this->json(['error' => 'Catering not found'], 404);
        }

        return $this->json([
            'id'             => $catering->getId(),
            'name'           => $catering->getName(),
            'description'    => $catering->getDescription(),
            'eventDate'      => $catering->getEventDate()->format('Y-m-d H:i:s'),
            'numberOfGuests' => $catering->getNumberOfGuests(),
            'price'          => $catering->getPrice(),
            'status'         => $catering->getStatus(),
            'createdBy'      => $catering->getCreatedBy()?->getName(),
            'createdAt'      => $catering->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt'      => $catering->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'products'       => $catering->getProducts()->map(fn($p) => [
                'id'    => $p->getId(),
                'name'  => $p->getName(),
                'price' => $p->getPrice(),
            ])->toArray(),
        ]);
    }
}