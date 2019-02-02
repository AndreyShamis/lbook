<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookTest;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookTestRepository;
use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Form\LogBookCycleType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Service\PagePaginator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Cycle controller.
 *
 * @Route("cycle")
 */
class LogBookCycleController extends AbstractController
{
    protected $index_size = 1000;
    protected $show_tests_size = 200;

    /**
     * Lists all cycle entities.
     *
     * @Route("/page/{page}", name="cycle_index", methods={"GET"})
     * @Template(template="lbook/cycle/index.html.twig")
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookCycleRepository $cycleRepo
     * @return array
     */
    public function index(PagePaginator $pagePaginator, LogBookCycleRepository $cycleRepo, $page = 1): array
    {
//        $em = $this->getDoctrine()->getManager();
//        $cycleRepo = $em->getRepository('App:LogBookCycle');
        $query = $cycleRepo->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');
        $paginator = $pagePaginator->paginate($query, $page, $this->index_size);
        //$posts = $this->getAllPosts($page); // Returns 5 posts out of 20
        // You can also call the count methods (check PHPDoc for `paginate()`)
        //$totalPostsReturned = $paginator->getIterator()->count(); # Total fetched (ie: `5` posts)
        $totalPosts = $paginator->count(); # Count of ALL posts (ie: `20` posts)
        $iterator = $paginator->getIterator(); # ArrayIterator

        $maxPages = ceil($totalPosts / $this->index_size);
        $thisPage = $page;
        return array(
            'size'      => $totalPosts,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'  => $iterator,
            'paginator' => $paginator,
        );
    }

    protected function getLogsFolder(LogBookCycle $cycle = null): string
    {
        if ($cycle === null) {
            return '';
        }
        $setup = $cycle->getSetup();
        $tmp = '%s/%d/%d/';
        return sprintf($tmp,  LogBookUploaderController::getUploadPath(), $setup->getId(), $cycle->getId());
    }

