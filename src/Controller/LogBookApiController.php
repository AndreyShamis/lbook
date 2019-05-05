<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\SuiteExecution;
use App\Repository\SuiteExecutionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

/**
 * Class LogBookApiController
 * @package App\Controller
 * @Route("api")
 */
class LogBookApiController extends AbstractController
{
    /** @var \Doctrine\Common\Persistence\ObjectManager  */
    protected $em;

    /**
     * @param Container $container
     * @throws \LogicException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->em = $this->getDoctrine()->getManager();

    }

    /**
     * @Route("/", name="api_index")
     */
    public function index(): Response
    {
        return $this->render('log_book_api/index.html.twig', [
            'controller_name' => 'LogBookApiController_api_index',
        ]);
    }

    /**
     * @Route("/execution/", name="api_execution_index")
     */
    public function executionIndex(): Response
    {
        return $this->render('log_book_api/execution.index.html.twig', [
            'controller_name' => 'LogBookApiController_executionIndex',
        ]);
    }


    /**
     *
     * @Route("/execution/publisher/state/{state}", name="publisher_by_state", methods={"GET", "POST"})
     * @param LoggerInterface $logger
     * @param SuiteExecutionRepository $suitesRepo
     * @param int $state
     * @return JsonResponse
     */
    public function getOneByState(LoggerInterface $logger, SuiteExecutionRepository $suitesRepo, int $state=0): ?JsonResponse
    {
        try {
            $suite = $suitesRepo->findOneBySate($state);
            if ($suite !== null) {
                $response = $this->json([]);
                $js = json_encode(self::toArray($suite));
                $response->setJson($js);
                $response->setEncodingOptions(JSON_PRETTY_PRINT);
                return $response;
            }
            $fin_res['message'] = 'Suites not found';
            $fin_res['state'] = $state;
            return new JsonResponse($fin_res);

        } catch (\Throwable $ex) {
            $logger->critical('ERROR :' . $ex->getMessage());
            $response = $this->json([]);
            $js = json_encode('["'. $ex->getMessage() .'"]');
            $response->setJson($js);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;
        }
    }

    /**
     *
     * @Route("/execution/publisher/move_to_2/{suite}", name="publisher_move_to_2", methods={"POST"})
     * @param SuiteExecution $suite
     * @param Request $request
     * @param LoggerInterface $logger
     * @return JsonResponse
     */
    public function moveTo2(SuiteExecution $suite, Request $request, LoggerInterface $logger): ?JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                $data = array();
            }
            if (!array_key_exists('test_execution_key', $data)) {
                $data['test_execution_key'] = '';
            }
            if (!array_key_exists('test_set_url', $data)) {
                $data['test_set_url'] = '';
            }
            if ($suite !== null) {
                if ($suite->getState() === 1) {
                    $suite->setState(2);
                    $suite->setTestSetUrl($data['test_set_url']);
                    $suite->setJiraKey($data['test_execution_key']);
                    $this->em->flush();
                    $fin_res['message'] = 'success';
                } else {
                    $fin_res['message'] = 'cannot covnert state from ' . $suite->getState() . ' to 2';
                }
            } else {
                $fin_res['message'] = 'Suites not found';
            }
            return new JsonResponse($fin_res);

        } catch (\Throwable $ex) {
            $logger->critical('ERROR :' . $ex->getMessage());
            $response = $this->json([]);
            $js = json_encode('["'. $ex->getMessage() .'"]');
            $response->setJson($js);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;
        }
    }

    /**
     *
     * @Route("/execution/publisher/move_to_3/{suite}", name="publisher_move_to_3", methods={"POST"})
     * @param SuiteExecution $suite
     * @param Request $request
     * @param LoggerInterface $logger
     * @return JsonResponse
     */
    public function moveTo3(SuiteExecution $suite, Request $request, LoggerInterface $logger): ?JsonResponse
    {
        try {
            if ($suite !== null) {
                // TODO Remove  || $suite->getState() === 3
                if ($suite->getState() === 2 || $suite->getState() === 3) {
                    $suite->setState(3);
                    $this->em->flush();
                    $response = $this->json([]);
                    /** @var LogBookCycle $cycle */
                    $cycle = $suite->getCycle();
                    $tests = $cycle->getTests();
                    $tests_arr = array();
                    foreach ($tests as $test) {
                        $tests_arr[$test->getId()] = $test->toArray();
                    }
                    $js = json_encode($tests_arr);
                    $response->setJson($js);
                    $response->setEncodingOptions(JSON_PRETTY_PRINT);
                    return $response;
                } else {
                    $fin_res['message'] = 'cannot covnert state from ' . $suite->getState() . ' to 2';
                }
            } else {
                $fin_res['message'] = 'Suites not found';
            }
            return new JsonResponse($fin_res);

        } catch (\Throwable $ex) {
            $logger->critical('ERROR :' . $ex->getMessage());
            $response = $this->json([]);
            $js = json_encode('["'. $ex->getMessage() .'"]');
            $response->setJson($js);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;
        }
    }

    private static function toArray($object) {
        $reflectionClass = new \ReflectionClass($object);

        $properties = $reflectionClass->getProperties();

        $array = [];
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($object);
            /** @var string $pName */
            $pName = $property->getName();
            if (is_object($value)) {
                if (strpos($pName, '__') !== false) {
                    continue;
                } else {
                    if ($pName === 'cycle') {
                        $array['cycle_id'] = $value->getId();
                        $array[$pName] = self::toArray($value);
                    }
                    if ($pName === 'tests') {
                        $array[$pName] = self::toArray($value);
                    }
                    continue; //$array[$pName] = self::toArray($value);
                }
            }
//            else if (is_array($value)) {
//                if ($pName === 'snapshot') {
//                    //$array[$pName] = self::toArray($value);
//                    // }
//                    foreach ($value as $key => $val) {
//                        $array[$pName][$key] = self::toArray($val);
//                    }
//                }
//            }
            else {
                $array[$pName] = $value;
            }
        }
        return $array;
    }
}
