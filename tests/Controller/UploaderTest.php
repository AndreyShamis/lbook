<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 27/03/18
 * Time: 20:18
 */

namespace App\Tests\Controller;

use App\Utils\RandomString;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class UploaderTest extends LogBookApplicationTestCase
{
    /**
     *
     * @param null|string $name
     * @param array $data
     * @param string $dataName
     * @throws \Exception
     */
    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->setUp();
    }

    public function testUploadCli():void
    {
        $token = RandomString::generateRandomString(20);
        $file1PathSrc = realpath(__DIR__) . '/ForUpload/PASS__network_WiFi_Perf.11g';
        $file2PathSrc = realpath(__DIR__) . '/ForUpload/ERROR__network_WiFi_BluetoothStreamPerf.11a';
        $filePath1 = realpath(__DIR__) . '/PASS__network_WiFi_Perf.11g';
        $filePath2 = realpath(__DIR__) . '/ERROR__network_WiFi_BluetoothStreamPerf.11a';
        copy($file1PathSrc, $filePath1);
        copy($file2PathSrc, $filePath2);

        $setupName = 'Test Setup';

        $file1 = new UploadedFile(
            $filePath1,
            'PASS__network_WiFi_Perf.11g',
            'text/plain',
            filesize($filePath1)
        );
        $file2 = new UploadedFile(
            $filePath2,
            'ERROR__network_WiFi_BluetoothStreamPerf.11a',
            'text/plain',
            filesize($filePath2)
        );
        $client = $this->getClient();
//        request(
//            $method,
//            $uri,
//            array $parameters = array(),
//            array $files = array(),
//            array $server = array(),
//            $content = null,
//            $changeHistory = true
//        )
        $client->request('POST', '/upload/new_cli',
            array(
                'token' => $token,
                'setup' => $setupName,
                //'cycle' => 'asdasdasdasd',
            ),
            array('file' => $file1),
            array(
                //'CONTENT_TYPE'          => 'multipart/form-data;',
                'HTTP_REFERER'          => '/upload/new_cli',
            )
        );
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        //echo  $this->getClient()->getResponse()->getContent();

        $client->request('POST', '/upload/new_cli',
            array(
                'token' => $token,
                'setup' => $setupName,
                //'cycle' => 'asdasdasdasd',
            ),
            array('file' => $file2),
            array(
                //'CONTENT_TYPE'          => 'multipart/form-data;',
                'HTTP_REFERER'          => '/upload/new_cli',
            )
        );

        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        //echo  $this->getClient()->getResponse()->getContent();

    }
}