<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookSetup;
use App\Entity\LogBookTest;
use App\Entity\LogBookUser;
use App\Entity\SuiteExecution;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookSetupRepository;
use App\Repository\LogBookTestRepository;
use App\Repository\SuiteExecutionRepository;
use App\Service\PagePaginator;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Form\LogBookSetupType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Doctrine\Common\Collections\Collection;

/**
 * Setup controller.
 *
 * @Route("setup")
 */
class LogBookSetupController extends AbstractController
{
    protected $index_size = 250;
    protected $show_cycle_size = 500;


    /**
     * Finds and displays a setup entity.
     *
     * @Route("indicator/{id}", name="setup_indicator", methods={"GET"})
     * @Route("indicator/{id}/mc/{mainCycle}/s/{size}", name="setup_indicator_with_main_cycle", methods={"GET"})
     * @Route("indicator/{id}/mc/{mainCycle}/cc/{compareCycle}/s/{size}", name="setup_indicator_with_main_cycle_and_compare", methods={"GET"})
     * @Route("indicator/{id}/s/{size}", name="setup_indicator_size_default", methods={"GET"})
     * @param LogBookSetup $setup
     * @param int $size
     * @param LogBookCycle|null $mainCycle
     * @param LogBookCycle|null $compareCycle
     * @param LogBookCycleRepository $cycleRepo
     * @param LogBookTestRepository|null $testRepo
     * @param SuiteExecutionRepository $suiteRepo
     * @return Response
     */
    public function indicator(LogBookSetup $setup = null, int $size= 10,
                              LogBookCycle $mainCycle = null,
                              LogBookCycle $compareCycle = null,
                              LogBookCycleRepository $cycleRepo = null,
                              LogBookTestRepository $testRepo = null,
                              SuiteExecutionRepository $suiteRepo = null): ?Response
    {
        try {
            if ($size > 100) {
                $size = 5;
            }
            $productVersions = [];
            $suiteNames = [];
            $testsNotPass = [];
            $testNames = [];
            $testNamesRemoved = [];
            $compareMode = false;
            if ($setup === null || $cycleRepo === null ) {
                throw new \RuntimeException('');
            }

            if ($compareCycle !== null && $compareCycle->getId() > 0 && $mainCycle !== null && $mainCycle->getId() >0 && $mainCycle->getId() !== $compareCycle->getId()) {
                $compareMode = true;
                $cycles = [];
            } else {
                $qb = $cycleRepo->createQueryBuilder('t')
                    ->where('t.setup = :setup')
                    ->orderBy('t.id', 'DESC')
                    ->setMaxResults($size)
                    ->setParameter('setup', $setup->getId());
                $cycles = $qb->getQuery()->execute();
            }

            if ($mainCycle !== null && $mainCycle->getId() > 0){
                $qb_cycle = $cycleRepo->createQueryBuilder('t')
                    ->where('t.id = :mainCycle')
                    ->setParameter('mainCycle', $mainCycle->getId())
                    ->setMaxResults(1);
                $tmp_cycles = $qb_cycle->getQuery()->execute();
                foreach ($tmp_cycles as $tmpcycle){
                    // Add only what not exist in cycles
                    if(!in_array($tmpcycle, $cycles)) {
                        array_push($cycles, $tmpcycle);

                    }
                }
            }
            if ($compareCycle !== null && $compareCycle->getId() > 0){
                $qb_cycle2 = $cycleRepo->createQueryBuilder('t')
                    ->where('t.id = :compareCycle')
                    ->setParameter('compareCycle', $compareCycle->getId())
                    ->setMaxResults(1);
                $tmp_cycles = $qb_cycle2->getQuery()->execute();
                foreach ($tmp_cycles as $tmpcycle){
                    array_push($cycles, $tmpcycle);
                }
            }
            $qb_s = $suiteRepo->createQueryBuilder('s')
                ->where('s.cycle IN (:cycles)')
                ->orderBy('s.id', 'DESC')
                ->setParameter('cycles', $cycles);
            $suites = $qb_s->getQuery()->execute();

            $qb_t = $testRepo->createQueryBuilder('tests')
                ->where('tests.suite_execution IN (:suites)')
                ->orderBy('tests.timeEnd', 'ASC')
                ->setParameter('suites', $suites);
            $tests = $qb_t->getQuery()->execute();

            $work_arr = [];
            /** @var LogBookCycle $cycle */
            foreach ($cycles as $cycle) {
                $cycle_build = $cycle->getBuild()->getName();
                $cycleSuites = $cycle->getSuiteExecution();
                /** @var SuiteExecution $tmpSuite */
                foreach ($cycleSuites as $tmpSuite) {
                    //$tmpSuite->getPlatform() . '_'. $tmpSuite->getChip() . '_' .
                    $firstKey = $tmpSuite->getName();
                    if ($compareMode) {
                        $secondKey = $tmpSuite->getCycle()->getName();
                    } else {
                        $secondKey = $tmpSuite->getProductVersion();
                    }
                    $work_arr[$firstKey][$secondKey][] = $tmpSuite;
                    if (!in_array($secondKey, $productVersions)){
                        $productVersions[] = $secondKey;
                    }
                    if (!in_array($firstKey, $suiteNames)){
                        $suiteNames[] = $firstKey;
                    }
//                    $tmpTests = $tmpSuite->getTests();
//                    /** @var LogBookTest $test */
//                    foreach ($tmpTests as $test) {
//                        $work_arr[$test->getName()][$test->getSuiteExecution()->getProductVersion()][] = $test;
//                    }
                }
            }

            $em = $this->getDoctrine()->getManager();

            /** @var LogBookTest $test */
            foreach ($tests as $test) {
                $firstKey = $test->getName();

                if (!in_array($firstKey, $testNames)){
                    $testNames[] = $firstKey;
                }
                if ($compareMode) {
                    $secondKey = $test->getCycle()->getName();
                } else {
                    $secondKey = $test->getSuiteExecution()->getProductVersion();
                }
                $work_arr[$firstKey][$secondKey][] = $test;
                if ($mainCycle !== null) {
                    if ($test->getCycle()->getId() === $mainCycle->getId() && $test->getVerdict()->getName() !== 'PASS'){
                        $testsNotPass[] = $test;
                    }
                } else {
                    if ($test->getVerdict()->getName() !== 'PASS'){
                        $testsNotPass[] = $test;
                    }
                }

                if ($test->getVerdict()->getName() !== 'PASS' && $test->getVerdict()->getName() !== 'UNKNOWN') {
                    if ($test->getFailDescription() == ' '){
                        $test->parseFailDescription();
                        $em->persist($test);
                    }
                }
            }
            foreach ($cycles as $cycle) {
                $cycle->setCalculateStatistic(false);
            }
            $em->flush();
            $removed_tests_counter = 0;
            foreach ($testNames as $testName) {
                $issue_found = false;
                foreach ($productVersions as $pv) {
                    try {
                        $tests_in_cell = $work_arr[$testName][$pv];
                        /** @var LogBookTest $tTest */

                        foreach ($tests_in_cell as $tTest) {
                            if ($tTest->getVerdict()->getName() !== 'PASS') {
                                $issue_found = true;
                                break;
                            }
                        }

                    } catch (\Throwable $ex) {}

                }
                if ($issue_found) {
                    $testNamesRemoved[] = $testName;
                } else {
                    $removed_tests_counter++;
                    unset($work_arr[$testName][$pv]);
                }
            }
            $mainCycleSuiteName = [];
            $mainCycleChips = [];
            $secondCycleChips = [];
            $secondCycleSuiteName = [];
            $a_only = [];
            $mainCycleTestsDefinedInsuite = 0;
            $mainCycleTestsEnabledInsuite = 0;
            $mainCycleTestsExecuted = 0;
            $mainCycleTestsDisabledInSuite = 0;
            $secondCycleTestsDefinedInsuite = 0;
            $secondCycleTestsEnabledInsuite = 0;
            $secondCycleTestsDisabledInSuite = 0;
            $secondCycleTestsExecuted = 0;
            $missing_in_a_exist_in_b = [];
            if ($compareMode) {
                foreach ($mainCycle->getSuiteExecution() as $tmp){
                    $mainCycleSuiteName[] = $tmp->getName(); // . '_|_' . $tmp->getUuid();
                    $mainCycleTestsDefinedInsuite += $tmp->getTestsCount();
                    $mainCycleTestsEnabledInsuite += $tmp->getTestsCountEnabled();
                    $mainCycleTestsExecuted += $tmp->getTotalExecutedTests();
                    $mainCycleTestsDisabledInSuite += $tmp->getTestsCountDisabled();
                    if (!array_key_exists($tmp->getChip(),$mainCycleChips)) {
                        $mainCycleChips[$tmp->getChip()] = 1;
                    } else {
                        $mainCycleChips[$tmp->getChip()]++;
                    }
                }
                foreach ($compareCycle->getSuiteExecution() as $tmp){
                    $secondCycleSuiteName[] = $tmp->getName(); // . '_|_' . $tmp->getUuid();
                    $secondCycleTestsDefinedInsuite += $tmp->getTestsCount();
                    $secondCycleTestsEnabledInsuite += $tmp->getTestsCountEnabled();
                    $secondCycleTestsExecuted += $tmp->getTotalExecutedTests();
                    $secondCycleTestsDisabledInSuite += $tmp->getTestsCountDisabled();
                    if (!array_key_exists($tmp->getChip(),$secondCycleChips)) {
                        $secondCycleChips[$tmp->getChip()] = 1;
                    } else {
                        $secondCycleChips[$tmp->getChip()]++;
                    }
                }
                $intersect = array_intersect($mainCycleSuiteName, $secondCycleSuiteName);
                $a_only = array_diff($mainCycleSuiteName, $secondCycleSuiteName);
                $missing_in_a_exist_in_b = array_diff($secondCycleSuiteName, $intersect);
            }

            return $this->render('lbook/setup/indicator.html.twig', array(
                'setup' => $setup,
                'iterator' => $suites,
                'suites' => $suites,
                'cycles' => $cycles,
                'size' => $size,
                'productVersions' => $productVersions,
                'suiteNames' => $suiteNames,
                'testNames' => $testNames,
                'work_arr' => $work_arr,
                'removed_tests_counter' => $removed_tests_counter,
                'testNamesRemoved' => $testNamesRemoved,
                'testsNotPass' => $testsNotPass,
                'mainCycle' => $mainCycle,
                'compareCycle' => $compareCycle,
                'show_user' => 0,
                'compareMode' => $compareMode,
                'newSuitesInMain' => $a_only,
                'missingSuitesInMain' => $missing_in_a_exist_in_b,
                'mainCycleTestsDefinedInsuite' => $mainCycleTestsDefinedInsuite,
                'secondCycleTestsDefinedInsuite' => $secondCycleTestsDefinedInsuite,
                'mainCycleTestsEnabledInsuite' => $mainCycleTestsEnabledInsuite,
                'secondCycleTestsEnabledInsuite' => $secondCycleTestsEnabledInsuite,
                'mainCycleTestsExecuted' => $mainCycleTestsExecuted,
                'secondCycleTestsExecuted' => $secondCycleTestsExecuted,
                'mainCycleTestsDisabledInSuite' => $mainCycleTestsDisabledInSuite,
                'secondCycleTestsDisabledInSuite' => $secondCycleTestsDisabledInSuite,
                'mainCycleChips' => $mainCycleChips,
                'secondCycleChips' => $secondCycleChips,
                //'missingSuitesInMain' => array_intersect($mainCycle->getSuiteExecution()->getValues(), $compareCycle->getSuiteExecution()->getValues()),
            ));
        } catch (\Throwable $ex) {
            return $this->setupNotFound($ex, $setup);
        }
    }

