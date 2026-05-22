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
}