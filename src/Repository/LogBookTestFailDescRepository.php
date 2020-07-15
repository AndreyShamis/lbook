<?php

namespace App\Repository;

use App\Entity\LogBookTestFailDesc;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method LogBookTestFailDesc|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBookTestFailDesc|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBookTestFailDesc[]    findAll()
 * @method LogBookTestFailDesc[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBookTestFailDescRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBookTestFailDesc::class);
    }

    // /**
    //  * @return LogBookTestFailDesc[] Returns an array of LogBookTestFailDesc objects
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
    public function findOneBySomeField($value): ?LogBookTestFailDesc
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
