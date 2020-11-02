<?php

namespace App\Repository;

use App\Entity\LogBookTestFailDesc;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * @method LogBookTestFailDesc|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBookTestFailDesc|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBookTestFailDesc[]    findAll()
 * @method LogBookTestFailDesc[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBookTestFailDescRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogBookTestFailDesc::class);
    }

    public function findByDescMd5($desc){
        return $this->findOneBy(['md5' => md5(LogBookTestFailDesc::validateDescription($desc))]);
    }


    /**
     * @param array $criteria
     * @return LogBookTestFailDesc
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function findOrCreate(array $criteria): LogBookTestFailDesc
    {
        $entity = $this->findByDescMd5($criteria['description']);
        if ($entity === null) {
            $entity = new LogBookTestFailDesc();
            $entity->setDescription($criteria['description']);
            if (array_key_exists('test', $criteria)) {
                $entity->addTest($criteria['test']);
            }
            $entity->setTestsCount(1);

            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        return $entity;
    }

    // /**
    //  * @return LogBookTestFailDesc[] Returns an array of LogBookTestFailDesc objects
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
    public function findOneBySomeField($value): ?LogBookTestFailDesc
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
