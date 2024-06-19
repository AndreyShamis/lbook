<?php

namespace App\Controller;

use App\Entity\LogBookTestInfo;
use App\Form\LogBookTestInfoType;
use App\Repository\LogBookTestInfoRepository;
use App\Service\PagePaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/testinfo")
 */
class LogBookTestInfoController extends AbstractController
{
    /**
     * @Route("/", name="log_book_test_info_index", methods={"GET"})
     * @Route("/page/{page}", name="log_book_test_info_index_page", methods={"GET"})
     * @param PagePaginator $pagePaginator
     * @param LogBookTestInfoRepository $logBookTestInfoRepository
     * @param int $page
     * @return Response
     */
    public function index(PagePaginator $pagePaginator, LogBookTestInfoRepository $logBookTestInfoRepository, int $page = 1): Response
    {
        $size = 100000;
        $paginator = $pagePaginator->paginate(
            $logBookTestInfoRepository->createQueryBuilder('tt')
            // ->where('tt.path is NOT NULL')
            ->orderBy('tt.lastMarkedAsSeenAt', 'DESC')
            ->orderBy('tt.path', 'ASC')
            , $page, $size);
        $totalPosts = $paginator->count();
        return $this->render('log_book_test_info/index.html.twig', [
            'log_book_test_infos' => $paginator,
            'thisPage' => $page,
            'maxPages' => ceil($totalPosts / $size),
        ]);
    }

