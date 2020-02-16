<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @ORM\Column(type="string", length=255, options={"default"="*"})
     */
    private $cluster = '*';

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
     * @ORM\Column(type="string", length=150, options={"default"="*"})
     */
    private $projectName = '*';

    /**
     * @ORM\Column(type="string", length=150, options={"default"="*"})
     */
    private $chip = '*';

    /**
     * @ORM\Column(type="string", length=150, options={"default"="*"})
     */
    private $platform = '*';

    /**
     * @ORM\Column(type="string", length=50, options={"default"="*"})
     */
    private $executionMode = '*';

    /**
     * @ORM\Column(type="string", length=255, options={"default"="*"})
     */
    private $branchName = '*';

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $defectUrl;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @Assert\Length(
     *      min = 5,
     *      max = 255,
     *      minMessage = "Please provide Person Of Contact",
     *      maxMessage = "The POC is too long"
     * )
     * @ORM\Column(type="string", length=255)
     */
    private $issueContact = '';

    public function __construct()
    {
        $this->setEnabled(true);
        $this->setCreatedAt(new \DateTime());
        $this->setUpdatedAt(new \DateTime());
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

    public function getSuiteUuid(): string
    {
        return $this->suiteUuid;
    }

    public function setSuiteUuid(string $suiteUuid): self
    {
        $this->suiteUuid = $suiteUuid;

        return $this;
    }

    public function getCluster(): string
    {
        return $this->cluster;
    }

    public function setCluster(string $cluster): self
    {
        $this->cluster = strtoupper($cluster);

        return $this;
    }

    public function getTestList(): string
    {
        return $this->testList;
    }

    public function setTestList(string $testList): self
    {

        $this->testList = $this->clean_tests_input($testList);

        return $this;
    }

    public function getTestingLevel(): string
    {
        return $this->testingLevel;
    }

    public function setTestingLevel(string $testingLevel): self
    {
        $this->testingLevel = strtoupper($testingLevel);

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

    public function getChip(): ?string
    {
        return $this->chip;
    }

    public function setChip(string $chip): self
    {
        $this->chip = $chip;

        return $this;
    }

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform): self
    {
        $this->platform = $platform;

        return $this;
    }

    public function getExecutionMode(): string
    {
        if ($this->executionMode === '') {
            $this->executionMode = '*';
        }
        return $this->executionMode;
    }

    public function setExecutionMode(string $executionMode): self
    {
        $this->executionMode = $executionMode;

        return $this;
    }

    public function getBranchName(): ?string
    {
        return $this->branchName;
    }

    public function setBranchName(string $branchName): self
    {
        $this->branchName = $branchName;

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

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

    public function getDefectUrl(): ?string
    {
        return $this->defectUrl;
    }

    public function setDefectUrl(string $defectUrl): self
    {
        $this->defectUrl = $defectUrl;

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

    public function getIssueContact(): ?string
    {
        return $this->issueContact;
    }

    public function setIssueContact(string $issueContact): self
    {
        $this->issueContact = $issueContact;

        return $this;
    }

    public function toJson()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'branch' => $this->getBranchName(),
            'suiteUuid' => $this->getSuiteUuid(),
            'tests' => $this->getTestList(),
            'cluster' => $this->getCluster(),
            'platform' => $this->getPlatform(),
            'chip' => $this->getChip(),
            'testing_level' => $this->getTestingLevel(),
            'project_name' => $this->getProjectName(),  // for Sanity only
            'execution_mode' => $this->getExecutionMode(),  // for regular/pack
            'owner' => $this->getUser()->getFullName(),
            'contact' => $this->getIssueContact(),
            'description' => $this->getDescription(),
            'defectUrl' => $this->getDefectUrl()
        ];
    }

    public function clean_tests_input(string $input=null): string
    {
        if ($input !== null) {
            $input = str_replace(' ', ',', $input);
            $input = str_replace("\n", ',', $input);
            $input = str_replace(", ", ',', $input);
            $input = str_replace(" , ", ',', $input);
            $input = str_replace(" ,", ',', $input);
            $input = str_replace(' ,', ',', $input);
            $input = str_replace(' ', ',', $input);
            $input = str_replace(' ', ',', $input);
        } else {
            $input = '';
        }
        return $input;
    }
}
