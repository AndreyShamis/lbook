<?php

namespace App\Repository;

use App\Entity\TestBookTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TestBookTest|null find($id, $lockMode = null, $lockVersion = null)
 * @method TestBookTest|null findOneBy(array $criteria, array $orderBy = null)
 * @method TestBookTest[]    findAll()
 * @method TestBookTest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TestBookTestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestBookTest::class);
    }

//    /**
//     * @return TestBookTest[] Returns an array of TestBookTest objects
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
    public function findOneBySomeField($value): ?TestBookTest
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
