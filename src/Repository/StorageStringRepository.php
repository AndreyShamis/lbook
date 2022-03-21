<?php

namespace App\Repository;

use App\Entity\StorageString;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StorageString|null find($id, $lockMode = null, $lockVersion = null)
 * @method StorageString|null findOneBy(array $criteria, array $orderBy = null)
 * @method StorageString[]    findAll()
 * @method StorageString[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StorageStringRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StorageString::class);
    }

    /**
     * @param string $name
     * @param string $key1
     * @param string $key2
     * @param string $key3
     * @param int $maxResults
     * @return StorageString
     */
    public function findByNameKeys(string $name,string  $key1='',string  $key2='', string $key3=null, $maxResults=1): StorageString
    {
        if ($maxResults < 1 || $maxResults > 10000) {
            $maxResults = 1;
        }

        return $this->createQueryBuilder('s')
            ->where('s.key1 = :key1')
            ->andWhere('s.key2 = :key2')
            ->andWhere('s.key3 = :key3')
            ->andWhere('s.vname = :name')
            ->setParameters([
                'key1'=> $key1,
                'key2'=> $key2,
                'key3'=> $key3,
                'name'=> $name
            ])
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $key1
     * @param string $key2
     * @param string $key3
     * @param int $maxResults
     * @return StorageString[]
     */
    public function findByKeys(string  $key1='',string  $key2='', string $key3=null, $maxResults=1000):array
    {
        if ($maxResults < 1 || $maxResults > 10000) {
            $maxResults = 1;
        }

        return $this->createQueryBuilder('s')
            ->where('s.key1 = :key1')
            ->andWhere('s.key2 = :key2')
            ->andWhere('s.key3 = :key3')
            ->setParameters([
                'key1'=> $key1,
                'key2'=> $key2,
                'key3'=> $key3
            ])
            ->orderBy('s.vname', 'ASC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }


    /**
     * @param array $criteria
     * @return StorageString
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function findOneOrCreate(array $criteria): StorageString
    {
        $tmpCriteria = array_merge([], $criteria);
        // We dont need search with value
        if (array_key_exists('value', $tmpCriteria)) {
            unset($tmpCriteria['value']);
        }
        $entity = $this->findOneBy($tmpCriteria);
        if (null === $entity) {
            $entity = new StorageString();
            $entity->setName($criteria['vname']);
            if (array_key_exists('value', $criteria)) {
                $entity->setValue($criteria['value']);
            }
            $entity->setKey1($criteria['key1']);
            if (array_key_exists('key2', $criteria)) {
                $entity->setKey2($criteria['key2']);
            }
            if (array_key_exists('key3', $criteria)) {
                $entity->setKey3($criteria['key3']);
            }
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        return $entity;
    }


    /*
    public function findOneBySomeField($value): ?StorageString
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
