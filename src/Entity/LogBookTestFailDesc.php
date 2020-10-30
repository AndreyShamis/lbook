<?php

namespace App\Entity;

use App\Repository\LogBookTestFailDescRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass=LogBookTestFailDescRepository::class)
 * @ORM\Table(name="lbooK_test_fail_desc", indexes={
 *     @Index(name="description_index", columns={"description"}),
 *     @Index(name="description_md5_index", columns={"description", "md5"}),
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
    public static $MAX_DESC_LEN = 250;

    public function __construct()
    {
        $this->setCreatedAt(new \DateTime());
    }

    public static function validateDescription($newDesc): string
    {
        if (mb_strlen($newDesc) > self::$MAX_DESC_LEN) {
            $newDesc = mb_substr($newDesc, 0, self::$MAX_DESC_LEN);
        }
        return $newDesc;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

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
}
