<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\LogBookCycle;
use App\Entity\LogBookSetup;
use App\Model\EventStatus;
use App\Model\EventType;
use App\Repository\EventRepository;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookSetupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query\ResultSetMapping;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * Class LogBookBotController
 * @package App\Controller
 * @Route("bot")
 */
class LogBookBotController extends AbstractController
{
    /** @var EntityManagerInterface  */
    protected $em;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * LogBookUploaderController constructor.
     * @param Container $container
     * @param LoggerInterface $logger
     */
    public function __construct(Container $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->em = $this->getDoctrine()->getManager();
        $this->logger = $logger;
    }


    /**
     * Setup Cleaner.
     *
     * @Route("/setups/clean", name="bot_setup_cleaner", methods={"GET"})
     * @Template(template="log_book_bot/delete.setups.html.twig")
     * @param LogBookSetupRepository $setupRepo
     * @return array
     * @throws \Exception
     */
    public function setupCleaner(LogBookSetupRepository $setupRepo, LoggerInterface $logger): array
    {
        $logger->notice(' #[SETUP_CLEANER][ + Setup cleaner started to work]');
        $maxDeleteAtOnce = 10;
        $query = $setupRepo->createQueryBuilder('setups')
            ->where('setups.disabled = 0')
            ->andWhere('setups.isPrivate = 0')
            ->andWhere('setups.updatedAt <= :theDate')
            ->orderBy('setups.id', 'ASC')
            ->setParameter('theDate', new \DateTime('-'. 3 . ' days'), \Doctrine\DBAL\Types\Type::DATETIME)
            ->setMaxResults(2000);
        ;
        $setups = $query->getQuery()->execute();
        $setupsForDelete = [];
        /** @var LogBookSetup $setup */
        foreach ($setups as $setup) {
            try {
                $sCycles = $setup->getCycles();
                $sCyclesCount = \count($sCycles);
                if ($sCyclesCount === 0 && count($setupsForDelete) <= $maxDeleteAtOnce) {
                    $setupsForDelete[] = $setup;
                } else {
                    $setup->setCyclesCount($sCyclesCount);
                }
                if (count($setupsForDelete) > $maxDeleteAtOnce) {
                    continue;
                }
            } catch (\Throwable $ex) {}

        }

        foreach ($setupsForDelete as $setup) {
            try {
                $this->em->remove($setup);
            } catch (\Throwable $ex) {}

        }

        $this->em->flush();
        if (count($setupsForDelete)) {
            $logger->notice(' #[SETUP_CLEANER]REMOVE ' . count($setupsForDelete) . ' setups');
        }
        $logger->notice(' #[SETUP_CLEANER][ - Setup cleaner FINISH]');
        return array(
            'setupsForDelete'      => $setupsForDelete,
            'setups'  => $setups,
        );
    }


    /**
     * Setup Cycles Counter.
     *
     * @Route("/setups/count_cycles", name="bot_setup_count_cycles", methods={"GET"})
     * @Template(template="log_book_bot/setups.calculate.cycles.html.twig")
     * @param LogBookSetupRepository $setupRepo
     * @return array
     * @throws \Exception
     */
    public function setupCalculateCycles(LogBookSetupRepository $setupRepo, LoggerInterface $logger): array
    {
        $hours = rand(0, 24 * 30);
        $logger->notice(' #[SETUP_CYCLE_COUNT][ + Setup [setupCalculateCycles] started to work] HOURS '. $hours);
        $query = $setupRepo->createQueryBuilder('setups')
            ->where('setups.disabled = 0')
            ->andWhere('setups.updatedAt >= :theDate')
            ->setParameter('theDate', new \DateTime('-'. $hours . ' hours'), \Doctrine\DBAL\Types\Type::DATETIME)
            ->setMaxResults(100);
        $query->addSelect('RAND() as HIDDEN rand')->orderBy('rand()');
        $setups = $query->getQuery()->execute();
        /** @var LogBookSetup $setup */
        foreach ($setups as $setup) {
            try {
                $sCycles = $setup->getCycles();
                $sCyclesCount = \count($sCycles);
                $setup->setCyclesCount($sCyclesCount);
            } catch (\Throwable $ex) {
                $logger->critical(' #[SETUP_CYCLE_COUNT]ERROR ' . $ex->getMessage());
            }

        }
        $this->em->flush();
        $logger->notice(' #[SETUP_CYCLE_COUNT][ - Setup [setupCalculateCycles] FINISH]');
        return array(
            'setups'  => $setups,
        );
    }

