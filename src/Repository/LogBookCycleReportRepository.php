<?php

namespace App\Repository;

use App\Entity\LogBookCycleReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LogBookCycleReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBookCycleReport|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBookCycleReport[]    findAll()
 * @method LogBookCycleReport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBookCycleReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBookCycleReport::class);
    }

    // /**
    //  * @return LogBookCycleReport[] Returns an array of LogBookCycleReport objects
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
    public function findOneBySomeField($value): ?LogBookCycleReport
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
