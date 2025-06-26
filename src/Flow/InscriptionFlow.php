<?php

namespace App\Flow;

use App\Form\InscriptionType;
use App\Entity\User;
use Craue\FormFlowBundle\Form\FormFlow;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class InscriptionFlow extends FormFlow
{
    private EntityManagerInterface $entityManager;
    private $session;
    private TokenStorageInterface $tokenStorage;
    private LoggerInterface $logger;
    private array $formData;

    public function __construct(
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->session = $requestStack->getSession();
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->formData = $this->createFormData();
    }

    protected function loadStepsConfig(): array
    {
        return [
            1 => [
                'label' => 'Informations personnelles',
                'form_type' => InscriptionType::class,
                'form_options' => ['step' => 1],
            ],
            2 => [
                'label' => 'Contact et urgence',
                'form_type' => InscriptionType::class,
                'form_options' => ['step' => 2],
            ],
            3 => [
                'label' => 'Informations scolaires',
                'form_type' => InscriptionType::class,
                'form_options' => ['step' => 3],
            ],
            4 => [
                'label' => 'Représentant légal 1',
                'form_type' => InscriptionType::class,
                'form_options' => ['step' => 4],
            ],
            5 => [
                'label' => 'Représentant légal 2',
                'form_type' => InscriptionType::class,
                'form_options' => ['step' => 5],
            ],
            6 => [
                'label' => 'Scolarité antérieure',
                'form_type' => InscriptionType::class,
                'form_options' => ['step' => 6],
            ],
            7 => [
                'label' => 'Informations médicales',
                'form_type' => InscriptionType::class,
                'form_options' => ['step' => 7],
            ],
            8 => [
                'label' => 'Responsable financier',
                'form_type' => InscriptionType::class,
                'form_options' => ['step' => 8],
            ],
            9 => [
                'label' => 'Documents',
                'form_type' => InscriptionType::class,
                'form_options' => ['step' => 9],
            ],
            10 => [
                'label' => 'Finalisation et adhésion',
                'form_type' => InscriptionType::class,
                'form_options' => ['step' => 10],
            ],
        ];
    }

    public function getFormOptions($step, array $options = []): array
    {
        $options = parent::getFormOptions($step, $options);
        $options['flow_step'] = $step;
        
        $this->loadFormData();
        $options['data'] = $this->getFormData();
        
        return $options;
    }

    public function setFormData(array $data): void
    {
        $this->formData = array_merge($this->formData, $data);
    }

    public function getFormData(): array
    {
        try {
            $parentData = parent::getFormData();
            if (is_array($parentData)) {
                return array_merge($this->formData, $parentData);
            }
        } catch (\Exception $e) {}

        return $this->formData;
    }

    protected function loadFormData(): void
    {
        if (empty($this->formData)) {
            $this->formData = $this->createFormData();
        }
    }

    protected function createFormData(): array
    {
        return [
            // Étape 1 - Informations personnelles
            'nom' => null,
            'prenom' => null,
            'email' => null,
            'dateNaissance' => null,
            'sexe' => null,
            'nationalite' => null,
            'departement' => null,
            'communeNaissance' => null,
            'numSecuSocial' => null,

            // Étape 2 - Contact et urgence
            'numeroMobile' => null,
            'telephoneFixe' => null,
            'accepterSms' => false,
            'nomContacteUrgence' => null,
            'numeroContacteUrgence' => null,

            // Étape 3 - Informations scolaires
            'classe' => null,
            'promotion' => null,
            'regime' => null,
            'redoublant' => false,
            'lvUn' => null,
            'lvDeux' => null,
            'dernierDiplome' => null,
            'transportScolaire' => null,
            'immatriculationVeic' => null,

            // Étape 4 - Représentant légal 1
            'representantLegal1Nom' => null,
            'representantLegal1Prenom' => null,
            'representantLegal1Courriel' => null,
            'representantLegal1Telephone' => null,
            'representantLegal1TelephoneFixe' => null,
            'representantLegal1TelephonePro' => null,
            'representantLegal1Adresse' => null,
            'representantLegal1CodePostal' => null,
            'representantLegal1Commune' => null,
            'representantLegal1Poste' => null,
            'representantLegal1LienEleve' => null,
            'representantLegal1NomEmployeur' => null,
            'representantLegal1AdresseEmployeur' => null,
            'representantLegal1Sms' => false,

            // Étape 5 - Représentant légal 2
            'representantLegal2Nom' => null,
            'representantLegal2Prenom' => null,
            'representantLegal2Courriel' => null,
            'representantLegal2Telephone' => null,
            'representantLegal2TelephoneFixe' => null,
            'representantLegal2TelephonePro' => null,
            'representantLegal2Adresse' => null,
            'representantLegal2CodePostal' => null,
            'representantLegal2Commune' => null,
            'representantLegal2Poste' => null,
            'representantLegal2LienEleve' => null,
            'representantLegal2NomEmployeur' => null,
            'representantLegal2AdresseEmployeur' => null,
            'representantLegal2Sms' => false,

            // Étape 6 - Scolarité antérieure
            'etablissementPrecedent1' => null,
            'classePrecedente1' => null,
            'anneeScolairePrecedente1' => null,
            'etablissementPrecedent2' => null,
            'classePrecedente2' => null,
            'anneeScolairePrecedente2' => null,

            // Étape 7 - Informations médicales
            'medecinTraitantNom' => null,
            'medecinTraitantTelephone' => null,
            'medecinTraitantAdresse' => null,
            'dernierRappelAntitetanique' => null,
            'observations' => null,
            'secuSocialeNom' => null,
            'secuSocialeAdresse' => null,
            'assureurNom' => null,
            'assureurAdresse' => null,
            'assureurNumeroAssurance' => null,

            // Étape 8 - Responsable financier
            'responsableFinancierNom' => null,
            'responsableFinancierPrenom' => null,
            'responsableFinancierNomEmployeur' => null,
            'responsableFinancierAdresseEmployeur' => null,

            // Étape 9 - Documents
            'carteVitale' => null,
            'photoIdentite' => null,
            'attestationIdentite' => null,
            'bourse' => null,
            'attestationJDC' => null,
            'attestationReusite' => null,

            // Étape 10 - Finalisation et adhésion
            'cheque' => false,
            'droitImage' => false,
            'adhesionAccepted' => false,
            'adhesionPaymentMethod' => null,
            'adhesionImageRights' => false,
        ];
    }

    public function validateAllData(): array
    {
        $errors = [];
        $data = $this->getFormData();

        // Validation étape 1 - Informations personnelles
        if (empty($data['nom'])) {
            $errors[] = 'Le nom est obligatoire.';
        }
        if (empty($data['prenom'])) {
            $errors[] = 'Le prénom est obligatoire.';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'adresse email est obligatoire et doit être valide.';
        }
        if (empty($data['dateNaissance'])) {
            $errors[] = 'La date de naissance est obligatoire.';
        }
        if (empty($data['sexe'])) {
            $errors[] = 'Le sexe est obligatoire.';
        }

        // Validation étape 2 - Contact et urgence
        if (empty($data['numeroMobile'])) {
            $errors[] = 'Le numéro de téléphone mobile est obligatoire.';
        }
        if (empty($data['nomContacteUrgence'])) {
            $errors[] = 'Le nom du contact d\'urgence est obligatoire.';
        }
        if (empty($data['numeroContacteUrgence'])) {
            $errors[] = 'Le numéro du contact d\'urgence est obligatoire.';
        }

        // Validation étape 3 - Informations scolaires
        if (empty($data['classe'])) {
            $errors[] = 'La classe est obligatoire.';
        }
        if (empty($data['regime'])) {
            $errors[] = 'Le régime est obligatoire.';
        }

        // Validation étape 4 - Représentant légal 1
        if (empty($data['representantLegal1Nom'])) {
            $errors[] = 'Le nom du représentant légal est obligatoire.';
        }
        if (empty($data['representantLegal1Prenom'])) {
            $errors[] = 'Le prénom du représentant légal est obligatoire.';
        }
        if (!empty($data['representantLegal1Courriel']) && !filter_var($data['representantLegal1Courriel'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'email du représentant légal n\'est pas valide.';
        }

        // Validation étape 8 - Responsable financier
        if (empty($data['responsableFinancierNom'])) {
            $errors[] = 'Le nom du responsable financier est obligatoire.';
        }
        if (empty($data['responsableFinancierPrenom'])) {
            $errors[] = 'Le prénom du responsable financier est obligatoire.';
        }

        // Validation étape 10 - Adhésion
        if (!($data['adhesionAccepted'] ?? false)) {
            $errors[] = 'L\'adhésion doit être acceptée pour finaliser l\'inscription.';
        }

        return $errors;
    }

    public function getDataSummary(): array
    {
        $data = $this->getFormData();
        
        return [
            'informations_personnelles' => [
                'nom' => $data['nom'] ?? '',
                'prenom' => $data['prenom'] ?? '',
                'email' => $data['email'] ?? '',
                'date_de_naissance' => $data['dateNaissance'] ?? null,
                'sexe' => $data['sexe'] ?? '',
                'nationalite' => $data['nationalite'] ?? '',
                'departement' => $data['departement'] ?? '',
                'commune_naissance' => $data['communeNaissance'] ?? '',
                'num_secu_social' => $data['numSecuSocial'] ?? '',
            ],
            'contact_urgence' => [
                'numero_mobile' => $data['numeroMobile'] ?? '',
                'telephone_fixe' => $data['telephoneFixe'] ?? '',
                'accepter_sms' => $data['accepterSms'] ?? false,
                'nom_contacte_urgence' => $data['nomContacteUrgence'] ?? '',
                'numero_contacte_urgence' => $data['numeroContacteUrgence'] ?? '',
            ],
            'scolarite' => [
                'classe' => $data['classe'] ?? '',
                'promotion' => $data['promotion'] ?? '',
                'regime' => $data['regime'] ?? '',
                'redoublant' => $data['redoublant'] ?? false,
                'lv1' => $data['lvUn'] ?? '',
                'lv2' => $data['lvDeux'] ?? '',
                'dernier_diplome' => $data['dernierDiplome'] ?? '',
                'transport_scolaire' => $data['transportScolaire'] ?? '',
                'immatriculation_vehicule' => $data['immatriculationVeic'] ?? '',
            ],
            'representant_legal_1' => [
                'nom' => $data['representantLegal1Nom'] ?? '',
                'prenom' => $data['representantLegal1Prenom'] ?? '',
                'email' => $data['representantLegal1Courriel'] ?? '',
                'telephone' => $data['representantLegal1Telephone'] ?? '',
                'adresse' => $data['representantLegal1Adresse'] ?? '',
                'code_postal' => $data['representantLegal1CodePostal'] ?? '',
                'commune' => $data['representantLegal1Commune'] ?? '',
                'poste' => $data['representantLegal1Poste'] ?? '',
                'lien_eleve' => $data['representantLegal1LienEleve'] ?? '',
                'employeur' => $data['representantLegal1NomEmployeur'] ?? '',
                'adresse_employeur' => $data['representantLegal1AdresseEmployeur'] ?? '',
                'sms' => $data['representantLegal1Sms'] ?? false,
            ],
            'representant_legal_2' => [
                'nom' => $data['representantLegal2Nom'] ?? '',
                'prenom' => $data['representantLegal2Prenom'] ?? '',
                'email' => $data['representantLegal2Courriel'] ?? '',
                'telephone' => $data['representantLegal2Telephone'] ?? '',
                'adresse' => $data['representantLegal2Adresse'] ?? '',
                'code_postal' => $data['representantLegal2CodePostal'] ?? '',
                'commune' => $data['representantLegal2Commune'] ?? '',
                'poste' => $data['representantLegal2Poste'] ?? '',
                'lien_eleve' => $data['representantLegal2LienEleve'] ?? '',
                'employeur' => $data['representantLegal2NomEmployeur'] ?? '',
                'adresse_employeur' => $data['representantLegal2AdresseEmployeur'] ?? '',
                'sms' => $data['representantLegal2Sms'] ?? false,
            ],
            'scolarite_anterieure' => [
                'etablissement_1' => $data['etablissementPrecedent1'] ?? '',
                'classe_1' => $data['classePrecedente1'] ?? '',
                'annee_scolaire_1' => $data['anneeScolairePrecedente1'] ?? '',
                'etablissement_2' => $data['etablissementPrecedent2'] ?? '',
                'classe_2' => $data['classePrecedente2'] ?? '',
                'annee_scolaire_2' => $data['anneeScolairePrecedente2'] ?? '',
            ],
            'informations_medicales' => [
                'medecin_traitant_nom' => $data['medecinTraitantNom'] ?? '',
                'medecin_traitant_telephone' => $data['medecinTraitantTelephone'] ?? '',
                'medecin_traitant_adresse' => $data['medecinTraitantAdresse'] ?? '',
                'dernier_rappel_antitetanique' => $data['dernierRappelAntitetanique'] ?? null,
                'observations' => $data['observations'] ?? '',
                'secu_sociale_nom' => $data['secuSocialeNom'] ?? '',
                'secu_sociale_adresse' => $data['secuSocialeAdresse'] ?? '',
                'assureur_nom' => $data['assureurNom'] ?? '',
                'assureur_adresse' => $data['assureurAdresse'] ?? '',
                'assureur_numero_assurance' => $data['assureurNumeroAssurance'] ?? '',
            ],
            'responsable_financier' => [
                'nom' => $data['responsableFinancierNom'] ?? '',
                'prenom' => $data['responsableFinancierPrenom'] ?? '',
                'nom_employeur' => $data['responsableFinancierNomEmployeur'] ?? '',
                'adresse_employeur' => $data['responsableFinancierAdresseEmployeur'] ?? '',
            ],
            'documents' => [
                'carte_vitale' => $data['carteVitale'],
                'photo_identite' => $data['photoIdentite'],
                'bourse' => $data['bourse'],
                'attestation_jdc' => $data['attestationJDC'],
                'attestation_identite' => $data['attestationIdentite'],
                'attestation_reusite' => $data['attestationReusite'],
            ],
            'adhesion' => [
                'accepted' => $data['adhesionAccepted'] ?? false,
                'payment_method' => $data['adhesionPaymentMethod'] ?? '',
                'image_rights' => $data['adhesionImageRights'] ?? '',
                'cheque' => $data['cheque'] ?? false,
                'droit_image' => $data['droitImage'] ?? false,
            ],
        ];
    }

    private function mapDataToEntity($inscription, array $data): void
    {
        // Informations personnelles
               $inscription->setNom($data['nom'] ?? '');
        $inscription->setPrenom($data['prenom'] ?? '');
        $inscription->setEmail($data['email'] ?? '');

        // Gérer la date de naissance
        if (!empty($data['dateNaissance'])) {
            $inscription->setDateNaissance(new \DateTime($data['dateNaissance']));
        }

        $inscription->setSexe($data['sexe'] ?? '');
        $inscription->setNationalite($data['nationalite'] ?? '');
        $inscription->setDepartement($data['departement'] ?? '');
        $inscription->setCommuneNaissance($data['communeNaissance'] ?? '');
        $inscription->setNumSecuSocial($data['numSecuSocial'] ?? '');

        // Contact et urgence
        $inscription->setNumeroMobile($data['numeroMobile'] ?? '');
        $inscription->setTelephoneFixe($data['telephoneFixe'] ?? '');
        $inscription->setAccepterSms($data['accepterSms'] ?? false);
        $inscription->setNomContacteUrgence($data['nomContacteUrgence'] ?? '');
        $inscription->setNumeroContacteUrgence($data['numeroContacteUrgence'] ?? '');

        // Informations scolaires
        $inscription->setClasse($data['classe'] ?? '');
        $inscription->setPromotion($data['promotion'] ?? '');
        $inscription->setRegime($data['regime'] ?? '');
        $inscription->setRedoublant($data['redoublant'] ?? false);
        $inscription->setLvUn($data['lvUn'] ?? '');
        $inscription->setLvDeux($data['lvDeux'] ?? '');
        $inscription->setDernierDiplome($data['dernierDiplome'] ?? '');
        $inscription->setTransportScolaire($data['transportScolaire'] ?? '');
        $inscription->setImmatriculationVeic($data['immatriculationVeic'] ?? '');

        // Représentant légal 1
        $inscription->setRepresentantLegal1Nom($data['representantLegal1Nom'] ?? '');
        $inscription->setRepresentantLegal1Prenom($data['representantLegal1Prenom'] ?? '');
        $inscription->setRepresentantLegal1Courriel($data['representantLegal1Courriel'] ?? '');
        $inscription->setRepresentantLegal1Telephone($data['representantLegal1Telephone'] ?? '');
        $inscription->setRepresentantLegal1TelephoneFixe($data['representantLegal1TelephoneFixe'] ?? '');
        $inscription->setRepresentantLegal1TelephonePro($data['representantLegal1TelephonePro'] ?? '');
        $inscription->setRepresentantLegal1Adresse($data['representantLegal1Adresse'] ?? '');
        $inscription->setRepresentantLegal1CodePostal($data['representantLegal1CodePostal'] ?? '');
        $inscription->setRepresentantLegal1Commune($data['representantLegal1Commune'] ?? '');
        $inscription->setRepresentantLegal1Poste($data['representantLegal1Poste'] ?? '');
        $inscription->setRepresentantLegal1LienEleve($data['representantLegal1LienEleve'] ?? '');
        $inscription->setRepresentantLegal1NomEmployeur($data['representantLegal1NomEmployeur'] ?? '');
        $inscription->setRepresentantLegal1AdresseEmployeur($data['representantLegal1AdresseEmployeur'] ?? '');
        $inscription->setRepresentantLegal1Sms($data['representantLegal1Sms'] ?? false);

        // Représentant légal 2
        $inscription->setRepresentantLegal2Nom($data['representantLegal2Nom'] ?? '');
        $inscription->setRepresentantLegal2Prenom($data['representantLegal2Prenom'] ?? '');
        $inscription->setRepresentantLegal2Courriel($data['representantLegal2Courriel'] ?? '');
        $inscription->setRepresentantLegal2Telephone($data['representantLegal2Telephone'] ?? '');
        $inscription->setRepresentantLegal2TelephoneFixe($data['representantLegal2TelephoneFixe'] ?? '');
        $inscription->setRepresentantLegal2TelephonePro($data['representantLegal2TelephonePro'] ?? '');
        $inscription->setRepresentantLegal2Adresse($data['representantLegal2Adresse'] ?? '');
        $inscription->setRepresentantLegal2CodePostal($data['representantLegal2CodePostal'] ?? '');
        $inscription->setRepresentantLegal2Commune($data['representantLegal2Commune'] ?? '');
        $inscription->setRepresentantLegal2Poste($data['representantLegal2Poste'] ?? '');
        $inscription->setRepresentantLegal2LienEleve($data['representantLegal2LienEleve'] ?? '');
        $inscription->setRepresentantLegal2NomEmployeur($data['representantLegal2NomEmployeur'] ?? '');
        $inscription->setRepresentantLegal2AdresseEmployeur($data['representantLegal2AdresseEmployeur'] ?? '');
        $inscription->setRepresentantLegal2Sms($data['representantLegal2Sms'] ?? false);

        // Scolarité antérieure
        $inscription->setEtablissementPrecedent1($data['etablissementPrecedent1'] ?? '');
        $inscription->setClassePrecedente1($data['classePrecedente1'] ?? '');
        $inscription->setAnneeScolairePrecedente1($data['anneeScolairePrecedente1'] ?? '');
        $inscription->setEtablissementPrecedent2($data['etablissementPrecedent2'] ?? '');
        $inscription->setClassePrecedente2($data['classePrecedente2'] ?? '');
        $inscription->setAnneeScolairePrecedente2($data['anneeScolairePrecedente2'] ?? '');

        // Informations médicales
        $inscription->setMedecinTraitantNom($data['medecinTraitantNom'] ?? '');
        $inscription->setMedecinTraitantTelephone($data['medecinTraitantTelephone'] ?? '');
        $inscription->setMedecinTraitantAdresse($data['medecinTraitantAdresse'] ?? '');
        
        if (!empty($data['dernierRappelAntitetanique'])) {
            $inscription->setDernierRappelAntitetanique(new \DateTime($data['dernierRappelAntitetanique']));
        }
        
        $inscription->setObservations($data['observations'] ?? '');
        $inscription->setSecuSocialeNom($data['secuSocialeNom'] ?? '');
        $inscription->setSecuSocialeAdresse($data['secuSocialeAdresse'] ?? '');
        $inscription->setAssureurNom($data['assureurNom'] ?? '');
        $inscription->setAssureurAdresse($data['assureurAdresse'] ?? '');
        $inscription->setAssureurNumeroAssurance($data['assureurNumeroAssurance'] ?? '');

        // Responsable financier
        $inscription->setResponsableFinancierNom($data['responsableFinancierNom'] ?? '');
        $inscription->setResponsableFinancierPrenom($data['responsableFinancierPrenom'] ?? '');
        $inscription->setResponsableFinancierNomEmployeur($data['responsableFinancierNomEmployeur'] ?? '');
        $inscription->setResponsableFinancierAdresseEmployeur($data['responsableFinancierAdresseEmployeur'] ?? '');

        // Documents
        $inscription->setCarteVitale($data['carteVitale']);
        $inscription->setPhotoIdentite($data['photoIdentite']);
        $inscription->setAttestationIdentite($data['attestationIdentite']);
        $inscription->setBourse($data['bourse']);
        $inscription->setAttestationJDC($data['attestationJDC']);
        $inscription->setAttestationReusite($data['attestationReusite']);

        // Adhésion et finalisation
        $inscription->setCheque($data['cheque'] ?? false);
        $inscription->setDroitImage($data['droitImage'] ?? false);
        $inscription->setAdhesionAccepted($data['adhesionAccepted'] ?? false);
        $inscription->setAdhesionPaymentMethod($data['adhesionPaymentMethod'] ?? '');
        $inscription->setAdhesionImageRights($data['adhesionImageRights'] ?? false);
    }

    /**
     * Nouvelle méthode : Génère un récapitulatif complet pour validation finale
     * Utilisée avant la sauvegarde pour présenter un résumé à l'utilisateur
     */
    public function generateCompleteSummary(): array
    {
        $data = $this->getFormData();
        $summary = $this->getDataSummary();
        $errors = $this->validateAllData();
        
        return [
            'resume' => $summary,
            'validation' => [
                'errors' => $errors,
                'is_valid' => empty($errors),
                'completion_rate' => $this->getCompletionRate()
            ],
            'metadata' => [
                'total_steps' => count($this->loadStepsConfig()),
                'completed_steps' => $this->getCurrentStepNumber(),
                'date_creation' => date('Y-m-d H:i:s'),
                'total_fields' => count($data),
                'filled_fields' => count(array_filter($data, function($value) {
                    return !empty($value) && $value !== null && $value !== false;
                }))
            ]
        ];
    }

    /**
     * Nouvelle méthode : Calcule le taux de complétion du formulaire
     */
    public function getCompletionRate(): float
    {
        $data = $this->getFormData();
        $requiredFields = $this->getRequiredFields();
        
        $filledRequired = 0;
        foreach ($requiredFields as $field) {
            if (!empty($data[$field])) {
                $filledRequired++;
            }
        }
        
        return count($requiredFields) > 0 ? round(($filledRequired / count($requiredFields)) * 100, 2) : 0;
    }

    /**
     * Nouvelle méthode : Définit les champs obligatoires
     */
    private function getRequiredFields(): array
    {
        return [
            // Étape 1 - Obligatoires
            'nom',
            'prenom', 
            'email',
            'dateNaissance',
            'sexe',
            
            // Étape 2 - Obligatoires
            'numeroMobile',
            'nomContacteUrgence',
            'numeroContacteUrgence',
            
            // Étape 3 - Obligatoires
            'classe',
            'regime',
            
            // Étape 4 - Obligatoires
            'representantLegal1Nom',
            'representantLegal1Prenom',
            
            // Étape 8 - Obligatoires
            'responsableFinancierNom',
            'responsableFinancierPrenom',
            
            // Étape 10 - Obligatoires
            'adhesionAccepted'
        ];
    }

    /**
     * Nouvelle méthode : Export des données en format PDF ou autre
     * Peut être utilisée pour générer un document récapitulatif
     */
    public function exportData(string $format = 'array'): mixed
    {
        $summary = $this->generateCompleteSummary();
        
        switch ($format) {
            case 'json':
                return json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                
            case 'csv':
                return $this->convertToCSV($summary['resume']);
                
            case 'xml':
                return $this->convertToXML($summary);
                
            default:
                return $summary;
        }
    }

    /**
     * Nouvelle méthode : Conversion en CSV
     */
    private function convertToCSV(array $data): string
    {
        $csv = '';
        $csv .= "Section,Champ,Valeur\n";
        
        foreach ($data as $section => $fields) {
            foreach ($fields as $field => $value) {
                $csv .= sprintf("%s,%s,%s\n", 
                    $section, 
                    $field, 
                    is_bool($value) ? ($value ? 'Oui' : 'Non') : (string)$value
                );
            }
        }
        
        return $csv;
    }

    /**
     * Nouvelle méthode : Conversion en XML
     */
    private function convertToXML(array $data): string
    {
        $xml = new \SimpleXMLElement('<inscription/>');
        $this->arrayToXML($data, $xml);
        return $xml->asXML();
    }

    /**
     * Méthode utilitaire pour la conversion XML
     */
    private function arrayToXML(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXML($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value));
            }
        }
    }

    /**
     * Nouvelle méthode : Nettoyage des données temporaires
     * À appeler après sauvegarde réussie
     */
    public function cleanup(): void
    {
        $this->formData = [];
        // Nettoyer les fichiers temporaires si nécessaire
        $this->cleanupTemporaryFiles();
    }

    /**
     * Nouvelle méthode : Nettoyage des fichiers temporaires
     */
    private function cleanupTemporaryFiles(): void
    {
        $documentsFields = [
            'carteVitale', 'photoIdentite', 'bourse',
            'attestationJDC', 'attestationIdentite', 'attestationReusite'
        ];
        
        $data = $this->getFormData();
        
        foreach ($documentsFields as $field) {
            if (!empty($data[$field]) && is_string($data[$field])) {
                $filePath = $data[$field];
                // Vérifier si c'est un fichier temporaire et le supprimer
                if (strpos($filePath, '/tmp/') === 0 && file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }
    }

    /**
     * Nouvelle méthode : Validation spécifique par étape
     * Permet de valider une étape particulière indépendamment
     */
    public function validateStep(int $step): array
    {
        $data = $this->getFormData();
        $errors = [];

        switch ($step) {
            case 1: // Informations personnelles
                if (empty($data['nom'])) $errors[] = 'Le nom est obligatoire.';
                if (empty($data['prenom'])) $errors[] = 'Le prénom est obligatoire.';
                if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'L\'adresse email est obligatoire et doit être valide.';
                }
                if (empty($data['dateNaissance'])) {
                    $errors[] = 'La date de naissance est obligatoire.';
                }
                if (empty($data['sexe'])) $errors[] = 'Le sexe est obligatoire.';
                break;

            case 2: // Contact et urgence
                if (empty($data['numeroMobile'])) {
                    $errors[] = 'Le numéro de téléphone mobile est obligatoire.';
                }
                if (empty($data['nomContacteUrgence'])) {
                    $errors[] = 'Le nom du contact d\'urgence est obligatoire.';
                }
                if (empty($data['numeroContacteUrgence'])) {
                    $errors[] = 'Le numéro du contact d\'urgence est obligatoire.';
                }
                break;

            case 3: // Informations scolaires
                if (empty($data['classe'])) $errors[] = 'La classe est obligatoire.';
                if (empty($data['regime'])) $errors[] = 'Le régime est obligatoire.';
                break;

            case 4: // Représentant légal 1
                if (empty($data['representantLegal1Nom'])) {
                    $errors[] = 'Le nom du représentant légal est obligatoire.';
                }
                if (empty($data['representantLegal1Prenom'])) {
                    $errors[] = 'Le prénom du représentant légal est obligatoire.';
                }
                if (!empty($data['representantLegal1Courriel']) && 
                    !filter_var($data['representantLegal1Courriel'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'L\'email du représentant légal n\'est pas valide.';
                }
                break;

            case 8: // Responsable financier
                if (empty($data['responsableFinancierNom'])) {
                    $errors[] = 'Le nom du responsable financier est obligatoire.';
                }
                if (empty($data['responsableFinancierPrenom'])) {
                    $errors[] = 'Le prénom du responsable financier est obligatoire.';
                }
                break;

            case 10: // Adhésion
                if (!($data['adhesionAccepted'] ?? false)) {
                    $errors[] = 'L\'adhésion doit être acceptée pour finaliser l\'inscription.';
                }
                break;
        }

        return $errors;
    }

    /**
     * Sauvegarde temporaire en session (brouillon)
     * Cette méthode ne touche PAS à la base de données
     */
    public function saveDraft(): bool
    {
        try {
            $data = $this->getFormData();
            
            // Nettoyer les données pour la session (supprimer les objets File)
            $cleanData = $this->cleanDataForSession($data);
            
            // Sauvegarder en session avec un identifiant unique
            $userId = $this->getCurrentUserId();
            $draftKey = 'inscription_draft_' . $userId;
            $this->session->set($draftKey, [
                'data' => $cleanData,
                'current_step' => $this->getCurrentStepNumber(),
                'timestamp' => time()
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la sauvegarde du brouillon', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Cette méthode NE fait PAS la sauvegarde en base !
     * Elle prépare seulement les données finales et retourne un statut
     * La sauvegarde réelle se fait dans le contrôleur avec saveInscriptionData()
     */
    public function saveFormData(): bool
    {
        try {
            // Valider toutes les données
            $errors = $this->validateAllData();
            if (!empty($errors)) {
                               return false;
            }
            
            // Les données sont prêtes pour la sauvegarde
            // Mais on ne sauvegarde PAS ici - c'est le rôle du contrôleur
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la validation finale des données', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Charge un brouillon depuis la session
     */
    public function loadDraft(): bool
    {
        try {
            $userId = $this->getCurrentUserId();
            $draftKey = 'inscription_draft_' . $userId;
            $draft = $this->session->get($draftKey);
            
            if ($draft && isset($draft['data'])) {
                $this->setFormData($draft['data']);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du chargement du brouillon', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getSavedStepFromDraft(): ?int
    {
        $userId = $this->getCurrentUserId();
        $draftKey = 'inscription_draft_' . $userId;
        $draft = $this->session->get($draftKey);

        if ($draft && isset($draft['current_step'])) {
            return (int) $draft['current_step'];
        }

        return null;
    }

    /**
     * Supprime le brouillon de la session
     */
    public function clearDraft(): void
    {
        $userId = $this->getCurrentUserId();
        $draftKey = 'inscription_draft_' . $userId;
        $this->session->remove($draftKey);
    }

    /**
     * Vérifie si un brouillon existe
     */
    public function hasDraft(): bool
    {
        $userId = $this->getCurrentUserId();
        $draftKey = 'inscription_draft_' . $userId;
        $draft = $this->session->get($draftKey);
        return $draft !== null && isset($draft['data']);
    }

    /**
     * Nettoie les données pour la session (supprime les objets File)
     */
    private function cleanDataForSession(array $data): array
    {
        $cleaned = [];
        
        foreach ($data as $key => $value) {
            // Ignorer les objets File et autres objets non sérialisables
            if (is_object($value) && !($value instanceof \DateTime)) {
                continue;
            }
            
            // Convertir les DateTime en string pour la session
            if ($value instanceof \DateTime) {
                $cleaned[$key] = $value->format('Y-m-d H:i:s');
            } else {
                $cleaned[$key] = $value;
            }
        }
        
        return $cleaned;
    }

    /**
     * Retourne les données finales prêtes pour la persistance
     * (utilisée par le contrôleur)
     */
    public function getFinalDataForPersistence(): array
    {
        $data = $this->getFormData();
        
        // Reconvertir les dates string en DateTime si nécessaire
        $this->reconvertDatesFromSession($data);
        
        return $data;
    }

    /**
     * Reconvertit les dates string en DateTime
     */
    private function reconvertDatesFromSession(array &$data): void
    {
        $dateFields = ['dateNaissance', 'dernierRappelAntitetanique'];
        
        foreach ($dateFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                try {
                    $data[$field] = new \DateTime($data[$field]);
                } catch (\Exception $e) {
                    // Si la conversion échoue, on supprime le champ
                    unset($data[$field]);
                }
            }
        }
    }

    /**
     * Méthode utilitaire pour obtenir l'ID de l'utilisateur courant
     * Corrigé pour éviter les erreurs de dépendance
     */
    private function getCurrentUserId(): ?int
    {
        try {
            $token = $this->tokenStorage->getToken();
            if ($token && ($user = $token->getUser ())) {
                if ($user instanceof User) {
                    return $user->getId();
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Impossible de récupérer l\'utilisateur courant', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Méthode pour sauvegarder les données d'inscription dans la base
     * À utiliser dans le contrôleur après validation finale
     */
    public function saveInscriptionData($inscriptionEntity): bool
    {
        try {
            $data = $this->getFinalDataForPersistence();
            
            // Mapper les données vers l'entité
            $this->mapDataToEntity($inscriptionEntity, $data);
            
            // Persister en base
            $this->entityManager->persist($inscriptionEntity);
            $this->entityManager->flush();
            
            // Nettoyer après sauvegarde réussie
            $this->clearDraft();
            $this->cleanup();
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la sauvegarde en base de données', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Vérifie si toutes les étapes obligatoires sont complétées
     */
    public function isCompleted(): bool
    {
        $errors = $this->validateAllData();
        return empty($errors);
    }

    /**
     * Retourne les statistiques du formulaire
     */
    public function getFormStatistics(): array
    {
        $data = $this->getFormData();
        $totalFields = count($data);
        $filledFields = count(array_filter($data, function($value) {
            return !empty($value) && $value !== null && $value !== false;
        }));
        
        return [
            'total_fields' => $totalFields,
            'filled_fields' => $filledFields,
            'completion_percentage' => $totalFields > 0 ? round(($filledFields / $totalFields) * 100, 2) : 0,
            'required_completion_rate' => $this->getCompletionRate(),
            'is_valid' => $this->isCompleted(),
            'current_step' => $this->getCurrentStepNumber(),
            'total_steps' => count($this->loadStepsConfig())
        ];
    }

    /**
     * Sauvegarde l'inscription complète en base de données
     */
    public function saveInscription($inscriptionEntity): bool
    {
        try {
            $data = $this->getFormData();
            
            // Mapper les données vers l'entité
            $this->mapDataToEntity($inscriptionEntity, $data);
            
            // Définir les dates de création/modification
            $inscriptionEntity->setDateCreation(new \DateTime());
            $inscriptionEntity->setDateModification(new \DateTime());
            
            // Définir l'utilisateur actuel si disponible
            $token = $this->tokenStorage->getToken();
            if ($token && $token->getUser ()) {
                $inscriptionEntity->setUtilisateur($token->getUser ());
            }
            
            // Persister l'entité
            $this->entityManager->persist($inscriptionEntity);
            $this->entityManager->flush();
            
            $this->logger->info('Inscription sauvegardée avec succès', [
                'inscription_id' => $inscriptionEntity->getId(),
                'nom' => $data['nom'] ?? '',
                'prenom' => $data['prenom'] ?? ''
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la sauvegarde de l\'inscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Nettoie les données de session après finalisation
     */
    public function cleanupSessionData(): void
    {
        try {
            // Nettoyer les données du flow
            $this->reset();
            
            // Nettoyer les données spécifiques de l'inscription en session
            $this->session->remove('inscription_flow_data');
            $this->session->remove('inscription_temp_files');
            
            $this->logger->info('Données de session nettoyées après finalisation de l\'inscription');
            
        } catch (\Exception $e) {
            $this->logger->warning('Erreur lors du nettoyage des données de session', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Génère un récapitulatif formaté pour l'affichage
     */
    public function getFormattedSummary(): array
    {
        $summary = $this->getDataSummary();
        $formatted = [];

        // Informations personnelles
        $formatted['Informations personnelles'] = [
            'Nom' => $summary['informations_personnelles']['nom'],
            'Prénom' => $summary['informations_personnelles']['prenom'],
            'Email' => $summary['informations_personnelles']['email'],
            'Date de naissance' => $summary['informations_personnelles']['date_de_naissance'] ? 
                $summary['informations_personnelles']['date_de_naissance']->format('d/m/Y') : '',
            'Sexe' => $summary['informations_personnelles']['sexe'],
            'Nationalité' => $summary['informations_personnelles']['nationalite'],
            'Département de naissance' => $summary['informations_personnelles']['departement'],
            'Commune de naissance' => $summary['informations_personnelles']['commune_naissance'],
            'Numéro de sécurité sociale' => $summary['informations_personnelles']['num_secu_social'],
        ];

        // Contact et urgence
        $formatted['Contact et urgence'] = [
            'Numéro mobile' => $summary['contact_urgence']['numero_mobile'],
            'Téléphone fixe' => $summary['contact_urgence']['telephone_fixe'],
            'Accepter SMS' => $summary['contact_urgence']['accepter_sms'] ? 'Oui' : 'Non',
            'Contact d\'urgence' => $summary['contact_urgence']['nom_contacte_urgence'],
            'Numéro d\'urgence' => $summary['contact_urgence']['numero_contacte_urgence'],
        ];

        // Scolarité
        $formatted['Informations scolaires'] = [
            'Classe' => $summary['scolarite']['classe'],
            'Promotion' => $summary['scolarite']['promotion'],
            'Régime' => $summary['scolarite']['regime'],
            'Redoublant' => $summary['scolarite']['redoublant'] ? 'Oui' : 'Non',
            'Langue vivante 1' => $summary['scolarite']['lv1'],
            'Langue vivante 2' => $summary['scolarite']['lv2'],
            'Dernier diplôme' => $summary['scolarite']['dernier_diplome'],
            'Transport scolaire' => $summary['scolarite']['transport_scolaire'],
            'Immatriculation véhicule' => $summary['scolarite']['immatriculation_vehicule'],
        ];

        // Représentant légal 1
        if (!empty($summary['representant_legal_1']['nom'])) {
            $formatted['Représentant légal 1'] = [
                'Nom' => $summary['representant_legal_1']['nom'],
                'Prénom' => $summary['representant_legal_1']['prenom'],
                'Email' => $summary['representant_legal_1']['email'],
                'Téléphone' => $summary['representant_legal_1']['telephone'],
                'Téléphone fixe' => $summary['representant_legal_1']['telephone_fixe'],
                'Téléphone pro' => $summary['representant_legal_1']['telephone_pro'],
                'Adresse' => $summary['representant_legal_1']['adresse'],
                'Code postal' => $summary['representant_legal_1']['code_postal'],
                'Commune' => $summary['representant_legal_1']['commune'],
                'Poste' => $summary['representant_legal_1']['poste'],
                'Lien avec l\'élève' => $summary['representant_legal_1']['lien_eleve'],
                'Employeur' => $summary['representant_legal_1']['employeur'],
                'Adresse employeur' => $summary['representant_legal_1']['adresse_employeur'],
                'SMS autorisés' => $summary['representant_legal_1']['sms'] ? 'Oui' : 'Non',
            ];
        }

        // Représentant légal 2
        if (!empty($summary['representant_legal_2']['nom'])) {
            $formatted['Représentant légal 2'] = [
                'Nom' => $summary['representant_legal_2']['nom'],
                'Prénom' => $summary['representant_legal_2']['prenom'],
                'Email' => $summary['representant_legal_2']['email'],
                'Téléphone' => $summary['representant_legal_2']['telephone'],
                'Téléphone fixe' => $summary['representant_legal_2']['telephone_fixe'],
                'Téléphone pro' => $summary['representant_legal_2']['telephone_pro'],
                'Adresse' => $summary['representant_legal_2']['adresse'],
                'Code postal' => $summary['representant_legal_2']['code_postal'],
                'Commune' => $summary['representant_legal_2']['commune'],
                'Poste' => $summary['representant_legal_2']['poste'],
                'Lien avec l\'élève' => $summary['representant_legal_2']['lien_eleve'],
                'Employeur' => $summary['representant_legal_2']['employeur'],
                'Adresse employeur' => $summary['representant_legal_2']['adresse_employeur'],
                'SMS autorisés' => $summary['representant_legal_2']['sms'] ? 'Oui' : 'Non',
            ];
        }

        // Scolarité antérieure
        $scolariteAnterieure = [];
        if (!empty($summary['scolarite_anterieure']['etablissement_1'])) {
            $scolariteAnterieure['Établissement 1'] = $summary['scolarite_anterieure']['etablissement_1'];
            $scolariteAnterieure['Classe 1'] = $summary['scolarite_anterieure']['classe_1'];
            $scolariteAnterieure['Année scolaire 1'] = $summary['scolarite_anterieure']['annee_scolaire_1'];
        }
        if (!empty($summary['scolarite_anterieure']['etablissement_2'])) {
            $scolariteAnterieure['Établissement 2'] = $summary['scolarite_anterieure']['etablissement_2'];
            $scolariteAnterieure['Classe 2'] = $summary['scolarite_anterieure']['classe_2'];
            $scolariteAnterieure['Année scolaire 2'] = $summary['scolarite_anterieure']['annee_scolaire_2'];
        }
        if (!empty($scolariteAnterieure)) {
            $formatted['Scolarité antérieure'] = $scolariteAnterieure;
        }

        // Informations médicales
        $formatted['Informations médicales'] = [
            'Médecin traitant' => $summary['informations_medicales']['medecin_traitant_nom'],
            'Téléphone médecin' => $summary['informations_medicales']['medecin_traitant_telephone'],
            'Adresse médecin' => $summary['informations_medicales']['medecin_traitant_adresse'],
            'Dernier rappel antitétanique' => $summary['informations_medicales']['dernier_rappel_antitetanique'] ? 
                $summary['informations_medicales']['dernier_rappel_antitetanique']->format('d/m/Y') : '',
            'Observations' => $summary['informations_medicales']['observations'],
            'Sécurité sociale' => $summary['informations_medicales']['secu_sociale_nom'],
            'Adresse sécu' => $summary['informations_medicales']['secu_sociale_adresse'],
            'Assureur' => $summary['informations_medicales']['assureur_nom'],
            'Adresse assureur' => $summary['informations_medicales']['assureur_adresse'],
            'N° assurance' => $summary['informations_medicales']['assureur_numero_assurance'],
        ];

        // Responsable financier
        $formatted['Responsable financier'] = [
            'Nom' => $summary['responsable_financier']['nom'],
            'Prénom' => $summary['responsable_financier']['prenom'],
            'Employeur' => $summary['responsable_financier']['nom_employeur'],
            'Adresse employeur' => $summary['responsable_financier']['adresse_employeur'],
        ];

        // Documents
        $formatted['Documents fournis'] = [
            'Carte vitale' => $summary['documents']['carteVitale'],
            'Photo d\'identité' => $summary['documents']['photoIdentite'],
            'Attestation bourse' => $summary['documents']['bourse'],
            'Attestation JDC' => $summary['documents']['attestationJdc'],
            'Attestation d\'identité' => $summary['documents']['attestationIdentite'],
            'Attestation de réussite' => $summary['documents']['attestationReusite'],
        ];

        // Adhésion
        $formatted['Finalisation'] = [
            'Adhésion acceptée' => $summary['adhesion']['accepted'] ? 'Oui' : 'Non',
            'Méthode de paiement' => $summary['adhesion']['payment_method'],
            'Droits à l\'image' => $summary['adhesion']['image_rights'],
            'Chèque fourni' => $summary['adhesion']['cheque'] ? 'Oui' : 'Non',
            'Droit à l\'image accepté' => $summary['adhesion']['droit_image'] ? 'Oui' : 'Non',
        ];

        return $formatted;
    }
}
