<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookTest;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookTestRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Form\LogBookCycleType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Service\PagePaginator;
use Symfony\Component\Filesystem\Filesystem;


/**
 * Cycle controller.
 *
 * @Route("cycle")
 */
class LogBookCycleController extends Controller
{
    protected $show_tests_size = 2000;
    protected $index_size = 100;

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
    public function index($page = 1, PagePaginator $pagePaginator, LogBookCycleRepository $cycleRepo): array
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
                    $newFileName = $test->getExecutionOrder() . '__' . $test->getName() . '.txt';
                    $zip->addFromString(basename($newFileName),  file_get_contents($log_path));
                }
            }

            $zip->close();
            $response = new Response(file_get_contents($zipName));
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
            $response->headers->set('Content-length', filesize($zipName));

            return $response;
        } catch (\Throwable $ex) {
            return $this->cycleNotFound($cycle, $ex);
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
        return $this->index(1, $pagePaginator, $cycleRepo);
    }

    /**
     * Creates a new cycle entity.
     *
     * @Route("/new", name="cycle_new", methods={"GET|POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \LogicException
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
     * @Route("/{id}", name="cycle_show_first", methods={"GET"})
     * @param LogBookCycle $cycle
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showFirstPage(LogBookCycle $cycle = null, PagePaginator $pagePaginator, LogBookTestRepository $testRepo): ?Response
    {
        return $this->show($cycle, 1, $pagePaginator, $testRepo);
    }

    /**
     * Finds and displays a cycle entity with paginator.
     *
     * @Route("/{id}/page/{page}", name="cycle_show", methods={"GET"})
     * @param LogBookCycle $cycle
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(LogBookCycle $cycle = null, $page = 1, PagePaginator $pagePaginator, LogBookTestRepository $testRepo): ?Response
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
            $paginator = $pagePaginator->paginate($qb, $page, $this->show_tests_size);
            $totalPosts = $paginator->count(); // Count of ALL posts (ie: `20` posts)
            $iterator = $paginator->getIterator(); # ArrayIterator

            $maxPages = ceil($totalPosts / $this->show_tests_size);
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

            return $this->render('lbook/cycle/show.full.html.twig', array(
                'cycle'                 => $cycle,
                'size'                  => $totalPosts,
                'maxPages'              => $maxPages,
                'thisPage'              => $thisPage,
                'iterator'              => $iterator,
                'paginator'             => $paginator,
                'disabled_uptime'       => $disable_uptime,
                'delete_form'           => $deleteForm->createView(),
                'additional_cols'       => $additional_cols,
                'additional_opt_cols'   => $additional_opt_cols,
            ));
        } catch (\Throwable $ex) {
            return $this->cycleNotFound($cycle, $ex);
        }
    }

    private function endsWith($haystack, $needle): bool
    {
        $length = mb_strlen($needle);

        return $length === 0 || (substr($haystack, -$length) === $needle);
    }

    /**
     * Finds and displays a cycle entity.
     *
     * @Route("/{id}", name="cycle_show_default", methods={"GET"})
     * @param LogBookCycle $obj
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(LogBookCycle $obj): Response
    {
        return $this->render('lbook/cycle/show.html.twig', array(
            'cycle' => $obj,
        ));
    }

    /**
     * @param LogBookCycle|null $cycle
     * @param \Throwable $ex
     * @return Response
     */
    protected function cycleNotFound(LogBookCycle $cycle = null, \Throwable $ex): ?Response
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
            return $this->cycleNotFound($obj, $ex);
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

                $cycleRepo = $em->getRepository('App:LogBookCycle');
                $cycleRepo->delete($obj);
            }

            return $this->redirectToRoute('cycle_index_first');
        } catch (\Throwable $ex) {
            return $this->cycleNotFound($obj, $ex);
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
