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

    /**
     * @Route("/", name="upload_index")
     * @Method("GET")
     */
    public function index()
    {
        return $this->render('lbook/upload/index.html.twig', array());
    }

    /**
     * Creates a new Upload entity.
     *
     * @Route("/new", name="upload_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $obj = new LogBookUpload();
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
            $obj->addMessage("New file name is :" . $fileName);
            $obj->addMessage("File ext :"  .$file->guessExtension());
            $copy_info = $file->move("../uploads/", $fileName);

            $obj->addMessage("File copy info :"  . $copy_info);
            $obj->setLogFile($fileName);
            $obj->file_info = $file;
            $obj->data = $this->parseFile($copy_info, 2);

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
    protected function parseFile($file, $testId=1)
    {
        $em = $this->getDoctrine()->getManager();
        $msgTypeRepo = $em->getRepository('App:LogBookMessageType');
        $logsRepo = $em->getRepository('App:LogBookMessage');
        $testsRepo = $em->getRepository('App:LogBookTest');
        $MIN_STR_LEN = 10;
        $SHORT_TIME_LEN = 8;
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
                $value = "";
            }
        }
        //exit;
        $counter=0;
        foreach ($temp_arr as $key => $value){
            if(strlen($value) < $MIN_STR_LEN){
                continue;
            }
            preg_match_all('/(\d{2,}.*\d{1,1})\s*([A-Z]+)\s*\|\s*(.*)/s', $value,$oneLine);

            if (count($oneLine[2]) > 0){
                $dLevel = null;

                //Removing : base_job:0395 utils:0262 ssh_host:0116
                preg_match('/([\w|\_]*\:\d+\s*)\|\s*(.*)/s', $oneLine[3][0], $messageWithDebug);
                if(count($messageWithDebug) == 3){
                    $oneLine[3][0] = $messageWithDebug[2];
                }

                $tmp_time = $this->clean_string($oneLine[1][0]);
                if(strlen($tmp_time) > $SHORT_TIME_LEN){
                    $timeFormat = 'm/d H:i:s';
                }
                else{
                    $timeFormat = 'H:i:s';
                }
                $date = \DateTime::createFromFormat($timeFormat, $tmp_time);

                //Get debug level message, convert to upper case
                $dLevel['name'] = strtoupper($this->clean_string($oneLine[2][0]));
                if($dLevel['name'] == 'WARNI'){
                    $dLevel['name'] = "WARNING";
                }
                $msgTypeResult = $msgTypeRepo->findOneOrCreate($dLevel);

                $ret_data[$counter]['logTime'] = $date;
                $ret_data[$counter]['msgType'] = $msgTypeResult;
                $ret_data[$counter]['message'] = trim($oneLine[3][0]);
                $ret_data[$counter]['chain'] = $counter;
                $ret_data[$counter]['test'] = $testsRepo->findOneOrCreate(array("id" => $testId));
                $logsRepo->Create($ret_data[$counter], false);
                $counter++;
            }
            else{
                echo count($oneLine) .  " $value:<pre>";
                print_r($oneLine);
                echo "</pre><br/>";
            }
        }
        $em->flush();
        return $ret_data;
    }

    /**
     * Clean string from bash characters
     * @param $string
     * @return string
     */
    protected function clean_string(string $string) : string {
        $s = trim($string);
        //$s = iconv("UTF-8", "UTF-8//IGNORE", $s); // drop all non utf-8 characters
        // this is some bad utf-8 byte sequence that makes mysql complain - control and formatting i think
        $s = preg_replace('/(?>[\x00-\x1F]|\xC2[\x80-\x9F]|\xE2[\x80-\x8F]{2}|\xE2\x80[\xA4-\xA8]|\xE2\x81[\x9F-\xAF])/', ' ', $s);
        //$s = preg_replace('/\s+/', ' ', $s); // reduce all multiple whitespace to a single space
        return $s;
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
    private function generateUniqueFileName() : string
    {
        // md5() reduces the similarity of the file names generated by uniqid(), which is based on timestamps
        return md5(uniqid());
    }
}
