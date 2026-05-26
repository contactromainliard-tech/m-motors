<?php

namespace App\Tests\Entity;

use App\Entity\Vehicle;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class VehicleTest extends TestCase
{
    private Vehicle $vehicle;

    protected function setUp(): void
    {
        $this->vehicle = new Vehicle();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->vehicle->getId());
        $this->assertNull($this->vehicle->getBrand());
        $this->assertNull($this->vehicle->getModel());
        $this->assertNull($this->vehicle->getYear());
        $this->assertNull($this->vehicle->getKilometrage());
        $this->assertNull($this->vehicle->getPrice());
        $this->assertNull($this->vehicle->getType());
        $this->assertNull($this->vehicle->getDescription());
        $this->assertNull($this->vehicle->getPhotoUrl());
        $this->assertNull($this->vehicle->isAvailable());
        $this->assertInstanceOf(Collection::class, $this->vehicle->getDossiers());
        $this->assertCount(0, $this->vehicle->getDossiers());
    }

    public function testSettersReturnFluentInterface(): void
    {
        $result = $this->vehicle
            ->setBrand('Renault')
            ->setModel('Clio')
            ->setYear(2021)
            ->setKilometrage(35000)
            ->setPrice(12500.0)
            ->setType('sale')
            ->setIsAvailable(true);

        $this->assertSame($this->vehicle, $result);
    }

    public function testBrandAndModel(): void
    {
        $this->vehicle->setBrand('Peugeot');
        $this->vehicle->setModel('308');

        $this->assertEquals('Peugeot', $this->vehicle->getBrand());
        $this->assertEquals('308', $this->vehicle->getModel());
    }

    public function testYearAndKilometrage(): void
    {
        $this->vehicle->setYear(2019);
        $this->vehicle->setKilometrage(0);

        $this->assertEquals(2019, $this->vehicle->getYear());
        $this->assertEquals(0, $this->vehicle->getKilometrage());
    }

    public function testPriceAsFloat(): void
    {
        $this->vehicle->setPrice(15999.99);
        $this->assertEquals(15999.99, $this->vehicle->getPrice());
    }

    public function testTypeCanBeSale(): void
    {
        $this->vehicle->setType('sale');
        $this->assertEquals('sale', $this->vehicle->getType());
    }

    public function testTypeCanBeRental(): void
    {
        $this->vehicle->setType('rental');
        $this->assertEquals('rental', $this->vehicle->getType());
    }

    public function testIsAvailableTrue(): void
    {
        $this->vehicle->setIsAvailable(true);
        $this->assertTrue($this->vehicle->isAvailable());
    }

    public function testIsAvailableFalse(): void
    {
        $this->vehicle->setIsAvailable(false);
        $this->assertFalse($this->vehicle->isAvailable());
    }

    public function testDescriptionIsNullable(): void
    {
        $this->vehicle->setDescription('Très bon état, révision récente.');
        $this->assertEquals('Très bon état, révision récente.', $this->vehicle->getDescription());

        $this->vehicle->setDescription(null);
        $this->assertNull($this->vehicle->getDescription());
    }

    public function testPhotoUrlIsNullable(): void
    {
        $this->vehicle->setPhotoUrl('/uploads/vehicles/clio.jpg');
        $this->assertEquals('/uploads/vehicles/clio.jpg', $this->vehicle->getPhotoUrl());

        $this->vehicle->setPhotoUrl(null);
        $this->assertNull($this->vehicle->getPhotoUrl());
    }
}