    /**
     * @Route("/delete_cycles", name="bot_delete_cycles")
     * @param LogBookCycleRepository $cycleRepo
     * @return Response
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function deleteCycle(LogBookCycleRepository $cycleRepo): Response
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('cycleDelete');
        $qd = $cycleRepo->createQueryBuilder('c')
            ->where('c.forDelete = :for_delete')
            ->setMaxResults(1)
            ->setParameter('for_delete', 1);
        $query = $qd->getQuery();
        $query->execute();
        $cycles = $query->getResult();
        $responseContent = '';

        /** @var LogBookCycle $cycle */
        foreach ((array) $cycles as $cycle) {
            $cycleName = $cycle->getName();
            $cycleId = $cycle->getId();
            $testsFound = $cycle->getTests()->count();
            $responseContent = sprintf('%sRemoving %s:[%d] - tests=%d %s',$responseContent,  $cycleName, $cycleId, $testsFound, "\n");
            $cycleRepo->delete($cycle);
        }

        if ($responseContent === '') {
            $responseContent = "Nothing found for delete\n";
        }
        $event = $stopwatch->stop('cycleDelete');
        $time = new \DateTime();
        $responseContent = sprintf('%s | Duration %d(milliseconds) %s%s', $time->format('Y/m/d H:i:s') , $event->getDuration(), '| ', $responseContent);
        return new Response($responseContent);
    }

    /**
     * @Route("/find_cycles_for_delete", name="find_cycles_for_delete")
     * @param LogBookCycleRepository $cycleRepo
     * @param EventRepository $events
     * @param LoggerInterface $logger
     * @return Response
     * @throws \Exception
     */
    public function findCyclesForDelete(LogBookCycleRepository $cycleRepo, EventRepository $events, LoggerInterface $logger): Response
    {
        $list = $cycleRepo->findByDeleteAt(1500);
        $logger->notice('[BOT][findCyclesForDelete]  START', [
            'COUNT' => count($list)
        ]);
        //$this->log("\n\n" . 'Found ' . count($list));
        /** @var LogBookCycle $cycle */
        $now = new \DateTime('now');
        $counter = $skip = $skipped_due_report = 0;
        $write_skipped = false;
        foreach ($list as $cycle) {
            $cycleReports = $cycle->getLogBookCycleReports();
            if ($cycleReports === null) {
                $cycleReports = [];
            }
            if ( count($cycleReports) === 0 ) {
                $msg = $cycle->getDeleteAt()->format('Y-m-d H:i:s') . ' <= ' . $now->format('Y-m-d H:i:s');
                $type = EventType::DELETE_CYCLE;
                $new_event = new Event($type);
                $name = EventType::getTypeName($type) . '_' . $cycle->getId();
                $new_event->setName($name);
                $new_event->setObjectClass(LogBookCycle::class);
                $new_event->setObjectId($cycle->getId());

                $res = $events->findOneBy(
                    array(
                        'name' => $new_event ->getName(),
                        'eventType' => $new_event->getEventType(),
                        'objectId' => $new_event->getObjectId(),
                        'objectClass' => $new_event->getObjectClass()
                    ));

                if ($res === null) {
                    $new_event->setMessage($name. ' - ' . $msg);
                    $new_event->addMetaData(
                        array(
                            'id' => $new_event->getObjectId(),
                            'class' => $new_event->getObjectClass(),
                            'delete_time' => $cycle->getDeleteAt()->format('Y-m-d H:i:s'),
                            'tests' => $cycle->getTestsCount(),
                            'pass_rate' => $cycle->getPassRate()
                        ));
                    $this->em->persist($new_event);
                    $counter++;
                } else {
                    $skip++;
                    $logger->notice('[BOT][findCyclesForDelete] Skip cycle', [
                        'EVENT' => (string)$new_event,
                        'STATUS' => $new_event->getStatus(),
                        'MESSAGE' => $new_event->getMetaData(),
                    ]);
                    //$this->log($new_event . ' already exist');
                }
            } else {
                $skipped_due_report++;
                if ($write_skipped === true) {
                    $logger->notice('[BOT][findCyclesForDelete] Skip cycle for delete due report existence', [
                        'ID' => $cycle->getId(),
                        'NAME' => $cycle->getName(),
                        'REPORTS_COUNT' => count($cycleReports),
                    ]);
                }

            }

        }
        //$this->log('Finish adding ' . $counter . ' objects, skipped: ' . $skip);
        $this->em->flush();
        $logger->notice('[BOT][findCyclesForDelete]  FINISH', [
            'ADDED_TO_REMOVE' => $counter,
            'SKIPPED' => $skip,
            'SKIPPED_DUE_REPORTS' => $skipped_due_report,
        ]);
        exit();

    }

    /**
     * @param string $msg
     * @throws \Exception
     */
    protected function log(string $msg, bool $monolog = true): void
    {
        $time = new \DateTime();
        echo $time->format('Y-m-d H:i:s') . ' | ' . $msg . "\n";
        if ($monolog === true) {
            $this->logger->notice($msg);
        }
    }

    /**
     * @param EventRepository $events
     * @return Response
     * @throws \Exception
     */
    protected function clearSuccess(EventRepository $events): Response
    {
        $limit = 1000;
        $this->log('-----------------------------------------------------------------', false);
        $list = $events->findBy(
            array(
                'status' => EventStatus::FINISH,
            ),
            null, $limit);
        $monolog = false;
        $found = count($list);
        if ($found > 0) {
            $monolog = true;
        }
        $this->log('Found clearSuccess:' . count($list) . ' to clear,  Limit is ' . $limit, $monolog);
        $counter = 0;
        foreach ($list as $event) {
            $cmp_date = new \DateTime('+10 minutes');
            if ($event->getStartedAt()->format('U') < $cmp_date->format('U')) { //new \DateTime('+7 days')) {
                $this->em->remove($event);
                $counter++;
            }
        }
        $this->log('Removed ' . $counter . ' objects', $monolog);
        if ($counter > 0) {
            $this->em->flush();
        }
        $this->log('Exit', $monolog);
        exit();
    }

    /**
     * @Route("/cycle_event_delete", name="delete_cycle_from_event_table")
     * @param LogBookCycleRepository $cycleRepo
     * @param EventRepository $events
     * @param LoggerInterface $logger
     * @return Response
     * @throws \Exception
     */
    public function deleteCycleByEvent(LogBookCycleRepository $cycleRepo, EventRepository $events, LoggerInterface $logger): Response
    {

        $limit = 15;

        $list = $events->findBy(
            array(
                'eventType' => EventType::DELETE_CYCLE,
                'status' => EventStatus::CREATED,
            ),
            null, $limit);
        $monolog = false;
        $found = count($list);
        if ($found > 0) {
            $monolog = true;
        }
        $logger->notice('[BOT][deleteCycleByEvent] Started' , [
            'LIMIT' => $limit,
            'MONOLOG' => $monolog,
            'FOUND' => $found
        ]);
        try {
            //$this->log('-----------------------------------------------------------------', $monolog);
            //$this->log("\n\n" . 'Found ' . count($list) . ' for PROGRESS, Limit is ' . $limit, $monolog);

            foreach ($list as $event) {
                $event->setStatus(EventStatus::PROGRESS);
                $event->setStartedAt(new \DateTime());
                $this->em->persist($event);
            }

            $this->em->flush();
            //$this->log('Start loop: ', $monolog);
        } catch (\Throwable $ex) {
            $logger->critical('[BOT][deleteCycleByEvent] ERROR on events:' . $ex->getMessage());
        }

        try {
            foreach ($list as $event) {
                //$this->em->refresh($event);
                /** @var LogBookCycle $cycle */
                $cycle = $cycleRepo->find($event->getObjectId());
                try {
                    if ( $cycle !== null ) {
                        $id = $cycle->getId();
                        $name = $cycle->getName();
                        $cycleRepo->delete($cycle);
                        $logger->notice('[BOT][deleteCycleByEvent] DELETE CYCLE' , [
                            'ID' => $id,
                            'NAME' => $name
                        ]);
                        //$this->log('Deleted ID:' . $id . ' - ' . $event);
                        $event->setStatus(EventStatus::FINISH);
                    } else {
                        $msg = EventStatus::getStatusName(EventStatus::ERROR) .
                            ':Object with ID ' . $event->getObjectId() . ' not found';
                        //$this->log('Failed to delete ' . $event. ' ['.$msg.']');
                        $logger->alert('[BOT][deleteCycleByEvent] FAIL in DELETE CYCLE' , [
                            'EVENT' => $event,
                            'MSG' => $msg
                        ]);
                        $event->addMetaData(array('message' => $msg));
                        $event->setStatus(EventStatus::ERROR);
                    }

                } catch (\Throwable $ex) {
                    $logger->critical('[BOT][deleteCycleByEvent] ERROR:' . $ex->getMessage());
                    $msg = EventStatus::getStatusName(EventStatus::ERROR) . ':' . $ex->getMessage();
                    $print_msg = 'Failed to delete ' . $event. ' ['.$msg.']';
                    $this->log($print_msg);
                    $this->logger->critical($print_msg, array($ex));
                    $event->addMetaData(array('message' => $msg));
                    $event->setStatus(EventStatus::ERROR);
                }
                $this->em->persist($event);
            }
            $this->em->flush();

        } catch (\Throwable $ex) {
            $logger->critical('[BOT][deleteCycleByEvent] ERROR on cycles:' . $ex->getMessage());
        }

        $this->log('===================================================================', $monolog);
        $logger->notice('[BOT][deleteCycleByEvent] FINISH');
        $this->clearSuccess($events);
        exit();
    }

    /**
     * @param LogBookCycleRepository $cycleRepo
     * @param LogBookSetupRepository $setupRepo
     * @return Response
     * @throws \Exception
     */
    public function deleteCycleRetention(LogBookCycleRepository $cycleRepo, LogBookSetupRepository $setupRepo): Response
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('deleteCycleRetention');
        $em = $this->getDoctrine()->getManager();
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('target_date', 'target_date');
        $rsm->addScalarResult('late_minutes', 'late_minutes');
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('name', 'name');
        $rsm->addScalarResult('name_shown', 'name_shown');
        $rsm->addScalarResult('is_disabled', 'is_disabled');
        $rsm->addScalarResult('is_private', 'is_private');
        $rsm->addScalarResult('created_at', 'created_at');
        $rsm->addScalarResult('updated_at', 'updated_at');
        $rsm->addScalarResult('retention_policy', 'retention_policy');
        $sql = '
            SELECT 
                *,
                NOW() - INTERVAL s.retention_policy DAY as target_date,
                NOW() - INTERVAL s.retention_policy DAY as late_minutes
            FROM lbook_setups s
            WHERE
                s.updated_at < (NOW() - INTERVAL s.retention_policy DAY)
            AND
                s.is_disabled = 0
            LIMIT
                100';
        /** @var NativeQuery $setups_nq */
        $setups_nq = $em->createNativeQuery($sql, $rsm);
        //$setups_nq->setParameter(':target_date', 'NOW()-INTERVAL s.retention_policy DAY');
        //$setups_nnq = $setupRepo->createNativeNamedQuery('s');
        //$qd_setups = $setupRepo->createNativeNamedQuery()
