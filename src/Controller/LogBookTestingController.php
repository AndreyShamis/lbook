<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Utils\RandomString;

class LogBookTestingController extends Controller
{
    /**
     * @Route("/debug", name="log_book_testing")
     */
    public function index()
    {
        echo RandomString::generateRandomString(120) . "<br/>";
        echo RandomString::generateRandomString(120, true) . "<br/>";

        echo RandomString::generateRandomStringShuffle(120) . "<br/>";
        echo RandomString::generateRandomStringShuffle(120, true) . "<br/>";

        echo RandomString::generateRandomStringRange(120) . "<br/>";

        echo RandomString::generateRandomStringSha1(120) . "<br/>";
        echo RandomString::generateRandomStringMd5(120) . "<br/>";
        exit();
        // replace this line with your own code!
        return $this->render('@Maker/demoPage.html.twig', [ 'path' => str_replace($this->getParameter('kernel.project_dir').'/', '', __FILE__) ]);
    }
}
