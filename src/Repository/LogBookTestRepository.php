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
