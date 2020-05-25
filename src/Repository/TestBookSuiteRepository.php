<?php

namespace App\Repository;

use App\Entity\TestBookSuite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method TestBookSuite|null find($id, $lockMode = null, $lockVersion = null)
 * @method TestBookSuite|null findOneBy(array $criteria, array $orderBy = null)
 * @method TestBookSuite[]    findAll()
 * @method TestBookSuite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TestBookSuiteRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TestBookSuite::class);
    }

//    /**
//     * @return TestBookSuite[] Returns an array of TestBookSuite objects
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
    public function findOneBySomeField($value): ?TestBookSuite
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
