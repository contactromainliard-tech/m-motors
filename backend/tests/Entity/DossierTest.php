<?php

namespace App\Tests\Entity;

use App\Entity\Dossier;
use App\Entity\Document;
use App\Entity\User;
use App\Entity\Vehicle;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class DossierTest extends TestCase
{
    private Dossier $dossier;

    protected function setUp(): void
    {
        $this->dossier = new Dossier();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->dossier->getId());
        $this->assertNull($this->dossier->getType());
        $this->assertNull($this->dossier->getStatus());
        $this->assertNull($this->dossier->getCreatedAt());
        $this->assertNull($this->dossier->getUpdatedAt());
        $this->assertNull($this->dossier->getComment());
        $this->assertNull($this->dossier->getClient());
        $this->assertNull($this->dossier->getVehicle());
        $this->assertInstanceOf(Collection::class, $this->dossier->getDocuments());
        $this->assertCount(0, $this->dossier->getDocuments());
    }

    public function testAddDocumentSetsOwningRelation(): void
    {
        $document = new Document();
        $this->dossier->addDocument($document);

        $this->assertCount(1, $this->dossier->getDocuments());
        $this->assertSame($this->dossier, $document->getDossier());
    }

    public function testAddDocumentIgnoresDuplicate(): void
    {
        $document = new Document();
        $this->dossier->addDocument($document);
        $this->dossier->addDocument($document);

        $this->assertCount(1, $this->dossier->getDocuments());
    }

    public function testAddMultipleDocuments(): void
    {
        $doc1 = new Document();
        $doc2 = new Document();
        $this->dossier->addDocument($doc1);
        $this->dossier->addDocument($doc2);

        $this->assertCount(2, $this->dossier->getDocuments());
    }

    public function testRemoveDocumentClearsOwningRelation(): void
    {
        $document = new Document();
        $this->dossier->addDocument($document);
        $this->dossier->removeDocument($document);

        $this->assertCount(0, $this->dossier->getDocuments());
        $this->assertNull($document->getDossier());
    }

    public function testRemoveDocumentNotInCollection(): void
    {
        $document = new Document();
        // Ne doit pas lever d'exception
        $this->dossier->removeDocument($document);

        $this->assertCount(0, $this->dossier->getDocuments());
    }

    public function testStatusTransitions(): void
    {
        foreach (['pending', 'in_progress', 'approved', 'rejected'] as $status) {
            $this->dossier->setStatus($status);
            $this->assertEquals($status, $this->dossier->getStatus());
        }
    }

    public function testTypeValues(): void
    {
        $this->dossier->setType('purchase');
        $this->assertEquals('purchase', $this->dossier->getType());

        $this->dossier->setType('rental');
        $this->assertEquals('rental', $this->dossier->getType());
    }

    public function testClientRelation(): void
    {
        $user = new User();
        $this->dossier->setClient($user);

        $this->assertSame($user, $this->dossier->getClient());
    }

    public function testVehicleRelation(): void
    {
        $vehicle = new Vehicle();
        $this->dossier->setVehicle($vehicle);

        $this->assertSame($vehicle, $this->dossier->getVehicle());
    }

    public function testTimestamps(): void
    {
        $createdAt = new \DateTimeImmutable('2026-01-15 10:00:00');
        $updatedAt = new \DateTimeImmutable('2026-01-16 14:30:00');

        $this->dossier->setCreatedAt($createdAt);
        $this->dossier->setUpdatedAt($updatedAt);

        $this->assertSame($createdAt, $this->dossier->getCreatedAt());
        $this->assertSame($updatedAt, $this->dossier->getUpdatedAt());
    }

    public function testUpdatedAtIsNullable(): void
    {
        $this->dossier->setUpdatedAt(null);
        $this->assertNull($this->dossier->getUpdatedAt());
    }

    public function testCommentIsNullable(): void
    {
        $this->dossier->setComment('Documents insuffisants, merci de renvoyer la fiche de paie.');
        $this->assertNotNull($this->dossier->getComment());

        $this->dossier->setComment(null);
        $this->assertNull($this->dossier->getComment());
    }
}
