<?php

namespace App\Repository;

use App\Entity\LogBookUserSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method LogBookUserSettings|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogBookUserSettings|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogBookUserSettings[]    findAll()
 * @method LogBookUserSettings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogBookUserSettingsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookUserSettings::class);
    }

//    /**
//     * @return LogBookUserSettings[] Returns an array of LogBookUserSettings objects
//     */
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
    public function findOneBySomeField($value): ?LogBookUserSettings
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
