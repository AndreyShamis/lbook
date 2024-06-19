<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
// BADAKTI


/**
 * @ORM\Entity(repositoryClass="App\Repository\PathGeneratorRepository")
 */
class PathGenerator
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
    private $classPath;

}