    /**
     * Lists all setup entities.
     *
     * @Route("/json/page/{page}", name="setups", methods={"GET"})
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookSetupRepository $setupRepo
     * @return JsonResponse
     */
    public function indexJson(PagePaginator $pagePaginator, LogBookSetupRepository $setupRepo, int $page = 1): JsonResponse
    {
        $query = $setupRepo->createQueryBuilder('setups')
           // ->select(array('setups.id', 'setups.disabled', 'setups.updatedAt'))
              // ->addSelect(array('setups.updatedAt as updatedAtDiff'))
            ->where('setups.disabled = 0')
            ->orderBy('setups.updatedAt', 'DESC')
            ->addOrderBy('setups.id', 'DESC');

        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();

        $dateTimeToStr = function ($dateTime) {
            return $dateTime instanceof \DateTime ? $dateTime->format(\DateTime::ATOM) : ''; //'d/m/Y H:i:s'
        };

        $owner_callback = function ($owner) {
            return $owner instanceof LogBookUser ? $owner->getUsername() : '';
        };
        $counter_callback = function ($obj) {
            return $obj instanceof Collection ? \count($obj) : 0;
        };
        $normalizer->setCallbacks([
            'cycles' => $counter_callback,
            'owner' => $owner_callback,
            'moderators' => $counter_callback,
            'createdAt' => $dateTimeToStr,
            'updatedAt' => $dateTimeToStr
        ]);
        $serializer = new Serializer(array($normalizer), array($encoder));

        $paginator = $pagePaginator->paginate($query, $page, $this->index_size);
        $paginator->setUseOutputWalkers(false);
        $res = $paginator->getQuery()->execute();
        $json = $serializer->serialize($res, 'json');

        $response = $this->json([]);
        $response->setJson($json);
        $response->setEncodingOptions(JSON_PRETTY_PRINT);
        return $response;
    }

