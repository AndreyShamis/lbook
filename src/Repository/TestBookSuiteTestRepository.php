<?php

namespace App\Repository;

use App\Entity\TestBookSuiteTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TestBookSuiteTest|null find($id, $lockMode = null, $lockVersion = null)
 * @method TestBookSuiteTest|null findOneBy(array $criteria, array $orderBy = null)
 * @method TestBookSuiteTest[]    findAll()
 * @method TestBookSuiteTest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TestBookSuiteTestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestBookSuiteTest::class);
    }

//    /**
//     * @return TestBookSuiteTest[] Returns an array of TestBookSuiteTest objects
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
    public function findOneBySomeField($value): ?TestBookSuiteTest
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
