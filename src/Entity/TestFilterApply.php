<?php

namespace App\Entity;

use App\Repository\TestFilterApplyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TestFilterApplyRepository::class)
 * @ORM\Table(name="lbook_test_filter_apply")
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
     * @ORM\ManyToOne(targetEntity="App\Entity\TestFilter", inversedBy="testFilterApplies")
     * @ORM\JoinColumn(name="filter", fieldName="id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $testFilter;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\SuiteExecution")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="id", onDelete="SET NULL")
     */
    private $suiteExecution;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookTestInfo")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $testInfo;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="boolean", name="emailed", options={"default"="0"})
     */
    private $emailed = false;

    /**
     * @return bool
     */
    public function isEmailed(): bool
    {
        return $this->emailed;
    }

    /**
     * @param bool $emailed
     */
    public function setEmailed(bool $emailed): void
    {
        $this->emailed = $emailed;
    }


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

    public function __toString(): string
    {
        return $this->getId() . '-' . $this->getTestFilter()->getId() . '-' . $this->getTestInfo()->getName();
    }
}
