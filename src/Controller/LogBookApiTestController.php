<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class LogBookApiTestController extends AbstractController
{
    /**
     * @Route("/log/book/api/test", name="log_book_api_test")
     */
    public function index()
    {
        return $this->render('log_book_api_test/index.html.twig', [
            'controller_name' => 'LogBookApiTestController',
        ]);
    }
}
