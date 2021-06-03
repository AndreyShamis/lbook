<?php

namespace App\Entity;

use App\Repository\CycleReportEditHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CycleReportEditHistoryRepository::class)
 */
class CycleReportEditHistory
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=LogBookUser::class)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=LogBookCycleReport::class, inversedBy="history")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $report;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $happenedAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $diff;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?LogBookUser
    {
        return $this->user;
    }

    public function setUser(?LogBookUser $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getReport(): ?LogBookCycleReport
    {
        return $this->report;
    }

    public function setReport(?LogBookCycleReport $report): self
    {
        $this->report = $report;

        return $this;
    }

    public function getHappenedAt(): ?\DateTimeInterface
    {
        return $this->happenedAt;
    }

    public function setHappenedAt(?\DateTimeInterface $happenedAt): self
    {
        $this->happenedAt = $happenedAt;

        return $this;
    }

    public function getDiff(): ?string
    {
        return $this->diff;
    }

    public function setDiff(?string $diff): self
    {
        $this->diff = $diff;

        return $this;
    }
}
