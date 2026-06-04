<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests unitaires du controller des vehicules.
 * Verifie la liste, la creation, la modification et la suppression.
 */
class VehicleControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private string $adminToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $em = $container->get("doctrine")->getManager();
        $em->getConnection()->executeStatement("DELETE FROM dossier");
        $em->getConnection()->executeStatement("DELETE FROM vehicle");
        $em->getConnection()->executeStatement("DELETE FROM \"user\"");

        // Creation du compte admin
        $this->client->request("POST", "/api/auth/register", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "admin@test.fr",
            "password" => "Admin1234!",
            "firstName" => "Admin",
            "lastName" => "Test",
        ]));

        // Passage en admin via Doctrine
        $userRepo = $em->getRepository(\App\Entity\User::class);
        $admin = $userRepo->findOneBy(["email" => "admin@test.fr"]);
        $admin->setIsAdmin(true);
        $admin->setRoles(["ROLE_ADMIN"]);
        $em->flush();

        // Connexion admin
        $this->client->request("POST", "/api/auth/login", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "admin@test.fr",
            "password" => "Admin1234!",
        ]));

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->adminToken = $data["token"];
    }

    public function testListVehiclesPublic(): void
    {
        $this->client->request("GET", "/api/vehicles");
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testCreateVehicleAsAdmin(): void
    {
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

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("Renault", $data["brand"]);
        $this->assertEquals("Clio", $data["model"]);
    }

    public function testCreateVehicleUnauthorized(): void
    {
        $this->client->request("POST", "/api/vehicles", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "brand" => "Renault",
            "model" => "Clio",
            "year" => 2021,
            "kilometrage" => 35000,
            "price" => 12500,
            "type" => "sale",
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateVehicle(): void
    {
        // Creation du vehicule
        $this->client->request("POST", "/api/vehicles", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode([
            "brand" => "Peugeot",
            "model" => "308",
            "year" => 2020,
            "kilometrage" => 42000,
            "price" => 16800,
            "type" => "sale",
        ]));

        $vehicle = json_decode($this->client->getResponse()->getContent(), true);

        // Modification du vehicule
        $this->client->request("PUT", "/api/vehicles/" . $vehicle["id"], [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode([
            "price" => 15000,
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(15000, $data["price"]);
    }

    public function testDeleteVehicle(): void
    {
        // Creation du vehicule
        $this->client->request("POST", "/api/vehicles", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode([
            "brand" => "Toyota",
            "model" => "Yaris",
            "year" => 2021,
            "kilometrage" => 28000,
            "price" => 14500,
            "type" => "sale",
        ]));

        $vehicle = json_decode($this->client->getResponse()->getContent(), true);

        // Suppression du vehicule
        $this->client->request("DELETE", "/api/vehicles/" . $vehicle["id"], [], [], [
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("Vehicule supprime avec succes", $data["message"]);
    }

    public function testToggleVehicleType(): void
    {
        // Creation du vehicule
        $this->client->request("POST", "/api/vehicles", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode([
            "brand" => "Ford",
            "model" => "Focus",
            "year" => 2019,
            "kilometrage" => 55000,
            "price" => 13200,
            "type" => "sale",
        ]));

        $vehicle = json_decode($this->client->getResponse()->getContent(), true);

        // Bascule du type
        $this->client->request("PATCH", "/api/vehicles/" . $vehicle["id"] . "/toggle-type", [], [], [
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("rental", $data["vehicle"]["type"]);
    }

    public function testShowVehicle(): void
    {
        $this->client->request("POST", "/api/vehicles", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode([
            "brand" => "Citroen", "model" => "C3",
            "year" => 2022, "kilometrage" => 18000,
            "price" => 15000, "type" => "sale",
        ]));
        $vehicle = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request("GET", "/api/vehicles/" . $vehicle["id"]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("Citroen", $data["brand"]);
        $this->assertEquals("C3", $data["model"]);
    }

    public function testShowVehicleNotFound(): void
    {
        $this->client->request("GET", "/api/vehicles/99999");
        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("Vehicule non trouve", $data["message"]);
    }

    public function testListVehiclesWithFilters(): void
    {
        $this->client->request("POST", "/api/vehicles", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode([
            "brand" => "BMW", "model" => "Serie 3",
            "year" => 2020, "kilometrage" => 40000,
            "price" => 25000, "type" => "sale",
        ]));

        $this->client->request("GET", "/api/vehicles?type=sale&brand=BMW&maxPrice=30000&maxKilometrage=50000");

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testDeleteVehicleWithDossier(): void
    {
        // Creer client + dossier pour bloquer la suppression
        $this->client->request("POST", "/api/auth/register", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "client2@test.fr", "password" => "Test1234!",
            "firstName" => "Client", "lastName" => "Test",
        ]));
        $this->client->request("POST", "/api/auth/login", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode(["email" => "client2@test.fr", "password" => "Test1234!"]));
        $loginData = json_decode($this->client->getResponse()->getContent(), true);
        $clientToken = $loginData["token"];

        $this->client->request("POST", "/api/vehicles", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode([
            "brand" => "Honda", "model" => "Civic",
            "year" => 2021, "kilometrage" => 22000,
            "price" => 16000, "type" => "sale",
        ]));
        $vehicle = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request("POST", "/api/dossiers", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $clientToken,
        ], json_encode(["vehicleId" => $vehicle["id"], "type" => "purchase"]));

        // Tenter la suppression — doit echouer (409)
        $this->client->request("DELETE", "/api/vehicles/" . $vehicle["id"], [], [], [
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ]);

        $this->assertResponseStatusCodeSame(409);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString("dossiers associes", $data["message"]);
    }

    public function testToggleVehicleTypeNotFound(): void
    {
        $this->client->request("PATCH", "/api/vehicles/99999/toggle-type", [], [], [
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateVehicleInvalidJson(): void
    {
        $this->client->request("POST", "/api/vehicles", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], "not valid json");

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("Donnees invalides", $data["message"]);
    }

    public function testUpdateVehicleNotFound(): void
    {
        $this->client->request("PUT", "/api/vehicles/99999", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode(["price" => 10000]));

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("Vehicule non trouve", $data["message"]);
    }

    public function testUploadPhotoVehicleNotFound(): void
    {
        $this->client->request("POST", "/api/vehicles/99999/upload-photo", [], [], [
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUploadPhotoNoFile(): void
    {
        $this->client->request("POST", "/api/vehicles", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode([
            "brand" => "Audi", "model" => "A3",
            "year" => 2023, "kilometrage" => 5000,
            "price" => 32000, "type" => "sale",
        ]));
        $vehicle = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request("POST", "/api/vehicles/" . $vehicle["id"] . "/upload-photo", [], [], [
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ]);

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("Aucun fichier fourni", $data["message"]);
    }

    public function testUploadPhotoWithRealImage(): void
    {
        $uploadDir = static::getContainer()->getParameter("kernel.project_dir") . "/public/uploads/vehicles";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $this->client->request("POST", "/api/vehicles", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken,
        ], json_encode([
            "brand" => "Audi", "model" => "A4",
            "year" => 2022, "kilometrage" => 15000,
            "price" => 38000, "type" => "sale",
        ]));
        $vehicle = json_decode($this->client->getResponse()->getContent(), true);

        // Creer une mini image JPEG valide (1x1 pixel)
        $tmpFile = tempnam(sys_get_temp_dir(), "test_photo_");
        $img = imagecreatetruecolor(1, 1);
        imagejpeg($img, $tmpFile);
        imagedestroy($img);

        $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
            $tmpFile, "photo.jpg", "image/jpeg", null, true
        );

        $this->client->request(
            "POST",
            "/api/vehicles/" . $vehicle["id"] . "/upload-photo",
            [],
            ["photo" => $uploadedFile],
            ["HTTP_AUTHORIZATION" => "Bearer " . $this->adminToken]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("Photo uploadee avec succes", $data["message"]);
        $this->assertArrayHasKey("photoUrl", $data);
    }
}