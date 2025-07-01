<?php

namespace App\Controller;

use App\Entity\InfoEleve;
use App\Service\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DocumentController extends AbstractController
{
    /**
     * Vérifie si l'utilisateur connecté peut accéder aux données de l'élève
     */
    private function canAccessEleve(InfoEleve $infoEleve): bool
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Vérifier si c'est un admin
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }
        
        // Vérifier si l'utilisateur connecté correspond à cet élève
        return $user->getInfoEleve() && $user->getInfoEleve()->getId() === $infoEleve->getId();
    }

    #[Route('/upload-document', name: 'upload_document', methods: ['POST'])]
    public function uploadDocument(
        Request $request,
        DocumentManager $documentManager,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $eleveId = $request->request->get('eleve_id');
        $documentType = $request->request->get('document_type');
        $file = $request->files->get('file');
        
        if (!$eleveId || !$documentType || !$file) {
            return $this->json(['error' => 'Paramètres manquants'], 400);
        }
        
        // Récupérer l'élève
        $infoEleve = $entityManager->getRepository(InfoEleve::class)->find($eleveId);
        
        if (!$infoEleve) {
            return $this->json(['error' => 'Élève non trouvé'], 404);
        }
        
        // Vérification d'accès corrigée
        if (!$this->canAccessEleve($infoEleve)) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }
        
        try {
            $result = $documentManager->uploadDocument($infoEleve, $file, $documentType);
            
            return $this->json([
                'success' => true,
                'message' => 'Document téléchargé avec succès',
                'filename' => $result['filename'],
                'web_path' => $documentManager->getDocumentWebPath($infoEleve, $documentType)
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
    
    #[Route('/delete-document', name: 'delete_document', methods: ['POST'])]
    public function deleteDocument(
        Request $request,
        DocumentManager $documentManager,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $eleveId = $request->request->get('eleve_id');
        $documentType = $request->request->get('document_type');
        
        if (!$eleveId || !$documentType) {
            return $this->json(['error' => 'Paramètres manquants'], 400);
        }
        
        $infoEleve = $entityManager->getRepository(InfoEleve::class)->find($eleveId);
        
        if (!$infoEleve) {
            return $this->json(['error' => 'Élève non trouvé'], 404);
        }
        
        // Vérification d'accès corrigée
        if (!$this->canAccessEleve($infoEleve)) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }
        
        try {
            $documentManager->deleteDocument($infoEleve, $documentType);
            
            return $this->json([
                'success' => true,
                'message' => 'Document supprimé avec succès'
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
    
    #[Route('/download-document/{eleveId}/{documentType}', name: 'download_document', methods: ['GET'])]
    public function downloadDocument(
        int $eleveId,
        string $documentType,
        DocumentManager $documentManager,
        EntityManagerInterface $entityManager
    ): Response {
        $infoEleve = $entityManager->getRepository(InfoEleve::class)->find($eleveId);
        
        if (!$infoEleve) {
            throw $this->createNotFoundException('Élève non trouvé');
        }
        
        // Vérification d'accès corrigée
        if (!$this->canAccessEleve($infoEleve)) {
            throw $this->createAccessDeniedException('Accès non autorisé');
        }
        
        $filePath = $documentManager->getDocumentPath($infoEleve, $documentType);
        
        if (!$filePath || !file_exists($filePath)) {
            throw $this->createNotFoundException('Document non trouvé');
        }
        
        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        
        return $response;
    }
    
    #[Route('/documents-status/{eleveId}', name: 'documents_status', methods: ['GET'])]
    public function getDocumentsStatus(
        int $eleveId,
        DocumentManager $documentManager,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $infoEleve = $entityManager->getRepository(InfoEleve::class)->find($eleveId);
        
        if (!$infoEleve) {
            return $this->json(['error' => 'Élève non trouvé'], 404);
        }
        
        // Vérification d'accès corrigée
        if (!$this->canAccessEleve($infoEleve)) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }
        
        $status = $documentManager->getDocumentsStatus($infoEleve);
        $missingRequired = $documentManager->getMissingRequiredDocuments($infoEleve);
        
        return $this->json([
            'documents' => $status,
            'missing_required' => $missingRequired,
            'all_required_uploaded' => $documentManager->hasAllRequiredDocuments($infoEleve)
        ]);
    }
}