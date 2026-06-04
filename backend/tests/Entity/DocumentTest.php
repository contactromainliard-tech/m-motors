<?php

namespace App\Tests\Entity;

use App\Entity\Document;
use App\Entity\Dossier;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de l entite Document.
 */
class DocumentTest extends TestCase
{
    private Document $document;

    protected function setUp(): void
    {
        $this->document = new Document();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->document->getId());
        $this->assertNull($this->document->getFilename());
        $this->assertNull($this->document->getOriginalName());
        $this->assertNull($this->document->getMimeType());
        $this->assertNull($this->document->getSize());
        $this->assertNull($this->document->getType());
        $this->assertNull($this->document->getUploadedAt());
        $this->assertNull($this->document->getDossier());
    }

    public function testSettersReturnFluentInterface(): void
    {
        $result = $this->document
            ->setFilename('doc_abc123.pdf')
            ->setOriginalName('carte_identite.pdf')
            ->setMimeType('application/pdf')
            ->setSize(512000)
            ->setType('cni')
            ->setUploadedAt(new \DateTimeImmutable());

        $this->assertSame($this->document, $result);
    }

    public function testFilenameAndOriginalName(): void
    {
        $this->document->setFilename('doc_abc123.pdf');
        $this->document->setOriginalName('carte_identite.pdf');

        $this->assertEquals('doc_abc123.pdf', $this->document->getFilename());
        $this->assertEquals('carte_identite.pdf', $this->document->getOriginalName());
    }

    public function testMimeType(): void
    {
        $this->document->setMimeType('image/jpeg');
        $this->assertEquals('image/jpeg', $this->document->getMimeType());
    }

    public function testSize(): void
    {
        $this->document->setSize(1048576);
        $this->assertEquals(1048576, $this->document->getSize());
    }

    public function testTypeValues(): void
    {
        foreach (['cni', 'justificatif_domicile', 'fiche_paie', 'rib', 'other'] as $type) {
            $this->document->setType($type);
            $this->assertEquals($type, $this->document->getType());
        }
    }

    public function testUploadedAt(): void
    {
        $now = new \DateTimeImmutable('2026-01-15 10:30:00');
        $this->document->setUploadedAt($now);
        $this->assertSame($now, $this->document->getUploadedAt());
    }

    public function testDossierRelation(): void
    {
        $dossier = new Dossier();
        $this->document->setDossier($dossier);
        $this->assertSame($dossier, $this->document->getDossier());
    }

    public function testDossierIsNullable(): void
    {
        $dossier = new Dossier();
        $this->document->setDossier($dossier);
        $this->document->setDossier(null);
        $this->assertNull($this->document->getDossier());
    }
}
