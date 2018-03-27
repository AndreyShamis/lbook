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
        $filePath = realpath(__DIR__) . '/ForUpload/PASS__network_WiFi_Perf.11g';
        $file = new UploadedFile(
            $filePath,
            'PASS__network_WiFi_Perf.11g',
            'text/plain',
            filesize($filePath)
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
        $client->request(
            'POST',
            '/upload/new_cli',
            array(
                'token' => $token,
                'setup' => 'Test Setup',
                //'cycle' => 'asdasdasdasd',

            ),
            array('file' => $file),
            array(
                'CONTENT_TYPE'          => 'multipart/form-data;',
                'HTTP_REFERER'          => '/upload/new_cli',
                //'HTTP_X-Requested-With' => 'XMLHttpRequest',
            )
        );
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        echo  $this->getClient()->getResponse()->getContent();
    }
}