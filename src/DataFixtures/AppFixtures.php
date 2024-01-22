<?php

namespace App\DataFixtures;

use App\Entity\Account;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher
    ) {
    }
    public function load(ObjectManager $manager): void
    {
        //create a customer
        $customer = new Account();
        $customer->setEmail("user@myapi.com");
        $customer->setRoles(["ROLE_USER"]);
        $customer->setPassword($this->userPasswordHasher->hashPassword($customer, "customer"));
        $manager->persist($customer);
        //create an admin
        $admin = new Account();
        $admin->setEmail("admin@myapi.com");
        $admin->setRoles(["ROLE_ADMIN"]);
        $admin->setPassword($this->userPasswordHasher->hashPassword($admin,"admin"));
        $manager->persist($admin);

        $manager->flush();
    }
}
