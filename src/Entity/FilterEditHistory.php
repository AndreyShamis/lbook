<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FilterEditHistoryRepository")
 */
class FilterEditHistory
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookUser")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TestFilter", inversedBy="filterEditHistories")
     * @ORM\JoinColumn(nullable=false)
     */
    private $testFilter;

    /**
     * @ORM\Column(type="datetime")
     */
    private $happenedAt;

    /**
     * @ORM\Column(type="text")
     */
    private $diff = '';

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

    public function getTestFilter(): ?TestFilter
    {
        return $this->testFilter;
    }

    public function setTestFilter(?TestFilter $testFilter): self
    {
        $this->testFilter = $testFilter;

        return $this;
    }

    public function getHappenedAt(): ?\DateTimeInterface
    {
        return $this->happenedAt;
    }

    public function setHappenedAt(\DateTimeInterface $happenedAt): self
    {
        $this->happenedAt = $happenedAt;

        return $this;
    }

    public function getDiff(): string
    {
        return $this->diff;
    }

    public function setDiff(string $diff): self
    {
        $this->diff = $diff;

        return $this;
    }
}
