<?php

namespace App\Repository;

use App\Entity\LogBookCycle;
use App\Entity\LogBookTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;

class LogBookTestRepository extends ServiceEntityRepository
{
    /** @var LoggerInterface  */
    protected $logger;

    /**
     * LogBookTestRepository constructor.
     * @param ManagerRegistry $registry
     * @param LoggerInterface $logger
     */
    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, LogBookTest::class);
        $this->logger = $logger;
    }

    public function getTestStatisticsForSetup(
        int $setupId, 
        int $minExecutions = 2, 
        ?\DateTime $startTime = null, 
        ?\DateTime $endTime = null, 
        array $suiteFilters = [], 
        array $testMetadataFilters = []
    ): array {
        $qb = $this->createQueryBuilder('t')
        ->select('
            ti.id as test_id,
            ti.name as test_name,
            se.chip as chip,
            se.platform as platform,
            JSON_UNQUOTE(JSON_EXTRACT(md.valueJson, \'$.BOARD\')) as board,
            JSON_UNQUOTE(JSON_EXTRACT(md.valueJson, \'$.PROJECT\')) as project,
            JSON_UNQUOTE(JSON_EXTRACT(md.valueJson, \'$.BRAIN\')) as brain,
            JSON_UNQUOTE(JSON_EXTRACT(md.valueJson, \'$.FLOW_NAME\')) as flow_name,
            JSON_UNQUOTE(JSON_EXTRACT(md.valueJson, \'$.PRODUCT_VERSION\')) as product_version,
            COUNT(t.id) as test_execution_count,
            SUM(CASE WHEN v.name = \'PASS\' THEN 1 ELSE 0 END) as pass_count,
            SUM(CASE WHEN v.name = \'FAIL\' THEN 1 ELSE 0 END) as fail_count,
            SUM(CASE WHEN v.name = \'ERROR\' THEN 1 ELSE 0 END) as error_count,
            SUM(CASE WHEN v.name NOT IN (\'PASS\', \'FAIL\', \'ERROR\') THEN 1 ELSE 0 END) as other_count,
            MAX(t.timeEnd) as last_run_time,
            AVG(t.timeRun) as avg_execution_time
        ')
        ->innerJoin('t.testInfo', 'ti')
        ->innerJoin('t.cycle', 'c') // Ensure we join with the cycle
        ->innerJoin('c.setup', 's') // Join with the setup to filter by setupId
        ->innerJoin('c.suiteExecution', 'se') // Adding SuiteExecution join
        ->innerJoin('se.suiteInfo', 'si')  // Join SuiteInfo if necessary
        ->innerJoin('t.verdict', 'v')
        ->leftJoin('t.newMetaData', 'md')
        ->where('s.id = :setupId')
        ->groupBy(
            'ti.name', 
            'se.chip', 
            'se.platform', 
            'board', 
            'project', 
            'brain', 
            'flow_name', 
            'product_version'
        )
        ->having('COUNT(t.id) >= :minExecutions')
        ->orderBy('ti.name', 'ASC')
        ->addOrderBy('test_execution_count', 'DESC')
        ->setParameter('setupId', $setupId)
        ->setParameter('minExecutions', $minExecutions);

        // Apply time filters
        if ($startTime) {
            $qb->andWhere('c.timeStart >= :startTime')
            ->setParameter('startTime', $startTime);
        }
        if ($endTime) {
            $qb->andWhere('c.timeEnd <= :endTime')
            ->setParameter('endTime', $endTime);
        }
    
        // Apply suite execution free-text filters (assuming $suiteFilters is an array of search strings)
        if (!empty($suiteFilters)) {
            $orX = $qb->expr()->orX();
            foreach ($suiteFilters as $suiteFilter) {
                $suiteFilter = '%' . $suiteFilter . '%';  // Add wildcards for LIKE search
                $orX->add(
                    $qb->expr()->orX(
                        $qb->expr()->like('se.summary', ':suiteFilter'),
                        $qb->expr()->like('se.description', ':suiteFilter'),
                        $qb->expr()->like('se.jobName', ':suiteFilter'),
                        $qb->expr()->like('se.productVersion', ':suiteFilter'),
                        $qb->expr()->like('se.platform', ':suiteFilter'),
                        $qb->expr()->like('se.chip', ':suiteFilter')
                    )
                );
                $qb->setParameter('suiteFilter', $suiteFilter);
            }
            $qb->andWhere($orX);
        }

        // Apply test metadata filters
        if (!empty($testMetadataFilters)) {
            $qb->leftJoin('t.newMetaData', 'md_filter');
            foreach ($testMetadataFilters as $key => $values) {
                if (!is_array($values)) {
                    $values = [$values];
                }
        
                $orX = $qb->expr()->orX();
                foreach ($values as $index => $value) {
                    $keyLength = strlen($key);
                    $valueLength = strlen($value);
                    $pattern = '%s:' . $keyLength . ':"' . $key . '";s:' . $valueLength . ':"' . $value . '";%';
                    
                    $orX->add($qb->expr()->like('md_filter.value', ':metadata_' . $key . '_' . $index));
                    $qb->setParameter('metadata_' . $key . '_' . $index, $pattern);
                }
                
                $qb->andWhere($orX);
            }
        }
        
        $result = $qb->getQuery()->getResult();
    
        // Fetch metadata for each test
        $testIds = array_column($result, 'test_id');
        $metadataQuery = $this->createQueryBuilder('t')
            ->select('ti.id as test_id, md.value as metadata')
            ->innerJoin('t.testInfo', 'ti')
            ->leftJoin('t.newMetaData', 'md')
            ->where('ti.id IN (:testIds)')
            ->setParameter('testIds', $testIds)
            ->getQuery();
        
        $metadataResults = $metadataQuery->getResult();
    
        // Process metadata
        $metadataMap = [];
        foreach ($metadataResults as $item) {
            if (!isset($metadataMap[$item['test_id']])) {
                $metadataMap[$item['test_id']] = [];
            }
            
            // Check if metadata is an array and not null
            if (is_array($item['metadata'])) {
                foreach ($item['metadata'] as $metaItem) {
                    if (is_string($metaItem)) {
                        $parts = explode(' = ', $metaItem, 1);
                        if (count($parts) == 2) {
                            $metadataMap[$item['test_id']][$parts[0]] = $parts[1];
                        }
                    }
                }
            } elseif (is_string($item['metadata'])) {
                // Handle case where metadata might be a single string
                $parts = explode(' = ', $item['metadata'], 2);
                if (count($parts) == 2) {
                    $metadataMap[$item['test_id']][$parts[0]] = $parts[1];
                }
            }
            // If metadata is null or any other type, we just leave it as an empty array
        }

        // Merge metadata with test results
        foreach ($result as &$test) {
            $test['metadata'] = $metadataMap[$test['test_id']] ?? [];
        }
    
        $totalTests = array_sum(array_column($result, 'test_execution_count'));
        $uniqueTests = count($result);
    
        return [
            'total_tests' => $totalTests,
            'unique_tests' => $uniqueTests,
            'test_details' => $result,
            'min_executions' => $minExecutions,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'suite_filters' => $suiteFilters,
            'test_metadata_filters' => $testMetadataFilters,
            'metadataResults' => $metadataResults,
        ];
    }

    /**
     * @param array $criteria
     * @param bool $flush
     * @return LogBookTest
     * @throws ORMException
     */
    public function findOneOrCreate(array $criteria, $flush = false): LogBookTest
    {
        $name = LogBookTest::validateName($criteria['name']);
        unset($criteria['name']);
        $entity = $this->findOneBy($criteria);
        if (null === $entity) {
            $entity = new LogBookTest();
            $entity->setName($name);
            //$entity->setVerdict($criteria['verdict']);
            $entity->setCycle($criteria['cycle']);
            $entity->setLogFile($criteria['logFile']);
            $entity->setLogFileSize($criteria['logFileSize']);
            $entity->setExecutionOrder($criteria['executionOrder']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
            if ($flush === true) {
                $this->_em->flush();
            }
        }
        return $entity;
    }

    /**
     * @param array $criteria
     * @param bool $flush
     * @return LogBookTest
     * @throws ORMException|UniqueConstraintViolationException
     */
    public function create(array $criteria, $flush = false): ?LogBookTest
    {
        $criteria['name'] = LogBookTest::validateName($criteria['name']);
        $entity = new LogBookTest();
        $entity->setName($criteria['name']);
        //$entity->setVerdict($criteria['verdict']);
        $entity->setCycle($criteria['cycle']);
        $entity->setLogFile($criteria['logFile']);
        $entity->setLogFileSize($criteria['logFileSize']);
        $entity->setExecutionOrder($criteria['executionOrder']);
        $this->_em->persist($entity);
        $this->_em->flush($entity);
        if ($flush === true) {
            $this->_em->flush();
        }
        return $entity;
    }

    /**
     * @param LogBookCycle $cycle
     */
    public function deleteByCycle(LogBookCycle $cycle): void
    {
        //$msgRepo = $this->getEntityManager()->getRepository('App:LogBookMessage');
//        foreach ($cycle->getTests() as $test){
//            /** @var LogBookTest $test */
//            //$msgRepo->deleteByTestId($test->getId());
//            $this->delete($test);
//            $this->_em->clear($test);
//        }
        /**
         * Additional clean | optional and may be removed
         */
        $classMetaData = $this->_em->getClassMetadata('App:LogBookMessage');
        if ($cycle->getDbName() !== null) {
            $classMetaData->setPrimaryTable(['name' => $cycle->getDbName()]);
            $logsRepo = $this->getEntityManager()->getRepository('App:LogBookMessage');
            foreach ($cycle->getTests() as $test) {
                try {
                    $logsRepo->createQueryBuilder('m')
                    ->delete()
                    ->where('m.test = :test_id')
                    ->setParameter('test_id', $test->getId());
                } catch (\Throwable $ex) {
                    $this->logger->critical('deleteByCycle: Throwable 1 for', [$ex->getMessage(), $ex]);
                }
            }
        }
        try {
            $qd = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.cycle = :cycle')
            ->setParameter('cycle', $cycle->getId());
            $query = $qd->getQuery();
            $query->execute();
        } catch (\Throwable $ex) {
            $this->logger->critical('deleteByCycle: Throwable 2 for', [$ex->getMessage(), $ex]);
        }

    }

    /**
     * @param LogBookTest $test
     * @throws ORMException
     */
    public function delete(LogBookTest $test): void
    {
        $fileName = '';
        $pre = '[TEST][DELETE]: ';
        $post = ' for TEST_ID:[' . $test->getId() . ']';
        try {
            $fileSystem = new Filesystem();
            $fileName = $test->getLogFilesPath();
            if ($fileSystem->exists($fileName) && is_file($fileName)) {
                $this->logger->notice($pre . 'Remove log file [' . $fileName . ']'. $post);
                $fileSystem->remove($fileName);
            } else {
                $this->logger->critical($pre . 'FILE_NOT_EXIST [' . $fileName . ']'. $post);
            }
        } catch (\Throwable $ex) {
            $this->logger->critical($pre . 'Throwable for'. $post,
                array(
                    $ex->getMessage(),
                    $ex, $test->getName(),
                    $fileName));
        }
        $this->_em->remove($test);
        $this->_em->flush($test);
    }
}
