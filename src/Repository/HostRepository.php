<?php

namespace App\Repository;

use App\Entity\Host;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Host|null find($id, $lockMode = null, $lockVersion = null)
 * @method Host|null findOneBy(array $criteria, array $orderBy = null)
 * @method Host[]    findAll()
 * @method Host[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Host::class);
    }


    /**
     * @param array $criteria
     * @return Host
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function findOneOrCreate(array $criteria): Host
    {
        $criteria['name'] = trim($criteria['name']);
        $entity = $this->findOneBy(['name' => $criteria['name']]);
        if (null === $entity && $criteria['name'] === '') {
            // In case host not found and host == '' , so we will check if we know about this host something
            $entity = $this->findOneBy(['ip' => $criteria['ip']]);
        }
        if (null === $entity) {
            $entity = new Host();
            $entity->setName($criteria['name']);
            $entity->setIp($criteria['ip']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        } else {
            if ($entity->getIp() !== $criteria['ip']) {
                $entity->setIp($criteria['ip']);
                $this->_em->persist($entity);
                $this->_em->flush($entity);
            }
        }
        return $entity;
    }
//    /**
//     * @return Host[] Returns an array of Host objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Host
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
