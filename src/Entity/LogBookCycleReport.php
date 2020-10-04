<?php

namespace App\Entity;

use App\Repository\LogBookCycleReportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LogBookCycleReportRepository::class)
 */
class LogBookCycleReport
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
     * @ORM\ManyToOne(targetEntity=LogBookUser::class, inversedBy="logBookCycleReports")
     */
    private $creator;

    /**
     * @ORM\Column(type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    private $updatedAt;

    /**
     * @ORM\ManyToMany(targetEntity=LogBookDefect::class, inversedBy="logBookCycleReports")
     */
    private $defects;

    /**
     * @ORM\ManyToMany(targetEntity=LogBookCycle::class, inversedBy="logBookCycleReports")
     */
    private $cycles;

    /**
     * @ORM\Column(type="text")
     */
    private $description = '';

    /**
     * @ORM\ManyToOne(targetEntity=LogBookBuild::class, inversedBy="logBookCycleReports")
     */
    private $build;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $period = 0;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $duration = 0;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $suitesCount = 0;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $testsCount = 0;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $testsPass = 0;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $testsFail = 0;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $testsError = 0;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $testsOther = 0;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $testsTotal = 0;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $platforms = [];

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $chips = [];

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $mode;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $components = [];



    public function __construct()
    {
        $this->defects = new ArrayCollection();
        $this->cycles = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function getCreator(): ?LogBookUser
    {
        return $this->creator;
    }

    public function setCreator(?LogBookUser $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection|LogBookDefect[]
     */
    public function getDefects(): Collection
    {
        return $this->defects;
    }

    public function addDefect(LogBookDefect $defect): self
    {
        if (!$this->defects->contains($defect)) {
            $this->defects[] = $defect;
        }

        return $this;
    }

    public function removeDefect(LogBookDefect $defect): self
    {
        if ($this->defects->contains($defect)) {
            $this->defects->removeElement($defect);
        }

        return $this;
    }

    /**
     * @return Collection|LogBookCycle[]
     */
    public function getCycles(): Collection
    {
        return $this->cycles;
    }

    public function addCycle(LogBookCycle $cycle): self
    {
        if (!$this->cycles->contains($cycle)) {
            $this->cycles[] = $cycle;
        }

        return $this;
    }

    public function removeCycle(LogBookCycle $cycle): self
    {
        if ($this->cycles->contains($cycle)) {
            $this->cycles->removeElement($cycle);
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getBuild(): ?LogBookBuild
    {
        return $this->build;
    }

    public function setBuild(?LogBookBuild $build): self
    {
        $this->build = $build;

        return $this;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function setPeriod(int $period): self
    {
        $this->period = $period;

        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getSuitesCount(): int
    {
        return $this->suitesCount;
    }

    public function setSuitesCount(int $suitesCount): self
    {
        $this->suitesCount = $suitesCount;

        return $this;
    }

    public function getTestsCount(): int
    {
        return $this->testsCount;
    }

    public function setTestsCount(int $testsCount): self
    {
        $this->testsCount = $testsCount;

        return $this;
    }

    public function getTestsPass(): int
    {
        return $this->testsPass;
    }

    public function setTestsPass(int $testsPass): self
    {
        $this->testsPass = $testsPass;

        return $this;
    }

    public function getTestsFail(): int
    {
        return $this->testsFail;
    }

    public function setTestsFail(int $testsFail): self
    {
        $this->testsFail = $testsFail;

        return $this;
    }

    public function getTestsError(): int
    {
        return $this->testsError;
    }

    public function setTestsError(int $testsError): self
    {
        $this->testsError = $testsError;

        return $this;
    }

    public function getTestsOther(): int
    {
        return $this->testsOther;
    }

    public function setTestsOther(int $testsOther): self
    {
        $this->testsOther = $testsOther;

        return $this;
    }

    public function getTestsTotal(): int
    {
        return $this->testsTotal;
    }

    public function setTestsTotal(int $testsTotal): self
    {
        $this->testsTotal = $testsTotal;

        return $this;
    }

    public function getPlatforms(): array
    {
        return $this->platforms;
    }

    public function setPlatforms(array $platforms): self
    {
        $this->platforms = $platforms;

        return $this;
    }

    public function getChips(): ?array
    {
        return $this->chips;
    }

    public function setChips(?array $chips): self
    {
        $this->chips = $chips;

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

    public function getComponents(): ?array
    {
        return $this->components;
    }

    public function setComponents(array $components): self
    {
        $this->components = $components;

        return $this;
    }

}
