<?php

namespace App\Flow;

use App\Entity\Inscription;
use App\Form\Type\InscriptionInfosEleveType;
use App\Form\Type\InscriptionRepLegal1Type;
use App\Form\Type\InscriptionRepLegal2Type;
use App\Form\Type\InscriptionScolariteType;
use App\Form\Type\InscriptionDocumentsType;
use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;

class InscriptionFlow extends FormFlow
{
    public function getName(): string
    {
        return 'inscription';
    }

    protected function loadStepsConfig(): array
    {
        return [
            [
                'label' => 'Informations de l\'élève',
                'form_type' => InscriptionInfosEleveType::class,
            ],
            [
                'label' => 'Représentant légal 1',
                'form_type' => InscriptionRepLegal1Type::class,
            ],
            [
                'label' => 'Représentant légal 2 (optionnel)',
                'form_type' => InscriptionRepLegal2Type::class,
                'skip' => function($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->getRepLegal2Nom() === null;
                }
            ],
        ];
    }

    public function getFormOptions($step, array $options = []): array
    {
        $options = parent::getFormOptions($step, $options);

        $options['validation_groups'] = ['Default', $this->getValidationGroupForStep($step)];

        return $options;
    }

    private function getValidationGroupForStep($step): string
    {
        switch ($step) {
            case 1:
                return 'flow_infos_eleve';
            case 2:
                return 'flow_rep_legal1';
            case 3:
                return 'flow_rep_legal2';
            default:
                return 'Default';
        }
    }
}