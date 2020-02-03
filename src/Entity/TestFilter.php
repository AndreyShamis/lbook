<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TestFilterRepository")
 */
class TestFilter
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, options={"default"=""})
     */
    private $name = '';

    /**
     * @ORM\Column(type="string", length=38, options={"default"=""})
     */
    private $suiteUuid = '';

    /**
     * @ORM\Column(type="string", length=255, options={"default"=""})
     */
    private $cluster = '';

    /**
     * @ORM\Column(type="string", length=320, options={"default"=""})
     */
    private $testList = '';

    /**
     * @ORM\Column(type="string", length=20, options={"default"="SANITY"})
     */
    private $testingLevel = 'SANITY';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookUser")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=150, options={"default"=""})
     */
    private $projectName = '';

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

    public function getSuiteUuid(): ?string
    {
        return $this->suiteUuid;
    }

    public function setSuiteUuid(string $suiteUuid): self
    {
        $this->suiteUuid = $suiteUuid;

        return $this;
    }

    public function getCluster(): ?string
    {
        return $this->cluster;
    }

    public function setCluster(string $cluster): self
    {
        $this->cluster = $cluster;

        return $this;
    }

    public function getTestList(): ?string
    {
        return $this->testList;
    }

    public function setTestList(string $testList): self
    {
        $this->testList = $testList;

        return $this;
    }

    public function getTestingLevel(): ?string
    {
        return $this->testingLevel;
    }

    public function setTestingLevel(string $testingLevel): self
    {
        $this->testingLevel = $testingLevel;

        return $this;
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

    public function getProjectName(): ?string
    {
        return $this->projectName;
    }

    public function setProjectName(string $projectName): self
    {
        $this->projectName = $projectName;

        return $this;
    }
}