//            ->where('s.updatedAt < :this_day')
//            //->where('s.updatedAt < :this_day')
//            //->where('(s.updated_at > (s.updated_at + INTERVAL s.retention_policy DAY))')
//            ->andWhere('s.disabled = 0')
//            //->where('(s.updatedAt > (s.updatedAt + INTERVAL s.retentionPolicy DAY))')
//            //->andWhere('s.disabled = 0')
//            //->setParameter(':now',  new \DateTime('now'))
//            ->setParameter(':this_day', 'CURRENT_TIMESTAMP()-INTERVAL s.retentionPolicy DAY')
        //->setParameter(':this_day', 'CURRENT_TIMESTAMP()-INTERVAL s.retentionPolicy DAY')

//
//        $qd_setups = $setupRepo->createQueryBuilder('s')
//            ->where('s.updatedAt < :this_day')
//            //->where('s.updatedAt < :this_day')
//            //->where('(s.updated_at > (s.updated_at + INTERVAL s.retention_policy DAY))')
//            ->andWhere('s.disabled = 0')
//            //->where('(s.updatedAt > (s.updatedAt + INTERVAL s.retentionPolicy DAY))')
//            //->andWhere('s.disabled = 0')
//            //->setParameter(':now',  new \DateTime('now'))
//            ->setParameter(':this_day', 'CURRENT_TIMESTAMP()-INTERVAL s.retentionPolicy DAY')
//            //->setParameter(':this_day', 'CURRENT_TIMESTAMP()-INTERVAL s.retentionPolicy DAY')
//            ->setMaxResults(100);
//        $query_setups = $qd_setups->getQuery();
//        $query_setups->execute();
        $setups = $setups_nq->getResult();
