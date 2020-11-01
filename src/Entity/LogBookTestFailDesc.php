<?php

namespace App\Entity;

use App\Repository\LogBookTestFailDescRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass=LogBookTestFailDescRepository::class)
 * @ORM\Table(name="lbook_test_fail_desc", indexes={
 *     @Index(name="description_index", columns={"description"}),
 *     @Index(name="md5_index", columns={"md5"}),
 *     @Index(name="ft_description_index", columns={"description"}, flags={"fulltext"})
 * }, uniqueConstraints={@ORM\UniqueConstraint(name="md5_unique", columns={"md5"})})
 */
class LogBookTestFailDesc
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(name="description", type="string", length=255, options={"default"=""})
     */
    private $description = '';

    /**
     * @ORM\Column(name="md5", type="string", length=64, options={"default"=""})
     */
    private $md5 = '';

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity=LogBookTest::class, mappedBy="failDesc", fetch="EXTRA_LAZY")
     */
    private $tests;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"unsigned"=true})
     */
    private $testsCount;

    /**
     * @ORM\Column(type="datetime", nullable=true, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $lastMarkedAsSeenAt;


    /**
     * LogBookTestFailDesc constructor.
     */
    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
        $this->setLastMarkedAsSeenAt($this->getCreatedAt());
        $this->tests = new ArrayCollection();
    }

    public static $MAX_DESC_LEN = 250;

    /**
     * @param $newDesc
     * @return string
     */
    public static function validateDescription($newDesc): string
    {
        if (mb_strlen($newDesc) > self::$MAX_DESC_LEN) {
            $newDesc = mb_substr($newDesc, 0, self::$MAX_DESC_LEN);
        }
        return $newDesc;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = LogBookTestFailDesc::validateDescription($description);
        $this->calculateHash();
        return $this;
    }

    public function calculateHash(){
        $this->setMd5(md5($this->getDescription()));
        return $this->getMd5();
    }

    public function getMd5(): string
    {
        return $this->md5;
    }

    public function setMd5(string $md5): self
    {
        $this->md5 = $md5;

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

    public function __toString()
    {
        return $this->getDescription();
    }

    /**
     * @return Collection|LogBookTest[]
     */
    public function getTests(): Collection
    {
        return $this->tests;
    }

    public function addTest(LogBookTest $test): self
    {
        if (!$this->tests->contains($test)) {
            $this->tests[] = $test;
            $test->setFailDesc($this);
        }

        return $this;
    }

    public function removeTest(LogBookTest $test): self
    {
        if ($this->tests->contains($test)) {
            $this->tests->removeElement($test);
            // set the owning side to null (unless already changed)
            if ($test->getFailDesc() === $this) {
                $test->setFailDesc(null);
            }
        }

        return $this;
    }

    public function getTestsCount(): int
    {
        if ($this->testsCount === null) {
            return 0;
        }
        return $this->testsCount;
    }

    public function setTestsCount(?int $testsCount): self
    {
        $this->testsCount = $testsCount;

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


}
