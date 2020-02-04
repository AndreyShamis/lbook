<?php

namespace App\Repository;

use App\Entity\SuiteExecution;
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


    public function findRelevantFiltersTo(SuiteExecution $suite)
    {
        try {
            $qb = $this->createQueryBuilder('f')
                ->where('f.suiteUuid IN (:uuids)')
                ->andWhere('f.testingLevel IN (:testing_level)')
                ->setParameter('uuids', [$suite->getUuid(), '*'])
                ->setParameter('testing_level', [strtoupper($suite->getTestingLevel()), '*'])
                ->andWhere('f.enabled = 1')
                ->setMaxResults(100)
            ;
        } catch (\Exception $e) {
        }
        return $qb->getQuery()->execute();
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
