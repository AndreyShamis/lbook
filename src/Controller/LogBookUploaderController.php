<?php

namespace App\Controller;

use App\Entity\LogBookUpload;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Uploader controller.
 *
 * @Route("upload")
 */
class LogBookUploaderController extends Controller
{
    protected static $messageTypes = array();

    /**
     * @Route("/", name="upload_index")
     * @Method("GET")
     */
    public function index()
    {
        //$em = $this->getDoctrine()->getManager();

        //$verdicts = $em->getRepository('App:LogBookUpload')->findAll();

        return $this->render('lbook/upload/index.html.twig', array(
            //'verdicts' => $verdicts,
        ));
        // replace this line with your own code!
        //return $this->render('@Maker/demoPage.html.twig', [ 'path' => str_replace($this->getParameter('kernel.project_dir').'/', '', __FILE__) ]);
    }

    protected function clean_string($string) {
        $s = trim($string);
        //$s = iconv("UTF-8", "UTF-8//IGNORE", $s); // drop all non utf-8 characters

        // this is some bad utf-8 byte sequence that makes mysql complain - control and formatting i think
        $s = preg_replace('/(?>[\x00-\x1F]|\xC2[\x80-\x9F]|\xE2[\x80-\x8F]{2}|\xE2\x80[\xA4-\xA8]|\xE2\x81[\x9F-\xAF])/', ' ', $s);

        //$s = preg_replace('/\s+/', ' ', $s); // reduce all multiple whitespace to a single space

        return $s;
    }
    /**
     * Creates a new verdict entity.
     *
     * @Route("/new", name="upload_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $obj = new LogBookUpload();
        //$form = $this->createForm(LogBookUpload::class, $obj);
        $form = $this->createForm('App\Form\LogBookUploadType', $obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // $file stores the uploaded PDF file
            /** @var UploadedFile $file */
            $file = $obj->getLogFile();

            $fileName = $this->generateUniqueFileName(). '_' . $file->getClientOriginalName(). '.'.$file->guessExtension();
            // moves the file to the directory where brochures are stored
//            $file->move(
//                $this->getParameter('brochures_directory'),
//                $fileName
//            );
            $obj->addMessage("New file name is " . $fileName);
            $obj->addMessage("File ext "  .$file->guessExtension());
            $copy_info = $file->move("../uploads/", $fileName);

            $obj->addMessage("File copy info "  . $copy_info);
            $obj->setLogFile($fileName);
            $obj->file_info = $file;
            $obj->data = $this->parseFile($copy_info);
            //$file->move("uploads/", $fileName);

            // updates the 'brochure' property to store the PDF file name
            // instead of its contents

//
//            $em = $this->getDoctrine()->getManager();
//            $em->persist($obj);
//            $em->flush();


            return $this->showAction($obj);
            //return $this->redirectToRoute('upload_show', array('id' => $obj->getId()));
        }

        return $this->render('lbook/verdict/new.html.twig', array(
            'verdict' => $obj,
            'form' => $form->createView(),
        ));
    }

    /**
     * @param String $file
     * @return array
     */
    public function parseFile($file)
    {
        $em = $this->getDoctrine()->getManager();
        $msgTypeRepo = $em->getRepository('App:LogBookMessageType');
        $MIN_STR_LEN = 10;
        $ret_data = array();

        $file_data = file_get_contents($file , FILE_USE_INCLUDE_PATH);
        $temp_arr = preg_split("/\\r\\n|\\r|\\n/", $file_data);

        $last_good_key = -1;
        foreach ($temp_arr as $key => &$value){

            if(strlen($value) < $MIN_STR_LEN){
                continue;
            }
            preg_match_all('/(\d{2,}.*\d{1,1})\s*([A-Z]+)\s*\|\s*(.*)/', $value,$oneLine);
            if (count($oneLine[2]) > 0){
                $last_good_key = $key;
                $value = $this->clean_string($value);
            }
            else{
                $temp_arr[$last_good_key] = $temp_arr[$last_good_key] . "\n" . $this->clean_string($value);
//                echo count($oneLine[0]) .  " :<pre>";
//                print_r($oneLine);
//                echo "</pre><br/>";
                //echo "Update by value $value to index  $last_good_key , new value is " .  $temp_arr[$last_good_key] ."  <br/>";
                $value = "";
            }
        }

        $counter=0;
        foreach ($temp_arr as $key => $value){
            if(strlen($value) < $MIN_STR_LEN){
                continue;
            }
            preg_match_all('/(\d{2,}.*\d{1,1})\s*([A-Z]+)\s*\|\s*(.*)/s', $value,$oneLine);

            if (count($oneLine[2]) > 0){
                $dLevel = null;
                $ret_data[$counter]['time'] = $this->clean_string($oneLine[1][0]);

                //Get debug level message, convert to upper case
                $dLevel['name'] = strtoupper($this->clean_string($oneLine[2][0]));

                if(isset(self::$messageTypes[$dLevel['name']])){
                    $msgTypeResult = self::$messageTypes[$dLevel['name']];
                }
                else{
                    $msgTypeResult = $msgTypeRepo->findOneOrCreate($dLevel);
                    self::$messageTypes[$dLevel['name']] = $msgTypeResult;
                }
                $ret_data[$counter]['debugLevel'] = $msgTypeResult;
                $ret_data[$counter]['msg'] = trim($oneLine[3][0]);
                $ret_data[$counter]['chain'] = $counter;
                $counter++;
            }
            else{
                echo count($oneLine) .  " $value:<pre>";
                print_r($oneLine);
                echo "</pre><br/>";
            }
        }
        return $ret_data;
    }

//    /**
//     * Show upload file info.
//     *
//     * @Route("/{id}", name="upload_show")
//     * @Method("GET")
//     * @param LogBookUpload $obj
//     * @return \Symfony\Component\HttpFoundation\Response
//     */
    public function showAction(LogBookUpload $obj)
    {
        return $this->render('lbook/upload/show.html.twig', array(
            'upload' => $obj,
        ));
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }
}
