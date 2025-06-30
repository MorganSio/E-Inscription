<?php

namespace App\Service;

use App\Entity\InfoEleve;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocxUrgenceGeneratorService
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
        $templatePath = __DIR__ . "/../../public/templates/Fiche d'urgence.docx";
        $outputDocxPath = __DIR__ . "/../../public/generated/Fiche_d_urgence_{$nom}.docx";

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
        $templateProcessor->setValue('etudiant.nom_contact_urgence', $etudiant->getNomContacteUrgence() ?? 'Non renseigné');
        $templateProcessor->setValue('representant.tel_contact_urgence', $etudiant->getNumeroContacteUrgence() ?? 'Non renseigné');
        $templateProcessor->setValue('etudiant.dernier_rappel_antitetanique', $etudiant->getDernierRappelAntitetanique()?->format('d/m/Y') ?? 'Non renseigné');

        $sexe = strtolower($etudiant->getSexe() ?? '');

        if ($sexe === 'Homme' || $sexe === 'homme' || $sexe === 'masculin' || $sexe === 'masculin') {
            $templateProcessor->setValue('etudiant.sexe_choix', '☑ Masculin   ☐ Féminin');
        } elseif ($sexe === 'Féminin' || $sexe === 'Feminin' || $sexe === 'feminin' || $sexe === 'féminin' || $sexe === 'femme' || $sexe === 'Femme') {
            $templateProcessor->setValue('etudiant.sexe_choix', '☐ Masculin   ☑ Féminin');
        } else {
            $templateProcessor->setValue('etudiant.sexe_choix', '☐ Masculin   ☐ Féminin');
        }

        // Représentant général
        $responsable = $etudiant->getResponsableUn();
        if ($responsable) {
            $this->setResponsableValues($templateProcessor, 'representant', $responsable);
        }

        // Représentants spécifiques père/mère
        $responsable1 = $etudiant->getResponsableUn();
        $responsable2 = $etudiant->getResponsableDeux();

        if ($responsable1?->getLienEleve()) {
            $lien1 = strtolower($responsable1->getLienEleve());

            if (str_contains($lien1, 'père') || str_contains($lien1, 'pere')) {
                $this->setResponsableValues($templateProcessor, 'pere', $responsable1);
            } elseif (str_contains($lien1, 'mère') || str_contains($lien1, 'mere')) {
                $this->setResponsableValues($templateProcessor, 'mere', $responsable1);
            }
        }

        if ($responsable2?->getLienEleve()) {
            $lien2 = strtolower($responsable2->getLienEleve());

            if (str_contains($lien2, 'père') || str_contains($lien2, 'pere')) {
                $this->setResponsableValues($templateProcessor, 'pere', $responsable2);
            } elseif (str_contains($lien2, 'mère') || str_contains($lien2, 'mere')) {
                $this->setResponsableValues($templateProcessor, 'mere', $responsable2);
            }
        }

        // Centre Sécurité Sociale
        $centreSecu = $etudiant->getSecuSociale();
        $templateProcessor->setValue('centresecu.nom', $centreSecu?->getNom() ?? 'Non renseigné');
        $templateProcessor->setValue('centresecu.adresse', $centreSecu?->getAddresse() ?? 'Non renseigné');

        // Assurance scolaire
        $assurance = $etudiant->getAssureur();
        $templateProcessor->setValue('assurancesco.nom', $assurance?->getNom() ?? 'Non renseigné');
        $templateProcessor->setValue('assurancesco.adresse', $assurance?->getAddresse() ?? 'Non renseigné');
        $templateProcessor->setValue('assurancesco.numero', $assurance?->getNumeroAssurance() ?? 'Non renseigné');

        // Médecin traitant
        $medecin = $etudiant->getMedecinTraitant();
        $templateProcessor->setValue('medecintraitant.nom', $medecin?->getNom() ?? 'Non renseigné');
        $templateProcessor->setValue('medecintraitant.adresse', $medecin?->getAdresse() ?? 'Non renseigné');
        $templateProcessor->setValue('medecintraitant.numero', $medecin?->getNumero() ?? 'Non renseigné');
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
        $templateProcessor->setValue("{$prefix}.email", $responsable->getCourriel() ?? 'Non renseigné');
        $templateProcessor->setValue("{$prefix}.tel_dom", $responsable->getTelephoneFixe() ?? 'Non renseigné');
        $templateProcessor->setValue("{$prefix}.tel_travail", $responsable->getTelephonePro() ?? 'Non renseigné');
        $templateProcessor->setValue("{$prefix}.tel_perso", $responsable->getTelephonePerso() ?? 'Non renseigné');
        $templateProcessor->setValue("{$prefix}.poste", $responsable->getPoste() ?? 'Non renseigné');
    }

    private function createDocxDownloadResponse(string $filePath): BinaryFileResponse
    {
        return new BinaryFileResponse($filePath, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"',
        ]);
    }
}