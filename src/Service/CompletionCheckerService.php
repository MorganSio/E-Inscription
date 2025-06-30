<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\InfoEleve;

class CompletionCheckerService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Analyse la complétude d'une entité
     * 
     * @param object $entity
     * @param array $excludedFields
     * @param bool $checkRelations 
     * @return array
     */
    public function getEntityCompletion(object $entity = null, array $excludedFields = ['id'], bool $checkRelations = true): array
    {
        if (!$entity) {
            return ['complete' => 0, 'incomplete' => 0, 'fields' => []];
        }

        $completion = ['complete' => 0, 'incomplete' => 0, 'fields' => []];
        $metadata = $this->entityManager->getClassMetadata(get_class($entity));
        
        // Vérification des champs simples
        foreach ($metadata->getFieldNames() as $field) {
            if (in_array($field, $excludedFields)) continue;

            $getter = 'get' . ucfirst($field);
            if (method_exists($entity, $getter)) {
                $value = $entity->$getter();
                
                $isComplete = $this->isValueComplete($value);
                
                if ($isComplete) {
                    $completion['complete']++;
                } else {
                    $completion['incomplete']++;
                    $completion['fields'][] = $field;
                }
            }
        }
        
        // Vérification des relations si demandé
        if ($checkRelations) {
            foreach ($metadata->getAssociationNames() as $association) {
                if (in_array($association, $excludedFields)) continue;
                
                $getter = 'get' . ucfirst($association);
                if (method_exists($entity, $getter)) {
                    $relatedEntity = $entity->$getter();
                    
                    // Vérifie si la relation existe
                    if ($relatedEntity) {
                        $completion['complete']++;
                    } else {
                        $completion['incomplete']++;
                        $completion['fields'][] = $association;
                    }
                }
            }
        }
        
        return $completion;
    }
    
    /**
     * Vérifie si une valeur est complète (non vide)
     */
    private function isValueComplete($value): bool
    {
        if ($value === null) return false;
        if (is_string($value) && trim($value) === '') return false;
        if (is_array($value) && count($value) === 0) return false;
        if ($value instanceof \DateTimeInterface) return true;
        if (is_bool($value)) return true;
        if (is_object($value)) return true;

        return true;
    }

    /**
     * Analyse la complétude des données d'un élève
     * 
     * @param User $user L'utilisateur dont on veut analyser les données
     * @return array Résultats détaillés de l'analyse
     */
    public function analyzeStudentDataCompletion(User $user): array
    {
        $infoEleve = $user->getInfoEleve();
        $result = [
            'global' => ['complete' => 0, 'incomplete' => 0],
            'sections' => [],
            'missing_fields' => []
        ];

        // Liste des entités à vérifier
        $entitiesToCheck = [
            'informations_personnelles' => $infoEleve,
            // 'responsable_financier' => $infoEleve?->getResponsableFinancier(),
            'responsable_1' => $infoEleve?->getResponsableUn(),
            'responsable_2' => $infoEleve?->getResponsableDeux(),
            'medecin_traitant' => $infoEleve?->getMedecinTraitant(),
            'assurance_scolaire' => $infoEleve?->getAssureur(),
            'regime_cantine' => $infoEleve?->getRegime(),
            'securite_sociale' => $infoEleve?->getSecuSociale(),
            'scolarite_anterieure_1' => $infoEleve?->getAnneScolaireUn(),
            'scolarite_anterieure_2' => $infoEleve?->getAnneScolaireDeux(),
        ];

        // Analyse chaque entité
        foreach ($entitiesToCheck as $section => $entity) {
            if (!is_object($entity)) {
                $result['sections'][$section] = [
                    'complete' => 0,
                    'incomplete' => 0,
                    'fields' => []
                ];
                continue;
            }

            $completion = $this->getEntityCompletion($entity);
            $result['sections'][$section] = $completion;

            $result['global']['complete'] += $completion['complete'];
            $result['global']['incomplete'] += $completion['incomplete'];

            foreach ($completion['fields'] as $field) {
                $result['missing_fields'][] = [
                    'section' => $section,
                    'field' => $field
                ];
            }
        }

        $total = $result['global']['complete'] + $result['global']['incomplete'];
        $result['completion_percentage'] = $total > 0 
            ? round(($result['global']['complete'] / $total) * 100, 2) 
            : 0;

        return $result;
    }

    /**
     * Analyse globale pour tous les utilisateurs
     * 
     * @return array Statistiques globales par section/champ
     */
    public function getGlobalStatistics(): array
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $globalStats = [
            'sections' => [],
            'missing_fields' => [],
            'completion_average' => 0,
            'total_users' => count($users)
        ];

        $totalCompletionPercentage = 0;

        foreach ($users as $user) {
            $userData = $this->analyzeStudentDataCompletion($user);
            $totalCompletionPercentage += $userData['completion_percentage'];

            foreach ($userData['sections'] as $section => $data) {
                if (!isset($globalStats['sections'][$section])) {
                    $globalStats['sections'][$section] = [
                        'complete' => 0,
                        'incomplete' => 0,
                        'users_missing' => 0
                    ];
                }

                $globalStats['sections'][$section]['complete'] += $data['complete'];
                $globalStats['sections'][$section]['incomplete'] += $data['incomplete'];

                if (count($data['fields']) > 0) {
                    $globalStats['sections'][$section]['users_missing']++;
                }
            }

            foreach ($userData['missing_fields'] as $missingField) {
                $key = $missingField['section'] . '.' . $missingField['field'];

                if (!isset($globalStats['missing_fields'][$key])) {
                    $globalStats['missing_fields'][$key] = [
                        'section' => $missingField['section'],
                        'field' => $missingField['field'],
                        'count' => 0
                    ];
                }

                $globalStats['missing_fields'][$key]['count']++;
            }
        }

        if (count($users) > 0) {
            $globalStats['completion_average'] = round($totalCompletionPercentage / count($users), 2);
        }

        usort($globalStats['missing_fields'], function($a, $b) {
            return $b['count'] - $a['count'];
        });

        return $globalStats;
    }

    /**
     * Vérifie si les documents requis sont présents
     * 
     * @param User $user Utilisateur concerné
     * @param string $projectDir Chemin racine du projet
     * @return array Informations sur les documents
     */
    public function checkRequiredDocuments(User $user, string $projectDir): array
    {

        $result = [
            'present' => 0,
            'missing' => 0,
            'details' => []
        ];

        // Vérifier les PDF générés
        $pdfTypes = ['intendance', 'urgence', 'mdl', 'dossier'];
        $result['pdfs'] = [];

        foreach ($pdfTypes as $type) {
            $filePath = "/uploads/pdfs/{$type}_{$user->getId()}.pdf";
            $absolutePath = $projectDir . '/public' . $filePath;
            $exists = file_exists($absolutePath);

            $result['pdfs'][$type] = [
                'exists' => $exists,
                'path' => $filePath
            ];
        }

        return $result;
    }
}
