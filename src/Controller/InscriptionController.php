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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InscriptionController extends AbstractController
{
    private const TOTAL_STEPS = 10;
    private $session;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InfoEleveRepository $infoEleveRepository,
        private readonly ClasseRepository $classeRepository,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator
    ) 
    {        
        $this->session = $requestStack->getSession();
    }

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

    #[Route('/inscription/formulaire/{step}', name: 'app_inscription_form', requirements: ['step' => '\d+'], defaults: ['step' => 1])]
    #[IsGranted('ROLE_USER')]
    public function inscriptionForm(Request $request, int $step): Response|JsonResponse
    {
        try {
            $user = $this->getUser();
            $this->logger->info('=== DÉBUT INSCRIPTION FORM ===', [
                'user_id' => $user->getId(),
                'step' => $step,
                'request_method' => $request->getMethod(),
                'is_ajax' => $request->isXmlHttpRequest(),
                'content_type' => $request->headers->get('Content-Type'),
            ]);

            // Validation de l'étape
            if ($step < 1 || $step > self::TOTAL_STEPS) {
                throw new \InvalidArgumentException('Étape invalide');
            }

            // Récupération des données de session ou base de données
            $data = $this->getInscriptionData($user);
            
            // Création du formulaire avec l'étape courante
            $form = $this->createForm(InscriptionType::class, $data, [
                'step' => $step,
            ]);

            $form->handleRequest($request);

            // Traitement AJAX
            if ($request->isXmlHttpRequest()) {
                return $this->handleAjaxRequest($request, $form, $user, $step, $data);
            }

            // Traitement standard
            if ($form->isSubmitted() && $form->isValid()) {
                return $this->handleFormSubmission($request, $form, $user, $step);
            }

            $this->logger->info('Affichage du formulaire', ['step' => $step]);
            
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
                'user' => $user,
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('ERREUR CRITIQUE dans inscriptionForm', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
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

    private function handleAjaxRequest(Request $request, FormInterface $form, User $user, int $step, array $data): JsonResponse
    {
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Sauvegarde des données de l'étape courante
                $formData = $form->getData();
                $this->saveStepData($user, $formData, $step);

                $transition = $request->request->get('flow_transition', 'next');

                switch ($transition) {
                    case 'next':
                        if ($step < self::TOTAL_STEPS) {
                            $nextStep = $step + 1;
                            return new JsonResponse([
                                'success' => true,
                                'redirect' => $this->urlGenerator->generate('app_inscription_form', ['step' => $nextStep])
                            ]);
                        }
                        break;

                    case 'previous':
                        if ($step > 1) {
                            $previousStep = $step - 1;
                            return new JsonResponse([
                                'success' => true,
                                'redirect' => $this->urlGenerator->generate('app_inscription_form', ['step' => $previousStep])
                            ]);
                        }
                        break;

                    case 'finish':
                        // Sauvegarde finale
                        $this->saveInscriptionData($user, $formData);
                        $this->session->remove('inscription_data');
                        
                        return new JsonResponse([
                            'success' => true,
                            'redirect' => $this->urlGenerator->generate('app_inscription_dashboard')
                        ]);
                }

                return new JsonResponse([
                    'success' => true,
                    'redirect' => $this->urlGenerator->generate('app_inscription_form', ['step' => $step])
                ]);

            } catch (\Exception $e) {
                $this->logger->error('Erreur AJAX', [
                    'error' => $e->getMessage(),
                    'step' => $step
                ]);
                
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Une erreur est survenue lors du traitement du formulaire.'
                ], 500);
            }
        }

        // Formulaire invalide - retourner les erreurs
        if ($request->isMethod('POST')) {
            $errors = $this->getFormErrors($form);
            
            return new JsonResponse([
                'success' => false,
                'errors' => $errors,
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
                ])
            ]);
        }

        return new JsonResponse(['success' => false, 'error' => 'Requête invalide'], 400);
    }

    private function handleFormSubmission(Request $request, FormInterface $form, User $user, int $step): Response
    {
        $formData = $form->getData();
        $this->saveStepData($user, $formData, $step);

        $transition = $request->request->get('flow_transition', 'next');

        switch ($transition) {
            case 'next':
                if ($step < self::TOTAL_STEPS) {
                    return $this->redirectToRoute('app_inscription_form', ['step' => $step + 1]);
                }
                break;

            case 'previous':
                if ($step > 1) {
                    return $this->redirectToRoute('app_inscription_form', ['step' => $step - 1]);
                }
                break;

            case 'finish':
                $this->saveInscriptionData($user, $formData);
                $this->session->remove('inscription_data');
                return $this->redirectToRoute('app_inscription_dashboard');
        }

        return $this->redirectToRoute('app_inscription_form', ['step' => $step]);
    }

    private function getInscriptionData(User $user): array
    {
        // Priorité aux données de session (brouillon)
        $sessionData = $this->session->get('inscription_data', []);
        if (!empty($sessionData)) {
            $this->logger->info('Données chargées depuis la session');
            return $sessionData;
        }

        // Sinon charger depuis la base de données
        $infoEleve = $this->infoEleveRepository->findOneBy(['user' => $user]);
        if ($infoEleve) {
            $data = $this->convertEntityToArray($user, $infoEleve);
            $this->prepareInscriptionData($infoEleve, $data);
            $this->logger->info('Données chargées depuis la base de données');
            return $data;
        }

        $this->logger->info('Nouvelles données d\'inscription');
        return [];
    }

    private function saveStepData(User $user, array $formData, int $step): void
    {
        // Fusionner avec les données existantes
        $existingData = $this->session->get('inscription_data', []);
        $mergedData = array_merge($existingData, $formData);
        
        $this->session->set('inscription_data', $mergedData);
        
        $this->logger->info('Données étape sauvegardées', [
            'user_id' => $user->getId(),
            'step' => $step,
            'data_keys' => array_keys($formData)
        ]);
    }

    #[Route('/inscription/confirmation', name: 'app_inscription_confirmation')]
    #[IsGranted('ROLE_USER')]
    public function confirmation(): Response
    {
        return $this->render('inscription/confirmation.html.twig');
    }

    #[Route('/inscription/modifier', name: 'app_inscription_edit')]
    #[IsGranted('ROLE_USER')]
    public function editInscription(): Response
    {
        $user = $this->getUser();
        $infoEleve = $this->infoEleveRepository->findOneBy(['user' => $user]);

        if (!$infoEleve) {
            $this->addFlash('error', 'Aucune inscription à modifier.');
            return $this->redirectToRoute('app_inscription_dashboard');
        }

        // Charger les données existantes dans la session
        $data = $this->convertEntityToArray($user, $infoEleve);
        $this->prepareInscriptionData($infoEleve, $data);
        $this->session->set('inscription_data', $data);
        
        // Marquer en mode édition
        $this->session->set('inscription_edit_mode', true);
        
        $this->addFlash('info', 'Mode modification activé. Vous pouvez maintenant modifier votre inscription.');
        
        return $this->redirectToRoute('app_inscription_form', ['step' => 1]);
    }

    #[Route('/inscription/reset-form', name: 'app_inscription_reset_form', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function resetForm(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('reset_form', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_inscription_dashboard');
        }

        $user = $this->getUser();
        
        try {
            // Supprimer les données de session
            $this->session->remove('inscription_data');
            $this->session->remove('inscription_edit_mode');
            
            // Supprimer les données en base de données
            $infoEleve = $this->infoEleveRepository->findOneBy(['user' => $user]);
            if ($infoEleve) {
                $this->deleteRelatedEntities($infoEleve);
                $this->entityManager->remove($infoEleve);
                $user->setInfoEleve(null);
                $this->entityManager->flush();
            }
            
            $this->addFlash('success', 'Le formulaire d\'inscription a été complètement réinitialisé.');
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la réinitialisation du formulaire', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            $this->addFlash('error', 'Erreur lors de la réinitialisation du formulaire.');
        }
        
        return $this->redirectToRoute('app_inscription_dashboard');
    }

    #[Route('/inscription/reset-draft', name: 'app_inscription_reset_draft')]
    #[IsGranted('ROLE_USER')]
    public function resetDraft(): Response
    {
        $this->session->remove('inscription_data');
        $this->addFlash('info', 'Le brouillon a été supprimé.');
        
        return $this->redirectToRoute('app_inscription_form', ['step' => 1]);
    }

    #[Route('/inscription/pdf', name: 'app_inscription_pdf')]
    #[IsGranted('ROLE_USER')]
    public function generatePdf(): Response
    {
        $user = $this->getUser();
        $infoEleve = $this->infoEleveRepository->findOneBy(['user' => $user]);

        if (!$infoEleve) {
            $this->addFlash('error', 'Aucune inscription trouvée.');
            return $this->redirectToRoute('app_inscription_dashboard');
        }

        if (!$this->isInscriptionComplete($infoEleve)) {
            $this->addFlash('error', 'L\'inscription doit être complétée avant de générer le PDF.');
            return $this->redirectToRoute('app_inscription_dashboard');
        }

        $inscription = $this->convertEntityToArray($user, $infoEleve);
        $this->prepareInscriptionData($infoEleve, $inscription);

        $html = $this->renderView('inscription/pdf.html.twig', [
            'user' => $user,
            'inscription' => $inscription,
            'infoEleve' => $infoEleve
        ]);

        return new Response($html, 200, [
            'Content-Type' => 'text/html'
        ]);
    }

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
            10 => 'Finalisation et adhésion',
        ];

        return $labels[$step] ?? 'Étape inconnue';
    }

    private function getFormErrors(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return $errors;
    }

    private function prepareInscriptionData(InfoEleve $infoEleve, array &$data): void
    {
        $this->convertScolariteAnterieurToArray($infoEleve, $data);
    }

    private function saveInscriptionData(User $user, array $data): void
    {
        try {
            $this->logger->info('Début de sauvegarde inscription', [
                'user_id' => $user->getId(),
                'data_keys' => array_keys($data)
            ]);
            
            $infoEleve = $user->getInfoEleve() ?? new InfoEleve($user);
            
            $this->mapDataToEntity($user, $infoEleve, $data);

            $this->entityManager->persist($infoEleve);
            $this->entityManager->flush();
            
            $this->logger->info('Inscription sauvegardée avec succès', [
                'user_id' => $user->getId(),
                'info_eleve_id' => $infoEleve->getId()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la sauvegarde de l\'inscription', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data ?? null
            ]);
            throw $e;
        }
    }

    private function mapDataToEntity(User $user, InfoEleve $infoEleve, array $data): void
    {
        try {
            // Informations personnelles
            $user->setNom($data['nom'] ?? '');
            $user->setPrenom($data['prenom'] ?? '');
            $user->setEmail($data['email'] ?? '');
            
            // Gérer la date de naissance
            $dateNaissance = $data['dateNaissance'] ?? null;
            if ($dateNaissance) {
                try {
                    if (is_string($dateNaissance)) {
                        $infoEleve->setDateDeNaissance(new \DateTime($dateNaissance));
                    } elseif ($dateNaissance instanceof \DateTime) {
                        $infoEleve->setDateDeNaissance($dateNaissance);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Erreur de format de date de naissance', [
                        'date' => $dateNaissance,
                        'error' => $e->getMessage()
                    ]);
                    throw new \InvalidArgumentException('Format de date de naissance invalide');
                }
            }
            
            $infoEleve->setSexe($data['sexe'] ?? '');
            $infoEleve->setNationalite($data['nationalite'] ?? '');
            $infoEleve->setDepartement($data['departement'] ?? '');
            $infoEleve->setCommuneNaissance($data['communeNaissance'] ?? '');
            $infoEleve->setNumeroMobile($data['numeroMobile'] ?? '');
            $infoEleve->setNomContacteUrgence($data['nomContacteUrgence'] ?? '');
            $infoEleve->setNumeroContacteUrgence($data['numeroContacteUrgence'] ?? '');

            // Informations scolaires
            if (isset($data['classe']) && !empty($data['classe'])) {
                if (is_string($data['classe'])) {
                    $classe = $this->classeRepository->findOneBy(['label' => $data['classe']]);
                    if (!$classe) {
                        $classe = $this->classeRepository->find($data['classe']);
                    }
                    $infoEleve->setClasse($classe);
                } elseif ($data['classe'] instanceof Classe) {
                    $infoEleve->setClasse($data['classe']);
                }
            }
            
            $infoEleve->setPromotion($data['promotion'] ?? '');
            $infoEleve->setRegime($data['regime'] ?? '');
            $infoEleve->setLVUn($data['lvUn'] ?? '');
            $infoEleve->setLVDeux($data['lvDeux'] ?? '');
            $infoEleve->setRedoublant((bool)($data['redoublant'] ?? false));
            $infoEleve->setDernierDiplome($data['dernierDiplome'] ?? '');
            $infoEleve->setTransportScolaire($data['transportScolaire'] ?? '');
            $infoEleve->setImmattriculationVeic($data['immatriculationVeic'] ?? '');
            $infoEleve->setNumSecuSocial($data['numSecuSocial'] ?? '');

            // Représentants légaux
            $this->handleRepresentantLegal($data, $infoEleve, 1);
            $this->handleRepresentantLegal($data, $infoEleve, 2);

            // Scolarité antérieure
            $this->handleScolariteAnterieure($data, $infoEleve);

            // Autres entités
            $this->handleOtherEntities($data, $infoEleve);

            // Adhésion
            $this->handleAdhesion($data, $infoEleve);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur dans mapDataToEntity', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    private function handleOtherEntities(array $data, InfoEleve $infoEleve): void
    {
        // Médecin traitant
        if (isset($data['medecinTraitantNom']) && $data['medecinTraitantNom'] !== "") {
            $medecin = $infoEleve->getMedecinTraitant();
            if (!$medecin) {
                $medecin = new MedecinTraitant();
                $infoEleve->setMedecinTraitant($medecin);
                $this->entityManager->persist($medecin);
            }
            
            $medecin->setNom($data['medecinTraitantNom']);
            if (isset($data['medecinTraitantTelephone'])) {
                $medecin->setNumero($data['medecinTraitantTelephone']);
            }
            if (isset($data['medecinTraitantAdresse'])) {
                $medecin->setAdresse($data['medecinTraitantAdresse']);
            }
        }

        // Responsable financier
        if (isset($data['responsableFinancierNom']) && $data['responsableFinancierNom'] !== "") {
            $responsable = $infoEleve->getResponsableFinancier();
            if (!$responsable) {
                $responsable = new ResposableFinancier();
                $infoEleve->setResponsableFinancier($responsable);
                $this->entityManager->persist($responsable);
            }
            
            $responsable->setNom($data['responsableFinancierNom']);
            if (isset($data['responsableFinancierPrenom'])) {
                $responsable->setPrenom($data['responsableFinancierPrenom']);
            }
            if (isset($data['responsableFinancierNomEmployeur'])) {
                $responsable->setNomEmployeur($data['responsableFinancierNomEmployeur']);
            }
            if (isset($data['responsableFinancierAdresseEmployeur'])) {
                $responsable->setAdresseEmployeur($data['responsableFinancierAdresseEmployeur']);
            }
        }

        // Sécurité sociale
        if (isset($data['secuSocialeNom']) && $data['secuSocialeNom'] !== "") {
            $secuSociale = $infoEleve->getSecuSociale();
            if (!$secuSociale) {
                $secuSociale = new CentreSecuriteSociale();
                $infoEleve->setSecuSociale($secuSociale);
                $this->entityManager->persist($secuSociale);
            }
            
            $secuSociale->setNom($data['secuSocialeNom']);
            if (isset($data['secuSocialeAdresse'])) {
                $secuSociale->setAddresse($data['secuSocialeAdresse']);
            }
        }

        // Assureur
        if (isset($data['assureurNom']) && $data['assureurNom'] !== "") {
            $assureur = $infoEleve->getAssureur();
            if (!$assureur) {
                $assureur = new AssuranceScolaire();
                $infoEleve->setAssureur($assureur);
                $this->entityManager->persist($assureur);
            }
            
            $assureur->setNom($data['assureurNom']);
            if (isset($data['assureurAdresse'])) {
                $assureur->setAddresse($data['assureurAdresse']);
            }
            if (isset($data['assureurNumeroAssurance'])) {
                $assureur->setNumeroAssurance($data['assureurNumeroAssurance']);
            }
        }
    }

    private function handleRepresentantLegal(array $data, InfoEleve $infoEleve, int $numero): void
    {
        try {
            $prefix = $numero === 1 ? 'representantLegal1' : 'representantLegal2';
            $representant = $numero === 1 ? $infoEleve->getResponsableUn() : $infoEleve->getResponsableDeux();

            $hasData = false;
            foreach (['Nom', 'Prenom', 'Email', 'Telephone'] as $field) {
                if (!empty($data[$prefix . $field])) {
                    $hasData = true;
                    break;
                }
            }

            if (!$hasData) {
                return;
            }

            if (!$representant) {
                $representant = new RepresentantLegal();
                $this->entityManager->persist($representant);
                if ($numero === 1) {
                    $infoEleve->setResponsableUn($representant);
                } else {
                    $infoEleve->setResponsableDeux($representant);
                }
            }

            $representant->setNom($data[$prefix . 'Nom'] ?? '');
            $representant->setPrenom($data[$prefix . 'Prenom'] ?? '');
            $representant->setCourriel($data[$prefix . 'Email'] ?? '');
            $representant->setTelephonePerso($data[$prefix . 'Telephone'] ?? '');
            $representant->setAdresse($data[$prefix . 'Adresse'] ?? '');
            $representant->setCodePostal($data[$prefix . 'CodePostal'] ?? '');
            $representant->setCommune($data[$prefix . 'Commune'] ?? '');
            $representant->setLienEleve($data[$prefix . 'LienEleve'] ?? '');
            $representant->setPoste($data[$prefix . 'Poste'] ?? '');
            $representant->setNomEmployeur($data[$prefix . 'NomEmployeur'] ?? '');
            $representant->setAdresseEmployeur($data[$prefix . 'AdresseEmployeur'] ?? '');
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur dans handleRepresentantLegal', [
                'numero' => $numero,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleScolariteAnterieure(array $data, InfoEleve $infoEleve): void
    {
        try {
            // Année scolaire 1 - seulement si des données existent
            if (!empty($data['anneeScolairePrecedente1']) || !empty($data['etablissementPrecedent1'])) {
                $annee1 = $infoEleve->getAnneScolaireUn();
                if (!$annee1) {
                    $annee1 = new ScolariteAnterieur();
                    $this->entityManager->persist($annee1);
                    $infoEleve->setAnneScolaireUn($annee1);
                }

                $annee1->setAnneScolaire($data['anneeScolairePrecedente1'] ?? '');
                $annee1->setEtablissement($data['etablissementPrecedent1'] ?? '');
                $annee1->setClasse($data['classePrecedente1'] ?? '');
                $annee1->setOption($data['option-1'] ?? '');
                $annee1->setLVUn($data['lv1-1'] ?? '');
                $annee1->setLVDeux($data['lv2-1'] ?? '');
            }

            // Année scolaire 2 - seulement si des données existent
            if (!empty($data['anneeScolairePrecedente2']) || !empty($data['etablissementPrecedent2'])) {
                $annee2 = $infoEleve->getAnneScolaireDeux();
                if (!$annee2) {
                    $annee2 = new ScolariteAnterieur();
                    $this->entityManager->persist($annee2);
                    $infoEleve->setAnneScolaireDeux($annee2);
                }

                $annee2->setAnneScolaire($data['anneeScolairePrecedente2'] ?? '');
                $annee2->setEtablissement($data['etablissementPrecedent2'] ?? '');
                $annee2->setClasse($data['classePrecedente2'] ?? '');
                $annee2->setOption($data['option-2'] ?? '');
                $annee2->setLVUn($data['lv1-2'] ?? '');
                $annee2->setLVDeux($data['lv2-2'] ?? '');
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur dans handleScolariteAnterieure', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleAdhesion(array $data, InfoEleve $infoEleve): void
    {
        try {
            if (isset($data['adhesionAccepted'])) {
                $adhesion = $infoEleve->getAdhesion();
                if (!$adhesion) {
                    $adhesion = new Adhesion();
                    $this->entityManager->persist($adhesion);
                    $infoEleve->setAdhesion($adhesion);
                }

                $adhesion->setAccepted($data['adhesionAccepted'] === "oui" || $data['adhesionAccepted'] === true);
                $adhesion->setPaymentMethod($data['adhesionPaymentMethod'] ?? null);
                $adhesion->setImageRights($data['adhesionImageRights'] === "oui" || $data['adhesionImageRights'] === true);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur dans handleAdhesion', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Convertir les données de scolarité antérieure en tableau
     */
    private function convertScolariteAnterieurToArray(InfoEleve $infoEleve, array &$data): void
    {
        // Année scolaire 1
        if ($infoEleve->getAnneScolaireUn()) {
            $annee1 = $infoEleve->getAnneScolaireUn();
            $data['anneeScolairePrecedente1'] = $annee1->getAnneScolaire();
            $data['etablissementPrecedent1'] = $annee1->getEtablissement();
            $data['classePrecedente1'] = $annee1->getClasse();
            $data['option-1'] = $annee1->getOption();
            $data['lv1-1'] = $annee1->getLVUn();
            $data['lv2-1'] = $annee1->getLVDeux();
        }

        // Année scolaire 2
        if ($infoEleve->getAnneScolaireDeux()) {
            $annee2 = $infoEleve->getAnneScolaireDeux();
            $data['anneeScolairePrecedente2'] = $annee2->getAnneScolaire();
            $data['etablissementPrecedent2'] = $annee2->getEtablissement();
            $data['classePrecedente2'] = $annee2->getClasse();
            $data['option-2'] = $annee2->getOption();
            $data['lv1-2'] = $annee2->getLVUn();
            $data['lv2-2'] = $annee2->getLVDeux();
        }
    }

    /**
     * Vérifier si l'inscription est complète
     */
    private function isInscriptionComplete(InfoEleve $infoEleve): bool
    {
        // Vérifications de base
        if (!$infoEleve->getDateDeNaissance() || 
            !$infoEleve->getPromotion() || 
            !$infoEleve->getClasse() ||
            !$infoEleve->getSexe()) {
            return false;
        }

        // Vérifier qu'au moins un représentant légal existe
        if (!$infoEleve->getResponsableUn() && !$infoEleve->getResponsableDeux()) {
            return false;
        }

        // Vérifier l'adhésion
        if (!$infoEleve->getAdhesion() || !$infoEleve->getAdhesion()->isAccepted()) {
            return false;
        }

        return true;
    }

    /**
     * Supprimer les entités liées à InfoEleve
     */
    private function deleteRelatedEntities(InfoEleve $infoEleve): void
    {
        // Supprimer les représentants légaux
        if ($infoEleve->getResponsableUn()) {
            $this->entityManager->remove($infoEleve->getResponsableUn());
        }
        if ($infoEleve->getResponsableDeux()) {
            $this->entityManager->remove($infoEleve->getResponsableDeux());
        }

        // Supprimer les années scolaires antérieures
        if ($infoEleve->getAnneScolaireUn()) {
            $this->entityManager->remove($infoEleve->getAnneScolaireUn());
        }
        if ($infoEleve->getAnneScolaireDeux()) {
            $this->entityManager->remove($infoEleve->getAnneScolaireDeux());
        }

        // Supprimer l'adhésion
        if ($infoEleve->getAdhesion()) {
            $this->entityManager->remove($infoEleve->getAdhesion());
        }

        // Supprimer le médecin traitant
        if ($infoEleve->getMedecinTraitant()) {
            $this->entityManager->remove($infoEleve->getMedecinTraitant());
        }

                // Supprimer le responsable financier
        if ($infoEleve->getResponsableFinancier()) {
            $this->entityManager->remove($infoEleve->getResponsableFinancier());
        }

        // Supprimer la sécurité sociale
        if ($infoEleve->getSecuSociale()) {
            $this->entityManager->remove($infoEleve->getSecuSociale());
        }

        // Supprimer l'assureur
        if ($infoEleve->getAssureur()) {
            $this->entityManager->remove($infoEleve->getAssureur());
        }
    }

    /**
     * Convertir les entités en tableau pour l'affichage
     */
    private function convertEntityToArray(User $user, InfoEleve $infoEleve): array
    {
        $data = [
            // Données utilisateur
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            
            // Données InfoEleve
            'dateNaissance' => $infoEleve->getDateDeNaissance(),
            'communeNaissance' => $infoEleve->getCommuneNaissance(),
            'departement' => $infoEleve->getDepartement(),
            'nationalite' => $infoEleve->getNationalite(),
            'numSecuSocial' => $infoEleve->getNumSecuSocial(),
            'numeroMobile' => $infoEleve->getNumeroMobile(),
            'sexe' => $infoEleve->getSexe(),
            'redoublant' => $infoEleve->isRedoublant(),
            'promotion' => $infoEleve->getPromotion(),
            'regime' => $infoEleve->getRegime(),
            'transportScolaire' => $infoEleve->getTransportScolaire(),
            'immatriculationVeic' => $infoEleve->getImmattriculationVeic(),
            'dernierDiplome' => $infoEleve->getDernierDiplome(),
            'lvUn' => $infoEleve->getLVUn(),
            'lvDeux' => $infoEleve->getLVDeux(),
            'observations' => $infoEleve->getObservations(),
            'nomContacteUrgence' => $infoEleve->getNomContacteUrgence(),
            'numeroContacteUrgence' => $infoEleve->getNumeroContacteUrgence(),
            
            // Champs booléens
            'cheque' => $infoEleve->isCheque(),
            'droitImage' => $infoEleve->isDroitImage(),
            'carteVitale' => $infoEleve->getCarteVitale(),
            'photoIdentite' => $infoEleve->getPhotoIdentite(),
            'attestationIdentite' => $infoEleve->getAttestationIdentite(),
            'bourse' => $infoEleve->getBourse(),
            'attestationJDC' => $infoEleve->getAttestationJDC(),
            'attestationReusite' => $infoEleve->getAttestationReusite(),
        ];

        // Classe
        if ($infoEleve->getClasse()) {
            $data['classe'] = $infoEleve->getClasse()->getLabel();
        }

        // Représentants légaux
        $this->addRepresentantToArray($data, $infoEleve->getResponsableUn(), 'responsableUn');
        $this->addRepresentantToArray($data, $infoEleve->getResponsableDeux(), 'responsableDeux');

        // Adhésion
        if ($infoEleve->getAdhesion()) {
            $adhesion = $infoEleve->getAdhesion();
            $data['adhesionAccepted'] = $adhesion->isAccepted();
            $data['adhesionPaymentMethod'] = $adhesion->getPaymentMethod();
            $data['adhesionImageRights'] = $adhesion->getImageRights();
        }

        // Médecin traitant
        if ($infoEleve->getMedecinTraitant()) {
            $medecin = $infoEleve->getMedecinTraitant();
            $data['medecinTraitantNom'] = $medecin->getNom();
            $data['medecinTraitantTelephone'] = $medecin->getNumero();
            $data['medecinTraitantAdresse'] = $medecin->getAdresse();
        }

        // Responsable financier
        if ($infoEleve->getResponsableFinancier()) {
            $resp = $infoEleve->getResponsableFinancier();
            $data['responsableFinancierNom'] = $resp->getNom();
            $data['responsableFinancierPrenom'] = $resp->getPrenom();
            $data['responsableFinancierNomEmployeur'] = $resp->getNomEmployeur();
            $data['responsableFinancierAdresseEmployeur'] = $resp->getAdresseEmployeur();
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

        return $data;
    }

    /**
     * Ajouter les données d'un représentant au tableau
     */
    private function addRepresentantToArray(array &$data, ?RepresentantLegal $representant, string $prefix): void
    {
        if ($representant) {
            $data[$prefix . 'Nom'] = $representant->getNom();
            $data[$prefix . 'Prenom'] = $representant->getPrenom();
            $data[$prefix . 'Adresse'] = $representant->getAdresse();
            $data[$prefix . 'CodePostal'] = $representant->getCodePostal();
            $data[$prefix . 'Commune'] = $representant->getCommune();
            $data[$prefix . 'TelephoneFixe'] = $representant->getTelephoneFixe();
            $data[$prefix . 'TelephonePerso'] = $representant->getTelephonePerso();
            $data[$prefix . 'Courriel'] = $representant->getCourriel();
            $data[$prefix . 'Profession'] = $representant->getPoste();
            $data[$prefix . 'LieuTravail'] = $representant->getAdresseEmployeur();
            $data[$prefix . 'TelephoneTravail'] = $representant->getTelephonePro();
            $data[$prefix . 'ComAddrAsso'] = $representant->getComAddrAsso();
        }
    }
}