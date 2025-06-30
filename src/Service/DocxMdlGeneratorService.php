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
        // Étudiant
        $templateProcessor->setValue('etudiant.nom', $user?->getNom() ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.prenom', $user?->getPrenom() ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.date_naissance', $etudiant->getDateDeNaissance()?->format('d/m/Y') ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.classe', $etudiant->getClasse()?->getLabel() ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.mail', $user?->getEmail() ?? 'Non renseigné');
        $adhesion = $infoEleve->getAdhesion();
        $templateProcessor->setValue('etudiant.tel', $etudiant?->getNumeroMobile ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.autorisation', $adhesion?->getImageRights() ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.type_paiement', $adhesion?->getPaymentMethod() ?? 'Non renseigné');

        // === Choix du représentant en fonction de l'âge ===
        $aujourdHui = new \DateTimeImmutable();
        $dateNaissance = $etudiant->getDateDeNaissance();
        $age = $dateNaissance ? $dateNaissance->diff($aujourdHui)->y : null;

        if ($age !== null && $age >= 18) {
            // majeur = lui-même
            $representant = $etudiant;
        } else {
            // mineur = responsable un si défini sinon lui-même
            $representant = $infoEleve?->getResponsableUn() ?? $etudiant;
        }

        $this->setRepresentantValues($templateProcessor, 'representant', $representant);
    }

    private function setRepresentantValues(TemplateProcessor $templateProcessor, string $prefix, $source): void
    {
        if ($source instanceof InfoEleve) {
            $user = $source->getUser();

            if ($user instanceof Humain) {
                $templateProcessor->setValue("{$prefix}.nom", $user->getNom() ?? 'Non renseigné');
                $templateProcessor->setValue("{$prefix}.adresse", $user->getAdresse() ?? 'Non renseigné');
            } else {
                // Valeurs par défaut si user non Humain
                $templateProcessor->setValue("{$prefix}.nom", 'Non renseigné');
                $templateProcessor->setValue("{$prefix}.adresse", 'Non renseigné');
            }
        } else {
            // Ici $source est un objet représentant légal classique (ex : ResponsableUn)
            $templateProcessor->setValue("{$prefix}.nom", $source->getNom() ?? 'Non renseigné');
            $templateProcessor->setValue("{$prefix}.adresse", $source->getAdresse() ?? 'Non renseigné');
        }
    }

    private function createDocxDownloadResponse(string $filePath): BinaryFileResponse
    {
        return new BinaryFileResponse($filePath, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"',
        ]);
    }
}