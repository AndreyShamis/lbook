<?php

namespace App\Repository;

use App\Entity\LogBookSetup;
use App\Entity\LogBookCycle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Model\OsType;
use Symfony\Component\Filesystem\Filesystem;

class LogBookSetupRepository extends ServiceEntityRepository
{
    /** @var LoggerInterface  */
    protected $logger;
    /**
     * @var array Keep hashed entity
     */
    protected static $hashedData = array();

    /**
     * LogBookSetupRepository constructor.
     * @param RegistryInterface $registry
     * @param LoggerInterface $logger
     */
    public function __construct(RegistryInterface $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, LogBookSetup::class);
        $this->logger = $logger;
    }

    /**
     * @param string $setupName
     * @return LogBookSetup|null
     */
    public function findByName(string $setupName): ?LogBookSetup
    {
        if (isset(self::$hashedData[$setupName])) {
            $entity = self::$hashedData[$setupName];
        } else {
            /** @var LogBookSetup $entity */
            $entity = $this->findOneBy(array('name' => $setupName));
            if($entity !== null){
                self::$hashedData[$setupName] = $entity;
                self::$hashedData[$entity->getId()] = $entity;
            }
        }

        return $entity;
    }

    /**
     * @param int $setupId
     * @return LogBookSetup|null
     */
    public function findById(int $setupId): ?LogBookSetup
    {
        if (isset(self::$hashedData[$setupId])) {
            $entity = self::$hashedData[$setupId];
        } else {
            /** @var LogBookSetup $entity */
            $entity = $this->findOneBy(array('id' => $setupId));
            if ($entity !== null) {
                self::$hashedData[$setupId] = $entity;
                self::$hashedData[$entity->getName()] = $entity;
            }
        }

        return $entity;
    }

    /**
     * @param array $criteria
     * @return LogBookSetup
     * @throws \Doctrine\ORM\ORMException
     */
    public function findOneOrCreate(array $criteria): LogBookSetup
    {
//        $add_hash = true;
//        if (isset(self::$hashedData[$criteria['name']])) {
//            $entity = self::$hashedData[$criteria['name']];
//            $add_hash = false;
//        } else {
//            $entity = $this->findOneBy($criteria);
//        }
        $criteria['name'] = LogBookSetup::validateName($criteria['name']);
        $entity = $this->findOneBy($criteria);
        if (null === $entity) {
            $entity = new LogBookSetup();
            $entity->setName($criteria['name']);
            $entity->setCheckUpTime(false);
            $entity->setDisabled(false);
            $entity->setOwner(null);   //TODO User, Owner
            $entity->setOs(OsType::OS_UNKNOWN);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }
//        if ($add_hash) {
//            self::$hashedData[$criteria['name']] = $entity;
//        }
        return $entity;
    }

    /**
     * @param LogBookSetup $setup
     * @throws \Doctrine\ORM\ORMException
     */
    public function delete(LogBookSetup $setup): void
    {
        /** @var LogBookCycleRepository $cycleRepo */
        $cycleRepo = $this->getEntityManager()->getRepository('App:LogBookCycle');
        /** @var LogBookCycle $cycle */
        //$cycles = $setup->getCycles();
        //echo "Cycles count :" . ($setup->getCycles()) . "\n";
        foreach ($setup->getCycles() as $cycle) {
            $cycleRepo->delete($cycle);
        }
        try {
            $fileSystem = new Filesystem();
            if ($fileSystem->exists($setup->getLogFilesPath())) {
                //$this->logger->warning('[SETUP][DELETE]: Delete SETUP ID:' . $setup->getId());
                $this->logger->warning('[SETUP][DELETE]: Delete SETUP ID:' . $setup->getId(),
                    array(
                        'getName' => $setup->getName(),
                        'getOwner' => $setup->getOwner(),
                        'getLogFilesPath' => $setup->getLogFilesPath(),
                        'getRetentionPolicy' => $setup->getRetentionPolicy()));
                $fileSystem->remove($setup->getLogFilesPath());
            }
        } catch (\Throwable $ex) {
            $this->logger->critical('[SETUP][DELETE]: Throwable SETUP ID:' . $setup->getId(),
                array(
                    $ex->getMessage(),
                    $ex,
                    $setup->getName()));
        }
        $this->_em->remove($setup);
        $this->_em->flush($setup);
    }
}
