<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Classe;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class InscriptionType extends AbstractType
{
    private const STEPS = [
        1 => 'Informations personnelles de l\'élève',
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

    private const TOTAL_STEPS = 10;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $step = $options['step'] ?? 1;

        if (!$this->isValidStep($step)) {
            throw new \InvalidArgumentException(sprintf('Étape invalide: %d. Les étapes valides sont: %s', $step, implode(', ', array_keys(self::STEPS))));
        }

        $this->buildStepForm($builder, $step, $options);
    }

    private function isValidStep(int $step): bool
    {
        return array_key_exists($step, self::STEPS);
    }

    private function buildStepForm(FormBuilderInterface $builder, int $step, array $options): void
    {
        switch ($step) {
            case 1:
                $this->buildStep1($builder);
                break;
            case 2:
                $this->buildStep2($builder);
                break;
            case 3:
                $this->buildStep3($builder);
                break;
            case 4:
                $this->buildStep4($builder);
                break;
            case 5:
                $this->buildStep5($builder);
                break;
            case 6:
                $this->buildStep6($builder);
                break;
            case 7:
                $this->buildStep7($builder);
                break;
            case 8:
                $this->buildStep8($builder);
                break;
            case 9:
                $this->buildStep9($builder, $options);
                break;
            case 10:
                $this->buildStep10($builder);
                break;
        }
    }

    private function buildStep1(FormBuilderInterface $builder): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de famille *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Entrez le nom de famille'],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom de famille est obligatoire']),
                    new Length(['min' => 2, 'max' => 50])
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Entrez le prénom'],
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est obligatoire']),
                    new Length(['min' => 2, 'max' => 50])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'email@exemple.fr'],
                'constraints' => [
                    new NotBlank(['message' => 'L\'adresse e-mail est obligatoire']),
                    new Email(['message' => 'L\'adresse e-mail n\'est pas valide'])
                ]
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance *',
                'widget' => 'single_text',
                'html5' => true,
                'required' => true,
                'attr' => ['class' => 'fr-input'],
                'constraints' => [
                    new NotBlank(['message' => 'La date de naissance est obligatoire'])
                ]
            ])
            ->add('sexe', ChoiceType::class, [
                'label' => 'Sexe *',
                'choices' => ['Sélectionnez...' => null, 'Masculin' => 'M', 'Féminin' => 'F'],
                'attr' => ['class' => 'fr-select'],
                'constraints' => [new NotBlank(['message' => 'Le sexe est obligatoire'])]
            ])
            ->add('nationalite', TextType::class, [
                'label' => 'Nationalité *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: Française'],
                'constraints' => [
                    new NotBlank(['message' => 'La nationalité est obligatoire']),
                    new Length(['max' => 50])
                ]
            ])
            ->add('departement', TextType::class, [
                'label' => 'Département de naissance *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: 75 - Paris'],
                'constraints' => [
                    new NotBlank(['message' => 'Le département de naissance est obligatoire']),
                    new Length(['max' => 100])
                ]
            ])
            ->add('communeNaissance', TextType::class, [
                'label' => 'Commune de naissance *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: Paris'],
                'constraints' => [
                    new NotBlank(['message' => 'La commune de naissance est obligatoire']),
                    new Length(['max' => 100])
                ]
            ])
            ->add('numSecuSocial', TextType::class, [
                'label' => 'Numéro de sécurité sociale',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => '123456789012345']
            ]);
    }

    private function buildStep2(FormBuilderInterface $builder): void
    {
        $builder
            ->add('numeroMobile', TelType::class, [
                'label' => 'Numéro de mobile *',
                'attr' => ['class' => 'fr-input', 'placeholder' => '0612345678'],
                'constraints' => [
                    new NotBlank(['message' => 'Le numéro de mobile est obligatoire']),
                    new Regex(['pattern' => '/^(?:\+33|0)[1-9](?:[0-9]{8})$/', 'message' => 'Le numéro de téléphone n\'est pas valide'])
                ]
            ])
            ->add('accepterSms', CheckboxType::class, [
                'label' => 'J\'accepte de recevoir des SMS',
                'required' => false,
                'attr' => ['class' => 'fr-checkbox']
            ])
            ->add('nomContacteUrgence', TextType::class, [
                'label' => 'Nom du contact d\'urgence *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Nom et prénom du contact'],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom du contact d\'urgence est obligatoire']),
                    new Length(['max' => 100])
                ]
            ])
            ->add('numeroContacteUrgence', TelType::class, [
                'label' => 'Numéro du contact d\'urgence *',
                'attr' => ['class' => 'fr-input', 'placeholder' => '0612345678'],
                'constraints' => [
                    new NotBlank(['message' => 'Le numéro du contact d\'urgence est obligatoire']),
                    new Regex(['pattern' => '/^(?:\+33|0)[1-9](?:[0-9]{8})$/', 'message' => 'Le numéro de téléphone n\'est pas valide'])
                ]
            ]);
    }

    private function buildStep3(FormBuilderInterface $builder): void
    {
        $builder
            ->add('classe', EntityType::class, [
                'class' => Classe::class,
                'choice_label' => 'label',
                'label' => 'Classe demandée *',
                'placeholder' => 'Sélectionnez une classe',
                'attr' => ['class' => 'fr-select'],
                'constraints' => [new NotBlank(['message' => 'La classe est obligatoire'])]
            ])
            ->add('promotion', TextType::class, [
                'label' => 'Promotion',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: 2024-2025']
            ])
            ->add('regime', ChoiceType::class, [
                'label' => 'Régime scolaire *',
                'choices' => [
                    'Sélectionnez...' => null,
                    'Externe' => 'externe',
                    'Demi-pensionnaire' => 'demi_pensionnaire',
                    'Interne' => 'interne'
                ],
                'attr' => ['class' => 'fr-select'],
                'constraints' => [new NotBlank(['message' => 'Le régime scolaire est obligatoire'])]
            ])
            ->add('redoublant', CheckboxType::class, [
                'label' => 'L\'élève redouble cette classe',
                'required' => false,
                'attr' => ['class' => 'fr-checkbox']
            ])
            ->add('lvUn', TextType::class, [
                'label' => 'Langue vivante 1 *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: Anglais, Espagnol, Allemand...'],
                'constraints' => [
                    new NotBlank(['message' => 'La langue vivante 1 est obligatoire']),
                    new Length(['max' => 50])
                ]
            ])
            ->add('lvDeux', TextType::class, [
                'label' => 'Langue vivante 2',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: Allemand, Italien...'],
                'constraints' => [new Length(['max' => 50])]
            ])
            ->add('dernierDiplome', TextType::class, [
                'label' => 'Dernier diplôme obtenu',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: Brevet, CAP...']
            ])
            ->add('transportScolaire', ChoiceType::class, [
                'label' => 'Transport scolaire',
                'choices' => [
                    'Voiture' => 'Voiture',
                    'Bus' => 'Bus',
                    'Train' => 'Train',
                    'Autre' => 'Autre',
                ],
                'expanded' => true, // pour des radio buttons
                'required' => false,
                'attr' => ['class' => 'fr-radio-group']
            ])
            ->add('immatriculationVeic', TextType::class, [
                'label' => 'Immatriculation du véhicule',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: AB-123-CD']
            ]);
    }

    private function buildStep4(FormBuilderInterface $builder): void
    {
        $builder
            ->add('representantLegal1Nom', TextType::class, [
                'label' => 'Nom *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Nom de famille'],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom du représentant légal est obligatoire']),
                    new Length(['max' => 100])
                ]
            ])
            ->add('representantLegal1Prenom', TextType::class, [
                'label' => 'Prénom *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Prénom'],
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom du représentant légal est obligatoire']),
                    new Length(['max' => 100])
                ]
            ])
            ->add('representantLegal1Courriel', EmailType::class, [
                'label' => 'Courriel',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'email@exemple.fr'],
                'constraints' => [new Email(['message' => 'L\'adresse e-mail n\'est pas valide'])]
            ])
            ->add('representantLegal1Telephone', TelType::class, [
                'label' => 'Téléphone mobile',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => '0612345678'],
                'constraints' => [
                    new Regex(['pattern' => '/^(?:\+33|0)[1-9](?:[0-9]{8})$/', 'message' => 'Le numéro de téléphone n\'est pas valide'])
                ]
            ])
            ->add('representantLegal1TelephoneFixe', TelType::class, [
                'label' => 'Téléphone fixe',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => '0123456789'],
                'constraints' => [
                    new Regex(['pattern' => '/^(?:\+33|0)[1-9](?:[0-9]{8})$/', 'message' => 'Le numéro de téléphone n\'est pas valide'])
                ]
            ])
            ->add('representantLegal1TelephonePro', TelType::class, [
                'label' => 'Téléphone professionnel',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => '0123456789'],
                'constraints' => [
                    new Regex(['pattern' => '/^(?:\+33|0)[1-9](?:[0-9]{8})$/', 'message' => 'Le numéro de téléphone n\'est pas valide'])
                ]
            ])
            ->add('representantLegal1Adresse', TextareaType::class, [
                'label' => 'Adresse *',
                'attr' => ['class' => 'fr-input', 'rows' => 2, 'placeholder' => 'Adresse complète'],
                'constraints' => [
                    new NotBlank(['message' => 'L\'adresse du représentant légal est obligatoire']),
                    new Length(['max' => 300])
                ]
            ])
            ->add('representantLegal1CodePostal', TextType::class, [
                'label' => 'Code postal *',
                'attr' => ['class' => 'fr-input', 'placeholder' => '75001'],
                'constraints' => [
                    new NotBlank(['message' => 'Le code postal est obligatoire']),
                    new Regex(['pattern' => '/^\d{5}$/', 'message' => 'Le code postal doit contenir exactement 5 chiffres'])
                ]
            ])
            ->add('representantLegal1Commune', TextType::class, [
                'label' => 'Commune *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: Paris'],
                'constraints' => [
                    new NotBlank(['message' => 'La commune est obligatoire']),
                    new Length(['max' => 100])
                ]
            ])
            ->add('representantLegal1Poste', TextType::class, [
                'label' => 'Poste/Profession',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Profession exercée']
            ])
            ->add('representantLegal1LienEleve', ChoiceType::class, [
                'label' => 'Lien avec l\'élève *',
                'choices' => [
                    'Sélectionnez...' => null,
                    'Père' => 'pere',
                    'Mère' => 'mere',
                    'Tuteur/Tutrice' => 'tuteur',
                    'Autre' => 'autre'
                ],
                'attr' => ['class' => 'fr-select'],
                'constraints' => [new NotBlank(['message' => 'Le lien avec l\'élève est obligatoire'])]
            ])
            ->add('representantLegal1NomEmployeur', TextType::class, [
                'label' => 'Nom de l\'employeur',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Nom de l\'entreprise']
            ])
            ->add('representantLegal1AdresseEmployeur', TextareaType::class, [
                'label' => 'Adresse de l\'employeur',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'rows' => 2, 'placeholder' => 'Adresse complète de l\'employeur']
            ])
            ->add('representantLegal1Sms', CheckboxType::class, [
                'label' => 'Autoriser le représentant légal 1 à recevoir des sms',
                'required' => false,
                'attr' => ['class' => 'fr-checkbox']
            ]);
    }

    private function buildStep5(FormBuilderInterface $builder): void
    {
        $builder
            ->add('representantLegal2Nom', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Nom de famille']
            ])
            ->add('representantLegal2Prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Prénom']
            ])
            ->add('representantLegal2Courriel', EmailType::class, [
                'label' => 'Courriel',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'email@exemple.fr'],
                'constraints' => [new Email(['message' => 'L\'adresse e-mail n\'est pas valide'])]
            ])
            ->add('representantLegal2Telephone', TelType::class, [
                'label' => 'Téléphone mobile',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => '06 12 34 56 78'],
                'constraints' => [
                    new Regex(['pattern' => '/^(?:\+33|0)[1-9](?:[0-9]{8})$/', 'message' => 'Le numéro de téléphone n\'est pas valide'])
                ]
            ])
            ->add('representantLegal2TelephoneFixe', TelType::class, [
                'label' => 'Téléphone fixe',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => '01 23 45 67 89'],
                'constraints' => [
                    new Regex(['pattern' => '/^(?:\+33|0)[1-9](?:[0-9]{8})$/', 'message' => 'Le numéro de téléphone n\'est pas valide'])
                ]
            ])
            ->add('representantLegal2TelephonePro', TelType::class, [
                'label' => 'Téléphone professionnel',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => '01 23 45 67 89'],
                'constraints' => [
                    new Regex(['pattern' => '/^(?:\+33|0)[1-9](?:[0-9]{8})$/', 'message' => 'Le numéro de téléphone n\'est pas valide'])
                ]
            ])
            ->add('representantLegal2Adresse', TextareaType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'rows' => 2, 'placeholder' => 'Adresse complète']
            ])
            ->add('representantLegal2CodePostal', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => '75001'],
                'constraints' => [
                    new Regex(['pattern' => '/^\d{5}$/', 'message' => 'Le code postal doit contenir exactement 5 chiffres'])
                ]
            ])
            ->add('representantLegal2Commune', TextType::class, [
                'label' => 'Commune',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: Paris']
            ])
            ->add('representantLegal2Poste', TextType::class, [
                'label' => 'Poste/Profession',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Profession exercée']
            ])
            ->add('representantLegal2LienEleve', ChoiceType::class, [
                'label' => 'Lien avec l\'élève',
                'choices' => [
                    'Sélectionnez...' => null,
                    'Père' => 'pere',
                    'Mère' => 'mere',
                    'Tuteur/Tutrice' => 'tuteur',
                    'Autre' => 'autre'
                ],
                'required' => false,
                'attr' => ['class' => 'fr-select']
            ])
            ->add('representantLegal2NomEmployeur', TextType::class, [
                'label' => 'Nom de l\'employeur',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Nom de l\'entreprise']
            ])
            ->add('representantLegal2AdresseEmployeur', TextareaType::class, [
                'label' => 'Adresse de l\'employeur',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'rows' => 2, 'placeholder' => 'Adresse complète de l\'employeur']
            ])
            ->add('representantLegal2Sms', CheckboxType::class, [
                'label' => 'Autoriser le représentant légal 2 à recevoir des sms',
                'required' => false,
                'attr' => ['class' => 'fr-checkbox']
            ]);
    }

    private function buildStep6(FormBuilderInterface $builder): void
    {
        $builder
            ->add('etablissementPrecedent1', TextType::class, [
                'label' => 'Établissement précédent *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Nom complet de l\'établissement'],
                'constraints' => [
                    new NotBlank(['message' => 'L\'établissement précédent est obligatoire']),
                    new Length(['max' => 150])
                ]
            ])
            ->add('classePrecedente1', TextType::class, [
                'label' => 'Classe précédente *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: 3ème, 2nde, 1ère S...'],
                'constraints' => [
                    new NotBlank(['message' => 'La classe précédente est obligatoire']),
                    new Length(['max' => 50])
                ]
            ])
            ->add('anneeScolairePrecedente1', TextType::class, [
                'label' => 'Année scolaire précédente *',
                'attr' => ['class' => 'fr-input', 'placeholder' => '2023-2024'],
                'constraints' => [
                    new NotBlank(['message' => 'L\'année scolaire précédente est obligatoire']),
                    new Regex(['pattern' => '/^\d{4}-\d{4}$/', 'message' => 'L\'année scolaire doit être au format YYYY-YYYY (ex: 2023-2024)'])
                ]
            ])
            ->add('lvUnPrecedente1', TextType::class, [
                'label' => 'Langue vivante 1 précédente',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: Anglais, Espagnol...']
            ])
            ->add('lvDeuxPrecedente1', TextType::class, [
                'label' => 'Langue vivante 2 précédente',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: Allemand, Italien...']
            ])
            ->add('optionPrecedente1', TextType::class, [
                'label' => 'Option précédente (année N-1)',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Maths, Sciences...'],
            ])
            ->add('etablissementPrecedent2', TextType::class, [
                'label' => 'Établissement précédent (année N-2)',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Nom complet de l\'établissement']
            ])
            ->add('classePrecedente2', TextType::class, [
                'label' => 'Classe précédente (année N-2)',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: 4ème, 3ème...']
            ])
            ->add('anneeScolairePrecedente2', TextType::class, [
                'label' => 'Année scolaire (année N-2)',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => '2022-2023'],
                'constraints' => [
                    new Regex(['pattern' => '/^\d{4}-\d{4}$/', 'message' => 'L\'année scolaire doit être au format YYYY-YYYY (ex: 2022-2023)'])
                ]
            ])
            ->add('lvUnPrecedente2', TextType::class, [
                'label' => 'Langue vivante 1 précédente (année N-2)',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: Anglais, Espagnol...']
            ])
            ->add('lvDeuxPrecedente2', TextType::class, [
                'label' => 'Langue vivante 2 précédente (année N-2)',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: Allemand, Italien...']
            ])
            ->add('optionPrecedente2', TextType::class, [
                'label' => 'Option précédente (année N-2)',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Maths, Sciences...'],
            ]);
    }

    private function buildStep7(FormBuilderInterface $builder): void
    {
        $builder
            ->add('medecinTraitantNom', TextType::class, [
                'label' => 'Nom du médecin traitant',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Dr. Martin Dupont']
            ])
            ->add('medecinTraitantTelephone', TelType::class, [
                'label' => 'Téléphone du médecin traitant',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => '01 23 45 67 89'],
                'constraints' => [
                    new Regex(['pattern' => '/^(?:\+33|0)[1-9](?:[0-9]{8})$/', 'message' => 'Le numéro de téléphone n\'est pas valide'])
                ]
            ])
            ->add('medecinTraitantAdresse', TextareaType::class, [
                'label' => 'Adresse du médecin traitant',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'rows' => 2, 'placeholder' => 'Adresse complète du cabinet médical']
            ])
            ->add('dernierRappelAntitetanique', DateType::class, [
                'label' => 'Dernier rappel antitétanique',
                'widget' => 'single_text',
                'html5' => true,
                'required' => false,
                'attr' => ['class' => 'fr-input'],
            ])
            ->add('observations', TextareaType::class, [
                'label' => 'Observations médicales',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'rows' => 3, 'placeholder' => 'Informations médicales importantes (allergies, traitements, etc.)']
            ])
            ->add('secuSocialeNom', TextType::class, [
                'label' => 'Nom de la sécurité sociale',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: CPAM Paris']
            ])
            ->add('secuSocialeAdresse', TextareaType::class, [
                'label' => 'Adresse de la sécurité sociale',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'rows' => 2, 'placeholder' => 'Adresse complète']
            ])
            ->add('assureurNom', TextType::class, [
                'label' => 'Nom de l\'assureur',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Ex: MAIF, MAAF, etc.']
            ])
            ->add('assureurNumeroAssurance', TextType::class, [
                'label' => 'Numéro d\'assurance',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Numéro de police d\'assurance']
            ])
            ->add('assureurAdresse', TextareaType::class, [
                'label' => 'Adresse de l\'assureur',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'rows' => 2, 'placeholder' => 'Adresse complète de l\'assureur']
            ]);
    }

    private function buildStep8(FormBuilderInterface $builder): void
    {
        $builder
            ->add('responsableFinancierNom', TextType::class, [
                'label' => 'Nom du responsable financier *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Nom de famille'],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom du responsable financier est obligatoire']),
                    new Length(['max' => 100])
                ]
            ])
            ->add('responsableFinancierPrenom', TextType::class, [
                'label' => 'Prénom du responsable financier *',
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Prénom'],
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom du responsable financier est obligatoire']),
                    new Length(['max' => 100])
                ]
            ])
            ->add('responsableFinancierNomEmployeur', TextType::class, [
                'label' => 'Nom de l\'employeur',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'placeholder' => 'Nom de l\'entreprise']
            ])
            ->add('responsableFinancierAdresseEmployeur', TextareaType::class, [
                'label' => 'Adresse de l\'employeur',
                'required' => false,
                'attr' => ['class' => 'fr-input', 'rows' => 2, 'placeholder' => 'Adresse complète de l\'employeur']
            ]);
    }

    private function buildStep9(FormBuilderInterface $builder, array $options): void 
    { }

    private function buildStep10(FormBuilderInterface $builder): void 
    {
        $data = $builder->getData() ?: [];
        
        $builder
            ->add('cheque', CheckboxType::class, [
                'label' => 'Chèque de règlement',
                'required' => false,
                'attr' => ['class' => 'fr-checkbox'],
                'mapped' => false,
                'data' => $data['cheque'] ?? false // Valeur par défaut
            ])
           ->add('adhesionImageRights', CheckboxType::class, [
                'label' => 'J\'accepte la cession des droits à l\'image *',
                'attr' => ['class' => 'fr-checkbox'],
                'mapped' => false,
                'data' => $data['adhesionImageRights'] ?? false,
                'constraints' => [
                    new NotBlank(['message' => 'Vous devez accepter la cession des droits à l\'image'])
                ]
            ])
            ->add('adhesionAccepted', CheckboxType::class, [
                'label' => 'J\'accepte les conditions d\'adhésion *',
                'attr' => ['class' => 'fr-checkbox'],
                'mapped' => false,
                'data' => isset($data['adhesionAccepted']) ? 
                        ($data['adhesionAccepted'] === 'oui' || $data['adhesionAccepted'] === true) : false,
                'constraints' => [
                    new NotBlank(['message' => 'Vous devez accepter les conditions d\'adhésion'])
                ]
                ]);
            // ->add('adhesionPaymentMethod', ChoiceType::class, [
            //     'label' => 'Mode de paiement de l\'adhésion *',
            //     'choices' => [
            //         'Sélectionnez...' => null,
            //         'Chèque' => 'cheque',
            //         'Virement bancaire' => 'virement',
            //         'Espèces' => 'especes',
            //         'Carte bancaire' => 'carte'
            //     ],
            //     'attr' => ['class' => 'fr-select'],
            //     'mapped' => false,
            //     'data' => $data['adhesionPaymentMethod'] ?? null,
            //     'constraints' => [
            //         new NotBlank(['message' => 'Le mode de paiement est obligatoire'])
            //     ]
            // ])
            // ->add('adhesionImageRights', CheckboxType::class, [
            //     'label' => 'J\'accepte la cession des droits à l\'image *',
            //     'attr' => ['class' => 'fr-checkbox'],
            //     'mapped' => false,
            //     'data' => $data['adhesionImageRights'] ?? false,
            //     'constraints' => [
            //         new NotBlank(['message' => 'Vous devez accepter la cession des droits à l\'image'])
            //     ]
            // ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'step' => 1,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'inscription_form',
        ]);

        $resolver->setAllowedTypes('step', 'int');
        $resolver->setAllowedValues('step', array_keys(self::STEPS));
    }

    public function getStepLabel(int $step): string
    {
        return self::STEPS[$step] ?? 'Étape inconnue';
    }

    public function getTotalSteps(): int
    {
        return self::TOTAL_STEPS;
    }

    public function getAllSteps(): array
    {
        return self::STEPS;
    }

    public function isLastStep(int $step): bool
    {
        return $step === self::TOTAL_STEPS;
    }

    public function getNextStep(int $step): ?int
    {
        return $this->isLastStep($step) ? null : $step + 1;
    }

    public function getPreviousStep(int $step): ?int
    {
        return $step === 1 ? null : $step - 1;
    }
}