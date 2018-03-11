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

    protected $message="";
    /**
     *
     * @Assert\NotBlank(message="Please, upload the log file as a DEBUG or INFO format file.")
     * //@Assert\File(mimeTypes={ "text/plain" })
     */
    private $logFile;

    protected $setup;

    protected $cycle;

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
    /**
     * @param string $message
     */
    public function addMessage(string $message)
    {
        $this->message = $this->message . "\n" . $message;
    }
    /**
     * @param string $message
     */
    public function setMessage(string $message)
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
