<?php

namespace App\Repository;

use App\Entity\CycleReportEditHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * @method CycleReportEditHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method CycleReportEditHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method CycleReportEditHistory[]    findAll()
 * @method CycleReportEditHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CycleReportEditHistoryRepository extends ServiceEntityRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, CycleReportEditHistory::class);
    }

    /**
     * @param array $criteria
     * @return CycleReportEditHistory
     * @throws ORMException
     */
    public function findOneOrCreate(array $criteria): CycleReportEditHistory
    {
        $entity = $this->findOneBy($criteria);
        if (null === $entity) {
            $entity = new CycleReportEditHistory();
            $entity->setUser($criteria['user']);
            $entity->setDiff($criteria['diff']);
            $entity->setHappenedAt($criteria['happenedAt']);
            $entity->setReport($criteria['report']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        return $entity;
    }

    // /**
    //  * @return CycleReportEditHistory[] Returns an array of CycleReportEditHistory objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CycleReportEditHistory
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
