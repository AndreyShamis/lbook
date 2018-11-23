<?php

namespace App\Controller;

use App\Entity\LogBookBuild;
use App\Form\LogBookBuildType;
use App\Repository\LogBookBuildRepository;
use App\Repository\LogBookCycleRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\PagePaginator;

/**
 * @Route("/build")
 */
class LogBookBuildController extends Controller
{
    protected $index_size = 500;
    protected $show_cycle_size = 1000;

    /**
     * @Route("/page/{page}", name="log_book_build_index", methods="GET")
     * @Template(template="lbook/build/index.html.twig")
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookBuildRepository $logBookBuildRepository
     * @return array
     */
    public function index(LogBookCycleRepository $cycleRepo, PagePaginator $pagePaginator, LogBookBuildRepository $logBookBuildRepository, $page = 1): array
    {
        $query = $logBookBuildRepository->createQueryBuilder('log_book_build')
            ->orderBy('log_book_build.id', 'DESC');
        $paginator = $pagePaginator->paginate($query, $page, $this->index_size);
        //$posts = $this->getAllPosts($page); // Returns 5 posts out of 20
        // You can also call the count methods (check PHPDoc for `paginate()`)
        //$totalPostsReturned = $paginator->getIterator()->count(); # Total fetched (ie: `5` posts)
        $totalPosts = $paginator->count(); # Count of ALL posts (ie: `20` posts)
        $iterator = $paginator->getIterator(); # ArrayIterator

        $iterator->rewind();
        try {
            if ($totalPosts > 0) {
                for ($x = 0; $x < $totalPosts; $x++) {
                    /** @var LogBookBuild $build */
                    $build = $iterator->current();
                    if ($build !== null) {
                        $build_id = $build->getId();
                        $build->setCycles($cycleRepo->count(array('build' => $build_id)));
                    }
                    $iterator->next();
                }
            }
        } catch (\Throwable $ex) { }



        $maxPages = ceil($totalPosts / $this->index_size);
        $thisPage = $page;
        return array(
            'size'      => $totalPosts,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'  => $iterator,
            'paginator' => $paginator,
        );
        // return $this->render('lbook/build/index.html.twig', ['log_book_builds' => $logBookBuildRepository->findAll()]);
    }

    /**
     * @Route("/new", name="log_book_build_new", methods="GET|POST")
     * @param Request $request
     * @return Response
     * @throws \LogicException
     */
    public function new(Request $request): Response
    {
        $logBookBuild = new LogBookBuild();
        $form = $this->createForm(LogBookBuildType::class, $logBookBuild);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($logBookBuild);
            $em->flush();

            return $this->redirectToRoute('log_book_build_index');
        }

        return $this->render('lbook/build/new.html.twig', [
            'log_book_build' => $logBookBuild,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/detail/{id}", name="log_book_build_show", methods="GET")
     * @param LogBookBuild $logBookBuild
     * @return Response
     */
    public function show(LogBookBuild $logBookBuild): Response
    {
        $this->denyAccessUnlessGranted('view', $logBookBuild);
        return $this->render('lbook/build/show.html.twig', ['log_book_build' => $logBookBuild]);
    }

    /**
     * @Route("/{id}", name="build_show_cycles_first", methods="GET")
     * @param LogBookBuild $logBookBuild
     * @param PagePaginator $pagePaginator
     * @param LogBookCycleRepository $cycleRepo
     * @return Response
     */
    public function showCyclesFirst(LogBookBuild $logBookBuild, PagePaginator $pagePaginator, LogBookCycleRepository $cycleRepo): Response
    {
        return $this->showCycles($logBookBuild , $pagePaginator, $cycleRepo, 1);
    }

    /**
     * @Route("/{id}/page/{page}", name="build_show_cycles", methods="GET")
     * @param LogBookBuild $logBookBuild
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookCycleRepository $cycleRepo
     * @return Response
     */
    public function showCycles(LogBookBuild $logBookBuild, PagePaginator $pagePaginator, LogBookCycleRepository $cycleRepo, $page = 1): Response
    {
        $qb = $cycleRepo->createQueryBuilder('t')
            ->where('t.build = :build')
            ->orderBy('t.updatedAt', 'DESC')
            ->setParameter('build', $logBookBuild->getId());
        $paginator = $pagePaginator->paginate($qb, $page, $this->show_cycle_size);
        $totalPosts = $paginator->count(); // Count of ALL posts (ie: `20` posts)
        $iterator = $paginator->getIterator(); # ArrayIterator

        $maxPages = ceil($totalPosts / $this->show_cycle_size);
        $thisPage = $page;
        return $this->render('lbook/build/show.cycle.html.twig', array(
            'build'         => $logBookBuild,
            'size'          => $totalPosts,
            'maxPages'      => $maxPages,
            'thisPage'      => $thisPage,
            'iterator'      => $iterator,
            'paginator'     => $paginator,
        ));
    }

    /**
     * @Route("/{id}/edit", name="log_book_build_edit", methods="GET|POST")
     * @param Request $request
     * @param LogBookBuild $logBookBuild
     * @return Response
     * @throws \LogicException
     */
    public function edit(Request $request, LogBookBuild $logBookBuild): Response
    {
        $this->denyAccessUnlessGranted('edit', $logBookBuild);

        $form = $this->createForm(LogBookBuildType::class, $logBookBuild);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('log_book_build_edit', ['id' => $logBookBuild->getId()]);
        }

        return $this->render('lbook/build/edit.html.twig', [
            'log_book_build' => $logBookBuild,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="log_book_build_delete", methods="DELETE")
     * @param Request $request
     * @param LogBookBuild $logBookBuild
     * @return Response
     * @throws \LogicException
     */
    public function delete(Request $request, LogBookBuild $logBookBuild): Response
    {
        $this->denyAccessUnlessGranted('delete', $logBookBuild);
        if ($this->isCsrfTokenValid('delete'.$logBookBuild->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($logBookBuild);
            $em->flush();
        }

        return $this->redirectToRoute('log_book_build_index');
    }
}
