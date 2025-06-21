
<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\InfoEleve;
use App\Entity\RepresentantLegal;
use App\Entity\MedecinTraitant;
use App\Entity\ResposableFinancier;
use App\Entity\CentreSecuriteSociale;
use App\Entity\Adhesion;
use App\Entity\AssuranceScolaire;
use App\Entity\ScolariteAnterieur;
use App\Entity\Classe;
use App\Repository\ClasseRepository;
use App\Flow\InscriptionFlow;
use App\Repository\InfoEleveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\File;
use Psr\Log\LoggerInterface;

class InscriptionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InfoEleveRepository $infoEleveRepository,
        private readonly ClasseRepository $classeRepository,
        private readonly LoggerInterface $logger
    ) {}

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_inscription_dashboard');
        }

        return $this->render('inscription/home.html.twig');
    }

    #[Route('/inscription/dashboard', name: 'app_inscription_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        $infoEleve = $this->infoEleveRepository->findOneBy(['user' => $user]);

        $inscription = null;
        $isComplete = false;

        if ($infoEleve) {
            $inscription = $this->convertEntityToArray($user, $infoEleve);
            $this->prepareInscriptionData($infoEleve, $inscription);
            $inscription['isComplete'] = $this->isInscriptionComplete($infoEleve);
            $isComplete = $inscription['isComplete'];
        }

        return $this->render('inscription/dashboard.html.twig', [
            'user' => $user,
            'inscription' => $inscription,
            'isComplete' => $isComplete,
        ]);
    }

    private function prepareInscriptionData(InfoEleve $infoEleve, array &$data): void
    {
        // Initialize the data array
        // $data = [];
        
        // Call the method to convert previous schooling data
        $this->convertScolariteAnterieurToArray($infoEleve, $data);
    }

    #[Route('/inscription/formulaire', name: 'app_inscription_form')]
    #[IsGranted('ROLE_USER')]
    public function inscriptionForm(Request $request, InscriptionFlow $flow): Response
    {
        $user = $this->getUser();
        $flow->bind($request);
        
        // Charger le brouillon s'il existe
        if ($flow->hasDraft()) {
            $flow->loadDraft();
        } else {
            // Charger les données existantes en base
            $this->loadExistingDataIntoFlow($user, $flow);
        }

        $form = $flow->createForm();

        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            // Sauvegarder le brouillon à chaque étape
            $flow->saveDraft();

            // Sauvegarder en base de données à chaque étape
            try {
                $this->saveInscriptionData($user, $flow);
                $this->addFlash('info', 'Étape sauvegardée avec succès !');
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la sauvegarde : ' . $e->getMessage());
                $this->addFlash('error', 'Erreur lors de la sauvegarde. Veuillez réessayer.');
            }

            if ($flow->nextStep()) {
                // Étape suivante
                $form = $flow->createForm();
            } else {
                // Formulaire terminé - validation finale
                if ($flow->saveFormData()) {
                    // Dernière sauvegarde complète
                    $this->saveInscriptionData($user, $flow);
                    
                    // Nettoyer le brouillon
                    $flow->clearDraft();
                    
                    $this->addFlash('success', 'Votre inscription a été complétée avec succès !');
                    return $this->redirectToRoute('app_inscription_dashboard');
                } else {
                    $this->addFlash('error', 'Erreur lors de la finalisation. Veuillez vérifier vos données.');
                }
            }
        }

        return $this->render('inscription/form.html.twig', [
            'form' => $form->createView(),
            'flow' => $flow,
            'user' => $user,
        ]);
    }

    #[Route('/inscription/reset', name: 'app_inscription_reset')]
    #[IsGranted('ROLE_USER')]
    public function resetInscription(InscriptionFlow $flow): Response
    {
        $flow->reset();
        return $this->redirectToRoute('app_inscription_form');
    }

    #[Route('/inscription/resume/{stepNumber}', name: 'app_inscription_resume')]
    #[IsGranted('ROLE_USER')]
    public function resumeInscription(int $stepNumber, Request $request, InscriptionFlow $flow): Response
    {
        $user = $this->getUser();

        $flow->bind($request);
        $this->loadExistingDataIntoFlow($user, $flow);
        $flow->nextStep($stepNumber);

        return $this->redirectToRoute('app_inscription_form');
    }

    #[Route('/inscription/recapitulatif', name: 'app_inscription_summary')]
    #[IsGranted('ROLE_USER')]
    public function summary(InscriptionFlow $flow): Response
    {
        $user = $this->getUser();

        $flow->bind(new Request());
        $this->loadExistingDataIntoFlow($user, $flow);

        return $this->render('inscription/summary.html.twig', [
            'summary' => $flow->getDataSummary(),
            'errors' => $flow->validateAllData(),
            'user' => $user,
        ]);
    }

    #[Route('/inscription/delete', name: 'app_inscription_delete')]
    #[IsGranted('ROLE_USER')]
    public function deleteInscription(): Response
    {
        $user = $this->getUser();
        $infoEleve = $this->infoEleveRepository->findOneBy(['user' => $user]);

        if ($infoEleve) {
            $this->entityManager->remove($infoEleve);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre inscription a été supprimée.');
        }

        return $this->redirectToRoute('app_inscription_dashboard');
    }

    /**
     * Sauvegarde des données d'inscription - VERSION CORRIGÉE
     */
    private function saveInscriptionData(User $user, InscriptionFlow $flow): void
    {
        try {
            $data = $flow->getFormData();
            
            // Récupérer ou créer InfoEleve
            $infoEleve = $this->infoEleveRepository->findOneBy(['user' => $user]);
            if (!$infoEleve) {
                $infoEleve = new InfoEleve($user);
                $infoEleve->setUser($user);
            }

            $this->mapDataToEntity($data, $user, $infoEleve);
            
            $this->entityManager->persist($infoEleve);
            $this->entityManager->flush();

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la sauvegarde de l\'inscription', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Mapping des données vers l'entité - VERSION CORRIGÉE basée sur l'ancien contrôleur
     */
    private function mapDataToEntity(array $data, User $user, InfoEleve $infoEleve): void
    {
        // Mettre à jour les données utilisateur
        if (isset($data['nom']) && $data['nom'] !== "") {
            $user->setNom($data['nom']);
        }
        if (isset($data['prenom']) && $data['prenom'] !== "") {
            $user->setPrenom($data['prenom']);
        }
        if (isset($data['email']) && $data['email'] !== "") {
            $user->setEmail($data['email']);
        }

        // Gestion des entités liées ScolariteAnterieur - AMÉLIORATION basée sur l'ancien code
        $this->handleScolariteAnterieur($data, $infoEleve);

        // Mapper les données de base vers InfoEleve
        if (isset($data['dateNaissance']) && $data['dateNaissance'] !== "") {
            if ($data['dateNaissance'] instanceof \DateTime) {
                $infoEleve->setDateDeNaissance($data['dateNaissance']);
            } elseif (is_string($data['dateNaissance'])) {
                try {
                    $date = \DateTime::createFromFormat('d-m-Y', $data['dateNaissance']);
                    if ($date) {
                        $infoEleve->setDateDeNaissance($date);
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Date de naissance invalide : ' . $data['dateNaissance']);
                }
            }
        }

        // Données de base
        $basicFields = [
            'promotion' => 'setPromotion',
            'numeroMobile' => 'setNumeroMobile',
            'nationalite' => 'setNationalite',
            'departement' => 'setDepartement',
            'communeNaissance' => 'setCommuneNaissance',
            'nomContacteUrgence' => 'setNomContacteUrgence',
            'numeroContacteUrgence' => 'setNumeroContacteUrgence',
            'dernierDiplome' => 'setDernierDiplome',
            'immatriculationVeic' => 'setImmattriculationVeic',
            'numSecuSocial' => 'setNumSecuSocial',
            'transportScolaire' => 'setTransportScolaire',
            'lvUn' => 'setLVUn',
            'lvDeux' => 'setLVDeux',
            'sexe' => 'setSexe',
            'regime' => 'setRegime',
            'observations' => 'setObservations'
        ];

        foreach ($basicFields as $dataKey => $method) {
            if (isset($data[$dataKey]) && $data[$dataKey] !== "") {
                $infoEleve->$method($data[$dataKey]);
            }
        }

        // Gestion des dates spéciales
        if (isset($data['dernierRappelAntitetanique']) && $data['dernierRappelAntitetanique'] !== "") {
            if ($data['dernierRappelAntitetanique'] instanceof \DateTime) {
                $infoEleve->setDernierRappelAntitetanique($data['dernierRappelAntitetanique']);
            } elseif (is_string($data['dernierRappelAntitetanique'])) {
                try {
                    $date = \DateTime::createFromFormat('Y-m-d', $data['dernierRappelAntitetanique']);
                    if ($date) {
                        $infoEleve->setDernierRappelAntitetanique($date);
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Date rappel antitétanique invalide : ' . $data['dernierRappelAntitetanique']);
                }
            }
        }

        // Gestion des booléens - AMÉLIORATION avec vérification comme dans l'ancien code
        $booleanFields = [
            'cheque' => 'setCheque',
            'droitImage' => 'setDroitImage',
            'redoublant' => 'setRedoublant',
            'carteVitale' => 'setCarteVitale',
            'photoIdentite' => 'setPhotoIdentite',
            'attestationIdentite' => 'setAttestationIdentite',
            'bourse' => 'setBourse',
            'attestationJDC' => 'setAttestationJDC',
            'attestationReusite' => 'setAttestationReusite'
        ];

        foreach ($booleanFields as $dataKey => $method) {
            if (isset($data[$dataKey])) {
                if ($data[$dataKey] === "oui" || $data[$dataKey] === "true" || $data[$dataKey] === true) {
                    $infoEleve->$method(true);
                } else {
                    $infoEleve->$method(false);
                }
            }
        }

        // Gestion de la classe
        if (isset($data['classe']) && $data['classe'] !== "") {
            $classe = $this->classeRepository->findOneBy(['label' => $data['classe']]);
            if ($classe) {
                $infoEleve->setClasse($classe);
            }
        }

        // Gestion des fichiers uploadés
        $this->handleFileUploads($data, $infoEleve);

        // Gestion des entités liées
        $this->handleRepresentantLegal($data, $infoEleve, 1);
        $this->handleRepresentantLegal($data, $infoEleve, 2);
        $this->handleMedecinTraitant($data, $infoEleve);
        $this->handleResponsableFinancier($data, $infoEleve);
        $this->handleSecuSociale($data, $infoEleve);
        $this->handleAssureur($data, $infoEleve);
        $this->handleAdhesion($data, $infoEleve);
    }

    /**
     * NOUVELLE MÉTHODE - Gestion améliorée de ScolariteAnterieur basée sur l'ancien contrôleur
     */
    private function handleScolariteAnterieur(array $data, InfoEleve $infoEleve): void
    {
        // Gestion ScolariteAnterieur Un
        $needsAnneeUn = isset($data['etablissementPrecedent1']) || isset($data['classePrecedente1']) || 
                       isset($data['anneeScolairePrecedente1']) || isset($data['lv1-1']) || 
                       isset($data['lv2-1']) || isset($data['option-1']);

        if ($needsAnneeUn) {
            $anneScolaireUn = $infoEleve->getAnneScolaireUn();
            if (!($anneScolaireUn instanceof ScolariteAnterieur)) {
                $anneScolaireUn = new ScolariteAnterieur();
                $this->entityManager->persist($anneScolaireUn);
                $infoEleve->setAnneScolaireUn($anneScolaireUn);
            }

            if (isset($data['etablissementPrecedent1']) && $data['etablissementPrecedent1'] !== "") {
                $anneScolaireUn->setEtablissement($data['etablissementPrecedent1']);
            }
            if (isset($data['classePrecedente1']) && $data['classePrecedente1'] !== "") {
                $anneScolaireUn->setClasse($data['classePrecedente1']);
            }
            if (isset($data['anneeScolairePrecedente1']) && $data['anneeScolairePrecedente1'] !== "") {
                $anneScolaireUn->setAnneScolaire($data['anneeScolairePrecedente1']);
            }
            if (isset($data['lv1-1']) && $data['lv1-1'] !== "") {
                $anneScolaireUn->setLVUn($data['lv1-1']);
            }
            if (isset($data['lv2-1']) && $data['lv2-1'] !== "") {
                $anneScolaireUn->setLVDeux($data['lv2-1']);
            }
            if (isset($data['option-1']) && $data['option-1'] !== "") {
                $anneScolaireUn->setOption($data['option-1']);
            }
        }

        // Gestion ScolariteAnterieur Deux
        $needsAnneeDeux = isset($data['etablissementPrecedent2']) || isset($data['classePrecedente2']) || 
                         isset($data['anneeScolairePrecedente2']) || isset($data['lv1-2']) || 
                         isset($data['lv2-2']) || isset($data['option-2']);

        if ($needsAnneeDeux) {
            $anneScolaireDeux = $infoEleve->getAnneScolaireDeux();
            if (!($anneScolaireDeux instanceof ScolariteAnterieur)) {
                $anneScolaireDeux = new ScolariteAnterieur();
                $this->entityManager->persist($anneScolaireDeux);
                $infoEleve->setAnneScolaireDeux($anneScolaireDeux);
            }

            if (isset($data['etablissementPrecedent2']) && $data['etablissementPrecedent2'] !== "") {
                $anneScolaireDeux->setEtablissement($data['etablissementPrecedent2']);
            }
            if (isset($data['classePrecedente2']) && $data['classePrecedente2'] !== "") {
                $anneScolaireDeux->setClasse($data['classePrecedente2']);
            }
            if (isset($data['anneeScolairePrecedente2']) && $data['anneeScolairePrecedente2'] !== "") {
                $anneScolaireDeux->setAnneScolaire($data['anneeScolairePrecedente2']);
            }
            if (isset($data['lv1-2']) && $data['lv1-2'] !== "") {
                $anneScolaireDeux->setLVUn($data['lv1-2']);
            }
            if (isset($data['lv2-2']) && $data['lv2-2'] !== "") {
                $anneScolaireDeux->setLVDeux($data['lv2-2']);
            }
            if (isset($data['option-2']) && $data['option-2'] !== "") {
                $anneScolaireDeux->setOption($data['option-2']);
            }
        }
    }

    /**
     * Gestion des fichiers uploadés
     */
    private function handleFileUploads(array $data, InfoEleve $infoEleve): void
    {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/inscriptions/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileFields = [
            'photoIdentiteFile',
            'carteVitaleFile', 
            'attestationIdentiteFile',
            'bourseFile',
            'attestationJDCFile',
            'attestationReusiteFile',
            'certificatMedicalFile'
        ];

        foreach ($fileFields as $fieldName) {
            if (isset($data[$fieldName]) && $data[$fieldName] instanceof File) {
                try {
                    $file = $data[$fieldName];
                    $fileName = uniqid() . '.' . $file->guessExtension();
                    $file->move($uploadDir, $fileName);
                    
                    $methodName = 'set' . str_replace('File', 'FileName', ucfirst($fieldName));
                    if (method_exists($infoEleve, $methodName)) {
                        $infoEleve->$methodName($fileName);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors de l\'upload du fichier ' . $fieldName, [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Gestion des représentants légaux - VERSION AMÉLIORÉE
     */
    private function handleRepresentantLegal(array $data, InfoEleve $infoEleve, int $number): void
    {
        $prefix = "representantLegal{$number}";
        
        // Vérifier si on a des données pour ce représentant
        $hasData = false;
        foreach ($data as $key => $value) {
            if (str_starts_with($key, $prefix) && $value !== "") {
                $hasData = true;
                break;
            }
        }

        // Gestion spéciale pour le cas où l'étudiant est son propre représentant légal
        if (isset($data['student-legal']) && $data['student-legal'] === "true" && $number === 1) {
            $hasData = true;
        }

        if (!$hasData) {
            return;
        }
        
        $representant = $number === 1 ? $infoEleve->getResponsableUn() : $infoEleve->getResponsableDeux();
        
        if (!($representant instanceof RepresentantLegal)) {
            $representant = new RepresentantLegal();
            $representant->setInfoEleve($infoEleve);
            
            // Si c'est le premier représentant et que l'étudiant est majeur, utiliser ses données
            if ($number === 1 && isset($data['student-legal']) && $data['student-legal'] === "true") {
                $user = $this->getUser();
                $representant->setNom($user->getNom());
                $representant->setPrenom($user->getPrenom());
            }
            
            $this->entityManager->persist($representant);
            
            if ($number === 1) {
                $infoEleve->setResponsableUn($representant);
            } else {
                $infoEleve->setResponsableDeux($representant);
            }
        }
        
        // Mapping des champs - AMÉLIORATION avec vérification comme dans l'ancien code
        $fieldMappings = [
            "{$prefix}Nom" => 'setNom',
            "{$prefix}Prenom" => 'setPrenom',
            "{$prefix}Email" => 'setCourriel',
            "{$prefix}Telephone" => 'setTelephonePerso',
            "{$prefix}TelephoneFixe" => 'setTelephoneFixe',
            "{$prefix}TelephonePro" => 'setTelephonePro',
            "{$prefix}Adresse" => 'setAdresse',
            "{$prefix}CodePostal" => 'setCodePostal',
            "{$prefix}Commune" => 'setCommune',
            "{$prefix}LienEleve" => 'setLienEleve',
            "{$prefix}Poste" => 'setPoste',
            "{$prefix}NomEmployeur" => 'setNomEmployeur',
            "{$prefix}AdresseEmployeur" => 'setAdresseEmployeur'
        ];

        // Mappings spéciaux pour le cas étudiant majeur
        if ($number === 1 && isset($data['student-legal']) && $data['student-legal'] === "true") {
            $specialMappings = [
                'resp-codepostal' => 'setCodePostal',
                'resp-email' => 'setCourriel',
                'resp-commune' => 'setCommune',
                'resp-adresse' => 'setAdresse',
                'resp-phone' => 'setTelephonePerso',
                'resp-phone-dom' => 'setTelephoneFixe'
            ];

            foreach ($specialMappings as $dataKey => $method) {
                if (isset($data[$dataKey]) && $data[$dataKey] !== "") {
                    $representant->$method($data[$dataKey]);
                }
            }

            // Gestion spéciale pour resp-comm (booléen)
            if (isset($data['resp-comm']) && $data['resp-comm'] !== "") {
                if ($data['resp-comm'] === "oui") {
                    $representant->setComAddrAsso(true);
                } else {
                    $representant->setComAddrAsso(false);
                }
            }
        }

        // Mappings normaux
        foreach ($fieldMappings as $dataKey => $method) {
            if (isset($data[$dataKey]) && $data[$dataKey] !== "") {
                $representant->$method($data[$dataKey]);
            }
        }
    }

    // Les autres méthodes handleXXX restent identiques...
    private function handleMedecinTraitant(array $data, InfoEleve $infoEleve): void
    {
        if (!isset($data['medecinTraitantNom']) || $data['medecinTraitantNom'] === "") {
            return;
        }
        
        $medecin = $infoEleve->getMedecinTraitant();
        if (!$medecin) {
            $medecin = new MedecinTraitant();
            $infoEleve->setMedecinTraitant($medecin);
        }
        
        if (isset($data['medecinTraitantNom']) && $data['medecinTraitantNom'] !== "") {
            $medecin->setNom($data['medecinTraitantNom']);
        }
        if (isset($data['medecinTraitantTelephone']) && $data['medecinTraitantTelephone'] !== "") {
            $medecin->setNumero($data['medecinTraitantTelephone']);
        }
        if (isset($data['medecinTraitantAdresse']) && $data['medecinTraitantAdresse'] !== "") {
            $medecin->setAdresse($data['medecinTraitantAdresse']);
        }
        
        $this->entityManager->persist($medecin);
    }

    private function handleResponsableFinancier(array $data, InfoEleve $infoEleve): void
    {
        if (!isset($data['responsableFinancierNom']) || $data['responsableFinancierNom'] === "") {
            return;
        }
        
        $responsableFinancier = $infoEleve->getResponsableFinancier();
        if (!$responsableFinancier) {
            $responsableFinancier = new ResposableFinancier();
            $infoEleve->setResponsableFinancier($responsableFinancier);
        }
        
        $fields = [
            'responsableFinancierNom' => 'setNom',
            'responsableFinancierPrenom' => 'setPrenom',
            'responsableFinancierNomEmployeur' => 'setNomEmployeur',
            'responsableFinancierAdresseEmployeur' => 'setAdresseEmployeur'
        ];

        foreach ($fields as $dataKey => $method) {
            if (isset($data[$dataKey]) && $data[$dataKey] !== "") {
                $responsableFinancier->$method($data[$dataKey]);
            }
        }
        
        $this->entityManager->persist($responsableFinancier);
    }

    private function handleSecuSociale(array $data, InfoEleve $infoEleve): void
    {
        if (!isset($data['secuSocialeNom']) || $data['secuSocialeNom'] === "") {
            return;
        }
        
        $secuSociale = $infoEleve->getSecuSociale();
        if (!$secuSociale) {
            $secuSociale = new CentreSecuriteSociale();
            $infoEleve->setSecuSociale($secuSociale);
        }
        
        if (isset($data['secuSocialeNom']) && $data['secuSocialeNom'] !== "") {
            $secuSociale->setNom($data['secuSocialeNom']);
        }
        if (isset($data['secuSocialeAdresse']) && $data['secuSocialeAdresse'] !== "") {
            $secuSociale->setAddresse($data['secuSocialeAdresse']);
        }
        
        $this->entityManager->persist($secuSociale);
    }

    private function handleAssureur(array $data, InfoEleve $infoEleve): void
    {
        if (!isset($data['assureurNom']) || $data['assureurNom'] === "") {
            return;
        }
        
        $assureur = $infoEleve->getAssureur();
        if (!$assureur) {
            $assureur = new AssuranceScolaire();
            $infoEleve->setAssureur($assureur);
        }
        
        $fields = [
            'assureurNom' => 'setNom',
            'assureurAdresse' => 'setAddresse',
            'assureurNumeroAssurance' => 'setNumeroAssurance'
        ];

        foreach ($fields as $dataKey => $method) {
            if (isset($data[$dataKey]) && $data[$dataKey] !== "") {
                $assureur->$method($data[$dataKey]);
            }
        }
        
        $this->entityManager->persist($assureur);
    }

    private function handleAdhesion(array $data, InfoEleve $infoEleve): void
    {
        $adhesion = $infoEleve->getAdhesion();
        if (!$adhesion) {
            $adhesion = new Adhesion();
            $infoEleve->setAdhesion($adhesion);
        }
        
        if (isset($data['adhesionAccepted'])) {
            $adhesion->setAccepted($data['adhesionAccepted'] === "oui" || $data['adhesionAccepted'] === true);
        }
        if (isset($data['adhesionPaymentMethod']) && $data['adhesionPaymentMethod'] !== "") {
            $adhesion->setPaymentMethod($data['adhesionPaymentMethod']);
        }
        if (isset($data['adhesionImageRights'])) {
            $adhesion->setImageRights($data['adhesionImageRights'] === "oui" || $data['adhesionImageRights'] === true);
        }
        
        $this->entityManager->persist($adhesion);
    }

    /**
     * Chargement sécurisé des données dans le flow
     */
    private function loadExistingDataIntoFlow(User $user, InscriptionFlow $flow): void
    {
        $infoEleve = $this->infoEleveRepository->findOneBy(['user' => $user]);
        if (!$infoEleve) {
            return;
        }

        $data = $this->convertEntityToArray($user, $infoEleve);
        
        // Nettoyer complètement les données de fichiers
        $cleanData = $this->cleanDataForFlow($data);
        
        // CORRECTION : Convertir les dates string vers DateTime AVANT de passer au flow
        $this->convertDatesForFlow($cleanData);
        
        $flow->setFormData($cleanData);
    }

    /**
     * NOUVELLE MÉTHODE : Conversion des dates pour le flow
     */
    private function convertDatesForFlow(array &$data): void
    {
        // Conversion de la date de naissance
        if (isset($data['dateNaissance']) && is_string($data['dateNaissance']) && !empty($data['dateNaissance'])) {
            try {
                $data['dateNaissance'] = \DateTime::createFromFormat('Y-m-d', $data['dateNaissance']);
                if ($data['dateNaissance'] === false) {
                    // Si le format Y-m-d échoue, essayer d'autres formats
                    $data['dateNaissance'] = new \DateTime($data['dateNaissance']);
                }
            } catch (\Exception $e) {
                $this->logger->warning('Date de naissance invalide lors du chargement : ' . $data['dateNaissance']);
                unset($data['dateNaissance']); // Supprimer la date invalide
            }
        }

        // Conversion de la date de rappel antitétanique
        if (isset($data['dernierRappelAntitetanique']) && is_string($data['dernierRappelAntitetanique']) && !empty($data['dernierRappelAntitetanique'])) {
            try {
                $data['dernierRappelAntitetanique'] = \DateTime::createFromFormat('Y-m-d', $data['dernierRappelAntitetanique']);
                if ($data['dernierRappelAntitetanique'] === false) {
                    $data['dernierRappelAntitetanique'] = new \DateTime($data['dernierRappelAntitetanique']);
                }
            } catch (\Exception $e) {
                $this->logger->warning('Date rappel antitétanique invalide lors du chargement : ' . $data['dernierRappelAntitetanique']);
                unset($data['dernierRappelAntitetanique']);
            }
        }
    }

    /**
     * Nettoyage complet des données pour le flow
     */
    private function cleanDataForFlow(array $data): array
    {
        // Liste de tous les champs de fichiers possibles
        $fileFields = [
            'photoIdentiteFile',
            'carteVitaleFile',
            'attestationIdentiteFile',
            'bourseFile',
            'attestationJDCFile',
            'attestationReusiteFile',
            'certificatMedicalFile'
        ];

        // Supprimer tous les champs de fichiers
        foreach ($fileFields as $field) {
            unset($data[$field]);
        }

        // Supprimer les objets File qui pourraient subsister
        $data = array_filter($data, function($value) {
            return !($value instanceof File);
        });

        return $data;
    }

    /**
     * Conversion complète de l'entité vers un tableau
     */
    private function convertEntityToArray(User $user, InfoEleve $infoEleve): array
    {
        $data = [];

        // Données utilisateur
        $data['nom'] = $user->getNom();
        $data['prenom'] = $user->getPrenom();
        $data['email'] = $user->getEmail();

        // CORRECTION : Conserver les dates comme DateTime si elles existent, sinon null
        $data['dateNaissance'] = $infoEleve->getDateDeNaissance(); // Garder comme DateTime
        $data['dernierRappelAntitetanique'] = $infoEleve->getDernierRappelAntitetanique(); // Garder comme DateTime

        // Données de base InfoEleve
        $data['promotion'] = $infoEleve->getPromotion();
        $data['numeroMobile'] = $infoEleve->getNumeroMobile();
        $data['nationalite'] = $infoEleve->getNationalite();
        $data['departement'] = $infoEleve->getDepartement();
        $data['communeNaissance'] = $infoEleve->getCommuneNaissance();
        $data['nomContacteUrgence'] = $infoEleve->getNomContacteUrgence();
        $data['numeroContacteUrgence'] = $infoEleve->getNumeroContacteUrgence();
        $data['dernierDiplome'] = $infoEleve->getDernierDiplome();
        $data['immatriculationVeic'] = $infoEleve->getImmattriculationVeic();
        $data['numSecuSocial'] = $infoEleve->getNumSecuSocial();
        $data['transportScolaire'] = $infoEleve->getTransportScolaire();
        $data['lvUn'] = $infoEleve->getLVUn();
        $data['lvDeux'] = $infoEleve->getLVDeux();
        $data['sexe'] = $infoEleve->getSexe();
        $data['regime'] = $infoEleve->getRegime();
        $data['observations'] = $infoEleve->getObservations();

        // Données booléennes
        $data['cheque'] = $infoEleve->isCheque();
        $data['droitImage'] = $infoEleve->isDroitImage();
        $data['redoublant'] = $infoEleve->isRedoublant();
        $data['carteVitale'] = $infoEleve->getCarteVitale();
        $data['photoIdentite'] = $infoEleve->getPhotoIdentite();
        $data['attestationIdentite'] = $infoEleve->getAttestationIdentite();
        $data['bourse'] = $infoEleve->getBourse();
        $data['attestationJDC'] = $infoEleve->getAttestationJDC();
        $data['attestationReusite'] = $infoEleve->getAttestationReusite();

        // Classe
        if ($infoEleve->getClasse()) {
            $data['classe'] = $infoEleve->getClasse()->getLabel(); // Correction : récupérer le label
        }

        // Noms des fichiers (pas les objets File)
        $data['photoIdentiteFileName'] = $infoEleve->getPhotoIdentite();
        $data['carteVitaleFileName'] = $infoEleve->getCarteVitale();
        $data['attestationIdentiteFileName'] = $infoEleve->getAttestationIdentite();
        $data['bourseFileName'] = $infoEleve->getBourse();
        $data['attestationJDCFileName'] = $infoEleve->getAttestationJDC();
        $data['attestationReusiteFileName'] = $infoEleve->getAttestationReusite();

        // Scolarité antérieure
        $this->convertScolariteAnterieurToArray($infoEleve, $data);

        // Représentants légaux
        $this->convertRepresentantLegalToArray($infoEleve->getResponsableUn(), $data, 1);
        $this->convertRepresentantLegalToArray($infoEleve->getResponsableDeux(), $data, 2);

        // Médecin traitant
        $this->convertMedecinTraitantToArray($infoEleve->getMedecinTraitant(), $data);

        // Responsable financier
        $this->convertResponsableFinancierToArray($infoEleve->getResponsableFinancier(), $data);

        // Sécurité sociale
        $this->convertSecuSocialeToArray($infoEleve->getSecuSociale(), $data);

        // Assureur
        $this->convertAssureurToArray($infoEleve->getAssureur(), $data);

        // Adhésion
        $this->convertAdhesionToArray($infoEleve->getAdhesion(), $data);

        return $data;
    }

    /**
     * Conversion de la scolarité antérieure vers tableau
     */
    private function convertScolariteAnterieurToArray(InfoEleve $infoEleve, array &$data): void
    {
        // Année scolaire un
        $anneeUn = $infoEleve->getAnneScolaireUn();
        if ($anneeUn instanceof ScolariteAnterieur) {
            $data['etablissementPrecedent1'] = $anneeUn->getEtablissement();
            $data['classePrecedente1'] = $anneeUn->getClasse();
            $data['anneeScolairePrecedente1'] = $anneeUn->getAnneScolaire();
            $data['lv1-1'] = $anneeUn->getLVUn();
            $data['lv2-1'] = $anneeUn->getLVDeux();
            $data['option-1'] = $anneeUn->getOption();
        }

        // Année scolaire deux
        $anneeDeux = $infoEleve->getAnneScolaireDeux();
        if ($anneeDeux instanceof ScolariteAnterieur) {
            $data['etablissementPrecedent2'] = $anneeDeux->getEtablissement();
            $data['classePrecedente2'] = $anneeDeux->getClasse();
            $data['anneeScolairePrecedente2'] = $anneeDeux->getAnneScolaire();
            $data['lv1-2'] = $anneeDeux->getLVUn();
            $data['lv2-2'] = $anneeDeux->getLVDeux();
            $data['option-2'] = $anneeDeux->getOption();
        }
    }


    /**
     * Conversion du représentant légal vers tableau
     */
    private function convertRepresentantLegalToArray(?RepresentantLegal $representant, array &$data, int $number): void
    {
        if (!$representant) {
            return;
        }

        $prefix = "representantLegal{$number}";
        
        $data["{$prefix}Nom"] = $representant->getNom();
        $data["{$prefix}Prenom"] = $representant->getPrenom();
        $data["{$prefix}Email"] = $representant->getCourriel();
        $data["{$prefix}Telephone"] = $representant->getTelephonePerso();
        $data["{$prefix}TelephoneFixe"] = $representant->getTelephoneFixe();
        $data["{$prefix}TelephonePro"] = $representant->getTelephonePro();
        $data["{$prefix}Adresse"] = $representant->getAdresse();
        $data["{$prefix}CodePostal"] = $representant->getCodePostal();
        $data["{$prefix}Commune"] = $representant->getCommune();
        $data["{$prefix}LienEleve"] = $representant->getLienEleve();
        $data["{$prefix}Poste"] = $representant->getPoste();
        $data["{$prefix}NomEmployeur"] = $representant->getNomEmployeur();
        $data["{$prefix}AdresseEmployeur"] = $representant->getAdresseEmployeur();

        // Champs spéciaux pour l'étudiant majeur (premier représentant seulement)
        if ($number === 1) {
            $data['resp-codepostal'] = $representant->getCodePostal();
            $data['resp-email'] = $representant->getCourriel();
            $data['resp-commune'] = $representant->getCommune();
            $data['resp-adresse'] = $representant->getAdresse();
            $data['resp-phone'] = $representant->getTelephonePerso();
            $data['resp-phone-dom'] = $representant->getTelephoneFixe();
            $data['resp-comm'] = $representant->getComAddrAsso() ? 'oui' : 'non';
        }
    }

    /**
     * Conversion du médecin traitant vers tableau
     */
    private function convertMedecinTraitantToArray(?MedecinTraitant $medecin, array &$data): void
    {
        if (!$medecin) {
            return;
        }

        $data['medecinTraitantNom'] = $medecin->getNom();
        $data['medecinTraitantTelephone'] = $medecin->getNumero();
        $data['medecinTraitantAdresse'] = $medecin->getAdresse();
    }

    /**
     * Conversion du responsable financier vers tableau
     */
    private function convertResponsableFinancierToArray(?ResposableFinancier $responsable, array &$data): void
    {
        if (!$responsable) {
            return;
        }

        $data['responsableFinancierNom'] = $responsable->getNom();
        $data['responsableFinancierPrenom'] = $responsable->getPrenom();
        $data['responsableFinancierNomEmployeur'] = $responsable->getNomEmployeur();
        $data['responsableFinancierAdresseEmployeur'] = $responsable->getAdresseEmployeur();
    }

    /**
     * Conversion de la sécurité sociale vers tableau
     */
    private function convertSecuSocialeToArray(?CentreSecuriteSociale $secuSociale, array &$data): void
    {
        if (!$secuSociale) {
            return;
        }

        $data['secuSocialeNom'] = $secuSociale->getNom();
        $data['secuSocialeAdresse'] = $secuSociale->getAddresse();
    }

    /**
     * Conversion de l'assureur vers tableau
     */
    private function convertAssureurToArray(?AssuranceScolaire $assureur, array &$data): void
    {
        if (!$assureur) {
            return;
        }

        $data['assureurNom'] = $assureur->getNom();
        $data['assureurAdresse'] = $assureur->getAddresse();
        $data['assureurNumeroAssurance'] = $assureur->getNumeroAssurance();
    }

    /**
     * Conversion de l'adhésion vers tableau
     */
    private function convertAdhesionToArray(?Adhesion $adhesion, array &$data): void
    {
        if (!$adhesion) {
            return;
        }

        $data['adhesionAccepted'] = $adhesion->isAccepted() ? 'oui' : 'non';
        $data['adhesionPaymentMethod'] = $adhesion->getPaymentMethod();
        $data['adhesionImageRights'] = $adhesion->getImageRights() ? 'oui' : 'non';
    }

    /**
     * Vérification de la complétude de l'inscription
     */
    private function isInscriptionComplete(InfoEleve $infoEleve): bool
    {
        // Vérifications de base
        $requiredFields = [
            $infoEleve->getDateDeNaissance(),
            $infoEleve->getPromotion(),
            $infoEleve->getNumeroMobile(),
            $infoEleve->getNationalite(),
            $infoEleve->getSexe(),
            $infoEleve->getClasse()
        ];

        foreach ($requiredFields as $field) {
            if (empty($field)) {
                return false;
            }
        }

        // Vérifier qu'au moins un représentant légal existe
        if (!$infoEleve->getResponsableUn() && !$infoEleve->getResponsableDeux()) {
            return false;
        }

        // Vérifier que l'adhésion est acceptée
        $adhesion = $infoEleve->getAdhesion();
        if (!$adhesion || !$adhesion->isAccepted()) {
            return false;
        }

        return true;
    }
}