<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
// BADAKTI

/**
 * @ORM\Entity(repositoryClass="App\Repository\DetailedNodeRepository")
 */
class DetailedNode
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Node", cascade={"persist", "remove"})
     */
    private $node;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\SessionInfo", cascade={"persist", "remove"})
     */
    private $sessionInfo;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Artifact", mappedBy="node")
     */
    private $artifacts;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Resolvable", cascade={"persist", "remove"})
     */
    private $resolvables;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\ExitCodes", cascade={"persist", "remove"})
     */
    private $exitCodes;

    // other properties...

    public function __construct()
    {
        $this->artifacts = new ArrayCollection();
    }

}

