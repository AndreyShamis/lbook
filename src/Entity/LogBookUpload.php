<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogBookUploadRepository")
 */
class LogBookUpload
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    protected $message='';

    private $logFile;

    protected $setup;

    protected $cycle;

    protected $debug;

    public function __construct()
    {
        $this->debug = false;
    }

    /**
     * @return mixed
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * @param mixed $debug
     */
    public function setDebug($debug): void
    {
        $this->debug = $debug;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        if ($this->getDebug()) {
            return $this->message;
        }
        return '';
    }
    /**
     * @param string $message
     */
    public function addMessage(string $message): void
    {
        $this->message = $this->message . "\n" . $message;
    }
    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getLogFile()
    {
        return $this->logFile;
    }

    public function setLogFile($logFile)
    {
        $this->logFile = $logFile;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCycle()
    {
        return $this->cycle;
    }

    /**
     * @param mixed $cycle
     */
    public function setCycle($cycle): void
    {
        $this->cycle = $cycle;
    }

    /**
     * @return mixed
     */
    public function getSetup()
    {
        return $this->setup;
    }

    /**
     * @param mixed $setup
     */
    public function setSetup($setup): void
    {
        $this->setup = $setup;
    }
}
