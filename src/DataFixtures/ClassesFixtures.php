<?php
// src/DataFixtures/ClasseFixtures.php

namespace App\DataFixtures;

use App\Entity\Classe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ClasseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Version simple - seulement avec le label
        $classesLabels = [
            'BTS SIO',
            'BTS NDRC',
            'BTS CG', 
            'BTS MCO'
        ];

        foreach ($classesLabels as $label) {
            $classe = new Classe();
            $classe->setLabel($label);
            
            $manager->persist($classe);
        }

        $manager->flush();
    }
}