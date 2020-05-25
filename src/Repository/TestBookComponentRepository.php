<?php

namespace App\Repository;

use App\Entity\TestBookComponent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method TestBookComponent|null find($id, $lockMode = null, $lockVersion = null)
 * @method TestBookComponent|null findOneBy(array $criteria, array $orderBy = null)
 * @method TestBookComponent[]    findAll()
 * @method TestBookComponent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TestBookComponentRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TestBookComponent::class);
    }

//    /**
//     * @return TestBookComponent[] Returns an array of TestBookComponent objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TestBookComponent
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
