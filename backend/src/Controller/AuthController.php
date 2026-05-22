<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/api/auth", name: "api_auth_")]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository
    ) {}

    #[Route("/register", name: "register", methods: ["POST"])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(["message" => "Donnees invalides"], 400);
        }

        if (empty($data["email"]) || empty($data["password"]) || empty($data["firstName"]) || empty($data["lastName"])) {
            return $this->json(["message" => "Champs obligatoires manquants"], 422);
        }

        $existingUser = $this->userRepository->findOneBy(["email" => $data["email"]]);
        if ($existingUser) {
            return $this->json(["message" => "Email deja utilise"], 409);
        }

        $user = new User();
        $user->setEmail($data["email"]);
        $user->setFirstName($data["firstName"]);
        $user->setLastName($data["lastName"]);
        $user->setPhone($data["phone"] ?? null);
        $user->setIsAdmin(false);
        $user->setRoles(["ROLE_USER"]);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data["password"]);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            "message" => "Compte cree avec succes",
            "user" => [
                "id" => $user->getId(),
                "email" => $user->getEmail(),
                "firstName" => $user->getFirstName(),
                "lastName" => $user->getLastName(),
            ]
        ], 201);
    }
        /**
     * Retourne les informations du profil de l'utilisateur connecté.
     */
    #[Route("/profile", name: "profile", methods: ["GET"])]
    public function profile(): JsonResponse
    {
        $user = $this->getUser();
        return $this->json([
            "id" => $user->getId(),
            "email" => $user->getEmail(),
            "firstName" => $user->getFirstName(),
            "lastName" => $user->getLastName(),
            "phone" => $user->getPhone(),
            "address" => $user->getAddress(),
            "city" => $user->getCity(),
            "zipCode" => $user->getZipCode(),
        ]);
    }

    /**
     * Met à jour les informations personnelles de l'utilisateur connecté.
     */
    #[Route("/profile", name: "profile_update", methods: ["PUT"])]
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (isset($data["firstName"])) $user->setFirstName($data["firstName"]);
        if (isset($data["lastName"])) $user->setLastName($data["lastName"]);
        if (isset($data["phone"])) $user->setPhone($data["phone"]);
        if (isset($data["address"])) $user->setAddress($data["address"]);
        if (isset($data["city"])) $user->setCity($data["city"]);
        if (isset($data["zipCode"])) $user->setZipCode($data["zipCode"]);

        $this->entityManager->flush();

        return $this->json([
            "message" => "Profil mis a jour",
            "user" => [
                "id" => $user->getId(),
                "email" => $user->getEmail(),
                "firstName" => $user->getFirstName(),
                "lastName" => $user->getLastName(),
                "phone" => $user->getPhone(),
                "address" => $user->getAddress(),
                "city" => $user->getCity(),
                "zipCode" => $user->getZipCode(),
            ]
        ]);
    }
}
