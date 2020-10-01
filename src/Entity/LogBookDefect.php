<?php

namespace App\Entity;

use App\Repository\LogBookDefectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LogBookDefectRepository::class)
 */
class LogBookDefect
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
    private $name = '';

    /**
     * @ORM\Column(type="text")
     */
    private $description = '';

    /**
     * @ORM\Column(type="boolean", options={"default"="0"})
     */
    private $isExternal = false;

    /**
     * @ORM\Column(type="boolean", options={"default"="0"})
     */
    private $isClosed = false;

    /**
     * @ORM\ManyToOne(targetEntity=LogBookUser::class, inversedBy="logBookDefects")
     */
    private $reporter;

    /**
     * @ORM\ManyToOne(targetEntity=LogBookProject::class, inversedBy="logBookDefects")
     */
    private $project;

    /**
     * @ORM\Column(type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $closedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $anotherReporter;

    /**
     * @ORM\Column(type="string", length=1500, nullable=true)
     */
    private $ext_url;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $ext_id;

    /**
     * @ORM\ManyToMany(targetEntity=LogBookCycleReport::class, mappedBy="defects")
     */
    private $logBookCycleReports;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->logBookCycleReports = new ArrayCollection();
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

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

    public function getClosedAt(): ?\DateTimeInterface
    {
        return $this->closedAt;
    }

    public function setClosedAt(\DateTimeInterface $closedAt): self
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function getIsExternal(): bool
    {
        return $this->isExternal;
    }

    public function setIsExternal(bool $isExternal): self
    {
        $this->isExternal = $isExternal;

        return $this;
    }

    public function getReporter(): ?LogBookUser
    {
        return $this->reporter;
    }

    public function setReporter(?LogBookUser $reporter): self
    {
        $this->reporter = $reporter;

        return $this;
    }

    public function __toString()
    {
        return $this->getId(). ':'. $this->getName();
    }

    public function getProject(): ?LogBookProject
    {
        return $this->project;
    }

    public function setProject(?LogBookProject $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getAnotherReporter(): ?string
    {
        return $this->anotherReporter;
    }

    public function setAnotherReporter(?string $anotherReporter): self
    {
        $this->anotherReporter = $anotherReporter;

        return $this;
    }

    public function getExtUrl(): ?string
    {
        return $this->ext_url;
    }

    public function setExtUrl(?string $ext_url): self
    {
        $this->ext_url = $ext_url;

        return $this;
    }

    public function getExtId(): ?string
    {
        return $this->ext_id;
    }

    public function setExtId(?string $ext_id): self
    {
        $this->ext_id = $ext_id;

        return $this;
    }

    public function getIsClosed(): ?bool
    {
        return $this->isClosed;
    }

    public function setIsClosed(bool $isClosed): self
    {
        $this->isClosed = $isClosed;

        return $this;
    }

    /**
     * @return Collection|LogBookCycleReport[]
     */
    public function getLogBookCycleReports(): Collection
    {
        return $this->logBookCycleReports;
    }

    public function addLogBookCycleReport(LogBookCycleReport $logBookCycleReport): self
    {
        if (!$this->logBookCycleReports->contains($logBookCycleReport)) {
            $this->logBookCycleReports[] = $logBookCycleReport;
            $logBookCycleReport->addDefect($this);
        }

        return $this;
    }

    public function removeLogBookCycleReport(LogBookCycleReport $logBookCycleReport): self
    {
        if ($this->logBookCycleReports->contains($logBookCycleReport)) {
            $this->logBookCycleReports->removeElement($logBookCycleReport);
            $logBookCycleReport->removeDefect($this);
        }

        return $this;
    }
}
