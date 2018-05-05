<?php

namespace App\Controller;

use App\Entity\LogBookBuild;
use App\Form\LogBookBuildType;
use App\Repository\LogBookBuildRepository;
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
    protected $index_size = 100;

    /**
     * @Route("/page/{page}", name="log_book_build_index", methods="GET")
     * @Template(template="lbook/build/index.html.twig")
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookBuildRepository $logBookBuildRepository
     * @return array
     */
    public function index($page = 1, PagePaginator $pagePaginator, LogBookBuildRepository $logBookBuildRepository): array
    {
        $query = $logBookBuildRepository->createQueryBuilder('log_book_build')
            ->orderBy('log_book_build.id', 'DESC');
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
     * @Route("/{id}", name="log_book_build_show", methods="GET")
     * @param LogBookBuild $logBookBuild
     * @return Response
     */
    public function show(LogBookBuild $logBookBuild): Response
    {
        return $this->render('lbook/build/show.html.twig', ['log_book_build' => $logBookBuild]);
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
        if ($this->isCsrfTokenValid('delete'.$logBookBuild->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($logBookBuild);
            $em->flush();
        }

        return $this->redirectToRoute('log_book_build_index');
    }
}
