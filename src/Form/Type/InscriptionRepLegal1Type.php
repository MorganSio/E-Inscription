<?php

namespace App\Form\Type;

use App\Entity\Inscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InscriptionRepLegal1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('repLegal1Nom', TextType::class, [
                'label' => 'Nom du représentant légal',
                'attr' => ['class' => 'form-control']
            ])
            ->add('repLegal1Prenom', TextType::class, [
                'label' => 'Prénom du représentant légal',
                'attr' => ['class' => 'form-control']
            ])
            ->add('repLegal1Email', EmailType::class, [
                'label' => 'Email du représentant légal',
                'attr' => ['class' => 'form-control']
            ])
            ->add('repLegal1Telephone', TelType::class, [
                'label' => 'Téléphone du représentant légal',
                'attr' => ['class' => 'form-control']
            ])
            ->add('repLegal1Lien', TextType::class, [
                'label' => 'Lien de parenté',
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
