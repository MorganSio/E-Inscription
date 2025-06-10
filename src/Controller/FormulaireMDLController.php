<?php

namespace App\Controller;

use Mpdf\Tag\Dd;
use App\Entity\User;
use App\Form\MdlForm;
use App\Entity\Adhesion;
use App\Entity\InfoEleve;
use App\Repository\InfoEleveRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FormulaireMDLController extends AbstractController
{
    #[Route('/fiche-mdl', name: 'fiche-mdl')]
    public function index(): Response
    {
        $task = new Adhesion();
        $form = $this->createForm( MdlForm::class, $task);

        return $this->render('forms/mdl.html.twig', [
            'controller_name' => 'FormulaireMDLController',
            'form' => $form,
        ]);
    }
}
