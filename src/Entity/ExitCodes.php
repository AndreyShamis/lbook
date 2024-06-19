<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
// BADAKTI

/**
 * @ORM\Entity(repositoryClass="App\Repository\ExitCodesRepository")
 */
class ExitCodes
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
    private $systemCode;

    
    /**
     * @ORM\Column(type="integer")
     */
    private $returnCode;

    /**
     * @ORM\Column(type="integer")
     */
    private $artifactCode;

    public function __construct(array $data)
    {
        $this->systemCode = $data['system_code'];
        $this->returnCode = $data['return_code'];
        $this->artifactCode = $data['artifact_code'];

    }
}
