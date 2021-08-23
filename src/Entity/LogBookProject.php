<?php

namespace App\Entity;

use App\Repository\LogBookProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LogBookProjectRepository::class)
 * @ORM\Table(name="lbook_project")
 */
class LogBookProject
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
     * @ORM\OneToMany(targetEntity=LogBookDefect::class, mappedBy="project")
     */
    private $logBookDefects;

    public function __construct()
    {
        $this->logBookDefects = new ArrayCollection();
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

    /**
     * @return Collection|LogBookDefect[]
     */
    public function getLogBookDefects(): Collection
    {
        return $this->logBookDefects;
    }

    public function addLogBookDefect(LogBookDefect $logBookDefect): self
    {
        if (!$this->logBookDefects->contains($logBookDefect)) {
            $this->logBookDefects[] = $logBookDefect;
            $logBookDefect->setProject($this);
        }

        return $this;
    }

    public function removeLogBookDefect(LogBookDefect $logBookDefect): self
    {
        if ($this->logBookDefects->contains($logBookDefect)) {
            $this->logBookDefects->removeElement($logBookDefect);
            // set the owning side to null (unless already changed)
            if ($logBookDefect->getProject() === $this) {
                $logBookDefect->setProject(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getId(). ':'. $this->getName();
    }
}