    /**
     * @Route("/update", name="log_book_test_info_update", methods={"GET"})
     * @param LogBookTestInfoRepository $logBookTestInfoRepository
     * @return Response
     */
    public function update(LogBookTestInfoRepository $logBookTestInfoRepository): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $da = $logBookTestInfoRepository->findAll();
        foreach ($da as $d) {
//            if ($d->getTestCount() > 2000) {
            $d->setTestCount($d->getLogBookTests()->count());
//            }

        }
        $entityManager->flush();
        return $this->redirectToRoute('log_book_test_info_index');
    }

    /**
     * @Route("/{id}", name="log_book_test_info_show", methods={"GET"})
     * @param LogBookTestInfo $logBookTestInfo
     * @return Response
     * @throws \Throwable
     */
    public function show(LogBookTestInfo $logBookTestInfo): Response
    {
        // $textFilename = 'text_file.txt';
        // $fileContent = $this->readFileContent($textFilename);
        $fileContent = "2024/06/04 13:40:06 INFO | Badakti URL https://badakti.mobileye.com/workflow/graph/QxVvyxtIF25/nodes/rwzdAVlbsKk/Execution\n2024/06/04 13:52:06 INFO | Badakti URL (Alpha) https://badakti-sandbox.mobileye.com/node/rwzdAVlbsKk/graph/QxVvyxtIF25\n";
        $urls = $this->parseURLs($fileContent);
        $filenames = [sys_get_temp_dir() . '/content1.html', sys_get_temp_dir() . '/content2.html'];
        print_r('URLS FOUND ' . count($urls));
        $htmlContents = [];
        foreach ($urls as $index => $url) {
            // Получаем начальный HTML-контент страницы
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $initialHtml = curl_exec($ch);
            curl_close($ch);
    
            // Находим AJAX-запрос в HTML-коде
            preg_match('/\/\/.*\/intervention\.js/', $initialHtml, $matches);
            if (!empty($matches)) {
                $ajaxUrl = substr($matches[0], 2); // Удаляем начальные "//"
    
                // Выполняем AJAX-запрос и получаем обновленный HTML-контент
                $ch = curl_init($ajaxUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $ajaxContent = curl_exec($ch);
                curl_close($ch);
    
                $htmlContents[] = $initialHtml . $ajaxContent;
                printf("HERE<br/>");
            } else {
                $htmlContents[] = $initialHtml;
                printf("HERE2 <br/>");
            }
        }
    

        $stderrContents = array_map([$this, 'getStderrContent'], $htmlContents);

        foreach ($stderrContents as $index => $stderrContent) {
            if ($stderrContent !== null) {
                echo "Содержимое stderr для файла {$filenames[$index]}:\n";
                echo $stderrContent . "\n\n";
            }
        }
    
        print(count($stderrContents));
        print_r($urls);
        print_r($htmlContents);
        $uniqueKeys = $this->getUniqueKeys($logBookTestInfo->getLogBookTests());
        return $this->render('log_book_test_info/show.html.twig', [
            'log_book_test_info' => $logBookTestInfo,
            'uniqueKeys' => array_keys($uniqueKeys),
        ]);
        // $htmlContents = $this->downloadContent($urls, $filenames);
        
        // $stderrContents = array_map([$this, 'getStderrContent'], $htmlContents);

        // foreach ($stderrContents as $index => $stderrContent) {
        //     if ($stderrContent !== null) {
        //         print("Содержимое stderr для файла {$filenames[$index]}:\n");
        //         echo $stderrContent . "\n\n";
        //     }
        // }

        // $uniqueKeys = $this->getUniqueKeys($logBookTestInfo->getLogBookTests());
        // return $this->render('log_book_test_info/show.html.twig', [
        //     'log_book_test_info' => $logBookTestInfo,
        //     'uniqueKeys' => array_keys($uniqueKeys),
        // ]);
    }

    private function getUniqueKeys($tests): array
    {
        $uniqueKeys = [];

        foreach ($tests as $test) {
            $metaData = $test->getNewMetaData();
            if ($metaData) {
                foreach ($metaData->getValue() as $key => $value) {
                    $uniqueKeys[$key] = true;
                }
            }
        }

        return $uniqueKeys;
    }

    // Function to read file content
    function readFileContent($filename) {
        return file_get_contents($filename);
    }

    function parseUrls($text) {
        // Регулярное выражение для поиска URL
        $urlPattern = '/https:\/\/badakti(?:-sandbox)?\.[^\s]+/';
    
        // Найти все URL в тексте
        preg_match_all($urlPattern, $text, $matches);
    
        // Вернуть массив найденных URL
        return $matches[0];
    }
    function downloadContent($urls, $filenames) {
        $contents = [];
    
        for ($i = 0; $i < count($urls); $i++) {
            $url = $urls[$i];
            $filename = $filenames[$i];
    
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $content = curl_exec($ch);
            curl_close($ch);
    
            file_put_contents($filename, $content);
            $contents[] = $content;
        }
    
        return $contents;
    }
    
    function getStderrContent($htmlContent) {
        $pattern = '/<div\s+(?:class="w3-container w3-left-align w3-small w3-margin-top w3-margin-bottom w3-monospace file-content"|style="min-height: 42vh; max-height: 42vh; overflow: initial; width: auto; max-width: initial; position: relative;")\s*>(.*?)<\/div>/s';
        preg_match($pattern, $htmlContent, $matches);
    
        if (isset($matches[1])) {
            $stderrContent = trim(strip_tags($matches[1]));
            return json_encode(['stderr' => $stderrContent]);
        }
    
        return null;
    }
    

//    /**
//     * @Route("/{id}/edit", name="log_book_test_info_edit", methods={"GET","POST"})
//     */
//    public function edit(Request $request, LogBookTestInfo $logBookTestInfo): Response
//    {
//        $form = $this->createForm(LogBookTestInfoType::class, $logBookTestInfo);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $this->getDoctrine()->getManager()->flush();
//
//            return $this->redirectToRoute('log_book_test_info_index');
//        }
//
//        return $this->render('log_book_test_info/edit.html.twig', [
//            'log_book_test_info' => $logBookTestInfo,
//            'form' => $form->createView(),
//        ]);
//    }
//
//    /**
//     * @Route("/{id}", name="log_book_test_info_delete", methods={"DELETE"})
//     */
//    public function delete(Request $request, LogBookTestInfo $logBookTestInfo): Response
//    {
//        if ($this->isCsrfTokenValid('delete'.$logBookTestInfo->getId(), $request->request->get('_token'))) {
//            $entityManager = $this->getDoctrine()->getManager();
//            $entityManager->remove($logBookTestInfo);
//            $entityManager->flush();
//        }
//
//        return $this->redirectToRoute('log_book_test_info_index');
//    }
}
