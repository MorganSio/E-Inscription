<?php

namespace App\Controller;

use App\Entity\Inscription;
use App\Entity\User;
use App\Flow\InscriptionFlow;
use App\Repository\InscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class InscriptionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InscriptionRepository $inscriptionRepository
    ) {}

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        // Si l'utilisateur est connecté, rediriger vers le tableau de bord
        if ($this->getUser()) {
            return $this->redirectToRoute('app_inscription_dashboard');
        }

        return $this->render('inscription/home.html.twig');
    }

    #[Route('/inscription/dashboard', name: 'app_inscription_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Chercher l'inscription existante ou créer une nouvelle
        $inscription = $this->inscriptionRepository->findOneBy(['user' => $user]);
        
        if (!$inscription) {
            $inscription = new Inscription();
            $inscription->setUser($user);
            $this->entityManager->persist($inscription);
            $this->entityManager->flush();
        }

        return $this->render('inscription/dashboard.html.twig', [
            'inscription' => $inscription,
            'user' => $user,
        ]);
    }

    #[Route('/inscription/formulaire', name: 'app_inscription_form')]
    #[IsGranted('ROLE_USER')]
    public function inscriptionForm(Request $request, InscriptionFlow $flow): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer ou créer l'inscription
        $inscription = $this->inscriptionRepository->findOneBy(['user' => $user]);
        
        if (!$inscription) {
            $inscription = new Inscription();
            $inscription->setUser($user);
        }

        $flow->bind($inscription);

        $form = $flow->createForm();
        
        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                $form = $flow->createForm();
            } else {
                // Formulaire terminé, sauvegarder en base
                $inscription->setIsComplete(true);
                $inscription->setUpdatedAt(new \DateTime());
                
                $this->entityManager->persist($inscription);
                $this->entityManager->flush();

                $this->addFlash('success', 'Votre inscription a été enregistrée avec succès !');
                
                return $this->redirectToRoute('app_inscription_dashboard');
            }
        }

        return $this->render('inscription/form.html.twig', [
            'form' => $form->createView(),
            'flow' => $flow,
            'inscription' => $inscription,
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
    public function resumeInscription(int $stepNumber, InscriptionFlow $flow): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $inscription = $this->inscriptionRepository->findOneBy(['user' => $user]);
        
        if (!$inscription) {
            return $this->redirectToRoute('app_inscription_form');
        }

        $flow->bind($inscription);
        $flow->nextStep($stepNumber);
        
        return $this->redirectToRoute('app_inscription_form');
    }
}