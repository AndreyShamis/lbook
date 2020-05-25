<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TestBookTestListRepository")
 */
class TestBookTestList
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $path;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDummy = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPreSuite = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPostSuite = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPrivate = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookUser")
     */
    private $owner;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\TestBookSuite", mappedBy="testLists")
     */
    private $testBookSuites;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TestBookSuiteTest", inversedBy="testBookTestLists")
     */
    private $tests;

    public function __construct()
    {
        $this->tests = new ArrayCollection();
        $this->setUpdatedAt(new \DateTime());
        $this->setCreatedAt(new \DateTime());
        $this->testBookSuites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getIsDummy(): bool
    {
        return $this->isDummy;
    }

    public function setIsDummy(bool $isDummy): self
    {
        $this->isDummy = $isDummy;

        return $this;
    }

    /**
     * @return Collection|TestBookTest[]
     */
    public function getTests(): Collection
    {
        return $this->tests;
    }

    public function addTest(TestBookTest $test): self
    {
        if (!$this->tests->contains($test)) {
            $this->tests[] = $test;
        }

        return $this;
    }

    public function removeTest(TestBookTest $test): self
    {
        if ($this->tests->contains($test)) {
            $this->tests->removeElement($test);
        }

        return $this;
    }

    public function getIsPreSuite(): bool
    {
        return $this->isPreSuite;
    }

    public function setIsPreSuite(bool $isPreSuite): self
    {
        $this->isPreSuite = $isPreSuite;

        return $this;
    }

    public function getIsPostSuite(): bool
    {
        return $this->isPostSuite;
    }

    public function setIsPostSuite(bool $isPostSuite): self
    {
        $this->isPostSuite = $isPostSuite;

        return $this;
    }

    public function getIsPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): self
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }

    public function getOwner(): LogBookUser
    {
        return $this->owner;
    }

    public function setOwner(?LogBookUser $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

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
            $testBookSuite->addTestList($this);
        }

        return $this;
    }

    public function removeTestBookSuite(TestBookSuite $testBookSuite): self
    {
        if ($this->testBookSuites->contains($testBookSuite)) {
            $this->testBookSuites->removeElement($testBookSuite);
            $testBookSuite->removeTestList($this);
        }

        return $this;
    }

    public function setTests(?TestBookSuiteTest $tests): self
    {
        $this->tests = $tests;

        return $this;
    }
}
