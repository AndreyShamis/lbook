<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TestBookComponentRepository")
 */
class TestBookComponent
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\TestBookTest", mappedBy="components")
     */
    private $testBookTests;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\TestBookSuite", mappedBy="components")
     */
    private $testBookSuites;

    public function __construct()
    {
        $this->testBookTests = new ArrayCollection();
        $this->testBookSuites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|TestBookTest[]
     */
    public function getTestBookTests(): Collection
    {
        return $this->testBookTests;
    }

    public function addTestBookTest(TestBookTest $testBookTest): self
    {
        if (!$this->testBookTests->contains($testBookTest)) {
            $this->testBookTests[] = $testBookTest;
            $testBookTest->addComponent($this);
        }

        return $this;
    }

    public function removeTestBookTest(TestBookTest $testBookTest): self
    {
        if ($this->testBookTests->contains($testBookTest)) {
            $this->testBookTests->removeElement($testBookTest);
            $testBookTest->removeComponent($this);
        }

        return $this;
    }

    /**
     * @return Collection|TestBookSuite[]
     */
    public function getTestBookSuites(): Collection
    {
        return $this->testBookSuites;
    }

    public function addTestBookSuite(TestBookSuite $testBookSuite): self
    {
        if (!$this->testBookSuites->contains($testBookSuite)) {
            $this->testBookSuites[] = $testBookSuite;
            $testBookSuite->addComponent($this);
        }

        return $this;
    }

    public function removeTestBookSuite(TestBookSuite $testBookSuite): self
    {
        if ($this->testBookSuites->contains($testBookSuite)) {
            $this->testBookSuites->removeElement($testBookSuite);
            $testBookSuite->removeComponent($this);
        }

        return $this;
    }
}
