<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Product;
use App\Entity\Catering;
use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogger
{
    private $session;

    public function __construct(
        private EntityManagerInterface $entityManager,
        RequestStack $requestStack
    ) {
        $this->session = $requestStack->getSession();
    }

    // Make this PUBLIC so GoogleAuthenticator can call it
    public function log(User $user, string $action, string $targetData): void
    {
        $log = new ActivityLog();
        $log->setUserId($user->getId());
        $log->setUsername($user->getEmail());
        $log->setRole($user->getRoleDisplay());
        $log->setAction($action);
        $log->setTargetData($targetData);

        $this->entityManager->persist($log);
        $this->entityManager->flush(); // Add flush here
    }

    public function logLogin(User $user): void
    {
        // Remove session check to always log Google logins
        $this->log($user, 'LOGIN', 'User logged in');
        $this->session->set('login_logged', true);
    }

    public function logLogout(User $user): void
    {
        $this->log($user, 'LOGOUT', 'User logged out');
        $this->session->remove('login_logged');
    }

    public function logProductCreate(User $user, Product $product): void
    {
        $this->log($user, 'CREATE PRODUCT', 'Created product: ' . $product->getName());
    }

    public function logProductUpdate(User $user, Product $product): void
    {
        $this->log($user, 'UPDATE PRODUCT', 'Updated product: ' . $product->getName());
    }

    public function logProductDelete(User $user, string $name, int $id): void
    {
        $this->log($user, 'DELETE PRODUCT', "Deleted product: $name (ID: $id)");
    }

    public function logUserCreate(User $admin, User $user): void
    {
        $this->log($admin, 'CREATE USER', 'Created user: ' . $user->getEmail());
    }

    public function logUserUpdate(User $admin, User $user): void
    {
        $this->log($admin, 'UPDATE USER', 'Updated user: ' . $user->getEmail());
    }

    public function logUserDelete(User $admin, User $user): void
    {
        $this->log($admin, 'DELETE USER', 'Deleted user: ' . $user->getEmail());
    }

    // === Catering logging methods ===

    public function logCateringCreate(User $user, Catering $catering): void
    {
        $this->log($user, 'CREATE CATERING', 'Created catering: ' . $catering->getName());
    }

    public function logCateringUpdate(User $user, Catering $catering): void
    {
        $this->log($user, 'UPDATE CATERING', 'Updated catering: ' . $catering->getName());
    }

    public function logCateringDelete(User $user, Catering $catering): void
    {
        $this->log($user, 'DELETE CATERING', 'Deleted catering: ' . $catering->getName() . ' (ID: ' . $catering->getId() . ')');
    }
}