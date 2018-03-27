<?php

namespace App\Repository;

use App\Entity\LogBookBuild;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LogBookBuildRepository extends ServiceEntityRepository
{
    /**
     * @var array Keep hashed entity
     */
    protected static $hashedData = array();

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookBuild::class);
    }

    /**
     * @param array $criteria
     * @return LogBookBuild
     */
    public function findOneOrCreate(array $criteria): LogBookBuild
    {
//        $add_hash = true;
//        if (isset(self::$hashedData[$criteria['name']])) {
//            $entity = self::$hashedData[$criteria['name']];
//            $add_hash = false;
//        } else {
            $entity = $this->findOneBy($criteria);
//        }

        if (null === $entity) {
            $entity = new LogBookBuild();
            $entity->setName($criteria['name']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
//        if ($add_hash) {
//            self::$hashedData[$criteria['name']] = $entity;
//        }

        return $entity;
    }
    /*
    public function findBySomething($value)
    {
        return $this->createQueryBuilder('l')
            ->where('l.something = :value')->setParameter('value', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
}
