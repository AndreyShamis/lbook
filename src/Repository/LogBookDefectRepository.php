<?php

namespace App\Repository;

use App\Entity\LogBookDefect;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method LogBookDefect|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBookDefect|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBookDefect[]    findAll()
 * @method LogBookDefect[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBookDefectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBookDefect::class);
    }

    // /**
    //  * @return LogBookDefect[] Returns an array of LogBookDefect objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LogBookDefect
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
