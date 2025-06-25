<?php

namespace App\Repository;

use App\Entity\Inscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inscription>
 */
class InscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscription::class);
    }

    public function findByUser(User $user): ?Inscription
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function findIncompleteInscriptions(): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.isComplete = :complete')
            ->setParameter('complete', false)
            ->getQuery()
            ->getResult();
    }

    public function getCompletionStats(): array
    {
        $total = $this->count([]);
        $complete = $this->count(['isComplete' => true]);
        $incomplete = $total - $complete;

        return [
            'total' => $total,
            'complete' => $complete,
            'incomplete' => $incomplete,
            'completion_rate' => $total > 0 ? round(($complete / $total) * 100, 2) : 0
        ];
    }
}