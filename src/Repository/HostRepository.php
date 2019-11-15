<?php

namespace App\Repository;

use App\Entity\Host;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Host|null find($id, $lockMode = null, $lockVersion = null)
 * @method Host|null findOneBy(array $criteria, array $orderBy = null)
 * @method Host[]    findAll()
 * @method Host[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HostRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
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
        }
        $entity->setIp($criteria['ip']);
        if (array_key_exists('host_uptime', $criteria)) {
            $entity->setUptime($criteria['host_uptime']);
        }
        if (array_key_exists('host_memory_total', $criteria)) {
            $entity->setMemoryTotal($criteria['host_memory_total']);
        }
        if (array_key_exists('host_memory_free', $criteria)) {
            $entity->setMemoryFree($criteria['host_memory_free']);
        }
        if (array_key_exists('host_system', $criteria)) {
            $entity->setSystem($criteria['host_system']);
        }
        if (array_key_exists('host_release', $criteria)) {
            $entity->setSystemRelease($criteria['host_release']);
        }
        if (array_key_exists('host_version', $criteria)) {
            $entity->setSystemVersion($criteria['host_version']);
        }
        if (array_key_exists('host_python_version', $criteria)) {
            $entity->setPythonVersion($criteria['host_python_version']);
        }
        if (array_key_exists('host_user', $criteria)) {
            $entity->setUserName($criteria['host_user']);
        }
        if (array_key_exists('host_cpu_count', $criteria)) {
            $entity->setCpuCount($criteria['host_cpu_count']);
        }
        $this->_em->persist($entity);
        $this->_em->flush($entity);

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
