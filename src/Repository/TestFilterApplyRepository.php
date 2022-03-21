<?php

namespace App\Repository;

use App\Entity\TestFilterApply;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * @method TestFilterApply|null find($id, $lockMode = null, $lockVersion = null)
 * @method TestFilterApply|null findOneBy(array $criteria, array $orderBy = null)
 * @method TestFilterApply[]    findAll()
 * @method TestFilterApply[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TestFilterApplyRepository extends ServiceEntityRepository
{

    public function __construct(Registry $registry)
    {
        parent::__construct($registry, TestFilterApply::class);
    }

    // /**
    //  * @return TestFilterApply[] Returns an array of TestFilterApply objects
    //  */
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
    public function findOneBySomeField($value): ?TestFilterApply
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
