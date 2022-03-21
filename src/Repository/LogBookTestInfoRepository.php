<?php

namespace App\Repository;

use App\Entity\LogBookTestInfo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * @method LogBookTestInfo|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBookTestInfo|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBookTestInfo[]    findAll()
 * @method LogBookTestInfo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBookTestInfoRepository extends ServiceEntityRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, LogBookTestInfo::class);
    }

    /**
     * @param array $criteria
     * @return LogBookTestInfo
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findOneOrCreate(array $criteria): LogBookTestInfo
    {
        $criteria['name'] = LogBookTestInfo::validateName($criteria['name']);
        $criteria['path'] = LogBookTestInfo::validatePath($criteria['path']);
        $entity = $this->findOneBy($criteria);
        if (null === $entity) {
            $entity = new LogBookTestInfo();
            $entity->setName($criteria['name']);
            $entity->setPath($criteria['path']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        return $entity;
    }
    // /**
    //  * @return LogBookTestInfo[] Returns an array of LogBookTestInfo objects
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
    public function findOneBySomeField($value): ?LogBookTestInfo
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
