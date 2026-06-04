<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests unitaires du controller des dossiers.
 * Verifie la creation, la consultation et la validation des dossiers.
 */
class DossierControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private string $clientToken;
    private string $adminToken;
    private int $vehicleId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $em = $container->get("doctrine")->getManager();
        $em->getConnection()->executeStatement("DELETE FROM dossier");
        $em->getConnection()->executeStatement("DELETE FROM vehicle");
        $em->getConnection()->executeStatement("DELETE FROM \"user\"");

        // Creation du compte client
        $this->client->request("POST", "/api/auth/register", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "client@test.fr",
            "password" => "Test1234!",
            "firstName" => "Jean",
            "lastName" => "Dupont",
        ]));

        $this->client->request("POST", "/api/auth/login", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "client@test.fr",
            "password" => "Test1234!",
        ]));

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->clientToken = $data["token"];

        // Creation du compte admin
        $this->client->request("POST", "/api/auth/register", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "admin@test.fr",
            "password" => "Admin1234!",
            "firstName" => "Admin",
            "lastName" => "Test",
        ]));

        $userRepo = $em->getRepository(\App\Entity\User::class);
        $admin = $userRepo->findOneBy(["email" => "admin@test.fr"]);
        $admin->setIsAdmin(true);
        $admin->setRoles(["ROLE_ADMIN"]);
        $em->flush();

        $this->client->request("POST", "/api/auth/login", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "admin@test.fr",
            "password" => "Admin1234!",
        ]));

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->adminToken = $data["token"];

        // Creation d un vehicule
        $this->client->request("POST", "/api/vehicles", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode([
            "brand" => "Renault",
            "model" => "Clio",
            "year" => 2021,
            "kilometrage" => 35000,
            "price" => 12500,
            "type" => "sale",
        ]));

        $vehicle = json_decode($this->client->getResponse()->getContent(), true);
        $this->vehicleId = $vehicle["id"];
    }

    public function testCreateDossier(): void
    {
        $this->client->request("POST", "/api/dossiers", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->clientToken,
        ], json_encode([
            "vehicleId" => $this->vehicleId,
            "type" => "purchase",
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("pending", $data["status"]);
        $this->assertEquals("purchase", $data["type"]);
    }

    public function testCreateDossierUnauthorized(): void
    {
        $this->client->request("POST", "/api/dossiers", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "vehicleId" => $this->vehicleId,
            "type" => "purchase",
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetMyDossiers(): void
    {
        // Creation d un dossier
        $this->client->request("POST", "/api/dossiers", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->clientToken,
        ], json_encode([
            "vehicleId" => $this->vehicleId,
            "type" => "purchase",
        ]));

        // Consultation des dossiers
        $this->client->request("GET", "/api/dossiers/my-dossiers", [], [], [
            "HTTP_AUTHORIZATION" => "Bearer " . $this->clientToken,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
    }

    public function testValidateDossier(): void
    {
        // Creation d un dossier
        $this->client->request("POST", "/api/dossiers", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->clientToken,
        ], json_encode([
            "vehicleId" => $this->vehicleId,
            "type" => "purchase",
        ]));

        $dossier = json_decode($this->client->getResponse()->getContent(), true);

        // Validation du dossier
        $this->client->request("PATCH", "/api/dossiers/" . $dossier["id"] . "/validate", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode([
            "status" => "approved",
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("approved", $data["status"]);
    }

    public function testValidateDossierInProgress(): void
    {
        // Creation d un dossier
        $this->client->request("POST", "/api/dossiers", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->clientToken,
        ], json_encode([
            "vehicleId" => $this->vehicleId,
            "type" => "purchase",
        ]));

        $dossier = json_decode($this->client->getResponse()->getContent(), true);

        // Passage en cours de traitement
        $this->client->request("PATCH", "/api/dossiers/" . $dossier["id"] . "/validate", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode([
            "status" => "in_progress",
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("in_progress", $data["status"]);
    }

    public function testGetAllDossiersAsAdmin(): void
    {
        $this->client->request("POST", "/api/dossiers", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->clientToken,
        ], json_encode(["vehicleId" => $this->vehicleId, "type" => "purchase"]));

        $this->client->request("GET", "/api/dossiers", [], [], [
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(1, count($data));
    }

    public function testShowDossierAsAdmin(): void
    {
        $this->client->request("POST", "/api/dossiers", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->clientToken,
        ], json_encode(["vehicleId" => $this->vehicleId, "type" => "purchase"]));
        $dossier = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request("GET", "/api/dossiers/" . $dossier["id"], [], [], [
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($dossier["id"], $data["id"]);
    }

    public function testShowDossierNotFound(): void
    {
        $this->client->request("GET", "/api/dossiers/99999", [], [], [
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testValidateDossierRejected(): void
    {
        $this->client->request("POST", "/api/dossiers", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->clientToken,
        ], json_encode(["vehicleId" => $this->vehicleId, "type" => "purchase"]));
        $dossier = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request("PATCH", "/api/dossiers/" . $dossier["id"] . "/validate", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode(["status" => "rejected", "comment" => "Documents insuffisants."]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("rejected", $data["status"]);
        $this->assertEquals("Documents insuffisants.", $data["comment"]);
    }

    public function testValidateDossierInvalidStatus(): void
    {
        $this->client->request("POST", "/api/dossiers", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->clientToken,
        ], json_encode(["vehicleId" => $this->vehicleId, "type" => "purchase"]));
        $dossier = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request("PATCH", "/api/dossiers/" . $dossier["id"] . "/validate", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode(["status" => "invalid_status"]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateDossierVehicleNotFound(): void
    {
        $this->client->request("POST", "/api/dossiers", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->clientToken,
        ], json_encode(["vehicleId" => 99999, "type" => "purchase"]));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateDossierInvalidData(): void
    {
        $this->client->request("POST", "/api/dossiers", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->clientToken,
        ], "not valid json");

        $this->assertResponseStatusCodeSame(400);
    }

    public function testValidateDossierNotFound(): void
    {
        $this->client->request("PATCH", "/api/dossiers/99999/validate", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode(["status" => "approved"]));

        $this->assertResponseStatusCodeSame(404);
    }
}