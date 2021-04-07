<?php

namespace App\Entity;

use App\Repository\LogBookTestInfoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass=LogBookTestInfoRepository::class)
 * @ORM\Table(name="log_book_test_info", indexes={
 *     @Index(name="fulltext_name_path", columns={"name", "path"}, flags={"fulltext"})})
 */
class LogBookTestInfo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=250)
     */
    private $name = '';

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $path;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $testCount = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $lastMarkedAsSeenAt;

    /**
     * @ORM\OneToMany(targetEntity=LogBookTest::class, mappedBy="testInfo", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"id" = "desc"})
     */
    private $logBookTests;

    /**
     * @ORM\Column(type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $lastUpdateDiff = 0;

    public static $MAX_NAME_LEN = 250;
    public static $MAX_PATH_LEN = 500;

    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
        $this->setLastMarkedAsSeenAt($this->getCreatedAt());
        $this->logBookTests = new ArrayCollection();
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

    public function getLastMarkedAsSeenAt(): ?\DateTimeInterface
    {
        return $this->lastMarkedAsSeenAt;
    }

    public function setLastMarkedAsSeenAt(\DateTimeInterface $lastMarkedAsSeenAt): self
    {
        $this->lastMarkedAsSeenAt = $lastMarkedAsSeenAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getLastUpdateDiff(): int
    {
        return $this->lastUpdateDiff;
    }

    /**
     * @param int $lastUpdateDiff
     */
    public function setLastUpdateDiff(int $lastUpdateDiff): self
    {
        $this->lastUpdateDiff = $lastUpdateDiff;

        return $this;
    }

    public static function validateName($newName): string
    {
        if (mb_strlen($newName) > self::$MAX_NAME_LEN) {
            $newName = mb_substr($newName, 0, self::$MAX_NAME_LEN);
        }
        return $newName;
    }

    /**
     * @param string|null $newPath
     * @return string|null
     */
    public static function validatePath(string $newPath = null): ? string
    {
        if ( $newPath === null ) {
            return $newPath;
        } elseif (mb_strlen($newPath) > self::$MAX_PATH_LEN) {
            $newPath = mb_substr($newPath, 0, self::$MAX_PATH_LEN);
        }
        return $newPath;
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
        $this->name = self::validateName($name);

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        if ($path !== null) {
            $this->path = self::validatePath($path);
        } else {
            $this->path = $path;
        }

        return $this;
    }

    /**
     * @return Collection|LogBookTest[]
     */
    public function getLogBookTests(): Collection
    {
        return $this->logBookTests;
    }

    public function addLogBookTest(LogBookTest $logBookTest): self
    {
        if (!$this->logBookTests->contains($logBookTest)) {
            $this->logBookTests[] = $logBookTest;
            $logBookTest->setTestInfo($this);
        }

        return $this;
    }

    public function removeLogBookTest(LogBookTest $logBookTest): self
    {
        if ($this->logBookTests->contains($logBookTest)) {
            $this->logBookTests->removeElement($logBookTest);
            // set the owning side to null (unless already changed)
            if ($logBookTest->getTestInfo() === $this) {
                $logBookTest->setTestInfo(null);
            }
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getTestCount(): int
    {
        return $this->testCount;
    }

    /**
     * @param int $testCount
     */
    public function setTestCount(int $testCount): self
    {
        $this->testCount = $testCount;
        return $this;
    }

    public function __toString()
    {
        $path = 'None';
        if ($this->getPath() !== null) {
            $path =  $this->getPath();
        }
        return $this->getName() .':'. $path;
    }
}
