<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
// BADAKTI


/**
 * @ORM\Entity(repositoryClass="App\Repository\RetryRepository")
 */
class Retry
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $maxAttempts;

    public $classPath;
    public $waitTime;

    public function __construct($data) {
        $this->classPath = $data['classpath'];
        $this->maxAttempts = $data['max_attempts'];
        $this->waitTime = $data['wait_time'];
        
    }
}
