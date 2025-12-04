<?php

namespace App\DataFixtures;

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
    // Admin user
    $admin = new User();
    $admin->setEmail('admin@mob.ch');
    $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
    $admin->setPassword(
      $this->passwordHasher->hashPassword($admin, 'admin123')
    );
    $manager->persist($admin);

    // Regular user
    $user = new User();
    $user->setEmail('user@mob.ch');
    $user->setRoles(['ROLE_USER']);
    $user->setPassword(
      $this->passwordHasher->hashPassword($user, 'user123')
    );
    $manager->persist($user);

    // Test user
    $testUser = new User();
    $testUser->setEmail('test@mob.ch');
    $testUser->setRoles(['ROLE_USER']);
    $testUser->setPassword(
      $this->passwordHasher->hashPassword($testUser, 'test123')
    );
    $manager->persist($testUser);

    $manager->flush();

    echo "✅ 3 utilisateurs créés : admin@mob.ch, user@mob.ch, test@mob.ch\n";
  }
}
