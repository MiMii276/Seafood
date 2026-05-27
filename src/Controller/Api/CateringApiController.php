<?php

namespace App\Controller\Api;

use App\Entity\Catering;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CateringRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/bookings')]
class CateringApiController extends AbstractController
{
    use ApiResponseTrait;

    private const CUSTOMER_STATUSES = ['Pending', 'Cancelled'];

    #[Route('', name: 'api_bookings_index', methods: ['GET'])]
    public function index(CateringRepository $cateringRepository): JsonResponse
    {
        $user = $this->requireUser();

        $bookings = $this->isGranted('ROLE_STAFF')
            ? $cateringRepository->findBy([], ['createdAt' => 'DESC'])
            : $cateringRepository->findBy(['createdBy' => $user], ['createdAt' => 'DESC']);

        return $this->success([
            'bookings' => array_map(fn (Catering $booking): array => $this->serializeBooking($booking), $bookings),
        ], 'Bookings retrieved successfully');
    }

    #[Route('', name: 'api_bookings_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = $this->requestData($request->getContent());
        if ($data === null) {
            return $this->error('Invalid JSON payload', 400);
        }

        $errors = $this->validateBookingPayload($data);
        if ($errors !== []) {
            return $this->error('Please correct the highlighted fields', 422, $errors);
        }

        $booking = new Catering();
        $booking->setName(trim((string) $data['name']));
        $booking->setDescription($data['description'] ?? null);
        $booking->setEventDate(new \DateTime((string) $data['eventDate']));
        $booking->setNumberOfGuests((int) $data['numberOfGuests']);
        $booking->setPrice((float) ($data['price'] ?? 0));
        $booking->setStatus('Pending');
        $booking->setCreatedBy($this->requireUser());
        $this->syncProducts($booking, $data['productIds'] ?? [], $entityManager);

        $entityManager->persist($booking);
        $entityManager->flush();

        return $this->success([
            'booking' => $this->serializeBooking($booking),
        ], 'Booking created successfully', 201);
    }

    #[Route('/{id}', name: 'api_bookings_show', methods: ['GET'])]
    public function show(Catering $booking): JsonResponse
    {
        if (!$this->canAccessBooking($booking)) {
            return $this->error('You are not allowed to view this booking', 403);
        }

        return $this->success([
            'booking' => $this->serializeBooking($booking),
        ], 'Booking retrieved successfully');
    }

    #[Route('/{id}', name: 'api_bookings_update', methods: ['PATCH'])]
    public function update(Catering $booking, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->canAccessBooking($booking)) {
            return $this->error('You are not allowed to update this booking', 403);
        }

        $data = $this->requestData($request->getContent());
        if ($data === null) {
            return $this->error('Invalid JSON payload', 400);
        }

        $errors = $this->validateBookingPayload($data, partial: true);
        if ($errors !== []) {
            return $this->error('Please correct the highlighted fields', 422, $errors);
        }

        if (array_key_exists('name', $data)) {
            $booking->setName(trim((string) $data['name']));
        }

        if (array_key_exists('description', $data)) {
            $booking->setDescription($data['description']);
        }

        if (array_key_exists('eventDate', $data)) {
            $booking->setEventDate(new \DateTime((string) $data['eventDate']));
        }

        if (array_key_exists('numberOfGuests', $data)) {
            $booking->setNumberOfGuests((int) $data['numberOfGuests']);
        }

        if (array_key_exists('price', $data)) {
            $booking->setPrice((float) $data['price']);
        }

        if (array_key_exists('status', $data)) {
            if (!$this->isGranted('ROLE_STAFF') && !in_array($data['status'], self::CUSTOMER_STATUSES, true)) {
                return $this->error('Customers can only set booking status to Pending or Cancelled', 403);
            }

            $booking->setStatus((string) $data['status']);
        }

        if (array_key_exists('productIds', $data)) {
            $this->syncProducts($booking, $data['productIds'], $entityManager);
        }

        $booking->setUpdatedAt(new \DateTime());
        $entityManager->flush();

        return $this->success([
            'booking' => $this->serializeBooking($booking),
        ], 'Booking updated successfully');
    }

    #[Route('/{id}', name: 'api_bookings_delete', methods: ['DELETE'])]
    public function delete(Catering $booking, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->canAccessBooking($booking)) {
            return $this->error('You are not allowed to delete this booking', 403);
        }

        $booking->setStatus('Cancelled');
        $booking->setUpdatedAt(new \DateTime());
        $entityManager->flush();

        return $this->success([
            'booking' => $this->serializeBooking($booking),
        ], 'Booking cancelled successfully');
    }

    private function validateBookingPayload(array $data, bool $partial = false): array
    {
        $errors = [];

        if (!$partial || array_key_exists('name', $data)) {
            if (!isset($data['name']) || trim((string) $data['name']) === '') {
                $errors['name'] = 'Booking name is required.';
            }
        }

        if (!$partial || array_key_exists('eventDate', $data)) {
            if (empty($data['eventDate']) || strtotime((string) $data['eventDate']) === false) {
                $errors['eventDate'] = 'A valid event date is required.';
            }
        }

        if (!$partial || array_key_exists('numberOfGuests', $data)) {
            if (!isset($data['numberOfGuests']) || (int) $data['numberOfGuests'] < 1) {
                $errors['numberOfGuests'] = 'Number of guests must be at least 1.';
            }
        }

        if (array_key_exists('price', $data) && (float) $data['price'] < 0) {
            $errors['price'] = 'Price cannot be negative.';
        }

        if (array_key_exists('productIds', $data) && !is_array($data['productIds'])) {
            $errors['productIds'] = 'Product IDs must be an array.';
        }

        return $errors;
    }

    private function syncProducts(Catering $booking, array $productIds, EntityManagerInterface $entityManager): void
    {
        foreach ($booking->getProducts()->toArray() as $product) {
            $booking->removeProduct($product);
        }

        foreach (array_unique($productIds) as $productId) {
            $product = $entityManager->getRepository(Product::class)->find((int) $productId);
            if ($product instanceof Product) {
                $booking->addProduct($product);
            }
        }
    }

    private function serializeBooking(Catering $booking): array
    {
        return [
            'id' => $booking->getId(),
            'name' => $booking->getName(),
            'description' => $booking->getDescription(),
            'eventDate' => $booking->getEventDate()->format(DATE_ATOM),
            'numberOfGuests' => $booking->getNumberOfGuests(),
            'price' => $booking->getPrice(),
            'status' => $booking->getStatus(),
            'customer' => [
                'id' => $booking->getCreatedBy()?->getId(),
                'name' => $booking->getCreatedBy()?->getName(),
                'email' => $booking->getCreatedBy()?->getEmail(),
            ],
            'products' => array_map(fn (Product $product): array => [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
            ], $booking->getProducts()->toArray()),
            'createdAt' => $booking->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $booking->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }

    private function canAccessBooking(Catering $booking): bool
    {
        return $this->isGranted('ROLE_STAFF') || $booking->getCreatedBy() === $this->requireUser();
    }

    private function requireUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
