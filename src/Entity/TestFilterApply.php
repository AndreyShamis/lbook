<?php

namespace App\Entity;

use App\Repository\TestFilterApplyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TestFilterApplyRepository::class)
 */
class TestFilterApply
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=TestFilter::class, inversedBy="testFilterApplies")
     */
    private $testFilter;

    /**
     * @ORM\ManyToOne(targetEntity=suiteExecution::class, cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $suiteExecution;

    /**
     * @ORM\ManyToOne(targetEntity=LogBookTestInfo::class)
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $testInfo;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;


    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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

    public function getSuiteExecution(): ?suiteExecution
    {
        return $this->suiteExecution;
    }

    public function setSuiteExecution(?suiteExecution $suiteExecution): self
    {
        $this->suiteExecution = $suiteExecution;

        return $this;
    }

    public function getTestInfo(): ?LogBookTestInfo
    {
        return $this->testInfo;
    }

    public function setTestInfo(?LogBookTestInfo $testInfo): self
    {
        $this->testInfo = $testInfo;

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
}
