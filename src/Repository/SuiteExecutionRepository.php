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
        $entity = $this->findOneBy(
            array(
                'summary' => $criteria['summary'],
                'testingLevel' => $criteria['testing_level'],
                'productVersion' => $criteria['product_version'],
                'platform' => $criteria['platform'],
                'chip' => $criteria['chip']
            ));
        if (null === $entity) {
            $entity = new SuiteExecution();
            $entity->setSummary($criteria['summary']);
            $entity->setTestingLevel($criteria['testing_level']);
            $entity->setProductVersion($criteria['product_version']);
            $entity->setPlatform($criteria['platform']);
            $entity->setChip($criteria['chip']);

            if (array_key_exists('publish', $criteria) && $criteria['publish'] === true) {
                $entity->setPublish($criteria['publish']);
            }

            if (array_key_exists('description', $criteria)) {
                $entity->setDescription($criteria['description']);
            }
            if (array_key_exists('job_name', $criteria)) {
                $entity->setJobName($criteria['job_name']);
            }
            if (array_key_exists('build_tag', $criteria)) {
                $entity->setBuildTag($criteria['build_tag']);
            }
            if (array_key_exists('target_arch', $criteria)) {
                $entity->setTargetArch($criteria['target_arch']);
            }
            if (array_key_exists('arch', $criteria)) {
                $entity->setArch($criteria['arch']);
            }
            if (array_key_exists('test_plan_url', $criteria)) {
                $entity->setTestPlanUrl($criteria['test_plan_url']);
            }
            if (array_key_exists('ci_url', $criteria)) {
                $entity->setCiUrl($criteria['ci_url']);
            }
            if (array_key_exists('test_set_url', $criteria)) {
                $entity->setTestSetUrl($criteria['test_set_url']);
            }
            if (array_key_exists('test_environments', $criteria)) {
                $entity->setTestEnvironments($criteria['test_environments']);
            }
            if (array_key_exists('components', $criteria)) {
                $entity->setComponents($criteria['components']);
            }
            if (array_key_exists('jira_key', $criteria) && mb_strlen($criteria['jira_key']) > 5) {
                $entity->setJiraKey($criteria['jira_key']);
            }
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
