<?php

namespace App\Repository;

use App\Entity\SuiteExecution;
use App\Utils\RandomString;
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
        $publish = false;
        if (!array_key_exists('datetime', $criteria)) {
            $criteria['datetime'] = RandomString::generateRandomStringMd5(40);
        }
        if (!array_key_exists('uuid', $criteria)) {
            $criteria['uuid'] = '';
        }
        if (array_key_exists('publish', $criteria) && $criteria['publish'] === true) {
            $publish = true;
        }
        $entity = null;

        $job_name = $build_tag = $target_arch = $arch = '';
        if (array_key_exists('job_name', $criteria)) {
            $job_name = $criteria['job_name'];
        }
        if (array_key_exists('build_tag', $criteria)) {
            $build_tag = $criteria['build_tag'];
        }
        if (array_key_exists('target_arch', $criteria)) {
            $target_arch = $criteria['target_arch'];
        }
        if (array_key_exists('arch', $criteria)) {
            $arch = $criteria['arch'];
        }
        if (!array_key_exists('tests_count', $criteria)) {
            $criteria['tests_count'] = 0;
        } else {
            $criteria['tests_count'] = (int)$criteria['tests_count'];
        }
        if (!array_key_exists('tests_count_enabled', $criteria)) {
            $criteria['tests_count_enabled'] = 0;
        } else {
            $criteria['tests_count_enabled'] = (int)$criteria['tests_count_enabled'];
        }
        try {
            $entity = $this->findOneBy(
                array(
                    'name' => $criteria['name'],
                    'summary' => $criteria['summary'],
                    'uuid' => $criteria['uuid'],
                    'testingLevel' => $criteria['testing_level'],
                    'productVersion' => $criteria['product_version'],
                    'platform' => $criteria['platform'],
                    'chip' => $criteria['chip'],
                    'publish' => $publish,
                    'jobName' => $job_name,
                    'buildTag' => $build_tag,
                    'targetArch' => $target_arch,
                    'arch' => $arch,
                    'datetime' => $criteria['datetime'],
                    'testsCount' => $criteria['tests_count'],
                    'testsCountEnabled' => $criteria['tests_count_enabled'],
                    'cycle' => null
                ));
        } catch (\Exception $ex) {}

        if (null === $entity) {
            $entity = new SuiteExecution();
            $entity->setName($criteria['name']);
            $entity->setSummary($criteria['summary']);
            $entity->setTestingLevel($criteria['testing_level']);
            $entity->setProductVersion($criteria['product_version']);
            $entity->setPlatform($criteria['platform']);
            $entity->setChip($criteria['chip']);
            $entity->setPublish($publish);
            $entity->setJobName($criteria['job_name']);
            $entity->setBuildTag($criteria['build_tag']);
            $entity->setTargetArch($criteria['target_arch']);
            $entity->setArch($criteria['arch']);
            $entity->setDatetime($criteria['datetime']);
            $entity->setTestsCount($criteria['tests_count']);
            $entity->setTestsCountEnabled($criteria['tests_count_enabled']);
            $entity->setUuid($criteria['uuid']);

            if (array_key_exists('description', $criteria)) {
                $entity->setDescription($criteria['description']);
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

    /**
     * @param int $state
     * @return SuiteExecution|null
     */
    public function findOneBySate(int $state=0)
    {
        $ret = $this->createQueryBuilder('s')
            ->andWhere('s.publish = 1')
            ->andWhere('s.jira_key IS NULL')
            ->andWhere('s.state = :state')
            ->andWhere('s.uuid != :uuid')
            ->setParameter('state', $state)
            ->setParameter('uuid', '')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
        ;
        if (count($ret) > 0) {
            return $ret[0];
        }
        return null;
//        $entity = $this->findOneBy(
//            array(
//                'publish' => true,
//                'jira_key' => null,
//                'state' => $state
//            ));
//        if ($entity !== null) {
//            return $entity;
//        }
//        return null;
    }

    /**
     * @param int $state
     * @param $max_results
     * @return mixed
     */
    public function findAllNotPublished(int $state=0, $max_results=100)
    {
        $ret = $this->createQueryBuilder('s')
            ->andWhere('s.publish = 1')
            ->andWhere('s.jira_key IS NULL')
            ->andWhere('s.state = :state')
            ->andWhere('s.uuid != :uuid')
            ->setParameter('state', $state)
            ->setParameter('uuid', '')
            ->setMaxResults($max_results)
            ->getQuery()
            ->getResult()
        ;
        return $ret;
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