    /**
     * Download full cycle as archive
     *
     * @Route("/{id}/download", name="cycle_download", methods={"GET"})
     * @param LogBookCycle|null $cycle
     * @return Response
     */
    public function downloadArchive(LogBookCycle $cycle = null): Response
    {
        try {
            if (!$cycle) {
                throw new \RuntimeException('');
            }
            $fileSystem = new Filesystem();
            $path = $this->getLogsFolder($cycle);

            $zip = new \ZipArchive();
            $zipName = sprintf('%d__%d__%s.zip', $cycle->getSetup()->getId(), $cycle->getId(), $cycle->getName());
            $zipName = preg_replace('/[^a-zA-Z0-9\-\_\.\(\)\s]/', '', $zipName);

            $zip->open($zipName,  \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            /** @var LogBookTest $test */
            foreach ($cycle->getTests() as $test) {
                $log_path = $path . $test->getLogFile();
                if ($fileSystem->exists($log_path)) {
                    $fixedFileName = str_replace(array('/', '\\'), '_', $test->getName());
                    $newFileName = $test->getExecutionOrder() . '__' . $fixedFileName . '.txt';
                    $zip->addFromString(basename($newFileName),  file_get_contents($log_path));
                }
            }

            $zip->close();
            $response = new Response(file_get_contents($zipName));
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
            $response->headers->set('Content-length', filesize($zipName));
            try {
                $em = $this->getDoctrine()->getManager();
                $cycle->increaseDownloads();
                $em->persist($cycle);
                $em->flush();
            } catch (\Exception $ex) {

            }
            return $response;
        } catch (\Throwable $ex) {
            return $this->cycleNotFound($ex, $cycle);
        }
    }

    /**
     * Lists all cycle entities.
     *
     * @Route("/", name="cycle_index_first", methods={"GET"})
     * @Template(template="lbook/cycle/index.html.twig")
     * @param PagePaginator $pagePaginator
     * @param LogBookCycleRepository $cycleRepo
     * @return array
     */
    public function indexFirst(PagePaginator $pagePaginator, LogBookCycleRepository $cycleRepo): array
    {
        return $this->index($pagePaginator, $cycleRepo, 1);
    }

    /**
     * Creates a new cycle entity.
     *
     * @Route("/new", name="cycle_new", methods={"GET|POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \LogicException
     * @throws \Exception
     */
    public function newAction(Request $request)
    {
        $obj = new LogBookCycle();
        $form = $this->createForm(LogBookCycleType::class, $obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($obj);
            $em->flush();

            return $this->redirectToRoute('cycle_show', array('id' => $obj->getId()));
        }

        return $this->render('lbook/cycle/new.html.twig', array(
            'cycle' => $obj,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a cycle entity with paginator.
     *
     * @Route("/json/{id}", name="cycle_tests_json", methods={"GET"})
     * @param LogBookCycle $cycle
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @return JsonResponse
     */
    public function cycleTestsJson(PagePaginator $pagePaginator, LogBookTestRepository $testRepo, LogBookCycle $cycle): ?JsonResponse
    {
        try {
            if (!$cycle) {
                throw new \RuntimeException('');
            }

            $qb = $testRepo->createQueryBuilder('t')
              // ->select('t')
               ->addSelect('v.name as verdict')
                //->addSelect('v.name ')
              //  ->from('App:LogBookVerdict', 'ver')
                ->innerJoin('App:LogBookVerdict', 'v', 'WITH', 'v.id = t.verdict')
              //  ->leftJoin('t.verdict', 'v')
                ->where('t.cycle = :cycle')
                ->andWhere('t.disabled = :disabled')
                ->orderBy('t.executionOrder', 'ASC')
                ->setParameters(['cycle'=> $cycle->getId(), 'disabled' => 0]);
            $q = $qb->getQuery();
            $sql = $q->getSQL();
            $encoder = new JsonEncoder();
            $normalizer = new ObjectNormalizer();

//            //$normalizer->setCircularReferenceLimit(0);
//            $dateTimeToStr = function ($dateTime) {
//                return $dateTime instanceof \DateTime ? $dateTime->format(\DateTime::ATOM) : ''; //'d/m/Y H:i:s'
//            };
//            $tests = function ($test) {
//                return $test instanceof LogBookTest ? $test->getName() : ''; //'d/m/Y H:i:s'
//            };
//            $verdicts = function ($verdict) {
//                return $verdict instanceof LogBookVerdict ? $verdict->getName() : ''; //'d/m/Y H:i:s'
//            };
//            $logs = function ($log) {
//                return  ''; //'d/m/Y H:i:s'
//            };
////          $owner_callback = function ($owner) {
////              return $owner instanceof LogBookUser ? $owner->getUsername() : '';
////          };
//            $counter_callback = function ($obj) {
//                return $obj instanceof Collection ? \count($obj) : 0;
//            };
//            $normalizer->setCallbacks([
////                'id' => $counter_callback,
////                'name' => $owner_callback,
//                'log' => $logs,
//                'verdict' => $verdicts,
//                'test' => $tests,
//                'timeStart' => $dateTimeToStr,
//                'timeEnd' => $dateTimeToStr
//            ]);
            $serializer = new Serializer(array($normalizer), array($encoder));

            $paginator = $pagePaginator->paginate($qb, 1, $this->show_tests_size*10);
            //$paginator->setUseOutputWalkers(false);
            //$res = $paginator->getQuery()->execute(null,Query::HYDRATE_ARRAY);
            $res = $paginator->getQuery()->getResult(Query::HYDRATE_ARRAY);
//            $additional_cols = array();
//            $additional_opt_cols = array();

            foreach ($res as $key => $val) {
                $test = $val[0];
                $verdict = $val['verdict'];
                $test['verdict'] = $verdict;
                $val = $test;
                //$val['timeStart'] = $val['timeStart']->format('H:i:s');
                $val['timeStart'] = $val['timeStart']->format(\DateTime::ATOM);
                //$val['timeEnd'] = $val['timeEnd']->format('H:i:s');
                $val['timeEnd'] = $val['timeEnd']->format(\DateTime::ATOM);
                unset($val['disabled'], $val['logFile'], $val['dutUpTimeStart'], $val['dutUpTimeEnd'], $val['forDelete']);
                if ($val['meta_data'] !== null) {
                    foreach ($val['meta_data'] as $md_key => $md_val) {
                        $val[$md_key] = $md_val;
                    }
                }
                unset($val['meta_data']);
                $res[$key] = $val;
            }
            $fin_res['total'] = $paginator->count();
            $fin_res['rows'] = $res;
            return new JsonResponse($fin_res);
            //$json = $serializer->serialize($fin_res, 'json');
            //return new JsonResponse($json, 200, array(), true);

//            $response = $this->json([]);
//            $response->setJson($json);
//            $response->setContent('text/json');
//            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;
        } catch (\Throwable $ex) {
            $response = $this->json([]);
            $js = json_encode('["'. $ex->getMessage() .'"]');
            $response->setJson($js);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;
        }
    }

    /**
     * @Route("/{id}", name="cycle_show_first", methods={"GET"})
     * @Route("/{id}/{maxSize}", name="cycle_show_size", methods={"GET"}, defaults={"maxSize"=""})
     * @Route("/{id}/{maxSize}/{page}", name="cycle_show_page", methods={"GET"}, defaults={"page"=1, "maxSize"=200})
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @param LogBookCycle $cycle
     * @param int $maxSize
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showFirstPage(PagePaginator $pagePaginator, LogBookTestRepository $testRepo, LogBookCycle $cycle = null, $page = null, $maxSize = null): ?Response
    {
        if ($page === null) {
            $page = 1;
        }
        if ($maxSize === null || $maxSize == "" || $maxSize == "1") {
            $maxSize = $this->show_tests_size;
        }
        $page = (int)$page;
        $maxSize = (int)$maxSize;
        return $this->show($pagePaginator, $testRepo, $cycle, $page, false, $maxSize);
    }

//    /**
//     * @Route("/{id}/{maxSize<\d+>?1}/{page<\d+>?1}", name="cycle_show_page", methods={"GET"}, defaults={"page"="", "maxSize"=2000})
//     * @Route("/{id}/{maxSize<\d+>?1}", name="cycle_show_page_2", methods={"GET"}, defaults={"page"="", "maxSize"=""})
//     * @param PagePaginator $pagePaginator
//     * @param LogBookTestRepository $testRepo
//     * @param LogBookCycle $cycle
//     * @param int $page
//     * @return \Symfony\Component\HttpFoundation\Response
//     */
//    public function show_page(PagePaginator $pagePaginator, LogBookTestRepository $testRepo, LogBookCycle $cycle = null, $page = null, $maxSize = null) : ?Response
//    {
//        if ($page === null) {
//            $page = 1;
//        }
//        if ($maxSize === null) {
//            $maxSize = $this->show_tests_size;
//        }
//        return $this->show($pagePaginator, $testRepo, $cycle, $page, false, $maxSize);
//    }

    /**
     * Finds and displays a cycle entity with paginator.
     *
     * @Route("/{id}/{maxSize<\d+>?1}/{page<\d+>?1}/use_json/{forJson}", name="cycle_show", methods={"GET"})
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @param LogBookCycle $cycle
     * @param int $page
     * @param bool $forJson if True the JSON table for tests will be used
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(PagePaginator $pagePaginator, LogBookTestRepository $testRepo, LogBookCycle $cycle = null, $page = null, $forJson=false, $maxSize=null): ?Response
    {
        if ($page === null) {
            $page = 1;
        }
        if ($maxSize === null) {
            $maxSize = $this->show_tests_size;
        }
        try {
            if (!$cycle) {
                throw new \RuntimeException('');
            }

            $qb = $testRepo->createQueryBuilder('t')
                ->where('t.cycle = :cycle')
                ->andWhere('t.disabled = :disabled')
                ->orderBy('t.executionOrder', 'ASC')
                //->setParameter('cycle', $cycle->getId());
                ->setParameters(['cycle'=> $cycle->getId(), 'disabled' => 0]);
            $paginator = $pagePaginator->paginate($qb, $page, $maxSize); //$this->show_tests_size);
            $totalPosts = $paginator->count(); // Count of ALL posts (ie: `20` posts)
            $iterator = $paginator->getIterator(); # ArrayIterator

            $maxPages = ceil($totalPosts / $maxSize); //$this->show_tests_size);
            $thisPage = $page;
            $disable_uptime = false;
            $deleteForm = $this->createDeleteForm($cycle);
            $nul_found = 0;

            $additional_cols = array();
            $additional_opt_cols = array();
            $iterator->rewind();
            if ($totalPosts > 0) {
                for ($x = 0; $x < $totalPosts; $x++) {
                    /** @var LogBookTest $test */
                    $test = $iterator->current();
                    if ($test !== null) {
                        /**
                         * Search for metadata with _SHOW postfix, if exist that column will be shown
                         * @var array $md
                         */
                        $md = $test->getMetaData();
                        if (\count($md) > 0) {
                            foreach ($md as $key => $value) {
                                if ($forJson) {
                                    $tmp_key = $key;
                                    if (!\in_array($tmp_key, $additional_cols, true)) {
                                        $additional_cols[] = $tmp_key;
                                    }
                                } else {
                                    if ($this->endsWith($key, '_SHOW') && !\in_array($key, $additional_cols, true)) {
                                        $additional_cols[] = $key;
                                    } else if ($this->endsWith($key, '_SHOW_OPT') && !\in_array($key, $additional_opt_cols, true)) {
                                        $additional_opt_cols[] = $key;
                                    }
                                }
                            }
                        }
                        /** Search for uptime if show or not */
                        if ($test->getDutUpTimeStart() === 0 && $test->getDutUpTimeEnd() === 0) {
                            $nul_found++;
                        }
                    }

                    $iterator->next();
                }
            }

            if ($nul_found === $totalPosts) {
                $disable_uptime = true;
            }
            if ($forJson) {
                $iterator = null;
            }
            $ret_arr = array(
                'cycle'                 => $cycle,
                'size'                  => $totalPosts,
                'maxPages'              => $maxPages,
                'thisPage'              => $thisPage,
                'iterator'              => $iterator,
                'disabled_uptime'       => $disable_uptime,
                'delete_form'           => $deleteForm->createView(),
                'additional_cols'       => $additional_cols,
                'additional_opt_cols'   => $additional_opt_cols,
                'tests_in_json'         => $forJson,
            );

            return $this->render('lbook/cycle/show.full.html.twig', $ret_arr);

        } catch (\Throwable $ex) {
            return $this->cycleNotFound($ex, $cycle);
        }
    }

