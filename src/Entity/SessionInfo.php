<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
// BADAKTI


/**
 * @ORM\Entity(repositoryClass="App\Repository\SessionInfoRepository")
 */
class SessionInfo
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
    private $seqNum;

    private $starTime;
    private $endTime;
    
    public function __construct($data) {
        $this->seqNum = $data['seq_num'];
        $this->starTime = $data['start_time'];
        $this->endTime = $data['end_time'];
    }
}