    /**
     * Lists all setup entities.
     *
     * @Route("/page/{page}", name="setup_index", methods={"GET"})
     * @Template(template="lbook/setup/index.html.twig")
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookSetupRepository $setupRepo
     * @return array
     */
    public function index(PagePaginator $pagePaginator, LogBookSetupRepository $setupRepo, int $page = 1): array
    {
        $query = $setupRepo->createQueryBuilder('setups')
            ->where('setups.disabled = 0')
            ->orderBy('setups.updatedAt', 'DESC')
            ->addOrderBy('setups.id', 'DESC');
        $paginator = $pagePaginator->paginate($query, $page, $this->index_size);
        $totalPosts = $paginator->count();
        $iterator = $paginator->getIterator();

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

    /**
     * Lists all setup entities.
     *
     * @Route("/", name="setup_index_first", methods={"GET"})
     * @Template(template="lbook/setup/index.html.twig")
     * @param PagePaginator $pagePaginator
     * @param LogBookSetupRepository $setupRepo
     * @return array
     */
    public function indexFirst(PagePaginator $pagePaginator, LogBookSetupRepository $setupRepo): array
    {
        return $this->index($pagePaginator, $setupRepo);
    }

    /**
     * Creates a new setup entity.
     *
     * @Route("/new", name="setup_new", methods={"GET|POST"})
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws LogicException|\LogicException|InvalidOptionsException
     */
    public function new(Request $request)
    {
        $obj = new LogBookSetup();
        $form = $this->get('form.factory')->create(LogBookSetupType::class, $obj, array(
            'user' => $this->get('security.token_storage')->getToken()->getUser(),
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($obj);
            $em->flush();

            return $this->redirectToRoute('setup_show', array('id' => $obj->getId()));
        }

        return $this->render('lbook/setup/new.html.twig', array(
            'test' => $obj,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a setup entity.
     *
     * @Route("/{id}", name="setup_show_first", methods={"GET"})
     * @param LogBookSetup $setup
     * @param PagePaginator $pagePaginator
     * @param LogBookCycleRepository $cycleRepo
     * @return Response
     */
    public function showFullFirst(LogBookSetup $setup = null, PagePaginator $pagePaginator = null, LogBookCycleRepository $cycleRepo = null): ?Response
    {
        return $this->showFull($setup, 1, $pagePaginator, $cycleRepo);
    }

    /**
     * Finds and displays a setup entity.
     *
     * @Route("/{id}/page/{page}", name="setup_show", methods={"GET"})
     * @param LogBookSetup $setup
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookCycleRepository $cycleRepo
     * @return Response
     */
    public function showFull(LogBookSetup $setup = null, $page = 1, PagePaginator $pagePaginator = null, LogBookCycleRepository $cycleRepo = null): ?Response
    {
        try {
            if ($setup === null || $cycleRepo === null || $pagePaginator === null) {
                throw new \RuntimeException('');
            }
            $qb = $cycleRepo->createQueryBuilder('t')
                ->where('t.setup = :setup')
                ->orderBy('t.timeEnd', 'DESC')
                ->addOrderBy('t.updatedAt', 'DESC')
                ->setParameter('setup', $setup->getId());
            $paginator = $pagePaginator->paginate($qb, $page, $this->show_cycle_size);
            $totalPosts = $paginator->count();
            $iterator = $paginator->getIterator();

            $maxPages = ceil($totalPosts / $this->show_cycle_size);
            $thisPage = $page;
            $deleteForm = $this->createDeleteForm($setup);
            $show_build = $this->showBuild($paginator);
            $show_user = $this->showUsers($paginator);
            return $this->render('lbook/setup/show.full.html.twig', array(
                'setup'          => $setup,
                'size'          => $totalPosts,
                'maxPages'      => $maxPages,
                'thisPage'      => $thisPage,
                'iterator'      => $iterator,
                'paginator'     => $paginator,
                'delete_form'   => $deleteForm->createView(),
                'show_build'    => $show_build,
                'show_user'     => $show_user,
            ));
        } catch (\Throwable $ex) {
            return $this->setupNotFound($ex, $setup);
        }
    }

    /**
     * @param Paginator $paginator
     * @return bool
     */
    protected function showUsers($paginator): bool
    {
        $show_user = false;
        $totalPosts = $paginator->count();
        $iterator = $paginator->getIterator();
        $prev_user_id = 0;
        $iterator->rewind();
        try {
            if ($totalPosts > 0) {
                for ($x = 0; $x < $totalPosts; $x++) {
                    /** @var LogBookCycle $cycle */
                    $cycle = $iterator->current();
                    if ($cycle !== null) {
                        $user = $cycle->getUser();
                        if ($user !== null) {
                            $user_id = $user->getId();
                            if ($user_id > 0) {
                                if ($prev_user_id !== $user_id) {
                                    $show_user = true;
                                    break;
                                }
                            }
                        }
                    }
                    $iterator->next();
                }
            }
        } catch (\Throwable $ex) { }
        return $show_user;
    }

    /**
     * @param Paginator $paginator
     * @return bool
     */
    protected function showBuild($paginator): bool
    {
        $show_build = false;
        $totalPosts = $paginator->count();
        $iterator = $paginator->getIterator();
        $prev_build_id = 0;
        $iterator->rewind();
        try {
            if ($totalPosts > 0) {
                for ($x = 0; $x < $totalPosts; $x++) {
                    /** @var LogBookCycle $cycle */
                    $cycle = $iterator->current();
                    if ($cycle !== null) {
                        $build = $cycle->getBuild();
                        if ($build !== null) {
                            $build_id = $build->getId();
                            if ($prev_build_id === 0) {
                                $prev_build_id = $build_id;
                            }
                            if ($prev_build_id !== $build_id) {
                                $show_build = true;
                                break;
                            }
                        }
                    }
                    $iterator->next();
                }
            }
        } catch (\Throwable $ex) { }
        return $show_build;
    }

    /**
     * @param \Throwable $ex
     * @param LogBookSetup|null $setup
     * @return Response
     */
    protected function setupNotFound(\Throwable $ex, LogBookSetup $setup = null): ?Response
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
        if ($setup === null) {
            return $this->render('lbook/404.html.twig', array(
                'short_message' => sprintf('Setup with provided ID:[%s] not found', $possibleId),
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
     * Displays a form to edit an existing setup entity.
     *
     * @Route("/{id}/edit", name="setup_edit", methods={"GET|POST"})
     * @param Request $request
     * @param LogBookSetup $obj
     * @return RedirectResponse|Response
     * @throws InvalidOptionsException
     * @throws \LogicException|AccessDeniedException
     */
    public function edit(Request $request, LogBookSetup $obj = null)
    {
        try {
            if (!$obj) {
                throw new \RuntimeException('');
            }
            $user = $this->get('security.token_storage')->getToken()->getUser();
            // check for "edit" access: calls all voters
            $this->denyAccessUnlessGranted('edit', $obj);
            /** @var PersistentCollection $moderators */
            //$moderators = $obj->getModerators();
            $deleteForm = $this->createDeleteForm($obj);
            //if (in_array($user, $moderators)) {
    //        if ($moderators->contains($user)) {
    //            $deleteForm = $this->createDeleteForm($obj)->createView();
    //        } else {
    //            $deleteForm = null;
    //        }

            $editForm = $this->get('form.factory')->create(LogBookSetupType::class, $obj, array(
                'user' => $user,
            ));
            $editForm->handleRequest($request);

            if ($editForm->isSubmitted() && $editForm->isValid()) {
                $this->getDoctrine()->getManager()->flush();
                return $this->redirectToRoute('setup_edit', array('id' => $obj->getId()));
            }

            return $this->render('lbook/setup/edit.html.twig', array(
                'setup' => $obj,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
            ));

        } catch (\Throwable $ex) {
            return $this->setupNotFound($ex, $obj);
        }
    }

    /**
     * Deletes a setup entity.
     *
     * @Route("/{id}", name="setup_delete", methods={"DELETE"})
     * @param Request $request
     * @param LogBookSetup $obj
     * @return RedirectResponse|Response
     * @throws AccessDeniedException|\LogicException
     */
    public function delete(Request $request, LogBookSetup $obj = null)
    {
        try {
            if (!$obj) {
                throw new \RuntimeException('');
            }

            /** Dont check access for test env */
            $env = getenv('APP_ENV');
            if ($env !== 'test') {
                $this->denyAccessUnlessGranted('delete', $obj);
            }
            $form = $this->createDeleteForm($obj);
            $form->handleRequest($request);

            if ($env === 'test' || ($form->isSubmitted() && $form->isValid())) {
                $em = $this->getDoctrine()->getManager();
                /** @var LogBookSetupRepository $setupRepo */
                $setupRepo = $em->getRepository('App:LogBookSetup');
                $setupRepo->delete($obj);
            }

            return $this->redirectToRoute('setup_index');
        } catch (\Throwable $ex) {
            return $this->setupNotFound($ex, $obj);
        }
    }

    /**
     * Creates a form to delete a setup entity.
     *
     * @param LogBookSetup $obj The test entity
     *
     * @return FormInterface | Response
     */
    private function createDeleteForm(LogBookSetup $obj)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('setup_delete', array('id' => $obj->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }
}
