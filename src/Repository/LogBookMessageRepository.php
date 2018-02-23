<?php

namespace App\Repository;

use App\Entity\LogBookMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LogBookMessageRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookMessage::class);
    }

    /*
    public function findBySomething($value)
    {
        return $this->createQueryBuilder('l')
            ->where('l.something = :value')->setParameter('value', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
}