//
//        echo $query_setups->getSQL() . "<br/>";
//        echo $query_setups->getDQL() . "<br/>";
//        echo "<pre>";
//        print_r($query_setups->getParameters());
//        $setups = $query_setups->getResult(); // \Doctrine\ORM\Query::HYDRATE_ARRAY
        //print_r($setups);
        echo 'Setups Count: ' . count($setups) . '<br/></pre>';
//        try {
//            foreach ( $setups as $setup) {
//                echo "Setup " . $setup->getId() . ' ' . $setup->getName() . "<br/>";
//            }
//        } catch (\Exception $ex) { }

        try {
            foreach ( $setups as $setup) {
                echo 'Setup [' . $setup['id'] . ':' . $setup['name'] . '] Target Date:' . $setup['target_date'] . '<br/>';
                echo '<pre>';
                print_r($setup);
                echo '</pre>';
            }
        } catch (\Exception $ex) { }

        exit();
        $qd = $cycleRepo->createQueryBuilder('c')
            ->where('c.keepForever = :keep_forever')
            ->andWhere('c.createdAt > :created_at')
            ->setMaxResults(1)
            ->setParameter('keep_forever', 0)
            ->setParameter('created_at', 'c.createdAt + ');
        $query = $qd->getQuery();
        $query->execute();
        $cycles = $query->getResult();
        $responseContent = '';
        /** @var LogBookCycle $cycle */
        foreach ((array) $cycles as $cycle) {
            $cycleName = $cycle->getName();
            $cycleId = $cycle->getId();
            $testsFound = $cycle->getTests()->count();
            $responseContent = sprintf('%sRemoving %s:[%d] - tests=%d %s',$responseContent,  $cycleName, $cycleId, $testsFound, "\n");
            $cycleRepo->delete($cycle);
        }

        if ($responseContent === '') {
            $responseContent = "Nothing found for delete\n";
        }
        $event = $stopwatch->stop('deleteCycleRetention');
        $time = new \DateTime();
        $responseContent = sprintf('%s | Duration %d(milliseconds) %s%s', $time->format('Y/m/d H:i:s') , $event->getDuration(), '| ', $responseContent);
        return new Response($responseContent);
    }
}
