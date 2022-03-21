<?php

namespace App\Repository;

use App\Entity\LogBookTestMD;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * @method LogBookTestMD|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBookTestMD|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBookTestMD[]    findAll()
 * @method LogBookTestMD[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBookTestMDRepository extends ServiceEntityRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, LogBookTestMD::class);
    }

    // /**
    //  * @return LogBookTestMD[] Returns an array of LogBookTestMD objects
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
    public function findOneBySomeField($value): ?LogBookTestMD
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
