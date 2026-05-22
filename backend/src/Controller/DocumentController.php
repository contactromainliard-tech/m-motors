<?php

namespace App\Controller;

use App\Entity\Document;
use App\Repository\DossierRepository;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller gérant les documents joints aux dossiers.
 * Permet l upload et la consultation des pièces justificatives.
 */
#[Route("/api/documents", name: "api_documents_")]
class DocumentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DossierRepository $dossierRepository,
        private DocumentRepository $documentRepository
    ) {}

    /**
     * Upload un document pour un dossier.
     * Accessible au client propriétaire du dossier.
     */
    #[Route("/upload/{dossierId}", name: "upload", methods: ["POST"])]
    #[IsGranted("ROLE_USER")]
    public function upload(int $dossierId, Request $request): JsonResponse
    {
        $dossier = $this->dossierRepository->find($dossierId);

        if (!$dossier) {
            return $this->json(["message" => "Dossier non trouve"], 404);
        }

        // Vérification que le dossier appartient au client connecté
        if ($dossier->getClient()->getId() !== $this->getUser()->getId()) {
            return $this->json(["message" => "Acces refuse"], 403);
        }

        $file = $request->files->get("document");
        if (!$file) {
            return $this->json(["message" => "Aucun fichier fourni"], 400);
        }

        // Validation du type de fichier
        $allowedMimes = ["application/pdf", "image/jpeg", "image/png", "image/jpg"];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return $this->json(["message" => "Format non accepte. PDF, JPG et PNG uniquement."], 400);
        }

        // Validation de la taille (10 Mo max)
        if ($file->getSize() > 10 * 1024 * 1024) {
            return $this->json(["message" => "Fichier trop volumineux. 10 Mo maximum."], 400);
        }

        $documentType = $request->request->get("type", "other");
        $allowedTypes = ["cni", "justificatif_domicile", "fiche_paie", "rib", "other"];
        if (!in_array($documentType, $allowedTypes)) {
            $documentType = "other";
        }

        // Génération d un nom unique
        $filename = uniqid("doc_") . "." . $file->getClientOriginalExtension();
        $uploadDir = $this->getParameter("kernel.project_dir") . "/public/uploads/documents";
        $file->move($uploadDir, $filename);

        $document = new Document();
        $document->setFilename($filename);
        $document->setOriginalName($file->getClientOriginalName());
        $document->setMimeType($file->getMimeType() ?? "application/octet-stream");
        $document->setSize($file->getSize() ?? 0);
        $document->setType($documentType);
        $document->setUploadedAt(new \DateTimeImmutable());
        $document->setDossier($dossier);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $this->json([
            "message" => "Document uploade avec succes",
            "document" => $this->formatDocument($document)
        ], 201);
    }

    /**
     * Retourne la liste des documents d un dossier.
     * Accessible au client propriétaire et aux administrateurs.
     */
    #[Route("/dossier/{dossierId}", name: "list", methods: ["GET"])]
    #[IsGranted("ROLE_USER")]
    public function listByDossier(int $dossierId): JsonResponse
    {
        $dossier = $this->dossierRepository->find($dossierId);

        if (!$dossier) {
            return $this->json(["message" => "Dossier non trouve"], 404);
        }

        // Vérification des droits d accès
        $user = $this->getUser();
        $isAdmin = in_array("ROLE_ADMIN", $user->getRoles());
        if (!$isAdmin && $dossier->getClient()->getId() !== $user->getId()) {
            return $this->json(["message" => "Acces refuse"], 403);
        }

        $documents = $this->documentRepository->findBy(["dossier" => $dossier]);
        $data = array_map(fn($doc) => $this->formatDocument($doc), $documents);

        return $this->json($data);
    }

    /**
     * Formate les données d un document pour la réponse JSON.
     */
    private function formatDocument(Document $document): array
    {
        return [
            "id" => $document->getId(),
            "originalName" => $document->getOriginalName(),
            "type" => $document->getType(),
            "mimeType" => $document->getMimeType(),
            "size" => $document->getSize(),
            "uploadedAt" => $document->getUploadedAt()->format("Y-m-d H:i:s"),
            "url" => "/uploads/documents/" . $document->getFilename(),
        ];
    }
}