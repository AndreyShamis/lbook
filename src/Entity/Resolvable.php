<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
// BADAKTI


/**
 * @ORM\Entity(repositoryClass="App\Repository\ResolvableRepository")
 */
class Resolvable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $launchInfo;

    private $conn;
    private $executor;
    private $executorParams;

    public function __construct($data) {
        $this->launchInfo = $data['launch_info'];
        $this->conn = $data['conn'];
        $this->executor = $data['executor'];
        $this->executorParams = $data['executor params'];
    }
}
