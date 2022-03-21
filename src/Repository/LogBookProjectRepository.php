<?php

namespace App\Repository;

use App\Entity\LogBookProject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * @method LogBookProject|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBookProject|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBookProject[]    findAll()
 * @method LogBookProject[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBookProjectRepository extends ServiceEntityRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, LogBookProject::class);
    }

    // /**
    //  * @return LogBookProject[] Returns an array of LogBookProject objects
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
    public function findOneBySomeField($value): ?LogBookProject
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
