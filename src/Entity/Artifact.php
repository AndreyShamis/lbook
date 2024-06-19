<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
// BADAKTI

/**
 * @ORM\Entity(repositoryClass="App\Repository\ArtifactRepository")
 */
class Artifact
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
    private $name;
    /**
     * @ORM\Column(type="string", length=10)
     */
    private $fileExt;
    private $displayer;
    private $srcFullpath;
    private $dstFullpath;

    public function __construct($data) {
        $this->name = $data['name'];
        $this->fileExt = $data['file_ext'];
        $this->displayer = $data['displayer'];
        $this->srcFullpath = $data['src_fullpath'];
        $this->dstFullpath = $data['dst_fullpath'];
    }
}
