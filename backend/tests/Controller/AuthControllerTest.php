<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests unitaires du controller d authentification.
 * Verifie l inscription, la connexion et la gestion du profil.
 */
class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $em = $container->get("doctrine")->getManager();
        $em->getConnection()->executeStatement("DELETE FROM dossier");
        $em->getConnection()->executeStatement("DELETE FROM vehicle");
        $em->getConnection()->executeStatement("DELETE FROM \"user\"");
    }

    public function testRegisterSuccess(): void
    {
        $this->client->request("POST", "/api/auth/register", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "newuser@mmotors.fr",
            "password" => "Test1234!",
            "firstName" => "Marie",
            "lastName" => "Martin",
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey("user", $data);
        $this->assertEquals("newuser@mmotors.fr", $data["user"]["email"]);
    }

    public function testRegisterDuplicateEmail(): void
    {
        $payload = json_encode([
            "email" => "duplicate@mmotors.fr",
            "password" => "Test1234!",
            "firstName" => "Jean",
            "lastName" => "Dupont",
        ]);

        $this->client->request("POST", "/api/auth/register", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], $payload);

        $this->client->request("POST", "/api/auth/register", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], $payload);

        $this->assertResponseStatusCodeSame(409);
    }

    public function testRegisterInvalidData(): void
    {
        $this->client->request("POST", "/api/auth/register", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], "invalid json");

        $this->assertResponseStatusCodeSame(400);
    }

    public function testGetProfile(): void
    {
        $this->client->request("POST", "/api/auth/register", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "profile@mmotors.fr",
            "password" => "Test1234!",
            "firstName" => "Jean",
            "lastName" => "Dupont",
        ]));

        $this->client->request("POST", "/api/auth/login", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "profile@mmotors.fr",
            "password" => "Test1234!",
        ]));

        $loginData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $loginData["token"];

        $this->client->request("GET", "/api/auth/profile", [], [], [
            "HTTP_AUTHORIZATION" => "Bearer " . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("profile@mmotors.fr", $data["email"]);
        $this->assertEquals("Jean", $data["firstName"]);
    }

    public function testUpdateProfile(): void
    {
        $this->client->request("POST", "/api/auth/register", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "update@mmotors.fr",
            "password" => "Test1234!",
            "firstName" => "Jean",
            "lastName" => "Dupont",
        ]));

        $this->client->request("POST", "/api/auth/login", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "update@mmotors.fr",
            "password" => "Test1234!",
        ]));

        $loginData = json_decode($this->client->getResponse()->getContent(), true);
        $token = $loginData["token"];

        $this->client->request("PUT", "/api/auth/profile", [], [], [
            "CONTENT_TYPE" => "application/json",
            "HTTP_AUTHORIZATION" => "Bearer " . $token,
        ], json_encode([
            "address" => "15 rue de la Paix",
            "city" => "Paris",
            "zipCode" => "75001",
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("Paris", $data["user"]["city"]);
        $this->assertEquals("75001", $data["user"]["zipCode"]);
    }

    public function testRegisterMissingFields(): void
    {
        $this->client->request("POST", "/api/auth/register", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode(["email" => "test@test.fr"]));

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterInvalidEmail(): void
    {
        $this->client->request("POST", "/api/auth/register", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "not-an-email",
            "password" => "Test1234!",
            "firstName" => "Jean",
            "lastName" => "Dupont",
        ]));

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterShortPassword(): void
    {
        $this->client->request("POST", "/api/auth/register", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "valid@test.fr",
            "password" => "abc",
            "firstName" => "Jean",
            "lastName" => "Dupont",
        ]));

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterShortName(): void
    {
        $this->client->request("POST", "/api/auth/register", [], [], [
            "CONTENT_TYPE" => "application/json",
        ], json_encode([
            "email" => "valid@test.fr",
            "password" => "Test1234!",
            "firstName" => "J",
            "lastName" => "Dupont",
        ]));

        $this->assertResponseStatusCodeSame(422);
    }
}
