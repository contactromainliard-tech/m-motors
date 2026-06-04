<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * Tests unitaires du UserRepository.
 * Couvre upgradePassword et la validation du type utilisateur.
 */
class UserRepositoryTest extends KernelTestCase
{
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->userRepository = static::getContainer()->get(UserRepository::class);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->getConnection()->executeStatement('DELETE FROM dossier');
        $em->getConnection()->executeStatement('DELETE FROM vehicle');
        $em->getConnection()->executeStatement('DELETE FROM "user"');
    }

    public function testUpgradePasswordUpdatesHash(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();

        $user = new User();
        $user->setEmail('upgrade@test.fr');
        $user->setPassword('old_hash');
        $user->setFirstName('Jean');
        $user->setLastName('Dupont');
        $user->setIsAdmin(false);
        $user->setRoles(['ROLE_USER']);
        $em->persist($user);
        $em->flush();

        $this->userRepository->upgradePassword($user, 'new_hashed_password');

        $em->clear();
        $refreshed = $this->userRepository->findOneBy(['email' => 'upgrade@test.fr']);
        $this->assertEquals('new_hashed_password', $refreshed->getPassword());
    }

    public function testUpgradePasswordThrowsForUnsupportedUser(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $unsupported = new class implements \Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface {
            public function getPassword(): ?string { return 'hash'; }
            public function getUserIdentifier(): string { return 'test'; }
        };

        $this->userRepository->upgradePassword($unsupported, 'new_hash');
    }
}
