<?php

namespace App\Controller;

use Doctrine\ORM\NativeQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DataBaseMessageController extends AbstractController
{
    /**
     * @Route("/database/messages", name="data_base_message")
     */
    public function index()
    {
        $sql = "SELECT DISTINCT(TABLE_NAME) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA LIKE 'lbook' AND TABLE_NAME LIKE 'log_book_message%'";
        $em = $this->getDoctrine()->getManager();
        /** @var \Doctrine\DBAL\Statement $statment */
        $statment = $em->getConnection()->prepare($sql);
        $statment->execute();
        $res = $statment->fetchAll();
        return $this->render('data_base_message/index.html.twig', [
            'tables' => $res,
        ]);
    }
}
