<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TestBookSuiteRepository")
 */
class TestBookSuite
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
     * @ORM\Column(type="string", length=38)
     */
    private $uuid = '';

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\TestBookComponent", inversedBy="testBookSuites")
     */
    private $components;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $setupConfigPath = '';

    /**
     * @ORM\Column(type="boolean")
     */
    private $stopOnFail = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $stopOnError = false;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $packageMode = 'regular_mode';

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $project = '';

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $interface = '';

    /**
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     */
    private $testingLevel = 1;

    /**
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $suiteTimeout = 0;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $hoursToRun = 2;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $testTimeout = 0;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $mode = 'SINGLE_TEST';

    /**
     * @ORM\Column(type="array")
     */
    private $modeSettings = [];

    /**
     * @ORM\Column(type="text")
     */
    private $labels;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $chip;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $platform;

    /**
     * @ORM\Column(type="boolean")
     */
    private $alphabeticalOrder;

    /**
     * @ORM\Column(type="boolean")
     */
    private $randomOrder;

    /**
     * @ORM\Column(type="array")
     */
    private $parallel = [];

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\TestBookTestList", inversedBy="testBookSuites")
     */
    private $testLists;

    public function __construct()
    {
        $this->components = new ArrayCollection();
        $this->testLists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUuid(): ?string
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

    public function getSetupConfigPath(): ?string
    {
        return $this->setupConfigPath;
    }

    public function setSetupConfigPath(string $setupConfigPath): self
    {
        $this->setupConfigPath = $setupConfigPath;

        return $this;
    }

    public function getStopOnFail(): ?bool
    {
        return $this->stopOnFail;
    }

    public function setStopOnFail(bool $stopOnFail): self
    {
        $this->stopOnFail = $stopOnFail;

        return $this;
    }

    public function getStopOnError(): ?bool
    {
        return $this->stopOnError;
    }

    public function setStopOnError(bool $stopOnError): self
    {
        $this->stopOnError = $stopOnError;

        return $this;
    }

    public function getPackageMode(): ?string
    {
        return $this->packageMode;
    }

    public function setPackageMode(string $packageMode): self
    {
        $this->packageMode = $packageMode;

        return $this;
    }

    public function getProject(): ?string
    {
        return $this->project;
    }

    public function setProject(string $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getInterface(): ?string
    {
        return $this->interface;
    }

    public function setInterface(string $interface): self
    {
        $this->interface = $interface;

        return $this;
    }

    public function getTestingLevel(): ?int
    {
        return $this->testingLevel;
    }

    public function setTestingLevel(int $testing_level): self
    {
        $this->testingLevel = $testing_level;

        return $this;
    }

    public function getSuiteTimeout(): ?int
    {
        return $this->suiteTimeout;
    }

    public function setSuiteTimeout(int $suiteTimeout): self
    {
        $this->suiteTimeout = $suiteTimeout;

        return $this;
    }

    public function getHoursToRun(): ?int
    {
        return $this->hoursToRun;
    }

    public function setHoursToRun(int $hoursToRun): self
    {
        $this->hoursToRun = $hoursToRun;

        return $this;
    }

    public function getTestTimeout(): ?int
    {
        return $this->testTimeout;
    }

    public function setTestTimeout(int $testTimeout): self
    {
        $this->testTimeout = $testTimeout;

        return $this;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getModeSettings(): ?array
    {
        return $this->modeSettings;
    }

    public function setModeSettings(array $modeSettings): self
    {
        $this->modeSettings = $modeSettings;

        return $this;
    }

    public function getLabels(): ?string
    {
        return $this->labels;
    }

    public function setLabels(string $labels): self
    {
        $this->labels = $labels;

        return $this;
    }

    public function getChip(): ?string
    {
        return $this->chip;
    }

    public function setChip(string $chip): self
    {
        $this->chip = $chip;

        return $this;
    }

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform): self
    {
        $this->platform = $platform;

        return $this;
    }

    public function getAlphabeticalOrder(): ?bool
    {
        return $this->alphabeticalOrder;
    }

    public function setAlphabeticalOrder(bool $alphabeticalOrder): self
    {
        $this->alphabeticalOrder = $alphabeticalOrder;

        return $this;
    }

    public function getRandomOrder(): ?bool
    {
        return $this->randomOrder;
    }

    public function setRandomOrder(bool $randomOrder): self
    {
        $this->randomOrder = $randomOrder;

        return $this;
    }

    public function getParallel(): ?array
    {
        return $this->parallel;
    }

    public function setParallel(array $parallel): self
    {
        $this->parallel = $parallel;

        return $this;
    }

    /**
     * @return Collection|TestBookTestList[]
     */
    public function getTestLists(): Collection
    {
        return $this->testLists;
    }

    public function addTestList(TestBookTestList $testList): self
    {
        if (!$this->testLists->contains($testList)) {
            $this->testLists[] = $testList;
        }

        return $this;
    }

    public function removeTestList(TestBookTestList $testList): self
    {
        if ($this->testLists->contains($testList)) {
            $this->testLists->removeElement($testList);
        }

        return $this;
    }
}
