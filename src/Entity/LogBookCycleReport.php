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
    private $description;



    public function __construct()
    {
        $this->defects = new ArrayCollection();
        $this->cycles = new ArrayCollection();
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

}
