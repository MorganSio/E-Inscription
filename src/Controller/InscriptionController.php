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
use App\Form\InscriptionType;
use App\Repository\InfoEleveRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InscriptionController extends AbstractController
{
    private const TOTAL_STEPS = 10;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InfoEleveRepository $infoEleveRepository,
        private readonly ClasseRepository $classeRepository,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $urlGenerator
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
    public function dashboard(DocumentManager $documentManager): Response
    {
        $user = $this->getUser();
        
        // Récupération de l'InfoEleve avec la classe
        $infoEleve = $this->infoEleveRepository->createQueryBuilder('i')
            ->leftJoin('i.classe', 'c')
            ->addSelect('c')
            ->where('i.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();

        $inscription = null;
        $isComplete = false;
        
        // Variables pour les documents
        $documentsStatus = [];
        $missingRequiredDocuments = [];
        $hasAllRequiredDocuments = false;

        if ($infoEleve) {
            // Traitement existant de l'inscription
            $inscription = $this->convertEntityToArray($user, $infoEleve);
            $this->prepareInscriptionData($infoEleve, $inscription);
            
            $document = $infoEleve;
            
            if ($infoEleve->getClasse()) {
                $inscription['classe'] = [
                    'id' => $infoEleve->getClasse()->getId(),
                    'label' => $infoEleve->getClasse()->getLabel()
                ];
            } else {
                $inscription['classe'] = null;
            }
            
            $inscription['isComplete'] = $this->isInscriptionComplete($infoEleve);
            $isComplete = $inscription['isComplete'];
            
            // Nouveau : Récupération du statut des documents
            $documentsStatus = $documentManager->getDocumentsStatus($infoEleve);
            $missingRequiredDocuments = $documentManager->getMissingRequiredDocuments($infoEleve);
            $hasAllRequiredDocuments = $documentManager->hasAllRequiredDocuments($infoEleve);
            
            // Ajout des informations documents dans le tableau inscription
            $inscription['documents'] = [
                'status' => $documentsStatus,
                'missingRequired' => $missingRequiredDocuments,
                'allRequiredUploaded' => $hasAllRequiredDocuments
            ];
        }

        return $this->render('inscription/dashboard.html.twig', [
            'user' => $user,
            'inscription' => $inscription,
            'isComplete' => $isComplete,
            'document' => $document,
            'documentManager' => $documentManager,
            'documentsStatus' => $documentsStatus,
            'missingRequiredDocuments' => $missingRequiredDocuments,
            'hasAllRequiredDocuments' => $hasAllRequiredDocuments,
        ]);
    }

    private function handleAjaxRequest(Request $request, FormInterface $form, User $user, int $step, InfoEleve $infoEleve): JsonResponse
    {
        try {
            // Log des données reçues pour debug
            $this->logger->info('Données AJAX reçues', [
                'step' => $step,
                'method' => $request->getMethod(),
                'content_type' => $request->headers->get('Content-Type'),
                'request_data' => $request->request->all(),
                'form_submitted' => $form->isSubmitted(),
                'form_valid' => $form->isSubmitted() ? $form->isValid() : false
            ]);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->logger->info('Traitement AJAX étape', [
                        'step' => $step,
                        'user_id' => $user->getId()
                    ]);

                    // Sauvegarde immédiate des données de l'étape courante
                    $formData = $form->getData();
                    
                    // Filtrer les données nulles et vides pour éviter les erreurs
                    $formData = $this->filterFormData($formData);
                    
                    // Log spécifique pour l'étape 4 et 5
                    if ($step === 4 || $step === 5) {
                        $prefix = $step === 4 ? 'representantLegal' : 'representantLegal2';
                        $this->logger->info("Données étape $step (Représentant légal)", [
                            'data_keys' => array_keys($formData),
                            'representant_nom' => $formData[$prefix . 'Nom'] ?? 'non défini',
                            'representant_prenom' => $formData[$prefix . 'Prenom'] ?? 'non défini',
                            'representant_adresse' => $formData[$prefix . 'Adresse'] ?? 'non défini',
                            'representant_lien' => $formData[$prefix . 'LienEleve'] ?? 'non défini'
                        ]);
                    }
                    
                    $this->saveStepDataToDatabase($user, $infoEleve, $formData, $step);

                    $transition = $request->request->get('flow_transition', 'next');

                    switch ($transition) {
                        case 'next':
                            if ($step < self::TOTAL_STEPS) {
                                return new JsonResponse([
                                    'success' => true,
                                    'redirect' => $this->urlGenerator->generate('app_inscription_form', ['step' => $step + 1])
                                ]);
                            }
                            break;

                        case 'previous':
                            if ($step > 1) {
                                return new JsonResponse([
                                    'success' => true,
                                    'redirect' => $this->urlGenerator->generate('app_inscription_form', ['step' => $step - 1])
                                ]);
                            }
                            break;

                        case 'finish':
                            if ($step === self::TOTAL_STEPS) {
                                // Marquer comme complète
                                $infoEleve->setInscriptionComplete(true);
                                $infoEleve->setDateInscription(new \DateTime());
                                $this->entityManager->flush();
                                
                                return new JsonResponse([
                                    'success' => true,
                                    'redirect' => $this->urlGenerator->generate('app_inscription_dashboard'),
                                    'message' => 'Inscription finalisée avec succès !'
                                ]);
                            }
                            break;
                    }

                    return new JsonResponse([
                        'success' => true,
                        'redirect' => $this->urlGenerator->generate('app_inscription_form', ['step' => $step])
                    ]);

                } catch (\Exception $e) {
                    $this->logger->error('Erreur AJAX étape ' . $step, [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'step' => $step,
                        'user_id' => $user->getId()
                    ]);
                    
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'Une erreur est survenue lors de la sauvegarde: ' . $e->getMessage()
                    ], 500);
                }
            } else {
                // 💥 Ajoute ceci :
                return new JsonResponse([
                    'success' => false,
                    'form_html' => $this->renderView('inscription/form.html.twig', [
                        'form' => $form->createView(),
                        'flow' => [
                            'currentStepNumber' => $step,
                            'currentStepLabel' => $this->getStepLabel($step),
                            'nextStepLabel' => $step < self::TOTAL_STEPS ? $this->getStepLabel($step + 1) : null,
                            'isFirstStep' => $step === 1,
                            'isLastStep' => $step === self::TOTAL_STEPS,
                            'totalSteps' => self::TOTAL_STEPS,
                        ],
                        'user' => $user,
                    ]),
                ]);
            }

            // Formulaire invalide - récupérer les erreurs détaillées
            if ($request->isMethod('POST')) {
                $errors = $this->getFormErrorsDetailed($form);
                
                $this->logger->warning('Formulaire invalide étape ' . $step, [
                    'errors' => $errors,
                    'step' => $step,
                    'form_data' => $form->getData(),
                    'request_data' => $request->request->all()
                ]);
                
                return new JsonResponse([
                    'success' => false,
                    'errors' => $errors,
                    'message' => 'Veuillez corriger les erreurs dans le formulaire.',
                    'debug_data' => [
                        'form_data' => $form->getData(),
                        'request_data' => $request->request->all()
                    ]
                ], 422);
            }

            return new JsonResponse(['success' => false, 'error' => 'Requête invalide'], 400);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur critique dans handleAjaxRequest', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'step' => $step,
                'user_id' => $user->getId()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

    private function filterFormData(array $data): array
    {
        $filteredData = [];
        
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '' && $value !== []) {
                // Nettoyer les chaînes
                if (is_string($value)) {
                    $cleanValue = trim($value);
                    if ($cleanValue !== '') {
                        $filteredData[$key] = $cleanValue;
                    }
                } elseif (is_array($value)) {
                    // Gérer les tableaux (comme les choix de formulaire)
                    if (!empty($value)) {
                        $filteredData[$key] = $value;
                    }
                } else {
                    $filteredData[$key] = $value;
                }
            }
        }
        
        return $filteredData;
    }

    /**
     * Récupère ou crée InfoEleve pour l'utilisateur
     */
    private function getOrCreateInfoEleve(User $user): InfoEleve
    {
        $infoEleve = $user->getInfoEleve();
        
        if (!$infoEleve) {
            $infoEleve = new InfoEleve($user);
            $user->setInfoEleve($infoEleve);
            $this->entityManager->persist($infoEleve);
            $this->entityManager->flush();
            
            $this->logger->info('Nouveau InfoEleve créé', [
                'user_id' => $user->getId(),
                'info_eleve_id' => $infoEleve->getId()
            ]);
        }
        
        return $infoEleve;
    }

    /**
     * Récupère les données depuis la base de données
     */
    private function getInscriptionDataFromDatabase(User $user, InfoEleve $infoEleve): array
    {
        $data = $this->convertEntityToArray($user, $infoEleve);
        $this->prepareInscriptionData($infoEleve, $data);
    
        // if ($infoEleve->getClasse()) {
        //     $data['classe'] = $infoEleve->getClasse(); 
        // } else {
        //     $data['classe'] = null; 
        // }

        // Pour le formulaire, on passe l'entité Classe (ou null)
        $data['classe'] = $infoEleve->getClasse() ?: null;

        return $data;
    }

    private function mapStepDataToEntity(User $user, InfoEleve $infoEleve, array $data, int $step): void
    {
        try {
            // Vérifier que les données ne sont pas nulles
            if (empty($data)) {
                $this->logger->warning('Données vides pour l\'étape', ['step' => $step]);
                return;
            }

            switch ($step) {
                case 1: // Informations personnelles
                    $this->mapStep1Data($user, $infoEleve, $data);
                    break;
                    
                case 2: // Contact et urgence
                    $this->mapStep2Data($infoEleve, $data);
                    break;
                    
                case 3: // Informations scolaires
                    $this->mapStep3Data($infoEleve, $data);
                    break;
                    
                case 4: // Représentant légal 1
                    $this->mapStep4Data($infoEleve, $data);
                    break;
                    
                case 5: // Représentant légal 2
                    $this->mapStep5Data($infoEleve, $data);
                    break;
                    
                case 6: // Scolarité antérieure
                    $this->mapStep6Data($infoEleve, $data);
                    break;
                    
                case 7: // Informations médicales
                    $this->mapStep7Data($infoEleve, $data);
                    break;
                    
                case 8: // Responsable financier
                    $this->mapStep8Data($infoEleve, $data);
                    break;
                    
                case 9: // Documents à fournir
                    $this->mapStep9Data($infoEleve, $data);
                    break;
                    
                case 10: // Finalisation et adhésion
                    $this->mapStep10Data($infoEleve, $data);
                    break;
                    
                default:
                    throw new \InvalidArgumentException('Étape inconnue: ' . $step);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur dans mapStepDataToEntity', [
                'step' => $step,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function mapStep1Data(User $user, InfoEleve $infoEleve, array $data): void
    {
        // Informations utilisateur
        if (isset($data['nom']) && !empty($data['nom'])) $user->setNom(trim($data['nom']));
        if (isset($data['prenom']) && !empty($data['prenom'])) $user->setPrenom(trim($data['prenom']));
        if (isset($data['email']) && !empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $user->setEmail($data['email']);
        }
        
        // Informations personnelles élève
        if (isset($data['dateNaissance'])) {
            $this->setDateNaissance($infoEleve, $data['dateNaissance']);
        }
        if (isset($data['sexe']) && !empty($data['sexe'])) $infoEleve->setSexe($data['sexe']);
        if (isset($data['nationalite']) && !empty($data['nationalite'])) $infoEleve->setNationalite($data['nationalite']);
        if (isset($data['departement']) && !empty($data['departement'])) $infoEleve->setDepartement($data['departement']);
        if (isset($data['communeNaissance']) && !empty($data['communeNaissance'])) $infoEleve->setCommuneNaissance($data['communeNaissance']);
        if (isset($data['numSecuSocial']) && !empty($data['numSecuSocial'])) $infoEleve->setNumSecuSocial($data['numSecuSocial']);
    }

    private function mapStep2Data(InfoEleve $infoEleve, array $data): void
    {
        if (isset($data['numeroMobile']) && !empty($data['numeroMobile'])) {
            $infoEleve->setNumeroMobile($data['numeroMobile']);
        }
        if (isset($data['nomContacteUrgence']) && !empty($data['nomContacteUrgence'])) {
            $infoEleve->setNomContacteUrgence($data['nomContacteUrgence']);
        }
        if (isset($data['numeroContacteUrgence']) && !empty($data['numeroContacteUrgence'])) {
            $infoEleve->setNumeroContacteUrgence($data['numeroContacteUrgence']);
        }
        // Toujours setter la valeur, même si la case n'est pas cochée
        $infoEleve->setSmsSend(!empty($data['accepterSms']));
    }

    private function mapStep3Data(InfoEleve $infoEleve, array $data): void
    {
        try {
            $this->logger->info('Début mapStep3Data', [
                'data_keys' => array_keys($data),
                'classe_value' => $data['classe'] ?? 'non définie'
            ]);

            // if (isset($data['classe']) && !empty($data['classe'])) {
            //     $classe = $this->findClasse($data['classe']);
            //     if ($classe) {
            //         $infoEleve->setClasse($classe);
            //         $this->logger->info('Classe trouvée et assignée', [
            //             'classe_id' => $classe->getId(),
            //             'classe_label' => $classe->getLabel()
            //         ]);
            //     } else {
            //         $this->logger->warning('Classe non trouvée', [
            //             'classe_recherchee' => $data['classe']
            //         ]);
            //     }
            // }

            if (isset($data['promotion']) && !empty($data['promotion'])) $infoEleve->setPromotion($data['promotion']);
            if (isset($data['regime']) && !empty($data['regime'])) $infoEleve->setRegime($data['regime']);
            if (isset($data['lvUn']) && !empty($data['lvUn'])) $infoEleve->setLVUn($data['lvUn']);
            if (isset($data['lvDeux']) && !empty($data['lvDeux'])) $infoEleve->setLVDeux($data['lvDeux']);
            if (isset($data['redoublant'])) $infoEleve->setRedoublant((bool)$data['redoublant']);
            if (isset($data['dernierDiplome']) && !empty($data['dernierDiplome'])) $infoEleve->setDernierDiplome($data['dernierDiplome']);
            if (isset($data['immatriculationVeic']) && !empty($data['immatriculationVeic'])) $infoEleve->setImmattriculationVeic($data['immatriculationVeic']);
            if (isset($data['classe']) && $data['classe'] instanceof \App\Entity\Classe) {
                $infoEleve->setClasse($data['classe']);
            }
            if (isset($data['transportScolaire'])) {
                $infoEleve->setTransportScolaire($data['transportScolaire']);
            }
            $this->logger->info('Fin mapStep3Data - succès');
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur dans mapStep3Data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => array_keys($data)
            ]);
            throw $e;
        }
    }

    private function mapStep4Data(InfoEleve $infoEleve, array $data): void
    {
        $this->handleRepresentantLegal($data, $infoEleve, 1);
    }

    private function mapStep5Data(InfoEleve $infoEleve, array $data): void
    {
        $this->handleRepresentantLegal($data, $infoEleve, 2);
    }

    // Correction dans mapStep6Data - Utiliser les bons noms de champs du formulaire
    private function mapStep6Data(InfoEleve $infoEleve, array $data): void
    {
        try {
            $this->logger->info('Traitement scolarité antérieure', [
                'data_keys' => array_keys($data),
                // CORRECTION : Utiliser les vrais noms de champs du formulaire
                'etablissement_precedent_1' => $data['etablissementPrecedent1'] ?? 'non défini',
                'etablissement_precedent_2' => $data['etablissementPrecedent2'] ?? 'non défini'
            ]);

            // CORRECTION : Gestion année scolaire 1 avec les vrais noms de champs
            $this->handleScolariteAnneeCorrect($data, $infoEleve, 1);
            
            // CORRECTION : Gestion année scolaire 2 avec les vrais noms de champs
            $this->handleScolariteAnneeCorrect($data, $infoEleve, 2);
            
            $this->logger->info('Scolarité antérieure traitée avec succès');
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement scolarité antérieure', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data_keys' => array_keys($data)
            ]);
            throw $e;
        }
    }

    // NOUVELLE MÉTHODE pour gérer correctement les scolarités antérieures
    private function handleScolariteAnneeCorrect(array $data, InfoEleve $infoEleve, int $annee): void
    {
        try {
            // CORRECTION : Utiliser les vrais noms de champs du formulaire HTML
            $etablissementField = 'etablissementPrecedent' . $annee;
            $classeField = 'classePrecedente' . $annee;
            $anneeField = 'anneeScolairePrecedente' . $annee;
            $optionField = 'optionPrecedente' . $annee;
            $lvUnField = 'lvUnPrecedente' . $annee;
            $lvDeuxField = 'lvDeuxPrecedente' . $annee;

            $this->logger->info("Traitement scolarité année $annee", [
                'etablissement_field' => $etablissementField,
                'etablissement_value' => $data[$etablissementField] ?? 'non défini'
            ]);

            // Vérifier si nous avons au moins un établissement
            if (!isset($data[$etablissementField]) || empty(trim($data[$etablissementField]))) {
                $this->logger->info("Pas d'établissement pour année $annee, pas de création/mise à jour");
                return;
            }

            // Récupérer ou créer la scolarité selon l'année
            $scolarite = $annee === 1 ? 
                $infoEleve->getAnneScolaireUn() : 
                $infoEleve->getAnneScolaireDeux();

            if (!$scolarite) {
                $scolarite = new ScolariteAnterieur();
                $this->entityManager->persist($scolarite);
                
                if ($annee === 1) {
                    $infoEleve->setAnneScolaireUn($scolarite);
                } else {
                    $infoEleve->setAnneScolaireDeux($scolarite);
                }
                
                $this->logger->info("Nouvelle scolarité année $annee créée");
            }

            // CORRECTION : Mapper avec les vrais noms de champs
            if (isset($data[$etablissementField]) && !empty($data[$etablissementField])) {
                $scolarite->setEtablissement(trim($data[$etablissementField]));
            }

            if (isset($data[$classeField]) && !empty($data[$classeField])) {
                $scolarite->setClasse(trim($data[$classeField]));
            }

            if (isset($data[$anneeField]) && !empty($data[$anneeField])) {
                $scolarite->setAnneScolaire(trim($data[$anneeField]));
            }

            if (isset($data[$optionField]) && !empty($data[$optionField])) {
                $scolarite->setOption((bool)$data[$optionField]);
            }

            if (isset($data[$lvUnField]) && !empty($data[$lvUnField])) {
                $scolarite->setLVUn(trim($data[$lvUnField]));
            }

            if (isset($data[$lvDeuxField]) && !empty($data[$lvDeuxField])) {
                $scolarite->setLVDeux(trim($data[$lvDeuxField]));
            }
            
            $this->logger->info("Scolarité année $annee traitée avec succès", [
                'id' => $scolarite->getId(),
                'etablissement' => $scolarite->getEtablissement(),
                'classe' => $scolarite->getClasse(),
                'annee_scolaire' => $scolarite->getAnneScolaire(),
                'option' => $scolarite->getOption(),
                'lv_un' => $scolarite->getLVUn(),
                'lv_deux' => $scolarite->getLVDeux()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors du traitement scolarité année $annee", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data_keys' => array_keys($data)
            ]);
            throw $e;
        }
    }

    // Correction dans mapStep7Data - Ajouter les champs manquants
    private function mapStep7Data(InfoEleve $infoEleve, array $data): void
    {
        // Médecin traitant
        if (isset($data['medecinTraitantNom']) && !empty($data['medecinTraitantNom'])) {
            $medecin = $infoEleve->getMedecinTraitant() ?: new MedecinTraitant();
            $medecin->setNom($data['medecinTraitantNom']);
            if (isset($data['medecinTraitantTelephone']) && !empty($data['medecinTraitantTelephone'])) {
                $medecin->setNumero($data['medecinTraitantTelephone']);
            }
            if (isset($data['medecinTraitantAdresse']) && !empty($data['medecinTraitantAdresse'])) {
                $medecin->setAdresse($data['medecinTraitantAdresse']);
            }
            
            if (!$infoEleve->getMedecinTraitant()) {
                $infoEleve->setMedecinTraitant($medecin);
                $this->entityManager->persist($medecin);
            }
        }

        if (isset($data['dernierRappelAntitetanique'])) {
            $this->setDateRappelAntitetanique($infoEleve, $data['dernierRappelAntitetanique']);
        }
        
        if (isset($data['observations']) && !empty($data['observations'])) {
            $infoEleve->setObservations(trim($data['observations']));
        }

        // Sécurité sociale
        if (isset($data['secuSocialeNom']) && !empty($data['secuSocialeNom'])) {
            $secuSociale = $infoEleve->getSecuSociale() ?: new CentreSecuriteSociale();
            $secuSociale->setNom($data['secuSocialeNom']);
            if (isset($data['secuSocialeAdresse']) && !empty($data['secuSocialeAdresse'])) {
                $secuSociale->setAddresse($data['secuSocialeAdresse']);
            }
            
            if (!$infoEleve->getSecuSociale()) {
                $infoEleve->setSecuSociale($secuSociale);
                $this->entityManager->persist($secuSociale);
            }
        }

        // Assureur
        if (isset($data['assureurNom']) && !empty($data['assureurNom'])) {
            $assureur = $infoEleve->getAssureur() ?: new AssuranceScolaire();
            $assureur->setNom($data['assureurNom']);
            if (isset($data['assureurAdresse']) && !empty($data['assureurAdresse'])) {
                $assureur->setAddresse($data['assureurAdresse']);
            }
            if (isset($data['assureurNumeroAssurance']) && !empty($data['assureurNumeroAssurance'])) {
                $assureur->setNumeroAssurance($data['assureurNumeroAssurance']);
            }
            
            if (!$infoEleve->getAssureur()) {
                $infoEleve->setAssureur($assureur);
                $this->entityManager->persist($assureur);
            }
        }
    }

    // NOUVELLE MÉTHODE pour gérer la date du rappel antitétanique
    private function setDateRappelAntitetanique(InfoEleve $infoEleve, $dateValue): void
    {
        try {
            if ($dateValue instanceof \DateTime) {
                $infoEleve->setDernierRappelAntitetanique($dateValue);
            } elseif (is_string($dateValue) && !empty($dateValue)) {
                $date = new \DateTime($dateValue);
                $infoEleve->setDernierRappelAntitetanique($date);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la définition de la date du rappel antitétanique', [
                'date_value' => $dateValue,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function mapStep8Data(InfoEleve $infoEleve, array $data): void
    {
        if (isset($data['responsableFinancierNom']) && !empty($data['responsableFinancierNom'])) {
            $responsable = $infoEleve->getResponsableFinancier() ?: new ResposableFinancier();
            $responsable->setNom($data['responsableFinancierNom']);
            if (isset($data['responsableFinancierPrenom']) && !empty($data['responsableFinancierPrenom'])) {
                $responsable->setPrenom($data['responsableFinancierPrenom']);
            }
            if (isset($data['responsableFinancierNomEmployeur']) && !empty($data['responsableFinancierNomEmployeur'])) {
                $responsable->setNomEmployeur($data['responsableFinancierNomEmployeur']);
            }
            if (isset($data['responsableFinancierAdresseEmployeur']) && !empty($data['responsableFinancierAdresseEmployeur'])) {
                $responsable->setAdresseEmployeur($data['responsableFinancierAdresseEmployeur']);
            }
            
            if (!$infoEleve->getResponsableFinancier()) {
                $infoEleve->setResponsableFinancier($responsable);
                $this->entityManager->persist($responsable);
            }
        }
    }

    // Correction dans mapStep9Data - Séparer les documents des autres champs
    private function mapStep9Data(InfoEleve $infoEleve, array $data): void
    {
        
    }

    private function mapStep10Data(InfoEleve $infoEleve, array $data): void 
    {
        if (!empty($data['adhesionPaymentMethod'])) {
            $adhesion = $infoEleve->getAdhesion() ?: new \App\Entity\Adhesion();
            $adhesion->setPaymentMethod('cheque');
            if (!$infoEleve->getAdhesion()) {
                $infoEleve->setAdhesion($adhesion);
                $this->entityManager->persist($adhesion);
            }
        } else {
            if ($infoEleve->getAdhesion()) {
                $infoEleve->getAdhesion()->setPaymentMethod(null);
            }
        }

        if (isset($data['adhesionImageRights'])) {
            $infoEleve->setDroitImage((bool)$data['adhesionImageRights']);
        }
        
        if (isset($data['adhesionAccepted'])) {
            $adhesion = $infoEleve->getAdhesion() ?: new Adhesion();
            $adhesion->setAccepted((bool)$data['adhesionAccepted']);
            
            if (isset($data['adhesionPaymentMethod']) && !empty($data['adhesionPaymentMethod'])) {
                $adhesion->setPaymentMethod($data['adhesionPaymentMethod']);
            }
            
            if (isset($data['adhesionImageRights'])) {
                $adhesion->setImageRights((bool)$data['adhesionImageRights']);
            }
            
            if (!$infoEleve->getAdhesion()) {
                $infoEleve->setAdhesion($adhesion);
                $this->entityManager->persist($adhesion);
            }
        }
    }

    // Correction dans mapRepresentantLegalData - Ajouter le champ email manquant
    private function mapRepresentantLegalData(RepresentantLegal $representant, array $data, string $prefix): void
    {
        try {
            $this->logger->info('Début mapRepresentantLegalData', [
                'prefix' => $prefix,
                'data_keys' => array_keys($data)
            ]);

            // Champs obligatoires avec validation renforcée
            if (isset($data[$prefix . 'Nom']) && !empty(trim($data[$prefix . 'Nom']))) {
                $representant->setNom(trim($data[$prefix . 'Nom']));
                $this->logger->info('Nom défini', ['nom' => $representant->getNom()]);
            }
            
            if (isset($data[$prefix . 'Prenom']) && !empty(trim($data[$prefix . 'Prenom']))) {
                $representant->setPrenom(trim($data[$prefix . 'Prenom']));
                $this->logger->info('Prénom défini', ['prenom' => $representant->getPrenom()]);
            }

            // CORRECTION : Email avec le bon nom de champ du formulaire
            if (isset($data[$prefix . 'Courriel']) && !empty(trim($data[$prefix . 'Courriel']))) {
                $email = trim($data[$prefix . 'Courriel']);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $representant->setCourriel($email); // ou setEmail selon votre entité
                } else {
                    $this->logger->warning("Email invalide pour représentant légal", [
                        'email' => $email,
                        'prefix' => $prefix
                    ]);
                }
            }

            // Téléphone - utiliser le bon nom de champ
            if (isset($data[$prefix . 'Telephone']) && !empty(trim($data[$prefix . 'Telephone']))) {
                $representant->setTelephonePerso(trim($data[$prefix . 'Telephone']));
            }

            // Adresse - champ obligatoire
            if (isset($data[$prefix . 'Adresse']) && !empty(trim($data[$prefix . 'Adresse']))) {
                $representant->setAdresse(trim($data[$prefix . 'Adresse']));
                $this->logger->info('Adresse définie', ['adresse' => $representant->getAdresse()]);
            }

            // Lien avec l'élève - champ obligatoire
            if (isset($data[$prefix . 'LienEleve']) && !empty(trim($data[$prefix . 'LienEleve']))) {
                $representant->setLienEleve(trim($data[$prefix . 'LienEleve']));
                $this->logger->info('Lien avec élève défini', ['lien' => $representant->getLienEleve()]);
            }

            // Code postal et commune
            if (isset($data[$prefix . 'CodePostal']) && !empty(trim($data[$prefix . 'CodePostal']))) {
                $representant->setCodePostal(trim($data[$prefix . 'CodePostal']));
            }
            
            if (isset($data[$prefix . 'Commune']) && !empty(trim($data[$prefix . 'Commune']))) {
                $representant->setCommune(trim($data[$prefix . 'Commune']));
            }

            // Champs téléphone avec validation
            if (isset($data[$prefix . 'Telephone']) && !empty(trim($data[$prefix . 'Telephone']))) {
                $representant->setTelephonePerso(trim($data[$prefix . 'Telephone']));
            }
            
            if (isset($data[$prefix . 'TelephoneFixe']) && !empty(trim($data[$prefix . 'TelephoneFixe']))) {
                $representant->setTelephoneFixe(trim($data[$prefix . 'TelephoneFixe']));
            }
            
            if (isset($data[$prefix . 'TelephonePro']) && !empty(trim($data[$prefix . 'TelephonePro']))) {
                $representant->setTelephonePro(trim($data[$prefix . 'TelephonePro']));
            }
            
            // SMS autorisation
            if (isset($data[$prefix . 'Sms'])) {
                $representant->setSmsSend((bool)$data[$prefix . 'Sms']);
            }
            
            // Informations professionnelles
            if (isset($data[$prefix . 'Poste']) && !empty(trim($data[$prefix . 'Poste']))) {
                $representant->setPoste(trim($data[$prefix . 'Poste']));
            }
            
            if (isset($data[$prefix . 'NomEmployeur']) && !empty(trim($data[$prefix . 'NomEmployeur']))) {
                $representant->setNomEmployeur(trim($data[$prefix . 'NomEmployeur']));
            }
            
            if (isset($data[$prefix . 'AdresseEmployeur']) && !empty(trim($data[$prefix . 'AdresseEmployeur']))) {
                $representant->setAdresseEmployeur(trim($data[$prefix . 'AdresseEmployeur']));
            }

            $this->logger->info('Fin mapRepresentantLegalData - succès', [
                'nom' => $representant->getNom(),
                'adresse' => $representant->getAdresse(),
                'lien' => $representant->getLienEleve()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur dans mapRepresentantLegalData', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'prefix' => $prefix
            ]);
            throw $e;
        }
    }

    /**
     * Gère les représentants légaux avec validation renforcée
     */
    private function handleRepresentantLegal(array $data, InfoEleve $infoEleve, int $numero): void
    {
        try {
            $prefix = $numero === 1 ? 'representantLegal1' : 'representantLegal2';
            
            $this->logger->info("Traitement représentant légal $numero", [
                'prefix' => $prefix,
                'data_keys' => array_keys($data),
                'nom_field' => $prefix . 'Nom',
                'nom_value' => $data[$prefix . 'Nom'] ?? 'non défini',
                'adresse_value' => $data[$prefix . 'Adresse'] ?? 'non défini',
                'lien_value' => $data[$prefix . 'LienEleve'] ?? 'non défini'
            ]);

            // Vérifier si nous avons les champs obligatoires minimum
            $hasRequiredFields = false;
            $requiredFields = ['Nom', 'Adresse', 'LienEleve'];
            
            foreach ($requiredFields as $field) {
                $fieldKey = $prefix . $field;
                if (isset($data[$fieldKey]) && !empty(trim($data[$fieldKey]))) {
                    $hasRequiredFields = true;
                    break;
                }
            }

            if (!$hasRequiredFields) {
                $this->logger->info("Pas de champs obligatoires pour représentant légal $numero, pas de création/mise à jour");
                return;
            }

            // Récupérer ou créer le représentant légal
            $representant = $numero === 1 ? 
                $infoEleve->getResponsableUn() : 
                $infoEleve->getResponsableDeux();

            if (!$representant) {
                $representant = new RepresentantLegal();
                $this->entityManager->persist($representant);
                
                if ($numero === 1) {
                    $infoEleve->setResponsableUn($representant);
                } else {
                    $infoEleve->setResponsableDeux($representant);
                }
                
                $this->logger->info("Nouveau représentant légal $numero créé");
            }

            // Mapper les données avec validation
            $this->mapRepresentantLegalData($representant, $data, $prefix);
            
            $this->logger->info("Représentant légal $numero traité avec succès", [
                'id' => $representant->getId(),
                'nom' => $representant->getNom(),
                'adresse' => $representant->getAdresse(),
                'lien' => $representant->getLienEleve()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors du traitement représentant légal $numero", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data_keys' => array_keys($data)
            ]);
            throw $e;
        }
    }

    /**
     * Trouve une classe par son ID ou son label
     */
    private function findClasse($classeValue): ?Classe
    {
        try {
            // Essayer de trouver par ID si c'est un nombre
            if (is_numeric($classeValue)) {
                $classe = $this->classeRepository->find((int)$classeValue);
                if ($classe) {
                    return $classe;
                }
            }
            
            // Sinon essayer par label
            return $this->classeRepository->findOneBy(['label' => $classeValue]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la recherche de classe', [
                'classe_value' => $classeValue,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Définit la date de naissance avec gestion des différents formats
     */
    private function setDateNaissance(InfoEleve $infoEleve, $dateValue): void
    {
        try {
            if ($dateValue instanceof \DateTime) {
                $infoEleve->setDateDeNaissance($dateValue);
            } elseif (is_string($dateValue) && !empty($dateValue)) {
                $date = new \DateTime($dateValue);
                $infoEleve->setDateDeNaissance($date);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la définition de la date de naissance', [
                'date_value' => $dateValue,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Récupère les erreurs détaillées du formulaire pour AJAX
     */
    private function getFormErrorsDetailed(FormInterface $form): array
    {
        $errors = [];
        
        // Erreurs du formulaire principal
        foreach ($form->getErrors() as $error) {
            $errors['form'][] = $error->getMessage();
        }
        
        // Erreurs des champs enfants
        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                foreach ($child->getErrors() as $error) {
                    $errors['fields'][$child->getName()][] = $error->getMessage();
                }
            }
        }
        
        return $errors;
    }

    /**
     * Convertit les entités en tableau pour le formulaire
     */
    private function convertEntityToArray(User $user, InfoEleve $infoEleve): array
    {
        $data = [
            // Données utilisateur
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            
            // Données personnelles
            'dateNaissance' => $infoEleve->getDateDeNaissance(),
            'sexe' => $infoEleve->getSexe(),
            'nationalite' => $infoEleve->getNationalite(),
            'departement' => $infoEleve->getDepartement(),
            'communeNaissance' => $infoEleve->getCommuneNaissance(),
            'numSecuSocial' => $infoEleve->getNumSecuSocial(),
            
            // Contact
            'numeroMobile' => $infoEleve->getNumeroMobile(),
            'nomContacteUrgence' => $infoEleve->getNomContacteUrgence(),
            'numeroContacteUrgence' => $infoEleve->getNumeroContacteUrgence(),
            'accepterSms' => $infoEleve->getSmsSend(),

            
            // Scolarité
            // 'classe' => $infoEleve->getClasse() ? $infoEleve->getClasse()->getId() : null, // Assurez-vous que c'est un objet Classe
            'promotion' => $infoEleve->getPromotion(),
            'regime' => $infoEleve->getRegime(),
            'lvUn' => $infoEleve->getLVUn(),
            'lvDeux' => $infoEleve->getLVDeux(),
            'redoublant' => $infoEleve->isRedoublant(),
            'dernierDiplome' => $infoEleve->getDernierDiplome(),
            'transportScolaire' => $infoEleve->getTransportScolaire(),
            'immatriculationVeic' => $infoEleve->getImmattriculationVeic(),
            'cheque' => $infoEleve->isCheque(),
            'droitImage' => $infoEleve->isDroitImage(),
        ];

        return $data;
    }

    private function prepareInscriptionData(InfoEleve $infoEleve, array &$data): void
    {
        // Représentant légal 1
        if ($infoEleve->getResponsableUn()) {
            $rep1 = $infoEleve->getResponsableUn();
            $data['representantLegal1Nom'] = $rep1->getNom();
            $data['representantLegal1Prenom'] = $rep1->getPrenom();
            $data['representantLegal1Telephone'] = $rep1->getTelephonePerso();
            $data['representantLegal1TelephoneFixe'] = $rep1->getTelephoneFixe();
            $data['representantLegal1TelephonePro'] = $rep1->getTelephonePro();
            $data['representantLegal1Sms'] = $rep1->getSmsSend();
            $data['representantLegal1Courriel'] = $rep1->getCourriel();
            $data['representantLegal1Adresse'] = $rep1->getAdresse();
            $data['representantLegal1CodePostal'] = $rep1->getCodePostal();
            $data['representantLegal1Commune'] = $rep1->getCommune();
            $data['representantLegal1LienEleve'] = $rep1->getLienEleve();
            $data['representantLegal1Poste'] = $rep1->getPoste();
            $data['representantLegal1NomEmployeur'] = $rep1->getNomEmployeur();
            $data['representantLegal1AdresseEmployeur'] = $rep1->getAdresseEmployeur();
        }

        // Représentant légal 2
        if ($infoEleve->getResponsableDeux()) {
            $rep2 = $infoEleve->getResponsableDeux();
            $data['representantLegal2Nom'] = $rep2->getNom();
            $data['representantLegal2Prenom'] = $rep2->getPrenom();
            $data['representantLegal2Telephone'] = $rep2->getTelephonePerso();
            $data['representantLegal2TelephoneFixe'] = $rep2->getTelephoneFixe();
            $data['representantLegal2TelephonePro'] = $rep2->getTelephonePro();
            $data['representantLegal2Sms'] = $rep2->getSmsSend();
            $data['representantLegal2Courriel'] = $rep2->getCourriel();
            $data['representantLegal2Adresse'] = $rep2->getAdresse();
            $data['representantLegal2CodePostal'] = $rep2->getCodePostal();
            $data['representantLegal2Commune'] = $rep2->getCommune();
            $data['representantLegal2LienEleve'] = $rep2->getLienEleve();
            $data['representantLegal2Poste'] = $rep2->getPoste();
            $data['representantLegal2NomEmployeur'] = $rep2->getNomEmployeur();
            $data['representantLegal2AdresseEmployeur'] = $rep2->getAdresseEmployeur();
        }

        // Scolarité antérieure - Année 1 (N-1)
        if ($infoEleve->getAnneScolaireUn()) {
            $scolarite1 = $infoEleve->getAnneScolaireUn();
            $data['etablissementPrecedent1'] = $scolarite1->getEtablissement();
            $data['classePrecedente1'] = $scolarite1->getClasse();
            $data['optionPrecedente1'] = $scolarite1->getOption();
            $data['lvUnPrecedente1'] = $scolarite1->getLVUn();
            $data['lvDeuxPrecedente1'] = $scolarite1->getLVDeux();
            $data['anneeScolairePrecedente1'] = $scolarite1->getAnneScolaire();
        }

        // Scolarité antérieure - Année 2 (N-2)
        if ($infoEleve->getAnneScolaireDeux()) {
            $scolarite2 = $infoEleve->getAnneScolaireDeux();
            $data['etablissementPrecedent2'] = $scolarite2->getEtablissement();
            $data['classePrecedente2'] = $scolarite2->getClasse();
            $data['optionPrecedente2'] = $scolarite2->getOption();
            $data['lvUnPrecedente2'] = $scolarite2->getLVUn();
            $data['lvDeuxPrecedente2'] = $scolarite2->getLVDeux();
            $data['anneeScolairePrecedente2'] = $scolarite2->getAnneScolaire();
        }

        // Médecin traitant
        if ($infoEleve->getMedecinTraitant()) {
            $medecin = $infoEleve->getMedecinTraitant();
            $data['medecinTraitantNom'] = $medecin->getNom();
            $data['medecinTraitantTelephone'] = $medecin->getNumero();
            $data['medecinTraitantAdresse'] = $medecin->getAdresse();
        }

        // Sécurité sociale
        if ($infoEleve->getSecuSociale()) {
            $secu = $infoEleve->getSecuSociale();
            $data['secuSocialeNom'] = $secu->getNom();
            $data['secuSocialeAdresse'] = $secu->getAddresse();
        }

        // Assureur
        if ($infoEleve->getAssureur()) {
            $assureur = $infoEleve->getAssureur();
            $data['assureurNom'] = $assureur->getNom();
            $data['assureurAdresse'] = $assureur->getAddresse();
            $data['assureurNumeroAssurance'] = $assureur->getNumeroAssurance();
        }

        // Responsable financier
        if ($infoEleve->getResponsableFinancier()) {
            $responsable = $infoEleve->getResponsableFinancier();
            $data['responsableFinancierNom'] = $responsable->getNom();
            $data['responsableFinancierPrenom'] = $responsable->getPrenom();
            $data['responsableFinancierNomEmployeur'] = $responsable->getNomEmployeur();
            $data['responsableFinancierAdresseEmployeur'] = $responsable->getAdresseEmployeur();
        }

        // Adhésion
        if ($infoEleve->getAdhesion()) {
            $adhesion = $infoEleve->getAdhesion();
            $data['adhesionAccepted'] = $adhesion->isAccepted();
            $data['adhesionPaymentMethod'] = $adhesion->getPaymentMethod();
            $data['adhesionImageRights'] = $adhesion->getImageRights();
        }

        // Classe
        // if ($infoEleve->getClasse()) {
        //     $data['classe'] = [
        //         'id' => $infoEleve->getClasse()->getId(),
        //         'label' => $infoEleve->getClasse()->getLabel()
        //     ];
        // } else {
        //     $data['classe'] = null;
        // }

        if ($infoEleve->getClasse()) {
            $data['classe'] = $infoEleve->getClasse()->getId();
        } else {
            $data['classe'] = null;
        }

        // Étape 10 : Droit à l'image et mode de paiement
        $data['cheque'] = $infoEleve->isCheque();
        $data['droitImage'] = $infoEleve->isDroitImage();
    }

    /**
     * Vérifie si l'inscription est complète
     */
    private function isInscriptionComplete(InfoEleve $infoEleve): bool
    {
        return $infoEleve->isInscriptionComplete();
    }

    /**
     * Retourne le libellé d'une étape
     */
    private function getStepLabel(int $step): string
    {
        $labels = [
            1 => 'Informations personnelles',
            2 => 'Contact et urgence',
            3 => 'Informations scolaires',
            4 => 'Représentant légal 1',
            5 => 'Représentant légal 2',
            6 => 'Scolarité antérieure',
            7 => 'Informations médicales',
            8 => 'Responsable financier',
            9 => 'Documents à fournir',
            10 => 'Finalisation et adhésion'
        ];

        return $labels[$step] ?? 'Étape inconnue';
    }
    #[Route('/inscription/formulaire/{step}', name: 'app_inscription_form', requirements: ['step' => '\d+'], defaults: ['step' => 1])]
    #[IsGranted('ROLE_USER')]
    public function inscriptionForm(Request $request, int $step): Response|JsonResponse
    {
        try {
            $user = $this->getUser ();
            
            // Validation de l'étape
            if ($step < 1 || $step > self::TOTAL_STEPS) {
                throw new \InvalidArgumentException('Étape invalide');
            }

            // S'assurer qu'InfoEleve existe
            $infoEleve = $this->getOrCreateInfoEleve($user);
            
            // Récupération des données depuis la BDD
            $data = $this->getInscriptionDataFromDatabase($user, $infoEleve);
            
            // Création du formulaire avec l'étape courante
            $form = $this->createForm(InscriptionType::class, $data, [
                'step' => $step,
            ]);

            $form->handleRequest($request);

            // Traitement AJAX
            if ($request->isXmlHttpRequest()) {
                return $this->handleAjaxRequest($request, $form, $user, $step, $infoEleve);
            }

            // Traitement standard
            if ($form->isSubmitted() && $form->isValid()) {
                return $this->handleFormSubmission($request, $form, $user, $step, $infoEleve);
            }

            return $this->render('inscription/form.html.twig', [
                'form' => $form->createView(),
                'flow' => [
                    'currentStepNumber' => $step,
                    'currentStepLabel' => $this->getStepLabel($step),
                    'nextStepLabel' => $step < self::TOTAL_STEPS ? $this->getStepLabel($step + 1) : null,
                    'isFirstStep' => $step === 1,
                    'isLastStep' => $step === self::TOTAL_STEPS,
                    'totalSteps' => self::TOTAL_STEPS,
                ],
                'infoEleve' => $infoEleve,
                'user' => $user,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('ERREUR dans inscriptionForm', [
                'error' => $e->getMessage(),
                'step' => $step,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Erreur de connexion avec le serveur.'
                ], 500);
            }
            
            throw $e;
        }
    }

    private function handleFormSubmission(Request $request, FormInterface $form, User $user, int $step, InfoEleve $infoEleve): Response
    {
        try {
            $formData = $form->getData();
            $formData = $this->filterFormData($formData);

            // Ajoute ceci pour l'étape 10 :
            if ($step === 10) {
                $formData['adhesionAccepted'] = $form->get('adhesionAccepted')->getData();
                $formData['adhesionImageRights'] = $form->get('adhesionImageRights')->getData();
                $formData['cheque'] = $form->get('cheque')->getData();
                // $formData['adhesionPaymentMethod'] = $form->get('adhesionPaymentMethod')->getData(); // si tu ajoutes ce champ
            }
            
            // Traitement spécifique selon l'étape (seulement si on avance)
            $transition = $request->request->get('flow_transition', 'next');
            
            if ($transition === 'next' || $transition === 'finish') {
                $this->processStepData($step, $form, $user, $infoEleve, $formData);
            }
            
            // Sauvegarde des données de l'étape
            $this->saveStepDataToDatabase($user, $infoEleve, $formData, $step);
            
            // Gestion des transitions
            return $this->handleTransition($request, $step, $infoEleve);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la soumission du formulaire', [
                'step' => $step,
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('error', 'Une erreur est survenue lors de l\'enregistrement : ' . $e->getMessage());
            
            return $this->redirectToRoute('app_inscription_form', ['step' => $step]);
        }
    }
    
    private function handleTransition(Request $request, int $step, InfoEleve $infoEleve): Response
    {
        $transition = $request->request->get('flow_transition', 'next');
        
        switch ($transition) {
            case 'next':
                if ($step < self::TOTAL_STEPS) {
                    return $this->redirectToRoute('app_inscription_form', ['step' => $step + 1]);
                }
                // Si on est à la dernière étape et qu'on clique sur "suivant", on finalise
                return $this->finalizeInscription($infoEleve);
                
            case 'previous':
                if ($step > 1) {
                    return $this->redirectToRoute('app_inscription_form', ['step' => $step - 1]);
                }
                break;
                
            case 'finish':
                if ($step === self::TOTAL_STEPS) {
                    return $this->finalizeInscription($infoEleve);
                }
                break;
        }
        
        return $this->redirectToRoute('app_inscription_form', ['step' => $step]);
    }

    private function finalizeInscription(InfoEleve $infoEleve): Response
    {
        $infoEleve->setInscriptionComplete(true);
        $infoEleve->setDateInscription(new \DateTime());
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Votre inscription a été finalisée avec succès !');
        
        return $this->redirectToRoute('app_inscription_dashboard');
    }

    private function processStepData(int $step, FormInterface $form, User $user, InfoEleve $infoEleve, array $formData): void
    {
        switch ($step) {
            case 1:
                $this->handleStep1Submission($form, $user, $infoEleve, $formData);
                break;
            case 2:
                $this->handleStep2Submission($form, $infoEleve, $formData);
                break;
            case 3:
                $this->handleStep3Submission($form, $infoEleve, $formData);
                break;
            case 4:
                $this->handleStep4Submission($form, $infoEleve, $formData);
                break;
            case 5:
                $this->handleStep5Submission($form, $infoEleve, $formData);
                break;
            case 6:
                $this->handleStep6Submission($form, $infoEleve, $formData);
                break;
            case 7:
                $this->handleStep7Submission($form, $infoEleve, $formData);
                break;
            case 8:
                $this->handleStep8Submission($form, $infoEleve, $formData);
                break;
            case 9:
                $this->handleStep9Submission($form, $infoEleve);
                break;
            case 10:
                $this->handleStep10Submission($form, $infoEleve, $formData);
                break;
        }
    }

    // Méthodes spécifiques pour chaque étape
    private function handleStep1Submission(FormInterface $form, User $user, InfoEleve $infoEleve, array $formData): void
    {
        // Traitement spécifique étape 1 - Informations personnelles
        // Exemple : validation de l'âge, formatage des données...
    }

    private function handleStep2Submission(FormInterface $form, InfoEleve $infoEleve, array $formData): void
    {
        // Traitement spécifique étape 2 - Adresse
        // Exemple : géocodage de l'adresse, validation code postal...
    }

    private function handleStep3Submission(FormInterface $form, InfoEleve $infoEleve, array $formData): void
    {
        // Traitement spécifique étape 3 - Contact d'urgence
    }

    private function handleStep4Submission(FormInterface $form, InfoEleve $infoEleve, array $formData): void
    {
        // Traitement spécifique étape 4 - Informations médicales
    }

    private function handleStep5Submission(FormInterface $form, InfoEleve $infoEleve, array $formData): void
    {
        // Traitement spécifique étape 5 - Scolarité antérieure
    }

    private function handleStep6Submission(FormInterface $form, InfoEleve $infoEleve, array $formData): void
    {
        // Traitement spécifique étape 6 - Choix de formation
    }

    private function handleStep7Submission(FormInterface $form, InfoEleve $infoEleve, array $formData): void
    {
        // Traitement spécifique étape 7 - Informations complémentaires
    }

    private function handleStep8Submission(FormInterface $form, InfoEleve $infoEleve, array $formData): void
    {
        // Traitement spécifique étape 8 - Validation des données
    }

    private function handleStep9Submission(FormInterface $form, InfoEleve $infoEleve): void
    {
        // Traitement spécifique étape 9 - Téléchargement des documents
    }
    
    private function handleStep10Submission(FormInterface $form, InfoEleve $infoEleve, array $formData): void
    {
        // Traitement spécifique étape 10 - Récapitulatif et validation finale
        // Exemple : génération d'un numéro d'inscription, envoi d'email de confirmation...
    }    

    private function saveStepDataToDatabase(User $user, InfoEleve $infoEleve, array $formData, int $step): void
    {
        try {
            $this->logger->info('Début sauvegarde étape', [
                'user_id' => $user->getId(),
                'step' => $step,
                'data_keys' => array_keys($formData)
            ]);

            // Utiliser la transaction pour assurer la cohérence
            $this->entityManager->getConnection()->beginTransaction();

            try {
                // Récupérer les entités fraîches depuis la base
                $freshInfoEleve = $this->infoEleveRepository->find($infoEleve->getId());
                $freshUser  = $this->entityManager->getRepository(User::class)->find($user->getId());

                if (!$freshInfoEleve || !$freshUser ) {
                    throw new \RuntimeException('Entités introuvables');
                }

                // Utiliser les entités fraîches
                $this->mapStepDataToEntity($freshUser , $freshInfoEleve, $formData, $step);

                // Vérifier l'état avant flush
                $this->logger->info('Avant flush', [
                    'step' => $step,
                    'info_eleve_id' => $freshInfoEleve->getId()
                ]);

                $this->entityManager->flush();
                $this->entityManager->getConnection()->commit();
                
                $this->logger->info('Étape sauvegardée avec succès', [
                    'user_id' => $freshUser ->getId(),
                    'step' => $step,
                    'info_eleve_id' => $freshInfoEleve->getId()
                ]);
                
            } catch (\Exception $e) {
                $this->entityManager->getConnection()->rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la sauvegarde étape', [
                'user_id' => $user->getId(),
                'step' => $step,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \RuntimeException('Erreur lors de la sauvegarde: ' . $e->getMessage(), 0, $e);
        }
    }
}