<?php

namespace App\Repository;

use App\Entity\FilterEditHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
/**
 * @method FilterEditHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method FilterEditHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method FilterEditHistory[]    findAll()
 * @method FilterEditHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FilterEditHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FilterEditHistory::class);
    }


    /**
     * @param array $criteria
     * @return FilterEditHistory
     * @throws ORMException
     */
    public function findOneOrCreate(array $criteria): FilterEditHistory
    {
        $entity = $this->findOneBy($criteria);
        if (null === $entity) {
            $entity = new FilterEditHistory();
            $entity->setUser($criteria['user']);
            $entity->setDiff($criteria['diff']);
            $entity->setHappenedAt($criteria['happenedAt']);
            $entity->setTestFilter($criteria['testFilter']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        return $entity;
    }
//    /**
//     * @return FilterEditHistory[] Returns an array of FilterEditHistory objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FilterEditHistory
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
