<?php

namespace App\Form;

use App\Entity\Adhesion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MdlForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('accepted', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'label' => 'Voulez-vous adhérer à la MDL ?',
                'choices' => [
                    'Oui, j\'accepte' => true,
                    'Non, je refuse' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
            ])
            ->add('paymentMethod', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Chèque' => 'cheque',
                    'Espèces' => 'especes',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
            ])
            ->add('imageRights', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'label' => 'Autorisation droit à l\'image',
                'choices' => [
                    'Autorise' => true,
                    'N\'autorise pas' => false,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Adhesion::class,
        ]);
    }
}
