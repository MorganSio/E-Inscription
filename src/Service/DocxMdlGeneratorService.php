<?php

namespace App\Service;

use App\Entity\InfoEleve;
use App\Entity\Humain;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\InfoEleveRepository;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DocxMdlGeneratorService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function generateDocx(int $etudiantId, bool $returnPath = false): string|BinaryFileResponse
    {
        $etudiant = $this->entityManager->getRepository(InfoEleve::class)->find($etudiantId);

        if (!$etudiant) {
            throw new NotFoundHttpException("Étudiant non trouvé.");
        }

        $nom = $etudiant->getUser()?->getNom() ?? 'etudiant';
        $templatePath = __DIR__ . '/../../public/templates/formulaire Adhésion MDL.docx';
        $outputDocxPath = __DIR__ . '/../../public/generated/formulaire_Adhésion_MDL_' . $nom . '.docx';

        $templateProcessor = new TemplateProcessor($templatePath);
        $this->fillTemplate($templateProcessor, $etudiant, $this->entityManager->getRepository(InfoEleve::class));
        $templateProcessor->saveAs($outputDocxPath);

        if ($returnPath) {
            return $outputDocxPath;
        }

        return $this->createDocxDownloadResponse($outputDocxPath);
    }

    private function fillTemplate(TemplateProcessor $templateProcessor, InfoEleve $etudiant, InfoEleveRepository $infoEleveRepository): void
    {
        $user = $etudiant->getUser();
        $infoEleve = $user ? $infoEleveRepository->findOneBy(['user' => $user]) : null;

        $adhesion = $infoEleve?->getAdhesion();

        // Étudiant
        $templateProcessor->setValue('etudiant.nom', $user?->getNom() ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.prenom', $user?->getPrenom() ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.date_naissance', $etudiant->getDateDeNaissance()?->format('d/m/Y') ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.classe', $etudiant->getClasse()?->getLabel() ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.mail', $user?->getEmail() ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.tel', $etudiant?->getNumeroMobile() ?? 'Non renseigné');
        $imageRights = $adhesion?->getImageRights();
        if ($imageRights === true) {
            $autorisationTexte = '☑ Autorise   ☐ N’autorise pas';
        } else {
            // false ou null = décoché
            $autorisationTexte = '☐ Autorise   ☑ N’autorise pas';
        }
        $templateProcessor->setValue('etudiant.autorisation', $autorisationTexte);
        $templateProcessor->setValue('etudiant.type_paiement', $adhesion?->getPaymentMethod() ?? 'Non renseigné');

        // Récupérer le représentant légal 1 (humain)
        $representant = $infoEleve->getResponsableUn();

        if ($representant) {
            $this->setRepresentantValues($templateProcessor, 'represantant', $representant);
        } else {
            // Remplir avec 'Non renseigné' si pas de représentant
            $this->setRepresentantValues($templateProcessor, 'represantant', null);
        }
    }

    private function setRepresentantValues(TemplateProcessor $templateProcessor, string $prefix, ?\App\Entity\Humain $representant): void
    {
        if ($representant === null) {
            $fields = ['nom', 'prenom', 'telephone', 'telephoneFixe', 'telephonePro', 'sms', 'courriel', 'adresse', 'codePostal', 'commune', 'lienEleve', 'poste', 'nomEmployeur', 'adresseEmployeur'];
            foreach ($fields as $field) {
                $templateProcessor->setValue("{$prefix}.{$field}", 'Non renseigné');
            }
            return;
        }

        $templateProcessor->setValue("{$prefix}.nom", $representant->getNom() ?? 'Non renseigné');
        $templateProcessor->setValue("{$prefix}.adresse", $representant->getAdresse() ?? 'Non renseigné');
    }

    private function createDocxDownloadResponse(string $filePath): BinaryFileResponse
    {
        return new BinaryFileResponse($filePath, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"',
        ]);
    }
}