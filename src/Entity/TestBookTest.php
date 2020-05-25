<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TestBookTestRepository")
 */
class TestBookTest
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
    private $name;

    /**
     * @ORM\Column(type="json")
     */
    private $controlData = [];

    /**
     * @ORM\Column(type="text", length=32000)
     */
    private $description = '';

    /**
     * @ORM\Column(type="text", length=32000)
     */
    private $labels = '';

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $author = '';

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     */
    private $priority = 1;

    /**
     * @ORM\Column(type="string", length=38)
     */
    private $uuid = '';

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\TestBookComponent", inversedBy="testBookTests")
     */
    private $components;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $testPath = '';

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TestBookSuiteTest", mappedBy="test")
     */
    private $testBookSuiteTests;


    public function __construct()
    {
        $this->components = new ArrayCollection();
        $this->setCreatedAt(new \DateTime());
        $this->setUpdatedAt(new \DateTime());
        $this->testBookSuiteTests = new ArrayCollection();
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

    public function getControlData(): ?array
    {
        return $this->controlData;
    }

    public function setControlData(array $controlData): self
    {
        $this->controlData = $controlData;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getLabels(): string
    {
        return $this->labels;
    }

    public function setLabels(string $labels): self
    {
        $this->labels = $labels;

        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return Collection|TestBookComponent[]
     */
    public function getComponents(): Collection
    {
        return $this->components;
    }

    public function addComponent(TestBookComponent $component): self
    {
        if (!$this->components->contains($component)) {
            $this->components[] = $component;
        }

        return $this;
    }

    public function removeComponent(TestBookComponent $component): self
    {
        if ($this->components->contains($component)) {
            $this->components->removeElement($component);
        }

        return $this;
    }

    public function getTestPath(): string
    {
        return $this->testPath;
    }

    public function setTestPath(string $testPath): self
    {
        $this->testPath = $testPath;

        return $this;
    }

    /**
     * @return Collection|TestBookSuiteTest[]
     */
    public function getTestBookSuiteTests(): Collection
    {
        return $this->testBookSuiteTests;
    }

    public function addTestBookSuiteTest(TestBookSuiteTest $testBookSuiteTest): self
    {
        if (!$this->testBookSuiteTests->contains($testBookSuiteTest)) {
            $this->testBookSuiteTests[] = $testBookSuiteTest;
            $testBookSuiteTest->setTest($this);
        }

        return $this;
    }

    public function removeTestBookSuiteTest(TestBookSuiteTest $testBookSuiteTest): self
    {
        if ($this->testBookSuiteTests->contains($testBookSuiteTest)) {
            $this->testBookSuiteTests->removeElement($testBookSuiteTest);
            // set the owning side to null (unless already changed)
            if ($testBookSuiteTest->getTest() === $this) {
                $testBookSuiteTest->setTest(null);
            }
        }

        return $this;
    }
}
