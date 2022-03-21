<?php

namespace App\Repository;

use App\Entity\LogBookTestType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * @method LogBookTestType|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBookTestType|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBookTestType[]    findAll()
 * @method LogBookTestType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBookTestTypeRepository extends ServiceEntityRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, LogBookTestType::class);
    }

    /**
     * @param array $criteria
     * @return LogBookTestType
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findOneOrCreate(array $criteria): LogBookTestType
    {
        $criteria['name'] = strtoupper($criteria['name']);
        $entity = $this->findOneBy($criteria);
        if (null === $entity) {
            $entity = new LogBookTestType();
            $entity->setName($criteria['name']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        return $entity;
    }

    // /**
    //  * @return LogBookTestType[] Returns an array of LogBookTestType objects
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
    public function findOneBySomeField($value): ?LogBookTestType
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
