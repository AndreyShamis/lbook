<?php

namespace App\Repository;

use App\Entity\LogBookSuiteInfo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\ORMException;

/**
 * @method LogBookSuiteInfo|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBookSuiteInfo|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBookSuiteInfo[]    findAll()
 * @method LogBookSuiteInfo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBookSuiteInfoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBookSuiteInfo::class);
    }

    /**
     * @param array $criteria
     * @return LogBookSuiteInfo
     * @throws ORMException
     */
    public function findOneOrCreate(array $criteria): LogBookSuiteInfo
    {
//        $add_hash = true;
//        if (isset(self::$hashedData[$criteria['name']])) {
//            $entity = self::$hashedData[$criteria['name']];
//            $add_hash = false;
//        } else {
//            $entity = $this->findOneBy($criteria);
//        }
        $criteria['name'] = LogBookSuiteInfo::validateName($criteria['name']);
        $entity = $this->findOneBy(['name' => $criteria['name'], 'uuid' => $criteria['uuid']]);
        if (null === $entity) {
            $entity = new LogBookSuiteInfo();
            $entity->setName($criteria['name']);
            $entity->setUuid($criteria['uuid']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        return $entity;
    }

    // /**
    //  * @return LogBookSuiteInfo[] Returns an array of LogBookSuiteInfo objects
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
    public function findOneBySomeField($value): ?LogBookSuiteInfo
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
