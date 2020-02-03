<?php

namespace App\Repository;

use App\Entity\TestFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TestFilter|null find($id, $lockMode = null, $lockVersion = null)
 * @method TestFilter|null findOneBy(array $criteria, array $orderBy = null)
 * @method TestFilter[]    findAll()
 * @method TestFilter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TestFilterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestFilter::class);
    }

//    /**
//     * @return TestFilter[] Returns an array of TestFilter objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TestFilter
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
