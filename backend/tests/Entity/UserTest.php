<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testGetRolesAlwaysContainsRoleUser(): void
    {
        $this->assertContains('ROLE_USER', $this->user->getRoles());
    }

    public function testGetRolesWithAdminIncludesBothRoles(): void
    {
        $this->user->setRoles(['ROLE_ADMIN']);
        $roles = $this->user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testGetRolesAreUnique(): void
    {
        // ROLE_USER est toujours ajouté — ne doit pas créer de doublon
        $this->user->setRoles(['ROLE_USER']);
        $roles = $this->user->getRoles();

        $this->assertEquals($roles, array_unique($roles));
        $this->assertCount(1, $roles);
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $this->user->setEmail('jean@mmotors.fr');
        $this->assertEquals('jean@mmotors.fr', $this->user->getUserIdentifier());
    }

    public function testIsAdminFalse(): void
    {
        $this->user->setIsAdmin(false);
        $this->assertFalse($this->user->isAdmin());
    }

    public function testIsAdminTrue(): void
    {
        $this->user->setIsAdmin(true);
        $this->assertTrue($this->user->isAdmin());
    }

    public function testSettersReturnFluentInterface(): void
    {
        $result = $this->user
            ->setEmail('test@mmotors.fr')
            ->setFirstName('Jean')
            ->setLastName('Dupont')
            ->setIsAdmin(false);

        $this->assertSame($this->user, $result);
    }

    public function testFullNameFields(): void
    {
        $this->user->setFirstName('Marie');
        $this->user->setLastName('Martin');

        $this->assertEquals('Marie', $this->user->getFirstName());
        $this->assertEquals('Martin', $this->user->getLastName());
    }

    public function testPhoneIsNullable(): void
    {
        $this->user->setPhone('0612345678');
        $this->assertEquals('0612345678', $this->user->getPhone());

        $this->user->setPhone(null);
        $this->assertNull($this->user->getPhone());
    }

    public function testAddressFields(): void
    {
        $this->user
            ->setAddress('15 rue de la Paix')
            ->setCity('Paris')
            ->setZipCode('75001');

        $this->assertEquals('15 rue de la Paix', $this->user->getAddress());
        $this->assertEquals('Paris', $this->user->getCity());
        $this->assertEquals('75001', $this->user->getZipCode());
    }

    public function testAddressFieldsAreNullable(): void
    {
        $this->user->setAddress(null);
        $this->user->setCity(null);
        $this->user->setZipCode(null);

        $this->assertNull($this->user->getAddress());
        $this->assertNull($this->user->getCity());
        $this->assertNull($this->user->getZipCode());
    }
}
