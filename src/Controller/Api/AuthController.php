<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    use ApiResponseTrait;

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = $this->requestData($request->getContent());
        if ($data === null) {
            return $this->error('Invalid JSON payload', 400);
        }
        
        $email = $data['email'] ?? null;
        $name = $data['name'] ?? null;
        $password = $data['password'] ?? null;
        
        $errors = [];
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email address is required.';
        }

        if (!$name || trim((string) $name) === '') {
            $errors['name'] = 'Name is required.';
        }

        if (!$password || strlen((string) $password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }

        if ($errors !== []) {
            return $this->error('Please correct the highlighted fields', 422, $errors);
        }
        
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->error('User already exists', 409, [
                'email' => 'This email is already registered.',
            ]);
        }
        
        $user = new User();
        $user->setEmail(strtolower(trim((string) $email)));
        $user->setName(trim((string) $name));
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setIsActive(true);
        $user->setIsVerified(false);
        $user->setRoles(['ROLE_USER']);
        
        $entityManager->persist($user);
        $entityManager->flush();
        
        return $this->success([
            'user' => $this->serializeUser($user),
        ], 'Registration successful', 201);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function apiLogin(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = $this->requestData($request->getContent());
        if ($data === null) {
            return $this->error('Invalid JSON payload', 400);
        }

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->error('Email and password fields are required.', 400);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => strtolower(trim((string)$email))]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->error('Invalid credentials, please check your spelling.', 401);
        }

        // Mock token generation for mobile state tracking matching your frontend expectations
        $mobileToken = base64_encode(random_bytes(32));

        return $this->success([
            'token' => $mobileToken,
            'user' => $this->serializeUser($user),
        ], 'Authentication successful');
    }

    #[Route('/api/profile', name: 'api_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->error('Authentication required', 401);
        }
        
        return $this->success([
            'user' => $this->serializeUser($user),
        ], 'Profile retrieved successfully');
    }

    #[Route('/api/profile', name: 'api_profile_update', methods: ['PATCH'])]
    public function updateProfile(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->error('Authentication required', 401);
        }

        $data = $this->requestData($request->getContent());
        if ($data === null) {
            return $this->error('Invalid JSON payload', 400);
        }

        $errors = [];
        if (array_key_exists('name', $data) && trim((string) $data['name']) === '') {
            $errors['name'] = 'Name cannot be blank.';
        }

        if (array_key_exists('email', $data) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email address is required.';
        }

        if ($errors !== []) {
            return $this->error('Please correct the highlighted fields', 422, $errors);
        }

        if (array_key_exists('name', $data)) {
            $user->setName(trim((string) $data['name']));
        }

        if (array_key_exists('email', $data)) {
            $email = strtolower(trim((string) $data['email']));
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($existingUser instanceof User && $existingUser->getId() !== $user->getId()) {
                return $this->error('Email is already in use', 409, [
                    'email' => 'This email is already registered.',
                ]);
            }

            $user->setEmail($email);
        }

        $user->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return $this->success([
            'user' => $this->serializeUser($user),
        ], 'Profile updated successfully');
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'roleLabel' => $user->getRoleDisplay() === 'User' ? 'Customer' : $user->getRoleDisplay(),
            'verified' => $user->isVerified(),
            'createdAt' => $user->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $user->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }
}