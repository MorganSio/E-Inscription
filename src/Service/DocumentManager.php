<?php

namespace App\Service;

use App\Entity\InfoEleve;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class DocumentManager
{
    private string $documentsDirectory;
    private string $documentsWebDirectory;
    private EntityManagerInterface $entityManager;
    private SluggerInterface $slugger;
    
    private const DOCUMENT_TYPES = [
        'carte_vitale' => [
            'label' => 'Carte Vitale',
            'required' => true,
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png']
        ],
        'photo_identite' => [
            'label' => 'Photo d\'identité',
            'required' => true,
            'extensions' => ['pdf','jpg', 'jpeg', 'png']
        ],
        'attestation_identite' => [
            'label' => 'Attestation d\'identité',
            'required' => true,
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png']
        ],
        'bourse' => [
            'label' => 'Justificatif de bourse',
            'required' => false,
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png']
        ],
        'attestation_jdc' => [
            'label' => 'Attestation JDC',
            'required' => false,
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png']
        ],
        'attestation_reusite' => [
            'label' => 'Attestation de réussite',
            'required' => false,
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png']
        ]
    ];
    
    public function __construct(
        string $documentsDirectory,
        string $documentsWebDirectory,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ) {
        $this->documentsDirectory = $documentsDirectory;
        $this->documentsWebDirectory = $documentsWebDirectory;
        $this->entityManager = $entityManager;
        $this->slugger = $slugger;
    }
    
    /**
     * Upload un document pour un élève
     */
    public function uploadDocument(InfoEleve $infoEleve, UploadedFile $file, string $documentType): array
    {
        if (!array_key_exists($documentType, self::DOCUMENT_TYPES)) {
            throw new \InvalidArgumentException('Type de document non valide');
        }
        
        $config = self::DOCUMENT_TYPES[$documentType];
        
        // Validation de l'extension
        $extension = $file->guessExtension();
        if (!in_array($extension, $config['extensions'])) {
            throw new \InvalidArgumentException(
                sprintf('Extension non autorisée. Extensions autorisées: %s', 
                    implode(', ', $config['extensions']))
            );
        }
        
        // Validation de la taille (5MB max)
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('Le fichier est trop volumineux (max 5MB)');
        }
        
        // Supprimer l'ancien fichier s'il existe
        $this->deleteDocument($infoEleve, $documentType);
        
        // Créer le répertoire de l'élève
        $eleveDirectory = $this->getEleveDirectory($infoEleve);
        if (!is_dir($eleveDirectory)) {
            mkdir($eleveDirectory, 0755, true);
        }
        
        // Générer un nom de fichier unique
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '_' . uniqid() . '.' . $extension;
        
        try {
            // Déplacer le fichier
            $file->move($eleveDirectory, $fileName);
            
            // Enregistrer le chemin en base
            $this->saveDocumentPath($infoEleve, $documentType, $fileName);
            
            return [
                'success' => true,
                'filename' => $fileName,
                'path' => $this->getDocumentPath($infoEleve, $documentType)
            ];
            
        } catch (FileException $e) {
            throw new \RuntimeException('Erreur lors du téléchargement: ' . $e->getMessage());
        }
    }
    
    /**
     * Supprimer un document
     */
    public function deleteDocument(InfoEleve $infoEleve, string $documentType): bool
    {
        $currentPath = $this->getDocumentPath($infoEleve, $documentType);
        
        if ($currentPath && file_exists($currentPath)) {
            unlink($currentPath);
        }
        
        // Supprimer de la base de données
        $this->saveDocumentPath($infoEleve, $documentType, null);
        
        return true;
    }
    
    /**
     * Obtenir le chemin complet d'un document
     */
    public function getDocumentPath(InfoEleve $infoEleve, string $documentType): ?string
    {
        $filename = $this->getDocumentFilename($infoEleve, $documentType);
        
        if (!$filename) {
            return null;
        }
        
        return $this->getEleveDirectory($infoEleve) . '/' . $filename;
    }
    
    /**
     * Obtenir l'URL web d'un document
     */
    public function getDocumentWebPath(InfoEleve $infoEleve, string $documentType): ?string
    {
        $filename = $this->getDocumentFilename($infoEleve, $documentType);
        
        if (!$filename) {
            return null;
        }
        
        return '/uploads/documents/' . $infoEleve->getId() . '/' . $filename;
    }
    
    /**
     * Vérifier si un document existe
     */
    public function hasDocument(InfoEleve $infoEleve, string $documentType): bool
    {
        $path = $this->getDocumentPath($infoEleve, $documentType);
        return $path && file_exists($path);
    }
    
    /**
     * Obtenir tous les types de documents
     */
    public function getDocumentTypes(): array
    {
        return self::DOCUMENT_TYPES;
    }
    
    /**
     * Obtenir les documents manquants obligatoires
     */
    public function getMissingRequiredDocuments(InfoEleve $infoEleve): array
    {
        $missing = [];
        
        foreach (self::DOCUMENT_TYPES as $type => $config) {
            if ($config['required'] && !$this->hasDocument($infoEleve, $type)) {
                $missing[] = $type;
            }
        }
        
        return $missing;
    }
    
    /**
     * Vérifier si tous les documents obligatoires sont présents
     */
    public function hasAllRequiredDocuments(InfoEleve $infoEleve): bool
    {
        return empty($this->getMissingRequiredDocuments($infoEleve));
    }
    
    /**
     * Obtenir le statut de tous les documents
     */
    public function getDocumentsStatus(InfoEleve $infoEleve): array
    {
        $status = [];
        
        foreach (self::DOCUMENT_TYPES as $type => $config) {
            $status[$type] = [
                'label' => $config['label'],
                'required' => $config['required'],
                'uploaded' => $this->hasDocument($infoEleve, $type),
                'web_path' => $this->getDocumentWebPath($infoEleve, $type)
            ];
        }
        
        return $status;
    }
    
    // Méthodes privées
    
    private function getEleveDirectory(InfoEleve $infoEleve): string
    {
        return $this->documentsDirectory . '/' . $infoEleve->getId();
    }
    
    private function getDocumentFilename(InfoEleve $infoEleve, string $documentType): ?string
    {
        $methodName = 'get' . $this->toCamelCase($documentType) . 'Filename';
        
        if (method_exists($infoEleve, $methodName)) {
            return $infoEleve->$methodName();
        }
        
        return null;
    }
    
    private function saveDocumentPath(InfoEleve $infoEleve, string $documentType, ?string $filename): void
    {
        $methodName = 'set' . $this->toCamelCase($documentType) . 'Filename';
        
        if (method_exists($infoEleve, $methodName)) {
            $infoEleve->$methodName($filename);
            $this->entityManager->flush();
        }
    }
    
    private function toCamelCase(string $string): string
    {
        return str_replace('_', '', ucwords($string, '_'));
    }
}