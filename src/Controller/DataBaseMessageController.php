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
        $ret2 = [];
        $setup_not_exist = 0;
        $deletedTables = 0;
        foreach ($res as $r) {
            $ret = [];
            try {
                $table_size = $r['table_size'];
                $table_name = $r['table_name'];
                $ret['table_size'] = $table_size;
                $ret['table_name'] = $table_name;
                $ret['found'] = 'WIP';
            } catch (\Throwable $ex) {
                $logger->critical('DataBaseMessageController:Cannot get tabl size : SQL: ' . $sql );
            }
            try {
                $setupId = (int)str_replace('log_book_message_' , '', $table_name);
                if ($setupId > 0) {
                    $setup = $setupRepo->findById($setupId);
                    if ($setup !== null) {
                        $setup->setLogsSize($table_size);
                        $ret['found'] = 'Exist';
                    } else {
                        $ret['found'] = 'Setup NOT EXIST';
                        $setup_not_exist++;
                        if ($deletedTables < 10) {
                            try{
                                $sql = 'DROP TABLE log_book_message_' . $setupId;
                                $connection->prepare($sql)->execute();
                                $deletedTables++;
                            } catch (\Throwable $ex) {
                                $this->logger->critical('/database/messages: Cannot drop table :' . $setupId , $ex->getTrace());
                            }
                        }
                    }
                } else {
                    $ret['found'] = 'Setup id not parsed';
                }
            } catch (\Throwable $ex) {
                $logger->critical('DataBaseMessageController:Cannot update table size for : ' . $table_name . ' : ' . $ex->getMessage());
            }
            $ret2[] = $ret;
        }
        $em->flush();

        return $this->render('data_base_message/index.html.twig', [
            'tables' => $ret2,
            'databaseName' => $databaseName,
            'setup_not_exist' => $setup_not_exist,
        ]);
    }
}