    /**
     * Finds and displays a cycle entity with paginator.
     *
     * @Route("/ajax/{id}", name="cycle_show_ajax", methods={"GET", "POST"})
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @param LogBookCycle $cycle
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAjax(PagePaginator $pagePaginator, LogBookTestRepository $testRepo, LogBookCycle $cycle = null): ?Response
    {
        try {
            if (!$cycle) {
                throw new \RuntimeException('');
            }

            $qb = $testRepo->createQueryBuilder('t')
                ->where('t.cycle = :cycle')
                ->andWhere('t.disabled = :disabled')
                ->orderBy('t.executionOrder', 'ASC')
                //->setParameter('cycle', $cycle->getId());
                ->setParameters(['cycle'=> $cycle->getId(), 'disabled' => 0]);
            $paginator = $pagePaginator->paginate($qb, 1, $this->show_tests_size);
            $totalPosts = $paginator->count(); // Count of ALL posts (ie: `20` posts)
            $iterator = $paginator->getIterator(); # ArrayIterator

            $maxPages = ceil($totalPosts / $this->show_tests_size);
            $thisPage = 1;
            $disable_uptime = false;
            $deleteForm = $this->createDeleteForm($cycle);
            $nul_found = 0;

            $additional_cols = array();
            $additional_opt_cols = array();
            $iterator->rewind();
            if ($totalPosts > 0) {
                for ($x = 0; $x < $totalPosts; $x++) {
                    /** @var LogBookTest $test */
                    $test = $iterator->current();
                    if ($test !== null) {
                        /**
                         * Search for metadata with _SHOW postfix, if exist that column will be shown
                         * @var array $md
                         */
                        $md = $test->getMetaData();
                        if (\count($md) > 0) {
                            foreach ($md as $key => $value) {
                                if ($this->endsWith($key, '_SHOW') && !\in_array($key, $additional_cols, true)) {
                                    $additional_cols[] = $key;
                                } else if ($this->endsWith($key, '_SHOW_OPT') && !\in_array($key, $additional_opt_cols, true)) {
                                    $additional_opt_cols[] = $key;
                                }

                            }
                        }
                        /** Search for uptime if show or not */
                        if ($test->getDutUpTimeStart() === 0 && $test->getDutUpTimeEnd() === 0) {
                            $nul_found++;
                        }
                    }

