<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Repository\DossierRepository;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/api/dossiers", name: "api_dossiers_")]
class DossierController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DossierRepository $dossierRepository,
        private VehicleRepository $vehicleRepository
    ) {}

    #[Route("", name: "create", methods: ["POST"])]
    #[IsGranted("ROLE_USER")]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(["message" => "Donnees invalides"], 400);
        }

        $vehicle = $this->vehicleRepository->find($data["vehicleId"] ?? 0);
        if (!$vehicle) {
            return $this->json(["message" => "Vehicule non trouve"], 404);
        }

        $dossier = new Dossier();
        $dossier->setType($data["type"] ?? "purchase");
        $dossier->setStatus("pending");
        $dossier->setCreatedAt(new \DateTimeImmutable());
        $dossier->setClient($this->getUser());
        $dossier->setVehicle($vehicle);

        $this->entityManager->persist($dossier);
        $this->entityManager->flush();

        return $this->json($this->formatDossier($dossier), 201);
    }

    #[Route("/my-dossiers", name: "my_dossiers", methods: ["GET"])]
    #[IsGranted("ROLE_USER")]
    public function myDossiers(): JsonResponse
    {
        $dossiers = $this->dossierRepository->findBy(["client" => $this->getUser()]);
        $data = array_map(fn($dossier) => $this->formatDossier($dossier), $dossiers);
        return $this->json($data);
    }

    #[Route("", name: "list", methods: ["GET"])]
    #[IsGranted("ROLE_ADMIN")]
    public function list(): JsonResponse
    {
        $dossiers = $this->dossierRepository->findAll();
        $data = array_map(fn($dossier) => $this->formatDossier($dossier), $dossiers);
        return $this->json($data);
    }

    #[Route("/{id}", name: "show", methods: ["GET"])]
    #[IsGranted("ROLE_ADMIN")]
    public function show(int $id): JsonResponse
    {
        $dossier = $this->dossierRepository->find($id);
        if (!$dossier) {
            return $this->json(["message" => "Dossier non trouve"], 404);
        }
        return $this->json($this->formatDossier($dossier));
    }

    #[Route("/{id}/validate", name: "validate", methods: ["PATCH"])]
    #[IsGranted("ROLE_ADMIN")]
    public function validate(int $id, Request $request): JsonResponse
    {
        $dossier = $this->dossierRepository->find($id);
        if (!$dossier) {
            return $this->json(["message" => "Dossier non trouve"], 404);
        }

        $data = json_decode($request->getContent(), true);
        $status = $data["status"] ?? null;

        if (!in_array($status, ["approved", "rejected"])) {
            return $this->json(["message" => "Statut invalide"], 400);
        }

        $dossier->setStatus($status);
        $dossier->setUpdatedAt(new \DateTimeImmutable());
        $dossier->setComment($data["comment"] ?? null);

        $this->entityManager->flush();
        return $this->json($this->formatDossier($dossier));
    }

    private function formatDossier(Dossier $dossier): array
    {
        return [
            "id" => $dossier->getId(),
            "type" => $dossier->getType(),
            "status" => $dossier->getStatus(),
            "createdAt" => $dossier->getCreatedAt()->format("Y-m-d H:i:s"),
            "updatedAt" => $dossier->getUpdatedAt()?->format("Y-m-d H:i:s"),
            "comment" => $dossier->getComment(),
            "client" => [
                "id" => $dossier->getClient()->getId(),
                "email" => $dossier->getClient()->getEmail(),
                "firstName" => $dossier->getClient()->getFirstName(),
                "lastName" => $dossier->getClient()->getLastName(),
            ],
            "vehicle" => [
                "id" => $dossier->getVehicle()->getId(),
                "brand" => $dossier->getVehicle()->getBrand(),
                "model" => $dossier->getVehicle()->getModel(),
                "type" => $dossier->getVehicle()->getType(),
                "price" => $dossier->getVehicle()->getPrice(),
            ],
        ];
    }
}
