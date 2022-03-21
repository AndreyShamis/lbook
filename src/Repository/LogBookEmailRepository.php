<?php

namespace App\Repository;

use App\Entity\LogBookEmail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LogBookEmail|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBookEmail|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBookEmail[]    findAll()
 * @method LogBookEmail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBookEmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBookEmail::class);
    }

    // /**
    //  * @return LogBookEmail[] Returns an array of LogBookEmail objects
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
    public function findOneBySomeField($value): ?LogBookEmail
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
