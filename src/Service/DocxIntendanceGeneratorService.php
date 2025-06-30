<?php

namespace App\Service;

use App\Entity\InfoEleve;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocxIntendanceGeneratorService
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
        $templatePath = __DIR__ . '/../../public/templates/Fiche intendance BTS.docx';
        $outputDocxPath = __DIR__ . '/../../public/generated/Fiche_Intendance_' . $nom . '.docx';

        $templateProcessor = new TemplateProcessor($templatePath);
        $this->fillTemplate($templateProcessor, $etudiant);
        $templateProcessor->saveAs($outputDocxPath);

        if ($returnPath) {
            return $outputDocxPath;
        }

        return $this->createDocxDownloadResponse($outputDocxPath);
    }

    private function fillTemplate(TemplateProcessor $templateProcessor, InfoEleve $etudiant): void
    {
        $user = $etudiant->getUser();

        // Étudiant
        $templateProcessor->setValue('etudiant.nom', $user?->getNom() ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.prenom', $user?->getPrenom() ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.date_naissance', $etudiant->getDateDeNaissance()?->format('d/m/Y') ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.classe', $etudiant->getClasse()?->getLabel() ?? 'Non renseigné');

        $regime = strtolower($etudiant->getRegime() ?? '');

        if ($regime === 'tickets' || $regime === 'ticket' || $regime === 'Tickets' || $regime === 'Ticket') {
            $templateProcessor->setValue('etudiant.regime', '☑ Tickets   ☐ Externe');
        } elseif ($regime === 'externe' || $regime === 'Externe') {
            $templateProcessor->setValue('etudiant.regime', '☐ Tickets   ☑ Externe');
        } else {
            $templateProcessor->setValue('etudiant.regime', '☐ Tickets   ☐ Externe');
        }

        // Représentant légal 1 = Responsable financier
        $responsable = $etudiant->getResponsableUn();
        if ($responsable) {
            $this->setResponsableValues($templateProcessor, 'representant', $responsable);
            $this->setResponsableValues($templateProcessor, 'representant_financier', $responsable);
        }
    }

    private function setResponsableValues(TemplateProcessor $templateProcessor, string $prefix, $responsable): void
    {
        if (!$responsable) {
            return;
        }

        $templateProcessor->setValue("{$prefix}.nom", $responsable->getNom() ?? 'Non renseigné');
        $templateProcessor->setValue("{$prefix}.prenom", $responsable->getPrenom() ?? 'Non renseigné');
        $templateProcessor->setValue("{$prefix}.adresse", $responsable->getAdresse() ?? 'Non renseigné');
        $templateProcessor->setValue("{$prefix}.code_postal", $responsable->getCodePostal() ?? 'Non renseigné');
        $templateProcessor->setValue("{$prefix}.ville", $responsable->getCommune() ?? 'Non renseigné');
        $templateProcessor->setValue("{$prefix}.telephone", $responsable->getTelephoneFixe() ?? 'Non renseigné');
        $templateProcessor->setValue("{$prefix}.email", $responsable->getCourriel() ?? 'Non renseigné');
        $templateProcessor->setValue("{$prefix}.nom_employeur", $responsable->getNomEmployeur() ?? 'Non renseigné');
        $templateProcessor->setValue("{$prefix}.adresse_employeur", $responsable->getAdresseEmployeur() ?? 'Non renseigné');
    }

    private function createDocxDownloadResponse(string $filePath): BinaryFileResponse
    {
        return new BinaryFileResponse($filePath, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"',
        ]);
    }
}
