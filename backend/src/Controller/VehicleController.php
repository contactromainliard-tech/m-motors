<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/api/vehicles", name: "api_vehicles_")]
class VehicleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private VehicleRepository $vehicleRepository
    ) {}

    #[Route("", name: "list", methods: ["GET"])]
    public function list(Request $request): JsonResponse
    {
    $type = $request->query->get("type");
    $brand = $request->query->get("brand");
    $maxPrice = $request->query->get("maxPrice");
    $maxKilometrage = $request->query->get("maxKilometrage");

    $criteria = ["isAvailable" => true];
    if ($type) $criteria["type"] = $type;
    if ($brand) $criteria["brand"] = $brand;

    $vehicles = $this->vehicleRepository->findBy($criteria);

    if ($maxPrice) {
        $vehicles = array_filter($vehicles, fn($v) => $v->getPrice() <= (float) $maxPrice);
    }

    if ($maxKilometrage) {
        $vehicles = array_filter($vehicles, fn($v) => $v->getKilometrage() <= (int) $maxKilometrage);
    }

    $data = array_map(fn($vehicle) => $this->formatVehicle($vehicle), array_values($vehicles));
    return $this->json($data);
    }

    #[Route("/{id}", name: "show", methods: ["GET"])]
    public function show(int $id): JsonResponse
    {
        $vehicle = $this->vehicleRepository->find($id);
        if (!$vehicle) {
            return $this->json(["message" => "Vehicule non trouve"], 404);
        }
        return $this->json($this->formatVehicle($vehicle));
    }

    #[Route("", name: "create", methods: ["POST"])]
    #[IsGranted("ROLE_ADMIN")]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(["message" => "Donnees invalides"], 400);
        }

        $vehicle = new Vehicle();
        $vehicle->setBrand($data["brand"] ?? "");
        $vehicle->setModel($data["model"] ?? "");
        $vehicle->setYear($data["year"] ?? 0);
        $vehicle->setKilometrage($data["kilometrage"] ?? 0);
        $vehicle->setPrice($data["price"] ?? 0);
        $vehicle->setType($data["type"] ?? "sale");
        $vehicle->setDescription($data["description"] ?? null);
        $vehicle->setPhotoUrl($data["photoUrl"] ?? null);
        $vehicle->setIsAvailable(true);

        $this->entityManager->persist($vehicle);
        $this->entityManager->flush();

        return $this->json($this->formatVehicle($vehicle), 201);
    }

    #[Route("/{id}", name: "update", methods: ["PUT"])]
    #[IsGranted("ROLE_ADMIN")]
    public function update(int $id, Request $request): JsonResponse
    {
        $vehicle = $this->vehicleRepository->find($id);
        if (!$vehicle) {
            return $this->json(["message" => "Vehicule non trouve"], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data["brand"])) $vehicle->setBrand($data["brand"]);
        if (isset($data["model"])) $vehicle->setModel($data["model"]);
        if (isset($data["year"])) $vehicle->setYear($data["year"]);
        if (isset($data["kilometrage"])) $vehicle->setKilometrage($data["kilometrage"]);
        if (isset($data["price"])) $vehicle->setPrice($data["price"]);
        if (isset($data["type"])) $vehicle->setType($data["type"]);
        if (isset($data["description"])) $vehicle->setDescription($data["description"]);
        if (isset($data["photoUrl"])) $vehicle->setPhotoUrl($data["photoUrl"]);
        if (isset($data["isAvailable"])) $vehicle->setIsAvailable($data["isAvailable"]);

        $this->entityManager->flush();
        return $this->json($this->formatVehicle($vehicle));
    }

    #[Route("/{id}/toggle-type", name: "toggle_type", methods: ["PATCH"])]
    #[IsGranted("ROLE_ADMIN")]
    public function toggleType(int $id): JsonResponse
    {
        $vehicle = $this->vehicleRepository->find($id);
        if (!$vehicle) {
            return $this->json(["message" => "Vehicule non trouve"], 404);
        }

        $newType = $vehicle->getType() === "sale" ? "rental" : "sale";
        $vehicle->setType($newType);
        $this->entityManager->flush();

        return $this->json([
            "message" => "Type de vehicule mis a jour",
            "vehicle" => $this->formatVehicle($vehicle)
        ]);
    }

    private function formatVehicle(Vehicle $vehicle): array
    {
        return [
            "id" => $vehicle->getId(),
            "brand" => $vehicle->getBrand(),
            "model" => $vehicle->getModel(),
            "year" => $vehicle->getYear(),
            "kilometrage" => $vehicle->getKilometrage(),
            "price" => $vehicle->getPrice(),
            "type" => $vehicle->getType(),
            "description" => $vehicle->getDescription(),
            "photoUrl" => $vehicle->getPhotoUrl(),
            "isAvailable" => $vehicle->isAvailable(),
        ];
    }
    /**
     * Upload d une photo pour un véhicule (admin uniquement).
     */
    #[Route("/{id}/upload-photo", name: "upload_photo", methods: ["POST"])]
#[IsGranted("ROLE_ADMIN")]
public function uploadPhoto(int $id, Request $request): JsonResponse
{
    try {
        $vehicle = $this->vehicleRepository->find($id);
        if (!$vehicle) {
            return $this->json(["message" => "Vehicule non trouve"], 404);
        }

        $file = $request->files->get("photo");
        if (!$file) {
            return $this->json(["message" => "Aucun fichier fourni"], 400);
        }

        $allowedMimes = ["image/jpeg", "image/png", "image/jpg"];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return $this->json(["message" => "Format non accepte. JPG et PNG uniquement."], 400);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json(["message" => "Fichier trop volumineux. 5 Mo maximum."], 400);
        }

        $filename = uniqid("vehicle_") . "." . $file->getClientOriginalExtension();
        $uploadDir = $this->getParameter("kernel.project_dir") . "/public/uploads/vehicles";
        $file->move($uploadDir, $filename);

        $photoUrl = "/uploads/vehicles/" . $filename;
        $vehicle->setPhotoUrl($photoUrl);
        $this->entityManager->flush();

        return $this->json([
            "message" => "Photo uploadee avec succes",
            "photoUrl" => $photoUrl,
            "vehicle" => $this->formatVehicle($vehicle)
        ]);
        } catch (\Exception $e) {
        return $this->json(["message" => $e->getMessage()], 500);
        }
    }
}
