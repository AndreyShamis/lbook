<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogBookCycleRepository")
 * @ORM\Table(name="lbook_cycles")
 */
class LogBookCycle
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    protected $id;


    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name = "";

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LogBookTest", mappedBy="cycle", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="tests", fieldName="id", referencedColumnName="id")
     * @ORM\OrderBy({"executionOrder" = "ASC"})
     */
    protected $tests;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookSetup", cascade={"persist"}, inversedBy="cycles")
     * @ORM\JoinColumn(name="setup", fieldName="id", referencedColumnName="id")
     * @ORM\OrderBy({"id" = "DESC"})
     */
    protected $setup;


    /**
     * @return mixed
     */
    public function getTests()
    {
        return $this->tests;
    }

    /**
     * @param mixed $tests
     */
    public function setTests($tests): void
    {
        $this->tests = $tests;
    }
    /**
     * @return mixed
     */


    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
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
    public function setSetup( $setup): void
    {
        $this->setup = $setup;
    }


}
