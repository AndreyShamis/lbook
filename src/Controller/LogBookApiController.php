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
            'functions' => get_class_methods($this)
        ]);
    }


    /**
     *
     * @Route("/execution/publisher/count", name="count_suites_for_publisher", methods={"GET", "POST"})
     * @param LoggerInterface $logger
     * @param SuiteExecutionRepository $suitesRepo
     * @param int $state
     * @return JsonResponse
     */
    public function suitesNotPublishedCount(LoggerInterface $logger, SuiteExecutionRepository $suitesRepo, int $state=0): ?JsonResponse
    {
        try {
            $suites = $suitesRepo->findAllNotPublished($state);
            $fin_res['state'] = $state;
            $fin_res['count'] = count($suites);
            return new JsonResponse($fin_res);

        } catch (\Throwable $ex) {
            $logger->critical('ERROR :' . $ex->getMessage());
            $response = $this->json([]);
            $arr[] = $ex->getMessage();
            $prev = $ex->getPrevious();
            if ($prev !== null) {
                $arr[] = $prev->getMessage();
            }
            $js = json_encode($arr);
            $response->setJson($js);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;
        }
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

                if ($suite->getState() === 0) {
                    $suite->setState(1);
                    $this->em->flush();
                }
                return $response;
            }
            $fin_res['message'] = 'Suites not found';
            $fin_res['state'] = $state;
            return new JsonResponse($fin_res);

        } catch (\Throwable $ex) {
            $logger->critical('ERROR :' . $ex->getMessage());
            $response = $this->json([]);
            $arr[] = $ex->getMessage();
            $prev = $ex->getPrevious();
            if ($prev !== null) {
                $arr[] = $prev->getMessage();
            }
            $js = json_encode($arr);
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
            $status = 200;
            $fin_res = array();

            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                $data = array();
            }
            if (!array_key_exists('test_execution_key', $data)) {
                $data['test_execution_key'] = '';
                $fin_res['message'] = 'test_execution_key not provided';
                $status = 400;
            } else if (mb_strlen($data['test_execution_key']) < 15) {
                $fin_res['message'] = 'Bad test_execution_key provided';
                $status = 400;
            }

            if (!array_key_exists('test_set_url', $data)) {
                $data['test_set_url'] = '';
                $fin_res['message'] = 'test_set_url not provided';
                $status = 400;
            } else if (mb_strlen($data['test_set_url']) < 5) {
                $fin_res['message'] = 'Bad test_set_url provided';
                $status = 400;
            }
            if ($status === 200) {
                if ($suite !== null) {
                    if ($suite->getState() === 1) {
                        $suite->setState(2);
                        $suite->setTestSetUrl($data['test_set_url']);
                        $suite->setJiraKey($data['test_execution_key']);
                        $this->em->flush();
                        $fin_res['message'] = 'success';
                    } else {
                        $fin_res['message'] = 'cannot convert state from ' . $suite->getState() . ' to 2';
                        $status = 400;
                    }
                } else {
                    $fin_res['message'] = 'Suites not found';
                    $status = 400;
                }
            }
            $response =  new JsonResponse($fin_res, $status);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;

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
                if ($suite->getState() === 2 || $suite->getState() === 3) {
                    $suite->setState(3);
                    $this->em->flush();
                    $response = $this->json([]);
                    /** @var LogBookCycle $cycle */
                    $cycle = $suite->getCycle();
                    $tests = $cycle->getTests();
                    $tests_arr = array();
                    foreach ($tests as $test) {
                        try {
                            if ($suite->getId() === $test->getSuiteExecution()->getId()) {
                                $tests_arr[$test->getId()] = $test->toArray();
                            }
                        } catch (\Throwable $ex) { }

                    }
                    $js = json_encode($tests_arr);
                    $response->setJson($js);
                    $response->setEncodingOptions(JSON_PRETTY_PRINT);
                    return $response;
                }

                $fin_res['message'] = 'cannot convert state from ' . $suite->getState() . ' to 3';
            } else {
                $fin_res['message'] = 'Suite not found';
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
     * @Route("/execution/publisher/move_to_4/{suite}", name="publisher_move_to_4", methods={"GET", "POST"})
     * @param SuiteExecution $suite
     * @param LoggerInterface $logger
     * @return JsonResponse
     */
    public function moveTo4(SuiteExecution $suite, LoggerInterface $logger): ?JsonResponse
    {
        try {
            if ($suite !== null) {
                if ($suite->getState() === 3) {
                    $suite->setState(4);
                    $this->em->flush();
                    $fin_res['message'] = 'success';
                } elseif ($suite->getState() === 4) {
                    $fin_res['message'] = 'already in state 4';
                } else {
                    $fin_res['message'] = 'cannot covnert state from ' . $suite->getState() . ' to 4';
                }
            } else {
                $fin_res['message'] = 'Suite not found';
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
     * @Route("/execution/publisher/execution/{suite}", name="suiteExecutionApi", methods={"GET", "POST"})
     * @param SuiteExecution $suite
     * @param LoggerInterface $logger
     * @param Request $request
     * @return JsonResponse
     */
    public function suiteExecution(SuiteExecution $suite, LoggerInterface $logger, Request $request): ?JsonResponse
    {
        $status = 200;
        try {
            if ($suite !== null) {
                $data = json_decode($request->getContent(), true);
                if ($data === null) {
                    $data = $request->query->all();
                }
                if ($data === null) {
                    $data = array();
                }
                if (!array_key_exists('operation', $data)) {
                    $data['operation'] = 'info';
                }

                $op = $data['operation'];
                if ($op === 'info') {
                    $fin_res = self::toArray($suite);
                }
                if ($op === 'update_state') {
                    if (!array_key_exists('state', $data)) {
                        $fin_res['message'] = 'state not provided use state={0,1,2,3,4}';
                        $status = 400;
                    } else {
                        $state = (int)$data['state'];
                        if ($state < 0 || $state > 4) {
                            $fin_res['message'] = 'state cannot be ' . $state . '.';
                            $status = 400;
                        } else {
                            $suite->setState($state);
                            if ($state === 0 && $suite->getJiraKey() === '') {
                                $suite->setJiraKey(null);
                            }
                            $this->em->flush();
                            $fin_res = self::toArray($suite);
                        }
                    }
                }
                if (!array_key_exists('test_execution_key', $data)) {
                    $data['test_execution_key'] = '';
                }
                if (!array_key_exists('test_set_url', $data)) {
                    $data['test_set_url'] = '';
                }
            } else {
                $fin_res['message'] = 'Suite not found';
            }
            $response =  new JsonResponse($fin_res, $status);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;

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
     * @param $object
     * @return array
     * @throws \ReflectionException
     */
    private static function toArray($object): array
    {
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
                }
                if ($pName === 'cycle') {
                    $array['cycle_id'] = $value->getId();
                    //$array[$pName] = self::toArray($value);
                    $array['setup_id'] = $value->getSetup()->getId();
                }
                continue;
            }
            $array[$pName] = $value;
        }
        return $array;
    }
}
