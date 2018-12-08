<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Utils\RandomString;

class LogBookTestingController extends AbstractController
{
    /**
     * @Route("/debug", name="log_book_testing")
     */
    public function index(): void
    {
        echo RandomString::generateRandomString(120) . '<br/>';
        echo RandomString::generateRandomString(120, true) . '<br/>';

        echo RandomString::generateRandomStringShuffle(120) . '<br/>';
        echo RandomString::generateRandomStringShuffle(120, true) . '<br/>';

        echo RandomString::generateRandomStringRange(120) . '<br/>';

        echo RandomString::generateRandomStringSha1(120) . '<br/>';
        echo RandomString::generateRandomStringMd5(120) . '<br/>';
        exit();
    }
}
