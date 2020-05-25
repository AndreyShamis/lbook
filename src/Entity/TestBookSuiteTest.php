<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TestBookSuiteTestRepository")
 */
class TestBookSuiteTest
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
    private $controlPath = '';

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDisabled = false;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $setupConfigPath = '';

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $disableReason = '';

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $iterations = 1;

    /**
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $timeout = 0;

    /**
     * @ORM\Column(type="float")
     */
    private $weight = 1;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TestBookTest", inversedBy="testBookSuiteTests")
     */
    private $test;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $type = 'TEST';

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TestBookTestList", mappedBy="tests")
     */
    private $testBookTestLists;

    public function __construct()
    {
        $this->testBookTestLists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getControlPath(): string
    {
        return $this->controlPath;
    }

    public function setControlPath(string $controlPath): self
    {
        $this->controlPath = $controlPath;

        return $this;
    }

    public function getIsDisabled(): bool
    {
        return $this->isDisabled;
    }

    public function setIsDisabled(bool $isDisabled): self
    {
        $this->isDisabled = $isDisabled;

        return $this;
    }

    public function getSetupConfigPath(): string
    {
        return $this->setupConfigPath;
    }

    public function setSetupConfigPath(string $setupConfigPath): self
    {
        $this->setupConfigPath = $setupConfigPath;

        return $this;
    }

    public function getDisableReason(): string
    {
        return $this->disableReason;
    }

    public function setDisableReason(string $disableReason): self
    {
        $this->disableReason = $disableReason;

        return $this;
    }

    public function getIterations(): int
    {
        return $this->iterations;
    }

    public function setIterations(int $iterations): self
    {
        $this->iterations = $iterations;

        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $testTimeout): self
    {
        $this->timeout = $testTimeout;

        return $this;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getTest(): ?TestBookTest
    {
        return $this->test;
    }

    public function setTest(?TestBookTest $test): self
    {
        $this->test = $test;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection|TestBookTestList[]
     */
    public function getTestBookTestLists(): Collection
    {
        return $this->testBookTestLists;
    }

    public function addTestBookTestList(TestBookTestList $testBookTestList): self
    {
        if (!$this->testBookTestLists->contains($testBookTestList)) {
            $this->testBookTestLists[] = $testBookTestList;
            $testBookTestList->setTests($this);
        }

        return $this;
    }

    public function removeTestBookTestList(TestBookTestList $testBookTestList): self
    {
        if ($this->testBookTestLists->contains($testBookTestList)) {
            $this->testBookTestLists->removeElement($testBookTestList);
            // set the owning side to null (unless already changed)
            if ($testBookTestList->getTests() === $this) {
                $testBookTestList->setTests(null);
            }
        }

        return $this;
    }
}
