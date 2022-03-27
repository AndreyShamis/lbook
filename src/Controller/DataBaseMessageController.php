<?php

namespace App\Controller;

use Doctrine\ORM\NativeQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class DataBaseMessageController extends AbstractController
{
    /**
     * @Route("/database/messages", name="data_base_message")
     */
    public function index(ManagerRegistry $doctrine)
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
        return $this->render('data_base_message/index.html.twig', [
            'tables' => $res,
        ]);
    }
}
