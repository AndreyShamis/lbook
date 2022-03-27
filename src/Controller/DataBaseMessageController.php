<?php

namespace App\Controller;

use Doctrine\ORM\NativeQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use App\Repository\LogBookSetupRepository;

class DataBaseMessageController extends AbstractController
{
    /**
     * @Route("/database/messages", name="data_base_message")
     */
    public function index(ManagerRegistry $doctrine, LogBookSetupRepository $setupRepo, LoggerInterface $logger)
    {
        $em = $doctrine->getManager();
        $connection = $em->getConnection();
        $databaseName = $connection->getDatabase();
        $sql = "SELECT TABLE_NAME AS `table_name`, ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024) AS `table_size` FROM information_schema.TABLES WHERE TABLE_SCHEMA LIKE '".$databaseName."' AND TABLE_NAME LIKE 'log_book_message%' AND TABLE_NAME != 'log_book_message'";
        //"SELECT DISTINCT(TABLE_NAME) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA LIKE '".$databaseName."' AND TABLE_NAME LIKE 'log_book_message%'";
        /** @var \Doctrine\DBAL\Statement $statment */
        $statment = $connection->prepare($sql);
        $statment->execute();
        $res = $statment->fetchAll();

        $res = $statment->fetchAll();
        foreach ($res as $r) {
            try {
                $table_size = $r[0]['table_size'];
                $table_name = $r[0]['table_name'];
            } catch (\Throwable $ex) {
                $logger->critical('DataBaseMessageController:Cannot get tabl size : SQL: ' . $sql );
            }
            try {
                $setupId = (int)str_replace('log_book_message_' , '', $table_name)
                if ($setupId > 0) {
                    $setup = $setupRepo->findById($setupId);
                    $setup->setLogsSize($table_size);
                    $r[0]['found'] = '1';
                } else {
                    $r[0]['found'] = '0';
                }
                
            } catch (\Throwable $ex) {
                $logger->critical('DataBaseMessageController:Cannot update table size for : ' . $table_name . ' : SQL: ' . $sql );
            }
        }
        $em->flush();

        return $this->render('data_base_message/index.html.twig', [
            'tables' => $res,
            'databaseName' => $databaseName,
        ]);
    }
}
