<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PreFlush;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass="App\Repository\HostRepository")
 * @ORM\Table(name="hosts", uniqueConstraints={@ORM\UniqueConstraint(
 *          name="hostname_ip_uniq",
 *          columns={"name", "ip"}
 *      )
 *  },
 *  indexes={
 *     @Index(name="h_ip_index", columns={"ip"}),
 *     @Index(name="h_updated_at_index", columns={"updated_at"}),
 *     @Index(name="h_last_seen_at_index", columns={"last_seen_at"}),
 *     @Index(name="fulltext_custom", columns={"name", "ip"}, flags={"fulltext"}),
 *  }
 * )
 */
class Host
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=150)
     */
    private $name;

    /**
     * @ORM\Column(name="ip", type="string", length=30)
     */
    private $ip;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(name="last_seen_at", type="datetime")
     */
    private $lastSeenAt;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SuiteExecution", mappedBy="host")
     */
    private $suiteExecutions;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\SuiteExecution", cascade={"persist"})
     */
    private $lastSuite;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $suitesCount;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $suitesPass;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $testsCount;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $testsFailed;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $testsError;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $testsExecuted;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $testsAndFlowsCount;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $testsAborted;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $testsNa;

    /**
     * @ORM\Column(name="target_label", type="string", length=50)
     */
    private $targetLabel;

    /**
     * @ORM\Column(name="target_labels", type="array")
     */
    private $targetLabels = [];

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
        $this->lastSeenAt = new \DateTime();
        $this->suiteExecutions = new ArrayCollection();
        $this->targetLabels = [];
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

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @PreFlush
     * @PrePersist
     * @throws \Exception
     */
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getLastSeenAt(): ?\DateTimeInterface
    {
        return $this->lastSeenAt;
    }

    public function setLastSeenAt(\DateTimeInterface $lastSeenAt): self
    {
        $this->lastSeenAt = $lastSeenAt;

        return $this;
    }

    /**
     * @return Collection|SuiteExecution[]
     */
    public function getSuiteExecutions(): Collection
    {
        return $this->suiteExecutions;
    }

    public function addSuiteExecution(SuiteExecution $suiteExecution): self
    {
        if (!$this->suiteExecutions->contains($suiteExecution)) {
            $this->suiteExecutions[] = $suiteExecution;
            $suiteExecution->setHost($this);
        }

        return $this;
    }

    public function removeSuiteExecution(SuiteExecution $suiteExecution): self
    {
        if ($this->suiteExecutions->contains($suiteExecution)) {
            $this->suiteExecutions->removeElement($suiteExecution);
            // set the owning side to null (unless already changed)
            if ($suiteExecution->getHost() === $this) {
                $suiteExecution->setHost(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getName() . ':' . $this->getIp();
    }

    public function getLastSuite(): ?SuiteExecution
    {
        return $this->lastSuite;
    }

    public function setLastSuite(?SuiteExecution $lastSuite): self
    {
        $this->lastSuite = $lastSuite;

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

    public function getSuitesPass(): int
    {
        return $this->suitesPass;
    }

    public function setSuitesPass(int $suitesPass): self
    {
        $this->suitesPass = $suitesPass;

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

    public function getTestsFailed(): int
    {
        return $this->testsFailed;
    }

    public function setTestsFailed(int $testsFailed): self
    {
        $this->testsFailed = $testsFailed;

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

    public function getTestsExecuted(): int
    {
        return $this->testsExecuted;
    }

    public function setTestsExecuted(int $testsExecuted): self
    {
        $this->testsExecuted = $testsExecuted;

        return $this;
    }

    public function getTestsAndFlowsCount(): int
    {
        return $this->testsAndFlowsCount;
    }

    public function setTestsAndFlowsCount(int $testsAndFlowsCount): self
    {
        $this->testsAndFlowsCount = $testsAndFlowsCount;

        return $this;
    }

    public function getTestsAborted(): int
    {
        return $this->testsAborted;
    }

    public function setTestsAborted(int $testsAborted): self
    {
        $this->testsAborted = $testsAborted;

        return $this;
    }

    public function getTestsNa(): int
    {
        return $this->testsNa;
    }

    public function setTestsNa(int $testsNa): self
    {
        $this->testsNa = $testsNa;

        return $this;
    }

    public function getTargetLabel(): string
    {
        return $this->targetLabel;
    }

    /**
     * @param string $targetLabel
     * @return $this
     */
    public function setTargetLabel(string $targetLabel): self
    {
        $this->targetLabel = $targetLabel;

        return $this;
    }

    /**
     * @return array
     */
    public function getTargetLabels(): array
    {
        $this->fix_array();
        return $this->targetLabels;
    }

    public function setTargetLabels(array $targetLabels): self
    {
        $this->fix_array();
        $this->targetLabels = $targetLabels;

        return $this;
    }

    protected function fix_array(): void
    {
        try {
            if ($this->targetLabels === null || \count($this->targetLabels) === 0) {
                $this->targetLabels = [];
            }
            if (gettype($this->targetLabels) === 'boolean') {
                $this->targetLabels = [];
            }
        } catch (\Throwable $ex) {
            $this->targetLabels = [];
        }
    }

    public function addTargetLabel(string $newLabel = null): self
    {
        $this->fix_array();
        if ($this->targetLabels === null) {
            $this->targetLabels = [];
        }
        if ($newLabel === null) {
            return $this;
        }
        if (strlen($newLabel) < 2 || strlen($newLabel) > 50) {
            return $this;
        }
        if (!in_array($newLabel, $this->targetLabels, true)) {
            array_unshift($this->targetLabels, $newLabel);
        }
        if (count($this->targetLabels) > 20) {
            array_pop($this->targetLabels);
        }

        return $this;
    }

    /**
     * @param array $newLabels
     */
    public function addTargetLabels(array $newLabels): void
    {
        foreach ($newLabels as $newLabel) {
            $this->addTargetLabel($newLabel);
        }
    }
}
