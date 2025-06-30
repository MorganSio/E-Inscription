<?php

namespace App\Form\Type;

use App\Entity\Inscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InscriptionRepLegal2Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('repLegal2Nom', TextType::class, [
                'label' => 'Nom du 2ème représentant légal',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('repLegal2Prenom', TextType::class, [
                'label' => 'Prénom du 2ème représentant légal',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('repLegal2Email', EmailType::class, [
                'label' => 'Email du 2ème représentant légal',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('repLegal2Telephone', TelType::class, [
                'label' => 'Téléphone du 2ème représentant légal',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('repLegal2Lien', TextType::class, [
                'label' => 'Lien de parenté',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Père, Mère, Tuteur, etc.']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Inscription::class,
        ]);
    }
}