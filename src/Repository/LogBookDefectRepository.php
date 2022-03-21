<?php

namespace App\Repository;

use App\Entity\LogBookDefect;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * @method LogBookDefect|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBookDefect|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBookDefect[]    findAll()
 * @method LogBookDefect[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBookDefectRepository extends ServiceEntityRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, LogBookDefect::class);
    }


    public function findOneOrCreate(array $criteria, bool $external): LogBookDefect
    {

        if (!$external) {
            $entity = $this->findOneBy(
                [
                    'name' => $criteria['name'],
                    'isExternal' => false,

                ]
            );

        } else {
            $entity = $this->findOneBy(
                [
                    'name' => $criteria['name'],
                    'isExternal' => true,
                    'ext_id' => $criteria['ext_id']
                ]
            );
        }
        if (null === $entity) {
            $entity = new LogBookDefect();
            $entity->setIsExternal($external);
            if ($external) {
                $entity->setName($criteria['name']); // summary
                $entity->setExtId($criteria['ext_id']); // key
                $entity->setDescription($criteria['description']);
                $entity->setLabels($criteria['labels']);
                $entity->setStatusString($criteria['statusString']);
                $entity->setExtReporter($criteria['extReporter']);
                $entity->setExtAssignee($criteria['extAssignee']);
                $entity->setPriority($criteria['priority']);
                $entity->setExtVersionFound($criteria['extVersionFound']);
                $entity->setExtUpdatedAt($criteria['ExtUpdatedAt']);
                $entity->setExtCreatedAt($criteria['ExtCreatedAt']);
            }

            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        return $entity;
    }
    // /**
    //  * @return LogBookDefect[] Returns an array of LogBookDefect objects
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
    public function findOneBySomeField($value): ?LogBookDefect
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
