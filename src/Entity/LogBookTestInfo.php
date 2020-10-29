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
     * @ORM\OneToMany(targetEntity=LogBookTest::class, mappedBy="testInfo", fetch="EXTRA_LAZY")
     */
    private $logBookTests;

    public static $MAX_NAME_LEN = 250;
    public static $MAX_PATH_LEN = 500;

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

    public function __construct()
    {
        $this->logBookTests = new ArrayCollection();
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

    public function __toString()
    {
        $path = 'None';
        if ($this->getPath() !== null) {
            $path =  $this->getPath();
        }
        return $this->getName() .':'. $path;
    }
}
