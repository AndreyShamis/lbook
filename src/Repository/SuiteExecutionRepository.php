<?php

namespace App\Repository;

use App\Entity\SuiteExecution;
use App\Utils\RandomString;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\AbstractQuery;

/**
 * @method SuiteExecution|null find($id, $lockMode = null, $lockVersion = null)
 * @method SuiteExecution|null findOneBy(array $criteria, array $orderBy = null)
 * @method SuiteExecution[]    findAll()
 * @method SuiteExecution[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SuiteExecutionRepository extends ServiceEntityRepository
{
    public function __construct(Registry $registry)
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
        $suite_dict = array();
        if (!array_key_exists('testing_level', $criteria)) {
            $criteria['testing_level'] = 'sanity';
        }
        if (!array_key_exists('product_version', $criteria)) {
            $criteria['product_version'] = '';
        }
        if (array_key_exists('suite_dict', $criteria)) {
            $suite_dict = $criteria['suite_dict'];
        }
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

        $job_name = $build_tag = $target_arch = $arch = $package_mode = '';
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
            if (array_key_exists('tests_count_enabled', $criteria) && (int)$criteria['tests_count_enabled'] > 0) {
                $criteria['tests_count'] = (int)$criteria['tests_count_enabled'];
            }else {
                $criteria['tests_count'] = 0;
            }
        } else {
            $criteria['tests_count'] = (int)$criteria['tests_count'];
        }

        if (!array_key_exists('tests_count_enabled', $criteria)) {
            if (array_key_exists('tests_count', $criteria) && (int)$criteria['tests_count'] > 0) {
                $criteria['tests_count_enabled'] = (int)$criteria['tests_count'];
            }else {

                $criteria['tests_count_enabled'] = 0;
            }
        } else {
            $criteria['tests_count_enabled'] = (int)$criteria['tests_count_enabled'];
        }

        if (!array_key_exists('build_flavor', $criteria)) {
            $criteria['build_flavor'] = 'devel';
        } else {
            $criteria['build_flavor'] = trim($criteria['build_flavor']);
            if  (strtolower($criteria['build_flavor']) === 'development' || strtolower($criteria['build_flavor']) === 'develop' || strtolower($criteria['build_flavor']) === 'dev') {
                $criteria['build_flavor'] = 'devel';
            }
            if ( strtolower($criteria['build_flavor']) === 'production' || strtolower($criteria['build_flavor']) === 'product' ) {
                $criteria['build_flavor'] = 'prod';
            }
        }
        if (!array_key_exists('platform_hw_ver', $criteria)) {
            $criteria['platform_hw_ver'] = '';
        } else {
            $criteria['platform_hw_ver'] = trim($criteria['platform_hw_ver']);
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
                    'buildType' => $criteria['build_flavor'],
                    'platformHardwareVersion' => $criteria['platform_hw_ver'],
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
            $entity->setBuildType($criteria['build_flavor']);
            $entity->setPlatformHardwareVersion($criteria['platform_hw_ver']);
            $entity->setPublish($publish);
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
            $entity->setDatetime($criteria['datetime']);
            $entity->setTestsCount($criteria['tests_count']);
            $entity->setTestsCountEnabled($criteria['tests_count_enabled']);
            $entity->setUuid($criteria['uuid']);
            $entity->setHost($criteria['host']);
            if (array_key_exists('package_mode', $criteria)) {
                $entity->setPackageMode($criteria['package_mode']);
            }
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
            if (array_key_exists('branchName', $criteria) && strlen(trim($criteria['branchName'])) > 2) {
                $entity->setBranchName(substr(trim($criteria['branchName']),0, 49));
            } else {
                $entity->setBranchName(substr(trim($entity->getBranchName()),0, 49));
            }
            if (array_key_exists('test_environments', $criteria)) {
                $entity->setTestEnvironments($criteria['test_environments']);
            }
            $entity->setComponents($criteria['components']);
//            if (array_key_exists('components', $criteria)) {
//
//            }
            if (array_key_exists('owners', $criteria) && $criteria['owners'] !== null && count($criteria['owners']) > 0) {
                $entity->setOwners($criteria['owners']);
            } elseif (array_key_exists('assignees', $suite_dict) && $suite_dict['assignees'] !== null && count($suite_dict['assignees']) > 0){
                    $entity->setOwners($suite_dict['assignees']);
            } else {
                $entity->setOwners(['NoOwner']);
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
     * @return SuiteExecution[]|null
     */
    public function getUniqOwners()
    {
        $reset = function ($input) {
            return $input['owners'];
        };
        $ret = $this->createQueryBuilder('s')
            ->select('s.owners')->distinct()
            ->orderBy('s.updatedAt', 'DESC')
            ->setMaxResults(10000)
            ->setLifetime(7200)
            ->setCacheable(true);

        $ret = $ret->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR)
        ;
        if (count($ret) > 0) {
            $map = array_map($reset, $ret);
            return array_combine($map, $map);
        }
        return null;

    }

    /**
     * @return SuiteExecution[]|null
     */
    public function getUniqPlatforms()
    {
        $reset = function ($input) {
            return $input['platform'];
        };
        $ret = $this->createQueryBuilder('s')
            ->select('s.platform')->distinct()
            ->orderBy('s.updatedAt', 'DESC')
            ->setMaxResults(10000)
            ->setLifetime(7200)
            ->setCacheable(true);

        $ret = $ret->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR)
        ;
        if (count($ret) > 0) {
            $map = array_map($reset, $ret);
            return array_combine($map, $map);
        }
        return null;

    }


    /**
     * @return SuiteExecution[]|null
     */
    public function getUniqJobNames()
    {
        $reset = function ($input) {
            return $input['jobName'];
        };
        $ret = $this->createQueryBuilder('s')
            ->select('s.jobName')->distinct()
            ->orderBy('s.updatedAt', 'DESC')
            ->setMaxResults(10000)
            ->setLifetime(7200)
            ->setCacheable(true);

        $ret = $ret->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR)
        ;
        if (count($ret) > 0) {
            $map = array_map($reset, $ret);
            return array_combine($map, $map);
        }
        return null;

    }

    /**
     * @return SuiteExecution[]|null
     */
    public function getUniqComponents()
    {
        $reset = function ($input) {
            return $input['components'];
        };
        $ret = $this->createQueryBuilder('s')
            ->select('s.components')->distinct()
            ->orderBy('s.updatedAt', 'DESC')
            ->setMaxResults(10000)
            ->setLifetime(7200)
            ->setCacheable(true);

        $ret = $ret->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR)
        ;
        if (count($ret) > 0) {
            $map = array_map($reset, $ret);
            return array_combine($map, $map);
        }
        return null;

    }
    /**
     * @param int $state
     * @return SuiteExecution[]|null
     */
    public function getUniqChips()
    {
        $reset = function ($input) {
            return $input['chip'];
        };
        $ret = $this->createQueryBuilder('s')
            ->select('s.chip')->distinct()
            ->orderBy('s.chip', 'ASC')
            ->setMaxResults(10000)
            ->setLifetime(7200)
            ->setCacheable(true);

        $ret = $ret->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR)
        ;
        if (count($ret) > 0) {
            $map = array_map($reset, $ret);
            return array_combine($map, $map);
        }
        return null;

    }

    /**
     * @param int $state
     * @return SuiteExecution|null
     */
    public function findOneBySate(int $state=0)
    {
        $ret = $this->createQueryBuilder('s')
            ->andWhere('s.publish = 1')

            ->andWhere('s.state = :state')
            ->andWhere('s.uuid != :uuid')
            ->setParameter('state', $state)
            ->setParameter('uuid', '')
            ->setMaxResults(1);
        if ($state >= 0 && $state <=1) {
            $ret = $ret->andWhere('s.jira_key IS NULL');
        }
        $ret = $ret->getQuery()
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
    public function findAllNotPublished(int $state=0, $max_results=200000)
    {
        $ret = $this->createQueryBuilder('s')
            ->andWhere('s.cycle is not null')
            ->andWhere('s.publish = 1')
            ->andWhere('s.state = :state')
            ->andWhere('s.uuid != :uuid')
            ->setParameter('state', $state)
            ->setParameter('uuid', '');
        if ($state >= 0 && $state <=1) {
            $ret = $ret->andWhere('s.jira_key IS NULL');
        }
        $ret = $ret->setMaxResults($max_results)
            ->getQuery()
            ->getResult()
        ;
        return $ret;
    }

    public function findSuitesInProgress(int $max_results=30000, int $days=1)
    {
        return $this->findSuitesInProgressByLevel('', $max_results, $days);
    }

    public function findSuitesInProgressByLevel(string $level = 'sanity', int $max_results=30000, int $days=1)
    {
        if ($days < 0 || $days > 365){
            $days = 1;
        }
        $ret = $this->createQueryBuilder('s')
            ->andWhere('s.cycle is not null')
            ->andWhere('s.startedAt >= :started')
            ->andWhere('s.testsCountEnabled > s.totalExecutedTests')
//            ->andWhere('s.uuid != :uuid')
//            ->setParameter('uuid', '')
            ->setParameter('started', new \DateTime('-'. $days. ' days'), \Doctrine\DBAL\Types\Type::DATETIME)
//            ->orderBy('s.id')
        ;
        if ($level !== '') {
            $ret = $ret->andWhere('s.testingLevel = :level')
                ->setParameter('level', $level);
        }

        $ret = $ret->setMaxResults($max_results)
            ->getQuery()
            ->getResult()
        ;
        return $ret;
    }

    /**
     * @param int $max_results
     * @param int $days
     * @return \Doctrine\ORM\QueryBuilder|mixed
     */
    public function findSanitySuitesInProgress(int $max_results=30000, int $days=1)
    {
        return $this->findSuitesInProgressByLevel('sanity', $max_results, $days);
    }

    /**
     * @param int $max_results
     * @param int $days
     * @return \Doctrine\ORM\QueryBuilder|mixed
     */
    public function findIntegrationSuitesInProgress(int $max_results=30000, int $days=1)
    {
        return $this->findSuitesInProgressByLevel('integration', $max_results, $days);
    }

    /**
     * @param int $max_results
     * @param int $days
     * @return \Doctrine\ORM\QueryBuilder|mixed
     */
    public function findNightlySuitesInProgress(int $max_results=30000, int $days=1)
    {
        return $this->findSuitesInProgressByLevel('nightly', $max_results, $days);
    }

    /**
     * @param int $max_results
     * @param int $days
     * @return \Doctrine\ORM\QueryBuilder|mixed
     */
    public function findWeeklySuitesInProgress(int $max_results=30000, int $days=1)
    {
        return $this->findSuitesInProgressByLevel('weekly', $max_results, $days);
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
