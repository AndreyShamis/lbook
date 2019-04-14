<?php

namespace App\Repository;

use App\Entity\SuiteExecution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method SuiteExecution|null find($id, $lockMode = null, $lockVersion = null)
 * @method SuiteExecution|null findOneBy(array $criteria, array $orderBy = null)
 * @method SuiteExecution[]    findAll()
 * @method SuiteExecution[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SuiteExecutionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, SuiteExecution::class);
    }

    /**
     * @param array $criteria
     * @return SuiteExecution
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findOneOrCreate(array $criteria): SuiteExecution
    {
        $criteria['name'] = strtoupper($criteria['name']);
        $entity = $this->findOneBy($criteria);
        if (null === $entity) {
            $entity = new SuiteExecution();
            $entity->setSummary($criteria['summary']);
            $entity->setDescription($criteria['description']);
            $entity->setProductVersion($criteria['product_version']);
            $entity->setJobName($criteria['job_name']);
            $entity->setBuildTag($criteria['build_tag']);
            $entity->setTargetArch($criteria['target_arch']);
            $entity->setArch($criteria['arch']);
            $entity->setTestingLevel($criteria['testing_level']);

            $entity->setTestEnvironments(explode(';', $criteria['test_environments']));
            $entity->setComponents(explode(';', $criteria['components']));
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
        return $entity;
    }
    // /**
    //  * @return SuiteExecution[] Returns an array of SuiteExecution objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SuiteExecution
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
