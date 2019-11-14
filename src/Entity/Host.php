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
 * @ORM\Table(name="hosts", uniqueConstraints={@ORM\UniqueConstraint(name="hostname_ip_uniq", columns={"name", "ip})},
 *  indexes={
 *     @Index(name="hostname_index", columns={"name"}),
 *     @Index(name="ip_index", columns={"ip"}),
 *     @Index(name="updated_at_index", columns={"updated_at"}),
 *     @Index(name="last_seen_at_index", columns={"last_seen_at"}),
 *     @Index(name="fulltext_custom", columns={"name", "ip"}, flags={"fulltext"}),
 *  })
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
     * @ORM\Column(type="string", length=150)
     */
    private $name = '';

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $ip = '';

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

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
        $this->lastSeenAt = new \DateTime();
        $this->suiteExecutions = new ArrayCollection();
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
}
