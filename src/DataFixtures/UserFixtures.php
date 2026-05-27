<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Create Admin User
        $admin = new User();
        $admin->setEmail('admin@seafoodie.com');
        $admin->setName('Admin User');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'admin123')
        );
        $admin->setIsActive(true);
        $manager->persist($admin);

        // Create Staff User
        $staff = new User();
        $staff->setEmail('staff@seafoodie.com');
        $staff->setName('Staff User');
        $staff->setRoles(['ROLE_STAFF']);
        $staff->setPassword(
            $this->passwordHasher->hashPassword($staff, 'staff123')
        );
        $staff->setIsActive(true);
        $manager->persist($staff);

        // Create another Staff User
        $staff2 = new User();
        $staff2->setEmail('staff2@seafoodie.com');
        $staff2->setName('Staff User Two');
        $staff2->setRoles(['ROLE_STAFF']);
        $staff2->setPassword(
            $this->passwordHasher->hashPassword($staff2, 'staff123')
        );
        $staff2->setIsActive(true);
        $manager->persist($staff2);

        $products = [
            ['Grilled Tuna Belly', 'Fresh tuna belly prepared for catering trays.', 450.00, 25, $staff],
            ['Garlic Butter Shrimp', 'Shrimp cooked with garlic butter sauce.', 380.00, 40, $staff],
            ['Seafood Bilao', 'Assorted seafood platter for group orders.', 1500.00, 12, $admin],
        ];

        foreach ($products as [$name, $description, $price, $stock, $createdBy]) {
            $product = new Product();
            $product->setName($name);
            $product->setDescription($description);
            $product->setPrice($price);
            $product->setStock($stock);
            $product->setCreatedBy($createdBy);
            $manager->persist($product);
        }

        $manager->flush();
    }
}