                    $iterator->next();
                }
            }

            if ($nul_found === $totalPosts) {
                $disable_uptime = true;
            }

            $ret_arr = array(
                'cycle'                 => $cycle,
                'size'                  => $totalPosts,
                'maxPages'              => $maxPages,
                'thisPage'              => $thisPage,
                'iterator'              => $iterator,
                'disabled_uptime'       => $disable_uptime,
                'delete_form'           => $deleteForm->createView(),
                'additional_cols'       => $additional_cols,
                'additional_opt_cols'   => $additional_opt_cols,
            );

            return $this->render('lbook/cycle/show.ajax.html.twig', $ret_arr);

        } catch (\Throwable $ex) {
            return $this->cycleNotFound($ex, $cycle);
        }
    }

    /**
     * @param $haystack
     * @param $needle
     * @return bool
     */
    private function endsWith($haystack, $needle): bool
    {
        $length = mb_strlen($needle);

        return $length === 0 || (substr($haystack, -$length) === $needle);
    }

    /**
     * @param \Throwable $ex
     * @param LogBookCycle|null $cycle
     * @return Response
     */
    protected function cycleNotFound(\Throwable $ex, LogBookCycle $cycle = null): ?Response
    {
        /** @var Request $request */
        $request= $this->get('request_stack')->getCurrentRequest();
        $possibleId = 0;
        $response = $otherResponse = null;
        $short_msg = 'Unknown error';
        try {
            $possibleId = $request->attributes->get('id');
            $response = new Response('', Response::HTTP_NOT_FOUND);
            if ( $ex->getCode() > 0 && Response::$statusTexts[$ex->getCode()] !== '') {
                $otherResponse = new Response('', $ex->getCode());
                $short_msg = Response::$statusTexts[$ex->getCode()];
            } else {
                $otherResponse = new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $ex) {
        }

        if ($cycle === null) {
            return $this->render('lbook/404.html.twig', array(
                'short_message' => sprintf('Cycle with provided ID:[%s] not found', $possibleId),
                'message' =>  $ex->getMessage(),
                'ex' => $ex,
            ), $response);
        }

        return $this->render('lbook/500.html.twig', array(
            'short_message' => $short_msg,
            'message' => $ex->getMessage(),
            'ex' => $ex,
        ), $otherResponse);
    }

    /**
     * Displays a form to edit an existing cycle entity.
     *
     * @Route("/{id}/edit", name="cycle_edit", methods={"GET|POST"})
     * @param Request $request
     * @param LogBookCycle $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\Form\Exception\LogicException
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @throws \LogicException
     */
    public function editAction(Request $request, LogBookCycle $obj = null)
    {
        try {
            if (!$obj) {
                throw new \RuntimeException('');
            }
            $this->denyAccessUnlessGranted('edit', $obj->getSetup());
            $deleteForm = $this->createDeleteForm($obj);
            $editForm = $this->createForm(LogBookCycleType::class, $obj);
            $editForm->handleRequest($request);

            if ($editForm->isSubmitted() && $editForm->isValid()) {
                $this->getDoctrine()->getManager()->flush();
                return $this->redirectToRoute('cycle_edit', array('id' => $obj->getId()));
            }

            return $this->render('lbook/cycle/edit.html.twig', array(
                'cycle' => $obj,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
            ));
        } catch (\Throwable $ex) {
            return $this->cycleNotFound($ex, $obj);
        }
    }

    /**
     * Deletes a setup entity.
     *
     * @Route("/{id}", name="cycle_delete", methods={"DELETE"})
     * @param Request $request
     * @param LogBookCycle $obj
     * @return RedirectResponse|Response
     * @throws \Symfony\Component\Form\Exception\LogicException
     * @throws \LogicException
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function deleteAction(Request $request, LogBookCycle $obj = null)
    {
        try {
            if (!$obj) {
                throw new \RuntimeException('');
            }
            $this->denyAccessUnlessGranted('delete', $obj->getSetup());
            $form = $this->createDeleteForm($obj);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                /** @var LogBookCycleRepository $cycleRepo */
                $cycleRepo = $em->getRepository('App:LogBookCycle');
                $cycleRepo->delete($obj);
            }

            return $this->redirectToRoute('cycle_index_first');
        } catch (\Throwable $ex) {
            return $this->cycleNotFound($ex, $obj);
        }
    }

    /**
     * Creates a form to delete a setup entity.
     *
     * @param LogBookCycle $obj The cycle entity
     *
     * @return \Symfony\Component\Form\FormInterface | Response
     */
    private function createDeleteForm(LogBookCycle $obj)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('cycle_delete', array('id' => $obj->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
