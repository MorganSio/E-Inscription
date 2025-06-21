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
            'date_de_naissance' => null,
            'sexe' => null,
            'nationalite' => null,
            'departement' => null,
            'departement_naissance' => null,
            'commune_naissance' => null,
            'communeNaissance' => null,
            
            // Étape 2 - Contact et urgence
            'numero_mobile' => null,
            'numeroMobile' => null,
            'nom_contacte_urgence' => null,
            'nomContacteUrgence' => null,
            'numero_contacte_urgence' => null,
            'numeroContacteUrgence' => null,
            
            // Étape 3 - Informations scolaires
            'classe' => null,
            'promotion' => null,
            'regime' => null,
            'lv1' => null,
            'lv_un' => null,
            'lvUn' => null,
            'lv2' => null,
            'lv_deux' => null,
            'lvDeux' => null,
            'redoublant' => false,
            'dernier_diplome' => null,
            'dernierDiplome' => null,
            'transport_scolaire' => null,
            'transportScolaire' => null,
            'immatriculation_veic' => null,
            'immatriculationVeic' => null,
            'num_secu_social' => null,
            'numSecuSocial' => null,
            
            // Étape 4 - Représentant légal 1
            'representant_legal_nom' => null,
            'representantLegal1Nom' => null,
            'representantLegal1Prenom' => null,
            'representantLegal1Email' => null,
            'representantLegal1Telephone' => null,
            'representantLegal1TelephoneFixe' => null,
            'representantLegal1TelephonePro' => null,
            'representantLegal1Adresse' => null,
            'representantLegal1CodePostal' => null,
            'representantLegal1Commune' => null,
            'representantLegal1LienEleve' => null,
            'representantLegal1Poste' => null,
            'representantLegal1NomEmployeur' => null,
            'representantLegal1AdresseEmployeur' => null,
            
            // Étape 5 - Représentant légal 2
            'representantLegal2Nom' => null,
            'representantLegal2Prenom' => null,
            'representantLegal2Email' => null,
            'representantLegal2Telephone' => null,
            'representantLegal2TelephoneFixe' => null,
            'representantLegal2TelephonePro' => null,
            'representantLegal2Adresse' => null,
            'representantLegal2CodePostal' => null,
            'representantLegal2Commune' => null,
            'representantLegal2LienEleve' => null,
            'representantLegal2Poste' => null,
            'representantLegal2NomEmployeur' => null,
            'representantLegal2AdresseEmployeur' => null,
            
            // Étape 6 - Scolarité antérieure
            'etablissement_precedent1' => null,
            'etablissementPrecedent1' => null,
            'classe_precedente1' => null,
            'classePrecedente1' => null,
            'annee_scolaire_precedente1' => null,
            'anneeScolairePrecedente1' => null,
            'etablissement_precedent2' => null,
            'etablissementPrecedent2' => null,
            'classe_precedente2' => null,
            'classePrecedente2' => null,
            'annee_scolaire_precedente2' => null,
            'anneeScolairePrecedente2' => null,
            
            // Étape 7 - Informations médicales
            'medecin_traitant_nom' => null,
            'medecinTraitantNom' => null,
            'medecinTraitantTelephone' => null,
            'medecinTraitantAdresse' => null,
            'dernier_rappel_antitetanique' => null,
            'dernierRappelAntitetanique' => null,
            'observations' => null,
            'secu_sociale_nom' => null,
            'secuSocialeNom' => null,
            'secu_sociale_adresse' => null,
            'secuSocialeAdresse' => null,
            'assureur_nom' => null,
            'assureurNom' => null,
            'assureur_adresse' => null,
            'assureurAdresse' => null,
            'assureur_numero_assurance' => null,
            'assureurNumeroAssurance' => null,
            
            // Étape 8 - Responsable financier
            'responsable_financier_nom' => null,
            'responsableFinancierNom' => null,
            'responsable_financier_prenom' => null,
            'responsableFinancierPrenom' => null,
            'responsable_financier_rib' => null,
            'responsableFinancierRIB' => null,
            'responsable_financier_nom_employeur' => null,
            'responsableFinancierNomEmployeur' => null,
            'responsable_financier_adresse_employeur' => null,
            'responsableFinancierAdresseEmployeur' => null,
            
            // Étape 9 - Documents
            'carte_vitale' => null,
            'carteVitale' => null,
            'photo_identite' => null,
            'photoIdentite' => null,
            'bourse' => null,
            'attestation_jdc' => null,
            'attestationJDC' => null,
            'attestation_identite' => null,
            'attestationIdentite' => null,
            'attestation_reusite' => null,
            'attestationReusite' => null,
            
            // Étape 10 - Finalisation et adhésion
            'cheque' => false,
            'droit_image' => false,
            'droitImage' => false,
            'adhesion_accepted' => false,
            'adhesionAccepted' => false,
            'adhesion_payment_method' => null,
            'adhesionPaymentMethod' => null,
            'adhesion_image_rights' => null,
            'adhesionImageRights' => null,
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
        if (empty($data['dateNaissance']) && empty($data['date_de_naissance'])) {
            $errors[] = 'La date de naissance est obligatoire.';
        }
        if (empty($data['sexe'])) {
            $errors[] = 'Le sexe est obligatoire.';
        }

        // Validation étape 2 - Contact et urgence
        if (empty($data['numeroMobile']) && empty($data['numero_mobile'])) {
            $errors[] = 'Le numéro de téléphone mobile est obligatoire.';
        }
        if (empty($data['nomContacteUrgence']) && empty($data['nom_contacte_urgence'])) {
            $errors[] = 'Le nom du contact d\'urgence est obligatoire.';
        }
        if (empty($data['numeroContacteUrgence']) && empty($data['numero_contacte_urgence'])) {
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
        if (empty($data['representantLegal1Nom']) && empty($data['representant_legal_nom'])) {
            $errors[] = 'Le nom du représentant légal est obligatoire.';
        }
        if (empty($data['representantLegal1Prenom'])) {
            $errors[] = 'Le prénom du représentant légal est obligatoire.';
        }
        if (!empty($data['representantLegal1Email']) && !filter_var($data['representantLegal1Email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'email du représentant légal n\'est pas valide.';
        }

        // Validation étape 8 - Responsable financier
        if (empty($data['responsableFinancierNom']) && empty($data['responsable_financier_nom'])) {
            $errors[] = 'Le nom du responsable financier est obligatoire.';
        }
        if (empty($data['responsableFinancierPrenom']) && empty($data['responsable_financier_prenom'])) {
            $errors[] = 'Le prénom du responsable financier est obligatoire.';
        }

        // Validation étape 10 - Adhésion
        if (!($data['adhesionAccepted'] ?? $data['adhesion_accepted'] ?? false)) {
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
                'date_de_naissance' => $data['dateNaissance'] ?? $data['date_de_naissance'] ?? null,
                'sexe' => $data['sexe'] ?? '',
                'nationalite' => $data['nationalite'] ?? '',
                'departement' => $data['departement'] ?? $data['departement_naissance'] ?? '',
                'commune_naissance' => $data['communeNaissance'] ?? $data['commune_naissance'] ?? '',
            ],
            'contact_urgence' => [
                'numero_mobile' => $data['numeroMobile'] ?? $data['numero_mobile'] ?? '',
                'nom_contacte_urgence' => $data['nomContacteUrgence'] ?? $data['nom_contacte_urgence'] ?? '',
                'numero_contacte_urgence' => $data['numeroContacteUrgence'] ?? $data['numero_contacte_urgence'] ?? '',
            ],
            'scolarite' => [
                'classe' => $data['classe'] ?? '',
                'promotion' => $data['promotion'] ?? '',
                'regime' => $data['regime'] ?? '',
                'lv1' => $data['lvUn'] ?? $data['lv_un'] ?? $data['lv1'] ?? '',
                'lv2' => $data['lvDeux'] ?? $data['lv_deux'] ?? $data['lv2'] ?? '',
                'redoublant' => $data['redoublant'] ?? false,
                'dernier_diplome' => $data['dernierDiplome'] ?? $data['dernier_diplome'] ?? '',
                'transport_scolaire' => $data['transportScolaire'] ?? $data['transport_scolaire'] ?? '',
                'immatriculation_vehicule' => $data['immatriculationVeic'] ?? $data['immatriculation_veic'] ?? '',
                'numero_secu_social' => $data['numSecuSocial'] ?? $data['num_secu_social'] ?? '',
            ],
            'representant_legal_1' => [
                'nom' => $data['representantLegal1Nom'] ?? $data['representant_legal_nom'] ?? '',
                'prenom' => $data['representantLegal1Prenom'] ?? '',
                'email' => $data['representantLegal1Email'] ?? '',
                'telephone' => $data['representantLegal1Telephone'] ?? '',
                'telephone_fixe' => $data['representantLegal1TelephoneFixe'] ?? '',
                'telephone_pro' => $data['representantLegal1TelephonePro'] ?? '',
                'adresse' => $data['representantLegal1Adresse'] ?? '',
                'code_postal' => $data['representantLegal1CodePostal'] ?? '',
                'commune' => $data['representantLegal1Commune'] ?? '',
                'lien_eleve' => $data['representantLegal1LienEleve'] ?? '',
                'poste' => $data['representantLegal1Poste'] ?? '',
                'nom_employeur' => $data['representantLegal1NomEmployeur'] ?? '',
                'adresse_employeur' => $data['representantLegal1AdresseEmployeur'] ?? '',
            ],
            'representant_legal_2' => [
                'nom' => $data['representantLegal2Nom'] ?? '',
                'prenom' => $data['representantLegal2Prenom'] ?? '',
                'email' => $data['representantLegal2Email'] ?? '',
                'telephone' => $data['representantLegal2Telephone'] ?? '',
                'telephone_fixe' => $data['representantLegal2TelephoneFixe'] ?? '',
                'telephone_pro' => $data['representantLegal2TelephonePro'] ?? '',
                'adresse' => $data['representantLegal2Adresse'] ?? '',
                'code_postal' => $data['representantLegal2CodePostal'] ?? '',
                'commune' => $data['representantLegal2Commune'] ?? '',
                'lien_eleve' => $data['representantLegal2LienEleve'] ?? '',
                'poste' => $data['representantLegal2Poste'] ?? '',
                'nom_employeur' => $data['representantLegal2NomEmployeur'] ?? '',
                'adresse_employeur' => $data['representantLegal2AdresseEmployeur'] ?? '',
            ],
                        'scolarite_anterieure' => [
                'etablissement_1' => $data['etablissementPrecedent1'] ?? $data['etablissement_precedent1'] ?? '',
                'classe_1' => $data['classePrecedente1'] ?? $data['classe_precedente1'] ?? '',
                'annee_scolaire_1' => $data['anneeScolairePrecedente1'] ?? $data['annee_scolaire_precedente1'] ?? '',
                'etablissement_2' => $data['etablissementPrecedent2'] ?? $data['etablissement_precedent2'] ?? '',
                'classe_2' => $data['classePrecedente2'] ?? $data['classe_precedente2'] ?? '',
                'annee_scolaire_2' => $data['anneeScolairePrecedente2'] ?? $data['annee_scolaire_precedente2'] ?? '',
            ],
            'informations_medicales' => [
                'medecin_traitant_nom' => $data['medecinTraitantNom'] ?? $data['medecin_traitant_nom'] ?? '',
                'medecin_traitant_telephone' => $data['medecinTraitantTelephone'] ?? '',
                'medecin_traitant_adresse' => $data['medecinTraitantAdresse'] ?? '',
                'dernier_rappel_antitetanique' => $data['dernierRappelAntitetanique'] ?? $data['dernier_rappel_antitetanique'] ?? null,
                'observations' => $data['observations'] ?? '',
                'secu_sociale_nom' => $data['secuSocialeNom'] ?? $data['secu_sociale_nom'] ?? '',
                'secu_sociale_adresse' => $data['secuSocialeAdresse'] ?? $data['secu_sociale_adresse'] ?? '',
                'assureur_nom' => $data['assureurNom'] ?? $data['assureur_nom'] ?? '',
                'assureur_adresse' => $data['assureurAdresse'] ?? $data['assureur_adresse'] ?? '',
                'assureur_numero_assurance' => $data['assureurNumeroAssurance'] ?? $data['assureur_numero_assurance'] ?? '',
            ],
            'responsable_financier' => [
                'nom' => $data['responsableFinancierNom'] ?? $data['responsable_financier_nom'] ?? '',
                'prenom' => $data['responsableFinancierPrenom'] ?? $data['responsable_financier_prenom'] ?? '',
                'nom_employeur' => $data['responsableFinancierNomEmployeur'] ?? $data['responsable_financier_nom_employeur'] ?? '',
                'adresse_employeur' => $data['responsableFinancierAdresseEmployeur'] ?? $data['responsable_financier_adresse_employeur'] ?? '',
            ],
            'documents' => [
                'carte_vitale' => !empty($data['carteVitale'] ?? $data['carte_vitale']),
                'photo_identite' => !empty($data['photoIdentite'] ?? $data['photo_identite']),
                'bourse' => !empty($data['bourse']),
                'attestation_jdc' => !empty($data['attestationJDC'] ?? $data['attestation_jdc']),
                'attestation_identite' => !empty($data['attestationIdentite'] ?? $data['attestation_identite']),
                'attestation_reusite' => !empty($data['attestationReusite'] ?? $data['attestation_reusite']),
            ],
            'adhesion' => [
                'accepted' => $data['adhesionAccepted'] ?? $data['adhesion_accepted'] ?? false,
                'payment_method' => $data['adhesionPaymentMethod'] ?? $data['adhesion_payment_method'] ?? '',
                'image_rights' => $data['adhesionImageRights'] ?? $data['adhesion_image_rights'] ?? '',
                'cheque' => $data['cheque'] ?? false,
                'droit_image' => $data['droitImage'] ?? $data['droit_image'] ?? false,
            ],
        ];
    }

    private function mapDataToEntity($inscription, array $data): void
    {
        // Informations personnelles
        $inscription->setNom($data['nom'] ?? '');
        $inscription->setPrenom($data['prenom'] ?? '');
        $inscription->setEmail($data['email'] ?? '');
        
        // Gérer la date de naissance (plusieurs formats possibles)
        $dateNaissance = $data['dateNaissance'] ?? $data['date_de_naissance'] ?? null;
        if ($dateNaissance) {
            if (is_string($dateNaissance)) {
                $dateNaissance = new \DateTime($dateNaissance);
            }
            $inscription->setDateNaissance($dateNaissance);
        }
        
        $inscription->setSexe($data['sexe'] ?? '');
        $inscription->setNationalite($data['nationalite'] ?? '');
        $inscription->setDepartement($data['departement'] ?? $data['departement_naissance'] ?? '');
        $inscription->setCommuneNaissance($data['communeNaissance'] ?? $data['commune_naissance'] ?? '');

        // Contact et urgence
        $inscription->setNumeroMobile($data['numeroMobile'] ?? $data['numero_mobile'] ?? '');
        $inscription->setNomContacteUrgence($data['nomContacteUrgence'] ?? $data['nom_contacte_urgence'] ?? '');
        $inscription->setNumeroContacteUrgence($data['numeroContacteUrgence'] ?? $data['numero_contacte_urgence'] ?? '');

        // Informations scolaires
        $inscription->setClasse($data['classe'] ?? '');
        $inscription->setPromotion($data['promotion'] ?? '');
        $inscription->setRegime($data['regime'] ?? '');
        $inscription->setLv1($data['lvUn'] ?? $data['lv_un'] ?? $data['lv1'] ?? '');
        $inscription->setLv2($data['lvDeux'] ?? $data['lv_deux'] ?? $data['lv2'] ?? '');
        $inscription->setRedoublant($data['redoublant'] ?? false);
        $inscription->setDernierDiplome($data['dernierDiplome'] ?? $data['dernier_diplome'] ?? '');
        $inscription->setTransportScolaire($data['transportScolaire'] ?? $data['transport_scolaire'] ?? '');
        $inscription->setImmatriculationVehicule($data['immatriculationVeic'] ?? $data['immatriculation_veic'] ?? '');
        $inscription->setNumeroSecuSocial($data['numSecuSocial'] ?? $data['num_secu_social'] ?? '');

        // Représentant légal 1
        $inscription->setRepresentantLegal1Nom($data['representantLegal1Nom'] ?? $data['representant_legal_nom'] ?? '');
        $inscription->setRepresentantLegal1Prenom($data['representantLegal1Prenom'] ?? '');
        $inscription->setRepresentantLegal1Email($data['representantLegal1Email'] ?? '');
        $inscription->setRepresentantLegal1Telephone($data['representantLegal1Telephone'] ?? '');
        $inscription->setRepresentantLegal1TelephoneFixe($data['representantLegal1TelephoneFixe'] ?? '');
        $inscription->setRepresentantLegal1TelephonePro($data['representantLegal1TelephonePro'] ?? '');
        $inscription->setRepresentantLegal1Adresse($data['representantLegal1Adresse'] ?? '');
        $inscription->setRepresentantLegal1CodePostal($data['representantLegal1CodePostal'] ?? '');
        $inscription->setRepresentantLegal1Commune($data['representantLegal1Commune'] ?? '');
        $inscription->setRepresentantLegal1LienEleve($data['representantLegal1LienEleve'] ?? '');
        $inscription->setRepresentantLegal1Poste($data['representantLegal1Poste'] ?? '');
        $inscription->setRepresentantLegal1NomEmployeur($data['representantLegal1NomEmployeur'] ?? '');
        $inscription->setRepresentantLegal1AdresseEmployeur($data['representantLegal1AdresseEmployeur'] ?? '');

        // Représentant légal 2
        $inscription->setRepresentantLegal2Nom($data['representantLegal2Nom'] ?? '');
        $inscription->setRepresentantLegal2Prenom($data['representantLegal2Prenom'] ?? '');
        $inscription->setRepresentantLegal2Email($data['representantLegal2Email'] ?? '');
        $inscription->setRepresentantLegal2Telephone($data['representantLegal2Telephone'] ?? '');
        $inscription->setRepresentantLegal2TelephoneFixe($data['representantLegal2TelephoneFixe'] ?? '');
        $inscription->setRepresentantLegal2TelephonePro($data['representantLegal2TelephonePro'] ?? '');
        $inscription->setRepresentantLegal2Adresse($data['representantLegal2Adresse'] ?? '');
        $inscription->setRepresentantLegal2CodePostal($data['representantLegal2CodePostal'] ?? '');
        $inscription->setRepresentantLegal2Commune($data['representantLegal2Commune'] ?? '');
        $inscription->setRepresentantLegal2LienEleve($data['representantLegal2LienEleve'] ?? '');
        $inscription->setRepresentantLegal2Poste($data['representantLegal2Poste'] ?? '');
        $inscription->setRepresentantLegal2NomEmployeur($data['representantLegal2NomEmployeur'] ?? '');
        $inscription->setRepresentantLegal2AdresseEmployeur($data['representantLegal2AdresseEmployeur'] ?? '');

        // Scolarité antérieure
        $inscription->setEtablissementPrecedent1($data['etablissementPrecedent1'] ?? $data['etablissement_precedent1'] ?? '');
        $inscription->setClassePrecedente1($data['classePrecedente1'] ?? $data['classe_precedente1'] ?? '');
        $inscription->setAnneeScolairePrecedente1($data['anneeScolairePrecedente1'] ?? $data['annee_scolaire_precedente1'] ?? '');
        $inscription->setEtablissementPrecedent2($data['etablissementPrecedent2'] ?? $data['etablissement_precedent2'] ?? '');
        $inscription->setClassePrecedente2($data['classePrecedente2'] ?? $data['classe_precedente2'] ?? '');
        $inscription->setAnneeScolairePrecedente2($data['anneeScolairePrecedente2'] ?? $data['annee_scolaire_precedente2'] ?? '');

        // Informations médicales
        $inscription->setMedecinTraitantNom($data['medecinTraitantNom'] ?? $data['medecin_traitant_nom'] ?? '');
        $inscription->setMedecinTraitantTelephone($data['medecinTraitantTelephone'] ?? '');
        $inscription->setMedecinTraitantAdresse($data['medecinTraitantAdresse'] ?? '');
        
        // Gérer la date du dernier rappel antitétanique
        $dernierRappel = $data['dernierRappelAntitetanique'] ?? $data['dernier_rappel_antitetanique'] ?? null;
        if ($dernierRappel) {
            if (is_string($dernierRappel)) {
                $dernierRappel = new \DateTime($dernierRappel);
            }
            $inscription->setDernierRappelAntitetanique($dernierRappel);
        }
        
        $inscription->setObservations($data['observations'] ?? '');
        $inscription->setSecuSocialeNom($data['secuSocialeNom'] ?? $data['secu_sociale_nom'] ?? '');
        $inscription->setSecuSocialeAdresse($data['secuSocialeAdresse'] ?? $data['secu_sociale_adresse'] ?? '');
        $inscription->setAssureurNom($data['assureurNom'] ?? $data['assureur_nom'] ?? '');
        $inscription->setAssureurAdresse($data['assureurAdresse'] ?? $data['assureur_adresse'] ?? '');
        $inscription->setAssureurNumeroAssurance($data['assureurNumeroAssurance'] ?? $data['assureur_numero_assurance'] ?? '');

        // Responsable financier
        $inscription->setResponsableFinancierNom($data['responsableFinancierNom'] ?? $data['responsable_financier_nom'] ?? '');
        $inscription->setResponsableFinancierPrenom($data['responsableFinancierPrenom'] ?? $data['responsable_financier_prenom'] ?? '');
        $inscription->setResponsableFinancierRIB($data['responsableFinancierRIB'] ?? $data['responsable_financier_rib'] ?? '');
        $inscription->setResponsableFinancierNomEmployeur($data['responsableFinancierNomEmployeur'] ?? $data['responsable_financier_nom_employeur'] ?? '');
        $inscription->setResponsableFinancierAdresseEmployeur($data['responsableFinancierAdresseEmployeur'] ?? $data['responsable_financier_adresse_employeur'] ?? '');

        // Documents (gérer les fichiers uploadés)
        $inscription->setCarteVitale($data['carteVitale'] ?? $data['carte_vitale'] ?? null);
        $inscription->setPhotoIdentite($data['photoIdentite'] ?? $data['photo_identite'] ?? null);
        $inscription->setBourse($data['bourse'] ?? null);
        $inscription->setAttestationJDC($data['attestationJDC'] ?? $data['attestation_jdc'] ?? null);
        $inscription->setAttestationIdentite($data['attestationIdentite'] ?? $data['attestation_identite'] ?? null);
        $inscription->setAttestationReusite($data['attestationReusite'] ?? $data['attestation_reusite'] ?? null);

        // Adhésion et finalisation
        $inscription->setCheque($data['cheque'] ?? false);
        $inscription->setDroitImage($data['droitImage'] ?? $data['droit_image'] ?? false);
        $inscription->setAdhesionAccepted($data['adhesionAccepted'] ?? $data['adhesion_accepted'] ?? false);
        $inscription->setAdhesionPaymentMethod($data['adhesionPaymentMethod'] ?? $data['adhesion_payment_method'] ?? '');
        $inscription->setAdhesionImageRights($data['adhesionImageRights'] ?? $data['adhesion_image_rights'] ?? '');
    }

    // public function saveInscriptionData($inscriptionEntity): bool
    // {
    //     try {
    //         $data = $this->getFinalDataForPersistence();
            
    //         // Mapper les données vers l'entité
    //         $this->mapDataToEntity($inscriptionEntity, $data);
            
    //         // Persister en base
    //         $this->entityManager->persist($inscriptionEntity);
    //         $this->entityManager->flush();
            
    //         // Nettoyer après sauvegarde réussie
    //         $this->clearDraft();
    //         $this->cleanup();
            
    //         return true;
    //     } catch (\Exception $e) {
    //         $this->logger->error('Erreur lors de la sauvegarde en base de données', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         return false;
    //     }
    // }

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
            'carteVitale', 'carte_vitale',
            'photoIdentite', 'photo_identite',
            'bourse',
            'attestationJDC', 'attestation_jdc',
            'attestationIdentite', 'attestation_identite',
            'attestationReusite', 'attestation_reusite'
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
                if (empty($data['dateNaissance']) && empty($data['date_de_naissance'])) {
                    $errors[] = 'La date de naissance est obligatoire.';
                }
                if (empty($data['sexe'])) $errors[] = 'Le sexe est obligatoire.';
                break;

            case 2: // Contact et urgence
                if (empty($data['numeroMobile']) && empty($data['numero_mobile'])) {
                    $errors[] = 'Le numéro de téléphone mobile est obligatoire.';
                }
                if (empty($data['nomContacteUrgence']) && empty($data['nom_contacte_urgence'])) {
                    $errors[] = 'Le nom du contact d\'urgence est obligatoire.';
                }
                if (empty($data['numeroContacteUrgence']) && empty($data['numero_contacte_urgence'])) {
                    $errors[] = 'Le numéro du contact d\'urgence est obligatoire.';
                }
                break;

            case 3: // Informations scolaires
                if (empty($data['classe'])) $errors[] = 'La classe est obligatoire.';
                if (empty($data['regime'])) $errors[] = 'Le régime est obligatoire.';
                break;

            case 4: // Représentant légal 1
                if (empty($data['representantLegal1Nom']) && empty($data['representant_legal_nom'])) {
                    $errors[] = 'Le nom du représentant légal est obligatoire.';
                }
                if (empty($data['representantLegal1Prenom'])) {
                    $errors[] = 'Le prénom du représentant légal est obligatoire.';
                }
                if (!empty($data['representantLegal1Email']) && 
                    !filter_var($data['representantLegal1Email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'L\'email du représentant légal n\'est pas valide.';
                }
                break;

            case 8: // Responsable financier
                if (empty($data['responsableFinancierNom']) && empty($data['responsable_financier_nom'])) {
                    $errors[] = 'Le nom du responsable financier est obligatoire.';
                }
                if (empty($data['responsableFinancierPrenom']) && empty($data['responsable_financier_prenom'])) {
                    $errors[] = 'Le prénom du responsable financier est obligatoire.';
                }
                break;

            case 10: // Adhésion
                if (!($data['adhesionAccepted'] ?? $data['adhesion_accepted'] ?? false)) {
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
            if ($token && ($user = $token->getUser())) {
                if ($user instanceof \App\Entity\User) {
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
            if ($token && $token->getUser()) {
                $inscriptionEntity->setUtilisateur($token->getUser());
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
        ];

        // Contact et urgence
        $formatted['Contact et urgence'] = [
            'Numéro mobile' => $summary['contact_urgence']['numero_mobile'],
            'Contact d\'urgence' => $summary['contact_urgence']['nom_contacte_urgence'],
            'Numéro d\'urgence' => $summary['contact_urgence']['numero_contacte_urgence'],
        ];

        // Scolarité
        $formatted['Informations scolaires'] = [
            'Classe' => $summary['scolarite']['classe'],
            'Promotion' => $summary['scolarite']['promotion'],
            'Régime' => $summary['scolarite']['regime'],
            'LV1' => $summary['scolarite']['lv1'],
            'LV2' => $summary['scolarite']['lv2'],
            'Redoublant' => $summary['scolarite']['redoublant'] ? 'Oui' : 'Non',
            'Dernier diplôme' => $summary['scolarite']['dernier_diplome'],
            'Transport scolaire' => $summary['scolarite']['transport_scolaire'],
            'Immatriculation véhicule' => $summary['scolarite']['immatriculation_vehicule'],
            'N° Sécurité sociale' => $summary['scolarite']['numero_secu_social'],
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
                'Lien avec l\'élève' => $summary['representant_legal_1']['lien_eleve'],
                'Poste' => $summary['representant_legal_1']['poste'],
                'Employeur' => $summary['representant_legal_1']['nom_employeur'],
                'Adresse employeur' => $summary['representant_legal_1']['adresse_employeur'],
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
                'Lien avec l\'élève' => $summary['representant_legal_2']['lien_eleve'],
                'Poste' => $summary['representant_legal_2']['poste'],
                'Employeur' => $summary['representant_legal_2']['nom_employeur'],
                'Adresse employeur' => $summary['representant_legal_2']['adresse_employeur'],
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
            'Carte vitale' => $summary['documents']['carte_vitale'] ? 'Oui' : 'Non',
            'Photo d\'identité' => $summary['documents']['photo_identite'] ? 'Oui' : 'Non',
            'Attestation bourse' => $summary['documents']['bourse'] ? 'Oui' : 'Non',
            'Attestation JDC' => $summary['documents']['attestation_jdc'] ? 'Oui' : 'Non',
            'Attestation d\'identité' => $summary['documents']['attestation_identite'] ? 'Oui' : 'Non',
            'Attestation de réussite' => $summary['documents']['attestation_reusite'] ? 'Oui' : 'Non',
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

    /**
     * Vérifie si toutes les étapes obligatoires sont complétées
     */
    public function isComplete(): bool
    {
        $errors = $this->validateAllData();
        return empty($errors);
    }

    /**
     * Retourne le pourcentage de completion du formulaire
     */
    public function getCompletionPercentage(): int
    {
        $data = $this->getFormData();
        $totalFields = 0;
        $completedFields = 0;

        // Champs obligatoires à vérifier
        $requiredFields = [
            'nom', 'prenom', 'email', 'sexe',
            'numeroMobile', 'numero_mobile',
            'nomContacteUrgence', 'nom_contacte_urgence',
            'numeroContacteUrgence', 'numero_contacte_urgence',
            'classe', 'regime',
            'representantLegal1Nom', 'representant_legal_nom',
            'representantLegal1Prenom',
            'responsableFinancierNom', 'responsable_financier_nom',
            'responsableFinancierPrenom', 'responsable_financier_prenom',
            'adhesionAccepted', 'adhesion_accepted'
        ];

        foreach ($requiredFields as $field) {
            $totalFields++;
            if (!empty($data[$field])) {
                $completedFields++;
            }
        }

        // Vérification spéciale pour la date de naissance
        $totalFields++;
        if (!empty($data['dateNaissance']) || !empty($data['date_de_naissance'])) {
            $completedFields++;
        }

        return $totalFields > 0 ? round(($completedFields / $totalFields) * 100) : 0;
    }
}